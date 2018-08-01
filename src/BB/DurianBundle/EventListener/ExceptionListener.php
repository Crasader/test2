<?php

namespace BB\DurianBundle\EventListener;

use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\Console\Event\ConsoleExceptionEvent;
use Doctrine\ORM\OptimisticLockException;

/**
 * Exception listener for customizing exception handling
 *
 * @author sliver <sliver@mail.cgs01.com>
 */
class ExceptionListener extends ContainerAware
{
    /**
     * @var float
     */
    private $startTime;

    /**
     * @var boolean
     */
    private $dbError = false;

    /**
     * @param GetResponseForExceptionEvent $event
     */
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $request = $event->getRequest();

        $pathInfo = $request->getPathInfo();

        // 暫時利用網址開頭/api/判定需輸出json格式的錯誤訊息，避免使用者沒代到format
        if (preg_match_all("/^\/api\//", $pathInfo)) {
            $request->setRequestFormat('json');
        }

        // 非api輸出預設錯誤畫面
        if ($request->getRequestFormat() != 'json') {
            return;
        }

        $language = $request->getPreferredLanguage();

        $translator = $this->container->get('translator');
        $translator->setLocale($language);

        $exception = $event->getException();

        $code = $exception->getCode();
        $message = $exception->getMessage();

        if ($exception instanceof OptimisticLockException) {
            $code = 150010071;
            $message = 'Database is busy';
        }

        $value = array();
        $value['result'] = 'error';
        $value['code'] = $code;
        $value['msg'] = $translator->trans($message);

        if (isset($exception->getPrevious()->errorInfo)) {
            $this->dbError = true;
        }

        $content = json_encode($value);

        //送訊息至 italking
        $italkingWorker = $this->container->get('durian.italking_worker');
        $exceptionType = get_class($exception);
        $allType = [
            'InvalidArgumentException',
            'DomainException',
            'RuntimeException',
            'RangeException',
            'Doctrine\ORM\OptimisticLockException',
            'BB\DurianBundle\Exception\PaymentException',
            'BB\DurianBundle\Exception\PaymentConnectionException'
        ];
        $method = $request->getMethod();
        $param = $request->request->all();

        if ($method == 'GET') {
            $param = $request->query->all();
        }

        //密碼加上遮罩
        $param = $this->maskPassword($param);

        $paramString = urldecode(http_build_query($param));
        $server = gethostname();
        $client = $request->getClientIp();
        $now = date('Y-m-d H:i:s');

        // 當Redis連線異常時，避免再推queue到Redis出同樣的錯
        try {
            if (!in_array($exceptionType, $allType) && $message) {
                $italkingWorker->push([
                    'type' => 'developer_acc',
                    'message' => "[$server] [$now] $method $pathInfo REQUEST: $paramString Failed, ErrorMessage: $message [$client]",
                    'exception' => $exceptionType
                ]);
            }
        } catch (\Exception $e) {
            if ($e->getCode() != $exception->getCode()) {
                $logger = $this->container->get('durian.logger_manager')->setUpLogger('event_listener_error.log');
                $logger->addInfo($e->getMessage());
                $logger->popHandler()->close();
            }
        }

        /**
         * As Symfony ensures that the Response status code is set to
         * the most appropriate one depending on the exception,
         * setting the status on the response won't work.
         * If you want to overwrite the status code (which you should not
         * without a good reason), set the X-Status-Code header:
         */

        $statusCode = 200;

        if (!in_array($exceptionType, $allType)) {
            $statusCode = 500;
        }

        $response = new Response($content, $statusCode, ['X-Status-Code' => $statusCode]);

        $event->setResponse($response);
    }

    /**
     * 記錄開始時間
     */
    public function onKernelRequest()
    {
        $this->startTime = microtime(true);
    }

    /**
     * @param FilterResponseEvent $event
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        // ROUTE帶錯時，並不會由onKernelRequest()設定startTime，須另外設定。
        if (!$this->startTime) {
            $this->startTime = microtime(true);
        }

        $request = $event->getRequest();

        $pathInfo = $request->getPathInfo();

        // 暫時利用網址開頭/api/判定需輸出json格式的訊息，避免使用者沒代到format
        if (preg_match_all("/^\/api\//", $pathInfo)) {
            $request->setRequestFormat('json');
        }

        // 非api輸出預設畫面
        if ($request->getRequestFormat() != 'json') {
            return;
        }

        $content = $event->getResponse()->getContent();

        $value = json_decode($content, true);

        //紀錄POST LOG
        if ($request->getMethod() != 'GET') {
            $this->writePostLog($request, $value);
        }

        if ($this->dbError) {
            $value['code'] = 150780001;
            $value['msg'] = 'Database is busy';
        }

        $value['profile']['execution_time'] = round((microtime(true) - $this->startTime) * 1000);
        $value['profile']['server_name'] = gethostname();

        $content = json_encode($value);

        $event->getResponse()->setContent($content);
    }

    /**
     * @param FilterControllerEvent $event
     */
    public function onKernelController(FilterControllerEvent $event)
    {
        $controller = $event->getController();

        /*
         * $controller passed can be either a class or a Closure.
         * This is not usual in Symfony but it may happen.
         * If it is a class, it comes in array format
         */
        if (!is_array($controller)) {
            return;
        }

        $request = $event->getRequest();
        $pathInfo = $request->getPathInfo();

        // 利用網址開頭/api/判定需先檢查header所帶入的session-id是否正確
        if (!preg_match_all("/^\/api\//", $pathInfo)) {
            return;
        }

        $method = $request->getMethod();

        // 這兩隻API不用驗證:PUT /api/login、PUT /api/oauth/login
        if (preg_match_all("/^\/api\/(oauth\/)?login$/", $pathInfo) && $method == 'PUT') {
            return;
        }

        $header = $request->headers;
        $verifySession = $header->get('verify-session');

        // 如果verify-session帶1,將session放入attribute,供API驗證
        if ($verifySession) {
            $sessionBroker = $this->container->get('durian.session_broker');

            $sessionData = $sessionBroker->getBySessionId($header->get('session-id'));
            $request->attributes->set('verified_user', $sessionData['user']);
        }
    }

    /**
     * 寫post log，需要記錄的資訊有
     * server ip, client ip, method, uri, 傳入參數 回傳結果
     *
     * @param \Symfony\Component\HttpFoundation\ParameterBag $request
     * @param array $output
     */
    public function writePostLog($request, $output)
    {
        $server = gethostname();
        $clientIp = $request->getClientIp();
        $method = $request->getMethod();
        $uri = $request->getPathInfo();
        $param = $request->request->all();

        //密碼加上遮罩
        $param = $this->maskPassword($param);

        // 因支付平台3KPay將回調Key存入merchant_extra，LOG Request需將密鑰做遮蔽
        if (isset($param['merchant_extra'])) {
            foreach ($param['merchant_extra'] as $key => $value) {
                if ($value['name'] == 'verifyKey') {
                    $param['merchant_extra'][$key]['value'] = '******';

                    break;
                }
            }
        }

        // 因支付平台3KPay將回調Key存入merchant_extra，LOG Response需將密鑰做遮蔽
        if (isset($output['ret']) && is_array($output['ret'])) {
            foreach ($output['ret'] as $key => $value) {
                if (isset($value['name']) && $value['name'] == 'verifyKey') {
                    $output['ret'][$key]['value'] = '******';

                    break;
                }
            }
        }

        $paramString = urldecode(http_build_query($param));
        $outputString = urldecode(http_build_query($output));

        $logContent = sprintf(
            '%s %s "%s %s" "REQUEST: %s" "RESPONSE: %s"',
            $server,
            $clientIp,
            $method,
            $uri,
            $paramString,
            $outputString
        );

        $env = $this->container->get('kernel')->getEnvironment();

        $logger = $this->container->get('durian.logger_manager')->setUpLogger('post.log');
        $logger->addInfo($logContent);
        $logger->popHandler()->close();
    }

    /**
     * 觸發 console exception event 寫入時間
     *
     * @param ConsoleExceptionEvent $event
     */
    public function onConsoleException(ConsoleExceptionEvent $event)
    {
        $output = $event->getOutput();

        $now = new \DateTime();

        $output->write($now->format('Y-m-d H:i:s'));
    }

    /**
     * 密碼增加遮罩
     *
     * @param array $param
     * @return array
     */
    private function maskPassword($param)
    {
        if (isset($param['password'])) {
            $param['password'] = '******';
        }

        if (isset($param['private_key'])) {
            $param['private_key'] = '******';
        }

        if (isset($param['old_password'])) {
            $param['old_password'] = '******';
        }

        if (isset($param['new_password'])) {
            $param['new_password'] = '******';
        }

        if (isset($param['confirm_password'])) {
            $param['confirm_password'] = '******';
        }

        if (isset($param['api_key'])) {
            $param['api_key'] = '******';
        }

        // 比特幣第二密碼遮罩
        if (isset($param['second_password'])) {
            $param['second_password'] = '******';
        }

        // 比特幣出款公鑰
        if (isset($param['xpub'])) {
            $param['xpub'] = '******';
        }

        // 比特幣api碼
        if (isset($param['api_code'])) {
            $param['api_code'] = '******';
        }

        // 商家公鑰
        if (isset($param['public_key_content'])) {
            $param['public_key_content'] = '******';
        }

        // 商家私鑰
        if (isset($param['private_key_content'])) {
            $param['private_key_content'] = '******';
        }

        return $param;
    }

}

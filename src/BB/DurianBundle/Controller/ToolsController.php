<?php

namespace BB\DurianBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Input\ArrayInput;
use BB\DurianBundle\Entity\Test;
use BB\DurianBundle\Service\Command\ReOpHelper;
use BB\DurianBundle\Share\ValidateShareLimitHelper;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Buzz\Message\Form\FormRequest;
use Buzz\Message\Response as CurlResponse;
use Buzz\Client\Curl;
use Seta0909\LaravelZhconverter\LaravelZhconverter;
use Symfony\Component\HttpFoundation\Request;
use Dapphp\Radius\Radius;

class ToolsController extends Controller
{
    /**
     * @var Dapphp\Radius\Radius
     */
    private $radius;

    /**
     * @param Dapphp\Radius\Radius $radius
     */
    public function setRadius($radius)
    {
        $this->radius = $radius;
    }

    /**
     * @Route("/api/validate_share_limit", name = "api_validate_share_limit")
     * @method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function validateShareLimitAction(Request $request)
    {
        $helper = new ValidateShareLimitHelper($this->container);
        $query = $request->query;
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');

        //設定logger
        $env = $this->container->getParameter('kernel.environment');
        $logsDir = $this->container->getParameter('kernel.logs_dir') . DIRECTORY_SEPARATOR . $env;
        $logger = $this->container->get('durian.logger_manager')->setUpLogger('validate_sl.log');

        $csvPath = $logsDir . DIRECTORY_SEPARATOR . 'validate_sharelimit_output.csv';
        $fileOpen = fopen($csvPath, 'a');

        $depth = $query->get('depth');
        $domain = $query->get('domain');
        $isDisable = $query->get('disable', false);
        $next = $query->get('next', false);
        $fix = false;
        $order = 'asc';
        $domains = array();
        $domains = $helper->loadDomains($domain);
        $errorMsgs = null;
        foreach ($domains as $domain) {
            $count = $helper->countChildOf($domain, $depth, $isDisable);
            $totalPage = $helper->getTotalPage($count);

            $domainId = $domain->getId();
            $msg = "Validate Domain:$domainId Depth: $depth Childs: $count ShareLimit";
            $logger->addInfo($msg);
            $config = $emShare->find('BBDurianBundle:DomainConfig', $domainId);

            for ($page = 1; $page <= $totalPage; $page++) {
                $firstResult = $helper->processRecodes($page, $totalPage);
                $users = $helper->getChildOf($domain, $isDisable, $firstResult, $order);
                $errorMsgs = $helper->processUsers(
                    $users,
                    $next,
                    $fix,
                    $config->getName(),
                    $logger,
                    $fileOpen
                );

                //清除em，釋放已使用完的users變數資料
                unset($users);
                $this->getEntityManager()->clear();
            }
        }

        //如有錯誤資訊便印出
        $output['Result'] = 'No error';
        if ($errorMsgs) {
            $output['Result'] = $errorMsgs;
        }

        fclose($fileOpen);
        $logger->addInfo('ValidateShareLimitCommand finish.');
        $logger->popHandler()->close();

        return new JsonResponse($output);
    }

    /**
     * Check environment
     *
     * @Route("/tools/check", name = "tools_check")
     *
     * @param Request $request
     * @return Renders
     */
    public function checkAction(Request $request)
    {
        $problems = array();

        if ($request->getMethod() == 'POST') {
            $problems['major'] = array();
            $problems['minor'] = array();

            $problems = array_merge_recursive($problems, $this->checkMinimum());
            $problems = array_merge_recursive($problems, $this->checkExtensions());
            $problems = array_merge_recursive($problems, $this->checkPhpIni());
            $problems = array_merge_recursive($problems, $this->checkRedis());
            $problems = array_merge_recursive($problems, $this->checkRedisSeq());
            $problems = array_merge_recursive($problems, $this->checkRedisInvalidSeq());
            $problems = array_merge_recursive($problems, $this->checkSessionMaintain());
            $problems = array_merge_recursive($problems, $this->checkSessionWhitelist());

            if (extension_loaded('pdo_mysql')) {
                $problems = array_merge_recursive($problems, $this->checkActivateSLNext());
                $problems = array_merge_recursive($problems, $this->checkDb());
            }
        }

        return $this->render(
            'BBDurianBundle:Default/Tools/CheckEnv:index.html.twig',
            array('problems' => $problems)
        );
    }

    /**
     * 顯示廳主與domain編號對應表
     *
     * @Route("/tools/domain_map", name="tools_domain_map")
     * @Method({"GET"})
     *
     * @param Request $request
     * @return Renders, JsonResponse
     */
    public function domainMapAction(Request $request)
    {
        $query = $request->query;

        $domainName = $query->get('domainName');
        $domainLoginCode = $query->get('domainLoginCode');
        $page = $query->get('page', 1);
        $enable = $query->get('enable', 1);

        if (!preg_match('/%/', $domainName)) {
            $domainName = '%' . $domainName . '%';
        }

        if (!preg_match('/%/', $domainLoginCode)) {
            $domainLoginCode = '%' . $domainLoginCode . '%';
        }

        $maxResults = 20;
        $firstResult = ($page - 1) * $maxResults;

        $em = $this->getEntityManager();
        $repository = $em->getRepository('BBDurianBundle:User');
        $domainMap = $this->getDomainMap(
            $domainName,
            $domainLoginCode,
            $enable,
            $firstResult,
            $maxResults
        );

        //若繁中找不到結果則改以簡中搜尋
        if (empty($domainMap)) {
            $domainName = LaravelZhconverter::translate($domainName, 'CN');

            $domainMap = $this->getDomainMap(
                $domainName,
                $domainLoginCode,
                $enable,
                $firstResult,
                $maxResults
            );
        }

        $num = $this->countDomainMap($domainName, $domainLoginCode, $enable);

        $totalPage = ceil($num / $maxResults);

        $response = [
            'domain_map' => $domainMap,
            'total' => $num,
            'total_page' => $totalPage
        ];

        //若收到ajax的request則回傳search結果
        if ($request->isXmlHttpRequest()) {
            return new JsonResponse($response);
        }

        return $this->render(
            'BBDurianBundle:Default/Tools:domainMap.html.twig',
            $response
        );
    }

    /**
     * 更新佔成
     *
     * @Route("/tools/update_share_limit",
     *        name = "api_update_share_limit",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @return JsonResponse
     */
    public function updateSharelimitAction()
    {
        $arguments = array(
            'command' => 'durian:cronjob:activate-sl-next',
        );
        $commandInput  = new ArrayInput($arguments);
        $commandOutput = new NullOutput();

        $app = new Application($this->get('kernel'));
        $app->setAutoExit(false);
        $status = $app->run($commandInput, $commandOutput);

        $output['result'] = 'error';
        if ($status == 0) {
            $output['result'] = 'ok';
        }

        return new JsonResponse($output);
    }

    /**
     * 測試廳主與子帳號OTP SERVER連線
     *
     * @Route("/api/tools/otp_server_connection",
     *        name = "api_otp_server_connection",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function otpServerConnectionAction(Request $request)
    {
        $em = $this->getEntityManager();
        $otpWorker = $this->container->get('durian.otp_worker');

        $query = $request->query;
        $domain = $query->get('domain');
        $sub = (bool) $query->get('sub', 0);
        $otpToken = $query->get('otp_token', '');
        $otpFree = (bool) $query->get('otp_free', 0);
        $userId = 0;

        if ($otpFree) {
            // 測試用靜態帳密，不用輸入OTP
            $domain = $this->container->getParameter('static_otp_username');
            $otpToken = $this->container->getParameter('static_otp_password');
        }

        $otpUser = $domain;

        if (!$otpFree && $sub) {
            $otpUser = $domain . '_sub';
        }

        try {
            $result = $otpWorker->getOtpResult($otpUser, $otpToken, $userId, $domain);

            $output['result'] = 'ok';

            if (!$result['response'] && $result['error'] == 'Access rejected (3)') {
                $output['ret']['password_verify'] = false;
                $output['ret']['msg'] = 'OTP 伺服器連線正常，回應訊息為: 帳密錯誤';

                return new JsonResponse($output);
            }

            if ($result['response']) {
                $output['ret']['password_verify'] = true;
                $output['ret']['msg'] = 'OTP 伺服器連線正常，回應訊息為: 帳密正確';
            } else {
                $msg = 'OTP 伺服器連線異常，回應訊息為: ' . $result['error'];

                throw new \RuntimeException($msg, 150170034);
            }
        } catch (\Exception $e) {
            $output['result'] = 'error';
            $output['code'] = $e->getCode();
            $output['msg'] = $e->getMessage();
        }

        return new JsonResponse($output);
    }

    /**
     * 列出裝置所有綁定使用者
     *
     * @Route("/api/tools/device/users",
     *        name = "api_tools_get_binding_users_by_device",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function listBindingUsersByDeviceAction(Request $request)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $query = $request->query;

        $appId = $query->get('app_id');

        if (!$appId) {
            throw new \InvalidArgumentException('No app_id specified', 150170035);
        }

        $device = $emShare->getRepository('BBDurianBundle:SlideDevice')
            ->findOneByAppId($appId);

        if (!$device) {
            throw new \RuntimeException('The device has not been bound', 150170036);
        }

        $userIds = [];

        foreach ($device->getBindings() as $binding) {
            $userIds[] = $binding->getUserId();
        }

        $users = $em->getRepository('BBDurianBundle:User')->findById($userIds);

        $output['result'] = 'ok';
        $output['ret'] = [];

        foreach ($users as $user) {
            $output['ret'][] = [
                'user_id' => $user->getId(),
                'username' => $user->getUsername(),
                'domain' => $user->getDomain()
            ];
        }

        return new JsonResponse($output);
    }

    /**
     * 測試執行速度
     *
     * @Route("/tools/check_speed", name="tools_check_speed")
     *
     * @param Request $request
     * @return Response
     */
    public function checkSpeedAction(Request $request)
    {
        $executionTime = '';
        $serverName = '';

        if ($request->getMethod() == 'POST') {
            $startTime = microtime(true);
            $serverName = gethostname();

            $em = $this->getEntityManager();
            $em->find('BBDurianBundle:BackgroundProcess', 1);

            $executionTime = round((microtime(true) - $startTime) * 1000);
        }

        $params = [
            'execution_time' => $executionTime,
            'server_name' => $serverName
        ];

        return $this->render(
            'BBDurianBundle:Default/Tools/CheckSpeed:index.html.twig',
            $params
        );
    }

    /**
     * 測試線上支付工具
     *
     * @Route("/tools/deposit/check",
     *        name = "tools_deposit_check")
     * @Method({"GET"})
     *
     * @return Response
     */
    public function depositCheckAction()
    {
        return $this->render('BBDurianBundle:Default/Tools:depositCheck.html.twig');
    }

    /**
     * 測試線上支付
     *
     * @Route("/tools/deposit/test",
     *        name = "tools_deposit_test",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"POST"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function depositTestAction(Request $request)
    {
        $em = $this->getEntityManager();

        $post = $request->request;
        $ip = $this->container->get('request')->getClientIp();

        $merchantId = $post->get('merchant_id');
        $domain = $post->get('domain');
        $amount = $post->get('amount');
        $currency = $post->get('currency');
        $paymentVendorId = $post->get('payment_vendor_id');
        $requestUrl = $post->get('request_url');
        $notifyUrl = $post->get('notify_url');
        $lang = $post->get('lang');
        $serverIp = $post->get('server_ip');
        $abandonOffer = $post->get('abandon_offer', 0);

        if (!$amount) {
            throw new \InvalidArgumentException('No amount specified', 150170017);
        }

        if (!$currency) {
            throw new \InvalidArgumentException('Currency can not be null', 150170019);
        }

        $paymentVendor = $em->find('BBDurianBundle:PaymentVendor', $paymentVendorId);
        if (!$paymentVendor) {
            throw new \RuntimeException('No PaymentVendor found', 150170016);
        }

        $domainUser = $em->find('BBDurianBundle:User', $domain);

        if (!$domainUser) {
            throw new \RuntimeException('No such user', 150170012);
        }

        if (!is_null($domainUser->getParent())) {
            throw new \RuntimeException('Not a domain', 150170027);
        }

        // 如果有代入user_id，則用指定的user
        $user = null;
        if ($post->has('user_id')) {
            $user = $em->find('BBDurianBundle:User', $post->get('user_id'));
        }

        // 如果沒有撈到user，自動搜尋php1test
        if (!$user) {
            $userCriteria = [
                'username' => 'php1test',
                'domain' => $domain
            ];

            $user = $em->getRepository('BBDurianBundle:User')->findOneBy($userCriteria);
        }

        // 沒有user就丟例外
        if (!$user) {
            throw new \RuntimeException('No such user', 150170012);
        }

        // user的domain和填入的domain不同就丟例外
        if ($user->getDomain() != $domain) {
            throw new \InvalidArgumentException('Invalid domain', 150170011);
        }

        if ($merchantId) {
            $merchant = $em->find('BBDurianBundle:Merchant', $merchantId);

            if (!$merchant) {
                throw new \RuntimeException('No Merchant found', 150170014);
            }

            // 如果user與商號的domain不同就丟例外
            if ($merchant->getDomain() != $user->getDomain()) {
                throw new \InvalidArgumentException('Invalid domain', 150170011);
            }
        }

        // 如果沒有cash就丟例外
        if (!$user->getCash()) {
            throw new \RuntimeException('No cash found', 150170013);
        }

        // 金流入款
        $depositParams = [
            'merchant_id' => $merchantId,
            'currency' => $currency,
            'payway' => 1,
            'amount' => $amount,
            'payment_vendor_id' => $paymentVendorId,
            'ip' => $ip,
            'abandon_offer' => $abandonOffer,
        ];

        $userId = $user->getId();
        $host = $request->getHost();
        $depositResult = $this->curlRequest('POST', "/api/user/$userId/deposit", $depositParams, $serverIp, $host);

        if ($depositResult['result'] === 'error') {
            $code = $depositResult['code'];
            $msg = $depositResult['msg'];

            throw new \RuntimeException($msg, $code);
        }

        $entryId = $depositResult['ret']['deposit_entry']['id'];
        $merchantId = $depositResult['ret']['deposit_entry']['merchant_id'];

        // 建立使用者 Captcha 資料
        $captchaData = [
            'identifier' => $entryId,
            'length' => 8,
        ];

        $captchaResult = $this->curlRequest('POST', "/api/user/$userId/captcha", $captchaData, $serverIp, $host);

        if ($captchaResult['result'] === 'error') {
            $code = $captchaResult['code'];
            $msg = $captchaResult['msg'];

            throw new \RuntimeException($msg, $code);
        }

        $captcha = $captchaResult['ret'];

        // 根據使用者編號，回傳 Session 資料
        $sessionResult = $this->curlRequest('GET', "/api/user/$userId/session", [], $serverIp, $host);

        if ($sessionResult['result'] === 'error') {
            $sessionResult = $this->curlRequest('POST', "/api/user/$userId/session", [], $serverIp, $host);

            if ($sessionResult['result'] === 'error') {
                $code = $sessionResult['code'];
                $msg = $sessionResult['msg'];

                throw new \RuntimeException($msg, $code);
            }
        }

        $sessionId = $sessionResult['ret']['session']['id'];

        $merchant = $em->find('BBDurianBundle:Merchant', $merchantId);
        $shopUrl = $merchant->getShopUrl();

        if ($requestUrl == '') {
            if ($shopUrl == '') {
                throw new \RuntimeException('No shop_url found', 150170028);
            }

            $requestUrl = $shopUrl . 'pay.php';
        }

        if ($notifyUrl == '') {
            if ($shopUrl == '') {
                throw new \RuntimeException('No shop_url found', 150170028);
            }

            $notifyUrl = $shopUrl;
        }

        $payData = [
            'ip' => $ip,
            'entry_id' => $entryId,
            'captcha' => $captcha,
            'notify_url' => $notifyUrl,
            'lang' => $lang,
            'BBSESSID' => $sessionId,
        ];

        $output = [];
        $output['result'] = 'ok';
        $output['ret']['request_url'] = $requestUrl;
        $output['ret']['params'] = $payData;

        return new JsonResponse($output);
    }

    /**
     * 顯示背景程式名稱
     *
     * @Route("/tools/display_background_process_name",
     *        name = "tools_display_background_process_name")
     * @Method({"GET"})
     *
     * @return Render
     */
    public function displayBgProcessNameAction()
    {
        $em = $this->getEntityManager();
        $repo = $em->getRepository('BBDurianBundle:BackgroundProcess');

        $allProcess = $repo->findAll();
        $allProcessName = [];

        foreach ($allProcess as $process) {
            $allProcessName[] = $process->getName();
        }

        return $this->render(
            'BBDurianBundle:Default/Tools:setBackgroundProcess.html.twig',
            ['allProcessName' => $allProcessName]
        );
    }

    /**
     * 修正背景執行數量和啟用狀態
     *
     * @Route("/tools/set_background_process",
     *        name = "tools_set_background_process",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function setBgProcessAction(Request $request)
    {
        $validator = $this->get('durian.validator');
        $bgMonitor = $this->get('durian.monitor.background');

        $post = $request->request;
        $processName = $post->get('process');
        $processNum = $post->get('num');
        $processEnable = $post->get('enable');
        $output = [];

        // 檢查帶入的值是否合法，並剔除未選程式的欄位
        foreach ($processName as $key => $name) {
            if ($name == null ) {
                if ($processNum[$key] != null || $processEnable[$key] != null) {
                    throw new \InvalidArgumentException('No name of process, number or enable specified', 150170008);
                }
            }

            if ($name != null ) {
                if ($processNum[$key] == null && $processEnable[$key] == null) {
                    throw new \InvalidArgumentException('No name of process, number or enable specified', 150170008);
                }
            }

            // 背景名稱為空則剔除
            if ($name == null) {
                unset($processName[$key]);
                unset($processNum[$key]);
                unset($processEnable[$key]);
                continue;
            }

            if ($processNum[$key] != null && !$validator->isInt($processNum[$key])) {
                throw new \InvalidArgumentException('Invalid number', 150170009);
            }
        }

        // 未選擇任何程式或皆未帶參數就return
        if (!$processName) {
            return new JsonResponse($output);
        }

        // 取得程式的data
        $em = $this->getEntityManager();
        $repo = $em->getRepository('BBDurianBundle:BackgroundProcess');
        $allProcess = $repo->findBy(['name' => $processName]);

        $em->beginTransaction();

        try {
            foreach ($allProcess as $process) {
                $key = array_search($process->getName(), $processName);

                if ($processNum[$key] != null) {
                    $checkNum = $processNum[$key] + $process->getNum();

                    // 調整後的執行數量須大於等於0
                    if ($checkNum < 0) {
                        throw new \RuntimeException('Process number can not be set below 0', 150170010);
                    }

                    // 更改執行數量
                    $bgMonitor->setBgProcessNum($process->getName(), $processNum[$key]);

                    // 將結果收集起來
                    $results[] = $process->getName() . ' num：' . $checkNum;
                }

                if ($processEnable[$key] != null) {
                    $bgMonitor->setBgProcessEnable($process->getName(), $processEnable[$key]);
                    $results[] = $process->getName() . ' enable：' . $processEnable[$key];
                }
            }

            $em->commit();
        } catch (\Exception $e) {
            $em->rollback();

            throw $e;
        }

        $output = [
            'result' => 'ok',
            'ret' => $results
        ];

        return new JsonResponse($output);
    }

    /**
     * 處理額度不符工具
     *
     * @Route("/tools/revise_entry", name = "tools_revise_entry")
     * @Method({"GET"})
     *
     * @return Response
     */
    public function reviseEntryAction()
    {
        $em = $this->getEntityManager('share');
        $ceRepo = $em->getRepository('BBDurianBundle:CashError');
        $cfeRepo = $em->getRepository('BBDurianBundle:CashFakeError');
        $cdeRepo = $em->getRepository('BBDurianBundle:CardError');
        $cashErrors = $ceRepo->findAll();
        $cashFakeErrors = $cfeRepo->findAll();
        $cardErrors = $cdeRepo->findAll();

        $content = ['cash' => [], 'cashFake' => [], 'card' => []];
        $columns = ['cash' => [], 'cashFake' => [], 'card' => []];
        $param = [];

        if ($cashErrors) {
            $columns['cash'] = $em->getClassMetadata('BBDurianBundle:CashError')->getFieldNames();

            foreach ($cashErrors as $cashError) {
                $content['cash'][] = $cashError;
            }
        }

        if ($cashFakeErrors) {
            $columns['cashFake'] = $em->getClassMetadata('BBDurianBundle:CashFakeError')->getFieldNames();

            foreach ($cashFakeErrors as $cashFakeError) {
                $content['cashFake'][] = $cashFakeError;
            }
        }

        if ($cardErrors) {
            $columns['card'] = $em->getClassMetadata('BBDurianBundle:CardError')->getFieldNames();

            foreach ($cardErrors as $cardError) {
                $content['card'][] = $cardError;
            }
        }

        if (!$cashErrors && !$cashFakeErrors && !$cardErrors) {
            $content = null;
        }

        $param = [
            'content' => $content,
            'columns' => $columns,
            'status'  => 'showTable'
        ];

        return $this->render('BBDurianBundle:Default/Tools:reviseEntry.html.twig', $param);
    }

    /**
     * 修改現金明細建立時間
     *
     * @Route("/tools/cash_entry/revise",
     *        name = "tools_cash_entry_revise",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function reviseCashEntryAction(Request $request)
    {
        $em = $this->getEntityManager();
        $emEntry = $this->getEntityManager('entry');
        $emHis = $this->getEntityManager('his');
        $emShare = $this->getEntityManager('share');
        $repo = $emEntry->getRepository('BBDurianBundle:CashEntry');
        $repoHis = $emHis->getRepository('BBDurianBundle:CashEntry');
        $pdweRepo = $em->getRepository('BBDurianBundle:PaymentDepositWithdrawEntry');
        $validator = $this->get('durian.validator');
        $operationLogger = $this->get('durian.operation_logger');
        $post = $request->request;

        $entryId = $post->get('entry_id');
        $at = $post->get('at');
        $newAt = $post->get('new_at');

        // 驗證id格式是否為正整數
        if (!$validator->isInt($entryId, true)) {
            throw new \InvalidArgumentException('Invalid cash entry id', 150170020);
        }

        // 驗證時間格式
        if (!$validator->validateDate($at) || !$validator->validateDate($newAt)) {
            throw new \InvalidArgumentException('Invalid cash entry at', 150170021);
        }

        $at = new \DateTime($at);
        $newAt = new \DateTime($newAt);

        $entry = $repo->findOneBy(['id' => $entryId, 'at' => $at->format('YmdHis')]);
        $entryHis = $repoHis->findOneBy(['id' => $entryId, 'at' => $at->format('YmdHis')]);

        if (!$entry) {
            throw new \RuntimeException('No cash entry found', 150170024);
        }

        $em->beginTransaction();
        $emEntry->beginTransaction();
        $emHis->beginTransaction();
        $emShare->beginTransaction();

        try {
            $opcode = $entry->getOpcode();

            // 當opcode小於9890，則要一起更新PaymentDepositWithdrawEntry的建立時間
            if ($opcode < 9890) {
                $pdwEntry = $pdweRepo->findOneBy(['id' => $entryId, 'at' => $at->format('YmdHis')]);
                $pdwEntry->setAt($newAt->format('YmdHis'));

                $log = $operationLogger->create('payment_deposit_withdraw_entry', ['id' => $entryId]);
                $log->addMessage('at', $at->format('YmdHis'), $newAt->format('YmdHis'));
                $operationLogger->save($log);
                $em->flush();
                $emShare->flush();

                $output['ret']['payment_deposit_withdraw'] = $pdwEntry->toArray();
            }

            // 更新資料庫與infobright資料庫現金明細建立時間
            $entry->setAt($newAt->format('YmdHis'));
            $entry->setCreatedAt($newAt);
            $entryHis->setAt($newAt->format('YmdHis'));
            $entryHis->setCreatedAt($newAt);

            $log = $operationLogger->create('cash_entry', ['id' => $entryId]);
            $log->addMessage('at', $at->format('YmdHis'), $newAt->format('YmdHis'));
            $log->addMessage('created_at', $at->format('Y-m-d H:i:s'), $newAt->format('Y-m-d H:i:s'));
            $operationLogger->save($log);

            $em->flush();
            $emEntry->flush();
            $emHis->flush();
            $emShare->flush();
            $em->commit();
            $emEntry->commit();
            $emHis->commit();
            $emShare->commit();
        } catch (\Exception $e) {
            $em->rollback();
            $emEntry->rollback();
            $emHis->rollback();
            $emShare->rollback();
            throw $e;
        }

        // 回傳資料
        $output['result'] = 'ok';
        $output['ret']['entry'] = $entry->toArray();

        return new JsonResponse($output);
    }

    /**
     * 修改假現金明細建立時間
     *
     * @Route("/tools/cashfake_entry/revise",
     *        name = "tools_cashfake_entry_revise",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function reviseCashfakeEntryAction(Request $request)
    {
        $em = $this->getEntityManager();
        $emHis = $this->getEntityManager('his');
        $emShare = $this->getEntityManager('share');
        $repo = $em->getRepository('BBDurianBundle:CashFakeEntry');
        $repoHis = $emHis->getRepository('BBDurianBundle:CashFakeEntry');
        $cfteRepo = $em->getRepository('BBDurianBundle:CashFakeTransferEntry');
        $validator = $this->get('durian.validator');
        $operationLogger = $this->get('durian.operation_logger');
        $post = $request->request;

        $entryId = $post->get('entry_id');
        $at = $post->get('at');
        $newAt = $post->get('new_at');

        // 驗證id格式是否為正整數
        if (!$validator->isInt($entryId, true)) {
            throw new \InvalidArgumentException('Invalid cash fake entry id', 150170022);
        }

        // 驗證時間格式
        if (!$validator->validateDate($at) || !$validator->validateDate($newAt)) {
            throw new \InvalidArgumentException('Invalid cash fake entry at', 150170023);
        }

        $at = new \DateTime($at);
        $newAt = new \DateTime($newAt);

        $entry = $repo->findOneBy(['id' => $entryId, 'at' => $at->format('YmdHis')]);
        $entryHis = $repoHis->findOneBy(['id' => $entryId, 'at' => $at->format('YmdHis')]);

        if (!$entry) {
            throw new \RuntimeException('No cash fake entry found', 150170025);
        }

        $em->beginTransaction();
        $emHis->beginTransaction();
        $emShare->beginTransaction();

        try {
            $opcode = $entry->getOpcode();

            // 當opcode小於9890，則要一起更新CashFakeTransferEntry的建立時間
            if ($opcode < 9890) {
                $transferEntry = $cfteRepo->findOneBy(['id' => $entryId, 'at' => $at->format('YmdHis')]);
                $transferEntry->setAt($newAt->format('YmdHis'));
                $transferEntry->setCreatedAt($newAt);

                $logTransfer = $operationLogger->create('cash_fake_transfer_entry', ['id' => $entryId]);
                $logTransfer->addMessage('at', $at->format('YmdHis'), $newAt->format('YmdHis'));
                $logTransfer->addMessage('created_at', $at->format('Y-m-d H:i:s'), $newAt->format('Y-m-d H:i:s'));
                $operationLogger->save($logTransfer);
                $em->flush();
                $emShare->flush();

                $output['ret']['transfer'] = $transferEntry->toArray();
            }

            // 更新資料庫與infobright資料庫假現金明細建立時間
            $entry->setAt($newAt->format('YmdHis'));
            $entry->setCreatedAt($newAt);
            $entryHis->setAt($newAt->format('YmdHis'));
            $entryHis->setCreatedAt($newAt);

            $log = $operationLogger->create('cash_fake_entry', ['id' => $entryId]);
            $log->addMessage('at', $at->format('YmdHis'), $newAt->format('YmdHis'));
            $log->addMessage('created_at', $at->format('Y-m-d H:i:s'), $newAt->format('Y-m-d H:i:s'));
            $operationLogger->save($log);

            $em->flush();
            $emHis->flush();
            $emShare->flush();
            $em->commit();
            $emHis->commit();
            $emShare->commit();
        } catch (\Exception $e) {
            $em->rollback();
            $emHis->rollback();
            $emShare->rollback();
            throw $e;
        }

        // 回傳資料
        $output['result'] = 'ok';
        $output['ret']['entry'] = $entry->toArray();

        return new JsonResponse($output);
    }

    /**
     * 刪除額度不符
     *
     * @Route("/tools/error/remove", name = "tools_error_remove")
     * @Method({"POST"})
     *
     * @return Response
     */
    public function removeErrorAction()
    {
        $emShare = $this->getEntityManager('share');
        $cashErrors = $emShare->getRepository('BBDurianBundle:CashError')->findAll();
        $cashFakeErrors = $emShare->getRepository('BBDurianBundle:CashFakeError')->findAll();
        $cardErrors = $emShare->getRepository('BBDurianBundle:CardError')->findAll();
        $operationLogger = $this->get('durian.operation_logger');

        foreach ($cashErrors as $cashError) {
            $errorId = $cashError->getId();
            $error = $emShare->find('BBDurianBundle:CashError', $errorId);
            $emShare->remove($error);

            $log = $operationLogger->create('cash_error', ['id' => $errorId]);
            $log->addMessage('id', $errorId);
            $operationLogger->save($log);
        }

        foreach ($cashFakeErrors as $cashFakeError) {
            $errorId = $cashFakeError->getId();
            $error = $emShare->find('BBDurianBundle:CashFakeError', $errorId);
            $emShare->remove($error);

            $log = $operationLogger->create('cash_fake_error', ['id' => $errorId]);
            $log->addMessage('id', $errorId);
            $operationLogger->save($log);
        }

        foreach ($cardErrors as $cardError) {
            $errorId = $cardError->getId();
            $error = $emShare->find('BBDurianBundle:CardError', $errorId);
            $emShare->remove($error);

            $log = $operationLogger->create('card_error', ['id' => $errorId]);
            $log->addMessage('id', $errorId);
            $operationLogger->save($log);
        }

        $emShare->flush();

        $param = [
             'content' => null,
             'columns' => null,
             'status'  => 'delete'
        ];

        return $this->render('BBDurianBundle:Default/Tools:reviseEntry.html.twig', $param);
    }

    /**
     * 設定支付平台隨機小數工具
     *
     * @Route("/tools/set_random_float_vendor", name = "tools_set_random_float_vendor")
     * @Method({"GET"})
     *
     * @return Response
     */
    public function setRandomFloatVendor()
    {
        return $this->render('BBDurianBundle:Default/Tools:setRandomFloatVendor.html.twig');
    }

    /**
     * 檢查必要的設定
     *
     * @return array 將問題回傳
     */
    private function checkMinimum()
    {
        $problems = array();

        if (!version_compare(phpversion(), '5.3.2', '>=')) {
            $version = phpversion();
            $problems['major'][] = "目前php版本是 \"$version\", 但至少需要 PHP \"5.3.2\" 以上.";
        }

        $rootDir = $this->get('kernel')->getRootDir();

        if (!is_writable($rootDir . DIRECTORY_SEPARATOR . 'cache')) {
            $problems['major'][] = '修改 app/cache/ 權限, 讓web服務可以對它寫入資料.';
        }

        if (!is_writable($rootDir . DIRECTORY_SEPARATOR . 'logs')) {
            $problems['major'][] = '修改 app/logs/ 權限, 讓web服務可以對它寫入資料.';
        }

        return $problems;
    }

    /**
     * 檢查php extension
     *
     * @return array 將問題回傳
     */
    private function checkExtensions()
    {
        $problems = array();

        if (!class_exists('DomDocument')) {
            $problems['minor'][] = '安裝並啟用 php-xml module.';
        }

        if (!function_exists('token_get_all')) {
            $problems['minor'][] = '安裝並啟用 Tokenizer extension.';
        }

        if (!function_exists('mb_strlen')) {
            $problems['minor'][] = '安裝並啟用 mbstring extension.';
        }

        if (!function_exists('iconv')) {
            $problems['minor'][] = '安裝並啟用 iconv extension.';
        }

        if (!function_exists('utf8_decode')) {
            $problems['minor'][] = '安裝並啟用 XML extension.';
        }

        if (!defined('PHP_WINDOWS_VERSION_BUILD') && !function_exists('posix_isatty')) {
            $problems['minor'][] = '安裝並啟用 php_posix extension (使CLI 輸出有色彩顯示).';
        }

        if (!class_exists('Locale')) {
            $problems['minor'][] = '安裝並啟用 intl extension.';
        } else {
            $version = '';

            if (defined('INTL_ICU_VERSION')) {
                $version =  INTL_ICU_VERSION;
            } else {
                $reflector = new \ReflectionExtension('intl');

                ob_start();
                $reflector->info();
                $output = strip_tags(ob_get_clean());

                $matches = array();
                preg_match('/^ICU version (.*)$/m', $output, $matches);
                $version = $matches[1];
            }

            if (!version_compare($version, '4.0', '>=')) {
                $problems['minor'][] = '升級 intl extension 到 ICU version (4+).';
            }
        }

        if (!function_exists('json_encode')) {
            $problems['major'][] = '安裝並啟用 json extension.';
        }

        if (!function_exists('session_start')) {
            $problems['major'][] = '安裝並啟用 session extension.';
        }

        if (!function_exists('ctype_alpha')) {
            $problems['major'][] = '安裝並啟用 ctype extension.';
        }

        if (!function_exists('token_get_all')) {
            $problems['major'][] = '安裝並啟用 Tokenizer extension.';
        }

        if (!extension_loaded('bcmath')) {
            $problems['major'][] = '安裝並啟用 bcmath.';
        }

        if (!extension_loaded('pdo_mysql')) {
            $problems['major'][] = '安裝並啟用 pdo_mysql.';
        }

        if (!defined('OPENSSL_VERSION_NUMBER')) {
            $problems['major'][] = '安裝並啟用 OpenSSL (1.0.0+)';
        }

        //OpenSSL 1.0.0 version number is 268435459
        if (defined('OPENSSL_VERSION_NUMBER') && OPENSSL_VERSION_NUMBER < 268435459) {
            $problems['major'][] = '升級 OpenSSL (1.0.0+)';
        }

        return $problems;
    }

    /**
     * 檢查php.ini
     *
     * @return array 將問題回傳
     */
    private function checkPhpIni()
    {
        $problems = array();

        if (!ini_get('date.timezone')) {
            $problems['major'][] = '需設定php.ini的 date.timezone (例如 Asia/Taipei).';
        }

        if (ini_get('magic_quotes_gpc')) {
            $problems['minor'][] = '建議將php.ini的 magic_quotes_gpc 設成 off.';
        }

        if (ini_get('register_globals')) {
            $problems['minor'][] = '建議將php.ini的 register_globals 設成 off.';
        }

        if (ini_get('session.auto_start')) {
            $problems['minor'][] = '建議將php.ini的 session.auto_start 設成 off.';
        }

        return $problems;
    }

    /**
     * 檢查是否需要執行更新佔成
     *
     * @return array 將問題回傳
     */
    private function checkActivateSLNext()
    {
        $problems = array();

        $now = new \DateTime();
        $activateSLNext = $this->get('durian.activate_sl_next');

        if (!$activateSLNext->hasBeenUpdated($now)) {
            $problems['major'][] = '尚未執行更新佔成';
        }

        return $problems;
    }

    /**
     * 檢查資料庫連線
     *
     * @return array 將問題回傳
     */
    private function checkDb()
    {
        $problems = [];

        try {
            $databases = [
                'default',
                'his',
                'share',
                'entry',
                'outside',
                'ip_blocker'
            ];

            foreach ($databases as $database) {
                $em = $this->getEntityManager($database);
                $msg = sprintf('需設定%s資料庫連線', $database);

                // 先插入一筆測試資料
                $testData = new Test('TEST!!');
                $em->persist($testData);
                $em->flush();

                // 檢查有無插入
                $id   = $testData->getId();
                $data = $em->find('BBDurianBundle:Test', $id);

                if ($data->getMemo() != 'TEST!!') {
                    $problems['major'][] = $msg;
                }

                // 測試修改資料是否成功
                $data->setMemo('RESET');
                $em->persist($data);
                $em->flush();

                $alteredData = $em->find('BBDurianBundle:Test', $id);

                if ($alteredData->getMemo() != 'RESET') {
                    $problems['major'][] = $msg;
                }

                // 刪除測試資料
                $em->remove($alteredData);
                $em->flush();

                $data = $em->find('BBDurianBundle:Test', $id);

                if (!empty($data)) {
                    $problems['major'][] = $msg;
                }
            }
        } catch (\Exception $e) {
            $problems['major'][] = $e->getMessage();
        }

        return $problems;
    }

    /**
     * 檢查redis連線
     *
     * @return array 將問題回傳
     */
    private function checkRedis()
    {
        $problems = [];

        try {
            $redisClient = [
                'default'    => 'snc_redis.default',
                'sequence'   => 'snc_redis.sequence',
                'cluster'    => 'snc_redis.cluster',
                'map'        => 'snc_redis.map',
                'reward'     => 'snc_redis.reward',
                'wallet1'    => 'snc_redis.wallet1',
                'wallet2'    => 'snc_redis.wallet2',
                'wallet3'    => 'snc_redis.wallet3',
                'wallet4'    => 'snc_redis.wallet4',
                'oauth2'     => 'snc_redis.oauth2',
                'suncity'    => 'snc_redis.suncity',
                'ip_blocker' => 'snc_redis.ip_blocker'
            ];

            foreach ($redisClient as $key) {
                $redis = $this->get($key);

                // 測試新增資料
                $redis->del('foo');
                $redis->set('foo', 'bar');

                if ($redis->get('foo') != 'bar') {
                    $problems['major'][] = '需設定redis';
                }

                // 測試刪除資料
                $redis->del('foo');

                if ($redis->get('foo') != '') {
                    $problems['major'][] = '需設定redis';
                }
            }
        } catch (\Exception $e) {
            $problems['major'][] = $e->getMessage();
        }

        return $problems;
    }

    /**
     * 檢查redis的seq key是否存在
     *
     * @return array
     */
    private function checkRedisSeq()
    {
        $problems = array();
        $redisSeq = $this->get('snc_redis.sequence');

        //檢查redis中的cash_seq是否還存在
        if (!$redisSeq->exists('cash_seq')) {
            $problems['major'][] = '無法產生現金明細id';
        }

        //檢查redis中的cashfake_seq是否還存在
        if (!$redisSeq->exists('cashfake_seq')) {
            $problems['major'][] = '無法產生快開額度明细id';
        }

        //檢查redis中的card_seq是否還存在
        if (!$redisSeq->exists('card_seq')) {
            $problems['major'][] = '無法產生租卡明細id';
        }

        //檢查redis中的cash_withdraw_seq是否還存在
        if (!$redisSeq->exists('cash_withdraw_seq')) {
            $problems['major'][] = '無法產生現金出款明細id';
        }

        //檢查redis中的user_seq是否還存在
        if (!$redisSeq->exists('user_seq')) {
            $problems['major'][] = '無法產生使用者id';
        }

        //檢查redis中的reward_seq是否還存在
        if (!$redisSeq->exists('reward_seq')) {
            $problems['major'][] = '無法產生紅包明細id';
        }

        // 檢查redis中的bitcoin_deposit_seq是否還存在
        if (!$redisSeq->exists('bitcoin_deposit_seq')) {
            $problems['major'][] = '無法產生比特幣入款明細id';
        }

        // 檢查redis中的bitcoin_withdraw_seq是否還存在
        if (!$redisSeq->exists('bitcoin_withdraw_seq')) {
            $problems['major'][] = '無法產生比特幣出款明細id';
        }

        //檢查redis中的outside_seq是否還存在
        if (!$redisSeq->exists('outside_seq')) {
            $problems['major'][] = '無法產生外接額度明細id';
        }

        return $problems;
    }

    /**
     * 檢查session 的維護資訊是否正確
     *
     * @return array
     */
    private function checkSessionMaintain()
    {
        $problems = [];
        $redis = $this->container->get('snc_redis.cluster');
        $msg = '資料庫與session的維護資訊不一致，請執行durian:create-session-info --maintain';

        // 檢查redis中的session_maintain是否存在
        if (!$redis->exists('session_maintain')) {
            $problems['major'] = $msg;

            return $problems;
        }

        $em = $this->getEntityManager();

        $sessionMaintain = $redis->hgetall('session_maintain');
        $repo = $em->getRepository('BBDurianBundle:Maintain');
        $maintainTotal = $repo->countNumOf();

        if ($maintainTotal != count($sessionMaintain)) {
            $problems['major'] = $msg;
        }

        return $problems;
    }

    /**
     * 檢查session 的白名單資訊是否正確
     *
     * @return array
     */
    private function checkSessionWhitelist()
    {
        $problems = [];
        $redis = $this->container->get('snc_redis.cluster');
        $msg = '資料庫與session的白名單資訊不一致，請執行durian:create-session-info --whitelist';

        $em = $this->getEntityManager();

        $allLists = $em->getRepository('BBDurianBundle:MaintainWhitelist')->findAll();
        $redisCount = $redis->scard('session_whitelist');

        // 判斷數量
        if (count($allLists) != $redisCount) {
            $problems['major'] = $msg;
        }

        // 判斷內容
        foreach ($allLists as $list) {
            $redisList = $redis->sismember('session_whitelist', $list->getIp());

            if (!$redisList) {
                $problems['major'] = $msg;

                break;
            }
        }

        return $problems;
    }

    /**
     * 檢查redis的seq key是否小於明細最大值
     *
     * @return array
     */
    private function checkRedisInvalidSeq()
    {
        $problems = [];
        $redisSeq = $this->get('snc_redis.sequence');

        $seqs = [
            'CashEntry' => 'cash_seq',
            'CashTrans' => 'cash_seq',
            'CashFakeEntry' => 'cashfake_seq',
            'CashFakeTrans' => 'cashfake_seq',
            'CardEntry' => 'card_seq',
            'User' => 'user_seq',
            'CashWithdrawEntry' => 'cash_withdraw_seq',
            'RewardEntry' => 'reward_seq',
            'OutsideEntry' => 'outside_seq'
        ];

        foreach ($seqs as $key => $value) {

            $redisValue = $redisSeq->get($value);

            //檢查redis中的seq是否存在
            if (!isset($redisValue)) {
                continue;
            }

            $em = $this->getEntityManager();

            if ($key == 'CashEntry') {
                $em = $this->getEntityManager('entry');
            }

            if ($key == 'OutsideEntry') {
                $em = $this->getEntityManager('outside');
            }

            $repo = $em->getRepository("BBDurianBundle:$key");
            $mysqlValue = $repo->getMaxId();

            //檢查redis中的seq是否小於明細最大值
            if ($redisValue < $mysqlValue) {
                $problems['major'][] = "$key Sequence 異常，須重新設定 $value";
            }
        }

        return $problems;
    }

    /** 修正差異明細工具(初始頁面)
     *
     * @Route("/tools/repair_entry_page", name="tools_repair_entry_page")
     *
     * @return Response
     */
    public function repairEntryPageAction()
    {
        return $this->render('BBDurianBundle:Default/Tools:repairEntry.html.twig');
    }

    /**
     * 顯示有差異的明細清單
     *
     * @Route("/tools/show_entry", name="tools_show_entry")
     * @Method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function showEntryAction(Request $request)
    {
        $diffNum = 0;
        $contents = [];
        $idArray = [];

        $query = $request->query;
        $em = $this->getEntityManager();
        $emEntry = $this->getEntityManager('entry');
        $emHis = $this->getEntityManager('his');
        $entryType = $query->get('entry_type');

        $entryDiffClassArray = [
            'cash'     => 'BBDurianBundle:CashEntryDiff',
            'cashFake' => 'BBDurianBundle:CashFakeEntryDiff'
        ];

        $entryClassArray = [
            'cash'     => 'BBDurianBundle:CashEntry',
            'cashFake' => 'BBDurianBundle:CashFakeEntry'
        ];

        if ($entryType != 'null') {
            $entryClass = $entryClassArray[$entryType];
            $entryDiffClass = $entryDiffClassArray[$entryType];

            // 找出EntryDiff內所有id
            $ids = $em->getRepository($entryDiffClass)->findAll();
            foreach ($ids as $id) {
                $idArray[] = $id->getId();
            }

            $diffNum = count($idArray);

            // 若為 cash_entry 需切換連線
            if ($entryType == 'cash') {
                $em = $emEntry;
            }

            // 找出現行和歷史明細
            $entries = $em->getRepository($entryClass)
                ->findBy(['id' => $idArray], ['id' => 'ASC']);
            $entriesHis = $emHis->getRepository($entryClass)
                ->findBy(['id' => $idArray], ['id' => 'ASC']);

            foreach ($entries as $idx => $entry) {
                // 現行明細
                $content = $entry->toArray();
                $content['at'] = $entry->getAt();
                $contents[] = $content;

                // 歷史明細
                $content = $entriesHis[$idx]->toArray();
                $content['at'] = $entriesHis[$idx]->getAt();
                $contents[] = $content;
            }
        }

        $params = [
            'entry_type' => $entryType,
            'contents' => $contents,
            'diff_num' => $diffNum
        ];

        return new JsonResponse($params);
    }

    /**
     * 修正有差異的明細清單
     *
     * @Route("/tools/execute_repair_entry", name="tools_execute_repair_entry")
     * @Method({"PUT"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function executeRepairEntryAction(Request $request)
    {
        $logs = [];
        $idArray = [];
        $translation = ['cash' => '現金', 'cashFake' => '假現金'];

        $request = $request->request;
        $em = $this->getEntityManager();
        $entryType = $request->get('entry_type');

        if ($entryType == 'cash') {
            $command = 'durian:cronjob:check-cash-entry';
            $entryDiffClass = 'BBDurianBundle:CashEntryDiff';
        }

        if ($entryType == 'cashFake') {
            $command = 'durian:cronjob:check-cash-fake-entry';
            $entryDiffClass = 'BBDurianBundle:CashFakeEntryDiff';
        }

        // 找出EntryDiff內所有id
        $ids = $em->getRepository($entryDiffClass)->findAll();
        foreach ($ids as $id) {
            $idArray[] = $id->getId();
        }

        $arguments = [
            'command'  => $command,
            '--update' => true
        ];
        $commandInput  = new ArrayInput($arguments);
        $commandOutput = new BufferedOutput();

        $app = new Application($this->get('kernel'));
        $app->setAutoExit(false);
        $status = $app->run($commandInput, $commandOutput);

        // command執行成功
        if ($status == 0) {
            $logs[] = $translation[$entryType] . '明細修正成功';
            $logs[] = '已修正明細編號:' . implode(', ', $idArray);

        } else {
            $logs[] = $translation[$entryType] . '明細修正失敗';

            // 抓噴的例外訊息
            $log = $commandOutput->fetch();
            $logs[] = substr($log, 0, strpos($log, 'durian'));
        }

        // 計算entryDiff的數量
        $diffNum['cash'] = $em->getRepository('BBDurianBundle:CashEntryDiff')->countNumOf();
        $diffNum['cashFake'] = $em->getRepository('BBDurianBundle:CashFakeEntryDiff')->countNumOf();

        $params = [
            'logs' => $logs,
            'status' => $status,
            'diff_num' => $diffNum
        ];

        return new JsonResponse($params);
    }

    /**
     * 取得監控IP封鎖列表資訊頁面
     *
     * @Route("/tools/display_ip_blacklist", name="tools_display_ip_blacklist")
     * @Method({"GET"})
     *
     * @param Request $request
     * @return Response
     */
    public function displayIpBlacklistAction(Request $request)
    {
        $query = $request->query;

        // 初始顯示兩天內資料
        $now = new \DateTime('now');
        $end = $now->format('Y-m-d H:i:s');
        $start = $now->sub(new \DateInterval('P2D'))->format('Y-m-d H:i:s');

        $criteria = [
            'start' => $query->get('created_at_start', $start),
            'end' => $query->get('created_at_end', $end)
        ];

        $ip = $query->get('ip');

        if (trim($ip) != '') {
            $criteria['ip'] = $ip;
        }

        // 單頁最多顯示50筆資料
        $page = $query->get('page', 1);
        $maxResults = 50;
        $firstResult = ($page - 1) * $maxResults;

        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $ipBlackRepo = $emShare->getRepository('BBDurianBundle:IpBlacklist');
        $geoipBlockRepo = $emShare->getRepository('BBDurianBundle:GeoipBlock');
        $parameterHandler = $this->get('durian.parameter_handler');

        // 照建立時間反向排序
        $sort = ['created_at'];
        $order = ['desc'];
        $orderBy = $parameterHandler->orderBy($sort, $order);

        // 未刪除IP封鎖列表
        $criteria['removed'] = 0;
        $list = $ipBlackRepo->getListBy($criteria, $orderBy, $firstResult, $maxResults);
        $total = $ipBlackRepo->countListBy($criteria);

        $ipBlacklist = [];
        $removedIpBlacklist = [];

        foreach ($list as $ipBlack) {
            $ipBlackTemp = $this->parseIpBlackData($ipBlack);

            // 整理ip來源相關資訊
            $verId = $geoipBlockRepo->getCurrentVersion();
            $ipBlock = $geoipBlockRepo->getBlockByIpAddress($ipBlackTemp['ip'], $verId);

            $ipBlackTemp['source'] = '未定義';

            if ($ipBlock) {
                $ipCountry = $emShare->find('BBDurianBundle:GeoipCountry', $ipBlock['country_id']);
                $ipCountryArr = $ipCountry->toArray();

                // 優先顯示中文翻譯, 沒有則顯示代碼
                if ($ipCountryArr['zh_tw_name']) {
                    $ipBlackTemp['source'] = $ipCountryArr['zh_tw_name'];
                } else {
                    $ipBlackTemp['source'] = $ipCountryArr['country_code'];
                }

                if ($ipBlock['city_id']) {
                    $ipCity = $emShare->find('BBDurianBundle:GeoipCity', $ipBlock['city_id']);
                    $ipCityArr = $ipCity->toArray();
                } else {
                    $ipBlacklist[] = $ipBlackTemp;
                    continue;
                }

                if ($ipCityArr['zh_tw_name']) {
                    $ipBlackTemp['source'] .= ' ' . $ipCityArr['zh_tw_name'];
                } else {
                    $ipBlackTemp['source'] .= ' ' . $ipCityArr['city_code'];
                }
            }

            $ipBlacklist[] = $ipBlackTemp;
        }

        // 照修改時間反向排序
        $sort = ['modified_at'];
        $order = ['desc'];
        $orderBy = $parameterHandler->orderBy($sort, $order);

        // 已刪除IP封鎖列表
        $criteria['removed'] = 1;
        $removedTotal = $ipBlackRepo->countListBy($criteria);
        $removedlist = $ipBlackRepo->getListBy($criteria, $orderBy);

        foreach ($removedlist as $ipBlack) {
            $removedIpBlacklist[] = $this->parseIpBlackData($ipBlack);
        }

        $totalPage = ceil($total / $maxResults);

        if ($totalPage == 0) {
            $totalPage = 1;
        }

        $ipBlackList = [
            'total' => $total,
            'ipBlacklist' => $ipBlacklist,
            'removedTotal' => $removedTotal,
            'removedIpBlacklist' => $removedIpBlacklist,
            'ip' => $ip,
            'start' => str_replace('T', ' ', $criteria['start']),
            'end' => str_replace('T', ' ', $criteria['end']),
            'page' => $page,
            'totalPage' => $totalPage
        ];

        return $this->render(
            'BBDurianBundle:Default/Tools:displayIpBlacklist.html.twig',
            $ipBlackList
        );
    }

    /**
     * 取得ip登入及註冊時間計次資料
     *
     * @Route("/tools/get_ip_activity_record",
     *        name = "tools_get_ip_activity_record",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getIpActivityRecordAction(Request $request)
    {
        $em = $this->getEntityManager('share');
        $parameterHandler = $this->get('durian.parameter_handler');

        $query = $request->query;
        $ipBlacklistId = $query->get('ip_blacklist_id');

        $ipBlack = $em->find('BBDurianBundle:IpBlacklist', $ipBlacklistId);

        $domain = $ipBlack->getDomain();
        $endAt = $ipBlack->getCreatedAt();
        $ip = $ipBlack->getIp();

        $start = clone $endAt;
        $start = $start->sub(new \DateInterval('P7D'))->format('YmdHis');
        $end = $endAt->format('YmdHis');

        $reasonStart = clone $endAt;
        $reasonStart = $reasonStart->sub(new \DateInterval('P1D'))->format('YmdHis');

        $criteria = [
            'domain' => $domain,
            'ip' => $ip,
            'startTime' => $start,
            'endTime' => $end
        ];

        $reasonRecord = [];
        $otherRecord = [];

        if ($ipBlack->isCreateUser()) {
            $criteria['ip'] = ip2long($ip);

            $orderBy = $parameterHandler->orderBy('at', 'desc');

            $lists = $em->getRepository('BBDurianBundle:UserCreatedPerIp')
                ->getUserCreatedPerIp($criteria, null, null, $orderBy);
        }

        if ($ipBlack->isLoginError()) {
            $lists = $em->getRepository('BBDurianBundle:LoginErrorPerIp')
                ->getListBy($criteria);
        }

        foreach ($lists as $record) {
            $recordTemp = $record->toArray();

            $recordTemp['at'] = str_replace('T', ' ', $recordTemp['at']);
            $recordTemp['at'] = str_replace('+0800', '', $recordTemp['at']);

            // 分開顯示IP封鎖列表產生原因及一周內其他異常紀錄
            if ($record->getAt() >= $reasonStart) {
                $reasonRecord[] = $recordTemp;
                continue;
            }

            $otherRecord[] = $recordTemp;
        }

        $reasonTotal = count($reasonRecord);
        $otherTotal = count($otherRecord);

        $params = [
            'result' => 'ok',
            'ip_blacklist_id' => $ipBlacklistId,
            'domain' => $domain,
            'ip' => $ip,
            'reasonTotal' => $reasonTotal,
            'reasonRecord' => $reasonRecord,
            'otherTotal' => $otherTotal,
            'otherRecord' => $otherRecord
        ];

        return new JsonResponse($params);
    }

    /**
     * kue job工具初始頁面
     *
     * @Route("/tools/display_kue_job",
     *        name = "tools_display_kue_job")
     * @Method({"GET"})
     *
     * @return Render
     */
    public function displayKueJobAction()
    {
        $kueManager = $this->get('durian.kue_manager');

        $nums = $kueManager->getJobNum();
        $types = $kueManager->getJobType();

        $kueData = [
            'types' => $types,
            'nums' => $nums
        ];

        return $this->render(
            'BBDurianBundle:Default/Tools:displayKueJob.html.twig',
            $kueData
        );
    }

    /**
     * 刪除kue job
     *
     * @Route("/tools/delete_kue_job",
     *        name = "tools_delete_kue_job",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"DELETE"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function deleteKueJobAction(Request $request)
    {
        $request = $request->request;

        $type = $request->get('type');
        $status = $request->get('status');
        $from = $request->get('from', 0);
        $to = $request->get('to', 0);
        $order = $request->get('order', 'asc');

        if ($type == 'null' || $status == 'null') {
            throw new \InvalidArgumentException('No kue type or status specified', 150170029);
        }

        if ($from < 0 || $to < 0 || $from > $to) {
            throw new \InvalidArgumentException('Invalid range specified', 150170030);
        }

        $param = [
            'type' => $type,
            'status' => $status,
            'from' => $from,
            'to' => $to,
            'order' => $order
        ];

        $kueManager = $this->get('durian.kue_manager');

        $counts = $kueManager->deleteJob($param);
        $kueNums = $kueManager->getJobNum();

        $output = [];
        $output['result'] = 'ok';
        $output['ret'] = [
            'nums' => $kueNums,
            'success_count' => $counts['success'],
            'failed_count' => $counts['failed']
        ];

        return new JsonResponse($output);
    }

    /**
     * 重新執行kue job
     *
     * @Route("/tools/redo_kue_job",
     *        name = "tools_redo_kue_job",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function redoKueJobAction(Request $request)
    {
        $request = $request->request;

        $type = $request->get('type');
        $status = $request->get('status');
        $from = $request->get('from', 0);
        $to = $request->get('to', 0);
        $order = $request->get('order', 'asc');

        if ($type == 'null' || $status == 'null') {
            throw new \InvalidArgumentException('No kue type or status specified', 150170029);
        }

        if ($from < 0 || $to < 0 || $from > $to) {
            throw new \InvalidArgumentException('Invalid range specified', 150170030);
        }

        $param = [
            'type' => $type,
            'status' => $status,
            'from' => $from,
            'to' => $to,
            'order' => $order
        ];

        $kueManager = $this->get('durian.kue_manager');

        $counts = $kueManager->redoJob($param);
        $kueNums = $kueManager->getJobNum();

        $output = [];
        $output['result'] = 'ok';
        $output['ret'] = [
            'nums' => $kueNums,
            'success_count' => $counts['success'],
            'failed_count' => $counts['failed']
        ];

        return new JsonResponse($output);
    }

    /**
     * 回傳Doctrine EntityManager
     *
     * @param string $name Entity manager name
     * @return \Doctrine\ORM\EntityManager
     */
    private function getEntityManager($name = 'default')
    {
        return $this->getDoctrine()->getManager($name);
    }

    /**
     * 整理IP封鎖列表資料
     *
     * @param IpBlacklist
     * @return array
     */
    private function parseIpBlackData($ipBlack)
    {
        $em = $this->getEntityManager('share');
        $ipBlackTemp = $ipBlack->toArray();

        // 需顯示廳的資料
        $ipBlackTemp['name'] = '';

        $config = $em->find('BBDurianBundle:DomainConfig', $ipBlackTemp['domain']);

        if ($config) {
            $ipBlackTemp['name'] = $config->getName();
        }

        // 修改輸出時間格式
        $ipBlackTemp['created_at'] = $ipBlack->getCreatedAt()->format('Y-m-d H:i:s');
        $ipBlackTemp['modified_at'] = $ipBlack->getModifiedAt()->format('Y-m-d H:i:s');

        return $ipBlackTemp;
    }

    /**
     * 呼叫API
     *
     * @param string $method
     * @param string $url
     * @param array $data
     * @param string $serverIp
     * @param string $host
     * @return array
     */
    private function curlRequest($method, $url, $data, $serverIp, $host)
    {
        $client = new Curl();
        $response = new CurlResponse();

        $curlRequest = new FormRequest($method, $url, $serverIp);
        $curlRequest->addFields($data);
        $curlRequest->addHeader('Host: ' . $host);
        $client->setOption(CURLOPT_TIMEOUT, 30);
        $client->setOption(CURLOPT_SSL_VERIFYHOST, false);
        $client->setOption(CURLOPT_SSL_VERIFYPEER, false);
        $client->send($curlRequest, $response);

        if ($response->getStatusCode() != 200) {
            throw new \RuntimeException('Durian api call failed', 150170018);
        }

        return json_decode($response->getContent(), true);
    }

    /**
     * 查詢廳主(DomainMap專用)
     *
     * @param string  $name        廳名
     * @param string  $loginCode   廳主後置碼
     * @param integer $enable      搜尋啟用/停用/全部廳主
     * @param integer $firstResult 資料開頭
     * @param integer $maxResults  資料筆數
     * @return ArrayCollection
     */
    private function getDomainMap(
        $name = null,
        $loginCode = null,
        $enable = 1,
        $firstResult = null,
        $maxResults = null
    ) {
        $qb = $this->getEntityManager('share')->createQueryBuilder();
        $qb->select('dc.domain, dc.name, dc.loginCode')
            ->from('BBDurianBundle:DomainConfig', 'dc');

        if ($name) {
            $qb->andwhere('dc.name LIKE :name');
            $qb->setParameter('name', $name);
        }

        if ($loginCode) {
            $qb->andwhere('dc.loginCode LIKE :loginCode')
                ->setParameter('loginCode', $loginCode);
        }

        $domains = $qb->getQuery()->getArrayResult();
        $domainId = [];
        $allDomain = [];
        foreach ($domains as $d) {
            $allDomain[$d['domain']] = $d;
            $domainId[] = $d['domain'];
        }

        if (!$domainId) {
            return [];
        }

        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('u.id, u.username, u.enable')
            ->from('BBDurianBundle:User', 'u')
            ->where('u.id in (:userId)')
            ->andWhere($qb->expr()->isNull('u.parent'))
            ->setParameter('userId', $domainId)
            ->orderBy('u.id', 'ASC');

        if ($enable >= 0) {
            $qb->andwhere('u.enable = :enable')
                ->setParameter('enable', $enable);
        }

        if ($firstResult) {
           $qb->setFirstResult($firstResult);
        }

        if ($maxResults) {
           $qb->setMaxResults($maxResults);
        }

        $users = $qb->getQuery()->getArrayResult();

        foreach ($users as $i => $u) {
            $domain = $allDomain[$u['id']];
            $u['name'] = $domain['name'];
            $u['loginCode'] = $domain['loginCode'];

            $users[$i] = $u;
        }

        return $users;
    }

    /**
     * 計算廳主數量(DomainMap專用)
     *
     * @param string  $name      廳名
     * @param string  $loginCode 廳主後置碼
     * @param integer $enable    搜尋啟用/停用/全部廳主
     * @return integer
     */
    private function countDomainMap($name = null, $loginCode = null, $enable = 1)
    {
        $qb = $this->getEntityManager('share')->createQueryBuilder();
        $qb->select('dc.domain, dc.name, dc.loginCode')
            ->from('BBDurianBundle:DomainConfig', 'dc');

        if ($name) {
            $qb->andwhere('dc.name LIKE :name');
            $qb->setParameter('name', $name);
        }

        if ($loginCode) {
            $qb->andwhere('dc.loginCode LIKE :loginCode')
                ->setParameter('loginCode', $loginCode);
        }

        $domains = $qb->getQuery()->getArrayResult();
        $domainId = [];
        foreach ($domains as $d) {
            $domainId[] = $d['domain'];
        }

        if (!$domainId) {
            return 0;
        }

        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('COUNT(u)')
            ->from('BBDurianBundle:User', 'u')
            ->where('u.id in (:userId)')
            ->andWhere($qb->expr()->isNull('u.parent'))
            ->setParameter('userId', $domainId);

        if ($enable >= 0) {
            $qb->andwhere('u.enable = :enable')
                ->setParameter('enable', $enable);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }
}

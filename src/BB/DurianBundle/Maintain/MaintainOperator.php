<?php
namespace BB\DurianBundle\Maintain;

use Buzz\Message\Form\FormRequest;
use Buzz\Message\Request;
use Buzz\Message\Response;
use Buzz\Client\Curl;
use Buzz\Listener\LoggerListener;
use BB\DurianBundle\Entity\Maintain;
use BB\DurianBundle\Entity\MaintainStatus;

class MaintainOperator
{
    /**
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    private $container;

    /**
     *
     * @var \Buzz\Message\Response
     */
    private $response;

    /**
     * @var \Buzz\Client\Curl
     */
    private $client;

    /**
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
     */
    public function __construct($container)
    {
        $this->container = $container;
    }

    /**
     * @param \Buzz\Message\Response $response
     */
    public function setResponse($response)
    {
        $this->response = $response;
    }

    /**
     * @param \Buzz\Client\Curl $client
     */
    public function setClient($client)
    {
        $this->client = $client;
    }

    /**
     * 處理傳送到各組的訊息
     *
     * @param Maintain $maintain
     * @param string $target
     * @param \DateTime $nowTime
     * @param integer $status
     * @param Array $whitelistArray
     *
     * @return array
     */
    public function prepareMessage($maintain, $target, $nowTime, $status, $whitelistArray = [])
    {
        $code = $maintain->getCode();
        $beginAt = $maintain->getBeginAt();
        $endAt = $maintain->getEndAt();
        $msg = $maintain->getMsg();

        $isMaintain = false;
        $atMaintainTime = $beginAt <= $nowTime && $nowTime < $endAt;

        if ($atMaintainTime && $beginAt != $endAt) {
            $isMaintain = true;
        }

        if ($target == '1') {
            //送給研一的參數
            $msgArray = [
                'tag'        => 'maintain_1',
                'method'     => 'GET',
                'msgContent' => [
                    'code'           => $code,
                    'begin_at'       => $beginAt->format(\DateTime::ISO8601),
                    'end_at'         => $endAt->format(\DateTime::ISO8601),
                    'msg'            => $msg,
                    'whitelist'      => $whitelistArray,
                    'is_maintaining' => 'false'
                ]
            ];

            if ($isMaintain == true) {
                $msgArray['msgContent']['is_maintaining'] = 'true';
            }
        } elseif ($target == '3') {
            //送給研三的參數
            $beginAt->setTimezone(new \DateTimeZone('Etc/GMT+4'));
            $endAt->setTimezone(new \DateTimeZone('Etc/GMT+4'));
            $msgArray = [
                'tag'        => 'maintain_3',
                'method'     => 'GET',
                'msgContent' => [
                    'gamekind'    => $code,
                    'start_date'  => $beginAt->format('Y-m-d'),
                    'starttime'   => $beginAt->format('H:i:s'),
                    'end_date'    => $endAt->format('Y-m-d'),
                    'endtime'     => $endAt->format('H:i:s'),
                    'message'     => $msg,
                    'state'       => 'n'
                ]
            ];
            $beginAt->setTimezone(new \DateTimeZone('Asia/Taipei'));
            $endAt->setTimezone(new \DateTimeZone('Asia/Taipei'));

            if ($isMaintain == true) {
                $msgArray['msgContent']['state'] = 'y';
            }
        } elseif ($target == 'mobile') {
            //送給Mobile的參數
            $msgArray = [
                'tag'        => 'maintain_mobile',
                'method'     => 'POST',
                'msgContent' => [
                    'code'           => $code,
                    'begin_at'       => $beginAt->format(\DateTime::ISO8601),
                    'end_at'         => $endAt->format(\DateTime::ISO8601),
                    'msg'            => $msg,
                    'is_maintaining' => 'false'
                ]
            ];

            if ($isMaintain == true) {
                $msgArray['msgContent']['is_maintaining'] = 'true';
            }
        } elseif ($target == 'domain') {
            //呼叫研一送廳主訊息api參數
            $domainMsg = $this->container->get('durian.domain_msg');
            $domains = $this->container->get('doctrine.orm.entity_manager')
                ->getRepository('BBDurianBundle:DomainConfig')
                ->getEnableDomain();
            $domain = implode(',', $domains);
            $maintainStatus = 'Start';

            if ($status == MaintainStatus::SEND_MAINTAIN_END) {
                $maintainStatus = 'End';
            }

            $getTWTitle = "get{$maintainStatus}MaintainTWTitle";
            $getTWContent = "get{$maintainStatus}MaintainTWContent";
            $getCNTitle = "get{$maintainStatus}MaintainCNTitle";
            $getCNContent = "get{$maintainStatus}MaintainCNContent";
            $getENTitle = "get{$maintainStatus}MaintainENTitle";
            $getENContent = "get{$maintainStatus}MaintainENContent";

            $msgArray = [
                'tag' => 'maintain_domain',
                'method' => 'POST',
                'msgContent' => [
                    'operator' => 'system',
                    'operator_id' => 0,
                    'hall_id' => $domain,
                    'subject_tw' => $domainMsg->$getTWTitle($maintain),
                    'content_tw' => $domainMsg->$getTWContent($maintain),
                    'subject_cn' => $domainMsg->$getCNTitle($maintain),
                    'content_cn' => $domainMsg->$getCNContent($maintain),
                    'subject_en' => $domainMsg->$getENTitle($maintain),
                    'content_en' => $domainMsg->$getENContent($maintain),
                    'category' => 2
                ]
            ];
        }

        return $msgArray;
    }

    /**
     * 送訊息到指定網址
     *
     * @param array $msgArray
     */
    public function sendMessageToDestination($msgArray)
    {
        $logger = $this->container->get('durian.logger_manager')
            ->setUpLogger('maintain/send_message_http_detail.log');
        $tag = $msgArray['tag'];
        $sendArray = $msgArray['msgContent'];
        $requestMethod = $msgArray['method'];

        //連到指定網址送訊息
        if ($this->client) {
            $client = $this->client;
        } else {
            $client = new Curl();
        }

        // 因歐博的維護API，最慢可能會執行到30秒左右，決定將timeout直接設為30秒
        if (isset($sendArray['gamekind']) && $sendArray['gamekind'] == 22) {
            $client->setOption(CURLOPT_TIMEOUT, 30);
        }

        $desResource = $this->getDesResource($tag);
        $desIp = $this->getDesIp($tag);
        $desDomain = $this->getDesDomain($tag);

        // 如果 domain 或 ip 為 null 則不送訊息
        if (empty($desIp) || empty($desDomain)) {
            return;
        }

        $request = new FormRequest($requestMethod, $desResource, $desIp);
        $request->addHeader("Host: $desDomain");
        $request->addFields($sendArray);

        if ($tag == 'maintain_mobile') {
            $request = new Request($requestMethod, $desResource, $desIp);
            $request->setContent(json_encode($sendArray));
            $request->addHeader("Host: $desDomain");
            $request->addHeader('Ekey: mobile');
            $request->addHeader('Content-Type: application/json');
        }

        $response = new Response();

        $listener = new LoggerListener(array($logger, 'addDebug'));
        $listener->preSend($request);

        if ($this->container->getParameter('kernel.environment') != 'test') {
            $client->send($request, $response);
        }

        $listener->postSend($request, $response);

        if ($this->response) {
            $response = $this->response;
        }

        $logger->addDebug($request . $response);

        $logger->popHandler()->close();

        if ($this->container->getParameter('kernel.environment') != 'test') {
            if ($tag == 'italking') {
                $this->checkITalkingResponse($response);
            } elseif ($tag == 'maintain_1' || $tag == 'maintain_3') {
                $this->checkMaintainResponse($response);
            } elseif ($tag == 'maintain_domain') {
                $this->checkDomainMsgResponse($response);
            } elseif ($tag == 'maintain_mobile') {
                $this->checkMobileMsgResponse($response);
            }
        }
    }

    /**
     * 回傳傳送目標的 url resource
     *
     * @param string $tag
     * @return string
     */
    public function getDesResource($tag)
    {

        return $this->container->getParameter($tag . '_url');
    }

    /**
     * 回傳傳送目標的ip
     *
     * @param string $tag
     * @return string
     */
    public function getDesIp($tag)
    {

        return $this->container->getParameter($tag . '_ip');
    }

    /**
     * 回傳傳送目標的 domain
     *
     * @param string $tag
     * @return string
     */
    public function getDesDomain($tag)
    {

        return $this->container->getParameter($tag . '_domain');
    }

    /**
     * 確認italking回傳的response是否正確
     *
     * @param Response $response
     * @throws \Exception
     */
    private function checkITalkingResponse($response)
    {
        $returnMsg = json_decode($response->getContent(), true);

        //json回傳code為0表示傳送訊息成功，不為0則傳送失敗
        if ($response->getStatusCode() != 200 || $returnMsg['code'] !== 0) {
            throw new \RuntimeException('Send message to italking failed', 150100009);
        }
    }

    /**
     * 確認maintain回傳的response是否正確
     *
     * @param Response $response
     * @throws \Exception
     */
    private function checkMaintainResponse($response)
    {
        $responseContent = json_decode($response->getContent());

        if (!$responseContent) {
            throw new \RuntimeException('Send maintain message failed', 150100010);
        }

        if ($responseContent->result != 'ok' && $responseContent->result != 'true') {
            throw new \RuntimeException('Send maintain message failed', 150100010);
        }
    }

    /**
     * 確認廳主訊息回傳的response是否正確
     *
     * @param Response $response
     * @throws \Exception
     */
    private function checkDomainMsgResponse($response)
    {
        if ($response->getStatusCode() != 200) {
            throw new \RuntimeException('Send domain message failed', 150100018);
        }

        $responseContent = json_decode($response->getContent(), true);

        if ($responseContent['status'] != 'success') {
            throw new \RuntimeException('Send domain message failed', 150100019);
        }
    }

    /**
     * 確認Mobile訊息回傳的response是否正確
     *
     * @param Response $response
     * @throws \Exception
     */
    private function checkMobileMsgResponse($response)
    {
        if ($response->getStatusCode() != 200) {
            throw new \RuntimeException('Send maintain message failed', 150100020);
        }

        $responseContent = json_decode($response->getContent(), true);

        if ($responseContent['status'] !== '000') {
            throw new \RuntimeException('Send maintain message failed', 150100021);
        }
    }
}

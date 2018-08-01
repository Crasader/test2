<?php

namespace BB\DurianBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use BB\DurianBundle\Entity\MaintainStatus;

/**
 * 發維護訊息request 到指定的網址
 */
class SendMaintainMessageCommand extends ContainerAwareCommand
{
    /**
     * @var InputInterface
     */
    private $input;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * @var \BB\DurianBundle\Maintain\MaintainOperator
     */
    private $maintainOperator;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * 取消記錄sql log
     *
     * @var bool
     */
    private $disableLog;

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function configure()
    {
        $this
            ->setName('durian:send-maintain-message')
            ->setDescription('送遊戲維護訊息至指定網址')
            ->addOption('disable-log', null, InputOption::VALUE_NONE, 'Disable logging')
            ->setHelp(<<<EOT
送遊戲維護訊息至指定網址
EOT
            );
    }

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input  = $input;
        $this->output = $output;
        $this->em = $this->getEntityManager();

        $container = $this->getContainer();
        $this->maintainOperator = $container->get('durian.maintain_operator');

        $bgMonitor = $container->get('durian.monitor.background');
        $bgMonitor->commandStart('send-maintain-message');

        $this->disableLog = $this->input->getOption('disable-log');

        $this->log("SendMaintainMessageCommand Start.");

        $count =  $this->sendMessage();

        $this->log("SendMaintainMessageCommand finish.");

        $handler = $this->logger->popHandler();
        $handler->close();

        $bgMonitor->setMsgNum($count);

        $bgMonitor->commandEnd();
    }

    /**
     * @return \Doctrine\ORM\EntityManager
     */
    protected function getEntityManager()
    {
        if ($this->em) {
            return $this->em;
        }

        $this->em = $this->getContainer()->get('doctrine.orm.entity_manager');
        return $this->em;
    }

    /**
     * 送訊息到指定網址
     * 依照訊息的狀態來推送訊息
     *
     * @return integer
     */
    private function sendMessage()
    {
        $nowTime = new \Datetime('now');
        $executeCount = 0;
        $maintainStatuses = $this->em->getRepository('BBDurianBundle:MaintainStatus')->findAll();

        $mapNextStatus = [
            MaintainStatus::SEND_MAINTAIN => MaintainStatus::SEND_MAINTAIN_START,
            MaintainStatus::SEND_MAINTAIN_START => MaintainStatus::SEND_MAINTAIN_END,
            MaintainStatus::SEND_MAINTAIN_END => null,
            MaintainStatus::SEND_MAINTAIN_NOTICE => null
        ];

        foreach ($maintainStatuses as $maintainStatus) {
            $status = $maintainStatus->getStatus();
            $maintain = $maintainStatus->getMaintain();
            $target = $maintainStatus->getTarget();
            $beginAt = $maintain->getBeginAt();
            $endAt = $maintain->getEndAt();

            try {
                //判斷target正不正確
                $targetAllow = ['1', '3', 'mobile', 'domain'];
                if (!in_array($target, $targetAllow)) {
                    throw new \InvalidArgumentException('Illegal game maintain target', 150100011);
                }

                /* status為1,2,3 處理是否發送訊息
                 * 1: 等待發送維護訊息
                 * 2: 等待發送開始維護訊息
                 * 3: 等待發送結束維護訊息
                 */
                $isSendMsg = false;
                if ($status == MaintainStatus::SEND_MAINTAIN) {
                    $isSendMsg = true;
                }

                if ($status == MaintainStatus::SEND_MAINTAIN_START && $nowTime >= $beginAt) {
                    $isSendMsg = true;
                }

                if ($status == MaintainStatus::SEND_MAINTAIN_END && $nowTime >= $endAt) {
                    $isSendMsg = true;
                }

                if ($status == MaintainStatus::SEND_MAINTAIN_NOTICE && $nowTime >= $maintainStatus->getUpdateAt()) {
                    $isSendMsg = true;
                }

                if (!$isSendMsg) {
                    continue;
                }

                $whitelistArray = [];
                $whitelists = $this->em->getRepository('BBDurianBundle:MaintainWhitelist')->findAll();

                foreach ($whitelists as $whitelist) {
                    $whitelistArray[] = $whitelist->getIp();
                }

                $msgArray = $this->maintainOperator->prepareMessage($maintain, $target, $nowTime, $status, $whitelistArray);
                $logMsg = $this->prepareLogMsg($msgArray);
                $this->maintainOperator->sendMessageToDestination($msgArray);

                if (isset($mapNextStatus[$status])) {
                    $maintainStatus->setStatus($mapNextStatus[$status]);
                    $maintainStatus->setUpdateAt($nowTime);
                } else {
                    $this->em->remove($maintainStatus);
                }

                $this->em->flush();

                $logStr = $logMsg . " Maintain send ok, status: $status";
                $this->log($logStr);
                $executeCount++;
            } catch (\Exception $e) {
                $errorCode = $e->getCode();

                if ($errorCode == 150100011) {
                    $this->log("Illegal game maintain target $target");
                    $italkingMsg = "SendMaintainMessageCommand: Illegal game maintain target $target";
                    $this->sendMessageToItalking($italkingMsg);
                    continue;
                }

                $logStr = $logMsg . ' Status:' . $status . ' Send to target failed, send to italking ';
                $logStr .= " Error $errorCode";
                $logStr .= " Msg: {$e->getMessage()}";
                $this->log($logStr);

                // 送訊息到 italking
                $desInfo = array(
                    'desResource' => $this->maintainOperator->getDesResource($msgArray['tag']),
                    'desIp' => $this->maintainOperator->getDesIp($msgArray['tag']),
                    'desDomain' => $this->maintainOperator->getDesDomain($msgArray['tag'])
                );
                $italkingMsg = $this->prepareItalkingMsg($desInfo, $msgArray, $errorCode, $e->getMessage());
                $this->sendMessageToItalking($italkingMsg);
            }
        }

        return $executeCount;
    }

    /**
     * 送維護訊息失敗，發送失敗訊息到 italking
     *
     * @param integer $code
     * @param string $target
     * @param integer $status
     */
    private function sendMessageToItalking($message)
    {
        $msgArray = [
            'tag'        => 'italking',
            'method'     => 'POST',
            'msgContent' => [
                'type'     => 'developer_acc',
                'message'  => $message,
                'user'     => $this->getContainer()->getParameter('italking_user'),
                'password' => $this->getContainer()->getParameter('italking_password'),
                'code'     => $this->getContainer()->getParameter('italking_gm_code')
            ]
        ];

        $logStr = $this->prepareLogMsg($msgArray);
        try {
            $this->maintainOperator->sendMessageToDestination($msgArray);
            $logStr .= ' Send to italking ok ';
            $this->log($logStr);
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
            $logStr .= ' Send to italking failed ';
            $logStr .= " Error $errorCode";
            $logStr .= " Msg: {$e->getMessage()}";
            $this->log($logStr);
        }
        if ($this->getContainer()->getParameter('kernel.environment') != 'test') {
            usleep(500000);
        }
    }

    /**
     * 將 message 做 urlencode 避免記錄在 log 為亂碼
     *
     * @param array $msgArray
     * @return string
     */
    private function prepareLogMsg($msgArray)
    {
        $tag = $msgArray['tag'];
        $logMsg = 'Tag:' . $tag;
        $logContentMsg = $msgArray['msgContent'];

        if ($tag == 'maintain_1' || $tag == 'maintain_mobile') {
            $logContentMsg['msg'] = urlencode($logContentMsg['msg']);
            $logContentMsg['begin_at'] = urlencode($logContentMsg['begin_at']);
            $logContentMsg['end_at'] = urlencode($logContentMsg['end_at']);
        } elseif ($tag == 'maintain_3' || $tag == 'italking') {
            $logContentMsg['message'] = urlencode($logContentMsg['message']);
        } elseif ($tag == 'maintain_domain') {
            $logContentMsg['operator'] = urlencode($logContentMsg['operator']);
            $logContentMsg['subject_tw'] = urlencode($logContentMsg['subject_tw']);
            $logContentMsg['content_tw'] = urlencode(str_replace("\n", '', $logContentMsg['content_tw']));
            $logContentMsg['subject_cn'] = urlencode($logContentMsg['subject_cn']);
            $logContentMsg['content_cn'] = urlencode(str_replace("\n", '', $logContentMsg['content_cn']));
            $logContentMsg['subject_en'] = urlencode($logContentMsg['subject_en']);
            $logContentMsg['content_en'] = urlencode(str_replace("\n", '', $logContentMsg['content_en']));
        }

        return $logMsg .= ' message: ' . urldecode(json_encode($logContentMsg));
    }

    /**
     * 設定並記錄log
     *
     * @param String $message
     */
    private function log($msg)
    {
        if ($this->disableLog) {
            return;
        }

        if (null === $this->logger) {
            $this->logger = $this->getContainer()->get('durian.logger_manager')
                ->setUpLogger('send_maintain_message.log');
        }

        $this->logger->addInfo($msg);
    }

    /**
     * 準備送到 italking 的訊息
     *
     * @param array $desInfo
     * @param array $msgArray
     * @param integer $errorCode
     * @return string
     */
    public function prepareItalkingMsg($desInfo, $msgArray, $errorCode, $errorMsg)
    {
        $groupMap = [
            'maintain_1' => 'RD1-系統核心部整合組',
            'maintain_3' => 'RD3'
        ];

        $italkingMsg = '分項遊戲維護發生錯誤, ';
        $msg = "請通知 RD5-帳號研發部 值班人員檢查, 錯誤代碼為 $errorCode, 錯誤訊息為 $errorMsg";

        // errorCode 150100010 代表回傳的內容錯誤
        if ($errorCode == 150100010) {
            $msg = "請通知 RD5-帳號研發部 與 " . $groupMap[$msgArray['tag']] . " 值班人員檢查, 回傳發生錯誤";
        }

        if ($errorCode == 7) { //errorCode 7 代表 couldn't connect to host
            $curlMsg = sprintf('curl -H"host:%s" "%s"', $desInfo['desDomain'], $desInfo['desIp']);
            $msg = "請通知 DC-OP 測試網路連線是否正常, 測試: 在 172.26.53.1 下指令 $curlMsg";
        }

        if ($errorCode == 28) {
            $msg = sprintf(
                '若客服重掛分項仍失敗, 請通知 %1$s 與 DC-OP 值班人員檢查。請通知 %1$s 以下資訊, ' .
                '來源: 172.26.53.1, 目標機器: %2$s (%3$s), 錯誤代碼為 28, 錯誤訊息為 %4$s。',
                $groupMap[$msgArray['tag']], $desInfo['desIp'], $desInfo['desDomain'], $errorMsg
            );
            $curlMsg = sprintf('curl -H"host:%s" "%s"', $desInfo['desDomain'], $desInfo['desIp']);
            $msg .= "請通知DC-OP測試網路連線是否正常, 測試: 在 172.26.53.1 下指令 $curlMsg";
        }
        $italkingMsg .= $msg;

        // 加入當下時間
        $nowTime = new \Datetime('now');
        $time = $nowTime->format('[Y-m-d H:i:s]');
        $italkingMsg = $time . ' ' . $italkingMsg;

        return $italkingMsg;
    }
}

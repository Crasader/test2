<?php
namespace BB\DurianBundle\Logger;

use Symfony\Component\DependencyInjection\ContainerAware;

/**
 * 公司入款建立自動確認對外連線的紀錄
 */
class RemitAutoConfirm extends ContainerAware
{
    /**
     * 寫公司入款建立自動確認對外連線的log
     *
     * @param array $message 需要記錄的參數，共有以下參數：
     *     string serverIp 提交 ip
     *     string host 提交域名
     *     string method 提交方式
     *     string uri 提交 uri
     *     string param 提交參數
     *     string output 返回結果
     */
    public function record($message)
    {
        // 預先設定好 logger 與儲存的 log 檔
        $handler = $this->container->get('monolog.handler.remit_auto_confirm');

        $logger = $this->container->get('logger');
        $logger->pushHandler($handler);

        $param = json_decode($message['param'], true);

        // 密鑰遮罩
        if (isset($param['apikey'])) {
            $param['apikey'] = '******';
        }
        $message['param'] = json_encode($param);
        $header = isset($message['header']) ? json_encode($message['header']) : '';

        $logContent = sprintf(
            '%s %s "%s %s" "HEADER: %s" "REQUEST: %s" "RESPONSE: %s"',
            $message['serverIp'],
            $message['host'],
            $message['method'],
            $message['uri'],
            $header,
            $message['param'],
            $message['output']
        );

        $logger->info($logContent);
        $logger->popHandler()->close();
    }
}

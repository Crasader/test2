<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;
use Buzz\Message\Form\FormRequest;
use Buzz\Message\Response;
use Buzz\Client\Curl;

/**
 * 彼特幣支付
 */
class Kbpay extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'shopid' => '', // 商號帳號
        'userid' => '', // 玩家帳號
        'price' => '', // 支付金額，韓圜
        'btcAddr' => '', // 玩家的固定入款電子錢包
        'param1' => '', // 自訂參數，這邊帶入訂單號
        'param2' => '', // 自訂參數，這邊帶入username
        'param3' => '', // 自訂參數，這邊帶入merchantId_domain
        'param4' => '', // 自訂參數，特殊加密方式
        'param5' => '', // 自訂參數
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'shopid' => 'number',
        'price' => 'amount',
        'param1' => 'orderId',
        'param2' => 'username',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'mode' => 1,
        'type' => 1,
        'shopid' => 1,
        'userid' => 1,
        'shopaddr' => 1,
        'btc' => 1,
        'price' => 1,
        'date' => 1,
        'result' => 1,
        'param1' => 1,
        'param2' => 1,
        'param3' => 1,
        'param4' => 1,
        'param5' => 1,
    ];

    /**
     * 回傳支付時要給支付平台驗證的資料
     *
     * @return array
     */
    public function getVerifyData()
    {
        $ip = $this->container->getParameter('payment_ip');
        // 驗證私鑰
        $this->verifyPrivateKey();

        $this->payVerify();

        // 從內部給定值到參數
        foreach ($this->requireMap as $paymentKey => $internalKey) {
            $this->requestData[$paymentKey] = $this->options[$internalKey];
        }

        // 額外的參數設定
        $names = ['userid', 'btcAddr'];
        $extra = $this->getMerchantExtraValue($names);
        $this->requestData['userid'] = $extra['userid'];
        $this->requestData['btcAddr'] = $extra['btcAddr'];
        $this->requestData['param3'] = sprintf(
            '%s_%s',
            $this->options['merchantId'],
            $this->options['domain']
        );

        /**
         * 設定param4
         * param1=value1&param2=value2&param3=value3串key之後做md5
         */
        $encodeParam = sprintf(
            'param1=%s&param2=%s&param3=%s',
            $this->requestData['param1'],
            $this->requestData['param2'],
            $this->requestData['param3']
        );
        $encodeParam .= $this->privateKey;
        $this->requestData['param4'] = md5($encodeParam);

        // 設定支付平台需要的加密串
        $encodeData = $this->encode();
        $param = [
            'style' => 1,
            'data' => $encodeData,
        ];

        $data = [
            'url' => '/deposit1.aspx?' . http_build_query($param),
            'host' => $this->options['postUrl']
        ];

        $client = new Curl();
        $response = new Response();

        if ($this->client) {
            $client = $this->client;
        }

        if ($this->response) {
            $response = $this->response;
        }

        $curlRequest = new FormRequest('GET', "/pay/curl.php", $ip);
        $curlRequest->addFields($data);
        $client->setOption(CURLOPT_TIMEOUT, 30);
        $client->setOption(CURLOPT_SSL_VERIFYHOST, false);
        $client->setOption(CURLOPT_SSL_VERIFYPEER, false);

        try {
            $client->send($curlRequest, $response);
        } catch (\Exception $e) {
            throw new PaymentConnectionException('Payment Gateway connection failure', 180088, $this->getEntryId());
        }

        $result = $response->getContent();

        // curl 取得qrcode html: <img id="imgQR" src="....." style="width: 160px; margin: 10px;" />
        $getData = [];
        preg_match('/<img id="imgQR" src="(.*)" style="width:/', $result, $getData);

        $html = str_replace('style="width:', '/>', $getData[0]);
        $this->setHtml($html);

        return [];
    }

    /**
     * 驗證線上支付是否成功
     *
     * @param array $entry
     */
    public function verifyOrderPayment($entry)
    {
        // 驗證私鑰
        $this->verifyPrivateKey();

        // 檢查回傳狀態
        if (!isset($this->options['result']) || $this->options['result'] != 'SUCCESS') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        $this->payResultVerify();

        // 檢查mode, 入款為deposit
        if ($this->options['mode'] != 'deposit') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        // 檢查商號額外欄位與回傳值是否一致
        if ($this->options['userid'] != $this->options['merchant_extra']['userid']) {
            throw new PaymentConnectionException('PaymentGateway error, userid error', 150180179, $this->getEntryId());
        }

        if ($this->options['shopaddr'] != $this->options['merchant_extra']['btcAddr']) {
            throw new PaymentConnectionException(
                'PaymentGateway error, shopaddr error',
                150180180,
                $this->getEntryId()
            );
        }

        // 驗證特殊欄位param4
        $encodeStr = sprintf(
            'param1=%s&param2=%s&param3=%s',
            $this->options['param1'],
            $this->options['param2'],
            $this->options['param3']
        );
        $encodeStr .= $this->privateKey;

        if ($this->options['param4'] != md5($encodeStr)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        // 驗證支付回傳的參數來判斷訂單號是否正確
        if ($this->options['param1'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        // 驗證支付回傳的金額來判斷金額是否正確
        if ($this->options['price'] != $entry['amount']) {
            throw new PaymentException('Order Amount error', 180058);
        }
    }

    /**
     * 支付時的加密
     *
     * @return string
     */
    protected function encode()
    {
        $encodeData = [];

        // 組織加密簽名
        foreach ($this->requestData as $key => $value) {
            $encodeData[$key] = $value;
        }

        // 依key1=value1&key2=value2&...&keyN=valueN之後做base64Encode
        $encodeStr = urldecode(http_build_query($encodeData));

        return base64_encode($encodeStr);
    }
}

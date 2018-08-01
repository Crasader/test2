<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 中聯
 */
class KKLpay extends PaymentBase
{
    /**
     * 支付提交參數
     *
     * @var array
     */
    protected $requestData = [
        'merchantCode' => '', // 商戶號
        'outOrderId' => '', // 訂單號
        'outUserId' => '', // 外部會員號
        'totalAmount' => '', // 支付金額，單位分，需整數
        'goodsName' => '', // 產品名稱(這邊塞username方便業主比對)
        'goodsDescription' => '', // 產品描述
        'merchantOrderTime' => '', // 訂單成立時間
        'lastPayTime' => '', // 訂單逾期時間
        'merUrl' => '', // 頁面同步跳轉通知地址
        'notifyUrl' => '', // 通知商戶服務端地址
        'randomStr' => '', // 隨機字符串
        'ext' => '',
        'sign' => '', // 簽名
    ];

    /**
     * 支付提交參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'merchantCode' => 'number',
        'outOrderId' => 'orderId',
        'goodsName' => 'username',
        'totalAmount' => 'amount',
        'merUrl' => 'notify_url',
        'notifyUrl' => 'notify_url',
        'randomStr' => 'username',
        'merchantOrderTime' => 'orderCreateDate',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'merchantCode',
        'outOrderId',
        'outUserId',
        'totalAmount',
        'merchantOrderTime',
        'notifyUrl',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'merchantCode' => 1,
        'instructCode' => 1,
        'transType' => 1,
        'outOrderId' => 1,
        'transTime' => 1,
        'totalAmount' => 1,
    ];

    /**
     * 應答機制訊息
     *
     * @var string
     */
    protected $msg = "{'code':'00'}";

    /**
     * 查詢時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $trackingRequestData = [
        'merchantCode' => '', // 商戶號
        'outOrderId' => '', // 訂單號
        'sign' => '',
    ];

    /**
     * 查詢時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $trackingRequireMap = [
        'merchantCode' => 'number',
        'outOrderId' => 'orderId',
    ];

    /**
     * 查詢解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $trackingDecodeParams = [
        'merchantCode' => 1,
        'outOrderId' => 1,
        'amount' => 1,
        'replyCode' => 1,
    ];

    /**
     * 回傳支付時要給支付平台驗證的資料
     *
     * @return array
     */
    public function getVerifyData()
    {
        // 驗證私鑰
        $this->verifyPrivateKey();

        $this->payVerify();

        $this->options['notify_url'] = sprintf(
            '%s?pay_system=%s&hallid=%s',
            $this->options['notify_url'],
            $this->options['merchantId'],
            $this->options['domain']
        );

        // 從內部給定值到參數
        foreach ($this->requireMap as $paymentKey => $internalKey) {
            $this->requestData[$paymentKey] = $this->options[$internalKey];
        }

        $this->requestData['totalAmount'] = round($this->requestData['totalAmount'] * 100);

        $date = new \DateTime($this->requestData['merchantOrderTime']);
        $this->requestData['merchantOrderTime'] = $date->format('YmdHis');
        $date->add(new \DateInterval('PT1H'));
        $this->requestData['lastPayTime'] = $date->format('YmdHis');

        // 設定支付平台需要的加密串
        $this->requestData['sign'] = $this->encode();

        return $this->requestData;
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

        $this->payResultVerify();

        $encodeData = [];

        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options)) {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        // 針對$encodeData按字母做升序排列
        ksort($encodeData);

        $encodeData['KEY'] = $this->privateKey;

        // 依key1=value1&key2=value2&...&keyN=valueN排序
        $encodeStr = urldecode(http_build_query($encodeData));

        $sign = strtoupper(md5($encodeStr));

        // 沒有返回sign就要丟例外
        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if (strtoupper($this->options['sign']) != $sign) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['outOrderId'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['totalAmount'] != round($entry['amount'] * 100)) {
            throw new PaymentException('Order Amount error', 180058);
        }
    }

    /**
     * 訂單查詢
     */
    public function paymentTracking()
    {
        // 驗證私鑰
        $this->verifyPrivateKey();

        // 驗證訂單查詢參數
        $this->trackingVerify();

        // 從內部給定值到參數
        foreach ($this->trackingRequireMap as $paymentKey => $internalKey) {
            $this->trackingRequestData[$paymentKey] = $this->options[$internalKey];
        }

        $this->trackingRequestData['sign'] = $this->trackingEncode();

        // 執行訂單查詢
        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $jsonParam = [
            'project_id' => 'test',
            'param' => $this->trackingRequestData,
        ];

        $curlParam = [
            'method' => 'POST',
            'uri' => '/ebank/queryOrder.do',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => json_encode($jsonParam),
            'header' => ['Content-Type' => 'application/json'],
        ];

        $result = $this->curlRequest($curlParam);

        $retArray = json_decode($result, true);

        if (!isset($retArray['code']) || !isset($retArray['data'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        if ($retArray['code'] != '00') {
            throw new PaymentConnectionException('Payment tracking failed', 180081, $this->getEntryId());
        }

        // 訂單不存在
        if (empty($retArray['data'])) {
            throw new PaymentConnectionException('Order does not exist', 180060, $this->getEntryId());
        }

        $parseData = $retArray['data'];

        $this->trackingResultVerify($parseData);

        $encodeData = [];

        foreach (array_keys($this->trackingDecodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $parseData)) {
                $encodeData[$paymentKey] = $parseData[$paymentKey];
            }
        }

        // 針對$encodeData按字母做升序排列
        ksort($encodeData);

        $encodeData['KEY'] = $this->privateKey;

        // 依key1=value1&key2=value2&...&keyN=valueN排序
        $encodeStr = urldecode(http_build_query($encodeData));

        $sign = strtoupper(md5($encodeStr));

        // 沒有sign就要丟例外，其他的參數在組織加密串的時候驗證過了
        if (!isset($parseData['sign'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        if (strtoupper($parseData['sign']) != $sign) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($parseData['replyCode'] != '00') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($parseData['outOrderId'] != $this->options['orderId']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($parseData['amount'] != round($this->options['amount'] * 100)) {
            throw new PaymentException('Order Amount error', 180058);
        }
    }

    /**
     * 取得訂單查詢時需要的參數
     *
     * @return array
     */
    public function getPaymentTrackingData()
    {
        // 驗證私鑰
        $this->verifyPrivateKey();

        // 驗證訂單查詢參數
        $this->trackingVerify();

        // 從內部給定值到參數
        foreach ($this->trackingRequireMap as $paymentKey => $internalKey) {
            $this->trackingRequestData[$paymentKey] = $this->options[$internalKey];
        }

        $this->trackingRequestData['sign'] = $this->trackingEncode();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $jsonParam = [
            'project_id' => 'test',
            'param' => $this->trackingRequestData,
        ];

        $curlParam = [
            'verify_ip' => $this->options['verify_ip'],
            'path' => '/ebank/queryOrder.do',
            'method' => 'POST',
            'json' => $jsonParam,
            'headers' => [
                'Host' => $this->options['verify_url']
            ]
        ];

        return $curlParam;
    }

    /**
     * 驗證訂單查詢是否成功
     */
    public function paymentTrackingVerify()
    {
        // 驗證私鑰
        $this->verifyPrivateKey();

        $retArray = json_decode($this->options['content'], true);

        if (!isset($retArray['code']) || !isset($retArray['data'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        if ($retArray['code'] != '00') {
            throw new PaymentConnectionException('Payment tracking failed', 180081, $this->getEntryId());
        }

        // 訂單不存在
        if (empty($retArray['data'])) {
            throw new PaymentConnectionException('Order does not exist', 180060, $this->getEntryId());
        }

        $parseData = $retArray['data'];

        $this->trackingResultVerify($parseData);

        $encodeData = [];

        foreach (array_keys($this->trackingDecodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $parseData)) {
                $encodeData[$paymentKey] = $parseData[$paymentKey];
            }
        }

        // 針對$encodeData按字母做升序排列
        ksort($encodeData);

        $encodeData['KEY'] = $this->privateKey;

        // 依key1=value1&key2=value2&...&keyN=valueN排序
        $encodeStr = urldecode(http_build_query($encodeData));

        $sign = strtoupper(md5($encodeStr));

        // 沒有sign就要丟例外，其他的參數在組織加密串的時候驗證過了
        if (!isset($parseData['sign'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        if (strtoupper($parseData['sign']) != $sign) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($parseData['replyCode'] != '00') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($parseData['outOrderId'] != $this->options['orderId']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($parseData['amount'] != round($this->options['amount'] * 100)) {
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

        foreach ($this->encodeParams as $key) {
            $encodeData[$key] = $this->requestData[$key];
        }

        // 針對$encodeData按字母做升序排列
        ksort($encodeData);

        $encodeData['KEY'] = $this->privateKey;

        // 依key1=value1&key2=value2&...&keyN=valueN之後做md5
        $encodeStr = urldecode(http_build_query($encodeData));

        return strtoupper(md5($encodeStr));
    }

    /**
     * 訂單查詢時的加密
     *
     * @return string
     */
    protected function trackingEncode()
    {
        $encodeData = [];

        // 組織加密簽名，排除sign(加密簽名)，其他非空的參數都要納入加密
        foreach ($this->trackingRequestData as $key => $value) {
            if ($key != 'sign' && trim($value) != '') {
                $encodeData[$key] = $value;
            }
        }

        // 針對$encodeData按字母做升序排列
        ksort($encodeData);

        $encodeData['KEY'] = $this->privateKey;

        // 依key1=value1&key2=value2&...&keyN=valueN之後做md5
        $encodeStr = urldecode(http_build_query($encodeData));

        return strtoupper(md5($encodeStr));
    }
}

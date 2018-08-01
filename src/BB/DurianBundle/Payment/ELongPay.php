<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 億龍支付
 */
class ELongPay extends PaymentBase
{
    /**
     * 支付提交參數
     *
     * @var array
     */
    protected $requestData = [
        'appid' => '', // 商號
        'version' => '1.0.0', // 版本號，固定值:1.0.0
        'request_time' => '', // 訂單提交時間，格式:yyyy-MM-dd HH:mm:ss
        'sign_type' => 'MD5', // 簽名類型
        'sign' => '', // 簽名
        'out_trade_no' => '', // 訂單號
        'goods_name' => '', // 商品名稱
        'total_amount' => '', // 交易金額，單位元，精確到小數點後兩位
        'channel_code' => '', // 支付渠道編碼
        'notify_url' => '', // 異步通知地址
        'remarks' => '', // 交易備註，可空
        'device' => '1', // 設備，0:手機 1:電腦
    ];

    /**
     * 支付提交參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'appid' => 'number',
        'request_time' => 'orderCreateDate',
        'out_trade_no' => 'orderId',
        'goods_name' => 'orderId',
        'total_amount' => 'amount',
        'channel_code' => 'paymentVendorId',
        'notify_url' => 'notify_url',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'appid',
        'version',
        'request_time',
        'out_trade_no',
        'goods_name',
        'total_amount',
        'channel_code',
        'notify_url',
        'device',
    ];

    /**
     * 返回驗證時需要加密的參數
     *     0: 有返回就要加密的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'trade_no' => 1,
        'out_trade_no' => 1,
        'total_amount' => 1,
        'goods_name' => 1,
        'status' => 1,
        'pay_time' => 1,
        'version' => 1,
    ];

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        1092 => 'aliscpay', // 支付寶_二維
        1098 => 'alipayh5', // 支付寶_手機支付
    ];

    /**
     * 回傳支付時要給支付平台驗證的資料
     *
     * @return array
     */
    public function getVerifyData()
    {
        $this->verifyPrivateKey();
        $this->payVerify();

        // 從內部設值到支付參數
        foreach ($this->requireMap as $paymentKey => $internalKey) {
            $this->requestData[$paymentKey] = $this->options[$internalKey];
        }

        // 檢查銀行代碼是否支援
        if (!array_key_exists($this->requestData['channel_code'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $this->requestData['channel_code'] = $this->bankMap[$this->requestData['channel_code']];
        $this->requestData['total_amount'] = sprintf('%.2f', $this->requestData['total_amount']);
        $createAt = new \Datetime($this->requestData['request_time']);
        $this->requestData['request_time'] = $createAt->format('Y-m-d H:i:s');

        // 設定支付平台需要的加密串
        $this->requestData['sign'] = $this->encode();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'method' => 'POST',
            'uri' => '/trade/create',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => json_encode($this->requestData),
            'header' => ['Content-Type' => 'application/json'],
        ];

        $result = $this->curlRequest($curlParam);
        $parseData = json_decode($result, true);

        if (!isset($parseData['code'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        if ($parseData['code'] !== '0') {
            throw new PaymentConnectionException('Pay error', 180130, $this->getEntryId());
        }

        if (!isset($parseData['content']) || $parseData['content'] == '') {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        // 支付寶二維
        if ($this->options['paymentVendorId'] == 1092) {
            $this->setQrcode($parseData['content']);

            return [];
        }

        $getUrl = [];
        preg_match('/action="([^"]+)/', $parseData['content'], $getUrl);

        if (!isset($getUrl[1])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        $urlData = $this->parseUrl($getUrl[1]);

        $out = [];
        if (!preg_match_all('/<input type="hidden" name="([^"]+)" value="([^"]*)"/U', $parseData['content'], $out)) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        return [
            'post_url' =>  $urlData['url'],
            'params' => array_merge($urlData['params'], array_combine($out[1], $out[2])),
        ];
    }

    /**
     * 驗證線上支付是否成功
     *
     * @param array $entry
     */
    public function verifyOrderPayment($entry)
    {
        $this->verifyPrivateKey();
        $this->payResultVerify();

        // 組合參數驗證加密簽名
        $encodeData = [];

        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options)) {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        ksort($encodeData);
        $encodeData['key'] = $this->privateKey;

        // 調整金額精確到小數點後兩位
        $encodeData['total_amount'] = sprintf('%.2f', $encodeData['total_amount']);

        $encodeStr = urldecode(http_build_query($encodeData));

        // 沒有返回sign就要丟例外
        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if (strcasecmp($this->options['sign'], strtoupper(md5($encodeStr))) != 0) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['status'] != '1000') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['out_trade_no'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['total_amount'] != $entry['amount']) {
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

        foreach ($this->encodeParams as $index) {
            if (array_key_exists($index, $this->requestData) && trim($this->requestData[$index] !== '')) {
                $encodeData[$index] = $this->requestData[$index];
            }
        }

        ksort($encodeData);

        $encodeData['key'] = $this->privateKey;

        $encodeStr = urldecode(http_build_query($encodeData));

        return strtoupper(md5($encodeStr));
    }
}

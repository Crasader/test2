<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 智付平方
 */
class JrFuPingFang extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'merchantNo' => '', // 商號
        'orderPrice' => '', // 支付金額，單位:元，精確到小數點後兩位
        'outOrderNo' => '', // 訂單號
        'tradeType' => '', // 交易類別
        'tradeTime' => '', // 下單時間，YmdHis
        'goodsName' => '', // 商品名稱
        'tradeIp' => '', // 客戶端IP
        'returnUrl' => '', // 頁面通知地址
        'notifyUrl' => '', // 異步通知地址
        'remark' => '', // 備註，可空
        'sign' => '', // 簽名
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'merchantNo' => 'number',
        'orderPrice' => 'amount',
        'outOrderNo' => 'orderId',
        'tradeType' => 'paymentVendorId',
        'tradeTime' => 'orderCreateDate',
        'goodsName' => 'orderId',
        'tradeIp' => 'ip',
        'returnUrl' => 'notify_url',
        'notifyUrl' => 'notify_url',

    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'merchantNo',
        'orderPrice',
        'outOrderNo',
        'tradeType',
        'tradeTime',
        'goodsName',
        'tradeIp',
        'returnUrl',
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
        'merchantNo' => 1,
        'goodsName' => 1,
        'outOrderNo' => 1,
        'orderPrice' => 1,
        'tradeType' => 1,
        'tradeStatus' => 1,
        'successTime' => 1,
        'orderTime' => 1,
        'tradeNo' => 1,
    ];

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        1092 => 'ali_pay_wap_t0', // 支付寶_二維
        1098 => 'ali_pay_wap_t0', // 支付寶_手機支付
    ];

    /**
     * 應答機制訊息
     *
     * @var string
     */
    protected $msg = 'SUCCESS';

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

        // 從內部給定值到參數
        foreach ($this->requireMap as $paymentKey => $internalKey) {
            $this->requestData[$paymentKey] = $this->options[$internalKey];
        }

        // 帶入未支援的銀行就噴例外
        if (!array_key_exists($this->requestData['tradeType'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $this->requestData['tradeType'] = $this->bankMap[$this->requestData['tradeType']];
        $this->requestData['orderPrice'] = sprintf('%.2f', $this->requestData['orderPrice']);
        $createAt = new \Datetime($this->requestData['tradeTime']);
        $this->requestData['tradeTime'] = $createAt->format('YmdHis');

        // 設定支付平台需要的加密串
        $this->requestData['sign'] = $this->encode();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'method' => 'POST',
            'uri' => '/simple-web-gateway/scan/apply',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => urldecode(http_build_query($this->requestData)),
            'header' => [],
        ];

        $result = $this->curlRequest($curlParam);
        $parseData = json_decode($result, true);

        if (!isset($parseData['resultCode'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        if ($parseData['resultCode'] !== '0000' && isset($parseData['errMsg'])) {
            throw new PaymentConnectionException($parseData['errMsg'], 180130, $this->getEntryId());
        }

        if ($parseData['resultCode'] !== '0000') {
            throw new PaymentConnectionException('Pay error', 180130, $this->getEntryId());
        }

        if (!isset($parseData['payMsg'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        // 支付寶二維
        if ($this->options['paymentVendorId'] == 1092) {
            $this->setQrcode($parseData['payMsg']);

            return [];
        }

        $urlData = $this->parseUrl($parseData['payMsg']);

        // Form使用GET才能正常跳轉
        $this->payMethod = 'GET';

        return [
            'post_url' => $urlData['url'],
            'params' => $urlData['params'],
        ];
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

        // 組織加密串
        $encodeStr = [];
        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options)) {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        ksort($encodeData);
        $encodeData['secretKey'] = $this->privateKey;
        $encodeStr = urldecode(http_build_query($encodeData));

        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['sign'] != strtoupper(md5($encodeStr))) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['tradeStatus'] != 'SUCCESS') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['outOrderNo'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['orderPrice'] != $entry['amount']) {
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
        foreach ($this->encodeParams as $index) {
            $encodeData[$index] = $this->requestData[$index];
        }

        ksort($encodeData);
        $encodeData['secretKey'] = $this->privateKey;
        $encodeStr = urldecode(http_build_query($encodeData));

        return strtoupper(md5($encodeStr));
    }
}

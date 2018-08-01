<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 睿捷通
 */
class RuiJieTong extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'version' => 'V2.0.0.0', // 版本號
        'merNo' => '', // 商戶號
        'netway' => '', // 支付方式
        'random' => '', // 隨機數，最多4位
        'orderNum' => '', // 訂單號
        'amount' => '', // 金額，單位：分
        'goodsName' => '', // 商品名稱，不可空
        'callBackUrl' => '', // 異步通知地址
        'callBackViewUrl' => '', // 回顯地址
        'charset' => 'UTF-8', // 客戶端系統編碼格式
        'sign' => '', // 簽名，字母大寫
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'merNo' => 'number',
        'netway' => 'paymentVendorId',
        'orderNum' => 'orderId',
        'amount' => 'amount',
        'goodsName' => 'orderId',
        'callBackUrl' => 'notify_url',
        'callBackViewUrl' => 'notify_url',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'version',
        'merNo',
        'netway',
        'random',
        'orderNum',
        'amount',
        'goodsName',
        'callBackUrl',
        'callBackViewUrl',
        'charset',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *      0: 可不返回的參數
     *      1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'merNo' => 1,
        'netway' => 1,
        'orderNum' => 1,
        'amount' => 1,
        'goodsName' => 1,
        'payResult' => 1,
        'payDate' => 1,
    ];

    /**
     * 應答機制訊息
     *
     * @var string
     */
    protected $msg = '0';

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        '1090' => 'WX', // 微信_二維
        '1092' => 'ZFB', // 支付寶_二維
        '1097' => 'WX_H5', // 微信_手機支付
        '1098' => 'ZFB_WAP', // 支付寶_手機支付
        '1103' => 'QQ', // QQ_二維
        '1104' => 'QQ_WAP', // QQ_手機支付
        '1107' => 'JD', // 京東錢包_二維
        '1108' => 'JD_WAP', // 京東錢包_手機支付
        '1109' => 'BAIDU', // 百度錢包_二維
        '1111' => 'UNION_WALLET', // 銀聯錢包_二維
        '1115' => 'WX_AUTH_CODE', // 微信條碼
        '1118' => 'WX_AUTH_CODE_H5', // 微信條碼_手機支付
    ];

    /**
     * 二維支付銀行
     *
     * @var array
     */
    protected $qrcodeBank = [
        '1090',
        '1092',
        '1103',
        '1107',
        '1109',
        '1111',
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

        // 從內部給定值到參數
        foreach ($this->requireMap as $paymentKey => $internalKey) {
            $this->requestData[$paymentKey] = $this->options[$internalKey];
        }

        // 帶入未支援的銀行就噴例外
        if (!array_key_exists($this->requestData['netway'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $this->requestData['random'] = strval(rand(0, 9999));
        $this->requestData['amount'] = strval(round($this->requestData['amount'] * 100));
        $this->requestData['netway'] = $this->bankMap[$this->requestData['netway']];

        // 設定支付平台需要的加密串
        $this->requestData['sign'] = $this->encode();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'method' => 'POST',
            'uri' => '/api/pay.action',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => 'data=' . json_encode($this->requestData, JSON_UNESCAPED_SLASHES),
            'header' => [],
            'timeout' => 20,
        ];

        $result = $this->curlRequest($curlParam);
        $parseData = json_decode($result, true);

        if (!isset($parseData['stateCode']) || !isset($parseData['msg'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        if ($parseData['stateCode'] != '00') {
            throw new PaymentConnectionException($parseData['msg'], 180130, $this->getEntryId());
        }

        if (!isset($parseData['qrcodeUrl'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        // 二維支付
        if (in_array($this->options['paymentVendorId'], $this->qrcodeBank)) {
            $this->setQrcode($parseData['qrcodeUrl']);

            return [];
        }

        return [
            'post_url' => $parseData['qrcodeUrl'],
            'params' => [],
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

        $encodeData = [];

        // 組織加密串
        foreach ($this->decodeParams as $paymentKey => $require) {
            if ($require && array_key_exists($paymentKey, $this->options)) {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        ksort($encodeData);

        $encodeStr = json_encode($encodeData) . $this->privateKey;

        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['sign'] != strtoupper(md5($encodeStr))) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['payResult'] != '00') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['orderNum'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['amount'] != round($entry['amount'] * 100)) {
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
            $encodeData[$index] = $this->requestData[$index];
        }

        ksort($encodeData);

        $encodeStr = json_encode($encodeData, JSON_UNESCAPED_SLASHES);
        $encodeStr .= $this->privateKey;

        return strtoupper(md5($encodeStr));
    }
}

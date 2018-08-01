<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 *　微笑支付
 */
class SmilePay extends PaymentBase
{
    /**
     * 支付提交參數
     *
     * @var array
     */
    protected $requestData = [
        'merchantId' => '', // 商戶號
        'payMode' => 'Bank', // 支付方式，網銀:Bank
        'orderNo' => '', // 商戶訂單號
        'orderAmount' => '', // 訂單金額，保留小數點兩位，單位：元
        'goods' => '', // 商品名稱
        'notifyUrl' => '', // 異步通知網址
        'returnUrl' => '', // 同步通知網址
        'bank' => '', // 銀行代碼，網銀用
        'memo' => '', // 訂單備註，非必填
        'encodeType' => 'SHA2', // 固定值
        'signSHA2' => '', // 簽名
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'merchantId' => 'number',
        'orderNo' => 'orderId',
        'orderAmount' => 'amount',
        'goods' => 'username',
        'notifyUrl' => 'notify_url',
        'returnUrl' => 'notify_url',
        'bank' => 'paymentVendorId',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'merchantId',
        'payMode',
        'orderNo',
        'orderAmount',
        'goods',
        'notifyUrl',
        'returnUrl',
        'bank',
        'encodeType',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'merchantId' => 1,
        'payMode' => 1,
        'orderNo' => 1,
        'orderAmount' => 1,
        'tradeNo' => 1,
        'encodeType' => 1,
    ];

    /**
     * 應答機制訊息
     *
     * @var string
     */
    protected $msg = 'SUCCESS';

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        '1' => 'ICBC', // 工商銀行
        '2' => 'COMM', // 交通銀行
        '3' => 'ABC', // 農業銀行
        '4' => 'CCB', // 建設銀行
        '5' => 'CMB', // 招商銀行
        '6' => 'CMBC', // 民生銀行
        '8' => 'SPDB', // 上海浦東發展銀行
        '10' => 'CIB', // 興業銀行
        '11' => 'CNCB', // 中信銀行
        '12' => 'CEB', // 光大銀行
        '13' => 'HXB', // 華夏銀行
        '14' => 'GDB', // 廣發銀行
        '15' => 'PAB', // 平安銀行
        '17' => 'BOC', // 中國銀行
        '278' => 'BankEX', // 銀聯在線
        '1088' => 'BankEX', // 銀聯在線_手機支付
        '1090' => 'Wechat', // 微信_二維
        '1092' => 'Alipay', // 支付寶_二維
        '1100' => 'Bank', // 手機收銀台
        '1102' => 'Bank', // 網銀收銀台
        '1103' => 'QQ', // QQ_二維
        '1104' => 'QQH5', // QQ_手機支付
        '1107' => 'JD', // 京東_二維
        '1111' => 'BankQRCode', // 銀聯_二維
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

        // 從內部設值到支付參數
        foreach ($this->requireMap as $paymentKey => $internalKey) {
            $this->requestData[$paymentKey] = $this->options[$internalKey];
        }

        // 帶入未支援的銀行就噴例外
        if (!array_key_exists($this->requestData['bank'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $this->requestData['orderAmount'] = sprintf('%.2f', $this->requestData['orderAmount']);
        $this->requestData['bank'] = $this->bankMap[$this->requestData['bank']];

        // 銀聯在線、收銀台需調整提交參數
        if (in_array($this->options['paymentVendorId'], [278, 1088, 1100, 1102])) {
            $this->requestData['payMode'] = $this->requestData['bank'];
            $this->requestData['bank'] = '';
        }

        // 二維、手機支付
        if (in_array($this->options['paymentVendorId'], [1090, 1092, 1103, 1104, 1107, 1111])) {
            $this->requestData['payMode'] = $this->requestData['bank'];
            $this->requestData['bank'] = '';

            $this->requestData['signSHA2'] = $this->encode();

            if (trim($this->options['verify_url']) == '') {
                throw new PaymentException('No verify_url specified', 180140);
            }

            $curlParam = [
                'method' => 'POST',
                'uri' => '/v10/sha2/',
                'ip' => $this->options['verify_ip'],
                'host' => $this->options['verify_url'],
                'param' => http_build_query($this->requestData),
                'header' => [],
            ];

            $result = $this->curlRequest($curlParam);

            if (strpos($result, 'Code') !== false) {
                throw new PaymentConnectionException('Pay error', 180130, $this->getEntryId());
            }

            // 手機支付
            if ($this->options['paymentVendorId'] == 1104) {
                return [
                    'post_url' => $result,
                    'params' => [],
                ];
            }

            // 回傳網址為Qrcode圖片網址，直接印出Qrcode
            $html = sprintf('<img src="%s"/>', $result);

            $this->setHtml($html);

            return [];
        }

        // 設定加密簽名
        $this->requestData['signSHA2'] = $this->encode();

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

        // 商家額外的參數設定
        $names = ['HashIV'];
        $extra = $this->getMerchantExtraValue($names);

        $encodeData = [];

        // 組織加密串
        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options)) {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        ksort($encodeData);

        $encodeData = array_merge(['SHA2Key' => $this->privateKey], $encodeData);
        $encodeData['HashIV'] = $extra['HashIV'];

        $encodeStr = urldecode(http_build_query($encodeData));

        $sign = strtoupper(hash('sha256', strtolower(urlencode($encodeStr))));

        if (!isset($this->options['signSHA2'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['signSHA2'] != $sign) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['success'] !== 'Y') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['orderNo'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['orderAmount'] != $entry['amount']) {
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
        // 商家額外的參數
        $names = ['HashIV'];
        $extra = $this->getMerchantExtraValue($names);

        foreach ($this->encodeParams as $index) {
            $encodeData[$index] = $this->requestData[$index];
        }

        ksort($encodeData);

        $encodeData = array_merge(['SHA2Key' => $this->privateKey], $encodeData);

        $encodeData['HashIV'] = $extra['HashIV'];
        $encodeStr = urldecode(http_build_query($encodeData));

        return strtoupper(hash('sha256', strtolower(urlencode($encodeStr))));
    }
}

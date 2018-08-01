<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * UNPAY樂天付
 */
class UNPay extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'parter' => '', // 商號
        'type' => '', // 銀行類型
        'value' => '', // 金額，單位:元
        'orderid' => '', // 訂單號
        'tyid' => '102', // 支付方式編號，網銀:102
        'callbackurl' => '', // 異步通知url，不能串參數
        'hrefbackurl' => '', // 同步通知url，非必填
        'attach' => '', // 備註訊息，非必填
        'payerIp' => '', // 支付用戶ip，網銀支付會判斷玩家支付時的ip和該值是否相同
        'sign' => '', // md5簽名
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'parter' => 'number',
        'type' => 'paymentVendorId',
        'value' => 'amount',
        'orderid' => 'orderId',
        'callbackurl' => 'notify_url',
        'payerIp' => 'ip',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'parter',
        'type',
        'value',
        'orderid',
        'tyid',
        'callbackurl',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'orderid' => 1,
        'opstate' => 1,
        'ovalue' => 1,
    ];

    /**
     * 應答機制訊息
     *
     * @var string
     */
    protected $msg = 'opstate=0';

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        '1' => '967', // 中國工商銀行
        '3' => '964', // 中國農業銀行
        '4' => '965', // 中國建設銀行
        '5' => '970', // 招商銀行
        '6' => '980', // 中國民生銀行
        '9' => '989', // 北京銀行
        '11' => '962', // 中信銀行
        '12' => '986', // 中國光大銀行
        '16' => '971', // 中國郵政
        '17' => '963', // 中國銀行
        '19' => '975', // 上海銀行
        '1088' => '1005', // 銀聯在線_手機支付
        '1090' => '991', // 微信_二維
        '1098' => '1006', // 支付寶_手機支付
        '1103' => '993', // QQ_二維
        '1111' => '1021', // 銀聯_二維
    ];

    /**
     * 支付平台支援的支付編碼參數
     *
     * @var array
     */
    protected $tyidMap = [
        '1088' => '1020',
        '1090' => '99',
        '1098' => '980',
        '1103' => '100',
        '1111' => '101',
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

        // 從內部給定值到參數
        foreach ($this->requireMap as $paymentKey => $internalKey) {
            $this->requestData[$paymentKey] = $this->options[$internalKey];
        }

        // 帶入未支援的銀行就噴例外
        if (!array_key_exists($this->requestData['type'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $this->requestData['value'] = sprintf('%.2f', $this->requestData['value']);
        $this->requestData['type'] = $this->bankMap[$this->requestData['type']];

        // 非網銀調整支付編碼參數
        if (in_array($this->options['paymentVendorId'], [1088, 1090, 1098, 1103, 1111])) {
            $this->requestData['tyid'] = $this->tyidMap[$this->options['paymentVendorId']];
        }

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

        // 組織加密串
        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options)) {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        $encodeStr = urldecode(http_build_query($encodeData));
        $encodeStr .= $this->privateKey;

        // 沒有sign就要丟例外
        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['sign'] != md5($encodeStr)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        // 請求參數無效
        if ($this->options['opstate'] == '-1') {
            throw new PaymentConnectionException('Invalid pay parameters', 180129, $this->getEntryId());
        }

        // 簽名錯誤
        if ($this->options['opstate'] == '-2') {
            throw new PaymentConnectionException(
                'PaymentGateway error, Merchant sign error',
                180127,
                $this->getEntryId()
            );
        }

        if ($this->options['opstate'] != '0') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['orderid'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['ovalue'] != $entry['amount']) {
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

        $encodeStr = urldecode(http_build_query($encodeData));
        $encodeStr .= $this->privateKey;

        return md5($encodeStr);
    }
}

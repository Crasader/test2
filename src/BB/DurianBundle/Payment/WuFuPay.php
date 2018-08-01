<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 五福支付
 */
class WuFuPay extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'svcName' => 'gatewayPay', // 服務名稱，網銀：gatewayPay
        'merId' => '', // 商號
        'merchOrderId' => '', // 訂單號
        'tranType' => '',  // 交易類型
        'pName' => '', // 商品名稱，設定username方便業主比對
        'amt' => '', // 金額，單位:分
        'notifyUrl' => '', // 異步通知網址
        'retUrl' => '', // 頁面通知網址
        'showCashier' => '1', // 是否顯示收銀台
        'merData' => '', // 商戶自訂數據，非必填
        'md5value' => '', // MD5簽名
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'merId' => 'number',
        'merchOrderId' => 'orderId',
        'tranType' => 'paymentVendorId',
        'pName' => 'username',
        'amt' => 'amount',
        'notifyUrl' => 'notify_url',
        'retUrl' => 'notify_url',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'amt',
        'merId',
        'merchOrderId',
        'notifyUrl',
        'pName',
        'retUrl',
        'showCashier',
        'svcName',
        'tranType',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'amt' => 1,
        'merId' => 0,
        'merchOrderId' => 1,
        'orderId' => 1,
        'orderStatusMsg' => 1,
        'status' => 1,
        'tranTime' => 1,
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
        '1' => '1000042', // 中國工商銀行
        '2' => '1000017', // 交通銀行
        '3' => '1000044', // 中國農業銀行
        '4' => '1000011', // 中國建設銀行
        '5' => '1000046', // 招商銀行
        '6' => '1000039', // 中國民生銀行
        '8' => '1000022', // 上海浦東發展銀行
        '9' => '1000034', // 北京銀行
        '10' => '1000023', // 興業銀行
        '11' => '1000027', // 中信銀行
        '12' => '1000025', // 中國光大銀行
        '13' => '1000035', // 華夏銀行
        '14' => '1000015', // 廣東發展銀行
        '15' => '1000019', // 平安銀行
        '16' => '1000031', // 中國郵政
        '17' => '1000013', // 中國銀行
        '19' => '1000030', //上海銀行
        '278' => '2000047', // 銀聯在線
        '1088' => '2000048', // 銀聯在線_手機支付
        '1092' => 'ALIPAY_NATIVE', // 支付寶_二維
        '1098' => 'ALIPAY_H5', // 支付寶_手機支付
        '1103' => 'QQ_NATIVE', // QQ_二維
        '1104' => 'QQ_H5', // QQ_手機支付
        '1107' => 'JD_NATIVE', // 京東_二維
        '1108' => 'JD_H5', // 京東_手機支付
        '1111' => 'UNIONPAY_NATIVE', // 銀聯_二維
        '1115' => 'WEIXIN_NATIVE', // 微信_條碼
        '1118' => 'WEIXIN_NATIVE', // 微信_條碼手機支付
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

        // 檢查銀行代碼是否支援
        if (!array_key_exists($this->requestData['tranType'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $this->requestData['amt'] = round($this->requestData['amt'] * 100);
        $this->requestData['tranType'] = $this->bankMap[$this->requestData['tranType']];

        // 二維、手機額外的參數設定
        if (in_array($this->options['paymentVendorId'], [1092, 1098, 1103, 1104, 1107, 1108, 1111, 1115, 1118])) {
            $this->requestData['svcName'] = 'UniThirdPay';
        }

        // 銀聯在線額外的參數設定
        if ($this->options['paymentVendorId'] == 278) {
            $this->requestData['svcName'] = 'pcQuickPay';
        }

        // 銀聯在線_手機支付額外的參數設定
        if ($this->options['paymentVendorId'] == 1088) {
            $this->requestData['svcName'] = 'wapQuickPay';
        }

        // 設定支付平台需要的加密串
        $this->requestData['md5value'] = $this->encode();

        return $this->requestData;
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

        $encodeStr = '';

        // 組織加密串
        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options)) {
                $encodeStr .= $this->options[$paymentKey];
            }
        }

        $encodeStr .= $this->privateKey;

        if (!isset($this->options['md5value'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['md5value'] != strtoupper(md5($encodeStr))) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['status'] !== '0') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['merchOrderId'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['amt'] != round($entry['amount'] * 100)) {
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
        $encodeStr = '';

        foreach ($this->encodeParams as $index) {
            $encodeStr .= $this->requestData[$index];
        }

        $encodeStr .= $this->privateKey;

        return strtoupper(md5($encodeStr));
    }
}

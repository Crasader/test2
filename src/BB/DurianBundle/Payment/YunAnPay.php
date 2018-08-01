<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 云安付
 */
class YunAnPay extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'partner' => '', // 商號
        'banktype' => '', // 銀行代碼
        'paymoney' => '', // 金額(單位:元)
        'ordernumber' => '', // 訂單號
        'callbackurl' => '', // 異步通知url，不能串參數
        'hrefbackurl' => '', // 同步通知url，可空
        'attach' => '', // 備註，可空
        'sign' => '', // 加密簽名
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'partner' => 'number',
        'banktype' => 'paymentVendorId',
        'paymoney' => 'amount',
        'ordernumber' => 'orderId',
        'callbackurl' => 'notify_url',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'partner',
        'banktype',
        'paymoney',
        'ordernumber',
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
        'partner' => 1,
        'ordernumber' => 1,
        'orderstatus' => 1,
        'paymoney' => 1,
    ];

    /**
     * 應答機制訊息
     *
     * @var string
     */
    protected $msg = 'ok';

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        '1' => 'ICBC', // 中國工商銀行
        '2' => 'BOCO', // 交通銀行
        '3' => 'ABC', // 中國農業銀行
        '4' => 'CCB', // 中國建設銀行
        '5' => 'CMB', // 招商銀行
        '6' => 'CMBC', // 中國民生銀行
        '9' => 'BCCB', // 北京銀行
        '10' => 'CIB', // 興業銀行
        '11' => 'CTTIC', // 中信銀行
        '12' => 'CEB', // 中國光大銀行
        '14' => 'GDB', // 廣東發展銀行
        '15' => 'PINGANBANK', // 平安銀行
        '16' => 'PSBS', // 中國郵政
        '17' => 'BOC', // 中國銀行
        '1090' => 'WEIXINPAY', // 微信支付_二維
        '1092' => 'ALIPAY', // 支付寶_二維
        '1098' => 'ALIWAPPAY', // 支付寶_手機支付
        '1103' => 'TENPAY', // QQ錢包_二維
        '1107' => 'JDPAY', // 京東錢包_二維
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
        if (!array_key_exists($this->requestData['banktype'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $this->requestData['paymoney'] = sprintf('%.2f', $this->requestData['paymoney']);
        $this->requestData['banktype'] = $this->bankMap[$this->requestData['banktype']];

        // 設定支付平台需要的加密串
        $this->requestData['sign'] = $this->encode();

        // 檢查是否有postUrl(支付平台提交的url)
        if (trim($this->options['postUrl']) == '') {
            throw new PaymentException('No pay parameter specified', 180145);
        }

        $params = http_build_query($this->requestData);
        $this->requestData['act_url'] = $this->options['postUrl'] . '?' . $params;

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

        // 依key1=value1&key2=value2&...&keyN=valueN之後做md5
        $encodeStr = urldecode(http_build_query($encodeData));
        $encodeStr .= $this->privateKey;

        // 如果沒有返回簽名要丟例外
        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['sign'] != md5($encodeStr)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        // 訂單未付款成功
        if ($this->options['orderstatus'] != '1') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['ordernumber'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['paymoney'] != $entry['amount']) {
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

        // 加密設定
        foreach ($this->encodeParams as $index) {
            $encodeData[$index] = $this->requestData[$index];
        }

        // 依key1=value1&key2=value2&...&keyN=valueN之後做md5
        $encodeStr = urldecode(http_build_query($encodeData));
        $encodeStr .= $this->privateKey;

        return md5($encodeStr);
    }
}

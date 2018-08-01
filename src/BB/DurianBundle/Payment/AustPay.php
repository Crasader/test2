<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * AustPay
 */
class AustPay extends PaymentBase
{
    /**
     * 支付提交參數
     *
     * @var array
     */
    protected $requestData = [
        'Amount' => '', // 金額，保留小數兩位
        'merchantid' => '', // 商號
        'siteid' => '', // 子站ID
        'order_id' => '', // 訂單號
        'security_code' => '', // 簽名
        'type' => '3', // 通道。1:支付寶, 2:微信, 3:網銀, 4:QQ錢包
        'bankcode' => '', // 網銀代碼，type為3時，可直連網銀
        'version' => '2.0', // 版本
        'return_url' => '', // 同步返回網址
        'notify_url' => '', // 異步返回網址
    ];

    /**
     * 支付提交參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'merchantid' => 'number',
        'order_id' => 'orderId',
        'Amount' => 'amount',
        'return_url' => 'notify_url',
        'notify_url' => 'notify_url',
        'bankcode' => 'paymentVendorId',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'order_id',
        'Amount',
        'merchantid',
        'siteid',
    ];

    /**
     * 返回驗證時需要加密的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'system_id' => 1,
        'oid' => 1,
        'successcode' => 1,
        'order_amount' => 1,
        'merchantid' => 1,
        'siteid' => 1,
    ];

    /**
     * 應答機制訊息
     *
     * @var string
     */
    protected $msg = '<result>yes</result>';

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        1 => '9', // 工商銀行
        2 => '67', // 交通銀行
        3 => '43', // 農業銀行
        4 => '4', // 建設銀行
        5 => '3', // 招商銀行
        6 => '28', // 民生銀行總行
        8 => '69', // 上海浦東發展銀行
        10 => '33', // 興業銀行
        11 => '84', // 中信銀行
        12 => '74', // 光大銀行
        14 => '44', // 廣東發展銀行
        16 => '9', // 中國郵政
        17 => '85', // 中國銀行
        1090 => '', // 微信_二維
        1092 => '', // 支付寶_二維
        1103 => '', // QQ錢包
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

        // 驗證支付參數
        $this->payVerify();

        // 從內部設值到支付參數
        foreach ($this->requireMap as $paymentKey => $internalKey) {
            $this->requestData[$paymentKey] = $this->options[$internalKey];
        }

        // 檢查銀行代碼是否支援
        if (!array_key_exists($this->requestData['bankcode'], $this->bankMap)) {
            throw new PaymentException(
                'PaymentVendor is not supported by PaymentGateway',
                180066
            );
        }

        // 取得商家附加設定值
        $merchantExtraValues = $this->getMerchantExtraValue(['siteid']);

        // 額外的參數設定
        $this->requestData['bankcode'] = $this->bankMap[$this->requestData['bankcode']];
        $this->requestData['Amount'] = sprintf('%.2f', $this->requestData['Amount']);
        $this->requestData['siteid'] = $merchantExtraValues['siteid'];

        // 微信二維碼
        if ($this->options['paymentVendorId'] == 1090) {
            $this->requestData['type'] = 2;
        }

        // 支付寶二維碼
        if ($this->options['paymentVendorId'] == 1092) {
            $this->requestData['type'] = 1;
        }

        // QQ錢包二維碼
        if ($this->options['paymentVendorId'] == 1103) {
            $this->requestData['type'] = 4;
        }

        // 設定加密簽名
        $this->requestData['security_code'] = $this->encode();

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

        // 驗證返回參數
        $this->payResultVerify();

        // 組合參數驗證加密簽名
        $encodeStr = '';
        foreach (array_keys($this->decodeParams) as $paymentKey) {
            $encodeStr .= $this->options[$paymentKey];
        }

        $encodeStr .= $this->privateKey;

        // 沒有返回 add_string 就要丟例外
        if (!isset($this->options['add_string'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if (strtoupper($this->options['add_string']) != strtoupper(md5($encodeStr))) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['successcode'] !== 'ok') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['oid'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['order_amount'] != $entry['amount']) {
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

        return md5(hash('sha256', hash('sha256', md5($encodeStr))));
    }
}

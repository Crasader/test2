<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * SA支付
 */
class SAPay extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'key' => '', // 商戶支付key
        'order_amount' => '', // 訂單金額，單位:元，保留小數點兩位
        'account_id' => '', // 商戶號
        'order_no' => '', // 商戶訂單號
        'pay_type' => '', // 產品類型
        'order_time' => '', // 下單時間，格式YmdHis
        'return_url' => '', // 頁面通知地址
        'callback_url' => '', // 異步通知地址
        'request_ip' => '', // 請求客戶端ip
        'mac' => '', // 簽名
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'key' => 'number',
        'order_amount' => 'amount',
        'account_id' => 'number',
        'order_no' => 'orderId',
        'pay_type' => 'paymentVendorId',
        'order_time' => 'orderCreateDate',
        'return_url' => 'notify_url',
        'callback_url' => 'notify_url',
        'request_ip' => 'ip',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'account_id',
        'order_time',
        'order_no',
        'order_amount',
        'request_ip',
        'callback_url',
        'pay_type',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'status' => 1,
        'pay_type' => 1,
        'order_no' => 1,
        'order_status' => 1,
        'order_amount' => 1,
        'order_time' => 1,
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
        '1098' => 'ALIH5', // 支付寶_手機支付
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

        // 從內部給定值到參數
        foreach ($this->requireMap as $paymentKey => $internalKey) {
            $this->requestData[$paymentKey] = $this->options[$internalKey];
        }

        // 帶入未支援的銀行就噴例外
        if (!array_key_exists($this->requestData['pay_type'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 商家額外的參數設定
        $this->requestData['pay_type'] = $this->bankMap[$this->requestData['pay_type']];
        $this->requestData['order_amount'] = sprintf('%.2f', $this->requestData['order_amount']);
        $createAt = new \Datetime($this->requestData['order_time']);
        $this->requestData['order_time'] = $createAt->format('YmdHis');

        $this->requestData['mac'] = $this->encode();

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

        $encodeData = [];

        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options)) {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        $encodeData['key'] = $this->privateKey;
        $encodeStr = urldecode(http_build_query($encodeData));

        // 若沒有返回簽名需丟例外
        if (!isset($this->options['mac'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['mac'] != md5($encodeStr)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['order_status'] == 'WAITING_PAYMENT') {
            throw new PaymentConnectionException('Unpaid order', 180062, $this->getEntryId());
        }

        if ($this->options['order_status'] != 'SUCCESS' || $this->options['status'] !== '200') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['order_no'] != $entry['id']) {
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
        $encodeData = [];

        foreach ($this->encodeParams as $index) {
            if (isset($this->requestData[$index])) {
                $encodeData[$index] = $this->requestData[$index];
            }
        }

        $encodeData['key'] = $this->privateKey;
        $encodeStr = urldecode(http_build_query($encodeData));

        return strtoupper(md5($encodeStr));
    }
}

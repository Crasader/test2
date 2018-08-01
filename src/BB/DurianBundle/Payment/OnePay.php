<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 萬付支付
 */
class OnePay extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'ag_account' => '', // 商號
        'amount' => '', // 金額
        'order_no' => '', // 訂單號
        'pay_time' => '', // 支付時間。YmdHis
        'pay_ip' => '', // 會員ip
        'attach' => '', // 附件，異步通知原樣返回
        'sign_type' => '2', // 簽名種類。1: ECB, 2: MD5
        'sign' => '', // 簽名
        'order_type' => '', // 支付種類
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'ag_account' => 'number',
        'pay_time' => 'orderCreateDate',
        'order_no' => 'orderId',
        'amount' => 'amount',
        'attach' => 'username',
        'pay_ip' => 'ip',
        'order_type' => 'paymentVendorId',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'order_no',
        'amount',
        'ag_account',
        'pay_ip',
        'pay_time',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'order_no' => 1,
        'amount' => 1,
        'ag_account' => 1,
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
        '1090' => '1', // 微信二維
        '1097' => '4', // 微信手機支付
        '1100' => '6', // 收銀台(手機支付)
        '1102' => '6', // 網銀收銀台
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
        if (!array_key_exists($this->requestData['order_type'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $date = new \DateTime($this->requestData['pay_time']);
        $this->requestData['pay_time'] = $date->format('YmdHis');
        $this->requestData['amount'] = sprintf('%.2f', $this->requestData['amount']);
        $this->requestData['order_type'] = $this->bankMap[$this->requestData['order_type']];

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
        $this->verifyPrivateKey();
        $this->payResultVerify();

        $encodeData = [];

        // 加密設定
        foreach (array_keys($this->decodeParams) as $paymentKey) {
            $encodeData[] = $this->options[$paymentKey];
        }

        // 通知返回的加密設定
        $encodeData[] = $this->privateKey;
        $encodeStr = implode('|', $encodeData);

        // 檢查簽名
        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if (strtoupper($this->options['sign']) !== strtoupper(md5($encodeStr))) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        // 返回非成功
        if ($this->options['status'] != 'SUCCESS') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        // 檢查單號
        if ($this->options['order_no'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        // 檢查金額
        if ($this->options['amount'] != $entry['amount']) {
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
            $encodeData[] = $this->requestData[$index];
        }

        $encodeData[] = $this->privateKey;
        $encodeStr = implode('|', $encodeData);

        return md5($encodeStr);
    }
}

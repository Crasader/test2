<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * Payza
 */
class Payza extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'ap_merchant' => '', //商號
        'ap_purchasetype' => 'item', //商品種類
        'ap_itemname' => '', //訂單號
        'ap_amount' => '', //金額，精確到小數後兩位
        'ap_currency' => 'USD' //幣別，目前只開放美金
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'ap_merchant' => 'number',
        'ap_itemname' => 'orderId',
        'ap_amount' => 'amount'
    ];

    /**
     * 回傳支付時要給支付平台驗證的資料
     *
     * @return array
     */
    public function getVerifyData()
    {
        $this->payVerify();

        // 從內部給定值到參數
        foreach ($this->requireMap as $paymentKey => $internalKey) {
            $this->requestData[$paymentKey] = $this->options[$internalKey];
        }

        $this->requestData['ap_amount'] = sprintf('%.2f', $this->requestData['ap_amount']);

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

        $verifyKey = [
            'ap_merchant',
            'ap_securitycode',
            'ap_status',
            'ap_referencenumber',
            'ap_amount',
            'ap_currency',
            'ap_test',
            'ap_itemname'
        ];

        foreach ($verifyKey as $index) {
            //如果沒有index就丟例外
            if (!isset($this->options[$index])) {
                throw new PaymentException('No return parameter specified', 180137);
            }
        }

        // 驗證是否為測試模式
        if (trim($this->options['ap_test']) === '1') {
            $msg = 'Test mode is enabled, please turn off test mode and try again later';

            throw new PaymentConnectionException($msg, 180084, $this->getEntryId());
        }

        if ($this->options['ap_status'] != 'Success') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->privateKey != $this->options['ap_securitycode']) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['ap_merchant'] != $entry['merchant_number']) {
            throw new PaymentConnectionException(
                'PaymentGateway error, Illegal merchant number',
                180082,
                $this->getEntryId()
            );
        }

        if ($this->options['ap_currency'] != 'USD') {
            throw new PaymentConnectionException('Illegal Order currency', 180083, $this->getEntryId());
        }

        if ($this->options['ap_itemname'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['ap_amount'] != $entry['amount']) {
            throw new PaymentException('Order Amount error', 180058);
        }
    }
}

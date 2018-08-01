<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * OKpay
 */
class OKpay extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'ok_receiver' => '', //商號
        'ok_item_1_name' => '', //訂單號
        'ok_item_1_price' => '', //金額，精確到小數後兩位
        'ok_currency' => 'USD' //幣別，目前只開放美金
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'ok_receiver' => 'number',
        'ok_item_1_name' => 'orderId',
        'ok_item_1_price' => 'amount'
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

        $this->requestData['ok_item_1_price'] = sprintf('%.2f', $this->requestData['ok_item_1_price']);

        return $this->requestData;
    }

    /**
     * 驗證線上支付是否成功
     *
     * @param array $entry
     */
    public function verifyOrderPayment($entry)
    {
        // 需要檢查的參數
        $verifyKey = [
            'ok_txn_kind',
            'ok_receiver_wallet',
            'ok_txn_currency',
            'ok_txn_status',
            'ok_item_1_name',
            'ok_item_1_amount'
        ];

        foreach ($verifyKey as $index) {
            //如果沒有index就丟例外
            if (!array_key_exists($index, $this->options)) {
                throw new PaymentException('No return parameter specified', 180137);
            }
        }

        // ok_txn_kind必須為payment_link，否則跳例外
        if ($this->options['ok_txn_kind'] != 'payment_link') {
            throw new PaymentConnectionException('Transaction kind error', 180085, $this->getEntryId());
        }

        // ok_txn_status必須為completed，否則跳例外
        if ($this->options['ok_txn_status'] != 'completed') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        // 檢查商號是否正確
        if ($this->options['ok_receiver_wallet'] != $entry['merchant_number']) {
            throw new PaymentConnectionException(
                'PaymentGateway error, Illegal merchant number',
                180082,
                $this->getEntryId()
            );
        }

        // 檢查幣別是否正確
        if ($this->options['ok_txn_currency'] != 'USD') {
            throw new PaymentConnectionException('Illegal Order currency', 180083, $this->getEntryId());
        }

        // 檢查訂單號是否正確
        if ($this->options['ok_item_1_name'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        // 檢查金額是否正確
        if ($this->options['ok_item_1_amount'] != $entry['amount']) {
            throw new PaymentException('Order Amount error', 180058);
        }

        $verifyUrl = $this->options['verify_url'];

        if (trim($verifyUrl) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        // 設定參數ok_verify=true
        $this->options['ok_verify'] = 'true';

        // 連結到https://www.okpay.com/ipn-verify.html?ok_verify=true
        $curlParam = [
            'method' => 'GET',
            'uri' => '/ipn-verify.html',
            'ip' => $this->options['verify_ip'],
            'host' => $verifyUrl,
            'param' => http_build_query($this->options),
            'header' => []
        ];

        $result = $this->curlRequest($curlParam);

        // 回傳內容為TEST
        if ($result == 'TEST') {
            $msg = 'Test mode is enabled, please turn off test mode and try again later';

            throw new PaymentConnectionException($msg, 180084, $this->getEntryId());
        }

        // 回傳內容不是VERIFIED
        if ($result != 'VERIFIED') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }
    }
}

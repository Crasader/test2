<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * In App Purchase IOS
 */
class IAPIOS extends PaymentBase
{
    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'cash_deposit_entry_id' => 1, // 訂單號
        'receipt' => 1, // 收據
        'amount' => 1, // 支付金額
    ];

    /**
     * 回傳支付時要給支付平台驗證的資料
     *
     * @return array
     */
    public function getVerifyData()
    {
        return ['cash_deposit_entry_id' => $this->options['orderId']];
    }

    /**
     * 驗證線上支付是否成功
     *
     * @param array $entry
     */
    public function verifyOrderPayment($entry)
    {
        $this->payResultVerify();

        $param = json_encode(['receipt-data' => $this->options['receipt']]);

        $curlParam = [
            'method' => 'POST',
            'uri' => '/verifyReceipt',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => $param,
            'header' => [],
        ];

        $result = $this->curlRequest($curlParam);

        $resultData = json_decode($result, true);

        if (!isset($resultData['status'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        $status = trim($resultData['status']);

        if ($status == '21000') {
            throw new PaymentConnectionException(
                'The App Store could not read the JSON object you provided',
                150180165,
                $this->getEntryId()
            );
        }

        if ($status == '21002') {
            throw new PaymentConnectionException(
                'The data in the receipt-data property was malformed or missing',
                150180166,
                $this->getEntryId()
            );
        }

        if ($status == '21003') {
            throw new PaymentConnectionException(
                'The receipt could not be authenticated',
                150180167,
                $this->getEntryId()
            );
        }

        if ($status == '21004') {
            throw new PaymentConnectionException(
                'The shared secret you provided does not match the shared secret on file for your account',
                150180168,
                $this->getEntryId()
            );
        }

        if ($status == '21005') {
            throw new PaymentConnectionException(
                'The receipt server is not currently available',
                150180169,
                $this->getEntryId()
            );
        }

        if ($status == '21006') {
            throw new PaymentConnectionException(
                'This receipt is valid but the subscription has expired',
                150180170,
                $this->getEntryId()
            );
        }

        if ($status == '21007') {
            throw new PaymentConnectionException(
                'This receipt is from the test environment, ' .
                'but it was sent to the production environment for verification',
                150180171,
                $this->getEntryId()
            );
        }

        if ($status == '21008') {
            throw new PaymentConnectionException(
                'This receipt is from the production environment, ' .
                'but it was sent to the test environment for verification',
                150180172,
                $this->getEntryId()
            );
        }

        if ($status != '0') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['cash_deposit_entry_id'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['amount'] != $entry['amount']) {
            throw new PaymentException('Order Amount error', 180058);
        }
    }
}

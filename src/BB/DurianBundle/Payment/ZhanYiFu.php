<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 展易付
 */
class ZhanYiFu extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'merchant_num' => '', // 商戶號
        'datetime' => '', // 交易日期 Y-m-d H:i:s
        'order_num' => '', // 訂單號
        'title' => '', // 訂單標題
        'body' => '', // 訂單描述
        'pay_money' => '', // 支付金額，保留小數點兩位，單位：元
        'notify_url' => '', // 異步通知網址
        'return_url' => '', // 同步通知網址
        'sign' => '', // 簽名
        'pay_type' => '', // 支付類型
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'merchant_num' => 'number',
        'datetime' => 'orderCreateDate',
        'order_num' => 'orderId',
        'title' => 'orderId',
        'body' => 'orderId',
        'pay_money' => 'amount',
        'notify_url' => 'notify_url',
        'return_url' => 'notify_url',
        'pay_type' => 'paymentVendorId',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'merchant_num',
        'datetime',
        'order_num',
        'title',
        'body',
        'pay_money',
        'notify_url',
        'return_url',
    ];

    /**
     * 返回驗證時需要加密的參數
     *     0: 有返回就要加密的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'order_num' => 1,
        'merchant_num' => 1,
        'pay_money' => 1,
        'order_status' => 1,
        'pay_type' => 1,
        'datetime' => 1,
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
        278 => '6', // 銀聯在線
        1088 => '6', // 銀聯在線_手機支付
        1090 => '1', // 微信_二維
        1102 => '3', // 收銀台
        1103 => '7', // QQ_二維
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

        foreach ($this->requireMap as $paymentKey => $internalKey) {
            $this->requestData[$paymentKey] = $this->options[$internalKey];
        }

        if (!array_key_exists($this->requestData['pay_type'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        $this->requestData['pay_type'] = $this->bankMap[$this->requestData['pay_type']];
        $this->requestData['pay_money'] = sprintf('%.2f', $this->requestData['pay_money']);

        $createAt = new \Datetime($this->requestData['datetime']);
        $this->requestData['datetime'] = $createAt->format('Y-m-d H:i:s');

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
        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options)) {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        ksort($encodeData);
        $encodeData['ukey'] = $this->privateKey;
        $encodeStr = urlencode(urldecode(http_build_query($encodeData)));

        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if (strcasecmp($this->options['sign'], strtoupper(md5($encodeStr))) != 0) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['order_status'] !== '1') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['order_num'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['pay_money'] != $entry['amount']) {
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

        ksort($encodeData);
        $encodeData['ukey'] = $this->privateKey;
        $encodeStr = urlencode(urldecode(http_build_query($encodeData)));

        return strtoupper(md5($encodeStr));
    }
}

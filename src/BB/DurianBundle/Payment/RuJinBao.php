<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 入金寶
 */
class RuJinBao extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'merchant_no' => '', // 商戶編號
        'version' => '1.0.3', // 版本號，固定值
        'out_trade_no' => '', // 訂單號
        'payment_type' => '', // 支付類型
        'payment_bank' => '', // 支付銀行，非必填
        'notify_url' => '', // 異步通知url
        'page_url' => '', // 同步通知url
        'total_fee' => '', // 訂單金額，單位:元，保留到小數第二位
        'trade_time' => '', // 交易時間，格式:YMDHIS
        'user_account' => '', // 用戶帳號，設定username方便業主比對
        'body' => '', // 商品描述，非必填
        'channel' => '', // 渠道信息，非必填
        'sign' => '', // MD5簽名
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'merchant_no' => 'number',
        'out_trade_no' => 'orderId',
        'payment_type' => 'paymentVendorId',
        'notify_url' => 'notify_url',
        'page_url' => 'notify_url',
        'total_fee' => 'amount',
        'trade_time' => 'orderCreateDate',
        'user_account' => 'username',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'merchant_no',
        'version',
        'out_trade_no',
        'payment_type',
        'payment_bank',
        'notify_url',
        'page_url',
        'total_fee',
        'trade_time',
        'user_account',
        'body',
        'channel',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'merchant_no' => 1,
        'version' => 1,
        'out_trade_no' => 1,
        'payment_type' => 1,
        'payment_bank' => 1,
        'trade_no' => 1,
        'trade_status' => 1,
        'notify_time' => 1,
        'body' => 1,
        'total_fee' => 1,
        'obtain_fee' => 1,
    ];

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        '1090' => 'wxpay', // 微信支付_二維
        '1097' => 'wxpaywap', // 微信支付_手機支付
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
        if (!array_key_exists($this->requestData['payment_type'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $this->requestData['payment_type'] = $this->bankMap[$this->requestData['payment_type']];
        $this->requestData['total_fee'] = sprintf('%.2f', $this->requestData['total_fee']);
        $date = new \DateTime($this->requestData['trade_time']);
        $this->requestData['trade_time'] = $date->format('YmdHis');

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

        ksort($encodeData);

        $encodeStr = urldecode(http_build_query($encodeData));
        $encodeStr .= $this->privateKey;

        // 沒有sign就要丟例外
        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if (strcasecmp($this->options['sign'], md5($encodeStr)) != 0) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['trade_status'] !== 'SUCCESS') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['out_trade_no'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['total_fee'] != $entry['amount']) {
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

        ksort($encodeData);

        $encodeStr = urldecode(http_build_query($encodeData));
        $encodeStr .= $this->privateKey;

        return md5($encodeStr);
    }
}

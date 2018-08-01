<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 萬通支付
 */
class OneTung extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'merchant' => '', // 商戶號
        'amount' => '', // 交易金額，保留小數點兩位，單位：元
        'merchant_order_id' => '', // 商戶訂單號
        'pay_method' => '', // 支付方式
        'description' => '', // 產品描述訊息，帶入username
        'notify_url' => '', // 通知URL
        'timestamp' => '', // 請求時間，YmdHis
        'nonce' => '', // 隨機字符串
        'sign' => '', // 簽名
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'merchant' => 'number',
        'amount' => 'amount',
        'merchant_order_id' => 'orderId',
        'pay_method' => 'paymentVendorId',
        'description' => 'username',
        'notify_url' => 'notify_url',
        'timestamp' => 'orderCreateDate',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'merchant',
        'amount',
        'merchant_order_id',
        'pay_method',
        'description',
        'notify_url',
        'timestamp',
        'nonce',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'ret' => 1,
        'msg' => 1,
        'merchant' => 1,
        'amount' => 1,
        'merchant_order_id' => 1,
        'pay_method' => 1,
        'submitted' => 1,
        'success' => 1,
        'notify_url' => 1,
        'QRCode_url' => 0,
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
        '1090' => '微信扫码', // 微信_二維
        '1092' => '支付宝扫码', // 支付寶_二維
        '1103' => 'QQ钱包', // QQ_二維
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
        if (!array_key_exists($this->requestData['pay_method'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 商家額外的參數設定
        $this->requestData['pay_method'] = $this->bankMap[$this->requestData['pay_method']];
        $this->requestData['amount'] = sprintf('%.2f', $this->requestData['amount']);
        $this->requestData['nonce'] = strval(rand(0, 9999));
        $createAt = new \Datetime($this->requestData['timestamp']);
        $this->requestData['timestamp'] = $createAt->format('YmdHis');

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        // 設定支付平台需要的加密串
        $this->requestData['sign'] = $this->encode();

        $curlParam = [
            'method' => 'POST',
            'uri' => '/merchant/api/v1/charge/create',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => json_encode($this->requestData, JSON_UNESCAPED_SLASHES),
            'header' => ['Content-Type' => 'application/json'],
        ];

        $result = $this->curlRequest($curlParam);
        $parseData = json_decode($result, true);

        if (!isset($parseData['ret']) || !isset($parseData['msg'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        if ($parseData['ret'] != 'success') {
            throw new PaymentConnectionException($parseData['msg'], 180130, $this->getEntryId());
        }

        if (!isset($parseData['QRCode_url'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        $this->setQrcode($parseData['QRCode_url']);

        return [];
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

        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options)) {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        ksort($encodeData);
        $encodeData['key'] = $this->privateKey;
        $encodeStr = urldecode(http_build_query($encodeData));

        // 若沒有返回簽名需丟例外
        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if (strcasecmp($this->options['sign'], md5($encodeStr)) != 0) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['success'] != 'True') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['merchant_order_id'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

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
            $encodeData[$index] = $this->requestData[$index];
        }

        ksort($encodeData);
        $encodeData['key'] = $this->privateKey;
        $encodeStr = urldecode(http_build_query($encodeData));

        return strtoupper(md5($encodeStr));
    }
}

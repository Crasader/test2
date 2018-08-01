<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 酷寶享
 */
class Kubxent extends PaymentBase
{
    /**
     * 支付提交參數
     *
     * @var array
     */
    protected $requestData = [
        'account' => '', // 商號
        'resqn' => '', // 訂單號，必須QD_開頭
        'pay_amount' => '', // 訂單金額，單位元
        'notify_url' => '', // 異步通知地址
        'pay_way' => '', // 支付方式
        'sign' => '', // 簽名
    ];

    /**
     * 支付提交參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'account' => 'number',
        'resqn' => 'orderId',
        'pay_amount' => 'amount',
        'notify_url' => 'notify_url',
        'pay_way' => 'paymentVendorId',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'account',
        'resqn',
        'pay_amount',
        'notify_url',
        'pay_way',
    ];

    /**
     * 返回驗證時需要加密的參數
     *     0: 有返回就要加密的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'status' => 1,
        'account' => 1,
        'resqn' => 1,
        'trade_no' => 1,
        'pay_amount' => 1,
        'remark' => 0,
    ];

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        1090 => '2', // 微信_二維
        1092 => '1', // 支付寶_二維
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

        // 從內部設值到支付參數
        foreach ($this->requireMap as $paymentKey => $internalKey) {
            $this->requestData[$paymentKey] = $this->options[$internalKey];
        }

        // 檢查銀行代碼是否支援
        if (!array_key_exists($this->requestData['pay_way'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $this->requestData['pay_way'] = $this->bankMap[$this->requestData['pay_way']];
        $this->requestData['resqn'] = 'QD_' . $this->requestData['resqn'];

        // 設定支付平台需要的加密串
        $this->requestData['sign'] = $this->encode();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'method' => 'POST',
            'uri' => '/api.php?s=/Scanpay/begin_Pay.html',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => http_build_query($this->requestData),
            'header' => [],
        ];

        $result = $this->curlRequest($curlParam);
        $parseData = json_decode($result, true);

        // 成功與失敗返回參數不同
        if (!isset($parseData['trxstatus']) && !isset($parseData['status'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        // 失敗狀況
        if (isset($parseData['status']) || $parseData['trxstatus'] !== '0000') {
            if (isset($parseData['msg'])) {
                throw new PaymentConnectionException($parseData['msg'], 180130, $this->getEntryId());
            }

            throw new PaymentConnectionException('Pay error', 180130, $this->getEntryId());
        }

        if (!isset($parseData['payinfo'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        $this->setQrcode($parseData['payinfo']);

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

        $encodeData = [];

        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options) && trim($this->options[$paymentKey]) !== '') {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        $encodeData['key'] = $this->privateKey;
        ksort($encodeData);
        $encodeStr = urldecode(http_build_query($encodeData));

        // 沒有返回簽名就要丟例外
        if (!isset($this->options['mer_sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['mer_sign'] != md5($encodeStr)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['status'] !== '200') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        // 單號需額外處理
        $this->options['resqn'] = preg_replace('/^QD_/', '', $this->options['resqn']);

        if ($this->options['resqn'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['pay_amount'] != round($entry['amount'] * 100)) {
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
            if (array_key_exists($index, $this->requestData) && trim($this->requestData[$index] !== '')) {
                $encodeData[$index] = $this->requestData[$index];
            }
        }

        $encodeData['key'] = $this->privateKey;
        ksort($encodeData);
        $encodeStr = urldecode(http_build_query($encodeData));

        return md5($encodeStr);
    }
}

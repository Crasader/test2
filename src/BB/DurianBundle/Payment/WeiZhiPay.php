<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 微支匯
 */
class WeiZhiPay extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'type' => 'PayData', // API類型，固定值
        'amount' => '', // 訂單金額，單位:元，精確到分
        'userid' => '', // 訂單號
        'Paytype' => '', // 支付類型
        'callbackurl' => '', // 異步通知網址
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'amount' => 'amount',
        'userid' => 'orderId',
        'Paytype' => 'paymentVendorId',
        'callbackurl' => 'notify_url',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'tradeNo' => 1,
        'desc' => 1,
        'time' => 1,
        'userid' => 1,
        'amount' => 1,
        'status' => 1,
        'type' => 1,
    ];

    /**
     * 應答機制訊息
     *
     * @var string
     */
    protected $msg = 'ok';

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        '1090' => '1', // 微信_二維
        '1092' => '2', // 支付寶_二維
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
        if (!array_key_exists($this->requestData['Paytype'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $this->requestData['Paytype'] = $this->bankMap[$this->requestData['Paytype']];
        $this->requestData['amount'] = sprintf('%.2f', $this->requestData['amount']);

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'method' => 'POST',
            'uri' => '/api/api.aspx',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => http_build_query($this->requestData),
            'header' => ['identifying' => $this->privateKey],
        ];

        $result = $this->curlRequest($curlParam);

        $resultData = [
            'Code' => $result,
            'SuccessUrl' => '',
        ];

        $payUrlMap = [
            1090 => 'WeChat.aspx',
            1092 => 'AliPay.aspx',
        ];

        $payUrl = sprintf(
            '%s%s%s%s',
            $this->options['postUrl'],
            $payUrlMap[$this->options['paymentVendorId']],
            '?',
            http_build_query($resultData)
        );

        return ['act_url' => $payUrl];
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

        $encodeStr = '';

        // 組織加密串
        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options)) {
                $encodeStr .= $this->options[$paymentKey] . '|';
            }
        }

        $encodeStr .= $this->privateKey;

        // 沒有sign就要丟例外
        if (!isset($this->options['sig'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['sig'] != strtoupper(md5($encodeStr))) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['status'] !== '交易成功') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['userid'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['amount'] != $entry['amount']) {
            throw new PaymentException('Order Amount error', 180058);
        }
    }
}

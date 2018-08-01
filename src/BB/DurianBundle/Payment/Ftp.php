<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * FTP支付
 */
class Ftp extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'order_id' => '', // 訂單號
        'amount' => '', // 金額，單位：分
        'currency' => 'CNY', // 幣別
        'api_key' => '', // api key
        'bank' => '', // 銀行列表
        'client_ip' => '', // 客戶IP，必填
        'callback_url' => '', // 異步通知URL
        'return_url' => '', // 同步通知URL
        'auth_version' => '1.0', // 版本
        'auth_key' => '', // 與api key相同
        'auth_timestamp' => '', // 時間戳
        'auth_signature' => '', // 簽名
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'order_id' => 'orderId',
        'amount' => 'amount',
        'api_key' => 'number',
        'bank' => 'paymentVendorId',
        'client_ip' => 'ip',
        'callback_url' => 'notify_url',
        'return_url' => 'notify_url',
        'auth_key' => 'number',
        'auth_timestamp' => 'orderCreateDate',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'order_id',
        'amount',
        'api_key',
        'currency',
        'bank',
        'client_ip',
        'callback_url',
        'return_url',
        'auth_version',
        'auth_key',
        'auth_timestamp',
    ];

    /**
     * 返回驗簽時需要加密的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'amount' => 1,
        'auth_key' => 1,
        'auth_timestamp' => 1,
        'auth_version' => 1,
        'code' => 1,
        'ftp_response' => 1,
        'order_id' => 1,
        'status' => 1,
        'trans_id' => 1,
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
        '1' => 'ICBC', // 工商銀行
        '2' => 'BOCO', // 交通銀行
        '3' => 'ABC', // 農業銀行
        '4' => 'CCB', // 建設銀行
        '5' => 'CMBCHINA', // 招商銀行
        '6' => 'CMBC', // 民生銀行總行
        '8' => 'SPDB', // 上海浦東發展銀行
        '9' => 'BOB', // 北京銀行
        '10' => 'CIB', // 興業銀行
        '11' => 'ECITIC', // 中信銀行
        '12' => 'CEB', // 光大銀行
        '13' => 'HXB', // 華夏銀行
        '14' => 'CGB', // 廣東發展銀行
        '15' => 'PAB', // 平安銀行
        '16' => 'POST', // 中國郵政
        '17' => 'BOC', // 中國銀行
        '19' => 'SHB', // 上海銀行
        '1104' => '' // QQ_手機支付
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

        // 從內部設值到支付參數
        foreach ($this->requireMap as $paymentKey => $internalKey) {
            $this->requestData[$paymentKey] = $this->options[$internalKey];
        }

        // 帶入未支援的支付方式就噴例外
        if (!array_key_exists($this->requestData['bank'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 網銀uri
        $uri = '/cup1/deposit/';

        // QQuri
        if ($this->requestData['bank'] == 1104) {
            $uri = '/qq/deposit/';
        }

        // 額外的參數設定
        $this->requestData['amount'] = round($this->requestData['amount'] * 100);
        $this->requestData['auth_timestamp'] = strtotime($this->requestData['auth_timestamp']);
        $this->requestData['bank'] = $this->bankMap[$this->requestData['bank']];

        $this->requestData['auth_signature'] = $this->encode();

        return [
            'post_url' => $this->options['postUrl'] . $uri,
            'params' => $this->requestData,
        ];
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

        // 產生加密串
        $encodeData = [];

        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options) && $paymentKey !== 'auth_signature') {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }
        ksort($encodeData);

        $encodeStr = urldecode(http_build_query($encodeData));
        $encodeStr = implode("\n", ['POST', 'ftp', $encodeStr]);

        $signature = hash_hmac('sha256', $encodeStr, $this->privateKey);

        // 如果沒有簽名也要丟例外(其他參數都在前面驗證過了，所以不須要驗證)
        if (!isset($this->options['auth_signature'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['auth_signature'] !== $signature) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['ftp_response']['code'] != 1) {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['order_id'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['amount'] != round($entry['amount'] * 100)) {
            throw new PaymentException('Order Amount error', 180058);
        }
    }

    /**
     * 支付時的加密
     *
     * @return array
     */
    protected function encode()
    {
        $encodeData = [];
        foreach ($this->encodeParams as $index) {
            if (array_key_exists($index, $this->requestData)) {
                $encodeData[$index] = $this->requestData[$index];
            }
        }

        ksort($encodeData);

        $encodeStr = urldecode(http_build_query($encodeData));
        $encodeStr = implode("\n", [strtoupper($this->payMethod), 'ftp', $encodeStr]);

        return hash_hmac('sha256', $encodeStr, $this->privateKey);
    }
}

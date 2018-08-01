<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 付营通支付
 */
class FuYingTong extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證參數
     *
     * @var array
     */
    protected $requestData = [
        'type' => 'PayData', // API類型，固定值
        'amount' => '', // 交易金額，單位元，精確到分
        'clientorderno' => '', // 訂單號
        'paytype' => '', // 支付類型，1: 微信，2: 支付寶
        'callbackurl' => '', // 異步通知網址
        'shopid' => '', // 商戶ID號，後臺取得
        'sig' => '', // 簽名
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'amount' => 'amount',
        'clientorderno' => 'orderId',
        'paytype' => 'paymentVendorId',
        'callbackurl' => 'notify_url',
        'shopid' => 'number',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'type',
        'amount',
        'clientorderno',
        'paytype',
        'callbackurl',
        'shopid',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0:可不返回的參數
     *     1:必要返回的參數
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
     * 不同支付方式對應的uri
     *
     * @var array
     */
    private $uriMap = [
        '1090' => '/pay/WeChat.aspx', // 微信
        '1092' => '/pay/AliPay.aspx', // 支付寶
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

        // 從內部給值到參數
        foreach ($this->requireMap as $paymentKey => $internalKey) {
            $this->requestData[$paymentKey] = $this->options[$internalKey];
        }

        // 帶入未支援的銀行就噴例外
        if (!array_key_exists($this->requestData['paytype'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外參數設定
        $this->requestData['paytype'] = $this->bankMap[$this->requestData['paytype']];

        // 設定支付平台需要的加密串
        $this->requestData['sig'] = $this->encode();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'method' => 'POST',
            'uri' => '/api/api_fyt.aspx',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => urldecode(http_build_query($this->requestData)),
            'header' => [],
        ];

        $result = $this->curlRequest($curlParam);

        // 若包含非大寫英數字即錯誤訊息
        if (preg_match('/[^A-Z0-9]/', $result)) {
            throw new PaymentConnectionException($result, 180130, $this->getEntryId());
        }

        // 取得的Code長度固定48
        if (strlen($result) !== 48) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        // 調整提交網址
        $postUrl = $this->options['postUrl'] . $this->uriMap[$this->options['paymentVendorId']];

        // Form使用GET才能正常跳轉
        $this->payMethod = 'GET';

        return [
            'post_url' => $postUrl,
            'params' => [
                'Code' => $result,
                'SuccessUrl' => $this->requestData['callbackurl'],
            ],
        ];
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

        $encodeStr = '';

        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options)) {
                $encodeStr .= $this->options[$paymentKey] . '|';
            }
        }

        $encodeStr .= $this->privateKey;

        // 沒有sig就要丟例外，其他的參數在組織加密串的時候驗證過了
        if (!isset($this->options['sig'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['sig'] !== strtoupper(md5($encodeStr))) {
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

    /**
     * 支付時的加密
     *
     * @return string
     */
    protected function encode()
    {
        $encodeStr = '';

        foreach ($this->encodeParams as $index) {
            $encodeStr .= $this->requestData[$index] . '|';
        }

        $encodeStr .= $this->privateKey;

        return md5($encodeStr);
    }
}
<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;
use Aw\Nusoap\NusoapParser;

/**
 * 日本 UIPAS 金流
 */
class UIPAS extends PaymentBase
{
    /**
     * 支付時要做加密的參數
     *
     * @var array
     */
    protected $requestData = [
        'amount' => '', // 金額
        'merchantid' => '', // 商號
        'key' => '', // 商號私鑰
        'refid' => '', // 訂單號
        'lang' => '', // 語系。en: 英文, jp: 日文
        'account_passward' => '' // 商戶密碼
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'merchantid' => 'number',
        'refid' => 'orderId',
        'amount' => 'amount',
        'lang' => 'lang'
    ];

    /**
     * 查詢時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $trackingRequestData = [
        'merchantid' => '', // 商號
        'account_passward' => '', // 商戶密碼
        'refid' => '' // 訂單號
    ];

    /**
     * 查詢時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $trackingRequireMap = [
        'merchantid' => 'number',
        'refid' => 'orderId'
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

        $this->requestData['key'] = $this->privateKey;

        $this->payVerify();

        // 從內部給定值到參數
        foreach ($this->requireMap as $paymentKey => $internalKey) {
            $this->requestData[$paymentKey] = $this->options[$internalKey];
        }

        // 如果沒有merchantId要丟例外
        if (trim($this->options['merchantId']) == '') {
            throw new PaymentException('No pay parameter specified', 180145);
        }

        // 取得商家附加設定值
        $merchantExtraValues = $this->getMerchantExtraValue(['account_passward']);
        $this->requestData['account_passward'] = $merchantExtraValues['account_passward'];

        // 檢查是否有postUrl(支付平台提交網址)
        if (trim($this->options['postUrl']) == '') {
            throw new PaymentException('No pay parameter specified', 180145);
        }

        // 語系預設為en，如果為ja需調整為jp
        $lang = 'en';
        if ($this->requestData['lang'] === 'ja') {
            $lang = 'jp';
        }

        $this->requestData['lang'] = $lang;

        $payUrl = sprintf(
            '%s/%s/%s/%s/%s/%s/%s',
            $this->options['postUrl'],
            $this->requestData['amount'],
            $this->requestData['merchantid'],
            $this->requestData['key'],
            $this->requestData['refid'],
            $this->requestData['lang'],
            $this->getHash()
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
        // 必要的回傳參數
        $veirfyParams = [
            'result',
            'refid',
            'amount'
        ];

        // 缺少回傳參數要丟例外
        foreach ($veirfyParams as $value) {
            if (!isset($this->options[$value])) {
                throw new PaymentException('No return parameter specified', 180137);
            }
        }

        if ($this->options['result'] != 'success') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['refid'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['amount'] != $entry['amount']) {
            throw new PaymentException('Order Amount error', 180058);
        }
    }

    /**
     * 訂單查詢
     */
    public function paymentTracking()
    {
       $this->trackingVerify();

        // 從內部給定值到參數
        foreach ($this->trackingRequireMap as $paymentKey => $internalKey) {
            $this->trackingRequestData[$paymentKey] = $this->options[$internalKey];
        }

        // 如果沒有merchantId要丟例外
        if (trim($this->options['merchantId']) == '') {
            throw new PaymentException('No tracking parameter specified', 180138);
        }

        // 取得商家附加設定值
        $merchantExtraValues = $this->getMerchantExtraValue(['account_passward']);
        $this->trackingRequestData['account_passward'] = $merchantExtraValues['account_passward'];

        // 對外取得交易狀態
        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $nusoapParam = [
            'serverIp' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'uri' => '/apiv2/index/wsdl',
            'function' => 'CheckTransfer',
            'callParams' => $this->trackingRequestData,
            'wsdl' => false,
        ];
        $transactionStatus = $this->soapRequest($nusoapParam);

        if (!isset($transactionStatus[0])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        if (!isset($transactionStatus[1])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        if (!isset($transactionStatus[2])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        if ($transactionStatus[0] === '1') {
            throw new PaymentException('Invalid Username or Password', 180147);
        }

        if ($transactionStatus[0] === '4') {
            throw new PaymentConnectionException(
                'System error, please try again later or contact customer service',
                180076,
                $this->getEntryId()
            );
        }

        if ($transactionStatus[0] === '5') {
            throw new PaymentConnectionException(
                'PaymentGateway error, Illegal merchant number',
                180082,
                $this->getEntryId()
            );
        }

        if ($transactionStatus[0] !== '0' || $transactionStatus[1] !== 'approved') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($transactionStatus[2] != $this->options['amount']) {
            throw new PaymentException('Order Amount error', 180058);
        }
    }

    /**
     * 取得訂單查詢時需要的參數
     *
     * @return array
     */
    public function getPaymentTrackingData()
    {
        $this->trackingVerify();

        // 從內部給定值到參數
        foreach ($this->trackingRequireMap as $paymentKey => $internalKey) {
            $this->trackingRequestData[$paymentKey] = $this->options[$internalKey];
        }

        // 如果沒有merchantId要丟例外
        if (trim($this->options['merchantId']) == '') {
            throw new PaymentException('No tracking parameter specified', 180138);
        }

        // 取得商家附加設定值
        $merchantExtraValues = $this->getMerchantExtraValue(['account_passward']);
        $this->trackingRequestData['account_passward'] = $merchantExtraValues['account_passward'];

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'verify_ip' => $this->options['verify_ip'],
            'path' => '/apiv2/index/wsdl',
            'function' => 'CheckTransfer',
            'arguments' => $this->trackingRequestData,
            'headers' => [
                'Host' => $this->options['verify_url']
            ]
        ];

        return $curlParam;
    }

    /**
     * 驗證訂單查詢是否成功
     */
    public function paymentTrackingVerify()
    {
        // 預設 xml encoding 是 UTF-8
        $xmlEncoding = 'UTF-8';
        $matches = [];

        // 取出 xml encoding
        if (preg_match("/<\?xml.*?encoding=[\"']([^\"']*)[\"'].*?\?>/", $this->options['content'], $matches)) {
            $xmlEncoding = $matches[1];
        }

        // 取出 soap 的結果
        $soapParser = new NusoapParser($this->options['content'], $xmlEncoding);
        $soapBody = $soapParser->get_soapbody();
        $transactionStatus = array_shift($soapBody);

        if (!isset($transactionStatus[0])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        if (!isset($transactionStatus[1])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        if (!isset($transactionStatus[2])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        if ($transactionStatus[0] === '1') {
            throw new PaymentException('Invalid Username or Password', 180147);
        }

        if ($transactionStatus[0] === '4') {
            throw new PaymentConnectionException(
                'System error, please try again later or contact customer service',
                180076,
                $this->getEntryId()
            );
        }

        if ($transactionStatus[0] === '5') {
            throw new PaymentConnectionException(
                'PaymentGateway error, Illegal merchant number',
                180082,
                $this->getEntryId()
            );
        }

        if ($transactionStatus[0] !== '0' || $transactionStatus[1] !== 'approved') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($transactionStatus[2] != $this->options['amount']) {
            throw new PaymentException('Order Amount error', 180058);
        }
    }

    /**
     * 處理訂單查詢支付平台返回的編碼
     *
     * @param array $response 訂單查詢的返回
     * @return array
     */
    public function processTrackingResponseEncoding($response)
    {
        // kue 先將回傳資料先做 base64 編碼，因此需先解開
        $response['body'] = trim(base64_decode($response['body']));

        return $response;
    }

    /**
     * 取得加密字串
     *
     * @return string
     */
    private function getHash()
    {
        $this->requestData['account_passward'] = md5($this->requestData['account_passward']);

        $hash = implode('|', $this->requestData);

        return md5($hash);
    }
}

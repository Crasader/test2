<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * CGPay
 */
class CGPay extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'MerchantOrderId' => '', // 訂單ID
        'OrderDescription' => '', // 訂單描述，客戶自訂
        'Attach' => '', // Memo
        'Amount' => '', // 數量(金額)
        'OrderBuildTimeSpan' => '', // 建立訂單時間(UTC)
        'OrderExpireTimeSpan' => '', // 訂單過期時間(UTC)
        'Symbol' => 'CGP', // 幣別(CGP)，單位聰(sat)
        'ReferUrl' => '', // 訂單送出後的導頁網址
        'Ip' => '', // 使用者IP，選填
        'MerchantId' => '', // 商戶ID
        'MerchantUserId' => '', // 商家使用者ID
        'Sign' => '', // 簽名
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'MerchantOrderId' => 'orderId',
        'OrderDescription' => 'orderId',
        'Attach' => 'orderId',
        'Amount' => 'amount',
        'OrderBuildTimeSpan' => 'orderCreateDate',
        'ReferUrl' => 'notify_url',
        'Ip' => 'ip',
        'MerchantId' => 'number',
        'MerchantUserId' => 'userId',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'MerchantOrderId',
        'OrderDescription',
        'Attach',
        'Amount',
        'OrderBuildTimeSpan',
        'OrderExpireTimeSpan',
        'Symbol',
        'ReferUrl',
        'Ip',
        'MerchantId',
        'MerchantUserId',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'MerchantId' => 1,
        'OrderId' => 1,
        'MerchantOrderId' => 1,
        'Attach' => 1,
        'PayAmount' => 1,
        'Symbol' => 1,
        'PayTimeSpan' => 1,
        'EventId' => 1,
    ];

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        '1117' => '', // CG錢包
    ];

    /**
     * 出款時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $withdrawRequestData = [
        'MerchantId' => '', // 商戶ID
        'MerchantUserId' => '', // 商戶使用者ID
        'MerchantWithdrawId' => '', // 商戶請款單ID
        'UserWallet' => '', // 使用者錢包
        'Amount' => '', // 金額(CGP)x100000000
        'Ip' => '', // 使用者IP
        'Sign' => '', // 簽名
        'AutoWithdraw' => 'AUTO', // 自動打款，跳過CG後台審核
    ];

    /**
     * 出款時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $withdrawRequireMap = [
        'MerchantId' => 'number',
        'MerchantUserId' => 'orderId',
        'MerchantWithdrawId' => 'orderId',
        'UserWallet' => 'account',
        'Amount' => 'amount',
    ];

    /**
     * 出款支援的銀行對應編號
     *
     * @var array
     */
    protected $withdrawBankMap = [
        '429' => '', // CG錢包
    ];

    /**
     * 出款時需要加密的參數
     *
     * @var array
     */
    protected $withdrawEncodeParams = [
        'MerchantId',
        'MerchantUserId',
        'MerchantWithdrawId',
        'UserWallet',
        'Amount',
        'Ip',
        'AutoWithdraw',
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
        if (!array_key_exists($this->options['paymentVendorId'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 商家額外的參數設定
        $this->requestData['OrderBuildTimeSpan'] = strtotime($this->requestData['OrderBuildTimeSpan']);
        $this->requestData['OrderExpireTimeSpan'] = strtotime('+1 day', $this->requestData['OrderBuildTimeSpan']);
        // RMB:CGP = 10:1，1CGP = 100000000聰(sat)
        $this->requestData['Amount'] = $this->requestData['Amount'] * 100000000 / 10;
        $this->requestData['MerchantUserId'] = md5($this->requestData['MerchantUserId']);

        $this->requestData['Sign'] = $this->encode();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'method' => 'POST',
            'uri' => '/api/v1/BuildGlobalPayOrder',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => json_encode($this->requestData),
            'header' => ['Content-Type' => 'application/json'],
        ];

        $result = $this->curlRequest($curlParam);
        $parseData = json_decode($result, true);

        if (!isset($parseData['ReturnCode']) || !isset($parseData['RetrunMessage'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        if ($parseData['ReturnCode'] !== '0') {
            throw new PaymentConnectionException($parseData['RetrunMessage'], 180130, $this->getEntryId());
        }

        if (!isset($parseData['Qrcode']) || !isset($parseData['Sign'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        // 組織加密串
        ksort($parseData);
        $encodeStr = '';

        foreach ($parseData as $key => $value) {
            if ($key != 'Sign') {
                $encodeStr .= $value . ',';
            }
        }

        $encodeStr .= $this->privateKey;

        // 驗證簽名
        if ($parseData['Sign'] != strtoupper(md5($encodeStr))) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        $this->payMethod = 'GET';

        return [
            'post_url' => $parseData['Qrcode'],
            'params' => [],
        ];
    }

    /**
     * 線上出款
     */
    public function withdrawPayment()
    {
        $this->verifyPrivateKey();
        $this->withdrawVerify();

        // 從內部給定值到參數
        foreach ($this->withdrawRequireMap as $paymentKey => $internalKey) {
            $this->withdrawRequestData[$paymentKey] = $this->options[$internalKey];
        }

        $withdrawHost = trim($this->options['withdraw_host']);

        // 金額必須為整數, 小數點不為0丟例外
        if (round($this->withdrawRequestData['Amount']) != $this->withdrawRequestData['Amount']) {
            throw new PaymentException('Amount must be an integer', 150180193);
        }
        $this->withdrawRequestData['Amount'] = round($this->withdrawRequestData['Amount']);

        // 轉換金額單位及匯率，RMB:CGP = 10:1，1CGP = 100000000聰(sat)
        $this->withdrawRequestData['Amount'] = $this->withdrawRequestData['Amount'] * 100000000 / 10;

        // 設定出款需要的加密串
        $this->withdrawRequestData['Sign'] = $this->withdrawEncode();

        // 出款
        $curlParam = [
            'method' => 'POST',
            'uri' => '/api/v1/MerchantWithdraw',
            'ip' => $this->options['verify_ip'],
            'host' => $withdrawHost,
            'param' => json_encode($this->withdrawRequestData),
            'header' => ['Content-Type' => 'application/json'],
            'timeout' => 60,
        ];

        $result = $this->curlRequest($curlParam);
        $parseData = json_decode($result, true);

        // 對返回結果做檢查
        if (!isset($parseData['ReturnCode']) || !isset($parseData['RetrunMessage'])) {
            throw new PaymentException('No withdraw return parameter specified', 150180209);
        }

        if ($parseData['ReturnCode'] !== '0') {
            throw new PaymentConnectionException($parseData['RetrunMessage'], 180124, $this->getEntryId());
        }

        if (isset($parseData['WithdrawId'])) {
            // 紀錄出款明細的支付平台參考編號
            $this->setCashWithdrawEntryRefId($parseData['WithdrawId']);
        }
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
        ksort($this->decodeParams);

        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options) && trim($this->options[$paymentKey]) !== '') {
                $encodeStr .= $this->options[$paymentKey] . ',';
            }
        }

        $encodeStr .= $this->privateKey;

        // 若沒有返回簽名需丟例外
        if (!isset($this->options['Sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['Sign'] !== strtoupper(md5($encodeStr))) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['MerchantOrderId'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['PayAmount'] != $entry['amount'] * 100000000 / 10) {
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
        asort($this->encodeParams);

        foreach ($this->encodeParams as $index) {
            if (array_key_exists($index, $this->requestData)) {
                $encodeStr .= $this->requestData[$index] . ',';
            }
        }

        $encodeStr .= $this->privateKey;

        return strtoupper(md5($encodeStr));
    }

    /**
     * 出款時的加密
     *
     * @return string
     */
    protected function withdrawEncode()
    {
        $encodeData = [];

        foreach ($this->withdrawEncodeParams as $index) {
            if (trim($this->withdrawRequestData[$index]) !== '') {
                $encodeData[$index] = $this->withdrawRequestData[$index];
            }
        }

        ksort($encodeData);
        $encodeStr = implode(',', $encodeData);
        $encodeStr .= ',' . $this->privateKey;

        return strtoupper(md5($encodeStr));
    }
}

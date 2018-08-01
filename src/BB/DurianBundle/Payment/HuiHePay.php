<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 匯合支付
 */
class HuiHePay extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'AppId' => '', // 商戶號
        'Method' => 'trade.page.pay', // 接口名稱，固定值
        'Format' => 'JSON', // 僅支持JSON，固定值
        'Charset' => 'UTF-8', // 編碼格式，固定值
        'Version' => '1.0', // 接口版本，固定值
        'SignType' => 'MD5', // 簽名類型，固定值
        'Sign' => '', // 簽名
        'Timestamp' => '', // 請求時間，格式:Y-M-D H:I:S
        'PayType' => '', // 支付類型
        'BankCode' => '', // 銀行代碼，非必填
        'OutTradeNo' => '', // 訂單編號
        'TotalAmount' => '', // 訂單金額，單位:元，精確到小數點第二位
        'Subject' => '', // 訂單標題，設定username方便業主比對
        'Body' => '', // 訂單描述，設定username方便業主比對
        'NotifyUrl' => '', // 異步通知網址
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'AppId' => 'number',
        'Timestamp' => 'orderCreateDate',
        'PayType' => 'paymentVendorId',
        'OutTradeNo' => 'orderId',
        'TotalAmount' => 'amount',
        'Subject' => 'username',
        'Body' => 'username',
        'NotifyUrl' => 'notify_url',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'AppId',
        'Method',
        'Format',
        'Charset',
        'Version',
        'Timestamp',
        'PayType',
        'OutTradeNo',
        'TotalAmount',
        'Subject',
        'Body',
        'NotifyUrl',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'Code' => 1,
        'Message' => 0,
        'AppId' => 1,
        'TradeNo' => 1,
        'OutTradeNo' => 1,
        'TotalAmount' => 1,
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
        '1090' => '2', // 微信_二維
        '1092' => '6', // 支付寶_二維
        '1098' => '5', // 支付寶_手機支付
        '1103' => '3', // QQ_二維
        '1104' => '11', // QQ_手機支付
        '1107' => '9', // 京東_二維
        '1108' => '12', // 京東_手機支付
        '1109' => '8', // 百度_二維
        '1111' => '10', // 銀聯_二維
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
        if (!array_key_exists($this->requestData['PayType'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $this->requestData['TotalAmount'] = sprintf('%.2f', $this->requestData['TotalAmount']);
        $this->requestData['PayType'] = $this->bankMap[$this->requestData['PayType']];
        $this->requestData['Sign'] = $this->encode();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'method' => 'POST',
            'uri' => '/Gateway',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => urldecode(http_build_query($this->requestData)),
            'header' => [],
        ];

        $result = $this->curlRequest($curlParam);
        $parseData = json_decode($result, true);

        if (!isset($parseData['Code'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        if ($parseData['Code'] != '0' && isset($parseData['Message'])) {
            throw new PaymentConnectionException($parseData['Message'], 180130, $this->getEntryId());
        }

        if ($parseData['Code'] != '0') {
            throw new PaymentConnectionException('Pay error', 180130, $this->getEntryId());
        }

        // 京東手機支付返回Form格式需額外調整
        if ($this->options['paymentVendorId'] == 1108) {
            if (!isset($parseData['Form'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            return ['act_url' => $this->getPostUrlFromForm($parseData['Form'])];
        }

        if (!isset($parseData['QrCode'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        // 手機支付
        if (in_array($this->options['paymentVendorId'], [1098, 1104])) {
            return ['act_url' => $parseData['QrCode']];
        }

        $this->setQrcode($parseData['QrCode']);

        return [];
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

        if ($this->options['Code'] !== '0') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if (!isset($this->options['Sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['Sign'] != strtoupper(md5($encodeStr))) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['OutTradeNo'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['TotalAmount'] != $entry['amount']) {
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

        // 加密設定
        foreach ($this->encodeParams as $index) {
            $encodeData[$index] = $this->requestData[$index];
        }

        ksort($encodeData);

        $encodeStr = urldecode(http_build_query($encodeData));

        $encodeStr .= $this->privateKey;

        return strtoupper(md5($encodeStr));
    }

    /**
     * 從對外回來的Form字串擷取提交網址
     *
     * @return string
     */
    private function getPostUrlFromForm($formString)
    {
        $fetchedUrl = [];
        preg_match("/action='([^']+)/", $formString, $fetchedUrl);

        if (!isset($fetchedUrl[1])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        return $fetchedUrl[1];
    }
}

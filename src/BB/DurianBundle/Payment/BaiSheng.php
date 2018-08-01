<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 百盛支付
 */
class BaiSheng extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'MerchantId' => '', // 商戶號
        'Sign' => '', // 簽名
        'Timestamp' => '', // 請求時間，格式:Y-M-D H:I:S
        'PaymentTypeCode' => '', // 入款類型
        'OutPaymentNo' => '', // 訂單編號
        'PaymentAmount' => '', // 訂單金額，單位:分
        'NotifyUrl' => '', // 異步通知網址
        'PassbackParams' => '', // 任意值，原樣返回，設定username方便業主比對
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'MerchantId' => 'number',
        'Timestamp' => 'orderCreateDate',
        'PaymentTypeCode' => 'paymentVendorId',
        'OutPaymentNo' => 'orderId',
        'PaymentAmount' => 'amount',
        'NotifyUrl' => 'notify_url',
        'PassbackParams' => 'username',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'MerchantId',
        'Timestamp',
        'PaymentTypeCode',
        'OutPaymentNo',
        'PaymentAmount',
        'NotifyUrl',
        'PassbackParams',
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
        'MerchantId' => 1,
        'PaymentNo' => 1,
        'OutPaymentNo' => 1,
        'PaymentAmount' => 1,
        'PaymentFee' => 1,
        'PaymentState' => 1,
        'PassbackParams' => 1,
    ];

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        '1090' => 'WECHAT_QRCODE_PAY', // 微信_二維
        '1092' => 'ALIPAY_QRCODE_PAY', // 支付寶_二維
        '1097' => 'WECHAT_WAP_PAY', // 微信_手機支付
        '1098' => 'ALIPAY_WAP_PAY', // 支付寶_手機支付
        '1103' => 'QQ_QRCODE_PAY', // QQ_二維
        '1104' => 'QQ_WAP_PAY', // QQ_手機支付
        '1107' => 'JD_QRCODE_PAY', // 京東_二維
        '1108' => 'JD_WAP_PAY', // 京東_手機支付
        '1111' => 'UNIONPAY_QRCODE_PAY', // 銀聯_二維
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
        if (!array_key_exists($this->requestData['PaymentTypeCode'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $this->requestData['PaymentAmount'] = strval(round($this->requestData['PaymentAmount']) * 100);
        $this->requestData['PaymentTypeCode'] = $this->bankMap[$this->requestData['PaymentTypeCode']];

        // 設定支付平台需要的加密串
        $this->requestData['Sign'] = $this->encode();

        // 二維支付
        if (in_array($this->options['paymentVendorId'], [1090, 1092, 1103, 1107, 1111])) {
            if (trim($this->options['verify_url']) == '') {
                throw new PaymentException('No verify_url specified', 180140);
            }

            $curlParam = [
                'method' => 'POST',
                'uri' => '/Payment/Gateway',
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

            if ($parseData['Code'] != '200' && isset($parseData['Message'])) {
                throw new PaymentConnectionException($parseData['Message'], 180130, $this->getEntryId());
            }

            if ($parseData['Code'] != '200') {
                throw new PaymentConnectionException('Pay error', 180130, $this->getEntryId());
            }

            if (!isset($parseData['QrCodeUrl'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            $this->setQrcode($parseData['QrCodeUrl']);

            return [];
        }

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

        if (!isset($this->options['Sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['Sign'] != strtoupper(md5($encodeStr))) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['PaymentState'] !== 'S') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['OutPaymentNo'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['PaymentAmount'] != round($entry['amount']) * 100) {
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

        return md5($encodeStr);
    }
}

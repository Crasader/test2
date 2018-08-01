<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 酷我支付
 */
class KuWo extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'memberGoods' => '', // 商品訊息(必須使用商戶訂單號)
        'noticeSysaddress' => '', // 異步通知地址
        'productNo' => '', // 產品編碼
        'requestAmount' => '', // 商戶號金額
        'trxMerchantNo' => '', // 商戶號
        'trxMerchantOrderno' => '', // 商戶訂單號
        'hmac' => '', // 簽名
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'memberGoods' => 'orderId',
        'noticeSysaddress' => 'notify_url',
        'productNo' => 'paymentVendorId',
        'requestAmount' => 'amount',
        'trxMerchantNo' => 'number',
        'trxMerchantOrderno' => 'orderId',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'memberGoods',
        'noticeSysaddress',
        'productNo',
        'requestAmount',
        'trxMerchantNo',
        'trxMerchantOrderno',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'reCode' => 1,
        'trxMerchantNo' => 1,
        'trxMerchantOrderno' => 1,
        'result' => 1,
        'productNo' => 1,
        'memberGoods' => 1,
        'amount' => 1,
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
        '1097' => 'WXWAP-JS', // 微信_手機支付
        '1098' => 'ALIPAYMOBILE-JS', // 支付寶_手機支付
        '1103' => 'QQWALLET-JS', // QQ_二維
        '1104' => 'QQWALLETWAP-JS', // QQ_手機支付
        '1111' => 'UNIONQR', // 銀聯_二維
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
        if (!array_key_exists($this->requestData['productNo'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $this->requestData['productNo'] = $this->bankMap[$this->requestData['productNo']];
        $this->requestData['requestAmount'] = sprintf('%.2f', $this->requestData['requestAmount']);

        // 設定支付平台需要的加密串
        $this->requestData['hmac'] = $this->encode();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'method' => 'POST',
            'uri' => '/saas-trx-gateway/order/acceptOrder',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => http_build_query($this->requestData),
            'header' => [],
        ];

        $result = $this->curlRequest($curlParam);
        $parseData = json_decode($result, true);

        if (!isset($parseData['code'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        if ($parseData['code'] !== '00000') {
            if (isset($parseData['message'])) {
                throw new PaymentConnectionException($parseData['message'], 180130, $this->getEntryId());
            }

            throw new PaymentConnectionException('Pay error', 180130, $this->getEntryId());
        }

        if (!isset($parseData['payUrl'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        if (in_array($this->options['paymentVendorId'], ['1097', '1098', '1104'])) {
            $urlData = $this->parseUrl(urldecode($parseData['payUrl']));

            // Form使用GET才能正常跳轉
            $this->payMethod = 'GET';

            return [
                'post_url' => $urlData['url'],
                'params' => $urlData['params'],
            ];
        }

        $this->setQrcode($parseData['payUrl']);

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

        $encodeData['key'] = $this->privateKey;

        // 依key1=value1&key2=value2&...&keyN=valueN之後做md5
        $encodeStr = urldecode(http_build_query($encodeData));

        // 如果沒有返回簽名要丟例外
        if (!isset($this->options['hmac'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['hmac'] != md5($encodeStr)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['reCode'] !== '1' || $this->options['result'] !== 'SUCCESS') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['trxMerchantOrderno'] != $entry['id']) {
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
        // 加密設定
        $encodeData = [];

        foreach ($this->encodeParams as $index) {
            $encodeData[$index] = $this->requestData[$index];
        }

        $encodeData['key'] = $this->privateKey;

        // 依key1=value1&key2=value2&...&keyN=valueN之後做md5
        $encodeStr = urldecode(http_build_query($encodeData));

        return md5($encodeStr);
    }
}

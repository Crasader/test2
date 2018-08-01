<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 迅捷付
 */
class XunJeiPay extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'version' => '1.0.0', // 版本號，固定值1.0.0
        'transType' => 'SALES', // 業務類型，固定值SALES
        'productId' => '0001', // 產品類型，網銀:0001
        'merNo' => '', // 商戶號
        'orderDate' => '', // 訂單交易日期，yyyyMMdd
        'orderNo' => '', // 訂單號
        'notifyUrl' => '', // 異步通知地址
        'returnUrl' => '', // 支付完成後跳轉網址
        'transAmt' => '', // 金額，單位為分
        'bankCode' => '', // 銀行編碼
        'signature' => '', // 簽名
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'merNo' => 'number',
        'orderDate' => 'orderCreateDate',
        'orderNo' => 'orderId',
        'notifyUrl' => 'notify_url',
        'returnUrl' => 'notify_url',
        'transAmt' => 'amount',
        'bankCode' => 'paymentVendorId',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'version',
        'transType',
        'productId',
        'merNo',
        'orderDate',
        'orderNo' ,
        'notifyUrl',
        'returnUrl',
        'transAmt',
        'bankCode',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'respDesc' => 1,
        'orderNo' => 1,
        'merNo' => 1,
        'productId' => 1,
        'transType' => 1,
        'serialId' => 1,
        'transAmt' => 1,
        'orderDate' => 1,
        'respCode' => 0,
    ];

    /**
     * 應答機制訊息
     *
     * @var string
     */
    protected $msg = 'success';

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        1 => 'ICBC', // 中國工商銀行
        2 => 'BOCM', // 交通銀行
        3 => 'ABC', // 中國農業銀行
        4 => 'CCB', // 建設銀行
        5 => 'CMB', // 招商銀行
        6 => 'CMBC', // 中國民生銀行
        9 => 'BOB', // 北京銀行
        12 => 'CEBB', // 中國光大銀行
        14 => 'GDB', // 廣東發展銀行
        16 => 'PSBC', // 中國郵政儲蓄銀行
        17 => 'BOC', // 中國銀行
        19 => 'BOS', // 上海銀行
        278 => '0003', // 銀聯在線
        1088 => '0003', // 銀聯在線_手機支付
        1090 => '0101', // 微信_二維
        1092 => '0103', // 支付寶_二維
        1098 => '0131', // 支付寶_手機支付
        1103 => '0102', // QQ_二維
        1104 => '0102', // QQ_手機支付
    ];

    /**
     * 回傳支付時要給支付平台驗證的資料
     *
     * @return array
     */
    public function getVerifyData()
    {
        $this->payVerify();

        // 從內部給定值到參數
        foreach ($this->requireMap as $paymentKey => $internalKey) {
            $this->requestData[$paymentKey] = $this->options[$internalKey];
        }

        // 帶入未支援的銀行就噴例外
        if (!array_key_exists($this->requestData['bankCode'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $this->requestData['bankCode'] = $this->bankMap[$this->requestData['bankCode']];
        $date = new \DateTime($this->requestData['orderDate']);
        $this->requestData['orderDate'] = $date->format('Ymd');

        // 金額以分為單位
        $this->requestData['transAmt'] = round($this->requestData['transAmt'] * 100);

        // 調整銀聯在線、銀聯在線手機支付提交參數
        if (in_array($this->options['paymentVendorId'], [278, 1088])) {
            $this->requestData['productId'] = $this->requestData['bankCode'];
            unset($this->requestData['bankCode']);
        }

        // 調整二維、支付寶手機支付、QQ手機支付提交參數
        if (in_array($this->options['paymentVendorId'], [1090, 1092, 1098, 1103, 1104])) {
            $this->requestData['productId'] = $this->requestData['bankCode'];
            unset($this->requestData['bankCode']);
        }

        // 設定支付平台需要的簽名串
        $this->requestData['signature'] = $this->encode();

        // 二維、QQ手機支付
        if (in_array($this->options['paymentVendorId'], [1090, 1092, 1103, 1104])) {
            if (trim($this->options['verify_url']) == '') {
                throw new PaymentException('No verify_url specified', 180140);
            }

            $curlParam = [
                'method' => 'POST',
                'uri' => '/trans/trans/api/back.json',
                'ip' => $this->options['verify_ip'],
                'host' => $this->options['verify_url'],
                'param' => http_build_query($this->requestData),
                'header' => [],
            ];

            $result = $this->curlRequest($curlParam);
            $parseData = json_decode($result, true);

            if (!isset($parseData['respCode']) || !isset($parseData['respDesc'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            if ($parseData['respCode'] != 'P000') {
                throw new PaymentConnectionException($parseData['respDesc'], 180130, $this->getEntryId());
            }

            if (!isset($parseData['payQRCode'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            // QQ手機支付
            if ($this->options['paymentVendorId'] == 1104) {
                $urlData = $this->parseUrl($parseData['payQRCode']);

                // Form使用GET才能正常跳轉
                $this->payMethod = 'GET';

                return [
                    'post_url' => $urlData['url'],
                    'params' => $urlData['params'],
                ];
            }

            $this->setQrcode($parseData['payQRCode']);

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

        if (!isset($this->options['signature'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        $sign = base64_decode($this->options['signature']);
        if (!openssl_verify($encodeStr, $sign, $this->getRsaPublicKey(), OPENSSL_ALGO_SHA1)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['respCode'] !== '0000') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['orderNo'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['transAmt'] != round($entry['amount'] * 100)) {
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

        foreach ($this->encodeParams as $key) {
            if (isset($this->requestData[$key])) {
                $encodeData[$key] = $this->requestData[$key];
            }
        }

        ksort($encodeData);

        $encodeStr = urldecode(http_build_query($encodeData));

        $sign = '';
        if (!openssl_sign($encodeStr, $sign, $this->getRsaPrivateKey(), OPENSSL_ALGO_SHA1)) {
            throw new PaymentException('Generate signature failure', 180144);
        }

        return base64_encode($sign);
    }
}

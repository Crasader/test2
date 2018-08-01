<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 九派支付
 */
class JiuPaiPay extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'charset' => '02', // 字符集，02:UTF-8
        'version' => '1.0', // 版本號
        'merchantId' => '', // 商戶號
        'requestTime' => '', // 請求時間，格式:YmdHis
        'requestId' => '', // 請求編號(當日唯一)，帶入訂單號
        'service' => 'rpmBankPayment', // 請求類型，網銀:rpmBankPayment
        'signType' => 'RSA256', // 簽名類型
        'merchantCert' => '', // 商戶證書
        'merchantSign' => '', // 簽名信息
        'pageReturnUrl' => '', // 頁面返回地址
        'notifyUrl' => '', // 後台返回地址
        'merchantName' => '', // 商戶展示名稱，設定username方便業主比對
        'memberId' => '', // 買家用戶標誌，設定username方便業主比對
        'orderTime' => '', // 訂單提交日期，格式:YmdHis
        'orderId' => '', // 訂單號
        'totalAmount' => '', // 訂單金額，分為單位
        'currency' => 'CNY', // 幣別
        'bankAbbr' => '', // 銀行簡稱
        'cardType' => '0', // 卡類型
        'payType' => 'B2C', // 支付類型
        'validNum' => '2', // 有效期數量
        'validUnit' => '02', // 有效期單位
        'showUrl' => '', // 商品展示地址，非必填
        'goodsName' => '', // 商品名稱，設定username方便業主比對
        'goodsId' => '', // 商品編號，非必填
        'goodsDesc' => '', // 商品描述，非必填
        'subMerchantId' => '', // 二級商戶，非必填
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'merchantId' => 'number',
        'requestTime' => 'orderCreateDate',
        'requestId' => 'orderId',
        'pageReturnUrl' => 'notify_url',
        'notifyUrl' => 'notify_url',
        'merchantName' => 'username',
        'memberId' => 'username',
        'orderTime' => 'orderCreateDate',
        'orderId' => 'orderId',
        'totalAmount' => 'amount',
        'bankAbbr' => 'paymentVendorId',
        'goodsName' => 'username',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'charset',
        'version',
        'merchantId',
        'requestTime',
        'requestId',
        'service',
        'signType',
        'pageReturnUrl',
        'notifyUrl',
        'merchantName',
        'memberId',
        'orderTime',
        'orderId',
        'totalAmount',
        'currency',
        'bankAbbr',
        'cardType',
        'payType',
        'validNum',
        'validUnit',
        'showUrl',
        'goodsName',
        'goodsId',
        'goodsDesc',
        'subMerchantId',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'charset' => 0,
        'version' => 1,
        'merchantId' => 1,
        'payType' => 1,
        'signType' => 1,
        'memberId' => 1,
        'orderId' => 1,
        'amount' => 1,
        'orderTime' => 1,
        'orderSts' => 1,
        'bankAbbr' => 0,
        'payTime' => 1,
        'acDate' => 1,
        'fee' => 1,
    ];

    /**
     * 應答機制訊息
     *
     * @var string
     */
    protected $msg = 'result=SUCCESS';

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        1 => 'ICBC', // 工商銀行
        2 => 'BCOM', // 交通銀行
        3 => 'ABC', // 農業銀行
        4 => 'CCB', // 建設銀行
        5 => 'CMB', // 招商銀行
        6 => 'CMBC', // 民生銀行總行
        8 => 'SPDB', // 上海浦東發展銀行
        10 => 'CIB', // 興業銀行
        11 => 'CITIC', // 中信銀行
        12 => 'CEB', // 光大銀行
        13 => 'HXB', // 華夏銀行
        14 => 'GDB', // 廣東發展銀行
        15 => 'PABC', // 平安銀行
        16 => 'PSBC', // 中國郵政
        17 => 'BOC', // 中國銀行
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

        // 從內部給定值到參數
        foreach ($this->requireMap as $paymentKey => $internalKey) {
            $this->requestData[$paymentKey] = $this->options[$internalKey];
        }

        // 帶入未支援的銀行就噴例外
        if (!array_key_exists($this->options['paymentVendorId'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $this->requestData['totalAmount'] = round($this->requestData['totalAmount'] * 100);
        $requestTime = new \DateTime($this->requestData['requestTime']);
        $this->requestData['requestTime'] = $requestTime->format('YmdHis');
        $this->requestData['orderTime'] = $this->requestData['requestTime'];
        $this->requestData['bankAbbr'] = $this->bankMap[$this->requestData['bankAbbr']];

        // 商戶證書
        $this->requestData['merchantCert'] = $this->getPublicCert();

        // 設定支付平台需要的加密串
        $this->requestData['merchantSign'] = $this->encode();

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
            if (array_key_exists($paymentKey, $this->options) && $this->options[$paymentKey] != '') {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        ksort($encodeData);

        $encodeStr = urldecode(http_build_query($encodeData));

        if (!isset($this->options['serverSign']) || !isset($this->options['serverCert'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        $serverCert = $this->hexToASCII($this->options['serverCert']);
        $serverSign = $this->hexToASCII($this->options['serverSign']);
        $serverKey = $this->getServerKey($serverCert);

        if (openssl_verify($encodeStr, $serverSign, $serverKey, OPENSSL_ALGO_SHA256) !== 1) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['orderSts'] == 'WP') {
            throw new PaymentConnectionException('Unpaid order', 180062, $this->getEntryId());
        }

        if ($this->options['orderSts'] != 'PD') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['orderId'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['amount'] != round($entry['amount'] * 100)) {
            throw new PaymentException('Order Amount error', 180058);
        }
    }

    /**
     * 回傳RSA憑證公鑰
     *
     * 因控管端申請商號需上傳公私鑰，且因上傳的公鑰需額外補上頭尾，但加解密並未使用到公鑰
     * 因此直接 override 空 function
     */
    public function getRsaPublicKey()
    {
    }

    /**
     * 回傳RSA憑證
     *
     * @return array
     */
    public function getRsaPrivateKey()
    {
        // privateKey為解開RSA憑證的口令，所以要驗證
        $this->verifyPrivateKey();

        $passphrase = $this->privateKey;

        $content = base64_decode($this->options['rsa_private_key']);

        if (!$content) {
            throw new PaymentException('Rsa private key is empty', 180092);
        }

        $privateCert = [];

        $status = openssl_pkcs12_read($content, $privateCert, $passphrase);

        if (!$status) {
            throw new PaymentException('Get rsa private key failure', 180093);
        }

        return $privateCert;
    }

    /**
     * 支付時的加密
     *
     * @return string
     */
    protected function encode()
    {
        $encodeData = [];

        // 組織加密簽名
        foreach ($this->encodeParams as $index) {
            if ($this->requestData[$index] != '') {
                $encodeData[$index] = $this->requestData[$index];
            }
        }

        ksort($encodeData);

        $encodeStr = urldecode(http_build_query($encodeData));

        $certificate = $this->getRsaPrivateKey();
        $key = openssl_pkey_get_private($certificate['pkey']);

        $sign = '';
        if (!openssl_sign($encodeStr, $sign, $key, OPENSSL_ALGO_SHA256)) {
            throw new PaymentException('Generate signature failure', 180144);
        }

        return chunk_split(bin2hex($sign), 2, '');
    }

    /**
     * 回傳證書公鑰
     *
     * @return string
     */
    private function getPublicCert()
    {
        $certificate = $this->getRsaPrivateKey();

        $dropBegin = str_replace('-----BEGIN CERTIFICATE-----', '', $certificate['cert']);
        $pkcsPublic = trim(str_replace('-----END CERTIFICATE-----', '', $dropBegin));
        $der = base64_decode($pkcsPublic);

        return chunk_split(bin2hex($der), 2, '');
    }

    /**
     * 取得支付平台回傳的證書公鑰
     *
     * @param string $serverCert
     * @return resource
     */
    private function getServerKey($serverCert)
    {
        $serverCertPem = chunk_split(base64_encode($serverCert), 64, "\n");
        $serverCertPem = "-----BEGIN CERTIFICATE-----\n" . $serverCertPem . "-----END CERTIFICATE-----\n";

        $serverKey = openssl_get_publickey($serverCertPem);

        return $serverKey;
    }

    /**
     * 十六進制轉 ASCII
     *
     * @param string $str
     * @return string
     */
    private function hexToASCII($str)
    {
        $len = strlen($str);
        $data = '';
        for ($i = 0; $i < $len; $i += 2) {
            $data .= chr(hexdec(substr($str, $i, 2)));
        }

        return $data;
    }
}

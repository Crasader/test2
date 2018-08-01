<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 閃億付
 */
class ShanYiFuPay extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'version' => 'V4.0.0.0', // 版本號固定值
        'merNo' => '', // 商戶號
        'netway' => '', // 支付方式
        'random' => '', // 隨機數，參數長度:4
        'orderNum' => '', // 訂單號
        'amount' => '', // 金額，單位:分
        'goodsName' => '', // 商品名稱
        'callBackUrl' => '', // 異步通知網址
        'callBackViewUrl' => '', // 支付成功轉跳網址
        'charset' => 'UTF-8', // 編碼格式
        'sign' => '', // 簽名，字母大寫
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'merNo' => 'number',
        'netway' => 'paymentVendorId',
        'orderNum' => 'orderId',
        'amount' => 'amount',
        'goodsName' => 'orderId',
        'callBackUrl' => 'notify_url',
        'callBackViewUrl' => 'notify_url',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'version',
        'merNo',
        'netway',
        'random',
        'orderNum',
        'amount',
        'goodsName',
        'callBackUrl',
        'callBackViewUrl',
        'charset',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *      0: 可不返回的參數
     *      1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'merNo' => 1,
        'netway' => 1,
        'orderNum' => 1,
        'amount' => 1,
        'goodsName' => 1,
        'payResult' => 1,
        'payDate' => 1,
    ];

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        1088 => 'UNION_WALLET_H5', // 銀聯在線_手機支付
        1090 => 'WX', // 微信_二維
        1092 => 'ZFB', // 支付寶_二維
        1097 => 'WX_WAP', // 微信_手機支付
        1098 => 'ZFB_WAP', // 支付寶_手機支付
        1103 => 'QQ', // QQ_二維
        1104 => 'QQ_WAP', // QQ_手機支付
        1107 => 'JD', // 京東_二維
        1108 => 'JD_WAP', // 京東_手機支付
        1111 => 'UNION_WALLET', // 銀聯_二維
    ];

    /**
     * 支付方式對應的支付地址
     *
     * @var array
     */
    protected $urlMap = [
        1088 => 'unionpay',
        1090 => 'wx',
        1092 => 'zfb',
        1097 => 'wxwap',
        1098 => 'zfbwap',
        1103 => 'qq',
        1104 => 'qqwap',
        1107 => 'jd',
        1108 => 'jd',
        1111 => 'unionpay',
    ];

    /**
     * 應答機制
     *
     * @var string
     */
    protected $msg = '0';

    /**
     * 回傳支付時要給支付平台驗證的資料
     *
     * @var array
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
        if (!array_key_exists($this->requestData['netway'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 調整支付地址
        $postUrl = 'payment.http.' . $this->urlMap[$this->requestData['netway']] . '.' . $this->options['postUrl'];

        // 額外的參數設定，參數均為字串
        $this->requestData['random'] = strval(rand(1000, 9999));
        $this->requestData['amount'] = strval(round($this->requestData['amount'] * 100));
        $this->requestData['netway'] = $this->bankMap[$this->requestData['netway']];

        // 設定支付平台需要的加密串
        $this->requestData['sign'] = $this->encode();

        $paramData = [
            'data' => urlencode($this->getRSAEncode()),
            'merchNo' => $this->options['number'],
            'version' => 'V4.0.0.0',
        ];

        $curlParam = [
            'method' => 'POST',
            'uri' => '/api/pay',
            'ip' => $this->options['verify_ip'],
            'host' => $postUrl,
            'param' => urldecode(http_build_query($paramData)),
            'header' => [],
        ];

        $result = $this->curlRequest($curlParam);
        $parseData = json_decode($result, true);

        if (!isset($parseData['stateCode']) || !isset($parseData['msg'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        if ($parseData['stateCode'] !== '00') {
            throw new PaymentConnectionException($parseData['msg'], 180130, $this->getEntryId());
        }

        if (!isset($parseData['qrcodeUrl'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        // 二維支付
        if (in_array($this->options['paymentVendorId'], [1090, 1092, 1103, 1107, 1111])) {
            $this->setQrcode($parseData['qrcodeUrl']);

            return [];
        }

        $urlData = $this->parseUrl($parseData['qrcodeUrl']);

        // Form使用GET才能正常跳轉
        $this->payMethod = 'GET';

        return [
            'post_url' => $urlData['url'],
            'params' => $urlData['params'],
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

        // 如果沒有返回data
        if (!isset($this->options['data'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        // RSA解碼
        $this->options = $this->getRSADecode($this->options['data']);

        // 如果沒有返回簽名要丟例外
        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        $this->payResultVerify();

        $encodeData = [];

        // 組織加密簽名，排除sign(加密簽名)
        foreach ($this->options as $key => $value) {
            if ($key != 'sign') {
                $encodeData[$key] = $value;
            }
        }

        // 針對$encodeData按字母做升序排列
        ksort($encodeData);

        $encodeStr = json_encode($encodeData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $encodeStr .= $this->privateKey;

        if ($this->options['sign'] != strtoupper(md5($encodeStr))) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['payResult'] !== '00') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['orderNum'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['amount'] != round($entry['amount'] * 100)) {
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

        foreach ($this->encodeParams as $index) {
            $encodeData[$index] = $this->requestData[$index];
        }

        ksort($encodeData);

        $encodeStr = json_encode($encodeData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $encodeStr .= $this->privateKey;

        return strtoupper(md5($encodeStr));
    }

    /**
     * 支付參數RSA加密
     *
     * @return string
     */
    private function getRSAEncode()
    {
        $encodeData = [];

        foreach ($this->encodeParams as $key) {
            $encodeData[$key] = $this->requestData[$key];
        }

        // 針對$encodeData按字母做升序排列
        ksort($encodeData);
        $encodeData['sign'] = $this->requestData['sign'];

        $encodeStr = json_encode($encodeData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $publicKey = $this->getRsaPublicKey();

        $encParam = '';
        foreach (str_split($encodeStr, 117) as $str) {
            $data = '';
            openssl_public_encrypt($str, $data, $publicKey);
            $encParam .= $data;
        }

        return base64_encode($encParam);
    }

    /**
     * 回調參數RSA解密
     *
     * @param string $response
     * @return array
     */
    private function getRSADecode($response)
    {
        // 先base64解碼
        $encodeStr = base64_decode(rawurldecode(urlencode($response)));

        $privateKey = $this->getRsaPrivateKey();

        // 待解密串長度大於128字元需分段解密，每128字元為一段，解密後再按照順序拼接成字串
        $dataStr = '';
        foreach (str_split($encodeStr, 128) as $chunk) {
            $decryptData = '';
            openssl_private_decrypt($chunk, $decryptData, $privateKey);
            $dataStr .= $decryptData;
        }

        return json_decode($dataStr, true);
    }
}

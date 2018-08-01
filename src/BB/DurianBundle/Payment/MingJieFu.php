<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 明捷付
 */
class MingJieFu extends PaymentBase
{
    /**
     * RSA公鑰加密最大明文區塊大小
     */
    const RSA_PUBLIC_ENCODE_BLOCKSIZE = 117;

    /**
     * RSA私鑰解密最大密文區塊大小
     */
    const RSA_PRIVATE_DECODE_BLOCKSIZE = 128;

    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'version' => 'V3.0.0.0', // 版本號，固定值
        'merchNo' => '', // 商戶號
        'netwayCode' => '', // 支付網關代碼
        'randomNum' => '', // 隨機數，最長8位
        'orderNum' => '', // 訂單號
        'amount' => '', // 金額，單位:分
        'goodsName' => '', // 商品名稱，必填
        'callBackUrl' => '', // 支付結果通知地址
        'callBackViewUrl' => '', // 回顯地址
        'charset' => 'UTF-8', // 編碼格式
        'sign' => '', // 簽名，字母大寫
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'merchNo' => 'number',
        'netwayCode' => 'paymentVendorId',
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
        'merchNo',
        'netwayCode',
        'randomNum',
        'orderNum',
        'amount',
        'goodsName',
        'callBackUrl',
        'callBackViewUrl',
        'charset',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'merchNo' => 1,
        'netwayCode' => 1,
        'orderNum' => 1,
        'amount' => 1,
        'goodsName' => 1,
        'payStateCode' => 1,
        'payDate' => 1,
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
        '1092' => 'ZFB', // 支付寶_二維
        '1098' => 'ZFB_WAP', // 支付寶_手機支付
        '1103' => 'QQ', // QQ_二維
        '1104' => 'QQ_WAP', // QQ_手機支付
        '1107' => 'JD', // 京東_二維
        '1108' => 'JD_WAP', // 京東_手機支付
        '1111' => 'UNION_WALLET', // 銀聯_二維
        '1115' => 'WX_AUTH_CODE', // 微信_條碼
        '1118' => 'WX_AUTH_CODE_WAP', // 微信_條碼手機支付
    ];

    /**
     * 支援的支付方式對應域名前綴
     *
     * @var array
     */
    private $domainPrefixMap = [
        '1092' => 'zfb', // 支付寶_二維
        '1098' => 'zfbwap', // 支付寶_手機支付
        '1103' => 'qq', // QQ_二維
        '1104' => 'qqwap', // QQ_手機支付
        '1107' => 'jd', // 京東_二維
        '1108' => 'jdwap', // 京東_手機支付
        '1111' => 'union', // 銀聯_二維
        '1115' => 'wx', // 微信_條碼
        '1118' => 'wxwap', // 微信_條碼手機支付
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
            if (array_key_exists($paymentKey, $this->requestData)) {
                $this->requestData[$paymentKey] = $this->options[$internalKey];
            }
        }

        // 帶入未支援的銀行就噴例外
        if (!array_key_exists($this->requestData['netwayCode'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $this->requestData['randomNum'] = strval(rand(1000,9999));
        $this->requestData['amount'] = strval(round($this->requestData['amount'] * 100));
        $this->requestData['netwayCode'] = $this->bankMap[$this->requestData['netwayCode']];

        // 設定加密簽名
        $this->requestData['sign'] = $this->encode();

        // 提交參數
        $requestParams = [
            'data' => $this->rsaPublicKeyEncrypt(),
            'merchNo' => $this->requestData['merchNo'],
            'version' => $this->requestData['version'],
        ];

        // 調整提交Host
        $postUrl = sprintf(
            'payment.http.%s.%s',
            $this->domainPrefixMap[$this->options['paymentVendorId']],
            $this->options['postUrl']
        );

        $curlParam = [
            'method' => 'POST',
            'uri' => '/api/pay',
            'ip' => $this->options['verify_ip'],
            'host' => $postUrl,
            'param' => http_build_query($requestParams),
            'header' => ['Port' => '90'],
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
        if (in_array($this->options['paymentVendorId'], [1092, 1103, 1107, 1111])) {
            $this->setQrcode($parseData['qrcodeUrl']);

            return [];
        }

        $parsedUrl = $this->parseUrl($parseData['qrcodeUrl']);

        $this->payMethod = 'GET';

        return [
            'post_url' => $parsedUrl['url'],
            'params' => $parsedUrl['params'],
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

        // 返回參數驗證
        if (!isset($this->options['data'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        $data = $this->rsaPrivateKeyDecrypt($this->options['data']);
        $this->options = json_decode($data, true);

        // 返回參數驗證
        $this->payResultVerify();

        $encodeData = [];

        // 組織加密串
        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options)) {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        ksort($encodeData);
        $encodeStr = json_encode($encodeData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $encodeStr .= $this->privateKey;

        // 沒有sign就要丟例外
        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        // 簽名錯誤
        if ($this->options['sign'] !== strtoupper(md5($encodeStr))) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['payStateCode'] !== '00') {
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

        // 組織加密串
        foreach ($this->encodeParams as $index) {
            if (array_key_exists($index, $this->requestData)) {
                $encodeData[$index] = $this->requestData[$index];
            }
        }

        ksort($encodeData);
        $encodeStr = json_encode($encodeData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return strtoupper(md5($encodeStr . $this->privateKey));
    }

    /**
     * RSA公鑰加密
     *
     * @return string
     */
    private function rsaPublicKeyEncrypt()
    {
        $publicKey = $this->getRsaPublicKey();

        ksort($this->requestData);
        $plaintext = json_encode($this->requestData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        // 明文需用公鑰加密、字串太長須分段
        $result = '';
        foreach (str_split($plaintext, self::RSA_PUBLIC_ENCODE_BLOCKSIZE) as $block) {
            $encrypted = '';

            if (!openssl_public_encrypt($block, $encrypted, $publicKey)) {
                throw new PaymentException('Generate signature failure', 180144);
            }
            $result .= $encrypted;
        }

        return base64_encode($result);
    }

    /**
     * RSA私鑰解密
     *
     * @param string $data
     * @return string
     */
    private function rsaPrivateKeyDecrypt($data)
    {
        $encrypted = base64_decode($data);
        $privateKey = $this->getRsaPrivateKey();

        // 私鑰解密、字串太長須分段
        $result = '';
        foreach (str_split($encrypted, self::RSA_PRIVATE_DECODE_BLOCKSIZE) as $block) {
            $decrypted = '';

            if (!openssl_private_decrypt($block, $decrypted, $privateKey)) {
                throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
            }
            $result .= $decrypted;
        }

        return $result;
    }
}

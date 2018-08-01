<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 聚寶雲支付
 */
class JubaoPay extends PaymentBase
{
    /**
     * 支付提交參數
     *
     * @var array
     */
    protected $requestData = [
        'payid' => '', // 訂單號
        'partnerid' => '', // 商戶號
        'amount' => '', // 訂單金額，單位元
        'payerName' => '', // 用戶ID
        'goodsName' => '', // 商品名稱(帶入username方便業主比對)
        'remark' => '', // 備註字段
        'returnURL' => '', // 前台返回URL
        'callBackURL' => '', // 後台返回URL
    ];

    /**
     * 支付提交參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'payid' => 'orderId',
        'partnerid' => 'number',
        'amount' => 'amount',
        'payerName' => 'username',
        'goodsName' => 'username',
        'returnURL' => 'notify_url',
        'callBackURL' => 'notify_url',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'payid',
        'partnerid',
        'amount',
        'payerName',
        'goodsName',
        'remark',
        'returnURL',
        'callBackURL',
    ];

    /**
     * 返回驗證時需要加密的參數
     *     0: 有返回就要加密的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'message' => 1,
    ];

    /**
     * 查詢時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $trackingRequestData = [
        'payid' => '', // 訂單號
        'partnerid' => '', // 商戶號
    ];

    /**
     * 查詢時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $trackingRequireMap = [
        'payid' => 'orderId',
        'partnerid' => 'number',
    ];

    /**
     * 查詢時需要加密的參數
     *
     * @var array
     */
    protected $trackingEncodeParams = [
        'payid',
        'partnerid',
    ];

    /**
     * 查詢解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $trackingDecodeParams = [
        'message' => 1,
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

        $this->options['notify_url'] = sprintf(
            '%s?trans_id=%s',
            $this->options['notify_url'],
            $this->options['orderId']
        );

        // 從內部設值到支付參數
        foreach ($this->requireMap as $paymentKey => $internalKey) {
            $this->requestData[$paymentKey] = $this->options[$internalKey];
        }

        // 額外的參數設定
        $this->requestData['amount'] = sprintf('%.2f', $this->requestData['amount']);

        $result = [
            'message' => $this->getMessage(),
            'signature' => $this->encode(),
            'payMethod' => 'ALL',
            'tab' => '',
        ];

        return $result;
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

        $decryptData = $this->decryptMessage($this->options['message']);

        $encodeStr = '';

        // 加密設定
        foreach ($decryptData as $paymentKey) {
            $encodeStr .= $paymentKey;
        }
        $encodeStr .= $this->privateKey;

        // 沒有返回signature就要丟例外
        if (!isset($this->options['signature'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        $signMsg = base64_decode($this->options['signature']);
        $status = openssl_verify($encodeStr, $signMsg, $this->getRsaPublicKey(), OPENSSL_ALGO_SHA1);

        if (!$status) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        // 缺少驗證的參數就要丟例外
        if (!isset($decryptData['state']) || !isset($decryptData['payid']) || !isset($decryptData['amount'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($decryptData['state'] !== '2') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($decryptData['payid'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($decryptData['amount'] != $entry['amount']) {
            throw new PaymentException('Order Amount error', 180058);
        }
    }

    /**
     * 訂單查詢
     */
    public function paymentTracking()
    {
        $this->verifyPrivateKey();
        $this->trackingVerify();

        // 從內部給定值到參數
        foreach ($this->trackingRequireMap as $paymentKey => $internalKey) {
            $this->trackingRequestData[$paymentKey] = $this->options[$internalKey];
        }

        $data = [
            'message' => $this->getTrackingMessage(),
            'signature' => $this->trackingEncode(),
        ];

        // 執行訂單查詢
        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'method' => 'POST',
            'uri' => '/apicheck.htm',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => http_build_query($data),
            'header' => []
        ];

        $result = $this->curlRequest($curlParam);
        $parseData = json_decode($result, true);
        $this->trackingResultVerify($parseData);

        $decryptData = $this->decryptMessage($parseData['message']);

        $encodeStr = '';

        // 加密設定
        foreach ($decryptData as $paymentKey) {
            $encodeStr .= $paymentKey;
        }
        $encodeStr .= $this->privateKey;

        // 沒有返回signature就要丟例外
        if (!isset($parseData['signature'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        $signMsg = base64_decode($parseData['signature']);
        $status = openssl_verify($encodeStr, $signMsg, $this->getRsaPublicKey(), OPENSSL_ALGO_SHA1);

        if (!$status) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        // 缺少驗證的參數就要丟例外
        if (!isset($decryptData['state']) || !isset($decryptData['payid']) || !isset($decryptData['amount'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        if ($decryptData['state'] !== '2') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($decryptData['amount'] != $this->options['amount']) {
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
        $sign = '';

        // 加密設定
        foreach ($this->encodeParams as $index) {
            $encodeStr .= $this->requestData[$index];
        }

        $digest = $encodeStr . $this->privateKey;

        if (!openssl_sign($digest, $sign, $this->getRsaPrivateKey(), OPENSSL_ALGO_SHA1)) {
            throw new PaymentException('Generate signature failure', 180144);
        }

        return base64_encode($sign);
    }

    /**
     * 訂單查詢時的加密
     *
     * @return string
     */
    protected function trackingEncode()
    {
        $encodeStr = '';
        $sign = '';

        // 加密設定
        foreach ($this->trackingEncodeParams as $index) {
            $encodeStr .= $this->trackingRequestData[$index];
        }

        $digest = $encodeStr . $this->privateKey;

        if (!openssl_sign($digest, $sign, $this->getRsaPrivateKey(), OPENSSL_ALGO_SHA1)) {
            throw new PaymentException('Generate signature failure', 180144);
        }

        return base64_encode($sign);
    }

    /**
     * curl request 解密
     *
     * @param string $request
     * @return string
     */
    protected function curlRequestDecode($request)
    {
        $parseData = [];
        parse_str(urldecode($request), $parseData);

        $params = array_merge($this->trackingRequestData, $parseData);

        return http_build_query($params);
    }

    /**
     * curl response 解密
     *
     * @param string $response
     * @return string
     */
    protected function curlResponseDecode($response)
    {
        $parseData = json_decode($response, true);

        // 沒有返回message 直接回傳response
        if (!isset($parseData['message'])) {
            return $response;
        }

        $result = $this->decryptMessage($parseData['message']);
        $params = array_merge($result, $parseData);

        return json_encode($params);
    }

    /**
     * 產生隨機加密串
     *
     * @return string
     */
    private function generateRandomString($length = 16)
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()-_ []{}<>~`+=,.;:/?|';
        $password = '';

        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[mt_rand(0, strlen($chars) - 1)];
        }

        return $password;
    }

    /**
     * 回傳支付時的訊息字串
     *
     * @return string
     */
    private function getMessage()
    {
        $encodeData = [];

        // 加密設定
        foreach ($this->encodeParams as $index) {
            $encodeData[] = urlencode($index) . '&' . urlencode($this->requestData[$index]);
        }
        $encodeStr = implode('&', $encodeData);

        $key = $this->generateRandomString();
        $iv = $this->generateRandomString();
        $keyResult = '';
        $ivResult = '';

        $encrypted = base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, $encodeStr, MCRYPT_MODE_CBC, $iv));

        openssl_public_encrypt($key, $keyResult, $this->getRsaPublicKey());
        openssl_public_encrypt($iv, $ivResult, $this->getRsaPublicKey());

        return base64_encode($keyResult) . base64_encode($ivResult) . $encrypted;
    }

    /**
     * 回傳訂單查詢時的訊息字串
     *
     * @return string
     */
    private function getTrackingMessage()
    {
        $encodeData = [];

        // 加密設定
        foreach ($this->trackingEncodeParams as $index) {
            $encodeData[] = urlencode($index) . '&' . urlencode($this->trackingRequestData[$index]);
        }
        $encodeStr = implode('&', $encodeData);

        $key = $this->generateRandomString();
        $iv = $this->generateRandomString();
        $keyResult = '';
        $ivResult = '';

        $encrypted = base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, $encodeStr, MCRYPT_MODE_CBC, $iv));

        openssl_public_encrypt($key, $keyResult, $this->getRsaPublicKey());
        openssl_public_encrypt($iv, $ivResult, $this->getRsaPublicKey());

        return base64_encode($keyResult) . base64_encode($ivResult) . $encrypted;
    }

    /**
     * 解密訊息
     *
     * @return array
     */
    private function decryptMessage($message)
    {
        $key = '';
        $iv = '';

        $keyResult = openssl_private_decrypt(base64_decode(substr($message, 0, 172)), $key, $this->getRsaPrivateKey());
        $ivResult = openssl_private_decrypt(base64_decode(substr($message, 172, 172)), $iv, $this->getRsaPrivateKey());

        // 避免RSA解密錯誤 這邊先檢查key及iv解密是否成功
        if (!$keyResult || !$ivResult) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        $decrypted = base64_decode(substr($message, 172 + 172));
        $plainString = rtrim(mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $key, $decrypted, MCRYPT_MODE_CBC, $iv));

        $encrypts = [];
        $items = explode('&', $plainString);

        for ($i = 0; $i < count($items) / 2; $i++) {
            $field = urldecode($items[2 * $i]);
            $value = urldecode($items[2 * $i + 1]);
            $encrypts[$field] = $value;
        }

        return $encrypts;
    }
}

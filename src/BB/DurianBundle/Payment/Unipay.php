<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 中國銀聯
 *
 * 支付驗證：
 * 1. 驗證不可為空的參數
 * 2. 設定參數
 * 3. 額外處理的參數
 * 4. 設定encodeStr(加密後的字串)
 *
 * 解密驗證：
 * 1. 驗證key
 * 2. 設定參數
 * 3. 驗證結果是否相符
 */
class Unipay extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'partner' => '', // 商號
        'info' => '', // 訂單內容
        'order_number' => '', // 訂單號
        'total_fee' => '', // 金額
        'pay_id' => '', // 銀行代碼
        'return_url' => '', // 伺服器通知url
        'notify_url' => '', // 支付成功跳轉url
        'card_type' => '01', // 01 借計卡 02 信用卡
        'version' => '', // 版本號
        'base64_memo' => '', // 備註(原樣返回)
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'partner' => 'number',
        'order_number' => 'orderId',
        'total_fee' => 'amount',
        'pay_id' => 'paymentVendorId',
        'return_url' => 'notify_url',
        'notify_url' => 'notify_url',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'order_number',
        'total_fee',
        'pay_id',
        'return_url',
        'notify_url',
        'card_type',
        'version',
        'base64_memo',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'partner' => 1,
        'out_trade_no' => 1,
        'pay_no' => 1,
        'amount' => 1,
        'mdr_fee' => 1,
        'pay_result' => 1,
        'sett_date' => 1,
    ];

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        '1' => 'gonghang', // 工商銀行
        '2' => 'jiaohang', // 交通銀行
        '3' => 'nonghang', // 農業銀行
        '4' => 'jianhang', // 建設銀行
        '5' => 'zhaohang', // 招商銀行
        '6' => 'minsheng', // 民生銀行
        '7' => 'shenfa', // 深圳發展銀行
        '8' => 'pufa', // 上海浦東發展銀行
        '9' => 'beijing', // 北京銀行
        '10' => 'xingye', // 興業銀行
        '11' => 'zhongxin', // 中信銀行
        '12' => 'guangda', // 中國光大銀行
        '13' => 'huaxia', // 華夏銀行
        '14' => 'guangfa', // 廣東發展銀行
        '16' => 'chuxu', // 中國郵政儲蓄銀行
        '17' => 'zhonghang', // 中國銀行
        '19' => 'shanghai', // 上海銀行
        '217' => 'bohai', // 渤海銀行
        '220' => 'hangzhou', // 杭州銀行
        '221' => 'zheshang', // 浙商銀行
        '222' => 'ningbo', // 寧波銀行
        '226' => 'nanjing', // 南京銀行
        '228' => 'shnongshang', // 上海農商銀行
        '321' => 'tianjin', // 天津銀行
    ];

    /**
     * 查詢時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $trackingRequestData = [
        'partner' => '', // 商號
        'info' => '', // 訂單內容
        'out_trade_no' => '', // 訂單號
    ];

    /**
     * 查詢時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $trackingRequireMap = [
        'partner' => 'number',
        'out_trade_no' => 'orderId',
    ];

    /**
     * 查詢時需要加密的參數
     *
     * @var array
     */
    protected $trackingEncodeParams = [
        'out_trade_no',
    ];

    /**
     * 查詢解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $trackingDecodeParams = [
        'out_trade_no' => 1, // 訂單號
        'amount' => 1, // 金額
        'curr_code' => 1, // 幣別
        'pay_result' => 1, // 交易結果
        'sett_date' => 1, // 成功日期
    ];

    /**
     * 回傳支付時要給支付平台驗證的資料
     *
     * @return array
     */
    public function getVerifyData()
    {
        $this->payVerify();

        $this->options['notify_url'] = sprintf(
            '%s?out_trade_no=%s',
            $this->options['notify_url'],
            $this->options['orderId']
        );

        // 從內部給定值到參數
        foreach ($this->requireMap as $paymentKey => $internalKey) {
            $this->requestData[$paymentKey] = $this->options[$internalKey];
        }

        // 帶入未支援的銀行就噴例外
        if (!array_key_exists($this->requestData['pay_id'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $this->requestData['pay_id'] = $this->bankMap[$this->requestData['pay_id']];
        $this->requestData['total_fee'] = sprintf('%.2f', $this->requestData['total_fee']);

        return [
            'partner' => $this->requestData['partner'],
            'info' => base64_encode($this->encode()),
        ];
    }

    /**
     * 驗證線上支付是否成功
     *
     * @param array $entry
     */
    public function verifyOrderPayment($entry)
    {
        if (!isset($this->options['info'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        $info = $this->decrypt($this->getRsaPrivateKey(), $this->options['info']);

        parse_str($info, $this->options);

        $this->payResultVerify();

        if ($this->options['pay_result'] != '1') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        // 驗證訂單號
        if ($this->options['out_trade_no'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        // 驗證金額
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

        $this->trackingRequestData['info'] = base64_encode($this->trackingEncode());

        // 加密後移除多餘參數
        unset($this->trackingRequestData['out_trade_no']);

        if (trim($this->options['reopUrl']) == '') {
            throw new PaymentException('No reopUrl specified', 180141);
        }

        // 因通過對外機 proxy 到中國銀聯會 timeout，改為此方式對外
        $params = [
            'url' => $this->options['reopUrl'],
            'data' => http_build_query($this->trackingRequestData),
        ];

        $curlParam = [
            'method' => 'GET',
            'uri' => '/pay/curl.php',
            'ip' => [$this->container->getParameter('payment_ip')],
            'host' => $this->container->getParameter('payment_ip'),
            'param' => http_build_query($params),
            'header' => [],
        ];

        $this->options['content'] = $this->curlRequest($curlParam);
        $this->paymentTrackingVerify();
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

        $this->trackingRequestData['info'] = base64_encode($this->trackingEncode());

        // 加密後移除多餘參數
        unset($this->trackingRequestData['out_trade_no']);

        if (trim($this->options['reopUrl']) == '') {
            throw new PaymentException('No reopUrl specified', 180141);
        }

        // 因通過對外機 proxy 到中國銀聯會 timeout，改為此方式對外
        $params = [
            'url' => $this->options['reopUrl'],
            'data' => http_build_query($this->trackingRequestData),
        ];

        $curlParam = [
            'verify_ip' => [$this->container->getParameter('payment_ip')],
            'path' => '/pay/curl.php?' . http_build_query($params),
            'method' => 'GET',
            'headers' => [
                'Host' => $this->container->getParameter('payment_ip'),
            ],
        ];

        return $curlParam;
    }

    /**
     * 驗證訂單查詢是否成功
     */
    public function paymentTrackingVerify()
    {
        // 檢查訂單查詢返回參數
        // 因支付平台回傳的內容沒有 urlencode 但 xmlToArray 會 decode, 因此先在這裡 urlencode
        $parseData = $this->xmlToArray(urlencode($this->options['content']));

        // 檢查第一層必要返回參數
        foreach (['resp_code', 'resp_desc', 'partner', 'info'] as $key) {
            if (!isset($parseData[$key])) {
                throw new PaymentException('No tracking return parameter specified', 180139);
            }
        }

        // 查詢失敗
        if ($parseData['resp_code'] !== '00' || strtolower($parseData['resp_desc']) !== 'success') {
            throw new PaymentConnectionException($parseData['resp_desc'], 180123, $this->getEntryId());
        }

        $info = $this->decrypt($this->getRsaPrivateKey(), $parseData['info']);

        parse_str($info, $parseData);

        $this->trackingResultVerify($parseData);

        // 尚未成功支付
        if ($parseData['pay_result'] != 1) {
            throw new PaymentConnectionException('Unpaid order', 180062, $this->getEntryId());
        }

        // 檢查訂單號
        if ($parseData['out_trade_no'] != $this->options['orderId']) {
            throw new PaymentException('Order Id error', 180061);
        }

        // 檢查金額
        if ($parseData['amount'] != $this->options['amount']) {
            throw new PaymentException('Order Amount error', 180058);
        }
    }

    /**
     * 支付時的加密
     *
     * 以 214 為單位切割字串，並依照 RSA OAEP 作加密
     *
     * @return string
     */
    protected function encode()
    {
        $encodeData = [];
        $info = '';
        $publicKey = $this->getRsaPublicKey();

        foreach ($this->encodeParams as $index) {
            $encodeData[$index] = $this->requestData[$index];
        }

        $encodeStr = http_build_query($encodeData);

        foreach (str_split($encodeStr, 214) as $chunk) {
            $sign = '';
            openssl_public_encrypt($chunk, $sign, $publicKey, OPENSSL_PKCS1_OAEP_PADDING);

            $info .= $sign;
        }

        return $info;
    }

    /**
     * 訂單查詢時的加密
     *
     * 以 214 為單位切割字串，並依照 RSA OAEP 作加密
     *
     * @return string
     */
    protected function trackingEncode()
    {
        $encodeData = [];
        $info = '';
        $publicKey = $this->getRsaPublicKey();

        foreach ($this->trackingEncodeParams as $index) {
            $encodeData[$index] = $this->trackingRequestData[$index];
        }

        $encodeStr = http_build_query($encodeData);

        foreach (str_split($encodeStr, 214) as $chunk) {
            $sign = '';
            openssl_public_encrypt($chunk, $sign, $publicKey, OPENSSL_PKCS1_OAEP_PADDING);

            $info .= $sign;
        }

        return $info;
    }

    /**
     * 解密訂單內容
     *
     * 以 256 為單位切割字串，並依照 RSA OAEP 作解密
     *
     * @param string $privateKey 密鑰
     * @param string $encrypted 密文
     * @return string 明文
     */
    protected function decrypt($privateKey, $encrypted)
    {
        $decrypted = '';

        foreach (str_split(base64_decode($encrypted), 256) as $chunk) {
            $raw = '';
            openssl_private_decrypt($chunk, $raw, $privateKey, OPENSSL_PKCS1_OAEP_PADDING);

            $decrypted .= $raw;
        }

        return $decrypted;
    }
}

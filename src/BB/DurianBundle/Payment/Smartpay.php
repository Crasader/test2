<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 捷銀
 */
class Smartpay extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'cmd'                 => '_webpay',            //命令，目前只支援_webpay
        'merchantId'          => '',                   //商戶編號
        'mobile'              => '',                   //支付用戶手機號，可空。
        'tx_date'             => '',                   //交易日期
        'tx_time'             => '',                   //交易時間
        'tx_no'               => '',                   //交易流水號
        'tx_params'           => '',                   //交易參數，可空。
        'payment_mode'        => '',                   //付款方式，可空。
        'debit_list'          => '',                   //扣款列表，可空。
        'debit_periodic_unit' => '',                   //扣款週期單位，可空。
        'debit_interval'      => '',                   //扣款間隔週期，可空。
        'first_debit_date'    => '',                   //第一次扣款日期，可空。
        'close_date'          => '',                   //總共扣款截止日期，可空。
        'amount'              => '',                   //訂單總額，以分為單位，整數。
        'price'               => '',                   //訂單標價，可空。
        'discount'            => '',                   //訂單優惠，可空。
        'item_name'           => '',                   //訂單描述，可空。
        'item_no'             => '',                   //商品編號
        'item_quantity'       => '',                   //商品數量，可空。
        'image_url'           => '',                   //商品圖片，可空。
        'quantity'            => '',                   //商品種類數量，可空。
        'item_name_n'         => '',                   //多類商品第n件的描述，可空。
        'item_no_n'           => '',                   //多件商品第n件的商品號碼，可空。
        'item_quantity_n'     => '',                   //多件商品第n件的數量，可空。
        'item_amount_n'       => '',                   //多件商品第n件的總金額，可空。
        'item_price_n'        => '',                   //多件商品第n件的標價，可空。
        'item_discount_n'     => '',                   //多件商品第n件的優惠，可空。
        'image_url_n'         => '',                   //多件商品第n件的圖片，可空。
        'notice_method'       => '1',                  //用戶支付後的通知方法，可空。1: 直接通知商戶
        'return_url'          => '',                   //用戶完成支付的返回URL地址，可空。
        'cancel_return_url'   => '',                   //用戶取消支付的返回URL地址，可空。
        'notice_url'          => '',                   //通知商戶結果的URL
        'no_shipping'         => '',                   //是否需要配送地址，可空。
        'valid_time'          => '',                   //訂單有效期，可空。
        'payment_type'        => 'ONLINEBANK_PAY_LOG', //支付方式，可空。ONLINEBANK_PAY_LOG: 線上網銀支付
        'client_ip'           => '',                   //客戶IP
        'sign'                => ''                    //商戶簽名字段
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'merchantId' => 'number',
        'tx_date' => 'orderCreateDate',
        'tx_no' => 'orderId',
        'item_no' => 'orderId',
        'amount' => 'amount',
        'notice_url' => 'notify_url',
        'client_ip' => 'ip'
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'cmd',
        'mobile',
        'merchantId',
        'tx_date',
        'tx_time',
        'tx_no',
        'tx_params',
        'payment_mode',
        'debit_list',
        'debit_periodic_unit',
        'debit_interval',
        'first_debit_date',
        'close_date',
        'amount',
        'price',
        'discount',
        'item_name',
        'item_no',
        'item_quantity',
        'image_url',
        'notice_method',
        'return_url',
        'cancel_return_url',
        'notice_url',
        'no_shipping',
        'valid_time',
        'client_ip'
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'cmd' => 1, //命令
        'merchantId' => 1, //商戶代碼
        'mobile' => 1, //支付用戶手機號
        'amount' => 1, //扣款金額
        'tx_date' => 0, //支付日期
        'tx_no' => 0, //交易流水號
        'tx_params' => 0, //交易參數
        'temp' => 1, //保留字段
        'settlement_date' => 1, //扣款日期
        'status' => 1, //支付結果
        'desc' => 0 //支付結果描述，描述status為02(支付失敗)的原因
    ];

    /**
     * 應答機制訊息
     *
     * @var string
     */
    protected $msg = 'code=200';

    /**
     * 查詢時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $trackingRequestData = [
        'user_id'            => '', //商戶
        'card_no'            => '', //卡號
        'card_pswd'          => '', //卡密
        'merchant_tx_seq_no' => '', //商戶訂單號
        'sign'               => ''  //簽名數據
    ];

    /**
     * 查詢時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $trackingRequireMap = [
        'user_id' => 'number',
        'merchant_tx_seq_no' => 'orderId'
    ];

    /**
     * 查詢時需要加密的參數
     *
     * @var array
     */
    protected $trackingEncodeParams = [
        'user_id',
        'card_no',
        'card_pswd',
        'merchant_tx_seq_no'
    ];

    /**
     * 回傳支付時要給支付平台驗證的資料
     *
     * @return array
     */
    public function getVerifyData()
    {
        //如果沒有商家id就丟例外
        if (trim($this->options['merchantId']) === '') {
            throw new PaymentException('No pay parameter specified', 180145);
        }

        $this->payVerify();

        $this->options['notify_url'] = sprintf(
            '%s?pay_system=%s&hallid=%s',
            $this->options['notify_url'],
            $this->options['merchantId'],
            $this->options['domain']
        );

        // 從內部給定值到參數
        foreach ($this->requireMap as $paymentKey => $internalKey) {
            $this->requestData[$paymentKey] = $this->options[$internalKey];
        }

        //額外的參數設定
        $date = new \DateTime($this->requestData['tx_date']);
        $this->requestData['tx_date'] = $date->format("Ymd");
        $this->requestData['tx_time'] = $date->format("His");
        //金額以分為單位，必須為整數
        $this->requestData['amount'] = round($this->options['amount'] * 100);

        //設定支付平台需要的加密串
        $this->requestData['sign'] = $this->encode();

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

        //組織加密串
        foreach (array_keys($this->decodeParams) as $paymentKey) {
            //如果為空值，則填入字串null，因此這邊先預設為null字串
            $encodeData[$paymentKey] = 'null';

            //如果有key而且不是空值就要把預設的null改掉
            if (array_key_exists($paymentKey, $this->options) && trim($this->options[$paymentKey]) !== '') {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        $encodeStr = urldecode(http_build_query($encodeData));

        //沒有sign就要丟例外，其他的參數在組織加密串的時候驗證過了
        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        $sign = $this->strToHex($this->options['sign']);

        if (openssl_verify($encodeStr, $sign, $this->getRsaPublicKey()) !== 1) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['status'] !== '01') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['tx_no'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['amount'] != round($entry['amount'] * 100)) {
            throw new PaymentException('Order Amount error', 180058);
        }
    }

    /**
     * 訂單查詢
     */
    public function paymentTracking()
    {
        // 如果沒有商家id就丟例外
        if (trim($this->options['merchantId']) === '') {
            throw new PaymentException('No tracking parameter specified', 180138);
        }

        $this->trackingVerify();

        // 從內部給定值到參數
        foreach ($this->trackingRequireMap as $paymentKey => $internalKey) {
            $this->trackingRequestData[$paymentKey] = $this->options[$internalKey];
        }
        $this->trackingRequestData['sign'] = $this->trackingEncode();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'method' => 'GET',
            'uri' => '/paymentGateway/queryMerchantOrder.htm',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => http_build_query($this->trackingRequestData),
            'header' => []
        ];

        // 取得訂單查詢結果
        $result = $this->curlRequest($curlParam);
        $parseData = $this->parseData($result);

        // 如果沒有$parseData['topupResult']要丟例外
        if (!isset($parseData['topupResult'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        // 如果沒有transactionStatus要丟例外
        if (!isset($parseData['topupResult']['transactionStatus'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        if ($parseData['topupResult']['transactionStatus'] === '00') {
            throw new PaymentConnectionException('Unpaid order', 180062, $this->getEntryId());
        }

        // 10為成功，防止有其他的錯誤碼，因此設定非10即為失敗
        if ($parseData['topupResult']['transactionStatus'] !== '10') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($parseData['topupResult']['transactionFactAmount'] != round($this->options['amount'] * 100)) {
            throw new PaymentException('Order Amount error', 180058);
        }
    }

    /**
     * 取得訂單查詢時需要的參數
     *
     * @return array
     */
    public function getPaymentTrackingData()
    {
        // 如果沒有商家id就丟例外
        if (trim($this->options['merchantId']) === '') {
            throw new PaymentException('No tracking parameter specified', 180138);
        }

        $this->trackingVerify();

        // 從內部給定值到參數
        foreach ($this->trackingRequireMap as $paymentKey => $internalKey) {
            $this->trackingRequestData[$paymentKey] = $this->options[$internalKey];
        }
        $this->trackingRequestData['sign'] = $this->trackingEncode();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'verify_ip' => $this->options['verify_ip'],
            'path' => '/paymentGateway/queryMerchantOrder.htm?' . http_build_query($this->trackingRequestData),
            'method' => 'GET',
            'headers' => [
                'Host' => $this->options['verify_url']
            ]
        ];

        return $curlParam;
    }

    /**
     * 驗證訂單查詢是否成功
     */
    public function paymentTrackingVerify()
    {
        $parseData = $this->parseData($this->options['content']);

        // 如果沒有$parseData['topupResult']要丟例外
        if (!isset($parseData['topupResult'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        // 如果沒有transactionStatus要丟例外
        if (!isset($parseData['topupResult']['transactionStatus'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        if ($parseData['topupResult']['transactionStatus'] === '00') {
            throw new PaymentConnectionException('Unpaid order', 180062, $this->getEntryId());
        }

        // 10為成功，防止有其他的錯誤碼，因此設定非10即為失敗
        if ($parseData['topupResult']['transactionStatus'] !== '10') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($parseData['topupResult']['transactionFactAmount'] != round($this->options['amount'] * 100)) {
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

        //加密設定
        foreach ($this->encodeParams as $index) {
            //如果為空值，則填入字串null，因此這邊先預設為null字串
            $encodeData[$index] = 'null';

            if (trim($this->requestData[$index]) !== '') {
                $encodeData[$index] = $this->requestData[$index];
            }
        }

        $encodeStr = urldecode(http_build_query($encodeData));

        $sign = '';
        if (!openssl_sign($encodeStr, $sign, $this->getRsaPrivateKey())) {
            throw new PaymentException('Generate signature failure', 180144);
        }

        return $this->hexToStr($sign);
    }

    /**
     * 訂單查詢時的加密
     *
     * @return string
     */
    protected function trackingEncode()
    {
        $encodeData = [];

        //加密設定
        foreach ($this->trackingEncodeParams as $index) {
            //如果為空值，則填入字串null
            $encodeData[$index] = 'null';

            if (trim($this->trackingRequestData[$index]) !== '') {
                $encodeData[$index] = $this->trackingRequestData[$index];
            }
        }

        $encodeStr = urldecode(http_build_query($encodeData));

        $sign = '';
        if (!openssl_sign($encodeStr, $sign, $this->getRsaPrivateKey())) {
            throw new PaymentException('Generate signature failure', 180144);
        }

        return $this->hexToStr($sign);
    }

    /**
     * 字串轉換成16進制
     *
     * @param string $string
     * @return string
     */
    private function strToHex($string)
    {
        $ret = '';

        for ($i = 0; $i < strlen($string); $i += 2) {
            $sub = substr($string, $i, 2);
            $r = hexdec($sub);

            if ($r > 127) {
                $r = 127 - $r;
            }

            $ret .= pack('c', $r);
        }

        return $ret;
    }

    /**
     * 16進制轉換成字串
     *
     * @param string $string
     * @return string
     */
    public function hexToStr($string)
    {
        $ret = '';

        $data = unpack('c*byte', $string);
        for ($i = 1; $i <= count($data); $i++) {
            $sub = dechex($data["byte$i"]);

            if ($data["byte$i"] < 0) {
                $sub = dechex(127 - $data["byte$i"]);
            }

            if (strlen($sub) < 2) {
                $ret .= '0' . $sub;
                continue;
            }

            $ret .= $sub;
        }

        return $ret;
    }

    /**
     * 入款查詢時使用，用來分解訂單查詢(補單)時回傳的XML格式
     *
     * @param string $content xml格式的回傳值
     * @return array
     */
    private function parseData($content)
    {
        $decodeContent = urldecode($content);

        //支付平台返回訊息是urlencode過的，所以要先urldecode，編碼是GB2312要做轉換
        $convertContent = iconv('GB2312', 'UTF-8', urldecode($decodeContent));

        return $this->xmlToArray($convertContent);
    }

    /**
     * 回傳RSA私鑰
     *
     * @return resource
     */
    public function getRsaPrivateKey()
    {
        $passphrase = 'smartpay';

        // 因存入DB會先做base64_encode，因此取出來要先base64_decode
        $content = base64_decode($this->options['rsa_private_key']);

        //如果取到的fileContent欄位是空的就要丟例外
        if (!$content) {
            throw new PaymentException('Rsa private key is empty', 180092);
        }

        $privateKey = openssl_pkey_get_private($content, $passphrase);

        if (!$privateKey) {
            throw new PaymentException('Get rsa private key failure', 180093);
        }

        return $privateKey;
    }
}

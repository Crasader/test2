<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 99寶付二代支付
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
class BooFooII99 extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'MerchantID'     => '',   //商號
        'PayID'          => 1000, //支付渠道
        'TradeDate'      => '',   //交易日期
        'TransID'        => '',   //訂單編號
        'OrderMoney'     => '',   //訂單金額
        'ProductName'    => '',   //商品名稱
        'Amount'         => 1,    //商品數量
        'ProductLogo'    => '',   //商品圖片url
        'Username'       => '',   //支付用戶名稱
        'Email'          => '',   //用戶電子郵件
        'Mobile'         => '',   //用戶手機
        'AdditionalInfo' => '',   //訂單附加訊息
        'Merchant_url'   => '',   //通知商戶url
        'Return_url'     => '',   //底層通知url
        'Md5Sign'        => '',   //Md5簽名
        'NoticeType'     => 0,    //通知方式，0: 伺服器通知，1: 伺服器通知及網頁通知
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'MerchantID' => 'number',
        'TradeDate' => 'orderCreateDate',
        'TransID' => 'orderId',
        'OrderMoney' => 'amount',
        'Merchant_url' => 'notify_url',
        'Return_url' => 'notify_url'
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'MerchantID',
        'PayID',
        'TradeDate',
        'TransID',
        'OrderMoney',
        'Merchant_url',
        'Return_url',
        'NoticeType',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'MerchantID' => 1,
        'TransID' => 1,
        'Result' => 1,
        'resultDesc' => 1,
        'factMoney' => 1,
        'additionalInfo' => 1,
        'SuccTime' => 1
    ];

    /**
     * 應答機制訊息
     *
     * @var string
     */
    protected $msg = 'OK';

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        1  => "3002", //工商银行
        2  => "3020", //交通银行
        3  => "3005", //农业银行
        4  => "3003", //建设银行
        5  => "3001", //招商银行
        6  => "3006", //民生银行
        8  => "3004", //浦发银行
        9  => "3032", //北京银行
        10 => "3009", //兴业银行
        11 => "3039", //中信银行
        12 => "3022", //光大银行
        14 => "3036", //广发银行
        15 => "3035", //平安银行
        16 => "3038", //中国邮政储蓄银行
        17 => "3026", //中国银行
        19 => "3059", //上海银行
    ];

    /**
     * 查詢時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $trackingRequestData = [
        'MerchantID' => '', //商號
        'TransID'    => '', //訂單編號
        'Md5Sign'    => '', //Md5簽名
    ];

    /**
     * 查詢時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $trackingRequireMap = [
        'MerchantID' => 'number',
        'TransID' => 'orderId'
    ];

    /**
     * 查詢時需要加密的參數
     *
     * @var array
     */
    protected $trackingEncodeParams = [
        'MerchantID',
        'TransID',
    ];

    /**
     * 查詢解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $trackingDecodeParams = [
        'MerchantID' => 1,
        'TransID' => 1,
        'CheckResult' => 1,
        'factMoney' => 1,
        'SuccTime' => 1
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
        $date = new \DateTime($this->options['orderCreateDate']);
        $this->requestData['TradeDate'] = $date->format("YmdHis");
        $this->requestData['OrderMoney'] = round($this->options['amount'] * 100);

        //設定支付平台需要的加密串
        $this->requestData['Md5Sign'] = $this->encode();

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

        $encodeStr = '';

        //組織加密串
        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options)) {
                $encodeStr .= $this->options[$paymentKey];
            }
        }

        //進行加密
        $encodeStr .= $this->privateKey;
        $encodeStr = md5($encodeStr);

        //沒有Md5Sign就要丟例外，其他的參數在組織加密串的時候驗證過了
        if (!isset($this->options['Md5Sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['Md5Sign'] != $encodeStr) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['Result'] != '1' || $this->options['resultDesc'] !== '01') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['TransID'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['factMoney'] != round($entry['amount'] * 100)) {
            throw new PaymentException('Order Amount error', 180058);
        }
    }

    /**
     * 訂單查詢
     */
    public function paymentTracking()
    {
        // 驗證私鑰
        $this->verifyPrivateKey();
        $this->trackingVerify();

        // 從內部給定值到參數
        foreach ($this->trackingRequireMap as $paymentKey => $internalKey) {
            $this->trackingRequestData[$paymentKey] = $this->options[$internalKey];
        }
        $this->trackingRequestData['Md5Sign'] = $this->trackingEncode();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'method' => 'POST',
            'uri' => '/Check/OrderQuery.aspx',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => http_build_query($this->trackingRequestData),
            'header' => []
        ];

        // 取得訂單查詢結果
        $result = $this->curlRequest($curlParam);

        // 因parseData有驗證過數量，所以不會有不存在index的問題，這邊不需要驗證
        $parseData = $this->parseData($result);
        $encodeStr = '';

        foreach (array_keys($this->trackingDecodeParams) as $index) {
            if (array_key_exists($index, $parseData)) {
                $encodeStr .= $parseData[$index];
            }
        }

        // 進行加密
        $encodeStr .= $this->privateKey;

        if ($parseData['Md5Sign'] != md5($encodeStr)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($parseData['CheckResult'] == 'P') {
            throw new PaymentConnectionException('Order Processing', 180059, $this->getEntryId());
        }

        if ($parseData['CheckResult'] == 'N') {
            throw new PaymentConnectionException('Order does not exist', 180060, $this->getEntryId());
        }

        if ($parseData['CheckResult'] == 'F') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($parseData['factMoney'] != round($this->options['amount'] * 100)) {
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
        // 驗證私鑰
        $this->verifyPrivateKey();
        $this->trackingVerify();

        // 從內部給定值到參數
        foreach ($this->trackingRequireMap as $paymentKey => $internalKey) {
            $this->trackingRequestData[$paymentKey] = $this->options[$internalKey];
        }
        $this->trackingRequestData['Md5Sign'] = $this->trackingEncode();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'verify_ip' => $this->options['verify_ip'],
            'path' => '/Check/OrderQuery.aspx',
            'method' => 'POST',
            'form' => $this->trackingRequestData,
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
        // 驗證私鑰
        $this->verifyPrivateKey();

        // 因parseData有驗證過數量，所以不會有不存在index的問題，這邊不需要驗證
        $parseData = $this->parseData($this->options['content']);
        $encodeStr = '';

        foreach (array_keys($this->trackingDecodeParams) as $index) {
            if (array_key_exists($index, $parseData)) {
                $encodeStr .= $parseData[$index];
            }
        }

        // 進行加密
        $encodeStr .= $this->privateKey;

        if ($parseData['Md5Sign'] != md5($encodeStr)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($parseData['CheckResult'] == 'P') {
            throw new PaymentConnectionException('Order Processing', 180059, $this->getEntryId());
        }

        if ($parseData['CheckResult'] == 'N') {
            throw new PaymentConnectionException('Order does not exist', 180060, $this->getEntryId());
        }

        if ($parseData['CheckResult'] == 'F') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($parseData['factMoney'] != round($this->options['amount'] * 100)) {
            throw new PaymentException('Order Amount error', 180058);
        }
    }

    /**
     * 分析支付平台回傳的入款查詢參數
     *
     * @param array $content
     * @return array
     */
    private function parseData($content)
    {
        $parseData = [];
        $content = explode('|', urldecode($content));

        if (count($content) != 6) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        $paramMap = [
            'MerchantID',
            'TransID',
            'CheckResult',
            'factMoney',
            'SuccTime',
            'Md5Sign'
        ];

        foreach ($content as $key => $value) {
            $parseData[$paramMap[$key]] = $value;
        }

        return $parseData;
    }
}

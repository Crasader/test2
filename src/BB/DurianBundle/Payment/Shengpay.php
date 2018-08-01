<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 盛付通支付
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
class Shengpay extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'Version' => '3.0', //版本號
        'Amount' => '', //支付金額 必须含兩位小數如:2.00
        'OrderNo' => '', //商户訂單號
        'MerchantNo' => '', //商户號
        'MerchantUserId' => '', //商户用户
        'PayChannel' => '04', //支付渠道
        'PostBackUrl' => '', //回調地址
        'NotifyUrl' => '', //發貨地址
        'BackUrl' => '', //商户下單地址
        'OrderTime' => '', //訂單日期
        'CurrencyType' => 'RMB', //貨幣類型
        'NotifyUrlType' => 'http', //發貨通知方式
        'SignType' => '2', //簽名類型 1:RSA 2:MD5 3:PKI
        'ProductNo' => '', //商品编號
        'ProductDesc' => '', //商品描述
        'Remark1' => '', //備註 1
        'Remark2' => '', //備註 2
        'BankCode' => '', //支付銀行
        'DefaultChannel' => '', //默認渠道
        'MAC' => '', //加密串
        'ExterInvokeIp' => '' //外部調用ip
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'Amount' => 'amount',
        'OrderNo' => 'orderId',
        'MerchantNo' => 'number',
        'PostBackUrl' => 'notify_url',
        'NotifyUrl' => 'notify_url',
        'OrderTime' => 'orderCreateDate',
        'ProductDesc' => 'username',
        'BankCode' => 'paymentVendorId'
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'Version',
        'Amount',
        'OrderNo',
        'MerchantNo',
        'MerchantUserId',
        'PayChannel',
        'PostBackUrl',
        'NotifyUrl',
        'BackUrl',
        'OrderTime',
        'CurrencyType',
        'NotifyUrlType',
        'SignType',
        'ProductNo',
        'ProductDesc',
        'Remark1',
        'Remark2',
        'BankCode',
        'DefaultChannel',
        'ExterInvokeIp'
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'Amount' => 1,
        'PayAmount' => 1,
        'OrderNo' => 1,
        'serialno' => 1,
        'Status' => 1,
        'MerchantNo' => 1,
        'PayChannel' => 1,
        'Discount' => 1,
        'SignType' => 1,
        'PayTime' => 1,
        'CurrencyType' => 1,
        'ProductNo' => 1,
        'ProductDesc' => 1,
        'Remark1' => 1,
        'Remark2' => 1,
        'ExInfo' => 1
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
        '1' => 'ICBC', //工商銀行
        '2' => 'BOCOM', //交通銀行
        '3' => 'ABOC', //農業銀行
        '4' => 'CCB', //建設銀行
        '5' => 'CMBCHINA', //招商銀行
        '6' => 'CMBC', //民生銀行總行
        '7' => 'SDB', //深圳發展銀行
        '8' => 'SPDB', //上海浦東發展銀行
        '9' => 'BOBJ', //北京銀行
        '10' => 'CIB', //興業銀行
        '11' => 'ECITIC', //中信銀行
        '12' => 'CEB', //光大銀行
        '13' => 'HXB', //華夏銀行
        '14' => 'GDB', //廣東發展銀行
        '15' => 'SZPAB', //深圳平安銀行
        '16' => 'PSBC', //中國郵政
        '17' => 'CB', //中國銀行
        '18' => 'GNXS', //農村信用合作社
        '19' => 'BOS', //上海銀行
        '217' => 'BHB', //渤海銀行
        '219' => 'GZCB', //廣州銀行
        '220' => 'HZB', //杭州銀行
        '222' => 'NBCB', //寧波銀行
        '223' => 'HKBEA', //東亞銀行
        '224' => 'WZCB', //溫州銀行
        '225' => 'SXJS', //晉商銀行
        '226' => 'NJCB', //南京銀行
        '227' => 'GNXS', //廣州農村信用合作社
        '228' => 'SHRCB', //上海市農村商業銀行
        '229' => 'HKBCHINA', //漢口銀行
        '230' => 'ZHNX', //珠海市農村信用合作聯
        '231' => 'SDE', //順德農信社
        '232' => 'YDXH', //堯都信用合作聯社
        '233' => 'CZCB', //浙江稠州商業銀行
        '234' => 'BJRCB' //北京農商行
    ];

    /**
     * 查詢時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $trackingRequestData = [
        'OrderNo' => '',
        'MerchantNo' => '',
        'SignType' => '2',
        'Remark1' => '',
        'Remark2' => '',
        'Mac' => ''
    ];

    /**
     * 查詢時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $trackingRequireMap = [
        'OrderNo' => 'orderId',
        'MerchantNo' => 'number'
    ];

    /**
     * 查詢時需要加密的參數
     *
     * @var array
     */
    protected $trackingEncodeParams = [
        'OrderNo',
        'MerchantNo',
        'SignType'
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

        //帶入未支援的銀行就噴例外
        if (!array_key_exists($this->requestData['BankCode'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        //額外的參數設定(要送去支付平台的參數)
        $this->requestData['Amount'] = sprintf("%.2f", $this->requestData['Amount']);
        $orderTime = new \DateTime($this->requestData['OrderTime']);
        $this->requestData['OrderTime'] = $orderTime->format('YmdHis');
        $this->requestData['BankCode'] = $this->bankMap[$this->requestData['BankCode']];

        //設定支付平台需要的加密串
        $this->requestData['MAC'] = $this->encode();

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

        //組織加密串
        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options)) {
                $encodeData[] = $this->options[$paymentKey];
            }
        }

        $encodeData[] = $this->privateKey;

        //組加密串
        $encodeStr = implode('|', $encodeData);

        //沒有Md5Sign就要丟例外，其他的參數在組織加密串的時候驗證過了
        if (!isset($this->options['MAC'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if (strtolower($this->options['MAC']) != md5($encodeStr)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['Status'] != '01') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['OrderNo'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['PayAmount'] != $entry['amount']) {
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
        $this->trackingRequestData['Mac'] = $this->trackingEncode();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'method' => 'POST',
            'uri' => '/orders.asmx',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => http_build_query($this->trackingRequestData),
            'header' => []
        ];

        // 取得訂單查詢結果
        $result = $this->curlRequest($curlParam);
        parse_str(urldecode($result), $result);
        $parseData = $this->parseData($result);

        // 沒有code就要丟例外
        if (!isset($parseData['Code'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        // 沒有status就要丟例外
        if (!isset($parseData['Order']['Status'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        if ($parseData['Code'] != '0' || $parseData['Order']['Status'] != '01') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($parseData['Order']['PayAmount'] != $this->options['amount']) {
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
        $this->trackingRequestData['Mac'] = $this->trackingEncode();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'verify_ip' => $this->options['verify_ip'],
            'path' => '/orders.asmx',
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

        parse_str(urldecode($this->options['content']), $this->options['content']);
        $parseData = $this->parseData($this->options['content']);

        // 沒有code就要丟例外
        if (!isset($parseData['Code'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        // 沒有status就要丟例外
        if (!isset($parseData['Order']['Status'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        if ($parseData['Code'] != '0' || $parseData['Order']['Status'] != '01') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($parseData['Order']['PayAmount'] != $this->options['amount']) {
            throw new PaymentException('Order Amount error', 180058);
        }
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
            $encodeData[] = $this->trackingRequestData[$index];
        }

        $encodeStr = implode('|', $encodeData);
        return md5($encodeStr . $this->privateKey);
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

        if (isset($content['OrderQueryResult'])) {
            $parseData = array_merge($content, $content['OrderQueryResult']);
            unset($content['OrderQueryResult']);
        }

        return $parseData;
    }
}

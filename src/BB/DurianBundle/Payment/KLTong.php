<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 開聯通支付
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
class KLTong extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'MerchantID' => '', //商戶號
        'MerOrderNo' => '', //訂單號
        'CardType'   => '15', //商品代碼(15: 網銀)
        'BankID'     => '', //銀行代碼
        'Money'      => '', //支付金額
        'CustomizeA' => '', //自定義返回參數
        'CustomizeB' => '', //自定義返回參數
        'CustomizeC' => '', //自定義返回參數
        'NoticeURL'  => '', //returnUrl
        'NoticePage' => '', //returnUrl
        'sign'       => '' //md5簽名
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'MerchantID' => 'number',
        'MerOrderNo' => 'orderId',
        'BankID' => 'paymentVendorId',
        'Money' => 'amount',
        'NoticeURL' => 'notify_url'
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'MerchantID',
        'MerOrderNo',
        'CardType',
        'BankID',
        'NoticeURL',
        'Money'
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'PayOrderNo' => 1,
        'MerchantID' => 1,
        'MerOrderNo' => 1,
        'CardNo' => 1,
        'CardType' => 1,
        'FactMoney' => 1,
        'PayResult' => 1,
        'CustomizeA' => 1,
        'CustomizeB' => 1,
        'CustomizeC' => 1,
        'PayTime' => 1
    ];

    /**
     * 應答機制訊息
     *
     * @var string
     */
    protected $msg = 'OK';

    /**
     * 查詢時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $trackingRequestData = [
        'MerchantID' => '',
        'MerOrderID' => ''
    ];

    /**
     * 查詢時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $trackingRequireMap = [
        'MerchantID' => 'number',
        'MerOrderID' => 'orderId'
    ];

    /**
     * 查詢時需要加密的參數
     *
     * @var array
     */
    protected $trackingEncodeParams = [
        'MerchantID',
        'MerOrderID',
    ];

    /**
     * 查詢解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $trackingDecodeParams = [
        'PayOrderNo' => 1,
        'MerchantID' => 1,
        'MerOrderNo' => 1,
        'CardNo' => 1,
        'CardType' => 1,
        'FactMoney' => 1,
        'PayResult' => 1,
        'CustomizeA' => 1,
        'CustomizeB' => 1,
        'CustomizeC' => 1,
        'PayTime' => 1
    ];

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        '1'   => 'ICBC', //中國工商銀行
        '2'   => 'COMM', //交通銀行
        '3'   => 'ABC', //中國農業銀行
        '4'   => 'CCB', //中國建設銀行
        '5'   => 'CMB', //招商銀行
        '6'   => 'CMBC', //中國民生銀行
        '7'   => 'SDB', //深圳發展銀行
        '8'   => 'SPDB', //上海浦東發展銀行
        '9'   => 'BCCB', //北京銀行
        '10'  => 'CIB', //興業銀行
        '11'  => 'CITIC', //中信銀行
        '12'  => 'CEB', //中國光大銀行
        '13'  => 'HXB', //華夏銀行
        '14'  => 'GDB', //廣東發展銀行
        '15'  => 'SZPAB', //平安银行
        '16'  => 'PSBC', //中國郵政
        '17'  => 'BOC', //中國銀行
        '19'  => 'BOS', //上海銀行
        '217' => 'CBHB', //渤海銀行
        '219' => 'GZCB', //廣州銀行
        '220' => 'HCCB', //杭州銀行
        '222' => 'NBCB', //寧波銀行
        '223' => 'HKBEA', //東亞銀行
        '224' => 'WZCB', //溫州銀行
        '226' => 'NJCB', //南京銀行
        '227' => 'GNXS', //廣州市農信社
        '228' => 'SHRCB', //上海市農商行
        '229' => 'HKBCHINA', //漢口銀行
        '230' => 'ZHNX', //珠海市農村信用聯
        '231' => 'SDE', //順德農信社
        '232' => 'YDXH', //堯都信用合作聯社
        '233' => 'CZCB', //浙江稠州商業銀行
        '234' => 'BJRCB' //北京農商行
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
        if (!array_key_exists($this->requestData['BankID'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        //額外的參數設定
        $this->requestData['BankID'] = $this->bankMap[$this->requestData['BankID']];

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
        // 驗證私鑰
        $this->verifyPrivateKey();

        $this->payResultVerify();

        $encodeData = [];

        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (!array_key_exists($paymentKey, $this->options)) {
                continue;
            }
            if ($paymentKey == 'PayTime') {
                //時間回傳值格式為2014-04-07+00%3A08%3A24.21，因此要做urldecode
                $this->options['PayTime'] = urldecode($this->options['PayTime']);
            }

            $encodeData [] = $this->options[$paymentKey];
        }

        //進行加密
        $encodeData[] = $this->privateKey;
        $encodeStr = implode('|', $encodeData);

        //沒有sign就要丟例外
        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if (strtoupper($this->options['sign']) != strtoupper(md5($encodeStr))) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['PayResult'] != 'true') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['MerOrderNo'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['FactMoney'] != $entry['amount']) {
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
        $this->trackingRequestData['sign'] = $this->trackingEncode();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'method' => 'POST',
            'uri' => '/busics/MerQuery',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => http_build_query($this->trackingRequestData),
            'header' => []
        ];

        // 取得訂單查詢結果
        $result = $this->curlRequest($curlParam);
        $parseData = $this->parseData($result);
        $this->trackingResultVerify($parseData[1]);

        $encodeData = [];

        foreach (array_keys($this->trackingDecodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $parseData[1])) {
                $encodeData[] = $parseData[1][$paymentKey];
            }
        }

        // 進行加密
        $encodeData[] = $this->privateKey;
        $encodeStr = implode('|', $encodeData);

        if (!isset($parseData[1]['sign'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        if (strtoupper($parseData[1]['sign']) != strtoupper(md5($encodeStr))) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        // 訂單结果: true表示成功, false表示失败, treat表示處理中
        if ($parseData[1]['PayResult'] == 'treat') {
            throw new PaymentConnectionException('Order Processing', 180059, $this->getEntryId());
        }

        if ($parseData[1]['PayResult'] != 'true') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($parseData[1]['FactMoney'] != $this->options['amount']) {
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
        $this->trackingRequestData['sign'] = $this->trackingEncode();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'verify_ip' => $this->options['verify_ip'],
            'path' => '/busics/MerQuery',
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

        $parseData = $this->parseData($this->options['content']);
        $this->trackingResultVerify($parseData[1]);

        $encodeData = [];

        foreach (array_keys($this->trackingDecodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $parseData[1])) {
                $encodeData[] = $parseData[1][$paymentKey];
            }
        }

        // 進行加密
        $encodeData[] = $this->privateKey;
        $encodeStr = implode('|', $encodeData);

        if (!isset($parseData[1]['sign'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        if (strtoupper($parseData[1]['sign']) != strtoupper(md5($encodeStr))) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        // 訂單结果: true表示成功, false表示失败, treat表示處理中
        if ($parseData[1]['PayResult'] == 'treat') {
            throw new PaymentConnectionException('Order Processing', 180059, $this->getEntryId());
        }

        if ($parseData[1]['PayResult'] != 'true') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($parseData[1]['FactMoney'] != $this->options['amount']) {
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
        //加密設定
        $encodeData = [];

        foreach ($this->encodeParams as $index) {
            $encodeData[] = $this->requestData[$index];
        }

        //額外的加密設定
        $encodeData[] = $this->privateKey;
        $encodeStr = implode('|', $encodeData);

        return md5($encodeStr);
    }

    /**
     * 訂單查詢時的加密
     *
     * @return string
     */
    protected function trackingEncode()
    {
        // 查詢接口key固定為: 49RU4T74HDY38976DT5JY36F44TN
        $privateKey = '49RU4T74HDY38976DT5JY36F44TN';

        $encodeStr = '';

        foreach ($this->trackingEncodeParams as $index) {
            $encodeStr .= $this->trackingRequestData[$index];
        }

        $encodeStr .= $privateKey;

        return md5($encodeStr);
    }

    /**
     * 分析支付平台回傳的入款查詢參數
     *
     * @param array $content
     * @return array
     */
    private function parseData($content)
    {
        $data = explode(',', urldecode($content), 2);

        $parseUrl = [];

        if (isset($data[1])) {
            $parseUrl = parse_url($data[1]);
        }

        if ($parseUrl === false) {
            throw new PaymentConnectionException('Payment tracking failed', 180081, $this->getEntryId());
        }

        // 如果解析後沒有query的部分，直接丟出返回的結果
        if (!isset($parseUrl['query'])) {
            throw new PaymentConnectionException($content, 180123, $this->getEntryId());
        }

        $query = str_replace('?', '&', $parseUrl['query']);
        parse_str($query, $verifyData);

        // 第一個參數是查詢結果、第二個參數是要用來解密的
        $parseData = [
            $data[0],
            $verifyData
        ];

        return $parseData;
    }
}

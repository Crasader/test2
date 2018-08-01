<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 幣付寶
 */
class Befpay extends PaymentBase
{
    /**
     * 支付提交參數
     *
     * @var array
     */
    protected $requestData = [
        'p1_md' => '1', // 1 網銀
        'p2_xn' => '', // 訂單號
        'p3_bn' => '', // 商戶號
        'p4_pd' => '', // 支付銀行ID
        'p5_name' => '', // 產品名稱(這邊塞username方便業主比對)
        'p6_amount' => '', // 支付金額
        'p7_cr' => '1', // 幣種，目前只支持人民幣
        'p8_ex' => '', // 擴展信息
        'p9_url' => '', // 通知支付结果地址
        'p10_reply' => '1', // 是否通知
        'p11_mode' => '2', // 2 不顯示幣付寶充值界面直接跳轉到網銀
        'p12_ver' => '1', // 版本號
        'sign' => '', // 簽名值
    ];

    /**
     * 支付提交參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'p3_bn' => 'number',
        'p2_xn' => 'orderId',
        'p5_name' => 'username',
        'p6_amount' => 'amount',
        'p9_url' => 'notify_url',
        'p4_pd' => 'paymentVendorId',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'p1_md',
        'p2_xn',
        'p3_bn',
        'p4_pd',
        'p5_name',
        'p6_amount',
        'p7_cr',
        'p8_ex',
        'p9_url',
        'p10_reply',
        'p11_mode',
        'p12_ver',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'p1_md' => 1,
        'p2_sn' => 1,
        'p3_xn' => 1,
        'p4_amt' => 1,
        'p5_ex' => 1,
        'p6_pd' => 1,
        'p7_st' => 1,
        'p8_reply' => 1,
    ];

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        1 => '10018', // 中國工商銀行
        2 => '10016', // 交通銀行
        3 => '10022', // 中國農業銀行
        4 => '10020', // 中國建設銀行
        5 => '10001', // 招商銀行
        6 => '10004', // 中國民生銀行
        8 => '10012', // 上海浦東發展銀行
        9 => '10010', // 北京銀行
        10 => '10002', // 興業銀行
        11 => '10003', // 中信銀行
        12 => '10005', // 中國光大銀行
        13 => '10006', // 華夏銀行
        14 => '10014', // 廣東發展銀行
        15 => '10017', // 深圳平安銀行
        16 => '10011', // 中國郵政
        17 => '10009', // 中國銀行
        220 => '10019', // 杭州銀行
        221 => '10023', // 浙商銀行
        222 => '10021', // 寧波銀行
        223 => '10013', // 東亞銀行
        226 => '10015', // 南京銀行
        234 => '10007', // 北京農村商業銀行
    ];

    /**
     * 查詢時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $trackingRequestData = [
        'BN' => '', // 商戶號
        'XN' => '', // 訂單號
        'DATE' => '', // 查詢日期
        'SIGN' => '', // 簽名數據
    ];

    /**
     * 查詢時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $trackingRequireMap = [
        'BN' => 'number',
        'XN' => 'orderId',
    ];

    /**
     * 查詢解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $trackingDecodeParams = [
        'AT' => 1,
        'SN' => 1,
        'PName' => 1,
        'XN' => 1,
        'SP' => 1,
        'Fee' => 1,
        'Amt' => 1,
        'ST' => 1,
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

        // 帶入未支援的銀行就噴例外
        if (!array_key_exists($this->requestData['p4_pd'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        $this->requestData['p4_pd'] = $this->bankMap[$this->requestData['p4_pd']];
        $this->requestData['p6_amount'] = sprintf('%.2f', $this->requestData['p6_amount']);

        // 設定支付平台需要的加密串
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
            if (array_key_exists($paymentKey, $this->options)) {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        // 依key1=value1&key2=value2&...&keyN=valueN組成字串
        $encodeStr = urldecode(http_build_query($encodeData));

        // privateKey的值接在最後面
        $encodeStr .= $this->privateKey;

        // 沒有返回sign就要丟例外
        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if (strtoupper($this->options['sign']) != strtoupper(md5($encodeStr))) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['p7_st'] != 'success') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['p3_xn'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['p4_amt'] != $entry['amount']) {
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

        // 驗證訂單查詢參數
        $this->trackingVerify();

        // 從內部給定值到參數
        foreach ($this->trackingRequireMap as $paymentKey => $internalKey) {
            $this->trackingRequestData[$paymentKey] = $this->options[$internalKey];
        }

        $date = new \DateTime($this->options['orderCreateDate']);
        $this->trackingRequestData['DATE'] = $date->format('Y-m-d');
        $this->trackingRequestData['SIGN'] = $this->trackingEncode();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'method' => 'GET',
            'uri' => '/frontpage/OrderInfo',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => http_build_query($this->trackingRequestData),
            'header' => [],
        ];

        $result = $this->curlRequest($curlParam);

        /**
         * 回傳範例:
         * "AT:2015-8-6 17:38:02|SN:201508068759|PName:befpay|XN:201508060000000203|SP:1|Fee:0.0100|Amt:0.0100|ST:3"
         */
        $regularResult = [];
        preg_match_all('/([^:|]*):([^|]*)/', trim($result, '"'), $regularResult);
        $parseData = array_combine($regularResult[1], $regularResult[2]);

        $this->trackingResultVerify($parseData);

        // 2結帳、3同步，都算是支付成功
        if (!in_array($parseData['ST'], [2, 3])) {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($parseData['XN'] != $this->options['orderId']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($parseData['Amt'] != $this->options['amount']) {
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

        $date = new \DateTime($this->options['orderCreateDate']);
        $this->trackingRequestData['DATE'] = $date->format('Y-m-d');
        $this->trackingRequestData['SIGN'] = $this->trackingEncode();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'verify_ip' => $this->options['verify_ip'],
            'path' => '/frontpage/OrderInfo?' . http_build_query($this->trackingRequestData),
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
        // 驗證私鑰
        $this->verifyPrivateKey();

        /**
         * 回傳範例:
         * "AT:2015-8-6 17:38:02|SN:201508068759|PName:befpay|XN:201508060000000203|SP:1|Fee:0.0100|Amt:0.0100|ST:3"
         */
        $regularResult = [];
        preg_match_all('/([^:|]*):([^|]*)/', trim($this->options['content'], '"'), $regularResult);
        $parseData = array_combine($regularResult[1], $regularResult[2]);

        $this->trackingResultVerify($parseData);

        // 2結帳、3同步，都算是支付成功
        if (!in_array($parseData['ST'], [2, 3])) {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($parseData['XN'] != $this->options['orderId']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($parseData['Amt'] != $this->options['amount']) {
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
            $encodeData[$key] = $this->requestData[$key];
        }

        // 依key1=value1&key2=value2&...&keyN=valueN組成字串
        $encodeStr = urldecode(http_build_query($encodeData));

        $encodeStr .= $this->privateKey;

        return md5($encodeStr);
    }

    /**
     * 訂單查詢時的加密
     *
     * @return string
     */
    protected function trackingEncode()
    {
        $number = $this->options['number'];
        $key = $this->privateKey;

        return md5($number . $key);
    }
}

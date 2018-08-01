<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 易票聯
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
class EPayLinks extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'partner'       => '', //商號
        'out_trade_no'  => '', //訂單號
        'total_fee'     => '', //金額
        'currency_type' => 'RMB', //幣別
        'return_url'    => '', //通知url
        'pay_id'        => '', //銀行代碼
        'sign'          => '' //加密簽名
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'partner' => 'number',
        'out_trade_no' => 'orderId',
        'total_fee' => 'amount',
        'return_url' => 'notify_url',
        'pay_id' => 'paymentVendorId'
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'currency_type',
        'out_trade_no',
        'partner',
        'pay_id',
        'return_url',
        'total_fee'
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'amount' => 1, //金額
        'out_trade_no' => 1, //訂單號
        'partner' => 1, //商號
        'pay_no' => 1, //支付平台的訂單號
        'pay_result' => 1, //支付狀態
        'sett_date' => 1, //支付日期
        'sign_type' => 1, //加密方式
        'version' => 1 //接口版本
    ];

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        '1'  => 'gonghang', //中國工商銀行
        '2'  => 'jiaohang', //交通銀行
        '3'  => 'nonghang', //中國農業銀行
        '4'  => 'jianhang', //中國建設銀行
        '5'  => 'zhaohang', //招商銀行
        '6'  => 'minsheng', //中國民生銀行
        '7'  => 'shenfa', //深圳發展銀行
        '8'  => 'pufa', //上海浦東發展銀行
        '10' => 'xingye', //興業銀行
        '11' => 'zhongxin', //中信銀行
        '12' => 'guangda', //中國光大銀行
        '14' => 'guangfa', //廣東發展銀行
        '15' => 'pingan', //深圳平安銀行
        '17' => 'zhonghang' //中國銀行
    ];

    /**
     * 查詢時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $trackingRequestData = [
        'partner'      => '', //商號
        'out_trade_no' => '', //訂單號
        'sign'         => '' //加密簽名
    ];

    /**
     * 查詢時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $trackingRequireMap = [
        'partner' => 'number',
        'out_trade_no' => 'orderId'
    ];

    /**
     * 查詢時需要加密的參數
     *
     * @var array
     */
    protected $trackingEncodeParams = [
        'partner',
        'out_trade_no'
    ];

    /**
     * 查詢解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $trackingDecodeParams = [
        'amount' => 1,
        'curr_code' => 1,
        'out_trade_no' => 1,
        'partner' => 1,
        'pay_no' => 1,
        'pay_result' => 1,
        'resp_code' => 1,
        'resp_desc' => 1,
        'sett_date' => 1,
        'sign_type' => 1,
        'version' => 1
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
        if (!array_key_exists($this->requestData['pay_id'], $this->bankMap)) {
            throw new PaymentException('PaymentGateway unsupported the PaymentVendor', 180066);
        }

        //額外的參數設定
        $this->requestData['total_fee'] = sprintf('%.2f', $this->requestData['total_fee']);
        $this->requestData['pay_id'] = $this->bankMap[$this->requestData['pay_id']];

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
            if (array_key_exists($paymentKey, $this->options)) {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        /**
         * pay_system、hallid這段因為目前提交的時候returnUrl後面有接這兩個參數，
         * 導致支付平台返回的時將這兩個參數加進去加密串裡，這邊的處理方式是判斷有沒
         * 有這兩個參數，有的話就要加進去加密串做加密，之後有調整就可以拿掉這一段。
         */
        if (isset($this->options['pay_system'])) {
            $encodeData['pay_system'] = $this->options['pay_system'];
        }

        if (isset($this->options['hallid'])) {
            $encodeData['hallid'] = $this->options['hallid'];
        }

        ksort($encodeData);
        $encodeData['key'] = $this->privateKey;
        $encodeStr = urldecode(http_build_query($encodeData));

        //如果沒有簽名擋也要丟例外(其他參數都在前面驗證過了，所以不須要驗證)
        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['sign'] != strtolower(hash("sha256", $encodeStr))) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['pay_result'] != '1') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['out_trade_no'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['amount'] != $entry['amount']) {
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
            'uri' => '/paycenter/queryOrder.do',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => http_build_query($this->trackingRequestData),
            'header' => []
        ];

        // 取得訂單查詢結果
        $result = $this->curlRequest($curlParam);
        $parseData = $this->parseData($result);

        // 如果沒有resp_code要丟例外
        if (!isset($parseData['resp_code'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        if ($parseData['resp_code'] != '00') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        // 如果沒有pay_result要丟例外
        if (!isset($parseData['pay_result'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        if ($parseData['pay_result'] != '1') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }
        $this->trackingResultVerify($parseData);

        $encodeData = [];

        // 加密設定
        foreach (array_keys($this->trackingDecodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $parseData)) {
                $encodeData[$paymentKey] = $parseData[$paymentKey];
            }
        }

        // 額外的加密設定
        ksort($encodeData);
        $encodeData['key'] = $this->privateKey;
        $encodeStr = urldecode(http_build_query($encodeData));

        // 如果沒有sign丟例外
        if (!isset($parseData['sign'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        if ($parseData['sign'] != strtolower(hash('sha256', $encodeStr))) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($parseData['amount'] != $this->options['amount']) {
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
            'path' => '/paycenter/queryOrder.do',
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

        // 檢查訂單查詢返回參數
        $parseData = $this->parseData($this->options['content']);

        // 如果沒有resp_code要丟例外
        if (!isset($parseData['resp_code'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        if ($parseData['resp_code'] != '00') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        // 如果沒有pay_result要丟例外
        if (!isset($parseData['pay_result'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        if ($parseData['pay_result'] != '1') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }
        $this->trackingResultVerify($parseData);

        $encodeData = [];

        // 加密設定
        foreach (array_keys($this->trackingDecodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $parseData)) {
                $encodeData[$paymentKey] = $parseData[$paymentKey];
            }
        }

        // 額外的加密設定
        ksort($encodeData);
        $encodeData['key'] = $this->privateKey;
        $encodeStr = urldecode(http_build_query($encodeData));

        // 如果沒有sign丟例外
        if (!isset($parseData['sign'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        if ($parseData['sign'] != strtolower(hash('sha256', $encodeStr))) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($parseData['amount'] != $this->options['amount']) {
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
            $encodeData[$index] = $this->requestData[$index];
        }

        //額外的加密設定
        ksort($encodeData);
        $encodeData['key'] = $this->privateKey;
        $encodeStr = urldecode(http_build_query($encodeData));

        return strtolower(hash("sha256", $encodeStr));
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
            $encodeData[$index] = $this->trackingRequestData[$index];
        }

        //額外的加密設定
        ksort($encodeData);
        $encodeData['key'] = $this->privateKey;
        $encodeStr = urldecode(http_build_query($encodeData));

        return strtolower(hash("sha256", $encodeStr));
    }

    /**
     * 入款查詢時使用，用來分解訂單查詢(補單)時回傳的XML格式
     *
     * @param string $content xml格式的回傳值
     * @return array
     */
    private function parseData($content)
    {
        return $this->xmlToArray($content);
    }
}

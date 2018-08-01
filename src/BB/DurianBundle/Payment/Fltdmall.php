<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 豐達支付
 */
class Fltdmall extends PaymentBase
{
    /**
     * 支付提交參數
     *
     * @var array
     */
    protected $requestData = [
        'orderno' => '', // 訂單號
        'paytype' => '20', // 網銀支付類型：20
        'paycode' => '', // 銀行編碼
        'usercode' => '', // 商號
        'value' => '', // 金額，單位：元
        'notifyurl' => '', // 異步通知 URL
        'returnurl' => '', // 同步通知 URL
        'remark' => '', // 自定義返回參數，長度最長 50
        'datetime' => '', // 訂單時間(YmdHis)
        'goodsname' => '', // 商品名稱，不可為空，長度最長 25，顯示在後台，設定username方便業主比對
        'sign' => '', // 加密簽名
    ];

    /**
     * 支付提交參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'orderno' => 'orderId',
        'paycode' => 'paymentVendorId',
        'usercode' => 'number',
        'value' => 'amount',
        'notifyurl' => 'notify_url',
        'returnurl' => 'notify_url',
        'datetime' => 'orderCreateDate',
        'goodsname' => 'username',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'usercode',
        'orderno',
        'datetime',
        'paytype',
        'value',
        'notifyurl',
        'returnurl',
    ];

    /**
     * 返回驗證時需要加密的參數
     *     0: 有返回就要加密的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'result' => 1,
        'usercode' => 1,
        'plat_billid' => 1,
        'orderno' => 1,
        'paytype' => 1,
        'value' => 1,
    ];

    /**
     * 應答機制訊息
     *
     * @var string
     */
    protected $msg = 'ok';

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        1 => '001', // 工商銀行
        2 => '006', // 交通銀行
        3 => '005', // 農業銀行
        4 => '003', // 建設銀行
        5 => '002', // 招商銀行
        6 => '013', // 民生銀行總行
        8 => '007', // 上海浦東發展銀行
        9 => '045', // 北京銀行
        10 => '011', // 興業銀行
        11 => '009', // 中信銀行
        12 => '010', // 光大銀行
        13 => '014', // 華夏銀行
        14 => '008', // 廣東發展銀行
        15 => '012', // 平安銀行
        16 => '020', // 中國郵政
        17 => '004', // 中國銀行
        222 => '024', // 寧波銀行
        1090 => '30', // 微信支付_二維，銀行編號為空
        1092 => '22', // 支付寶_二維，銀行編號為空
        1103 => '23', // QQ_二維，銀行編號為空
        1104 => '33', // QQ_手機支付，銀行編號為空
        1107 => '31', // 京東錢包_二維，銀行編號為空
    ];

    /**
     * 查詢時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $trackingRequestData = [
        'usercode' => '', // 商號
        'orderno' => '', // 訂單號
        'datetime' => '', // 訂單時間(YmdHis)
        'sign' => '', // 加密簽名
    ];

    /**
     * 查詢時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $trackingRequireMap = [
        'usercode' => 'number',
        'orderno' => 'orderId',
        'datetime' => 'orderCreateDate',
    ];

    /**
     * 查詢時需要加密的參數
     *
     * @var array
     */
    protected $trackingEncodeParams = [
        'usercode',
        'orderno',
        'datetime',
    ];

    /**
     * 查詢解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $trackingDecodeParams = [
        'usercode' => 1,
        'orderno' => 1,
        'plat_billid' => 1,
        'paytype' => 1,
        'result' => 1,
        'value' => 1,
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

        // 驗證支付參數
        $this->payVerify();

        // 從內部設值到支付參數
        foreach ($this->requireMap as $paymentKey => $internalKey) {
            $this->requestData[$paymentKey] = $this->options[$internalKey];
        }

        // 檢查銀行代碼是否支援
        if (!array_key_exists($this->requestData['paycode'], $this->bankMap)) {
            throw new PaymentException(
                'PaymentVendor is not supported by PaymentGateway',
                180066
            );
        }

        // 額外的參數設定
        $date = new \DateTime($this->requestData['datetime']);
        $this->requestData['datetime'] = $date->format('YmdHis');
        $this->requestData['value'] = sprintf('%.2f', $this->requestData['value']);
        $this->requestData['remark'] = sprintf(
            '%s_%s',
            $this->options['merchantId'],
            $this->options['domain']
        );

        $this->requestData['paycode'] = $this->bankMap[$this->requestData['paycode']];

        // 二維支付、手機支付，銀行編號為空(微信、支付寶、QQ錢包、京東錢包)
        if (in_array($this->options['paymentVendorId'], [1090, 1092, 1103, 1104, 1107])) {
            $this->requestData['paytype'] = $this->requestData['paycode'];
            $this->requestData['paycode'] = '';
        }

        // 設定加密簽名
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

        // 驗證返回參數
        $this->payResultVerify();

        // 組合參數驗證加密簽名
        $encodeStr ='';
        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options)) {
                $encodeStr .= $this->options[$paymentKey];
            }
        }
        $encodeStr .= $this->privateKey;

        // 如果沒有簽名也要丟例外(其他參數都在前面驗證過了，所以不須要驗證)
        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['sign'] !== md5($encodeStr)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['result'] !== '1') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['orderno'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['value'] != $entry['amount']) {
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

        // 額外的參數設定
        $createAt = new \Datetime($this->trackingRequestData['datetime']);
        $this->trackingRequestData['datetime'] = $createAt->format('YmdHis');

        // 設定加密簽名
        $this->trackingRequestData['sign'] = $this->trackingEncode();

        // 執行訂單查詢
        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'method' => 'POST',
            'uri' => '/api/pay/query/v2',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => http_build_query($this->trackingRequestData),
            'header' => [],
        ];

        // 取得訂單查詢結果
        $result = $this->curlRequest($curlParam);
        $parseData = $this->parseData($result);
        $this->trackingResultVerify($parseData);

        $encodeStr = '';

        // 組織加密串
        foreach (array_keys($this->trackingDecodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $parseData)) {
                $encodeStr .= $parseData[$paymentKey];
            }
        }
        $encodeStr .= $this->privateKey;

        // 沒有 sign 就要丟例外，其他的參數在組織加密串的時候驗證過了
        if (!isset($parseData['sign'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        if ($parseData['sign'] != md5($encodeStr)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($parseData['result'] !== '1') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($parseData['value'] != $this->options['amount']) {
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

        // 加密設定
        foreach ($this->encodeParams as $index) {
            $encodeData[$index] = $this->requestData[$index];
        }

        // 額外的加密設定
        $encodeData['key'] = $this->privateKey;
        $encodeStr = urldecode(http_build_query($encodeData));

        return md5($encodeStr);
    }

    /**
     * 訂單查詢時的加密
     *
     * @return string
     */
    protected function trackingEncode()
    {
        $encodeData = [];

        // 組織加密簽名
        foreach ($this->trackingEncodeParams as $index) {
            $encodeData[$index] = $this->trackingRequestData[$index];
        }

        $encodeData['key'] = $this->privateKey;
        $encodeStr = urldecode(http_build_query($encodeData));

        return md5($encodeStr);
    }

    /**
     * 解析訂單查詢結果
     *
     * @param string $content
     * @return array
     */
    private function parseData($content)
    {
        /**
         * 回傳範例：
         * usercode=1001|orderno=2005032001234|plat_billid=X201605112|paytype=22|
         * result=1|value=12.01|pay_message=test|sign=6f8fb4aeeafac5820979a86f0d2d1300
         */
        $parseData = [];

        // 移除換行
        $content = str_replace("\n", '', $content);
        parse_str(str_replace('|', '&', $content), $parseData);

        return $parseData;
    }
}

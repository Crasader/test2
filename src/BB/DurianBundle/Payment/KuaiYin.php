<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 快銀支付
 *
 * 支付驗證:
 * 1. 驗證不可為空的參數
 * 2. 設定參數
 * 3. 額外處理的參數
 * 4. 設定encodeStr(加密後的字串)
 *
 * 解密驗證:
 * 1. 驗證key
 * 2. 設定參數
 * 3. 驗證結果是否相符
 */
class KuaiYin extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'version'      => '1.0.0', //版本號。固定值1.0.0
        'bank_code'    => '', //銀行代碼
        'amount'       => '', //金額，精確到小數後兩位
        'merchant_id'  => '', //商號
        'order_time'   => '', //訂單時間(YmdHis)
        'order_id'     => '', //訂單號
        'cust_param'   => '', //附加訊息，可空，如不填不加入做簽名
        'merchant_url' => '', //支付成功返回url
        'sign_msg'     => '' //加密簽名
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'merchant_id' => 'number',
        'order_id' => 'orderId',
        'amount' => 'amount',
        'merchant_url' => 'notify_url',
        'order_time' => 'orderCreateDate',
        'bank_code' => 'paymentVendorId'
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'version',
        'bank_code',
        'amount',
        'merchant_id',
        'order_time',
        'order_id',
        'cust_param',
        'merchant_url',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'version' => 1, //版本號。固定值1.0.0
        'merchant_id' => 1, //商戶號
        'bank_order_id' => 1, //銀行訂單號
        'order_amount' => 0, //訂單金額
        'order_id' => 1, //商戶訂單號
        'kuaiyin_order_id' => 1, //快銀訂單號
        'order_time' => 1, //訂單提交時間
        'paid_amount' => 1, //實際支付金額
        'deal_id' => 1, //交易流水號
        'deal_time' => 1, //快銀交易處理時間
        'account_date' => 1, //訂單結帳日期
        'cust_param' => 0, //自定義參數
        'result' => 1, //支付結果，Y為成功，N為失敗
        'code' => 1 //返回錯誤代碼，成功為0
    ];

    /**
     * 應答機制訊息
     *
     * @var string
     */
    protected $msg = '0000|';

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        1   => 'ICBC', //工商銀行
        2   => 'BANKCOMM', //交通銀行
        3   => 'ABC', //農業銀行
        4   => 'CCB', //建設銀行
        5   => 'CMB', //招商銀行
        6   => 'CMBC', //民生銀行
        7   => 'BOSZ', //深圳發展銀行
        8   => 'SPDB', //上海浦東發展銀行
        9   => 'BOB', //北京銀行
        10  => 'CIB', //興業銀行
        11  => 'ECITIC', //中信銀行
        12  => 'CEB', //光大銀行
        13  => 'HXB', //華夏銀行
        14  => 'CGB', //廣東發產銀行
        15  => 'PINGAN', //平安銀行
        16  => 'PSBC', //中國郵政儲蓄銀行
        17  => 'BOC', //中國銀行
        19  => 'SHB', //上海銀行
        217 => 'CBHB', //渤海銀行
        220 => 'HCCB', //杭州銀行
        221 => 'CZB', //浙商銀行
        222 => 'NBCB', //寧波銀行
        223 => 'BEA', //東亞銀行
        226 => 'NJCB', //南京銀行
        234 => 'BJRCB', //北京農村商業銀行
        278 => 'UP' //銀聯在線
    ];

    /**
     * 查詢時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $trackingRequestData = [
        'mer_order_id' => '', //訂單號
        'merchant_id'  => '', //商號
        'sign_msg'     => '' //加密簽名
    ];

    /**
     * 查詢時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $trackingRequireMap = [
        'mer_order_id' => 'orderId',
        'merchant_id' => 'number'
    ];

    /**
     * 查詢時需要加密的參數
     *
     * @var array
     */
    protected $trackingEncodeParams = [
        'mer_order_id',
        'merchant_id'
    ];

    /**
     * 查詢解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $trackingDecodeParams = [
        'merchant_id' => 1, //商號
        'cust_id' => 1, //支付ID。0: 網銀支付
        'order_id' => 1, //訂單號
        'order_date' => 1, //訂單時間
        'order_amount' => 1, //訂單金額
        'paid_amount' => 1, //實際支付金額
        'is_refund' => 1, //是否已退款
        'result' => 1, //支付結果。Y: 支付成功，N: 支付失敗
        'code' => 1 //結果返回碼。0: 成功
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

        if (trim($this->options['merchantId']) === '') {
            throw new PaymentException('No pay parameter specified', 180145);
        }

        if (trim($this->options['domain']) === '') {
            throw new PaymentException('No pay parameter specified', 180145);
        }

        //帶入未支援的銀行就噴例外
        if (!array_key_exists($this->requestData['bank_code'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        //額外的參數設定
        $date = new \DateTime($this->requestData['order_time']);
        $this->requestData['order_time'] = $date->format('YmdHis');
        $this->requestData['bank_code'] = $this->bankMap[$this->requestData['bank_code']];
        $this->requestData['amount'] = sprintf('%.2f', $this->requestData['amount']);
        $this->requestData['cust_param'] = $this->options['merchantId'] . '_' . $this->options['domain'];

        //設定支付平台需要的加密串
        $this->requestData['sign_msg'] = $this->encode();

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
            //如果返回值為空則不納入加密
            if (!array_key_exists($paymentKey, $this->options) || $this->options[$paymentKey] === '') {
                continue;
            }

            $encodeData[$paymentKey] = $this->options[$paymentKey];
        }

        //針對$encodeData按字母做升序排列
        ksort($encodeData);

        //排序後依k1=v1&k2=v2&...&kN=vN之後加上key=$this->privateKey做urlencode再md5
        $encodeData['key'] = $this->privateKey;
        $encodeStr = urlencode(urldecode(http_build_query($encodeData)));

        //沒有signMsg就要丟例外，其他的參數在組織加密串的時候驗證過了
        if (!isset($this->options['signMsg'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if (strtoupper($this->options['signMsg']) !== strtoupper(md5($encodeStr))) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['result'] !== 'Y' || $this->options['code'] !== '0') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['order_id'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['paid_amount'] != $entry['amount']) {
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
        $this->trackingRequestData['sign_msg'] = $this->trackingEncode();

        $uri = sprintf(
            '/kuaiyinAPI/inquiryOrder/merchantOrderId/%s/%s/%s',
            $this->trackingRequestData['merchant_id'],
            $this->trackingRequestData['mer_order_id'],
            $this->trackingRequestData['sign_msg']
        );

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'method' => 'GET',
            'uri' => $uri,
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => http_build_query($this->trackingRequestData),
            'header' => []
        ];

        // 取得訂單查詢結果
        $result = $this->curlRequest($curlParam);
        $parseData = $this->parseData($result);

        // 沒有返回錯誤代碼
        if (!isset($parseData['code'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        // 訂單查詢時加密簽名驗證錯誤
        if ($parseData['code'] === '-4') {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        // 訂單查詢時提交的參數錯誤
        if ($parseData['code'] === '-3') {
            throw new PaymentConnectionException('Submit the parameter error', 180075, $this->getEntryId());
        }

        // 訂單查詢時，支付平台系統錯誤，需聯絡該支付平台客服
        if ($parseData['code'] === '-2') {
            throw new PaymentConnectionException(
                'System error, please try again later or contact customer service',
                180076,
                $this->getEntryId()
            );
        }

        // 訂單查詢時，支付平台連接錯誤，需聯絡該支付平台客服
        if ($parseData['code'] === '-1') {
            throw new PaymentConnectionException(
                'Connection error, please try again later or contact customer service',
                180077,
                $this->getEntryId()
            );
        }

        // 訂單查詢時，結果為訂單不存在
        if ($parseData['code'] === '100001') {
            throw new PaymentConnectionException('Order does not exist', 180060, $this->getEntryId());
        }

        // 訂單查詢時，結果為支付超時
        if ($parseData['code'] === '100002') {
            throw new PaymentConnectionException('Paid time out', 180079, $this->getEntryId());
        }

        // 訂單查詢時，結果為訂單未支付
        if ($parseData['code'] === '100003') {
            throw new PaymentConnectionException('Unpaid order', 180062, $this->getEntryId());
        }

        // 0為查詢成功，防止有非以上的錯誤碼，因此非0則為訂單查詢失敗
        if ($parseData['code'] !== '0') {
            throw new PaymentConnectionException('Payment tracking failed', 180081, $this->getEntryId());
        }

        $this->trackingResultVerify($parseData);
        $encodeData = [];

        // 組織加密簽名
        foreach (array_keys($this->trackingDecodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $parseData)) {
                $encodeData[$paymentKey] = $parseData[$paymentKey];
            }
        }

        // 針對$encodeData按字母做升序排列
        ksort($encodeData);

        // 排序後依k1=v1&k2=v2&...&kN=vN之後加上key=$this->privateKey做urlencode再md5
        $encodeData['key'] = $this->privateKey;
        $encodeStr = urlencode(urldecode(http_build_query($encodeData)));

        // result = Y為支付成功，不為Y則為支付失敗
        if ($parseData['result'] !== 'Y') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        // 如果沒有Sign丟例外
        if (!isset($parseData['signMsg'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        if (strtoupper($parseData['signMsg']) != strtoupper(md5($encodeStr))) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($parseData['paid_amount'] != round($this->options['amount'] * 100)) {
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
        $this->trackingRequestData['sign_msg'] = $this->trackingEncode();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $path = sprintf(
            '/kuaiyinAPI/inquiryOrder/merchantOrderId/%s/%s/%s?%s',
            $this->trackingRequestData['merchant_id'],
            $this->trackingRequestData['mer_order_id'],
            $this->trackingRequestData['sign_msg'],
            http_build_query($this->trackingRequestData)
        );

        $curlParam = [
            'verify_ip' => $this->options['verify_ip'],
            'path' => $path,
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

        // 檢查訂單查詢返回參數
        $parseData = $this->parseData($this->options['content']);

        // 沒有返回錯誤代碼
        if (!isset($parseData['code'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        // 訂單查詢時加密簽名驗證錯誤
        if ($parseData['code'] === '-4') {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        // 訂單查詢時提交的參數錯誤
        if ($parseData['code'] === '-3') {
            throw new PaymentConnectionException('Submit the parameter error', 180075, $this->getEntryId());
        }

        // 訂單查詢時，支付平台系統錯誤，需聯絡該支付平台客服
        if ($parseData['code'] === '-2') {
            throw new PaymentConnectionException(
                'System error, please try again later or contact customer service',
                180076,
                $this->getEntryId()
            );
        }

        // 訂單查詢時，支付平台連接錯誤，需聯絡該支付平台客服
        if ($parseData['code'] === '-1') {
            throw new PaymentConnectionException(
                'Connection error, please try again later or contact customer service',
                180077,
                $this->getEntryId()
            );
        }

        // 訂單查詢時，結果為訂單不存在
        if ($parseData['code'] === '100001') {
            throw new PaymentConnectionException('Order does not exist', 180060, $this->getEntryId());
        }

        // 訂單查詢時，結果為支付超時
        if ($parseData['code'] === '100002') {
            throw new PaymentConnectionException('Paid time out', 180079, $this->getEntryId());
        }

        // 訂單查詢時，結果為訂單未支付
        if ($parseData['code'] === '100003') {
            throw new PaymentConnectionException('Unpaid order', 180062, $this->getEntryId());
        }

        // 0為查詢成功，防止有非以上的錯誤碼，因此非0則為訂單查詢失敗
        if ($parseData['code'] !== '0') {
            throw new PaymentConnectionException('Payment tracking failed', 180081, $this->getEntryId());
        }

        $this->trackingResultVerify($parseData);
        $encodeData = [];

        // 組織加密簽名
        foreach (array_keys($this->trackingDecodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $parseData)) {
                $encodeData[$paymentKey] = $parseData[$paymentKey];
            }
        }

        // 針對$encodeData按字母做升序排列
        ksort($encodeData);

        // 排序後依k1=v1&k2=v2&...&kN=vN之後加上key=$this->privateKey做urlencode再md5
        $encodeData['key'] = $this->privateKey;
        $encodeStr = urlencode(urldecode(http_build_query($encodeData)));

        // result = Y為支付成功，不為Y則為支付失敗
        if ($parseData['result'] !== 'Y') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        // 如果沒有Sign丟例外
        if (!isset($parseData['signMsg'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        if (strtoupper($parseData['signMsg']) != strtoupper(md5($encodeStr))) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($parseData['paid_amount'] != round($this->options['amount'] * 100)) {
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

        //組織加密簽名，排除sign_msg(加密簽名)，其他參數都要納入加密
        foreach ($this->encodeParams as $index) {
            $encodeData[$index] = $this->requestData[$index];
        }

        //針對$encodeData按字母做升序排列
        ksort($encodeData);

        //排序後依k1=v1&k2=v2&...&kN=vN之後加上key=$this->privateKey做urlencode再md5
        $encodeData['key'] = $this->privateKey;
        $encodeStr = urlencode(urldecode(http_build_query($encodeData)));

        return strtoupper(md5($encodeStr));
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

        //針對$encodeData按字母做升序排列
        ksort($encodeData);

        //排序後依k1=v1&k2=v2&...&kN=vN之後加上key=$this->privateKey做urlencode再md5
        $encodeData['key'] = $this->privateKey;
        $encodeStr = urlencode(urldecode(http_build_query($encodeData)));

        return strtoupper(md5($encodeStr));
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

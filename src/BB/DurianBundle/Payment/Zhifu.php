<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 商物通
 */
class Zhifu extends PaymentBase
{
    /**
     * 支付提交參數
     *
     * @var array
     */
    protected $requestData = [
        'merchantId' => '', // 商號
        'prodCode' => '', // 商戶購買產品號
        'orderId' => '', // 訂單號
        'orderAmount' => '', // 金額，單位：分
        'orderDate' => '', // 交易日期(格式：YmdHis)
        'prdOrdType' => '0', // 訂單類型，0：消費，3：商户充值
        'retUrl' => '', // 異步通知URL
        'returnUrl' => '', // 同步通知URL
        'prdName' => '', // 商品名稱，不可為空，設定username方便業主比對
        'prdDesc' => '', // 商品描述，可為空
        'signType' => 'MD5', // 加密方式
        'signature' => '', // 簽名
    ];

    /**
     * 支付提交參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'merchantId' => 'number',
        'prodCode' => 'paymentVendorId',
        'orderId' => 'orderId',
        'orderAmount' => 'amount',
        'orderDate' => 'orderCreateDate',
        'retUrl' => 'notify_url',
        'returnUrl' => 'notify_url',
        'prdName' => 'username',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'merchantId',
        'prodCode',
        'orderId',
        'orderAmount',
        'orderDate',
        'prdOrdType',
        'retUrl',
        'returnUrl',
        'prdName',
        'prdDesc',
        'signType',
    ];

    /**
     * 手機支付提交參數轉換對應
     *
     * @var array
     */
    protected $wapRequestDataMap = [
        'orderId' => 'prdOrdNo',
        'orderAmount' => 'ordAmt',
        'orderDate' => 'orderTime',
        'retUrl' => 'notifyUrl',
        'returnUrl' => 'retUrl',
    ];

    /**
     * 手機支付時需要加密的參數
     *
     * @var array
     */
    protected $wapEncodeParams = [
        'prdOrdNo',
        'merchantId',
        'prodCode',
        'prdOrdType',
        'ordAmt',
        'orderTime',
        'signType',
    ];

    /**
     * 返回驗證時需要加密的參數
     *     0: 有返回就要加密的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'versionId' => 1,
        'merchantId' => 1,
        'orderId' => 1,
        'settleDate' => 1,
        'completeDate' => 1,
        'status' => 1,
        'notifyTyp' => 1,
        'payOrdNo' => 1,
        'orderAmt' => 1,
        'notifyUrl' => 1,
        'signType' => 1,
    ];

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        1 => 'CP00000015', // 工商銀行(收銀台)
        2 => 'CP00000015', // 交通銀行(收銀台)
        3 => 'CP00000015', // 農業銀行(收銀台)
        4 => 'CP00000015', // 建設銀行(收銀台)
        5 => 'CP00000015', // 招商銀行(收銀台)
        6 => 'CP00000015', // 民生銀行(收銀台)
        8 => 'CP00000015', // 上海浦東發展銀行(收銀台)
        9 => 'CP00000015', // 北京銀行(收銀台)
        10 => 'CP00000015', // 興業銀行(收銀台)
        11 => 'CP00000015', // 中信銀行(收銀台)
        12 => 'CP00000015', // 光大銀行(收銀台)
        13 => 'CP00000015', // 華夏銀行(收銀台)
        14 => 'CP00000015', // 廣發銀行(收銀台)
        15 => 'CP00000015', // 平安銀行(收銀台)
        16 => 'CP00000015', // 中國郵政(收銀台)
        17 => 'CP00000015', // 中國銀行(收銀台)
        19 => 'CP00000015', // 上海銀行(收銀台)
        217 => 'CP00000015', // 渤海銀行(收銀台)
        222 => 'CP00000015', // 寧波銀行(收銀台)
        223 => 'CP00000015', // 東亞銀行(收銀台)
        226 => 'CP00000015', // 南京銀行(收銀台)
        228 => 'CP00000015', // 上海農商銀行(收銀台)
        278 => 'CP00000003', // 銀聯在線
        308 => 'CP00000015', // 徽商銀行(收銀台)
        312 => 'CP00000015', // 成都銀行(收銀台)
        1088 => 'CP00000003', // 銀聯在線手機支付
        1090 => 'CP00000013', // 微信_二維(民生接口)
        1092 => 'CP00000012', // 支付寶_二維
    ];

    /**
     * 查詢時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $trackingRequestData = [
        'merchantId' => '', // 商號
        'queryType' => '1', // 查詢方式，1：明細查詢
        'orderId' => '', // 訂單號
        'signType' => 'MD5', // 加密方式
        'signature' => '', // 簽名
    ];

    /**
     * 查詢時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $trackingRequireMap = [
        'merchantId' => 'number',
        'orderId' => 'orderId',
    ];

    /**
     * 查詢時需要加密的參數
     *
     * @var array
     */
    protected $trackingEncodeParams = [
        'merchantId',
        'queryType',
        'orderId',
        'signType',
    ];

    /**
     * 查詢解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $trackingDecodeParams = [
        '@retCode' => 1,
        '@retMsg' => 1,
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
        if (!array_key_exists($this->requestData['prodCode'], $this->bankMap)) {
            throw new PaymentException(
                'PaymentVendor is not supported by PaymentGateway',
                180066
            );
        }

        // 額外的參數設定
        $this->requestData['prodCode'] = $this->bankMap[$this->requestData['prodCode']];
        $this->requestData['orderAmount'] = round($this->requestData['orderAmount'] * 100);

        $createAt = new \Datetime($this->requestData['orderDate']);
        $this->requestData['orderDate'] = $createAt->format('YmdHis');

        $this->requestData['prdName'] = strtoupper(bin2hex($this->requestData['prdName']));

        // 設定加密簽名
        $this->requestData['signature'] = $this->encode();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'method' => 'POST',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => http_build_query($this->requestData),
            'header' => [],
        ];

        // 二維支付(微信、支付寶)
        if (in_array($this->options['paymentVendorId'], [1090, 1092])) {
            $curlParam['uri'] = '/user/MerchantmerchantWechatPay.do';

            $result = $this->curlRequest($curlParam);
            $parseData = $this->xmlToArray($result);

            if (!isset($parseData['@retCode']) || !isset($parseData['@retMsg'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            if ($parseData['@retCode'] != '0001') {
                throw new PaymentConnectionException($parseData['@retMsg'], 180130, $this->getEntryId());
            }

            if (!isset($parseData['qrURL'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            unset($this->requestData);

            $this->requestData['act_url'] = $parseData['qrURL'];
        }

        // 手機支付(銀聯在線)
        if ($this->options['paymentVendorId'] == '1088') {
            // 修改支付參數名稱
            foreach ($this->wapRequestDataMap as $oldKey => $newKey) {
                $this->requestData[$newKey] = $this->requestData[$oldKey];
                unset($this->requestData[$oldKey]);
            }

            // 修改支付時需要加密的參數
            $this->encodeParams = $this->wapEncodeParams;

            // 設定加密簽名
            $this->requestData['signature'] = $this->encode();

            $curlParam['param'] = http_build_query($this->requestData);
            $curlParam['uri'] = '/RMobPay/820001.tran';

            $result = $this->curlRequest($curlParam);
            $parseData = json_decode($result, true);

            if (!isset($parseData['RSPCOD']) || !isset($parseData['RSPMSG'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            if ($parseData['RSPCOD'] != '00000') {
                throw new PaymentConnectionException($parseData['RSPMSG'], 180130, $this->getEntryId());
            }

            if (!isset($parseData['UPOPWAPPAYURL'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            unset($this->requestData);

            $this->requestData['act_url'] = $parseData['UPOPWAPPAYURL'];
        }

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
        $encodeData = [];
        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options)) {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        $encodeStr = urldecode(http_build_query($encodeData));
        $encodeStr .= $this->privateKey;

        // 沒有返回signature就要丟例外(其他參數都在前面驗證過了，所以不須要驗證)
        if (!isset($this->options['signature'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['signature'] != md5($encodeStr)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['status'] !== '1') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['orderId'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['orderAmt'] != round($entry['amount'] * 100)) {
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

        $this->trackingRequestData['signature'] = $this->trackingEncode();

        // 執行訂單查詢
        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'method' => 'POST',
            'uri' => '/user/MerchantmerchantTransQuery.do',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => http_build_query($this->trackingRequestData),
            'header' => [],
        ];

        $result = $this->curlRequest($curlParam);

        // 檢查訂單查詢返回參數
        $parseData = $this->xmlToArray($result);

        $this->trackingResultVerify($parseData);

        // 訂單查詢異常
        if ($parseData['@retCode'] != '0001') {
            throw new PaymentConnectionException($parseData['@retMsg'], 180123, $this->getEntryId());
        }

        $returnValues = [
            '@orderId' => 1,
            '@amount' => 1,
            '@status' => 1,
        ];

        foreach ($returnValues as $paymentKey => $require) {
            if ($require && !isset($parseData['order'][$paymentKey])) {
                throw new PaymentException('No tracking return parameter specified', 180139);
            }
        }

        // 支付失敗
        if ($parseData['order']['@status'] != '1') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($parseData['order']['@orderId'] != $this->options['orderId']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($parseData['order']['@amount'] != round($this->options['amount'] * 100)) {
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
        $encodeData = [];

        // 加密設定
        foreach ($this->trackingEncodeParams as $index) {
            $encodeData[$index] = $this->trackingRequestData[$index];
        }

        $encodeStr = urldecode(http_build_query($encodeData));
        $encodeStr .= $this->privateKey;

        return md5($encodeStr);
    }
}

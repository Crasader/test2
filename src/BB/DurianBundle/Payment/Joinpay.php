<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 匯聚支付
 */
class Joinpay extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'p1_MerchantNo' => '', // 商戶編號
        'p2_OrderNo' => '', // 訂單號
        'p3_Amount' => '', // 訂單金額，精確到分
        'p4_Cur' => '1', // 幣別，1:人民幣
        'p5_ProductName' => '', // 商品名稱(這邊塞username方便業主比對)
        'p6_Mp' => '', // 公用回傳參數，可空
        'p7_ReturnUrl' => '', // 商戶頁面通知地址
        'p8_NotifyUrl' => '', // 異步通知地址
        'p9_FrpCode' => '', // 銀行編碼
        'pa_OrderPeriod' => '0', // 訂單有效期(單位分鐘，0為不超時)
        'hmac' => '', // 簽名數據
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'p1_MerchantNo' => 'number',
        'p2_OrderNo' => 'orderId',
        'p3_Amount' => 'amount',
        'p5_ProductName' => 'username',
        'p7_ReturnUrl' => 'notify_url',
        'p8_NotifyUrl' => 'notify_url',
        'p9_FrpCode' => 'paymentVendorId',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'p1_MerchantNo',
        'p2_OrderNo',
        'p3_Amount',
        'p4_Cur',
        'p5_ProductName',
        'p6_Mp',
        'p7_ReturnUrl',
        'p8_NotifyUrl',
        'p9_FrpCode',
        'pa_OrderPeriod',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'r1_MerchantNo' => 1,
        'r2_OrderNo' => 1,
        'r3_Amount' => 1,
        'r4_Cur' => 1,
        'r5_Mp' => 1,
        'r6_Status' => 1,
        'r7_TrxNo' => 1,
        'r8_BankOrderNo' => 1,
        'r9_BankTrxNo' => 1,
        'ra_PayTime' => 1,
        'rb_DealTime' => 1,
        'rc_BankCode' => 1,
    ];

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        1 => 'ICBC_NET_B2C', // 中國工商銀行
        2 => 'BOCO_NET_B2C', // 交通銀行
        3 => 'ABC_NET_B2C', // 中國農業銀行
        4 => 'CCB_NET_B2C', // 建設銀行
        5 => 'CMBCHINA_NET_B2C', // 招商銀行
        6 => 'CMBC_NET_B2C', // 中國民生銀行
        7 => 'SDB_NET_B2C', // 深圳發展銀行
        8 => 'SPDB_NET_B2C', // 上海浦東發展銀行
        9 => 'BCCB_NET_B2C', // 北京銀行
        10 => 'CIB_NET_B2C', // 興業銀行
        11 => 'ECITIC_NET_B2C', // 中信銀行
        12 => 'CEB_NET_B2C', // 中國光大銀行
        13 => 'HXB_NET_B2C', // 華夏銀行
        14 => 'CGB_NET_B2C', // 廣東發展銀行
        15 => 'PINGANBANK_NET_B2C', // 深圳平安銀行
        16 => 'POST_NET_B2C', // 中國郵政
        17 => 'BOC_NET_B2C', // 中國銀行
        1090 => 'NET_NATIVE', // 微信二維
        1092 => 'NET_NATIVE', // 支付寶_二維
    ];

    /**
     * 對外到支付平台的掃碼提交網址
     *
     * @var array
     */
    protected $scanPostUrl = [
        '1090' => '/trade/weixinApi.action', // 微信
        '1092' => '/trade/alipayApi.action', // 支付寶
    ];

    /**
     * 查詢時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $trackingRequestData = [
        'p1_MerchantNo' => '', // 商戶號
        'p2_OrderNo' => '', // 訂單號
        'hmac' => '', // 簽名數據
    ];

    /**
     * 查詢時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $trackingRequireMap = [
        'p1_MerchantNo' => 'number',
        'p2_OrderNo' => 'orderId',
    ];

    /**
     * 查詢時需要加密的參數
     *
     * @var array
     */
    protected $trackingEncodeParams = [
        'p1_MerchantNo',
        'p2_OrderNo',
    ];

    /**
     * 查詢解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $trackingDecodeParams = [
        'r1_MerchantNo' => 1,
        'r2_OrderNo' => 1,
        'r3_Amount' => 1,
        'r4_ProductName' => 1,
        'r5_TrxNo' => 0,
        'ra_Status' => 1,
        'rb_Code' => 1,
        'rc_CodeMsg' => 1,
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

        // 從內部給定值到參數
        foreach ($this->requireMap as $paymentKey => $internalKey) {
            $this->requestData[$paymentKey] = $this->options[$internalKey];
        }

        // 帶入未支援的銀行就噴例外
        if (!array_key_exists($this->requestData['p9_FrpCode'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $this->requestData['p9_FrpCode'] = $this->bankMap[$this->requestData['p9_FrpCode']];
        $this->requestData['p3_Amount'] = sprintf('%.2f', $this->requestData['p3_Amount']);

        // 二維支付(微信、支付寶)
        if (in_array($this->options['paymentVendorId'], [1090, 1092])) {
            // 修改需要提交和加密的參數
            unset($this->requestData['pa_OrderPeriod']);
            $encodeParamsKey = array_search('pa_OrderPeriod', $this->encodeParams);
            unset($this->encodeParams[$encodeParamsKey]);

            // 設定加密簽名
            $this->requestData['hmac'] = $this->encode();

            if (trim($this->options['verify_url']) == '') {
                throw new PaymentException('No verify_url specified', 180140);
            }

            $curlParam = [
                'method' => 'POST',
                'uri' => $this->scanPostUrl[$this->options['paymentVendorId']],
                'ip' => $this->options['verify_ip'],
                'host' => $this->options['verify_url'],
                'param' => urldecode(http_build_query($this->requestData)),
                'header' => []
            ];

            $result = $this->curlRequestWithoutValidStatusCode($curlParam);
            $parseData = json_decode($result, true);

            if (!$parseData) {
                $getData = [];
                preg_match('/<h1>(.*)<\/h1>/', $result, $getData);

                if (!isset($getData[1]) || $getData[1] == '') {
                    throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
                }
                throw new PaymentConnectionException($getData[1], 180130, $this->getEntryId());
            }

            if (!isset($parseData['ra_Status']) || !isset($parseData['rc_CodeMsg'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            if ($parseData['ra_Status'] != '100') {
                throw new PaymentConnectionException($parseData['rc_CodeMsg'], 180130, $this->getEntryId());
            }

            if (!isset($parseData['ra_code'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            $this->setQrcode($parseData['ra_code']);

            return [];
        }

        // 設定加密簽名
        $this->requestData['hmac'] = $this->encode();

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
        $encodeStr = '';

        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options)) {
                $encodeStr .= $this->options[$paymentKey];
            }
        }
        $encodeStr .= $this->privateKey;

        // 沒有返回hmac就要丟例外
        if (!isset($this->options['hmac'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if (strcasecmp($this->options['hmac'], md5(urldecode($encodeStr))) != 0) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['r6_Status'] != '100') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['r2_OrderNo'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['r3_Amount'] != $entry['amount']) {
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

        // 設定加密簽名
        $this->trackingRequestData['hmac'] = $this->trackingEncode();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'method' => 'POST',
            'uri' => '/trade/queryOrder.action',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => http_build_query($this->trackingRequestData),
            'header' => [],
        ];

        $result = $this->curlRequest($curlParam);
        $data = json_decode($result, true);

        $this->trackingResultVerify($data);

        // 組織加密串
        $encodeStr = '';

        foreach (array_keys($this->trackingDecodeParams) as $index) {
            if (!array_key_exists($index, $data)) {
                continue;
            }
            if ($index == 'r3_Amount') {
                $data['r3_Amount'] = sprintf('%.2f', $data['r3_Amount']);
            }
            $encodeStr .= $data[$index];
        }
        $encodeStr .= $this->privateKey;

        // 沒有hmac就要丟例外，其他的參數在組織加密串的時候驗證過了
        if (!isset($data['hmac'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        if (strcasecmp($data['hmac'], md5($encodeStr)) != 0) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        // 訂單未支付
        if ($data['ra_Status'] == '102') {
            throw new PaymentConnectionException('Unpaid order', 180062, $this->getEntryId());
        }

        if ($data['ra_Status'] != '100') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['amount'] != $data['r3_Amount']) {
            throw new PaymentException('Order Amount error', 180058);
        }
    }
}

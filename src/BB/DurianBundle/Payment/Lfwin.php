<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 樂付支付
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
class Lfwin extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'money' => '', // 消費金額
        'm_paytype' => '', // 支付方式
        'mch_orderid' => '', // 訂單號
        'mid' => '', // 店員序號
        'lfkey' => '', // 收款key
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'money' => 'amount',
        'mch_orderid' => 'orderId',
        'lfkey' => 'number',
        'm_paytype' => 'paymentVendorId',
    ];

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        1090 => 'wxpay', // 微信_二維
        1092 => 'alipay', // 支付寶_二維
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'paystatus' => 1, // 付款狀態
        'mch_orderid' => 1, // 訂單號
        'paymoney' => 1, // 付款金額
        'orderid' => 1, // 支付平台訂單號
    ];

    /**
     * 應答機制訊息
     *
     * @var string
     */
    protected $msg = 'SUCCESS';

    /**
     * 查詢時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $trackingRequestData = [
        'orderid' => '', // 支付平台訂單號
        'lfkey' => '', // 收款key
    ];

    /**
     * 查詢時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $trackingRequireMap = [
        'orderid' => 'ref_id',
        'lfkey' => 'number',
    ];

    /**
     * 查詢解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $trackingDecodeParams = [
        'paystatus' => 1, // 付款狀態
        'mch_orderid' => 1, // 訂單號
        'paymoney' => 1, // 付款金額
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
        if (!array_key_exists($this->requestData['m_paytype'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 商家額外的參數設定
        $this->requestData['m_paytype'] = $this->bankMap[$this->requestData['m_paytype']];

        $extra = $this->getMerchantExtraValue(['ClerkID']);
        $this->requestData['mid'] = $extra['ClerkID'];

        // 設定支付平台需要的加密串
        $data = $this->encode();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'method' => 'POST',
            'uri' => '/PayApi/OfflinePay/qrcode',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => $data,
            'header' => ['lfkey' => $this->requestData['lfkey']]
        ];

        $result = $this->curlRequest($curlParam);
        $decodeStr = $this->decrypt($this->privateKey, $result);
        $parseData = json_decode($decodeStr, true);

        if (!isset($parseData['status']) || !isset($parseData['info'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        if ($parseData['status'] != '1') {
            throw new PaymentConnectionException($parseData['info'], 180130, $this->getEntryId());
        }

        if (!isset($parseData['data']['qr_code'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        $this->setQrcode($parseData['data']['qr_code']);

        if (!isset($parseData['data']['orderid'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        // 紀錄入款明細的支付平台參考編號
        if ($this->getPayway() == self::PAYWAY_CASH) {
            $this->setCashDepositEntryRefId($parseData['data']['orderid']);
        }

        return [];
    }

    /**
     * 驗證線上支付是否成功
     *
     * @param array $entry
     */
    public function verifyOrderPayment($entry)
    {
        // 驗證返回參數
        $this->payResultVerify();

        if ($this->options['paystatus'] != '1') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['mch_orderid'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['paymoney'] != $entry['amount']) {
            throw new PaymentException('Order Amount error', 180058);
        }

        if ($this->options['orderid'] != $entry['ref_id']) {
            throw new PaymentException('Ref Id error', 150180176);
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

        $data = $this->trackingEncode();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'method' => 'POST',
            'uri' => '/PayApi/OfflinePay/getOrderDetails',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => $data,
            'header' => ['lfkey' => $this->trackingRequestData['lfkey']]
        ];

        // 取得訂單查詢結果
        $result = $this->curlRequest($curlParam);
        $decodeStr = $this->decrypt($this->privateKey, $result);
        $parseData = json_decode($decodeStr, true);

        // 檢查訂單查詢結果
        if (!isset($parseData['status']) || !isset($parseData['data'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        if ($parseData['status'] != '1') {
            throw new PaymentConnectionException('Payment tracking failed', 180081, $this->getEntryId());
        }

        $this->trackingResultVerify($parseData['data']);

        if ($parseData['data']['paystatus'] != '1') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($parseData['data']['mch_orderid'] != $this->options['orderId']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($parseData['data']['paymoney'] != $this->options['amount']) {
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

        // 組織加密簽名，排除lfkey(收款key), 其他的參數都要納入加密
        foreach ($this->requestData as $key => $value) {
            if ($key != 'lfkey') {
                $encodeData[$key] = $value;
            }
        }

        // 針對$encodeData按字母做升序排列
        ksort($encodeData);

        // 依key1=value1&key2=value2&...&keyN=valueN之後做DES加密
        $encodeStr = urldecode(http_build_query($encodeData));

        return $this->encrypt($this->privateKey, $encodeStr);
    }

    /**
     * 訂單查詢時的加密
     *
     * @return string
     */
    protected function trackingEncode()
    {
        $encodeData = [];

        // 組織加密簽名，排除lfkey(收款key), 其他的參數都要納入加密
        foreach ($this->trackingRequestData as $key => $value) {
            if ($key != 'lfkey') {
                $encodeData[$key] = $value;
            }
        }

        // 針對$encodeData按字母做升序排列
        ksort($encodeData);

        // 依key1=value1&key2=value2&...&keyN=valueN之後做DES加密
        $encodeStr = urldecode(http_build_query($encodeData));

        return $this->encrypt($this->privateKey, $encodeStr);
    }

    /**
     * curl request 解密
     *
     * @param string $request
     * @return string
     */
    protected function curlRequestDecode($request)
    {
        return $this->decrypt($this->privateKey, $request);
    }

    /**
     * curl response 解密
     *
     * @param string $response
     * @return string
     */
    protected function curlResponseDecode($response)
    {
        $decodeStr = $this->decrypt($this->privateKey, $response);
        return http_build_query(json_decode($decodeStr, true));
    }
}

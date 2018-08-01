<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 途貝支付
 */
class TuBeiPay extends PaymentBase
{
    /**
     * 生成預付單提交參數
     *
     * @var array
     */
    protected $requestData = [
        'mch_id' => '', // 商戶號
        'nonce_str' => '', // 隨機字串，不可為空，長度最長 32
        'body' => '', // 商品描述，不可為空，設定username方便業主比對
        'out_trade_no' => '', // 商戶訂單號
        'total_fee' => '', // 金額，單位：分
        'spbill_create_ip' => '', // 終端IP
        'notify_url' => '', // 通知地址
        'trade_type' => '', // 交易類型
        'sign' => '', // 加密簽名
    ];

    /**
     * 生成預付單提交參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'mch_id' => 'number',
        'body' => 'username',
        'out_trade_no' => 'orderId',
        'total_fee' => 'amount',
        'spbill_create_ip' => 'ip',
        'notify_url' => 'notify_url',
        'trade_type' => 'paymentVendorId',
    ];

    /**
     * 生成預付單時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'mch_id',
        'nonce_str',
        'body',
        'out_trade_no',
        'total_fee',
        'spbill_create_ip',
        'notify_url',
        'trade_type',
    ];

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        '1090' => 'trade.weixin.native', // 微信_二維
        '1097' => 'trade.weixin.h5pay', // 微信_手機支付
        '1103' => 'trade.qqpay.native', // QQ_二維
        '1104' => 'trade.qqpay.h5pay', // QQ_手機支付
    ];

    /**
     * 預付單解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $prepayDecodeParams = [
        'return_code' => 1,
        'return_msg' => 0,
        'mch_id' => 1,
        'result_code' => 1,
        'err_code' => 0,
        'err_code_des' => 0,
        'trade_type' => 1,
        'prepay_id' => 1,
        'prepay_url' => 0,
        'package_json' => 0,
    ];

    /**
     * 返回驗證時需要加密的參數
     *     0: 有返回就要加密的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'return_code' => 1,
        'mch_id' => 1,
        'nonce_str' => 1,
        'result_code' => 1,
        'trade_type' => 1,
        'bank_type' => 1,
        'total_fee' => 1,
        'cash_fee' => 1,
        'transaction_id' => 1,
        'third_trans_id' => 1,
        'out_trade_no' => 1,
        'time_end' => 1,
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

        // 從內部設值到支付參數
        foreach ($this->requireMap as $paymentKey => $internalKey) {
            $this->requestData[$paymentKey] = $this->options[$internalKey];
        }

        // 帶入未支援的銀行就噴例外
        if (!array_key_exists($this->requestData['trade_type'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $this->requestData['trade_type'] = $this->bankMap[$this->requestData['trade_type']];
        $this->requestData['total_fee'] = round($this->requestData['total_fee'] * 100);
        $this->requestData['nonce_str'] = md5(uniqid(rand(), true));

        // 設定加密簽名
        $this->requestData['sign'] = $this->encode();

        // 生成預付單
        $prepayData = $this->getPrepay();

        // 手機支付
        if (in_array($this->options['paymentVendorId'], ['1097', '1104'])) {
            return $this->getPhonePayData($prepayData);
        }

        // 二維支付
        $this->setQrcode($prepayData['prepay_url']);

        return [];
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

        // 先驗證平台回傳的必要參數
        if (!isset($this->options['content'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        // 解析xml驗證相關參數
        $this->options = $this->xmlToArray($this->options['content']);

        if (!isset($this->options['return_code'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['return_code'] !== 'SUCCESS' && isset($this->options['return_msg'])) {
            throw new PaymentConnectionException($this->options['return_msg'], 180130, $this->getEntryId());
        }

        if (!isset($this->options['result_code'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        // 正常情況為返回參數 return_code 和 result_code 都為 SUCCESS
        if ($this->options['return_code'] !== 'SUCCESS' || $this->options['result_code'] !== 'SUCCESS') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        // 驗證返回參數
        $this->payResultVerify();

        $encodeData = [];

        // 組織加密串，以支付平台返回為主
        foreach ($this->options as $paymentKey => $value) {
            // 除了 sign 欄位以外的非空值欄位皆須加密
            if ($paymentKey != 'sign' && $value !== '') {
                $encodeData[$paymentKey] = $value;
            }
        }
        ksort($encodeData);

        $encodeData['key'] = $this->privateKey;
        $encodeStr = urldecode(http_build_query($encodeData));

        // 如果沒有簽名也要丟例外(其他參數都在前面驗證過了，所以不須要驗證)
        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['sign'] !== strtoupper(md5($encodeStr))) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['out_trade_no'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['total_fee'] != round($entry['amount'] * 100)) {
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

        foreach ($this->encodeParams as $index) {
            if (isset($this->requestData[$index]) && $this->requestData[$index] !== '') {
                $encodeData[$index] = $this->requestData[$index];
            }
        }
        ksort($encodeData);

        $encodeData['key'] = $this->privateKey;
        $encodeStr = urldecode(http_build_query($encodeData));

        return strtoupper(md5($encodeStr));
    }

    /**
     * 生成預付單
     *
     * @return array
     */
    private function getPrepay()
    {
        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $paramXml = $this->arrayToXml($this->requestData, [], 'xml');
        $param = str_replace('<?xml version="1.0"?>', '', $paramXml);

        $curlParam = [
            'method' => 'POST',
            'uri' => '/v1/pay/unifiedorder',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => $param,
            'header' => ['Content-Type' => 'text/xml'],
        ];

        $result = $this->curlRequest($curlParam);

        // 檢查返回參數
        $parseData = $this->xmlToArray(urlencode($result));

        if (!isset($parseData['return_code'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        if ($parseData['return_code'] !== 'SUCCESS' && isset($parseData['return_msg'])) {
            throw new PaymentConnectionException($parseData['return_msg'], 180130, $this->getEntryId());
        }

        if (!isset($parseData['result_code'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        // 返回參數 return_code 和 result_code 都為SUCCESS時，才會返回提交支付時需要的 qrcode
        if ($parseData['return_code'] !== 'SUCCESS' || $parseData['result_code'] !== 'SUCCESS') {
            throw new PaymentConnectionException('Pay error', 180130, $this->getEntryId());
        }

        // 驗證預付單返回參數
        foreach ($this->prepayDecodeParams as $paymentKey => $require) {
            if ($require && !isset($parseData[$paymentKey])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }
        }

        $encodeData = [];

        // 組織加密串，以支付平台返回為主
        foreach ($parseData as $paymentKey => $value) {
            // 除了 sign 欄位以外的非空值欄位皆須加密
            if ($paymentKey != 'sign' && $value !== '') {
                $encodeData[$paymentKey] = $value;
            }
        }
        ksort($encodeData);

        $encodeData['key'] = $this->privateKey;
        $encodeStr = urldecode(http_build_query($encodeData));

        // 沒有sign就要丟例外
        if (!isset($parseData['sign'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        if ($parseData['sign'] !== strtoupper(md5($encodeStr))) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        return $parseData;
    }

    /**
     * 取得手機支付參數
     *
     * @param array $prepayData
     * @return array
     */
    private function getPhonePayData($prepayData)
    {
        $parseUrl = parse_url(urldecode($prepayData['prepay_url']));

        $parseUrlValues = [
            'scheme',
            'host',
            'path',
        ];

        foreach ($parseUrlValues as $key) {
            if (!isset($parseUrl[$key])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }
        }

        $param = [];

        if (isset($parseUrl['query'])) {
            parse_str($parseUrl['query'], $param);
        }

        $postUrl = sprintf(
            '%s://%s%s',
            $parseUrl['scheme'],
            $parseUrl['host'],
            $parseUrl['path']
        );

        // Form使用GET才能正常跳轉
        $this->payMethod = 'GET';

        return [
            'post_url' => $postUrl,
            'params' => $param,
        ];
    }
}

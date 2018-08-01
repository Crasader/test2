<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 雲盟支付
 */
class YunFuxPay extends PaymentBase
{
    /**
     * 支付提交參數
     *
     * @var array
     */
    protected $requestData = [
        'service' => '', // 接口類型
        'version' => '2.0', // 版本號，固定值
        'charset' => 'UTF-8', // 字符集，固定值
        'sign_type' => 'MD5', // 簽名方式，固定值
        'mch_id' => '', // 商戶號
        'out_trade_no' => '', // 商戶訂單號
        'body' => '', // 商品描述
        'total_fee' => '', // 金額，單位：分
        'mch_create_ip' => '', // 訂單生成機器 IP
        'notify_url' => '', // 異步通知網址
        'return_url' => '', // 同步通知網址，非必填
        'nonce_str' => '', // 隨機字串，長度最長 32
        'sign' => '', // 加密簽名
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'service' => 'paymentVendorId',
        'mch_id' => 'number',
        'out_trade_no' => 'orderId',
        'body' => 'username',
        'total_fee' => 'amount',
        'mch_create_ip' => 'ip',
        'notify_url' => 'notify_url',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'service',
        'version',
        'charset',
        'sign_type',
        'mch_id',
        'out_trade_no',
        'body',
        'total_fee',
        'mch_create_ip',
        'notify_url',
        'return_url',
        'nonce_str',
    ];

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        '1090' => 'pay.weixin.native', // 微信_二維
        '1092' => 'pay.alipay.native', // 支付寶_二維
        '1093' => 'pay.wyh5.wap', // 銀聯無卡手機支付
        '1102' => 'pay.wywg.wap', // 網銀收銀檯
        '1103' => 'pay.qq.native', // QQ_二維
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'version' => 0,
        'charset' => 0,
        'sign_type' => 0,
        'status' => 0,
        'message' => 0,
        'result_code' => 0,
        'mch_id' => 0,
        'device_info' => 0,
        'nonce_str' => 0,
        'err_code' => 0,
        'err_msg' => 0,
        'openid' => 0,
        'trade_type' => 0,
        'is_subscribe' => 0,
        'pay_result' => 0,
        'pay_info' => 0,
        'transaction_id' => 0,
        'out_transaction_id' => 0,
        'sub_is_subscribe' => 0,
        'sub_appid' => 0,
        'sub_openid' => 0,
        'out_trade_no' => 1,
        'total_fee' => 1,
        'coupon_fee' => 0,
        'fee_type' => 0,
        'attach' => 0,
        'bank_type' => 0,
        'bank_billno' => 0,
        'time_end' => 0,
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
        if (!array_key_exists($this->requestData['service'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $this->requestData['service'] = $this->bankMap[$this->requestData['service']];
        $this->requestData['total_fee'] = round($this->requestData['total_fee'] * 100);
        $this->requestData['nonce_str'] = md5(uniqid(rand(), true));

        // 二維支付
        if (in_array($this->options['paymentVendorId'], [1090, 1092, 1103])) {
            unset($this->requestData['return_url']);
        }

        // 設定加密簽名
        $this->requestData['sign'] = $this->encode();

        $paramXml = $this->arrayToXml($this->requestData, [], 'xml');
        $param = str_replace('<?xml version="1.0"?>', '', $paramXml);

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'method' => 'POST',
            'uri' => '/pay/gateway',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => $param,
            'header' => [],
        ];

        $result = $this->curlRequest($curlParam);
        $parseData = $this->xmlToArray($result);

        if (!isset($parseData['result_code'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        if ($parseData['result_code'] !== '0' && isset($parseData['err_msg'])) {
            throw new PaymentConnectionException($parseData['err_msg'], 180130, $this->getEntryId());
        }

        if (!isset($parseData['status'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        // 正常情況為返回參數 status 和 result_code 都為0
        if ($parseData['status'] !== '0' || $parseData['result_code'] !== '0') {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        // 二維支付
        if (in_array($this->options['paymentVendorId'], [1090, 1092, 1103])) {
            if (!isset($parseData['code_url'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            $this->setQrcode($parseData['code_url']);

            return [];
        }

        if (!isset($parseData['url'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        return ['act_url' => $parseData['url']];
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

        if (!isset($this->options['status'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['status'] !== '0' && isset($this->options['message'])) {
            throw new PaymentConnectionException($this->options['message'], 180130, $this->getEntryId());
        }

        if (!isset($this->options['result_code'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        // 正常情況為返回參數 status 和 result_code 都為0
        if ($this->options['status'] !== '0' || $this->options['result_code'] !== '0') {
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

        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if (strcasecmp($this->options['sign'], md5($encodeStr)) != 0) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['out_trade_no'] !== $entry['id']) {
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
}

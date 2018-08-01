<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 立付通
 */
class LiFuTong extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'service' => '', // 接口類型
        'version' => '2.0', // 版本號
        'charset' => 'UTF-8', // 字符集
        'sign_type' => 'MD5', // 簽名方式
        'mch_id' => '', // 商號
        'out_trade_no' => '', // 訂單號
        'body' => '', // 商品描述
        'total_fee' => '', // 金額，單位：分
        'mch_create_ip' => '', // 訂單生成機器 IP
        'notify_url' => '', // 異步通知網址，長度最長 255
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
        'nonce_str',
    ];

    /**
     * 返回驗證時需要加密的參數
     *     0: 有返回就要加密的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'service' => 1,
        'version' => 1,
        'charset' => 1,
        'sign_type' => 1,
        'status' => 1,
    ];

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        '1090' => 'pay.weixin.nativepay', // 微信二維
        '1092' => 'pay.alipay.nativepay', // 支付寶二維
        '1097' => 'pay.weixin.nativepay', // 微信_手機支付
        '1103' => 'pay.qq.nativepay', // QQ二維
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

        // 帶入未支援的支付方式就噴例外
        if (!array_key_exists($this->requestData['service'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $this->requestData['total_fee'] = round($this->requestData['total_fee'] * 100);
        $this->requestData['nonce_str'] = md5(uniqid(rand(), true));
        $this->requestData['service'] = $this->bankMap[$this->requestData['service']];

        // 設定加密簽名
        $this->requestData['sign'] = $this->encode();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $param = $this->arrayToXml($this->requestData, [], 'xml');

        $curlParam = [
            'method' => 'POST',
            'uri' => '/api/gateway.do',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => $param,
            'header' => [],
        ];

        $result = $this->curlRequest($curlParam);

        // 檢查返回參數
        $parseData = $this->xmlToArray($result);

        if (!isset($parseData['status'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        if ($parseData['status'] !== '0' && isset($parseData['message'])) {
            throw new PaymentConnectionException($parseData['message'], 180130, $this->getEntryId());
        }

        if (!isset($parseData['result_code'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        if ($parseData['result_code'] !== '0' && isset($parseData['err_msg'])) {
            throw new PaymentConnectionException($parseData['err_msg'], 180130, $this->getEntryId());
        }

        // 返回參數 status 和 result_code 都為0時，才會返回提交支付時需要的 pay_info
        if ($parseData['status'] !== '0' || $parseData['result_code'] !== '0') {
            throw new PaymentConnectionException('Pay error', 180130, $this->getEntryId());
        }

        if (!isset($parseData['pay_info'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        $payInfo = json_decode($parseData['pay_info'], true);

        if (!isset($payInfo['codeUrl'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        if ($this->options['paymentVendorId'] == 1097) {
            // 單引號會導致提交網址被截斷
            return [
                'post_url' => urlencode($payInfo['codeUrl']),
                'params' => [],
            ];
        }

        $this->setQrcode($payInfo['codeUrl']);

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

        // 如果沒有簽名也要丟例外(其他參數都在前面驗證過了，所以不須要驗證)
        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['sign'] !== strtoupper(md5($encodeStr))) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['pay_result'] !== '0') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['out_trade_no'] !== $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['total_fee'] !== trim(round($entry['amount'] * 100))) {
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
            $encodeData[$index] = $this->requestData[$index];
        }

        ksort($encodeData);

        $encodeData['key'] = $this->privateKey;

        $encodeStr = urldecode(http_build_query($encodeData));

        return strtoupper(md5($encodeStr));
    }
}

<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 隨行付
 */
class SuiXingPay extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'mercNo' => '', // 商戶編號
        'tranCd' => '1001', // 交易碼，支付:1001
        'version' => '1.0', // 版本號，網銀:1.0
        'reqData' => '', // 業務數據,
        'ip' => '', // 客户端ip
        'encodeType' => 'RSA#RSA', // 加密簽名方式，固定值
        'sign' => '', // 簽名
        'type' => '1', // 1:web
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'mercNo' => 'number',
        'ip' => 'ip',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'mercNo' => 1,
        'orderNo' => 1,
        'tranCd' => 1,
        'resCode' => 1,
        'resMsg' => 1,
        'resData' => 1,
    ];

    /**
     * 應答機制訊息
     *
     * @var string
     */
    protected $msg = 'SUCCESS';

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        '1' => 'ICBC', // 中國工商銀行
        '2' => 'BOCOM', // 交通銀行
        '3' => 'ABC', // 中國農業銀行
        '4' => 'CCB', // 中國建設銀行
        '5' => 'CMB', // 招商銀行
        '6' => 'CMBC', // 中國民生銀行
        '8' => 'SPDB', // 上海浦東發展銀行
        '9' => 'BOB', // 北京銀行
        '10' => 'CIB', // 興業銀行
        '11' => 'ECITIC', // 中信銀行
        '12' => 'CEB', // 中國光大銀行
        '14' => 'CGB', // 廣東發展銀行
        '15' => 'PAB', // 平安銀行
        '16' => 'PSBC', // 中國郵政
        '17' => 'BOC', // 中國銀行
    ];

    /**
     * 组合業務數據的参数
     *
     * @var array
     */
    protected $reqData = [
        'orderNo' => '', // 訂單編號
        'tranAmt' => '', // 交易金額，單位:元，精確到分
        'ccy' => 'CNY', // 交易幣別
        'pname' => '', // 商品名稱，帶入username
        'pnum' => '1', // 商品數量，固定帶1
        'pdesc' => '', // 商品描述，帶入username
        'retUrl' => '', // 同步轉跳網址
        'notifyUrl' => '', // 異步通知網址
        'bankWay' => '', // 銀行簡稱
        'payWay' => '2', // 2:直連
        'payChannel' => '1', // 支付渠道，1:網銀
    ];

    /**
     * 業務數據参数與內部參數的對應
     *
     * @var array
     */
    protected $reqDataMap = [
        'orderNo' => 'orderId',
        'tranAmt' => 'amount',
        'pname' => 'username',
        'pdesc' => 'username',
        'retUrl' => 'notify_url',
        'notifyUrl' => 'notify_url',
        'bankWay' => 'paymentVendorId',
    ];

    /**
     * 回傳支付時要給支付平台驗證的資料
     *
     * @return array
     */
    public function getVerifyData()
    {
        $this->payVerify();

        // 從內部給定值到提交參數
        foreach ($this->requireMap as $paymentKey => $internalKey) {
            $this->requestData[$paymentKey] = $this->options[$internalKey];
        }

        // 從內部給定值到業務參數
        foreach ($this->reqDataMap as $paymentKey => $internalKey) {
            $this->reqData[$paymentKey] = $this->options[$internalKey];
        }

        // 帶入未支援的支付方式就噴例外
        if (!array_key_exists($this->options['paymentVendorId'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定(組合業務數據的參數)
        $this->reqData['tranAmt'] = sprintf('%.2f', $this->reqData['tranAmt']);
        $this->reqData['bankWay'] = $this->bankMap[$this->reqData['bankWay']];

        // 生成業務數據
        $this->requestData['reqData'] = $this->reqDataEncode();

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
        $this->payResultVerify();

        $encodeData = [];

        // 組織加密串
        foreach (array_keys($this->decodeParams) as $paymentKey) {
            $encodeData[$paymentKey] = $this->options[$paymentKey];
        }

        $encodeStr = json_encode($encodeData, JSON_UNESCAPED_UNICODE);
        $encodeStr = stripslashes($encodeStr);

        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        $sign = base64_decode($this->options['sign']);

        if (!openssl_verify($encodeStr, $sign, $this->getRsaPublicKey())) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['resCode'] !== '000000') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['orderNo'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
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

        foreach ($this->requestData as $key => $value) {
            if ($key != 'encodeType' && $key != 'sign' && $key != 'type') {
                $encodeData[$key] = $value;
            }
        }

        $encodeStr = stripslashes(json_encode($encodeData));

        $sign = '';

        if (!openssl_sign($encodeStr, $sign, $this->getRsaPrivateKey(), OPENSSL_ALGO_SHA1)) {
            throw new PaymentException('Generate signature failure', 180144);
        }

        return base64_encode($sign);
    }

    /**
     * 加密業務數據
     *
     * @return string
     */
    protected function reqDataEncode()
    {
        $encodeData = [];

        foreach ($this->reqData as $key => $value) {
            $encodeData[$key] = $value;
        }

        $encodeData = json_encode($encodeData);

        openssl_public_encrypt(substr($encodeData, 0, 245), $encodeStr1, $this->getRsaPublicKey());
        openssl_public_encrypt(substr($encodeData, 245), $encodeStr2, $this->getRsaPublicKey());

        return base64_encode($encodeStr1 . $encodeStr2);
    }
}

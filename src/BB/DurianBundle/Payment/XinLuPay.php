<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 新陸支付
 */
class XinLuPay extends PaymentBase
{
    /**
     * 支付提交參數
     *
     * @var array
     */
    protected $requestData = [
        'userId' => '', // 商號
        'orderNo' => '', // 訂單號
        'tradeType' => '41', // 交易類型，網銀:41
        'payAmt' => '', // 支付金額，單位:元
        'bankId' => '', // 銀行id
        'goodsName' => '', // 商品名稱
        'goodsDesc' => '', // 商品描述
        'returnUrl' => '', // 同步通知地址
        'notifyUrl' => '', // 異步通知地址
        'sign' => '', // 簽名
    ];

    /**
     * 支付提交參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'userId' => 'number',
        'orderNo' => 'orderId',
        'payAmt' => 'amount',
        'bankId' => 'paymentVendorId',
        'goodsName' => 'orderId',
        'goodsDesc' => 'orderId',
        'returnUrl' => 'notify_url',
        'notifyUrl' => 'notify_url',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'userId',
        'orderNo',
        'tradeType',
        'payAmt',
        'returnUrl',
        'notifyUrl',
    ];

    /**
     * 返回驗證時需要加密的參數
     *     0: 有返回就要加密的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'retCode' => 1,
        'userId' => 1,
        'orderNo' => 1,
        'transNo' => 1,
        'payAmt' => 1,
    ];

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        '1' => '102', // 中國工商銀行
        '3' => '103', // 中國農業銀行
        '4' => '105', // 中國建設銀行
        '9' => '313', // 北京銀行
        '11' => '302', // 中信銀行
        '12' => '303', // 中國光大銀行
        '14' => '306', // 廣東發展銀行
        '16' => '403', // 中國郵政
        '17' => '104', // 中國銀行
        '221' => '316', // 浙商銀行
        '222' => '408', // 寧波銀行
        '278' => '61', // 銀聯在線
        '1088' => '51', // 銀聯在線_手機支付
        '1098' => '12', // 支付寶_手機支付
        '1103' => '21', // QQ_二維
        '1111' => '71', // 銀聯_二維
        '1114' => '81', // 一碼付
    ];

    /**
     * 回傳支付時要給支付平台驗證的資料
     *
     * @return array
     */
    public function getVerifyData()
    {
        $this->verifyPrivateKey();
        $this->payVerify();

        // 從內部設值到支付參數
        foreach ($this->requireMap as $paymentKey => $internalKey) {
            $this->requestData[$paymentKey] = $this->options[$internalKey];
        }

        // 檢查銀行代碼是否支援
        if (!array_key_exists($this->requestData['bankId'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $this->requestData['bankId'] = $this->bankMap[$this->requestData['bankId']];

        // 非網銀調整提交參數
        if (in_array($this->options['paymentVendorId'], ['278', '1088', '1098', '1103', '1111', '1114'])) {
            $this->requestData['tradeType'] = $this->requestData['bankId'];
            $this->requestData['bankId'] = '';
        }

        // 設定支付平台需要的加密串
        $this->requestData['sign'] = $this->encode();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'method' => 'POST',
            'uri' => '/orderpay.do',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => http_build_query($this->requestData),
            'header' => [],
        ];

        $result = $this->curlRequest($curlParam);
        $parseData = json_decode($result, true);

        if (!isset($parseData['retCode'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        if ($parseData['retCode'] !== 0 && isset($parseData['retMsg'])) {
            throw new PaymentConnectionException($parseData['retMsg'], 180130, $this->getEntryId());
        }

        if ($parseData['retCode'] !== 0) {
            throw new PaymentConnectionException('Pay error', 180130, $this->getEntryId());
        }

        if (!isset($parseData['payUrl']) || $parseData['payUrl'] == '') {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        // 二維、一碼付
        if (in_array($this->options['paymentVendorId'], ['1103', '1111', '1114'])) {
            $this->setQrcode($parseData['payUrl']);

            return [];
        }

        $getUrl = [];
        preg_match('/action="([^"]+)/', $parseData['payUrl'], $getUrl);

        if (!isset($getUrl[1])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        $out = [];
        if (!preg_match_all('/name="([^"]+)" value="([^"]*)"/U', $parseData['payUrl'], $out)) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        return [
            'post_url' => $getUrl[1],
            'params' => array_combine($out[1], $out[2]),
        ];
    }

    /**
     * 驗證線上支付是否成功
     *
     * @param array $entry
     */
    public function verifyOrderPayment($entry)
    {
        $this->verifyPrivateKey();
        $this->payResultVerify();

        // 組合參數驗證加密簽名
        $encodeData = [];

        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options)) {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        ksort($encodeData);
        $encodeData['key'] = $this->privateKey;

        $encodeStr = urldecode(http_build_query($encodeData));

        // 沒有返回sign就要丟例外
        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['sign'] !== md5($encodeStr)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['retCode'] !== '0') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['orderNo'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['payAmt'] != $entry['amount']) {
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
            if (array_key_exists($index, $this->requestData)) {
                $encodeData[$index] = $this->requestData[$index];
            }
        }

        ksort($encodeData);

        $encodeData['key'] = $this->privateKey;

        $encodeStr = urldecode(http_build_query($encodeData));

        return md5($encodeStr);
    }
}

<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * epay支付
 */
class EPay extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'command' => '', // 接口編碼
        'serverCode' => 'ser2001', // 服務編碼，固定值，ser2001:交易下單服務碼
        'merchNo' => '', // 商號
        'version' => '2.0', // 接口版本，固定值
        'charset' => 'utf-8', // 編碼格式，固定值
        'currency' => 'CNY', // 幣種，固定值
        'reqIp' => '', // 請求方對外IP
        'reqTime' => '', // 請求時間戳，格式:YmdHis
        'signType' => 'MD5', // 簽名算法類型
        'sign' => '', // 加密簽名值
        'payType' => '', // 支付類型
        'cOrderNo' => '', // 訂單號
        'amount' => '', // 交易金額，單位為元，整數
        'goodsName' => '', // 商品名稱
        'goodsNum' => '', // 商品數量
        'goodsDesc' => '', // 商品描述
        'memberId' => '', // 用戶ID
        'returnUrl' => '', // 返回網址，非必要參數
        'notifyUrl' => '', // 通知網址
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'merchNo' => 'number',
        'reqIp' => 'ip',
        'reqTime' => 'orderCreateDate',
        'payType' => 'paymentVendorId',
        'cOrderNo' => 'orderId',
        'amount' => 'amount',
        'goodsName' => 'orderId',
        'goodsNum' => 'orderId',
        'goodsDesc' => 'orderId',
        'memberId' => 'orderId',
        'returnUrl' => 'notify_url',
        'notifyUrl' => 'notify_url',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'code' => '1',
        'message' => '1',
        'merchNo' => '1',
        'amount' => '1',
        'cOrderNo' => '1',
        'pOrderNo' => '1',
        'status' => '1',
    ];

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        '278' => '7', // 銀聯在線(快捷)
        '1102' => '9', // 網銀收銀台
        '1111' => '8', // 銀聯_二維
    ];

    /**
     * 支付平台支援的銀行對應
     *
     * @var array
     */
    protected $commandMap = [
        '278' => 'cmd104', // 銀聯在線(快捷)
        '1102' => 'cmd105', // 網銀收銀台
        '1111' => 'cmd101', // 銀聯_二維
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

        // 從內部給定值到參數
        foreach ($this->requireMap as $paymentKey => $internalKey) {
            $this->requestData[$paymentKey] = $this->options[$internalKey];
        }

        // 帶入未支援的銀行就噴例外
        if (!array_key_exists($this->requestData['payType'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $date = new \DateTime($this->requestData['reqTime']);
        $this->requestData['reqTime'] = $date->format('YmdHis');
        $this->requestData['command'] = $this->commandMap[$this->requestData['payType']];
        $this->requestData['payType'] = $this->bankMap[$this->requestData['payType']];

        // 設定支付平台需要的加密串
        $this->requestData['sign'] = $this->encode();

        // 二維支付
        if ($this->options['paymentVendorId'] == '1111') {
            if (trim($this->options['verify_url']) == '') {
                throw new PaymentException('No verify_url specified', 180140);
            }

            $curlParam = [
                'method' => 'POST',
                'uri' => "/onlinepay/gateway/epayapi",
                'ip' => $this->options['verify_ip'],
                'host' => $this->options['verify_url'],
                'param' => http_build_query($this->requestData),
                'header' => [],
            ];

            $result = $this->curlRequest($curlParam);

            $parseData = json_decode($result, true);

            if (!isset($parseData['code']) || !isset($parseData['message'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            if ($parseData['code'] != '10000') {
                throw new PaymentConnectionException($parseData['message'], 180130, $this->getEntryId());
            }

            if (!isset($parseData['payUrl'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            $this->setQrcode($parseData['payUrl']);

            return [];
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
        $this->payResultVerify();

        $encodeData = [];

        // 組織加密串
        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options)) {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        ksort($encodeData);
        $encodeData['key'] = $this->privateKey;

        // 依key1=value1&key2=value2&...&keyN=valueN之後做md5
        $encodeStr = urldecode(http_build_query($encodeData));

        // 如果沒有返回簽名要丟例外
        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['sign'] != md5($encodeStr)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        // 10000:受理成功，4:支付成功
        if ($this->options['code'] != '10000' || $this->options['status'] != '4') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['cOrderNo'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['amount'] != $entry['amount']) {
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

        // 排除sign(加密參數)，其他非空的參數都要納入加密
        foreach ($this->requestData as $key => $value) {
            if ($key != 'sign' && trim($value) != '') {
                $encodeData[$key] = $value;
            }
        }

        ksort($encodeData);
        $encodeData['key'] = $this->privateKey;

        // key1=value1&key2=value2&...&keyN=valueN
        $encodeStr = urldecode(http_build_query($encodeData));

        // 第三方在解密驗證時會去掉.000與.0000
        $encodeStr = str_replace(".0000", "", $encodeStr);
        $encodeStr = str_replace(".000", "", $encodeStr);

        return md5($encodeStr);
    }
}

<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 商碼付
 */
class ShangMaFu extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證參數
     *
     * @var array
     */
    protected $requestData = [
        'mchNo' => '', // 商戶號
        'outTradeNo' => '', // 訂單號
        'amount' => '', // 交易金額，單位為分
        'body' => '', // 商品名稱，設定username方便業主比對
        'payDate' => '', // 訂單支付日期
        'notifyUrl' => '', // 異步通知網址
        'returnUrl' => '', // 同步通知網址
        'channel' => '1', // 支付渠道，固定值
        'bankType' => '11', // 銀行帳戶類型，11為借記卡
        'bankCode' => '', // 銀行卡類型
        'title' => '', // 訂單標題，二維參數，設定orderid方便業主比對
        'sign' => '', // 簽名
        'remark' => '', // 備註
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'mchNo' => 'number',
        'outTradeNo' => 'orderId',
        'amount' => 'amount',
        'body' => 'username',
        'payDate' => 'orderCreateDate',
        'notifyUrl' => 'notify_url',
        'returnUrl' => 'notify_url',
        'bankCode' => 'paymentVendorId',
        'title' => 'orderId',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'mchNo',
        'outTradeNo',
        'amount',
        'body',
        'payDate',
        'notifyUrl',
        'returnUrl',
        'channel',
        'bankType',
        'bankCode',
        'title',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0:可不返回的參數
     *     1:必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'amount' => 1,
        'outTradeNo' => 1,
        'remark' => 1,
        'resultCode' => 1,
        'resultMsg' => 1,
        'returnCode' => 1,
    ];

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        '1' => 'ICBC', // 工商銀行
        '2' => 'BOCO', // 交通銀行
        '3' => 'ABC', // 中國農業銀行
        '4' => 'CCB', // 中國建設銀行
        '5' => 'CMB', // 招商銀行
        '6' => 'CMBC', // 中國民生銀行
        '8' => 'SPDB', // 上海浦東發展銀行
        '9' => 'BCCB', // 北京銀行
        '10' => 'CIB', // 興業銀行
        '11' => 'CITIC', // 中信銀行
        '12' => 'CEB', // 光大銀行
        '13' => 'HXB', // 華夏銀行
        '14' => 'GDB', // 廣東發展銀行
        '15' => 'PINGAN', // 平安銀行
        '16' => 'PSBC', // 中國郵政
        '17' => 'BOC', // 中國銀行
        '19' => 'SHB', // 上海銀行
        '222' => 'NBB', // 寧波銀行
        '226' => 'NJB', // 南京銀行
        '234' => 'BJRCB', // 北京農村商業銀行
        '278' => 'YL', // 銀聯在線
        '1090' => '1', // 微信_二維
        '1092' => '2', // 支付寶_二維
        '1103' => '3', // QQ_二維
        '1107' => '4', // 京東_二維
        '1111' => '5', // 銀聯_二維
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

        foreach ($this->requireMap as $paymentKey => $internalKey) {
            $this->requestData[$paymentKey] = $this->options[$internalKey];
        }

        if (!array_key_exists($this->requestData['bankCode'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外參數設定
        $this->requestData['amount'] = strval(round($this->requestData['amount']) * 100);
        $createAt = new \Datetime($this->requestData['payDate']);
        $this->requestData['payDate'] = $createAt->format('Ymdhis');
        $this->requestData['bankCode'] = $this->bankMap[$this->requestData['bankCode']];

        // 二維支付
        if (in_array($this->options['paymentVendorId'], [1090, 1092, 1103, 1107, 1111])) {
            $this->requestData['channel'] = $this->requestData['bankCode'];
            unset($this->requestData['returnUrl']);
            unset($this->requestData['bankType']);
            unset($this->requestData['bankCode']);

            // 設定支付平台需要的加密串
            $this->requestData['sign'] = $this->encode();

            $curlParam = [
                'method' => 'POST',
                'uri' => '/merchantPay/scancodepay',
                'ip' => $this->options['verify_ip'],
                'host' => $this->options['verify_url'],
                'param' => urldecode(http_build_query($this->requestData)),
                'header' => [],
            ];

            $result = $this->curlRequest($curlParam);
            $parseData = json_decode($result, true);

            if (!isset($parseData['resultCode']) || !isset($parseData['resultMsg'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            if ($parseData['resultCode'] != '00') {
                throw new PaymentConnectionException($parseData['resultMsg'], 180130, $this->getEntryId());
            }

            if (!isset($parseData['qrcode'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            $this->setQrcode($parseData['qrcode']);

            return [];
        }

        // 移除非網銀參數
        unset($this->requestData['title']);

        // 設定支付平台需要的加密串
        $this->requestData['sign'] = $this->encode();

        return [
            'post_url' => $this->options['postUrl'],
            'params' => $this->requestData,
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

        $encodeData = [];

        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options)) {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        $encodeData['signKey'] = $this->privateKey;
        $encodeStr = urldecode(http_build_query($encodeData));

        // 沒有sign就要丟例外，其他的參數在組織加密串的時候驗證過了
        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        $sign = base64_decode($this->options['sign']);

        if (!openssl_verify(md5($encodeStr), $sign, $this->getRsaPublicKey(), OPENSSL_ALGO_SHA1)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['returnCode'] != 2) {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['outTradeNo'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['amount'] != round($entry['amount'] * 100)) {
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
            if (array_key_exists($index, $this->requestData) && $this->requestData[$index] != '') {
                $encodeData[$index] = $this->requestData[$index];
            }
        }

        ksort($encodeData);
        $encodeData['signKey'] = $this->privateKey;
        $encodeStr = urldecode(http_build_query($encodeData));

        $sign = '';
        if (!openssl_sign(md5($encodeStr), $sign, $this->getRsaPrivateKey(), OPENSSL_ALGO_SHA1)) {
            throw new PaymentException('Generate signature failure', 180144);
        }

        return base64_encode($sign);
    }
}

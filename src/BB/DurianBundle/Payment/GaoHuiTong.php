<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 高匯通支付
 */
class GaoHuiTong extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'service' => 'bankPay', // 接口名稱，網銀：bankPay
        'merchantNo' => '', // 商戶編號
        'bgUrl' => '', // 異步通知網址
        'version' => 'V2.0', // 網關版本，固定值
        'payChannelCode' => '', // 支付通道編碼
        'payChannelType' => '1', // 支付通道類型 1:儲蓄卡
        'orderNo' => '', // 商戶訂單號
        'orderAmount' => '', // 金額
        'curCode' => 'CNY', // 幣值，固定值
        'orderTime' => '', // 訂單時間 YmdHis
        'orderSource' => '1', // 訂單來源
        'signType' => '2', // 1:MD5 2:RSA
        'sign' => '', // 簽名
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'merchantNo' => 'number',
        'orderNo' => 'orderId',
        'orderAmount' => 'amount',
        'payChannelCode' => 'paymentVendorId',
        'orderTime' => 'orderCreateDate',
        'bgUrl' => 'notify_url',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'service',
        'merchantNo',
        'bgUrl',
        'version',
        'payChannelCode',
        'payChannelType',
        'orderNo',
        'orderAmount',
        'curCode',
        'orderTime',
        'orderSource',
        'signType',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'merchantNo' => 1,
        'orderNo' => 1,
        'cxOrderNo' => 1,
        'version' => 1,
        'payChannelCode' => 1,
        'productName' => 1,
        'orderAmount' => 1,
        'curCode' => 1,
        'orderTime' => 1,
        'dealTime' => 1,
        'fee' => 1,
        'dealCode' => 1,
        'dealMsg' => 1,
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
        1 => 'ICBC', // 工商銀行
        2 => 'BCOM', // 交通銀行
        3 => 'ABC', // 農業銀行
        4 => 'CCB', // 建設銀行
        5 => 'CMB', // 招商銀行
        6 => 'CMBC', // 民生銀行
        7 => 'SDB', // 深圳發展銀行
        8 => 'SPDB', // 上海浦東發展銀行
        9 => 'BOB', // 北京銀行
        10 => 'CIB', // 興業銀行
        11 => 'CITIC', // 中信銀行
        12 => 'CEB', // 光大銀行
        13 => 'HXB', // 華夏銀行
        14 => 'GDB', // 廣東發展銀行
        15 => 'PAB', // 平安銀行
        16 => 'PSBC', // 中國郵政
        17 => 'BOC', // 中國銀行
        19 => 'SHB', // 上海銀行
        217 => 'NBCB', // 渤海銀行
        219 => 'GZCB', // 廣州銀行
        220 => 'HZB', // 杭州銀行
        221 => 'CZB', // 浙商銀行
        222 => 'CBHB', // 寧波銀行
        223 => 'BEA', // 東亞銀行
        226 => 'NJCB', // 南京銀行
        228 => 'SRCB', // 上海市農村商業銀行
        234 => 'BJRCB', // 北京農商行
        278 => 'UPOP', // 銀聯在線
        307 => 'DLB', // 大連銀行
        308 => 'HSB', // 徽商銀行
        309 => 'JSB', // 江蘇銀行
        1111 => 'CX_DC', // 銀聯_二維
    ];

    /**
     * 出款時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $withdrawRequestData = [
        'service' => 'payForAnotherOne', // 接口名稱，固定值
        'merchantNo' => '', // 商號
        'orderNo' => '', // 訂單號
        'version' => 'V2.0', // 網關版本，固定值
        'accountProp' => '1', // 對公對私，1:私人　2:公司
        'accountNo' => '', // 銀行帳號
        'accountName' => '', // 帳戶名稱
        'bankGenneralName' => '', // 銀行名稱
        'bankName' => '', // 開戶行名稱(支行)
        'bankCode' => '', // 開戶行號
        'currency' => 'CNY', // 貨幣類型
        'bankProvcince' => '', // 開戶行所在省
        'bankCity' => '', // 開戶行所在城市
        'orderAmount' => '', // 金額，單位分
        'orderTime' => '', // 訂單時間，YmdHis
        'signType' => 'RSA', // 簽名類型，1:MD5 2:RSA
        'sign' => '', // 簽名
    ];

    /**
     * 出款時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $withdrawRequireMap = [
        'merchantNo' => 'number',
        'orderNo' => 'orderId',
        'orderAmount' => 'amount',
        'accountNo' => 'account',
        'accountName' => 'nameReal',
        'bankCode' => 'bank_info_id',
        'bankGenneralName' => 'bank_name',
        'bankName' => 'bank_name',
        'bankProvcince' => 'bank_name',
        'bankCity' => 'bank_name',
        'orderTime' => 'orderCreateDate',
    ];

    /**
     * 出款支援的銀行對應編號
     *
     * @var array
     */
    protected $withdrawBankMap = [
        '1' => 'ICBC', // 工商銀行
        '2' => 'BCOM', // 交通銀行
        '3' => 'ABC', // 農業銀行
        '4' => 'CCB', // 建設銀行
        '5' => 'CMB', // 招商銀行
        '6' => 'CMBC', // 民生銀行
        '7' => 'SDB', // 深圳發展銀行
        '8' => 'SPDB', // 上海浦東發展銀行
        '9' => 'BOB', // 北京銀行
        '10' => 'CIB', // 興業銀行
        '11' => 'CITIC', // 中信銀行
        '12' => 'CEB', // 光大銀行
        '13' => 'HXB', // 華夏銀行
        '14' => 'GDB', // 廣東發展銀行
        '15' => 'PAB', // 平安銀行
        '16' => 'PSBC', // 中國郵政
        '17' => 'BOC', // 中國銀行
        '19' => 'SHB', // 上海銀行
        '217' => 'NBCB', // 渤海銀行
        '219' => 'GZCB', // 廣州銀行
        '220' => 'HZB', // 杭州銀行
        '221' => 'CZB', // 浙商銀行
        '222' => 'CBHB', // 寧波銀行
        '223' => 'BEA', // 東亞銀行
        '226' => 'NJCB', // 南京銀行
        '228' => 'SRCB', // 上海市農村商業銀行
        '234' => 'BJRCB', // 北京農商行
        '278' => 'UPOP', // 銀聯在線
        '307' => 'DLB', // 大連銀行
        '308' => 'HSB', // 徽商銀行
        '309' => 'JSB', // 江蘇銀行
    ];

    /**
     * 出款時需要加密的參數
     *
     * @var array
     */
    protected $withdrawEncodeParams = [
        'service',
        'merchantNo',
        'orderNo',
        'version',
        'accountProp',
        'accountNo',
        'accountName',
        'bankGenneralName',
        'bankName',
        'bankCode',
        'currency',
        'bankProvcince',
        'bankCity',
        'orderAmount',
        'orderTime',
        'signType',
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

        // 帶入未支援的支付方式就噴例外
        if (!array_key_exists($this->options['paymentVendorId'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        $this->requestData['orderAmount'] = round($this->requestData['orderAmount'] * 100);
        $this->requestData['payChannelCode'] = $this->bankMap[$this->requestData['payChannelCode']];

        $date = new \DateTime($this->options['orderCreateDate']);
        $this->requestData['orderTime'] = $date->format('YmdHis');

        // 二維支付
        if ($this->options['paymentVendorId'] == 1111) {
            unset($this->requestData['payChannelType']);
            $this->requestData['service'] = 'getCodeUrl';
            $this->requestData['sign'] = $this->encode();

            if (trim($this->options['verify_url']) == '') {
                throw new PaymentException('No verify_url specified', 180140);
            }

            $curlParam = [
                'method' => 'POST',
                'uri' => '/PayApi/nativePay',
                'ip' => $this->options['verify_ip'],
                'host' => $this->options['verify_url'],
                'param' => http_build_query($this->requestData),
                'header' => [],
            ];

            $result = $this->curlRequest($curlParam);
            $parseData = json_decode($result, true);

            if (!isset($parseData['dealCode'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            if ($parseData['dealCode'] != '10000' && isset($parseData['dealMsg'])) {
                throw new PaymentConnectionException($parseData['dealMsg'], 180130, $this->getEntryId());
            }

            if ($parseData['dealCode'] != '10000' || !isset($parseData['codeUrl'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            $this->setQrcode($parseData['codeUrl']);

            return [];
        }

        $this->requestData['sign'] = urlencode($this->encode());

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

        ksort($encodeData);

        $encodeStr = urldecode(http_build_query($encodeData));

        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        $sign = base64_decode(urldecode($this->options['sign']));

        if (!openssl_verify($encodeStr, $sign, $this->getRsaPublicKey())) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['dealCode'] != '10000') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['orderNo'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['orderAmount'] != round($entry['amount'] * 100)) {
            throw new PaymentException('Order Amount error', 180058);
        }
    }

    /**
     * 線上出款
     */
    public function withdrawPayment()
    {
        $this->withdrawVerify();

        // 從內部給定值到參數
        foreach ($this->withdrawRequireMap as $paymentKey => $internalKey) {
            if (in_array($paymentKey, ['accountNo', 'accountName'])) {
                $this->withdrawRequestData[$paymentKey] = base64_encode($this->options[$internalKey]);

                continue;
            }

            $this->withdrawRequestData[$paymentKey] = $this->options[$internalKey];
        }

        if (trim($this->options['branch']) != '') {
            $this->withdrawRequestData['bankName'] = $this->options['branch'];
        }

        if (trim($this->options['province']) != '') {
            $this->withdrawRequestData['bankProvcince'] = $this->options['province'];
        }

        if (trim($this->options['city']) != '') {
            $this->withdrawRequestData['bankCity'] = $this->options['city'];
        }

        $this->withdrawRequestData['bankCode'] = $this->withdrawBankMap[$this->withdrawRequestData['bankCode']];
        $this->withdrawRequestData['orderAmount'] = round($this->withdrawRequestData['orderAmount'] * 100);
        $createAt = new \Datetime($this->withdrawRequestData['orderTime']);
        $this->withdrawRequestData['orderTime'] = $createAt->format('YmdHis');

        // 設定出款需要的加密串
        $this->withdrawRequestData['sign'] = $this->withdrawEncode();

        // 出款
        $curlParam = [
            'method' => 'POST',
            'uri' => '/PayApi/agentPay',
            'ip' => $this->options['verify_ip'],
            'host' => trim($this->options['withdraw_host']),
            'param' => http_build_query($this->withdrawRequestData),
            'header' => [],
            'timeout' => 60,
        ];

        $result = $this->curlRequest($curlParam);
        $parseData = json_decode($result, true);

        // 對返回結果做檢查
        if (!isset($parseData['dealCode']) || !isset($parseData['dealMsg'])) {
            throw new PaymentException('No withdraw return parameter specified', 150180209);
        }

        // 餘額不足
        if ($parseData['dealCode'] == '20003') {
            throw new PaymentException('Insufficient balance', 150180197);
        }

        if ($parseData['dealCode'] !== '10000') {
            throw new PaymentConnectionException($parseData['dealMsg'], 180124, $this->getEntryId());
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
            if (isset($this->requestData[$index])) {
                $encodeData[$index] = $this->requestData[$index];
            }
        }

        ksort($encodeData);

        $encodeStr = urldecode(http_build_query($encodeData));
        $sign = '';

        if (!openssl_sign($encodeStr, $sign, $this->getRsaPrivateKey())) {
            throw new PaymentException('Generate signature failure', 180144);
        }

        return urlencode(base64_encode($sign));
    }

    /**
     * 出款時的加密
     *
     * @return string
     */
    protected function withdrawEncode()
    {
        $encodeData = [];

        foreach ($this->withdrawEncodeParams as $index) {
            if (isset($this->withdrawRequestData[$index])) {
                $encodeData[$index] = $this->withdrawRequestData[$index];
            }
        }

        ksort($encodeData);

        $encodeStr = urldecode(http_build_query($encodeData));
        $sign = '';

        if (!openssl_sign($encodeStr, $sign, $this->getRsaPrivateKey())) {
            throw new PaymentException('Generate signature failure', 180144);
        }

        return urlencode(base64_encode($sign));
    }
}

<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 艾尚RSA
 */
class IShangRsa extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'version' => '1.0.0', // SDK版本號，固定值:1.0.0
        'tranType' => '40000', // 渠道類型，網銀:40000
        'platformNo' => '', // 機構平台編號
        'merNo' => '', // 商戶編號
        'signature' => '', // 簽名
        'service' => 'pay', // 服務類型，固定值:pay
        'orderAmount' => '', // 訂單金額，單位:分
        'subject' => '', // 訂單標題，帶入username
        'desc' => '', // 訂單描述，非必填
        'merOrderNo' => '', // 訂單號
        'bankType' => '', // 銀行代碼，網銀用
        'frontUrl' => '', // 同步通知網址
        'backUrl' => '', // 異步通知網址
        'tradeRate' => '', // 交易手續費率
        'drawFee' => '0', // 支付手續費，固定值:0
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'merNo' => 'number',
        'orderAmount' => 'amount',
        'subject' => 'username',
        'merOrderNo' => 'orderId',
        'bankType' => 'paymentVendorId',
        'frontUrl' => 'notify_url',
        'backUrl' => 'notify_url',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'version',
        'tranType',
        'platformNo',
        'merNo',
        'service',
        'orderAmount',
        'subject',
        'desc',
        'merOrderNo',
        'bankType',
        'frontUrl',
        'backUrl',
        'tradeRate',
        'drawFee',
        'ip',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'orderNo' => 1,
        'merOrderNo' => 1,
        'payAmount' => 1,
        'status' => 1,
        'reqTime' => 1,
        'payTime' => 1,
        'signType' => 1,
        'version' => 1,
    ];

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        '1' => '1001', // 中國工商銀行
        '2' => '1005', // 交通銀行
        '3' => '1002', // 中國農業銀行
        '4' => '1004', // 建設銀行
        '5' => '1012', // 招商銀行
        '6' => '1010', // 中國民生銀行
        '8' => '1014', // 上海浦東發展銀行
        '9' => '1016', // 北京銀行
        '10' => '1013', // 興業銀行
        '11' => '1007', // 中信銀行
        '12' => '1008', // 中國光大銀行
        '13' => '1009', // 華夏銀行
        '14' => '1017', // 廣東發展銀行
        '15' => '1011', // 平安銀行
        '16' => '1006', // 中國郵政儲蓄銀行
        '17' => '1003', // 中國銀行
        '19' => '1025', // 上海銀行
        '234' => '1103', // 北京農村商業銀行
        '1090' => '10000', // 微信_二維
        '1092' => '60000', // 支付寶_二維
        '1097' => '50000', // 微信_手機支付
        '1098' => '70000', // 支付寶_手機支付
        '1103' => '20000', // QQ_二維
        '1104' => '30000', // QQ_手機支付
        '1111' => '80000', // 銀聯二維
    ];

    /**
     * 回傳支付時要給支付平台驗證的資料
     *
     * @return array
     */
    public function getVerifyData()
    {
        $this->payVerify();

        // 從內部給定值到參數
        foreach ($this->requireMap as $paymentKey => $internalKey) {
            $this->requestData[$paymentKey] = $this->options[$internalKey];
        }

        // 帶入未支援的支付方式就噴例外
        if (!array_key_exists($this->requestData['bankType'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 商家額外的參數設定
        $names = ['platformNo', 'tradeRate'];
        $merchantExtraValues = $this->getMerchantExtraValue($names);

        // 額外的參數設定
        $this->requestData['orderAmount'] = round($this->requestData['orderAmount'] * 100);
        $this->requestData['bankType'] = $this->bankMap[$this->requestData['bankType']];
        $this->requestData['platformNo'] = $merchantExtraValues['platformNo'];
        $this->requestData['tradeRate'] = $merchantExtraValues['tradeRate'];

        // 二維支付、手機支付
        if (in_array($this->options['paymentVendorId'], [1090, 1092, 1097, 1098, 1103, 1104, 1111])) {
            // 調整額外參數設定
            $this->requestData['tranType'] = $this->requestData['bankType'];
            $this->requestData['ip'] = $this->options['ip'];
            unset($this->requestData['bankType']);
            unset($this->requestData['frontUrl']);

            // 設定支付平台需要的加密串
            $this->requestData['signature'] = $this->encode();

            if (trim($this->options['verify_url']) == '') {
                throw new PaymentException('No verify_url specified', 180140);
            }

            $curlParam = [
                'method' => 'POST',
                'uri' => '/trade/handle',
                'ip' => $this->options['verify_ip'],
                'host' => $this->options['verify_url'],
                'param' => http_build_query($this->requestData),
                'header' => [],
            ];

            $result = $this->curlRequest($curlParam);
            $parseData = json_decode($result, true);

            if (!isset($parseData['respCode']) || !isset($parseData['respMsg'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            if ($parseData['respCode'] != '10000') {
                throw new PaymentConnectionException($parseData['respMsg'], 180130, $this->getEntryId());
            }

            // 手機支付
            if (in_array($this->options['paymentVendorId'], [1097, 1098, 1104])) {
                if (!isset($parseData['payUrl'])) {
                    throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
                }

                return [
                    'post_url' => $parseData['payUrl'],
                    'params' => [],
                ];
            }

            if (!isset($parseData['qrCode'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            $this->setQrcode($parseData['qrCode']);

            return [];
        }

        // 設定支付平台需要的加密串
        $this->requestData['signature'] = $this->encode();

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
            if (array_key_exists($paymentKey, $this->options)) {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        ksort($encodeData);

        $encodeStr = urldecode(http_build_query($encodeData));

        if (!isset($this->options['signature'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        $sign = base64_decode($this->options['signature']);
        if (!openssl_verify($encodeStr, $sign, $this->getRsaPublicKey())) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['status'] != '1') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['merOrderNo'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['payAmount'] != round($entry['amount'] * 100)) {
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

        foreach ($this->encodeParams as $key) {
            if (isset($this->requestData[$key])) {
                $encodeData[$key] = $this->requestData[$key];
            }
        }

        ksort($encodeData);

        $encodeStr = urldecode(http_build_query($encodeData));

        $sign = '';
        if (!openssl_sign($encodeStr, $sign, $this->getRsaPrivateKey())) {
            throw new PaymentException('Generate signature failure', 180144);
        }

        return base64_encode($sign);
    }
}

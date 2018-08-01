<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 易云匯
 */
class YiYunHuei extends PaymentBase
{
    /**
     * 支付提交參數
     *
     * @var array
     */
    protected $requestData = [
        'service' => 'bankPay', // 接口名稱，網銀:bankPay
        'merchantNo' => '', // 商戶號
        'bgUrl' => '', // 異步通知網址
        'version' => 'V2.0', // 網關版本，固定值
        'payChannelCode' => '', // 支付通道編碼
        'payChannelType' => '1', // 支付通道類型，固定值，儲蓄卡:1
        'orderNo' => '', // 商戶訂單號
        'orderAmount' => '', // 訂單金額，單位:分
        'curCode' => 'CNY', // 交易幣種，固定值
        'orderTime' => '', // 訂單時間，格式YmdHis
        'orderSource' => '1', // 訂單來源，PC:1
        'signType' => '1', // 簽名類型，MD5:1
        'sign' => '', // 簽名
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'merchantNo' => 'number',
        'bgUrl' => 'notify_url',
        'payChannelCode' => 'paymentVendorId',
        'orderNo' => 'orderId',
        'orderAmount' => 'amount',
        'orderTime' => 'orderCreateDate',
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
        'ext1' => 0,
        'ext2' => 0,
        'fee' => 1,
        'dealCode' => 1,
        'dealMsg' => 1,
        'signType' => 0,
    ];

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        '1' => 'ICBC', // 工商銀行
        '2' => 'BCOM', // 交通銀行
        '3' => 'ABC', // 農業銀行
        '4' => 'CCB', // 建設銀行
        '5' => 'CMB', // 招商銀行
        '6' => 'CMBC', // 民生銀行
        '8' => 'SPDB', // 上海浦東發展銀行
        '9' => 'BOB', // 北京銀行
        '10' => 'CIB', // 興業銀行
        '11' => 'CITIC', // 中信銀行
        '12' => 'CEB', // 光大銀行
        '13' => 'HXB', // 華夏銀行
        '14' => 'GDB', // 廣發銀行
        '15' => 'PAB', // 平安銀行
        '16' => 'PSBC', // 中國郵政
        '17' => 'BOC', // 中國銀行
        '19' => 'SHB', // 上海銀行
        '217' => 'CBHB', // 渤海銀行
        '219' => 'GZCB', // 廣州銀行
        '220' => 'HZB', // 杭州銀行
        '221' => 'CZB', // 浙商銀行
        '222' => 'NBCB', // 寧波銀行
        '223' => 'BEA', // 東亞銀行
        '226' => 'NJCB', // 南京銀行
        '228' => 'SRCB', // 上海農村商業銀行
        '234' => 'BJRCB', // 北京農商行
        '278' => 'UPOP', // 銀聯在線
        '307' => 'DLB', // 大連銀行
        '308' => 'HSB', // 徽商銀行
        '309' => 'JSB', // 江蘇銀行
        '1090' => 'CX_WX', // 微信_二維
        '1092' => 'CX_ZFB', // 支付寶_二維
        '1103' => 'CX_QQ', // QQ_二維
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
        if (!array_key_exists($this->requestData['payChannelCode'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $this->requestData['payChannelCode'] = $this->bankMap[$this->requestData['payChannelCode']];
        $this->requestData['orderAmount'] = round($this->requestData['orderAmount'] * 100);
        $date = new \DateTime($this->requestData['orderTime']);
        $this->requestData['orderTime'] = $date->format('YmdHis');

        // 二維支付
        if (in_array($this->options['paymentVendorId'], [1090, 1092, 1103])) {
            $this->requestData['service'] = 'getCodeUrl';

            // 移除二維不需要的參數
            unset($this->requestData['payChannelType']);

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

            if ($parseData['dealCode'] !== '10000' && isset($parseData['dealMsg'])) {
                throw new PaymentConnectionException($parseData['dealMsg'], 180130, $this->getEntryId());
            }

            if ($parseData['dealCode'] !== '10000') {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            if (!isset($parseData['codeUrl'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            $this->setQrcode($parseData['codeUrl']);

            return [];
        }

        // 設定加密簽名
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

        $encodeStr = urldecode(http_build_query($encodeData));
        $encodeStr .= $this->privateKey;

        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['sign'] != strtoupper(md5($encodeStr))) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['dealCode'] !== '10000') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['orderNo'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['orderAmount'] != round($entry['amount'] * 100)) {
            throw new PaymentException('Order Amount error', 180058);
        }

        $returnMsg = [
            'merchantNo' => $this->options['merchantNo'],
            'dealResult' => 'SUCCESS',
            'signType' => '1',
        ];

        ksort($returnMsg);

        $encodeStr = urldecode(http_build_query($returnMsg));
        $encodeStr .= $this->privateKey;

        $returnMsg['sign'] = md5($encodeStr);

        $this->msg = json_encode($returnMsg);
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
        $encodeStr .= $this->privateKey;

        return md5($encodeStr);
    }
}

<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 四方支付
 */
class SiFangPay extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證參數
     *
     * @var array
     */
    protected $requestData = [
        'version' => 'V4.0', // 版本號，固定值
        'channel' => 'cardpay', // 支付渠道
        'transType' => '01', // 交易類型，固定值
        'merNo' => '', // 商戶號
        'orderDate' => '', // 商品訂單支付日期
        'orderNo' => '', // 商戶訂單號
        'returnUrl' => '', // 同步通知網址
        'notifyUrl' => '', // 異步通知網址
        'amount' => '', // 交易金額，單位為分
        'goodsInf' => '', // 商品名稱，設定username方便業主比對
        'bankCode' => '', // 銀行編號
        'payType' => '1', // 支付方式，1:借記卡
        'signature' => '', // 簽名
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'merNo' => 'number',
        'orderDate' => 'orderCreateDate',
        'orderNo' => 'orderId',
        'returnUrl' => 'notify_url',
        'notifyUrl' => 'notify_url',
        'amount' => 'amount',
        'goodsInf' => 'username',
        'bankCode' => 'paymentVendorId',
    ];

    /**
     * 支付機需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'amount',
        'channel',
        'goodsInf',
        'merNo',
        'notifyUrl',
        'orderDate',
        'orderNo',
        'returnUrl',
        'transType',
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
        'merNo' => 1,
        'notifyUrl' => 1,
        'orderDate' => 1,
        'orderNo' => 1,
        'payId' => 1,
        'respCode' => 1,
        'respDesc' => 1,
        'transType' => 1,
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
        '1' => '01020000', // 工商銀行
        '2' => '03010000', // 交通銀行
        '3' => '01030000', // 中國農業銀行
        '4' => '01050000', // 中國建設銀行
        '5' => '03080000', // 招商銀行
        '6' => '03050000', // 中國民生銀行
        '8' => '03100000', // 上海浦東發展銀行
        '9' => '04031000', // 北京銀行
        '10' => '03090000', // 興業銀行
        '11' => '03020000', // 中信銀行
        '12' => '03030000', // 光大銀行
        '13' => '03040000', // 華夏銀行
        '14' => '03060000', // 廣東發展銀行
        '15' => '03070000', // 平安銀行
        '16' => 'PSBC', // 中國郵政
        '17' => '01040000', // 中國銀行
        '19' => '65012900', // 上海銀行
        '220' => 'HZCB', // 杭州銀行
        '278' => 'quick', // 銀聯在線
        '1088' => 'quick', // 銀聯在線_手機支付
        '1090' => 'wx_qr', // 微信支付_二維
        '1098' => 'alipay_h5', // 支付寶_手機支付
        '1103' => 'qq_qr', // QQ_二維
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

        $this->requestData['bankCode'] = $this->bankMap[$this->requestData['bankCode']];
        $this->requestData['amount'] = strval(round(sprintf('%.2f', $this->requestData['amount']) * 100));
        $createAt = new \Datetime($this->requestData['orderDate']);
        $this->requestData['orderDate'] = $createAt->format('Ymd');

        if (in_array($this->options['paymentVendorId'], [278, 1088, 1098])) {
            $this->requestData['channel'] = $this->requestData['bankCode'];
            unset($this->requestData['bankCode']);
        }

        if (in_array($this->options['paymentVendorId'], [1090, 1103])) {
            $this->requestData['channel'] = $this->requestData['bankCode'];
            unset($this->requestData['bankCode']);

            $this->requestData['signature'] = $this->encode();

            if (trim($this->options['verify_url']) == '') {
                throw new PaymentException('No verify_url specified', 180140);
            }

            $parseData = [];

            $curlParam = [
                'method' => 'POST',
                'uri' => '/gate/pay/tran',
                'ip' => $this->options['verify_ip'],
                'host' => $this->options['verify_url'],
                'param' => urldecode(http_build_query($this->requestData)),
                'header' => [],
            ];

            $result = $this->curlRequest($curlParam);
            parse_str($result, $parseData);

            if (!isset($parseData['respCode']) || !isset($parseData['respDesc'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            if ($parseData['respCode'] != 'P000') {
                throw new PaymentConnectionException($parseData['respDesc'], 180130, $this->getEntryId());
            }

            if (!isset($parseData['codeUrl'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            $this->setQrcode($parseData['codeUrl']);

            return [];
        }

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
        $this->verifyPrivateKey();

        $this->payResultVerify();

        $encodeData = [];

        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options)) {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        $encodeStr = urldecode(http_build_query($encodeData));
        $encodeStr .= $this->privateKey;

        if (!isset($this->options['signature'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['signature'] != strtoupper(md5($encodeStr))) {
            throw new PaymentConnectionException('Signature verification failed', 180134, $this->getEntryId());
        }

        if ($this->options['respCode'] != '0000') {
            throw new PaymentConnectionException('Payment failure', 180135, $this->getEntryId());
        }

        if ($this->options['orderNo'] != $entry['id']) {
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
            if (isset($this->requestData[$index]) && $this->requestData[$index] != '') {
                $encodeData[$index] = $this->requestData[$index];
            }
        }

        ksort($encodeData);

        $encodeStr = urldecode(http_build_query($encodeData));
        $encodeStr .= $this->privateKey;

        return strtoupper(md5($encodeStr));
    }
}

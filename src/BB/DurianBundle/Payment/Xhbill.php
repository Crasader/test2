<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;

/**
 * 鑫寶通
 */
class Xhbill extends PaymentBase
{
    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        1 => [1, 2], // 工商銀行
        2 => [1, 3], // 交通銀行
        3 => [1, 4], // 農業銀行
        4 => [1, 5], // 建設銀行
        5 => [1, 6], // 招商銀行
        6 => [1, 7], // 民生銀行
        8 => [1, 8], // 上海浦東發展銀行
        9 => [1, 9], // 北京銀行
        10 => [1, 10], // 興業銀行
        11 => [1, 11], // 中信銀行
        12 => [1, 12], // 光大銀行
        13 => [1, 13], // 華夏銀行
        14 => [1, 14], // 廣發銀行
        15 => [1, 15], // 平安銀行
        16 => [1, 16], // 中國郵政
        17 => [1, 17], // 中國銀行
        19 => [1, 19], // 上海銀行
        220 => [1, 23], // 杭州銀行
        222 => [1, 25], // 寧波銀行
        278 => [1, 163], // 銀聯在線(快捷)
        1088 => 207, // 銀聯在線_手機支付(快捷)
        1090 => 100, // 微信_二維
        1092 => 101, // 支付寶_二維
        1097 => 200, // 微信_手機支付
        1098 => 201, // 支付寶_手機支付
        1102 => [1, 1], // 網銀_收銀台
        1103 => 103, // QQ_二維
        1104 => 203, // QQ_手機支付
        1107 => 102, // 京東_二維
        1111 => 105, // 銀聯_二維
    ];

    /**
     * 回傳支付時要給支付平台驗證的資料
     *
     * @return array
     */
    public function getVerifyData()
    {
        $this->setRequestData();

        // 帶入未支援的銀行就噴例外
        if (!array_key_exists($this->requestData['method_id'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        $this->requestData['method_id'] = $this->bankMap[$this->requestData['method_id']];

        // 如果是網銀，需要設定bank_id
        if (is_array($this->requestData['method_id'])) {
            $payType = $this->requestData['method_id'];
            $this->requestData['method_id'] = $payType[0];
            $this->requestData['bank_id'] = $payType[1];
        }

        // 網銀支付
        $url = sprintf("https://pay.%s/gateway?input_charset=UTF-8", $this->options['postUrl']);

        // 二維支付
        if (in_array($this->options['paymentVendorId'], [1090, 1092, 1103, 1107, 1111])) {
            $url = sprintf("https://api.%s/gateway/api/scanpay", $this->options['postUrl']);
        }

        // 手機支付
        if (in_array($this->options['paymentVendorId'], [1097, 1098, 1104])) {
            $url = sprintf("https://api.%s/gateway/api/h5apipay", $this->options['postUrl']);
        }

        $this->requestData['url'] = $url;

        return $this->getPaymentDepositParams();
    }

    /**
     * 驗證線上支付是否成功
     */
    public function verifyOrderPayment()
    {
        $this->paymentVerify();
    }
}

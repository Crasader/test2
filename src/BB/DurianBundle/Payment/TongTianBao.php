<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;

/**
 * 通天寶
 */
class TongTianBao extends PaymentBase
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
        14 => [1, 14], // 廣東發展銀行
        15 => [1, 15], // 平安銀行
        16 => [1, 16], // 中國郵政
        17 => [1, 17], // 中國銀行
        19 => [1, 19], // 上海銀行
        220 => [1, 23], // 杭州銀行
        221 => [1, 24], // 浙商銀行
        222 => [1, 25], // 寧波銀行
        226 => [1, 29], // 南京銀行
        234 => [1, 37], // 北京農商銀行
        308 => [1, 47], // 徽商銀行
        309 => [1, 48], // 江蘇銀行
        311 => [1, 50], // 恒豐銀行
        321 => [1, 58], // 天津銀行
        340 => [1, 77], // 盛京銀行
        1103 => 103, // QQ_二維
        1111 => 105, // 銀聯_二維
    ];

    /**
     * 支付平台支援的通道對應的接口名稱
     *
     * @var array
     */
    private $interfaceMap = [
        1103 => 'api_wx_pay_apply.cgi',
        1111 => 'api_yl_pay_apply.cgi',
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

        // 設定提交網址, 預設網銀
        $url = sprintf('http://cardpay.%s/cgi-bin/v2.0/api_cardpay_apply.cgi', $this->options['postUrl']);

        // 調整非網銀提交網址
        if (in_array($this->requestData['method_id'], [1103, 1111])) {
            $baseUrl = sprintf('http://upay.%s/cgi-bin/v2.0/', $this->options['postUrl']);

            $url = $baseUrl . $this->interfaceMap[$this->requestData['method_id']];
        }

        $this->requestData['url'] = $url;

        // durian、pink通道id轉換
        $this->requestData['method_id'] = $this->bankMap[$this->requestData['method_id']];

        // 如果是網銀，需要設定bank_id
        if (is_array($this->requestData['method_id'])) {
            $payType = $this->requestData['method_id'];
            $this->requestData['method_id'] = $payType[0];
            $this->requestData['bank_id'] = $payType[1];
        }

        return $this->getPaymentDepositParams();
    }

    /**
     * 驗證線上支付是否成功
     *
     *  @param array $entry
     */
    public function verifyOrderPayment($entry)
    {
        $this->verifyData['method_id'] = $this->bankMap[$entry['payment_vendor_id']];

        // 如果是網銀，需要設定bank_id
        if (is_array($this->verifyData['method_id'])) {
            $payType = $this->verifyData['method_id'];
            $this->verifyData['method_id'] = $payType[0];
            $this->verifyData['bank_id'] = $payType[1];
        }

        $this->paymentVerify();
    }
}

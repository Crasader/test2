<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;

/**
 * 米寶雲支付
 */
class MiBauYun extends PaymentBase
{
    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        1090 => 100, // 微信_二維
        1092 => 101, // 支付寶_二維
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

        return $this->getPaymentDepositParams();
    }

    /**
     * 驗證線上支付是否成功
     */
    public function verifyOrderPayment()
    {
        // 將返回的參數轉換編碼
        $detach = ['GB2312', 'UTF-8', 'GBK'];

        foreach ($this->verifyData['verify_data'] as $index => $data) {
            $charset = mb_detect_encoding($data, $detach);

            $this->verifyData['verify_data'][$index] = iconv($charset, 'UTF-8', $data);
        }
        $this->paymentVerify();
    }
}
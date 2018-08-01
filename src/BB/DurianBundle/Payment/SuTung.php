<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;

/**
 * 速通支付
 */
class SuTung extends PaymentBase
{
    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        1092 => 101, // 支付寶_二維
        1098 => 201, // 支付寶_手機支付
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

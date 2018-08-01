<?php

namespace BB\PineappleTradeBundle\Payment;

/**
 * 米寶雲支付
 */
class MiBauYun extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'parter' => '', // 商號
        'type' => '', // 銀行類型
        'value' => '', // 金額，單位:元，精確到小數點後2位
        'orderid' => '', // 訂單號
        'callbackurl' => '', // 異步通知網址
        'sign' => '', // 簽名
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'parter' => 'merchant_number',
        'type' => 'method_id',
        'value' => 'amount',
        'orderid' => 'order_number',
        'callbackurl' => 'notify_url',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'parter',
        'type',
        'value',
        'orderid',
        'callbackurl',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'orderid' => 1,
        'opstate' => 1,
        'ovalue' => 1,
    ];

    /**
     * 應答機制訊息
     *
     * @var string
     */
    protected $msg = 'opstate=0';

    /**
     * 支付平台支援的銀行
     *
     * @var array
     */
    protected $methodMap = [
        100 => '1004', // 微信_掃碼
        101 => '1003', // 支付寶_掃碼
    ];

    /**
     * 取得訂單號
     *
     * @param array $verifyData
     * @return array
     */
    public function getOrderNumber($verifyData)
    {
        return $verifyData['orderid'];
    }

    /**
     * 回傳支付時要給支付平台驗證的資料
     *
     * @return array
     */
    public function getDepositParams()
    {
        parent::getDepositParams();

        // 額外的參數設定
        $this->requestData['value'] = sprintf('%.2f', $this->requestData['value']);
        $this->requestData['type'] = $this->methodMap[$this->requestData['type']];

        // 設定支付平台需要的加密串
        $this->requestData['sign'] = $this->encode();

        return $this->requestData;
    }

    /**
     * 驗證線上支付是否成功
     */
    public function depositVerify()
    {
        parent::depositVerify();

        $verifyData = $this->options['verify_data'];
        $encodeData = [];

        // 組織加密串
        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $verifyData)) {
                $encodeData[$paymentKey] = $verifyData[$paymentKey];
            }
        }

        $encodeStr = urldecode(http_build_query($encodeData));
        $encodeStr .= $this->privateKey;

        // 若沒有返回簽名需丟例外
        if (!isset($verifyData['sign'])) {
            throw new \RuntimeException('No return parameter specified', 1509020001);
        }

        if ($verifyData['sign'] != md5($encodeStr)) {
            throw new \RuntimeException('Signature verification failed', 1509020011);
        }

        if ($verifyData['opstate'] !== '0') {
            throw new \RuntimeException('Payment failure', 1509020012);
        }

        if ($verifyData['orderid'] != $this->options['order_number']) {
            throw new \RuntimeException('Order Id error', 1509020013);
        }

        if ($verifyData['ovalue'] != $this->options['amount']) {
            throw new \RuntimeException('Order Amount error', 1509020014);
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
            $encodeData[$index] = $this->requestData[$index];
        }

        $encodeStr = urldecode(http_build_query($encodeData));
        $encodeStr .= $this->privateKey;

        return md5($encodeStr);
    }
}

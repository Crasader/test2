<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 小熊寶
 */
class XiaoXioBao extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'type' => 'form', // 接口調用方式，默認:form
        'merchantId' => '', // 商戶號
        'money' => '', // 金額，單位:元，精確到小數點後兩位
        'timestamp' => '', // 時間戳，精確到毫秒
        'notifyURL' => '', // 異步通知地址
        'returnURL' => '', // 同步通知地址，可空
        'merchantOrderId' => '', // 訂單號
        'sign' => '', // 簽名
        'paytype' => '', // 支付類型
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'merchantId' => 'number',
        'money' => 'amount',
        'timestamp' => 'orderCreateDate',
        'notifyURL' => 'notify_url',
        'returnURL' => 'notify_url',
        'merchantOrderId' => 'orderId',
        'paytype' => 'paymentVendorId',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'money',
        'merchantId',
        'notifyURL',
        'returnURL',
        'merchantOrderId',
        'timestamp',
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
        'merchantOrderNo' => 1,
        'money' => 1,
        'payAmount' => 1,
    ];

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        '1090' => 'WX', // 微信_二維
        '1092' => 'ALIPAY', // 支付寶_二維
        '1097' => 'WX', // 微信_手機支付
        '1098' => 'ALIPAY', // 支付寶_手機支付
        '1103' => 'QQ', // QQ_二維
        '1104' => 'QQ', // QQ_手機支付
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

        // 從內部給定值到參數
        foreach ($this->requireMap as $paymentKey => $internalKey) {
            $this->requestData[$paymentKey] = $this->options[$internalKey];
        }

        // 帶入未支援的銀行就噴例外
        if (!array_key_exists($this->requestData['paytype'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $this->requestData['money'] = sprintf('%.2f', $this->requestData['money']);
        $this->requestData['timestamp'] = (strtotime($this->requestData['timestamp'])) * 1000;
        $this->requestData['paytype'] = $this->bankMap[$this->requestData['paytype']];

        // 設定支付平台需要的加密串
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
        $this->verifyPrivateKey();
        $this->payResultVerify();

        $encodeData = [];

        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options)) {
                $encodeData[] = $this->options[$paymentKey];
            }
        }

        $encodeData[] = $this->privateKey;

        $encodeStr = implode('&', $encodeData);

        // 如果沒有簽名也要丟例外(其他參數都在前面驗證過了，所以不須要驗證)
        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['sign'] !== md5($encodeStr)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['merchantOrderNo'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['money'] != $entry['amount']) {
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

        // 加密設定
        foreach ($this->encodeParams as $index) {
            if (isset($this->requestData[$index])) {
                $encodeData[] = $this->requestData[$index];
            }
        }

        $encodeData[] = $this->privateKey;

        // 額外的加密設定
        $encodeStr = implode('&', $encodeData);

        return md5($encodeStr);
    }
}

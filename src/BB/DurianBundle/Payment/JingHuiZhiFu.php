<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 璟匯支付
 */
class JingHuiZhiFu extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'version' => '1.0', // 1.0
        'agentId' => '', // 商戶ID
        'agentOrderId' => '', // 訂單單號
        'payType' => '', // 支付方式
        'bankCode' => '', // 銀行編碼
        'payAmt' => '', // 訂單金額，保留小數點後兩位
        'orderTime' => '', // 訂單時間，YmdHis
        'notifyUrl' => '', // 異步通知地址
        'payIp' => '', // 請求IP
        'noticePage' => '', // 前台轉跳頁面GET方式，含異步通知參數，選填
        'remark' => '', // 備註，選填
        'sign' => '', // MD5簽名
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'agentId' => 'number',
        'agentOrderId' => 'orderId',
        'payType' => 'paymentVendorId',
        'payAmt' => 'amount',
        'orderTime' => 'orderCreateDate',
        'notifyUrl' => 'notify_url',
        'payIp' => 'ip',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'version',
        'agentId',
        'agentOrderId',
        'payType',
        'payAmt',
        'orderTime',
        'notifyUrl',
        'payIp',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'version' => 1,
        'agentId' => 1,
        'agentOrderId' => 1,
        'jnetOrderId' => 1,
        'payAmt' => 1,
        'payResult' => 1,
    ];

    /**
     * 應答機制訊息
     *
     * @var string
     */
    protected $msg = 'OK';

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        '1092' => 'P0003', // 支付寶二維
        '1097' => 'P0002', // 微信_手機支付
        '1098' => 'P0004', // 支付寶_手機支付
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

        // 從內部給定值到參數
        foreach ($this->requireMap as $paymentKey => $internalKey) {
            $this->requestData[$paymentKey] = $this->options[$internalKey];
        }

        // 帶入未支援的銀行就噴例外
        if (!array_key_exists($this->options['paymentVendorId'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $this->requestData['payAmt'] = sprintf('%.2f', $this->requestData['payAmt']);
        $this->requestData['orderTime'] = date('YmdHis', strtotime($this->requestData['orderTime']));
        $this->requestData['payType'] = $this->bankMap[$this->requestData['payType']];

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

        $encodeData['key'] = $this->privateKey;
        $encodeStr = implode('|', $encodeData);

        // 沒有sign就要丟例外，其他的參數在組織加密串的時候驗證過了
        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['sign'] != md5($encodeStr)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['payResult'] != 'SUCCESS') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['agentOrderId'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['payAmt'] != $entry['amount']) {
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
            $encodeData[$index] = $this->requestData[$index];
        }

        $encodeData['key'] = $this->privateKey;
        $encodeStr = implode('|', $encodeData);

        return md5($encodeStr);
    }
}

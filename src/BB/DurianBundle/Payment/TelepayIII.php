<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 神州付三代
 */
class TelepayIII extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'scode' => '', // 商家代號
        'orderid' => '', // 訂單號
        'paytype' => '', // 支付方式
        'amount' => '', // 支付金額，單位元
        'productname' => '', // 商品名稱，放入orderid
        'currcode' => 'CNY', // 支付幣別，固定值
        'userid' => '', // 用戶編號或帳號
        'memo' => '', // 備註，可空
        'noticeurl' => '', // 商家接收交易結果網址
        'sign' => '', // 簽名
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'scode' => 'number',
        'orderid' => 'orderId',
        'paytype' => 'paymentVendorId',
        'amount' => 'amount',
        'productname' => 'orderId',
        'userid' => 'orderId',
        'noticeurl' => 'notify_url',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'scode',
        'orderid',
        'amount',
        'currcode',
        'paytype',
        'noticeurl',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'scode' => 1,
        'orderno' => 1,
        'orderid' => 1,
        'amount' => 1,
        'paytype' => 1,
        'status' => 1,
        'respcode' => 1,
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
        '278' => 'unionpayq', // 銀聯在線(快捷)
        '1088' => 'unionpayq', // 銀聯在線_手機支付(快捷)
        '1103' => 'qqpay', // QQ_二维
        '1104' => 'qqpay', // QQ_手機支付
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
        if (!array_key_exists($this->requestData['paytype'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外參數設定
        $this->requestData['paytype'] = $this->bankMap[$this->requestData['paytype']];
        $this->requestData['amount'] = sprintf('%.2f', $this->requestData['amount']);
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

        // 如果沒有返回簽名檔要丟例外
        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        // 組合參數驗證加密簽名
        $encodeData = [];

        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options)) {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        $encodeStr = implode('&', $encodeData);
        $encodeStr .= ':' . $this->privateKey;

        if ($this->options['sign'] != md5($encodeStr)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['status'] !== '1') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['orderid'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['amount'] != $entry['amount']) {
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
            $encodeData[$key] = $this->requestData[$key];
        }

        $encodeStr = implode('&', $encodeData);
        $encodeStr .= ':' . $this->privateKey;

        return md5($encodeStr);
    }
}

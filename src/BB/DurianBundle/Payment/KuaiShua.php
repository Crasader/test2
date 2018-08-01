<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 快刷支付
 */
class KuaiShua extends PaymentBase
{
    /**
     * 支付提交參數
     *
     * @var array
     */
    protected $requestData = [
        'versionId' => '001', // 版本號，固定值
        'businessType' => '1100', // 交易類型，固定值
        'insCode' => '', // 機構號，非必填，網銀參數
        'merId' => '', // 商戶號
        'orderId' => '', // 訂單號
        'transDate' => '', // 交易日期 YmdHis
        'transAmount' => '', // 支付金額，單位：元
        'transCurrency' => '156', // 交易幣別，網銀參數
        'transChanlName' => '', // 交易渠道名稱
        'openBankName' => '', // 開戶銀行，非必填，網銀參數
        'pageNotifyUrl' => '', // 前台頁面通知地址，網銀參數
        'backNotifyUrl' => '', // 後台通知網址
        'orderDesc' => '', // 訂單描述，可空
        'dev' => '', // 商戶自定義域，可空
        'signData' => '', // 簽名數據
    ];

    /**
     * 支付提交參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'transChanlName' => 'paymentVendorId',
        'merId' => 'number',
        'orderId' => 'orderId',
        'transDate' => 'orderCreateDate',
        'transAmount' => 'amount',
        'pageNotifyUrl' => 'notify_url',
        'backNotifyUrl' => 'notify_url',
        'orderDesc' => 'orderId',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'versionId',
        'businessType',
        'insCode',
        'merId',
        'orderId',
        'transDate',
        'transAmount',
        'transCurrency',
        'transChanlName',
        'openBankName',
        'pageNotifyUrl',
        'backNotifyUrl',
        'orderDesc',
        'dev',
    ];

    /**
     * 返回驗證時需要加密的參數
     *     0: 有返回就要加密的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'versionId' => 1,
        'businessType' => 1,
        'insCode' => 1,
        'merId' => 1,
        'transDate' => 1,
        'transAmount' => 1,
        'transCurrency' => 1,
        'transChanlName' => 1,
        'openBankName' => 1,
        'orderId' => 1,
        'payStatus' => 1,
        'payMsg' => 1,
        'pageNotifyUrl' => 1,
        'backNotifyUrl' => 1,
        'orderDesc' => 1,
        'dev' => 1,
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
        278 => 'UNIONPAY', // 銀聯在線
        1088 => 'UNIONPAY', // 銀聯在線_手機支付
        1100 => '', // 手機收銀台
        1102 => '', // 收銀台
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

        // 驗證支付參數
        $this->payVerify();

        // 從內部設值到支付參數
        foreach ($this->requireMap as $paymentKey => $internalKey) {
            $this->requestData[$paymentKey] = $this->options[$internalKey];
        }

        // 檢查銀行代碼是否支援
        if (!array_key_exists($this->requestData['transChanlName'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $this->requestData['transChanlName'] = $this->bankMap[$this->requestData['transChanlName']];
        $this->requestData['transAmount'] = sprintf('%.2f', $this->requestData['transAmount']);
        $createAt = new \Datetime($this->requestData['transDate']);
        $this->requestData['transDate'] = $createAt->format('YmdHis');

        // 設定加密簽名
        $this->requestData['signData'] = $this->encode();

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

        // 驗證返回參數
        $this->payResultVerify();

        $encodeData = [];

        // 組織加密簽名
        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options)) {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        $encodeStr = urldecode(http_build_query($encodeData));
        $encodeStr .= $this->privateKey;

        // 沒有返回簽名就要丟例外
        if (!isset($this->options['signData'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['signData'] != strtoupper(md5($encodeStr))) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        // 檢查是否支付成功
        if ($this->options['payStatus'] !== '00') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['orderId'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['transAmount'] != $entry['amount']) {
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

        $encodeStr = urldecode(http_build_query($encodeData));
        $encodeStr .= $this->privateKey;

        return strtoupper(md5($encodeStr));
    }
}

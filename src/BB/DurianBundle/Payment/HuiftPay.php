<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 匯付通支付
 */
class HuiftPay extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'amount' => '', // 金額，單位元，必須有兩位小數
        'merchantNo' => '', // 商戶號
        'orderNo' => '', // 商戶訂單號
        'sign' => '', // 簽名
        'bank' => '', // 銀行代碼
        'name' => '', // 商品名稱
        'count' => '1', // 商品數量
        'returnUrl' => '', // 跳轉地址
        'notifyUrl' => '', // 通知地址
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'amount' => 'amount',
        'merchantNo' => 'number',
        'orderNo' => 'orderId',
        'bank' => 'paymentVendorId',
        'name' => 'orderId',
        'returnUrl' => 'notify_url',
        'notifyUrl' => 'notify_url',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'amount',
        'bank',
        'orderNo',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'amount' => 1,
        'orderNo' => 1,
        'transactionNo' => 1,
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
        '1' => 'ICBC', // 工商銀行
        '2' => 'BOC', // 交通銀行
        '3' => 'ABC', // 中國農業銀行
        '4' => 'CBC', // 中國建設銀行
        '5' => 'CMBC', // 招商銀行
        '6' => 'CMSB', // 中國民生銀行
        '8' => 'SPDB', // 上海浦東發展銀行
        '9' => 'BBJ', // 北京銀行
        '10' => 'CIB', // 興業銀行
        '11' => 'CTTIC', // 中信銀行
        '12' => 'CEB', // 中國光大銀行
        '13' => 'HXB', // 華夏銀行
        '14' => 'ICGB', // 廣東發展銀行
        '15' => 'PAB', // 平安銀行
        '16' => 'PSBC', // 中國郵政
        '17' => 'BC', // 中國銀行
        '19' => 'BOS', // 上海銀行
        '217' => 'CBB', // 渤海銀行
        '221' => 'CZB', // 浙商銀行
        '222' => 'NBCB', // 寧波銀行
        '226' => 'NJCB', // 南京銀行
        '278' => 'UQUICK', // 銀聯在線
        '309' => 'JSB', // 江蘇銀行
        '311' => 'HFB', // 恆豐銀行
        '1092' => 'ALISCAN', // 支付寶_二維
        '1098' => 'ALIH5', // 支付寶_手機支付
        '1103' => 'QQSCAN', // QQ_二維
        '1104' => 'QQH5', // QQ_手機支付
        '1111' => 'USCAN', // 銀聯_二維
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
        if (!array_key_exists($this->requestData['bank'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $this->requestData['amount'] = sprintf('%.2f', $this->requestData['amount']);
        $this->requestData['bank'] = $this->bankMap[$this->requestData['bank']];

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

        // 依key1=value1&key2=value2&...&keyN=valueN之後做md5
        $encodeStr = urldecode(http_build_query($encodeData));
        $encodeStr .= '#' . $this->privateKey;

        // 沒有sign就要丟例外，其他的參數在組織加密串的時候驗證過了
        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['sign'] != md5($encodeStr)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['payStatus'] != '1') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['orderNo'] != $entry['id']) {
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

        // 加密設定
        foreach ($this->encodeParams as $index) {
            $encodeData[$index] = $this->requestData[$index];
        }

        // 依key1=value1&key2=value2&...&keyN=valueN之後做md5
        $encodeStr = urldecode(http_build_query($encodeData));
        $encodeStr .= '#' . $this->privateKey;

        return md5($encodeStr);
    }
}
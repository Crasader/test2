<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 金冪支付
 */
class JinMiPay extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'Service' => 'pay.b2c', // 固定值
        'MerNo' => '', // 商家號
        'BillNo' => '', // 訂單編號
        'Amount' => '', // 金額，單位:分
        'ReturnURL' => '', // 支付成功導回的URL
        'NotifyURL' => '', // 異步通知
        'MD5info' => '', // 簽名
        'GoodsSubject' => '', // 商品名稱
        'BankCode' => '', // 銀行編碼
        'UserId' => '', // 用戶商務平台唯一ID(快捷)
        'Remark' => '', // 商戶備註，可空
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'MerNo' => 'number',
        'BillNo' => 'orderId',
        'Amount' => 'amount',
        'NotifyURL' => 'notify_url',
        'ReturnURL' => 'notify_url',
        'GoodsSubject' => 'username',
        'BankCode' => 'paymentVendorId',
        'UserId' => 'username',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'Service',
        'MerNo',
        'BillNo',
        'Amount',
        'ReturnURL',
        'NotifyURL',
        'GoodsSubject',
        'BankCode',
        'UserId',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'FlowId' => 1,
        'BillNo' => 1,
        'TransTime' => 1,
        'Amount' => 1,
        'Status' => 1,
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
        '1' => '1021000', // 工商銀行
        '2' => '3012900', // 交通銀行
        '3' => '1031000', // 中國農業銀行
        '4' => '1051000', // 中國建設銀行
        '5' => '3085840', // 招商銀行
        '6' => '3051000', // 民生銀行
        '8' => '3102900', // 上海浦東發展銀行
        '9' => '3131000', // 北京銀行
        '10' => '3093910', // 興業銀行
        '11' => '3021000', // 中信銀行
        '12' => '3031000', // 光大銀行
        '14' => '3065810', // 廣東發展銀行
        '15' => '3071000', // 平安銀行
        '16' => '4031000', // 中國郵政
        '17' => '1041000', // 中國銀行
        '279' => 'pay.kj', // 銀聯無卡
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
        if (!array_key_exists($this->requestData['BankCode'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $this->requestData['BankCode'] = $this->bankMap[$this->requestData['BankCode']];
        $this->requestData['Amount'] = round($this->requestData['Amount'] * 100);

        // 銀聯無卡額外參數設定
        if ($this->options['paymentVendorId'] == '279') {
            $this->requestData['Service'] = $this->bankMap[$this->options['paymentVendorId']];

            // 移除非銀聯無卡參數
            unset($this->requestData['BankCode']);

            // 設定支付平台需要的加密串
            $this->requestData['MD5info'] = $this->encode();

            return $this->requestData;
        }

        // 移除非網銀參數
        unset($this->requestData['UserId']);

        // 設定支付平台需要的加密串
        $this->requestData['MD5info'] = $this->encode();

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

        $decodeVerifyData = [];

        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options)) {
                $decodeVerifyData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        ksort($decodeVerifyData);

        $encodeStr = urldecode(http_build_query($decodeVerifyData));
        $encodeStr .= $this->privateKey;

        if (!isset($this->options['MD5info'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['MD5info'] != md5($encodeStr)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['Status'] != '1') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['BillNo'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['Amount'] != round($entry['amount'] * 100)) {
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
            if (isset($this->requestData[$index]) && $this->requestData[$index] != '') {
                $encodeData[$index] = $this->requestData[$index];
            }
        }

        ksort($encodeData);

        $encodeStr = urldecode(http_build_query($encodeData));
        $encodeStr .= $this->privateKey;

        return md5($encodeStr);
    }
}

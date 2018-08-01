<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 蘑菇支付
 */
class MushroomPay extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'merchant_no' => '', // 商戶號
        'method' => 'pay', // 固定值:pay
        'sign' => '', // 簽名
        'out_trade_no' => '', // 商戶訂單號
        'timestamp' => '', // 發送請求時間戳
        'amount' => '', // 金額，單位分
        'body' => '', // 商品描述
        'notify_url' => '', // 異步通知地址
        'way' => 'ebank', // 支付渠道類型編碼
        'bank_code' => '', // 銀行代碼
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'merchant_no' => 'number',
        'out_trade_no' => 'orderId',
        'timestamp' => 'orderCreateDate',
        'amount' => 'amount',
        'body' => 'orderId',
        'notify_url' => 'notify_url',
        'bank_code' => 'paymentVendorId',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'merchant_no',
        'method',
        'out_trade_no',
        'timestamp',
        'amount',
        'body',
        'notify_url',
        'way',
        'bank_code',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'code' => 1,
        'msg' => 1,
        'amount' => 1,
        'trade_no' => 1,
        'out_trade_no' => 1,
        'status' => 1,
    ];

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        '1' => 'ICBC', // 中國工商銀行
        '2' => 'BCOM', // 交通銀行
        '3' => 'ABC', // 中國農業銀行
        '4' => 'CCB', // 中國建設銀行
        '5' => 'CMB', // 招商銀行
        '6' => 'CMBC', // 中國民生銀行
        '9' => 'BOB', // 北京銀行
        '10' => 'CIB', // 興業銀行
        '11' => 'ECITIC', // 中信銀行
        '12' => 'CEBB', // 中國光大銀行
        '13' => 'HXB', // 華夏銀行
        '14' => 'GDB', // 廣東發展銀行
        '15' => 'SPABANK', // 平安銀行
        '16' => 'PSBC', // 中國郵政
        '17' => 'BOC', // 中國銀行
        '278' => 'quickpay', // 銀聯在線(快捷)
        '1088' => 'quickpay', // 銀聯在線_手機支付(快捷)
    ];

    /**
     * 應答機制訊息
     *
     * @var string
     */
    protected $msg = 'SUCCESS';

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
        if (!array_key_exists($this->requestData['bank_code'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $this->requestData['amount'] = round($this->requestData['amount'] * 100);
        $this->requestData['bank_code'] = $this->bankMap[$this->requestData['bank_code']];

        // 非銀行代碼設定
        if (in_array($this->options['paymentVendorId'], ['278', '1088'])) {
            $this->requestData['way'] = $this->requestData['bank_code'];
            $this->requestData['bank_code'] = '';
        }

        $date = new \DateTime($this->requestData['timestamp']);
        $this->requestData['timestamp'] = $date->getTimestamp() . '000';
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

        // 驗證返回參數
        $this->payResultVerify();

        $encodeData = [];

        // 組織加密串
        foreach (array_keys($this->decodeParams) as $paymentKey) {
            // 如果有key而且不是空值的參數才需要做加密
            if (array_key_exists($paymentKey, $this->options) && $this->options[$paymentKey] != '') {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        // 針對$encodeData按字母做升序排列
        ksort($encodeData);

        //依key1=value1&key2=value2&...&keyN=valueN之後做md5
        $encodeStr = urldecode(http_build_query($encodeData));
        $encodeStr .= $this->privateKey;

        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['code'] !== '0000' || $this->options['status'] !== '1') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['sign'] != md5($encodeStr)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['out_trade_no'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['amount'] != round($entry['amount'] * 100)) {
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

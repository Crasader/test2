<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 好支付
 */
class HaoPay extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'service' => 'Pay.Request', // 接口類型
        'partner' => '', // 商號
        'clientapp' => '1', // 應用類型(固定值)
        'sign' => '', // 簽名
        'sign_type' => 'MD5', // 簽名類型
        'type' => '', // 支付接口類型
        'charset' => 'utf-8', // 編碼
        'subject' => '', // 商品標題
        'out_trade_no' => '', // 訂單號
        'total_fee' => '', // 金額
        'notify_url' => '', // 異步通知
        'return_url' => '', // 同步通知(可空)
        'show_url' => '', // 商品展示地址(可空)
        'body' => '', // 商品描述(可空)
        'extra_common_param' => '', // 商家數據(可空)
        'movement' => '', // 請求發起是腳本(可空)
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'partner' => 'number',
        'type' => 'paymentVendorId',
        'subject' => 'username',
        'out_trade_no' => 'orderId',
        'total_fee' => 'amount',
        'notify_url' => 'notify_url',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'service',
        'partner',
        'clientapp',
        'sign_type',
        'type',
        'charset',
        'subject',
        'out_trade_no',
        'total_fee',
        'notify_url',
        'return_url',
        'show_url',
        'body',
        'extra_common_param',
        'movement',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'type' => 0,
        'out_trade_no' => 1,
        'trade_no' => 0,
        'total_fee' => 1,
        'extra_common_param' => 0,
        'movement' => 0,
        'trade_status' => 1,
        'notify_url' => 0,
        'return_url' => 0,
        'clientid' => 0,
        'status' => 0,
        'subject' => 0,
        'body' => 0,
    ];

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        '1090' => 'Wechatnative', // 微信二維
        '1092' => 'Alipay', // 支付寶二維
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

        // 檢查銀行是否支援
        if (!array_key_exists($this->options['paymentVendorId'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $this->requestData['type'] = $this->bankMap[$this->requestData['type']];

        // 產生加密字串
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

        // 驗簽
        $encodeData = [];
        foreach (array_keys($this->decodeParams) as $key) {
            if (array_key_exists($key, $this->options)) {
                $encodeData[$key] = $this->options[$key];
            }
        }

        ksort($encodeData);

        $encodeStr = urldecode(http_build_query($encodeData)) . $this->privateKey;

        // 檢查簽名
        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if (strtoupper($this->options['sign']) !== strtoupper(md5($encodeStr))) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        // 檢查支付狀態
        if (strtoupper($this->options['trade_status']) !== 'TRADE_SUCCESS') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        // 檢查單號
        if ($this->options['out_trade_no'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        // 檢查金額
        if ($this->options['total_fee'] != $entry['amount']) {
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
        foreach ($this->encodeParams as $index) {
            $encodeData[$index] = $this->requestData[$index];
        }

        ksort($encodeData);

        $encodeStr = urldecode(http_build_query($encodeData));

        return md5($encodeStr . $this->privateKey);
    }
}

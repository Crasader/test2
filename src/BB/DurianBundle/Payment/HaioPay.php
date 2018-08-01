<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 海鷗閃付
 */
class HaioPay extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'userid' => '', // 商戶編號
        'orderid' => '', // 商戶訂單號
        'price' => '', // 金額(單位元，2位小數)
        'payvia' => '', // 支付類型，可空
        'notify' => '', // 異步通知地址
        'callback' => '', // 同步跳轉
        'timespan' => '', // 提交時間(格式yyyyMMddHHmmss)
        'custom' => '', // 自定義數據，可空
        'format' => '', // 掃碼接口型式，可空
        'sign' => '', // md5簽名
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'userid' => 'number',
        'orderid' => 'orderId',
        'price' => 'amount',
        'payvia' => 'paymentVendorId',
        'notify' => 'notify_url',
        'callback' => 'notify_url',
        'timespan' => 'orderCreateDate',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'userid',
        'orderid',
        'price',
        'payvia',
        'notify',
        'callback',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'userid' => 1,
        'orderid' => 1,
        'billno' => 1,
        'price' => 1,
        'payvia' => 1,
        'state' => 1,
        'timespan' => 1,
    ];

    /**
     * 應答機制訊息
     *
     * @var string
     */
    protected $msg = 'success';

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        1 => 'icbc', // 中國工商銀行
        2 => 'boco', // 交通銀行
        3 => 'abc', // 中國農業銀行
        4 => 'ccb', // 中國建設銀行
        5 => 'cmb', // 招商銀行
        6 => 'cmbc', // 中國民生銀行
        8 => 'spdb', // 上海浦東發展銀行
        9 => 'bccb', // 北京銀行
        10 => 'cib', // 興業銀行
        11 => 'ecitic', // 中信银行
        12 => 'ceb', // 光大銀行
        13 => 'hxb', // 華夏銀行
        14 => 'gdb', // 廣東發展銀行
        16 => 'post', // 郵政儲蓄銀行
        17 => 'boc', // 中國銀行
        297 => 'tenpay', // 財付通
        1090 => 'weixin', // 微信支付_二維
        1092 => 'alipay', // 支付寶_二維
        1097 => 'wxwap', // 微信支付_手機支付
        1098 => 'alipaywap', // 支付寶_手機支付
        1099 => 'tenpaywap', // 財付通_手機支付
        1103 => 'qqpay', // QQ_二維
        1104 => 'qqwap', // QQ_手機支付
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
        if (!array_key_exists($this->requestData['payvia'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $orderCreateDate = new \DateTime($this->options['orderCreateDate']);
        $this->requestData['price'] = sprintf('%.2f', $this->requestData['price']);
        $this->requestData['payvia'] = $this->bankMap[$this->requestData['payvia']];
        $this->requestData['timespan'] = $orderCreateDate->format('YmdHis');

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

        // 依key1=value1&key2=value2&...&keyN=valueN
        $encodeStr = urldecode(http_build_query($encodeData));

        // 兩次md5
        $md5Str = md5(md5($encodeStr) . $this->privateKey);

        // 如果沒有返回簽名要丟例外
        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['sign'] != $md5Str) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['state'] != '1') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['orderid'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['price'] != $entry['amount']) {
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

        // 依key1=value1&key2=value2&...&keyN=valueN
        $encodeStr = urldecode(http_build_query($encodeData));

        // 兩次md5
        $md5Str = md5(md5($encodeStr) . $this->privateKey);

        return $md5Str;
    }
}
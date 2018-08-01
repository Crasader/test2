<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * my pay
 */
class MyPay extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'version' => 'V1.0', // 接口版本，固定值
        'merId' => '', // 商號
        'orgId' => '', // 機構號
        'payType' => '', // 支付類型
        'merchantNo' => '', // 訂單號
        'terminalClient' => 'wap', // 支付裝置
        'tradeDate' => '', // 交易日期YmdHis
        'amount' => '', // 訂單金額，單位元，精確到小數點後兩位
        'clientIp' => '', // 付款人IP地址
        'notifyUrl' => '', // 支付通知網址
        'sign' => '', // 簽名
        'signType' => 'MD5', // 簽名方式
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'merId' => 'number',
        'payType' => 'paymentVendorId',
        'merchantNo' => 'orderId',
        'tradeDate' => 'orderCreateDate',
        'amount' => 'amount',
        'clientIp' => 'ip',
        'notifyUrl' => 'notify_url',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'merId',
        'payType',
        'merchantNo',
        'terminalClient',
        'tradeDate',
        'amount',
        'clientIp',
        'notifyUrl',
    ];

    /**
     * 返回驗證時需要加密的參數
     *     0: 有返回就要加密的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'merId' => 1,
        'orderNo' => 1,
        'merchantNo' => 1,
        'tradeDate' => 1,
        'amount' => 1,
        'realAmount' => 1,
    ];

    /**
     * 應答機制訊息
     *
     * @var string
     */
    protected $msg = 'MyPay';

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        '278' => '14', // 銀聯在線(快捷)
        '1088' => '14', // 銀聯在線_手機支付(快捷)
        '1090' => '3', // 微信_二維
        '1092' => '1', // 支付寶_二維
        '1097' => '4', // 微信_手機支付
        '1098' => '2', // 支付寶_手機支付
        '1102' => '5', // 網銀收銀台
        '1103' => '8', // QQ_二維
        '1104' => '9', // QQ_手機支付
        '1107' => '10', // 京東_二維
        '1108' => '11', // 京東_手機支付
        '1111' => '16', // 銀聯_二維
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

        // 從內部設值到支付參數
        foreach ($this->requireMap as $paymentKey => $internalKey) {
            $this->requestData[$paymentKey] = $this->options[$internalKey];
        }

        // 帶入未支援的支付方式就噴例外
        if (!array_key_exists($this->requestData['payType'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $createAt = new \Datetime($this->requestData['tradeDate']);
        $this->requestData['tradeDate'] = $createAt->format('YmdHis');
        $this->requestData['payType'] = $this->bankMap[$this->requestData['payType']];
        $this->requestData['amount'] = sprintf('%.2f', $this->requestData['amount']);

        // 取得商家附加設定值
        $merchantExtraValues = $this->getMerchantExtraValue([
            'orgId',
            'postUrl',
        ]);
        $this->requestData['orgId'] = $merchantExtraValues['orgId'];

        // 設定加密簽名
        $this->requestData['sign'] = $this->encode();

        return [
            'post_url' => filter_var($merchantExtraValues['postUrl'], FILTER_SANITIZE_URL),
            'params' => $this->requestData,
        ];
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

        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options)) {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        ksort($encodeData);
        $encodeData['key'] = $this->privateKey;
        $encodeStr = urldecode(http_build_query($encodeData));

        // 若沒有返回簽名需丟例外
        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if (strcasecmp($this->options['sign'], md5($encodeStr)) != 0) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['merchantNo'] != $entry['id']) {
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

        foreach ($this->encodeParams as $paymentKey) {
            if (array_key_exists($paymentKey, $this->requestData)) {
                $encodeData[$paymentKey] = $this->requestData[$paymentKey];
            }
        }

        ksort($encodeData);
        $encodeData['key'] = $this->privateKey;
        $encodeStr = urldecode(http_build_query($encodeData));

        return strtoupper(md5($encodeStr));
    }
}

<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 *　新聚易云支付
 */
class XinJuYiYunPay extends PaymentBase
{
    /**
     * 支付提交參數
     *
     * @var array
     */
    protected $requestData = [
        'signMethod' => 'MD5', // 簽名方式，目前只有MD5
        'signature' => '', // 簽名
        'version' => '1.0.0', // 版本號，固定為1.0.0
        'subject' => '', // 標題，必填
        'describe' => '', // 描述，可空
        'remark' => '', // 備註，可空
        'userIP' => '', // IP位址
        'merOrderId' => '', // 商戶訂單號
        'payMode' => '0201', // 支付方式，0701-扫码支付，0201-web支付
        'tradeTime' => '', // 交易時間，格式YmdHis
        'tradeType' => '52', // 交易類型，網銀:52
        'tradeSubtype' => '01', // 交易子類型，必填，固定值
        'currency' => 'CNY', // 貨幣
        'amount' => '', // 金額，單位：分
        'urlBack' => '', // 異步通知網址
        'urlJump' => '', // 同步通知網址
        'merId' => '', // 商戶shopId
        'merUserId' => '', // 用戶標示，可空
        'bankCode' => '', // 銀行代碼，網銀用
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'merId' => 'number',
        'merOrderId' => 'orderId',
        'amount' => 'amount',
        'subject' => 'username',
        'urlBack' => 'notify_url',
        'urlJump' => 'notify_url',
        'bankCode' => 'paymentVendorId',
        'userIP' => 'ip',
        'tradeTime' => 'orderCreateDate',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'version',
        'subject',
        'describe',
        'remark',
        'userIP',
        'merOrderId',
        'merId',
        'merUserId',
        'payMode',
        'tradeTime',
        'tradeType',
        'tradeSubtype',
        'currency',
        'amount',
        'bankCode',
        'urlBack',
        'urlJump',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'merOrderId' => 1,
        'customerOrderId' => 1,
        'notifyType' => 1,
        'notifyTime' => 1,
        'merId' => 1,
        'amount' => 1,
        'status' => 1,
        'remark' => 1,
    ];

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        '1' => '100001', // 工商銀行
        '2' => '100012', // 交通銀行
        '3' => '100003', // 農業銀行
        '4' => '100006', // 建設銀行
        '5' => '100010', // 招商銀行
        '6' => '100011', // 民生銀行
        '8' => '100017', // 上海浦東發展銀行
        '10' => '100008', // 興業銀行
        '11' => '100009', // 中信銀行
        '12' => '100007', // 光大銀行
        '13' => '100015', // 華夏銀行
        '14' => '100013', // 廣發銀行
        '15' => '100016', // 平安銀行
        '16' => '100002', // 中國郵政
        '17' => '100005', // 中國銀行
        '19' => '100018', // 上海銀行
        '223' => '100014', // 東亞銀行
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

        // 帶入未支援的銀行就噴例外
        if (!array_key_exists($this->requestData['bankCode'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $this->requestData['amount'] = strval(round($this->requestData['amount']) * 100);
        $createAt = new \Datetime($this->requestData['tradeTime']);
        $this->requestData['tradeTime'] = $createAt->format('YmdHis');
        $this->requestData['bankCode'] = $this->bankMap[$this->requestData['bankCode']];

        // 設定加密簽名
        $this->requestData['signature'] = $this->encode();

        $this->requestData['subject'] = base64_encode($this->requestData['subject']);

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

        ksort($encodeData);
        $encodeStr = urldecode(http_build_query($encodeData));
        $encodeStr .= $this->privateKey;

        if (!isset($this->options['signature'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['signature'] != base64_encode(md5($encodeStr))) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['status'] !== '0000') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['customerOrderId'] != $entry['id']) {
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
        foreach ($this->encodeParams as $index) {
            $encodeData[$index] = $this->requestData[$index];
        }

        ksort($encodeData);
        $encodeStr = urldecode(http_build_query($encodeData));
        $encodeStr .= $this->privateKey;

        return base64_encode(md5($encodeStr));
    }
}

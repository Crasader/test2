<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 *　融财付
 */
class RongCaiFu extends PaymentBase
{
    /**
     * 支付提交參數
     *
     * @var array
     */
    protected $requestData = [
        'requestNo' => '', // 交易流水號
        'version' => 'V4.0', // 版本號，固定值
        'productId' => '0103', // 產品類型，網銀:0103
        'transId' => '01', // 交易類型，網銀:01
        'merNo' => '', // 商戶號
        'orderDate' => '', // 訂單日期
        'orderNo' => '', // 訂單號
        'returnUrl' => '', // 同步通知網址
        'notifyUrl' => '', // 異步通知網址
        'transAmt' => '', // 訂單金額，單位：分
        'commodityName' => '', // 商品名稱，帶入username
        'cashier' => '0', // 是否展示收銀檯，直連銀行:0
        'bankCode' => '', // 銀行編號
        'payType' => '1', // 支付方式，借記卡:1
        'memo' => '', // 備註，帶入username
        'signature' => '', // 簽名
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'requestNo' => 'orderId',
        'merNo' => 'number',
        'orderDate' => 'orderCreateDate',
        'orderNo' => 'orderId',
        'returnUrl' => 'notify_url',
        'notifyUrl' => 'notify_url',
        'transAmt' => 'amount',
        'commodityName' => 'username',
        'bankCode' => 'paymentVendorId',
        'memo' => 'username',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'requestNo',
        'productId',
        'transId',
        'merNo',
        'orderDate',
        'orderNo',
        'returnUrl',
        'notifyUrl',
        'transAmt',
        'commodityName',
        'memo'
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'productId' => 1,
        'transId' => 1,
        'merNo' => 1,
        'orderNo' => 1,
        'transAmt' => 1,
        'orderDate' => 1,
        'notifyUrl' => 1,
        'respCode' => 1,
        'respDesc' => 1,
        'payId' => 0,
        'payTime' => 0,
        'memo' => 1,
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
        '1' => '01020000', // 工商銀行
        '2' => '03010000', // 交通銀行
        '3' => '01030000', // 農業銀行
        '4' => '01050000', // 建設銀行
        '5' => '03080000', // 招商銀行
        '6' => '03050000', // 民生銀行
        '8' => '03100000', // 上海浦東發展銀行
        '9' => '04031000', // 北京銀行
        '10' => '03090000', // 興業銀行
        '11' => '03020000', // 中信銀行
        '12' => '03030000', // 光大銀行
        '13' => '03040000', // 華夏銀行
        '14' => '03060000', // 廣發銀行
        '15' => '03070000', // 平安銀行
        '16' => 'PSBC', // 中國郵政
        '17' => '01040000', // 中國銀行
        '19' => '65012900', // 上海銀行
        '220' => 'HZCB', // 杭州銀行
        '278' => '0120', // 銀聯在線
        '1088' => '0117', // 銀聯在線_手機支付
        '1090' => '0104', // 微信_二維
        '1092' => '0109', // 支付寶_二維
        '1097' => '0107', // 微信_手機支付
        '1098' => '0121', // 支付寶_手機支付
        '1103' => '0118', // QQ_二維
        '1104' => '0122', // QQ_手機支付
        '1111' => '0125', // 銀聯錢包_二維
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
        $this->requestData['bankCode'] = $this->bankMap[$this->requestData['bankCode']];
        $this->requestData['transAmt'] = round($this->requestData['transAmt'] * 100);
        $date = new \DateTime($this->requestData['orderDate']);
        $this->requestData['orderDate'] = $date->format('Ymd');

        // 二維支付
        if (in_array($this->options['paymentVendorId'], [1090, 1092, 1103, 1111])) {
            $this->requestData['transId'] = '10';
            $this->requestData['productId'] = $this->requestData['bankCode'];
            $this->requestData['cashier'] = '1';
            // 移除二維不需要的參數
            unset($this->requestData['bankCode']);
            unset($this->requestData['payType']);
        }

        // 手機支付、快捷支付
        if (in_array($this->options['paymentVendorId'], [278, 1088, 1097, 1098, 1104])) {
            $this->requestData['productId'] = $this->requestData['bankCode'];
            // 移除手機支付、快捷支付不需要的參數
            unset($this->requestData['bankCode']);
            unset($this->requestData['payType']);
            unset($this->requestData['cashier']);
        }

        // 設定加密簽名
        $this->requestData['signature'] = $this->encode();

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

        if (strcasecmp($this->options['signature'], md5($encodeStr)) != 0) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['respCode'] !== '0000') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['orderNo'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['transAmt'] != $entry['amount'] * 100) {
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
            if (isset($this->requestData[$index])) {
                $encodeData[$index] = $this->requestData[$index];
            }
        }

        ksort($encodeData);

        $encodeStr = urldecode(http_build_query($encodeData));
        $encodeStr .= $this->privateKey;

        return strtoupper(md5($encodeStr));
    }
}

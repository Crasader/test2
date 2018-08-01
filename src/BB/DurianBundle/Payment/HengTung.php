<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 恒通支付
 */
class HengTung extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'customer' => '', // 商號
        'banktype' => '', // 支付類型
        'amount' => '', // 金額，單位元
        'orderid' => '', // 訂單號
        'asynbackurl' => '', // 異步通知地址，不能串參數
        'request_time' => '', // 請求時間
        'synbackurl' => '', // 同步通知地址，可空
        'attach' => '', // 備註，可空
        'israndom' => '', // 啟用訂單風控保護規則，可空
        'sign' => '', // 加密簽名
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'customer' => 'number',
        'banktype' => 'paymentVendorId',
        'amount' => 'amount',
        'orderid' => 'orderId',
        'asynbackurl' => 'notify_url',
        'request_time' => 'orderCreateDate',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'customer',
        'banktype',
        'amount',
        'orderid',
        'asynbackurl',
        'request_time',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'orderid' => 1,
        'result' => 1,
        'amount' => 1,
        'systemorderid' => 1,
        'completetime' => 1,
    ];

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        '1' => '967', // 中國工商銀行
        '2' => '981', // 交通銀行
        '3' => '964', // 中國農業銀行
        '4' => '965', // 中國建設銀行
        '5' => '970', // 招商銀行
        '6' => '980', // 中國民生銀行
        '8' => '977', // 上海浦東發展銀行
        '9' => '989', // 北京銀行
        '10' => '972', // 興業銀行
        '11' => '962', // 中信銀行
        '12' => '986', // 中國光大銀行
        '14' => '985', // 廣東發展銀行
        '16' => '971', // 中國郵政
        '17' => '963', // 中國銀行
        '220' => '983', // 杭州銀行
        '223' => '987', // 東亞銀行
        '226' => '979', // 南京銀行
        '228' => '976', // 上海市農村商業銀行
        '1090' => '1000', // 微信_二維
        '1092' => '1003', // 支付寶_二維
        '1097' => '1002', // 微信_手機支付
        '1098' => '1004', // 支付寶_手機支付
        '1103' => '1005', // QQ_二維
        '1104' => '1006', // QQ_手機支付
        '1107' => '1007', // 京東_二維
        '1108' => '1008', // 京東_手機支付
        '1111' => '1009', // 銀聯錢包_二維
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
        if (!array_key_exists($this->requestData['banktype'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $date = new \DateTime($this->requestData['request_time']);
        $this->requestData['request_time'] = $date->format('YmdHis');
        $this->requestData['amount'] = sprintf('%.2f', $this->requestData['amount']);
        $this->requestData['banktype'] = $this->bankMap[$this->requestData['banktype']];

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
        $encodeStr = urldecode(http_build_query($encodeData));

        // 如果沒有返回簽名要丟例外
        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['sign'] != md5($encodeStr)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['result'] != '1') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['orderid'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['payamount'] != $entry['amount']) {
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
        $encodeStr = urldecode(http_build_query($encodeData));

        return md5($encodeStr);
    }
}

<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 速付支付
 */
class SuFuPay extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'notify_url' => '', // 異步通知地址
        'return_url' => '', // 同步通知地址，可空
        'pay_type' => '1', // 支付方式，網銀:1
        'bank_code' => '', // 銀行編碼
        'merchant_code' => '', // 商戶號
        'order_no' => '', // 商戶訂單號
        'order_amount' => '', // 訂單總金額，單位元，精確到小數點後兩位
        'order_time' => '', // 訂單時間
        'req_referer' => '', // 來路域名
        'customer_ip' => '', // ip
        'return_params' => '', // 回傳參數，可空
        'sign' => '', // 簽名
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'notify_url' => 'notify_url',
        'bank_code' => 'paymentVendorId',
        'merchant_code' => 'number',
        'order_no' => 'orderId',
        'order_amount' => 'amount',
        'order_time' => 'orderCreateDate',
        'req_referer' => 'notify_url',
        'customer_ip' => 'ip',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'notify_url',
        'return_url',
        'pay_type',
        'bank_code',
        'merchant_code',
        'order_no',
        'order_amount',
        'order_time',
        'req_referer',
        'customer_ip',
        'return_params',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'merchant_code' => 1,
        'order_no' => 1,
        'order_amount' => 1,
        'order_time' => 1,
        'trade_no' => 1,
        'trade_time' => 1,
        'trade_status' => 1,
        'notify_type' => 1,
    ];

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        '1' => 'ICBC', // 工商銀行
        '2' => 'BOCOM', // 交通銀行
        '3' => 'ABC', // 農業銀行
        '4' => 'CCB', // 建設銀行
        '5' => 'CMBC', // 招商銀行
        '6' => 'CMBCS', // 民生銀行
        '8' => 'SPDB', // 浦發銀行
        '9' => 'BJBANK', // 北京銀行
        '10' => 'CIB', // 興業銀行
        '11' => 'ECITIC', // 中信銀行
        '12' => 'CEBBANK', // 光大銀行
        '13' => 'HXB', // 華夏銀行
        '14' => 'CGB', // 廣發銀行
        '15' => 'PINGAN', // 平安銀行
        '16' => 'PSBC', // 中國郵政
        '17' => 'BOC', // 中國銀行
        '19' => 'BOS', // 上海銀行
        '1098' => '3', // 支付寶_手機支付
        '1103' => '5', // QQ_二維
    ];

    /**
     * 出款時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $withdrawRequestData = [
        'merchant_code' => '', // 商戶號
        'sign' => '', // 簽名
        'order_amount' => '', // 總金額，單位元，精確到小數點後兩位
        'trade_no' => '', // 商戶唯一訂單號
        'order_time' => '', // 交易日期
        'bank_code' => '', // 出款支持銀行
        'account_name' => '', // 銀行卡戶名
        'account_number' => '', // 銀行卡卡號
    ];

    /**
     * 出款時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $withdrawRequireMap = [
        'merchant_code' => 'number',
        'order_amount' => 'amount',
        'trade_no' => 'orderId',
        'order_time' => 'orderCreateDate',
        'bank_code' => 'bank_info_id',
        'account_name' => 'nameReal',
        'account_number' => 'account',
    ];

    /**
     * 出款支援的銀行對應編號
     *
     * @var array
     */
    protected $withdrawBankMap = [
        '1' => 'ICBC', // 工商銀行
        '2' => 'BOCOM', // 交通銀行
        '3' => 'ABC', // 農業銀行
        '4' => 'CCB', // 建設銀行
        '5' => 'CMBC', // 招商銀行
        '6' => 'CMBCS', // 民生銀行
        '8' => 'SPDB', // 浦發銀行
        '9' => 'BJBANK', // 北京銀行
        '10' => 'CIB', // 興業銀行
        '11' => 'ECITIC', // 中信銀行
        '12' => 'CEBBANK', // 光大銀行
        '13' => 'HXB', // 華夏銀行
        '14' => 'CGB', // 廣發銀行
        '15' => 'PINGAN', // 平安銀行
        '16' => 'PSBC', // 中國郵政
        '17' => 'BOC', // 中國銀行
        '19' => 'BOS', // 上海銀行
    ];

    /**
     * 出款時需要加密的參數
     *
     * @var array
     */
    protected $withdrawEncodeParams = [
        'merchant_code',
        'order_amount',
        'trade_no',
        'order_time',
        'bank_code',
        'account_name',
        'account_number',
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
        if (!array_key_exists($this->requestData['bank_code'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $this->requestData['order_amount'] = sprintf('%.2f', $this->requestData['order_amount']);
        $this->requestData['bank_code'] = $this->bankMap[$this->requestData['bank_code']];
        $date = new \DateTime($this->requestData['order_time']);
        $this->requestData['order_time'] = $date->format('Y-m-d H:i:s');

        // 二維、手機支付調整提交參數
        if (in_array($this->options['paymentVendorId'], [1098, 1103])) {
            $this->requestData['pay_type'] = $this->requestData['bank_code'];
            unset($this->requestData['bank_code']);
        }

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

        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options)) {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        ksort($encodeData);

        $encodeData['key'] = $this->privateKey;

        $encodeStr = urldecode(http_build_query($encodeData));

        // 沒有sign就要丟例外，其他的參數在組織加密串的時候驗證過了
        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if (strcasecmp($this->options['sign'], md5($encodeStr)) != 0) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['trade_status'] !== 'success') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['order_no'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['order_amount'] != $entry['amount']) {
            throw new PaymentException('Order Amount error', 180058);
        }
    }

    /**
     * 線上出款
     */
    public function withdrawPayment()
    {
        $this->verifyPrivateKey();
        $this->withdrawVerify();

        // 從內部給定值到參數
        foreach ($this->withdrawRequireMap as $paymentKey => $internalKey) {
            $this->withdrawRequestData[$paymentKey] = $this->options[$internalKey];
        }

        // 驗證出款時支付平台對外設定
        if (trim($this->options['withdraw_host']) == '') {
            throw new PaymentException('No withdraw_host specified', 150180194);
        }

        // 帶入未支援的銀行就噴例外
        if (!array_key_exists($this->withdrawRequestData['bank_code'], $this->withdrawBankMap)) {
            throw new PaymentException('BankInfo is not supported by PaymentGateway', 150180195);
        }

        // 額外的參數設定
        $this->withdrawRequestData['bank_code'] = $this->withdrawBankMap[$this->withdrawRequestData['bank_code']];
        $this->withdrawRequestData['order_amount'] = sprintf('%.2f', $this->withdrawRequestData['order_amount']);
        $createAt = new \DateTime($this->withdrawRequestData['order_time']);
        $this->withdrawRequestData['order_time'] = $createAt->format('Y-m-d H:i:s');

        // 設定出款需要的加密串
        $this->withdrawRequestData['sign'] = $this->withdrawEncode();

        // 出款
        $curlParam = [
            'method' => 'POST',
            'uri' => '/remit.html',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['withdraw_host'],
            'param' => http_build_query($this->withdrawRequestData),
            'header' => [],
            'timeout' => 60,
        ];

        $result = $this->curlRequest($curlParam);
        $parseData = json_decode($result, true);

        // 對返回結果做檢查
        if (!isset($parseData['bank_status'])) {
           throw new PaymentException('No withdraw return parameter specified', 150180209);
        }

        if ($parseData['bank_status'] != '1') {
           throw new PaymentConnectionException('Withdraw error', 180124, $this->getEntryId());
        }

        if (isset($parseData['order_id'])) {
            // 紀錄出款明細的支付平台參考編號
            $this->setCashWithdrawEntryRefId($parseData['order_id']);
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
        foreach ($this->encodeParams as $key) {
            if (isset($this->requestData[$key])) {
                $encodeData[$key] = $this->requestData[$key];
            }
        }

        // 針對$encodeData按字母做升序排列
        ksort($encodeData);

        $encodeData['key'] = $this->privateKey;

        $encodeStr = urldecode(http_build_query($encodeData));

        return md5($encodeStr);
    }

    /**
     * 出款時的加密
     *
     * @return string
     */
    protected function withdrawEncode()
    {
        $encodeData = [];

        foreach ($this->withdrawEncodeParams as $key) {
            $encodeData[$key] = $this->withdrawRequestData[$key];
        }

        // 針對$encodeData按字母做升序排列
        ksort($encodeData);

        $encodeData['key'] = $this->privateKey;

        $encodeStr = urldecode(http_build_query($encodeData));

        return md5($encodeStr);
    }
}

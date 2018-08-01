<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 隨意付
 */
class HfbPay extends PaymentBase
{
    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        1 => [1, 2], // 工商銀行
        2 => [1, 3], // 交通銀行
        3 => [1, 4], // 農業銀行
        4 => [1, 5], // 建設銀行
        5 => [1, 6], // 招商銀行
        6 => [1, 7], // 民生銀行
        8 => [1, 8], // 上海浦東發展銀行
        10 => [1, 10], // 興業銀行
        11 => [1, 11], // 中信銀行
        12 => [1, 12], // 光大銀行
        13 => [1, 13], // 華夏銀行
        14 => [1, 14], // 廣發銀行
        15 => [1, 15], // 平安銀行
        16 => [1, 16], // 中國郵政
        17 => [1, 17], // 中國銀行
    ];

    /**
     * 出款時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $withdrawRequestData = [
        'apiName' => 'SINGLE_ENTRUST_SETT', // 接口名稱，固定值
        'apiVersion' => '1.0.0.0', // 接口版本，固定值
        'platformID' => '', // 平台號ID
        'merchNo' => '', // 商戶號
        'orderNo' => '', // 商戶訂單號
        'tradeDate' => '', // 交易日期，格式:Ymd
        'merchUrl' => '', // 通知地址
        'merchParam' => '', // 商戶參數，可空
        'bankAccNo' => '', // 銀行卡卡號
        'bankAccName' => '', // 銀行卡戶名
        'bankCode' => '', // 銀行卡銀行代碼
        'bankName' => '', // 銀行卡開戶行名稱，需串支行信息
        'Amt' => '', // 結算金額，單位元，精確到小數點後兩位
        'tradeSummary' => '', // 交易摘要，可空
        'signMsg' => '', // 簽名
    ];

    /**
     * 出款時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $withdrawRequireMap = [
        'platformID' => 'number',
        'merchNo' => 'number',
        'orderNo' => 'orderId',
        'tradeDate' => 'orderCreateDate',
        'merchUrl' => 'shop_url',
        'bankAccNo' => 'account',
        'bankAccName' => 'nameReal',
        'bankCode' => 'bank_info_id',
        'bankName' => 'bank_name',
        'Amt' => 'amount',
    ];

    /**
     * 出款支援的銀行對應編號
     *
     * @var array
     */
    protected $withdrawBankMap = [
        1 => 'ICBC', // 工商銀行
        2 => 'COMM', // 交通銀行
        3 => 'ABC', // 農業銀行
        4 => 'CCB', // 建設銀行
        5 => 'CMB', // 招商銀行
        6 => 'CMBC', // 民生銀行總行
        8 => 'SPBD', // 上海浦東發展銀行
        10 => 'CIB', // 興業銀行
        11 => 'CNCB', // 中信銀行
        12 => 'CEB', // 光大銀行
        13 => 'HXB', // 華夏銀行
        14 => 'GDB', // 廣東發展銀行
        15 => 'PAB', // 平安銀行
        16 => 'PSBC', // 中國郵政
        17 => 'BOC', // 中國銀行
    ];

    /**
     * 出款時需要加密的參數
     *
     * @var array
     */
    protected $withdrawEncodeParams = [
        'apiName',
        'apiVersion',
        'platformID',
        'merchNo',
        'orderNo',
        'tradeDate',
        'merchUrl',
        'merchParam',
        'bankAccNo',
        'bankAccName',
        'bankCode',
        'bankName',
        'Amt',
        'tradeSummary',
    ];

    /**
     * 回傳支付時要給支付平台驗證的資料
     *
     * @return array
     */
    public function getVerifyData()
    {
        $this->setRequestData();

        // 帶入未支援的銀行就噴例外
        if (!array_key_exists($this->requestData['method_id'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        $this->requestData['method_id'] = $this->bankMap[$this->requestData['method_id']];

        // 如果是網銀，需要設定bank_id
        if (is_array($this->requestData['method_id'])) {
            $payType = $this->requestData['method_id'];
            $this->requestData['method_id'] = $payType[0];
            $this->requestData['bank_id'] = $payType[1];
        }

        return $this->getPaymentDepositParams();
    }

    /**
     * 驗證線上支付是否成功
     */
    public function verifyOrderPayment()
    {
        $this->paymentVerify();
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

        $withdrawHost = trim($this->options['withdraw_host']);

        // 驗證出款時支付平台對外設定
        if ($withdrawHost == '') {
            throw new PaymentException('No withdraw_host specified', 150180194);
        }

        // 設定返回網址
        $this->withdrawRequestData['merchUrl'] .= 'withdraw_return.php';

        $this->withdrawRequestData['bankCode'] = $this->withdrawBankMap[$this->withdrawRequestData['bankCode']];
        $this->withdrawRequestData['Amt'] = sprintf('%.2f', $this->withdrawRequestData['Amt']);
        $createAt = new \Datetime($this->withdrawRequestData['tradeDate']);
        $this->withdrawRequestData['tradeDate'] = $createAt->format('Ymd');

        // bankName需串上支行
        $this->withdrawRequestData['bankName'] .= $this->options['branch'];

        // 設定出款需要的加密串
        $this->withdrawRequestData['signMsg'] = $this->withdrawEncode();

        // 出款
        $curlParam = [
            'method' => 'POST',
            'uri' => '/cgi-bin/netpayment/pay_gate.cgi',
            'ip' => $this->options['verify_ip'],
            'host' => $withdrawHost,
            'param' => http_build_query($this->withdrawRequestData),
            'header' => [],
            'timeout' => 60,
        ];

        $result = $this->curlRequest($curlParam);
        $parseData = $this->xmlToArray($result);

        // 對返回結果做檢查
        if (!isset($parseData['respData']['respCode']) || !isset($parseData['respData']['respDesc'])) {
            throw new PaymentException('No withdraw return parameter specified', 150180209);
        }

        if ($parseData['respData']['respCode'] !== '00') {
            throw new PaymentConnectionException($parseData['respData']['respDesc'], 180124, $this->getEntryId());
        }

        if (isset($parseData['respData']['batchNo'])) {
            // 紀錄出款明細的支付平台參考編號
            $this->setCashWithdrawEntryRefId($parseData['respData']['batchNo']);
        }
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

        $encodeStr = urldecode(http_build_query($encodeData));
        $encodeStr .= $this->privateKey;

        return strtoupper(md5($encodeStr));
    }
}

<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 暢捷支付
 */
class Chanpay extends PaymentBase
{
    /**
     * 出款時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $withdrawRequestData = [
        'Service' => 'cjt_dsf', // 接口名稱，固定值
        'Version' => '1.0', // 接口版本，固定值
        'PartnerId' => '', // 商戶編號
        'TradeDate' => '', // 請求日期
        'TradeTime' => '', // 請求時間
        'InputCharset' => 'UTF-8', // 參數編碼字符集，固定值
        'SignType' => 'RSA', // 簽名方式，固定值
        'TransCode'  => 'T10000', // 交易碼，固定值
        'OutTradeNo' => '', // 交易請求號
        'BusinessType' => '0', // 業務類型，0:私人、1:公司
        'BankCommonName' => '', // 通用銀行名稱
        'AcctNo' => '', // 付款方銀行卡
        'AcctName' => '', // 付款方帳戶名稱
        'TransAmt' => '', // 交易金額，精確到小數第二位
        'AccountType' => '00', // 帳戶類型，00:借記卡、01:貸記卡
        'Currency' => 'CNY', // 幣別類型
        'Sign' => '', // 簽名
    ];

    /**
     * 出款時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $withdrawRequireMap = [
        'PartnerId' => 'number',
        'TradeDate' => 'orderCreateDate',
        'OutTradeNo' => 'orderId',
        'BankCommonName' => 'bank_info_id',
        'AcctNo' => 'account',
        'AcctName' => 'nameReal',
        'TransAmt' => 'amount',
    ];

    /**
     * 出款支援的銀行對應編號
     *
     * @var array
     */
    protected $withdrawBankMap = [
        1 => '中国工商银行', // 工商銀行
        2 => '交通银行', // 交通銀行
        3 => '中国农业银行', // 農業銀行
        4 => '中国建设银行', // 建設銀行
        5 => '招商银行', // 招商銀行
        6 => '民生银行', // 民生銀行
        8 => '上海浦东发展银行', // 浦發銀行
        9 => '北京银行', // 北京銀行
        10 => '兴业银行', // 興業銀行
        11 => '中信银行', // 中信銀行
        12 => '中国光大银行', // 光大銀行
        13 => '华夏银行', // 華夏銀行
        14 => '广州发展银行', // 廣發銀行
        15 => '平安银行', // 平安銀行
        16 => '中国邮政储蓄银行', // 中國郵政儲蓄銀行
        17 => '中国银行', // 中國銀行
        19 => '上海银行', // 上海銀行
        217 => '渤海银行', // 渤海銀行
        220 => '杭州银行', // 杭州銀行
        221 => '浙商银行', // 浙商銀行
        234 => '北京农村商业银行', // 北京農商銀行
    ];

    /**
     * 出款時需要加密的參數
     *
     * @var array
     */
    protected $withdrawEncodeParams = [
        'Service',
        'Version',
        'PartnerId',
        'TradeDate',
        'TradeTime',
        'InputCharset',
        'TransCode',
        'OutTradeNo',
        'BusinessType',
        'BankCommonName',
        'AcctNo',
        'AcctName',
        'TransAmt',
        'AccountType',
        'Currency',
    ];

    /**
     * 線上出款
     */
    public function withdrawPayment()
    {
        $this->withdrawVerify();

        // 從內部給定值到參數
        foreach ($this->withdrawRequireMap as $paymentKey => $internalKey) {
            $this->withdrawRequestData[$paymentKey] = $this->options[$internalKey];
        }

        $withdrawHost = trim($this->options['withdraw_host']);

        // 額外的參數設定
        $bank = $this->withdrawRequestData['BankCommonName'];
        $this->withdrawRequestData['BankCommonName'] = $this->withdrawBankMap[$bank];
        $this->withdrawRequestData['TransAmt'] = sprintf('%.2f', $this->withdrawRequestData['TransAmt']);
        $createAt = new \Datetime($this->withdrawRequestData['TradeDate']);
        $this->withdrawRequestData['TradeDate'] = $createAt->format('Ymd');
        $this->withdrawRequestData['TradeTime'] = $createAt->format('His');

        // 單號串上商號和流水號
        $this->withdrawRequestData['OutTradeNo'] .= $this->withdrawRequestData['PartnerId'];
        $this->withdrawRequestData['OutTradeNo'] .= strval(rand(0, 9999));

        // 參數額外加密
        $needEncryptData = ['AcctNo', 'AcctName'];

        foreach($needEncryptData as $index){
            $encryptStr = '';
            openssl_public_encrypt($this->withdrawRequestData[$index], $encryptStr, $this->getRsaPublicKey());
            $this->withdrawRequestData[$index] = base64_encode($encryptStr);
        }

        // 設定出款需要的加密串
        $this->withdrawRequestData['Sign'] = $this->withdrawEncode();

        // 出款
        $curlParam = [
            'method' => 'GET',
            'uri' => '/mag-unify/gateway/receiveOrder.do',
            'ip' => $this->options['verify_ip'],
            'host' => $withdrawHost,
            'param' => http_build_query($this->withdrawRequestData),
            'header' => [],
            'timeout' => 60,
        ];

        $result = $this->curlRequest($curlParam);
        $parseData = json_decode($result, true);

        if (!isset($parseData['AcceptStatus'])) {
            throw new PaymentException('No withdraw return parameter specified', 150180209);
        }

        // 檢查受理狀態，S表示成功，但不代表業務處理結果成功
        if ($parseData['AcceptStatus'] == 'F' && isset($parseData['PlatformErrorMessage'])) {
            throw new PaymentConnectionException($parseData['PlatformErrorMessage'], 180124, $this->getEntryId());
        }

        if ($parseData['AcceptStatus'] != 'S') {
            throw new PaymentConnectionException('Withdraw error', 180124, $this->getEntryId());
        }

        if (!isset($parseData['OriginalRetCode']) || !isset($parseData['OriginalErrorMessage'])) {
            throw new PaymentException('No withdraw return parameter specified', 150180209);
        }

        // OriginalRetCode為000000代表系統受理成功；000001可能銀行端有延遲，但後來交易成功
        if (!in_array($parseData['OriginalRetCode'], ['000000', '000001'], true)) {
            throw new PaymentConnectionException($parseData['OriginalErrorMessage'], 180124, $this->getEntryId());
        }

        // 紀錄出款明細的支付平台參考編號
        if (isset($parseData['FlowNo'])) {
            $this->setCashWithdrawEntryRefId($parseData['FlowNo']);
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

        ksort($encodeData);

        $encodeStr = urldecode(http_build_query($encodeData));
        $sign = '';

        if (!openssl_sign($encodeStr, $sign, $this->getRsaPrivateKey())) {
            throw new PaymentException('Generate signature failure', 180144);
        }

        return base64_encode($sign);
    }
}

<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * DinDin
 */
class DinDin extends PaymentBase
{
    /**
     * 出款時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $withdrawRequestData = [
        'LoginAccount'  => '', // 操作員帳號
        'GetFundInfo' => '', // 加密後的申請訊息
    ];

    /**
     * 出款時加密前的申請訊息
     *
     * @var array
     */
    protected $withdrawInfoData = [
        'Id' => '0', // 預設值
        'IntoAccount' => '', // 轉入卡號
        'IntoName' => '', // 轉入姓名
        'IntoBank1' => '', // 轉入銀行
        'IntoBank2' => '', // 轉入分行，可空
        'IntoAmount' => '', // 轉入金額，到小數點第二位
        'SerialNumber' => '', // 訂單號
    ];

    /**
     * 出款時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $withdrawRequireMap = [
        'IntoAccount' => 'account',
        'IntoName' => 'nameReal',
        'IntoBank1' => 'bank_info_id',
        'IntoAmount' => 'amount',
        'SerialNumber' => 'orderId',
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
        6 => 'CMBC', // 民生銀行
        8 => 'SPDB', // 浦發銀行
        9 => 'BOB', // 北京銀行
        10 => 'CIB', // 興業銀行
        11 => 'CITIC', // 中信銀行
        12 => 'CEB', // 光大銀行
        13 => 'HXB', // 華夏銀行
        14 => 'GDB', // 廣發銀行
        15 => 'PAB', // 平安銀行
        16 => 'PSBC', // 中國郵政儲蓄銀行
        17 => 'BOC', // 中國銀行
        19 => 'BOS', // 上海銀行
        217 => 'CBHB', // 渤海銀行
        219 => 'GZB', // 廣州銀行
        218 => 'BOD', // 東莞銀行
        220 => 'HZB', // 杭州銀行
        221 => 'CZB', // 浙商銀行
        234 => 'BJRCB', // 北京農商銀行
    ];

    /**
     * 出款查詢時要提交給支付平台的參數
     *
     * @var array
     */
    protected $withdrawTrackingRequestData = [
        'LoginAccount' => '', // 操作員帳號
        'Id' => '', // 平台流水號
    ];

    /**
     * 出款查詢時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $withdrawTrackingRequireMap = [
        'Id' => 'ref_id',
    ];

    /**
     * 出款查詢時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $withdrawTrackingDecodeParams = [
        'Id' => 1,
        'RolloutAccount' => 1,
        'IntoAccount' => 1,
        'IntoName' => 1,
        'IntoBank1' => 1,
        'IntoAmount' => 1,
        'RecordsState' => 1,
        'Tip' => 1,
        'ApplicationTime' => 1,
        'ProcessingTime' => 1,
        'SerialNumber' => 1,
        'beforeMoney' => 1,
        'afterMoney' => 1,
        'bankNumber' => 0,
    ];

    /**
     * 線上出款
     */
    public function withdrawPayment()
    {
        // 驗證私鑰
        $this->verifyPrivateKey();

        $this->withdrawVerify();

        // 從內部給定值到參數
        foreach ($this->withdrawRequireMap as $paymentKey => $internalKey) {
            $this->withdrawInfoData[$paymentKey] = $this->options[$internalKey];
        }

        $withdrawHost = trim($this->options['withdraw_host']);

        // 取得商家附加設定值
        $merchantExtraValues = $this->getMerchantExtraValue(['key2', 'ID', 'CardNum']);

        // 額外的參數設定
        $this->withdrawInfoData['IntoBank1'] = $this->withdrawBankMap[$this->withdrawInfoData['IntoBank1']];
        $this->withdrawInfoData['IntoAmount'] = sprintf('%.2f', $this->withdrawInfoData['IntoAmount']);
        $this->withdrawRequestData['LoginAccount'] = $merchantExtraValues['ID'];

        // 檢查餘額是否足夠出款
        $params = [
            'CardNum' => $merchantExtraValues['CardNum'],
            'LoginName' => $merchantExtraValues['ID'],
        ];

        $curlBalanceParam = [
            'method' => 'POST',
            'uri' => '/8001/Customer.asmx/GetBalances',
            'ip' => $this->options['verify_ip'],
            'host' => $withdrawHost,
            'param' => http_build_query($params),
            'header' => [],
            'timeout' => 60,
        ];

        $balanceResult = $this->curlRequest($curlBalanceParam);
        $balance = $this->xmlToArray($balanceResult);

        // 回傳值小於0代表異常
        if ($balance[0] < 0) {
            throw new PaymentConnectionException($balance[0], 180124, $this->getEntryId());
        }

        if ($balance[0] < $this->withdrawInfoData['IntoAmount']) {
            throw new PaymentException('Insufficient balance', 150180197);
        }

        // 設定出款需要的加密串
        $this->withdrawRequestData['GetFundInfo'] = $this->withdrawEncode($merchantExtraValues['key2']);

        // 出款
        $curlParam = [
            'method' => 'POST',
            'uri' => '/8001/Customer.asmx/GetFund',
            'ip' => $this->options['verify_ip'],
            'host' => $withdrawHost,
            'param' => http_build_query($this->withdrawRequestData),
            'header' => [],
        ];

        $result = $this->curlRequest($curlParam);
        $parseData = $this->xmlToArray($result);

        // 回傳值小於0代表異常
        if ($parseData[0] <= 0) {
            throw new PaymentConnectionException($parseData[0], 180124, $this->getEntryId());
        }

        // 紀錄出款明細的支付平台參考編號
        $this->setCashWithdrawEntryRefId($parseData[0]);
    }

    /**
     * 出款訂單查詢
     */
    public function withdrawTracking()
    {
        // 驗證私鑰
        $this->verifyPrivateKey();

        $this->withdrawTrackingVerify();

        // 從內部給定值到參數
        foreach ($this->withdrawTrackingRequireMap as $paymentKey => $internalKey) {
            $this->withdrawTrackingRequestData[$paymentKey] = $this->options[$internalKey];
        }

        // 取得商家附加設定值
        $merchantExtraValues = $this->getMerchantExtraValue(['key2', 'ID']);

        // 設定訂單查詢提交參數
        $this->withdrawTrackingRequestData['LoginAccount'] = $merchantExtraValues['ID'];

        $withdrawHost = trim($this->options['withdraw_host']);

        $curlParam = [
            'method' => 'POST',
            'uri' => '/8001/Customer.asmx/ExitTransferInfomationModel',
            'ip' => $this->options['verify_ip'],
            'host' => $withdrawHost,
            'param' => http_build_query($this->withdrawTrackingRequestData),
            'header' => [],
        ];

        // 取得訂單查詢結果
        $result = $this->curlRequest($curlParam);

        $parseData = $this->xmlToArray(urlencode($result));

        // 解密
        $xml = $this->withdrawDecode($merchantExtraValues['key2'], $parseData[0]);
        $data = $this->xmlToArray($xml);

        // Id為0或空白，代表系統查不出任何結果
        if ($data['Id'] == 0) {
            throw new PaymentException('Withdraw tracking failed', 150180198);
        }

        $this->withdrawTrackingResultVerify($data);

        // 2為成功，其他皆為出款失敗，將錯誤訊息印出
        if ($data['RecordsState'] != 2) {
            throw new PaymentException($data['Tip'], 150180201);
        }

        if ($data['SerialNumber'] != $this->options['orderId']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($data['IntoAmount'] != $this->options['auto_withdraw_amount']) {
            throw new PaymentException('Order Amount error', 180058);
        }
    }

    /**
     * 出款時的加密
     *
     * @param string $key2
     * @return string
     */
    protected function withdrawEncode($key2)
    {
        $xml = $this->arrayToXml($this->withdrawInfoData, [], 'TransferInformation');
        $parseXml = str_replace('<?xml version="1.0"?>', '', $xml);

        // 產生唯一標識碼
        $md5Hash = md5(time());
        $key = base64_decode($this->privateKey);
        $iv = base64_decode($key2);

        $encodeStr = openssl_encrypt($parseXml, 'des-cbc', $key, 0, $iv);
        $encodeStr .= $md5Hash;

        return $encodeStr;
    }

    /**
     * 出款時返回資料解密
     *
     * @param string $key2
     * @param string $data
     * @return string
     */
    protected function withdrawDecode($key2, $data)
    {
        $parseStr = substr($data, 0, -32);

        $key = base64_decode($this->privateKey);
        $iv = base64_decode($key2);

        $ret = openssl_decrypt ($parseStr, 'des-cbc', $key, 0, $iv);

        return $ret;
    }
}

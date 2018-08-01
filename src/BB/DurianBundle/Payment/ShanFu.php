<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 閃付支付
 *
 * 支付驗證：
 * 1. 驗證不可為空的參數
 * 2. 設定參數
 * 3. 額外處理的參數
 * 4. 設定encodeStr(加密後的字串)
 *
 * 解密驗證：
 * 1. 驗證key
 * 2. 設定參數
 * 3. 驗證結果是否相符
 */
class ShanFu extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'MemberID' => '', // 商戶ID
        'TerminalID' => '', // 終端ID
        'InterfaceVersion' => '4.0', // 接口版本, 固定值4.0
        'KeyType' => '1', // 加密類型
        'PayID' => '', // 銀行通道編號
        'TradeDate' => '', // 訂單日期, Ymdhis
        'TransID' => '', // 訂單號
        'OrderMoney' => '', // 訂單金額, 單位：分
        'Username' => '', // 支付用戶名
        'NoticeType' => '1', // 通知類型, 固定數字：1
        'PageUrl' => '', // 通知商戶頁面端地址
        'ReturnUrl' => '', // 服務器底層通知地址
        'Md5Sign' => '', // 簽名
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'MemberID' => 'number',
        'PayID' => 'paymentVendorId',
        'TradeDate' => 'orderCreateDate',
        'TransID' => 'orderId',
        'OrderMoney' => 'amount',
        'Username' => 'username',
        'PageUrl' => 'notify_url',
        'ReturnUrl' => 'notify_url',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'MemberID',
        'PayID',
        'TradeDate',
        'TransID',
        'OrderMoney',
        'PageUrl',
        'ReturnUrl',
        'NoticeType',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'MemberID' => 1,
        'TerminalID' => 1,
        'TransID' => 1,
        'Result' => 1,
        'ResultDesc' => 1,
        'FactMoney' => 1,
        'AdditionalInfo' => 1,
        'SuccTime' => 1,
    ];

    /**
     * 應答機制訊息
     *
     * @var string
     */
    protected $msg = 'OK';

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        '1' => '3002', // 工商銀行
        '2' => '3020', // 交通銀行
        '3' => '3005', // 農業銀行
        '4' => '3003', // 建設銀行
        '5' => '3001', // 招商銀行
        '6' => '3006', // 民生銀行
        '8' => '3004', // 上海浦東發展銀行
        '9' => '3032', // 北京銀行
        '10' => '3009', // 興業銀行
        '11' => '3039', // 中信銀行
        '12' => '3022', // 光大銀行
        '13' => '3050', // 華夏銀行
        '14' => '3036', // 廣東發展銀行
        '15' => '3035', // 平安銀行
        '16' => '3038', // 中國郵政儲蓄
        '17' => '3026', // 中國銀行
        '19' => '3059', // 上海銀行
        '217' => '', // 渤海銀行
        '226' => '', // 南京銀行
        '228' => '3037', // 上海農村商業銀行
        '234' => '3060', // 北京農村商業銀行
        '315' => '', // 河北銀行
        '1090' => '57', // 微信支付
        '1092' => '758', // 支付寶_二維
        '1103' => '77', // QQ_二維
        '1111' => '17', // 銀聯_二維
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

        // 驗證支付參數
        $this->payVerify();

        $this->options['notify_url'] = sprintf(
            '%s?pay_system=%s&hallid=%s',
            $this->options['notify_url'],
            $this->options['merchantId'],
            $this->options['domain']
        );

        // 從內部給定值到參數
        foreach ($this->requireMap as $paymentKey => $internalKey) {
            $this->requestData[$paymentKey] = $this->options[$internalKey];
        }

        // 帶入未支援的銀行就噴例外
        if (!array_key_exists($this->requestData['PayID'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }


        // 額外的參數設定
        $date = new \DateTime($this->requestData['TradeDate']);
        $this->requestData['TradeDate'] = $date->format('YmdHis');
        $this->requestData['PayID'] = $this->bankMap[$this->requestData['PayID']];
        $this->requestData['OrderMoney'] = round($this->requestData['OrderMoney'] * 100); // 單位為分

        // 商家額外的參數設定
        $names = ['TerminalID'];
        $extra = $this->getMerchantExtraValue($names);
        $this->requestData['TerminalID'] = $extra['TerminalID'];

        // 設定支付平台需要的加密串
        $this->requestData['Md5Sign'] = $this->encode();

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

        // 驗證返回參數
        $this->payResultVerify();

        $encodeData = [];

        //　加密設定
        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options)) {
                $encodeData[] = $paymentKey . '=' . $this->options[$paymentKey];
            }
        }

        //　額外的加密設定
        $encodeData[] = 'Md5Sign=' . $this->privateKey;
        $encodeStr = implode('~|~', $encodeData);

        // 沒有Md5Sign就要丟例外，其他的參數在組織加密串的時候驗證過了
        if (!isset($this->options['Md5Sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['Md5Sign'] != strtolower(md5($encodeStr))) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['Result'] != '1' || $this->options['ResultDesc'] != '01') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['TransID'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['FactMoney'] != round($entry['amount'] * 100)) {
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

        //加密設定
        foreach ($this->encodeParams as $index) {
            $encodeData[] = $this->requestData[$index];
        }

        //額外的加密設定
        $encodeData[] = $this->privateKey;
        $encodeStr = implode('|', $encodeData);

        return strtolower(md5($encodeStr));
    }
}

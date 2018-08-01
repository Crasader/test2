<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 寶付手機支付
 */
class BaoFooWap extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'MemberID' => '', // 商戶號
        'TerminalID' => '', // 終端號
        'InterfaceVersion' => '4.0', // 接口版本號
        'PayID' => '', // 支付ID
        'TradeDate' => '', // 交易時間(格式：YmdHis)
        'TransID' => '', // 訂單號
        'OrderMoney' => '', // 金額(單位：分)
        'ProductName' => '', // 商品名稱，可為空
        'Amount' => '', // 商品數量，可為空
        'UserName' => '', // 用戶名稱，可為空
        'AdditionalInfo' => '', // 附加訊息，可為空
        'PageUrl' => '', // 頁面通知地址
        'ReturnUrl' => '', // 服務器通知地址
        'NoticeType' => '0', // 通知方式(0:伺服器通知)
        'KeyType' => '1', // 加密類型(1:MD5)
        'Signature' => '', // Md5簽名
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
        'PageUrl' => 'notify_url',
        'ReturnUrl' => 'notify_url',
        'UserName' => 'username',
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
        1004 => '4020020', // 交通銀行
        1006 => '4020003', // 建設銀行
        1007 => '4020001', // 招商銀行
        1018 => '4020026', // 中國銀行
        1088 => '4020080', // 銀聯在線
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

        // 檢查銀行代碼是否支援
        if (!array_key_exists($this->requestData['PayID'], $this->bankMap)) {
            throw new PaymentException(
                'PaymentVendor is not supported by PaymentGateway',
                180066
            );
        }

        // 取得商家附加設定值
        $merchantExtraValues = $this->getMerchantExtraValue(['terminalId']);

        // 額外的參數設定
        $this->requestData['PayID'] = $this->bankMap[$this->requestData['PayID']];
        $date = new \DateTime($this->requestData['TradeDate']);
        $this->requestData['TradeDate'] = $date->format("YmdHis");
        $this->requestData['OrderMoney'] = round($this->requestData['OrderMoney'] * 100);
        $this->requestData['TerminalID'] = $merchantExtraValues['terminalId'];

        // 設定加密簽名
        $this->requestData['Signature'] = $this->encode();

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
                $encodeData[] = $paymentKey . '=' . $this->options[$paymentKey];
            }
        }

        // 進行加密
        $encodeData[] = 'Md5Sign=' . $this->privateKey;
        $encodeStr = implode('~|~', $encodeData);
        $SignStr = md5($encodeStr);

        // 沒有Md5Sign就要丟例外，其他的參數在組織加密串的時候驗證過了
        if (!isset($this->options['Md5Sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['Md5Sign'] != $SignStr) {
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

        foreach ($this->encodeParams as $index) {
            $encodeData[] = $this->requestData[$index];
        }

        $encodeData[] = $this->privateKey;
        $encodeStr = implode('|', $encodeData);

        return md5($encodeStr);
    }
}

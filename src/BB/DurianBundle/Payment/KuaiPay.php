<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 快支付
 */
class KuaiPay extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'MemberID' => '', // 商號
        'TerminalID' => '10066008', // 終端號，固定值
        'InterfaceVersion' => '4.0', // 接口版本，固定值
        'KeyType' => '1', // 加密類型，MD5
        'PayID' => '', // 銀行預留參數
        'TradeDate' => '', // 訂單日期(YmdHis)
        'TransID' => '', // 訂單號
        'OrderMoney' => '', // 訂單金額，單位元，精確到小數後兩位
        'ProductName' => '', // 商品名稱，可為空
        'Amount' => '1', // 商品數量
        'Username' => '', // 支付用戶名，可為空
        'AdditionalInfo' => '', // 訂單附加訊息，可為空
        'PageUrl' => '', // 同步通知網址
        'ReturnUrl' => '', // 異步通知網址
        'ResultType' => '', // 返回類型，可為空
        'PayType' => 'ONLINE_BANK_PAY', // 支付類型，網銀：ONLINE_BANK_PAY
        'Signature' => '', // 簽名
        'NoticeType' => '1', // 通知方式, 異步和同步通知：1
        'bankId' => '', // 銀行代碼(網銀專用)
        'showPayTypes' => '', // 收銀台顯示方式，可為空
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'MemberID' => 'number',
        'TradeDate' => 'orderCreateDate',
        'TransID' => 'orderId',
        'OrderMoney' => 'amount',
        'PageUrl' => 'notify_url',
        'ReturnUrl' => 'notify_url',
        'bankId' => 'paymentVendorId',
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
    protected $msg = 'SUCCESS';

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        '1' => 'ICBC-NET-B2C', // 中國工商銀行
        '2' => 'BOCO-NET-B2C', // 交通銀行
        '3' => 'ABC-NET-B2C', // 中國農業銀行
        '4' => 'CCB-NET-B2C', // 中國建設銀行
        '5' => 'CMBCHINA-NET-B2C', // 招商銀行
        '6' => 'CMBC-NET-B2C', // 中國民生銀行
        '8' => 'SPDB-NET-B2C', // 上海浦東發展銀行
        '9' => 'BCCB-NET-B2C', // 北京銀行
        '10' => 'CIB-NET-B2C', // 興業銀行
        '11' => 'ECITIC-NET-B2C', // 中信銀行
        '12' => 'CEB-NET-B2C', // 中國光大銀行
        '13' => 'GFB-HXBC', // 華夏銀行
        '14' => 'GDB-NET-B2C', // 廣東發展銀行
        '15' => 'PINGANBANK-NET', // 平安銀行
        '16' => 'POST-NET-B2C', // 中國郵政儲蓄銀行
        '17' => 'BOC-NET-B2C', // 中國銀行
        '220' => 'HZBANK-NET-B2C', // 杭州銀行
        '222' => 'NBCB-NET-B2C', // 寧波銀行
        '226' => 'NJCB-NET-B2C', // 南京銀行
        '234' => 'BJRCB-NET-B2C', // 北京農村商業銀行
        '278' => 'UNION_DIRECT_PAY', // 銀聯在線(快捷)
        '1088' => 'UNION_DIRECT_PAY', // 銀聯在線_手機支付(快捷)
        '1090' => 'WECHAT_QRCODE_PAY', // 微信_二維
        '1098' => 'ALIPAY_WAP_PAY', // 支付寶_手機支付
        '1108' => 'JD_WAP_PAY', // 京東_手機支付
    ];

    /**
     * 回傳支付時要給支付平台驗證的資料
     *
     * @return array
     */
    public function getVerifyData()
    {
        $this->verifyPrivateKey();
        $this->payVerify();

        // 從內部給定值到參數
        foreach ($this->requireMap as $paymentKey => $internalKey) {
            $this->requestData[$paymentKey] = $this->options[$internalKey];
        }

        // 帶入未支援的銀行就噴例外
        if (!array_key_exists($this->requestData['bankId'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $date = new \DateTime($this->requestData['TradeDate']);
        $this->requestData['TradeDate'] = $date->format('YmdHis');
        $this->requestData['bankId'] = $this->bankMap[$this->requestData['bankId']];
        $this->requestData['OrderMoney'] = sprintf('%.2f', $this->requestData['OrderMoney']);

        // 銀聯在線、二維支付、手機支付需調整參數
        if (in_array($this->options['paymentVendorId'], ['278', '1088', '1090', '1098', '1108'])) {
            $this->requestData['PayType'] = $this->requestData['bankId'];
            $this->requestData['bankId'] = '';
        }

        // 設定支付平台需要的加密串
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
        $this->verifyPrivateKey();
        $this->payResultVerify();

        $encodeData = [];

        // 組織加密串
        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options)) {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }
        $encodeData['Md5Sign'] = $this->privateKey;

        // 依key1=value1~|~key2=value2~|~...~|~keyN=valueN
        $encodeStr = urldecode(http_build_query($encodeData, '', '~|~'));

        // 如果沒有簽名也要丟例外(其他參數都在前面驗證過了，所以不須要驗證)
        if (!isset($this->options['Md5Sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['Md5Sign'] !== md5($encodeStr)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['Result'] !== '1') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['TransID'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['FactMoney'] != $entry['amount']) {
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

        $encodeStr = implode('~|~', $encodeData);

        return md5($encodeStr);
    }
}

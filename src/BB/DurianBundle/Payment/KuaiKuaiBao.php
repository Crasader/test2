<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 快快寶支付
 */
class KuaiKuaiBao extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'MemberID' => '', // 商號
        'TerminalID' => '', // 終端號
        'InterfaceVersion' => '4.0', // 接口版本，固定值 4.0
        'KeyType' => '1', // 加密類型
        'PayID' => '', // 銀行通道編號
        'TradeDate' => '', // 訂單日期(Ymdhis)
        'TransID' => '', // 訂單號
        'OrderMoney' => '', // 訂單金額，單位:分
        'ProductName' => '', // 商品名稱(可空)
        'Amount' => '1', // 商品數量
        'Username' => '', // 支付用戶名(可空)
        'AdditionalInfo' => '', // 附加訊息(可空)
        'NoticeType' => '1', // 通知類型, 固定數字：1
        'PageUrl' => '', // 頁面返回地址
        'ReturnUrl' => '', // 異步通知地址
        'Signature' => '', // 簽名
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
        '16' => '3038', // 中國郵政儲蓄
        '17' => '3026', // 中國銀行
        '19' => '3059', // 上海銀行
        '1090' => '1801', // 微信二維
        '1092' => '1901', // 支付寶二維
        '1103' => '1802', // QQ錢包
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
        if (!array_key_exists($this->options['paymentVendorId'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $date = new \DateTime($this->requestData['TradeDate']);
        $extra = $this->getMerchantExtraValue(['TerminalID']);
        $this->requestData['TradeDate'] = $date->format('YmdHis');
        $this->requestData['TerminalID'] = $extra['TerminalID'];
        $this->requestData['PayID'] = $this->bankMap[$this->requestData['PayID']];
        $this->requestData['OrderMoney'] = round($this->requestData['OrderMoney'] * 100);

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

        // 加密設定
        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options)) {
                $encodeData[] = $paymentKey . '=' . $this->options[$paymentKey];
            }
        }

        // 通知返回的加密設定
        $encodeData[] = 'Md5Sign=' . $this->privateKey;
        $encodeStr = implode('~|~', $encodeData);

        // 檢查簽名
        if (!isset($this->options['Md5Sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['Md5Sign'] !== md5($encodeStr)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        // 返回非成功
        if ($this->options['Result'] != '1') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        // 檢查單號
        if ($this->options['TransID'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        // 檢查金額
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

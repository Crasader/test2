<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 新寶付
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
class NewBaoFoo extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'MemberID'         => '', //商號
        'TerminalID'       => '', //終端號
        'InterfaceVersion' => '4.0', //接口版本號
        'KeyType'          => '1', //加密類型(1:MD5)
        'PayID'            => '', //功能ID
        'TradeDate'        => '', //交易日期(格式：YmdHis)
        'TransID'          => '', //訂單號
        'OrderMoney'       => '', //金額(單位：分)
        'ProductName'      => '', //商品名稱
        'Amount'           => '', //數量
        'Username'         => '', //用戶名稱
        'AdditionalInfo'   => '', //訂單附加訊息
        'PageUrl'          => '', //通知商戶url
        'ReturnUrl'        => '', //底層通知url
        'Signature'        => '', //Md5簽名
        'NoticeType'       => '0' //通知方式(0:伺服器通知)
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
        'ProductName' => 'username',
        'Username' => 'username'
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
        'NoticeType'
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
        'SuccTime' => 1
    ];

    /**
     * 應答機制訊息
     *
     * @var string
     */
    protected $msg = 'OK';

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

        // 取得商家附加設定值
        $merchantExtraValues = $this->getMerchantExtraValue(['terminalId']);

        //額外的參數設定
        $date = new \DateTime($this->requestData['TradeDate']);
        $this->requestData['TradeDate'] = $date->format("YmdHis");
        $this->requestData['OrderMoney'] = round($this->requestData['OrderMoney'] * 100);
        $this->requestData['TerminalID'] = $merchantExtraValues['terminalId'];

        //設定支付平台需要的加密串
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

        //進行加密
        $encodeData[] = 'Md5Sign=' . $this->privateKey;
        $encodeStr = implode('~|~', $encodeData);
        $encodeStr = md5($encodeStr);

        //沒有Md5Sign就要丟例外，其他的參數在組織加密串的時候驗證過了
        if (!isset($this->options['Md5Sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['Md5Sign'] != $encodeStr) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['Result'] != '1' || $this->options['ResultDesc'] != '01') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['TransID'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['FactMoney']  != round($entry['amount'] * 100)) {
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

        return md5($encodeStr);
    }
}

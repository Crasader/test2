<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 寶付支付
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
 *
 * @author sweet <pigsweet7834@gmail.com>
 */
class BaoFoo extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'MerchantID'     => '',   //商號
        'PayID'          => 1000, //支付渠道
        'TradeDate'      => '',   //交易日期
        'TransID'        => '',   //訂單編號
        'OrderMoney'     => '',   //訂單金額
        'ProductName'    => '',   //商品名稱
        'Amount'         => 1,    //商品數量
        'ProductLogo'    => '',   //商品圖片url
        'Username'       => '',   //支付用戶名稱
        'Email'          => '',   //用戶電子郵件
        'Mobile'         => '',   //用戶手機
        'AdditionalInfo' => '',   //訂單附加訊息
        'Merchant_url'   => '',   //通知商戶url
        'Return_url'     => '',   //底層通知url
        'Md5Sign'        => '',   //Md5簽名
        'NoticeType'     => 0     //通知方式，0: 伺服器通知，1: 伺服器通知及網頁通知
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'MerchantID' => 'number',
        'TradeDate' => 'orderCreateDate',
        'TransID' => 'orderId',
        'OrderMoney' => 'amount',
        'ProductName' => 'username',
        'Username' => 'username',
        'Merchant_url' => 'notify_url',
        'Return_url' => 'notify_url'
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'MerchantID',
        'PayID',
        'TradeDate',
        'TransID',
        'OrderMoney',
        'Merchant_url',
        'Return_url',
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
        'MerchantID' => 1,
        'TransID' => 1,
        'Result' => 1,
        'resultDesc' => 1,
        'factMoney' => 1,
        'additionalInfo' => 1,
        'SuccTime' => 1
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
        '1'  => 'ICBC',  //中國工商銀行
        '2'  => 'BOCOM', //交通銀行
        '3'  => 'ABC',   //中國農業銀行
        '4'  => 'CCB',   //中國建設銀行
        '5'  => 'CMB',   //招商銀行
        '6'  => 'CMBC',  //中國民生銀行
        '7'  => 'SDB',   //深圳發展銀行
        '8'  => 'SPDB',  //上海浦東發展銀行
        '9'  => 'BOBJ',  //北京銀行
        '10' => 'CIB',   //興業銀行
        '11' => 'CNCB',  //中信銀行
        '12' => 'CEB',   //中國光大銀行
        '14' => 'GDB',   //廣東發展銀行
        '17' => 'BOCSH'  //中國銀行
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

        //額外的參數設定
        $date = new \DateTime($this->options['orderCreateDate']);
        $this->requestData['TradeDate'] = $date->format("YmdHis");
        $this->requestData['OrderMoney'] = round($this->options['amount'] * 100);

        //設定支付平台需要的加密串
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

        $this->payResultVerify();

        $encodeStr = '';

        //組織加密串
        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options)) {
                $encodeStr .= $this->options[$paymentKey];
            }
        }

        //進行加密
        $encodeStr .= $this->privateKey;
        $encodeStr = md5($encodeStr);

        //沒有Md5Sign就要丟例外，其他的參數在組織加密串的時候驗證過了
        if (!isset($this->options['Md5Sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['Md5Sign'] != $encodeStr) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['Result'] != '1' || $this->options['resultDesc'] !== '01') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['TransID'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['factMoney'] != round($entry['amount'] * 100)) {
            throw new PaymentException('Order Amount error', 180058);
        }
    }
}

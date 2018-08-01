<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 支付衛士
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
class Weishih extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'userID'      => '', //使用者id(存商號)
        'viewID'      => '78881765305982', //支付平台內部使用
        'systemAppID' => '3929805116271', //支付平台內部使用
        'styleID'     => '7888390597618', //支付平台內部使用
        'orderId'     => '', //訂單號
        'amt'         => '', //金額
        'url'         => '', //支付成功回傳網址
        'bank'        => '', //銀行編碼，預設空字串，用對方頁面提供的銀行代碼
        'name'        => '', //交易界面顯示的名稱(存使用者名稱)
        'cur'         => 'RMB', //幣別
        'hmac'        => '' //Md5簽名
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'userID' => 'number',
        'orderId' => 'orderId',
        'amt' => 'amount',
        'url' => 'notify_url',
        'name' => 'username',
        'bank' => 'paymentVendorId'
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'userID',
        'orderId',
        'amt'
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'userID' => 1,
        'orderId' => 1,
        'amt' => 1,
        'succ' => 1
    ];

    /**
     * 應答機制訊息
     *
     * @var string
     */
    protected $msg = '[success]';

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        1   => 'R', //工商银行
        2   => 'F', //交通银行
        3   => 'U', //农业银行
        4   => 'T', //建设银行
        5   => 'P', //招商银行
        6   => 'G', //民生银行
        7   => 'K', //深圳發展銀行
        8   => 'I', //浦发银行
        9   => 'A', //北京银行
        10  => 'N', //兴业银行
        11  => 'W', //中信銀行
        12  => 'S', //光大银行
        13  => 'E', //華夏銀行
        14  => 'B', //广发银行
        15  => 'H', //平安银行
        16  => 'O', //中国邮政储蓄银行
        17  => 'V', //中國銀行
        19  => 'Z', //上海银行
        217 => 'AC', //渤海銀行
        220 => 'AE', //杭州銀行
        221 => 'Y', //浙商銀行
        222 => 'AA', //寧波銀行
        223 => 'AB', //東亞銀行
        226 => 'AD', //南京銀行
        227 => 'C', //廣州市農村信用合作社
        228 => 'J', //上海農村商業銀行
        231 => 'M', //順德農信社
        234 => 'X' //北京農村商業銀行
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

        //帶入未支援的銀行就噴例外
        if (!array_key_exists($this->requestData['bank'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        //額外的參數設定。金額*1是為了去除小數點後面的0。(ex: 1.1000 => 1.1)
        $this->requestData['amt'] = $this->requestData['amt'] * 1;
        $this->requestData['bank'] = $this->bankMap[$this->requestData['bank']];

        //設定支付平台需要的加密串
        $this->requestData['hmac'] = $this->encode();

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
                $encodeData[] = $this->options[$paymentKey];
            }
        }

        //進行加密
        $encodeData[] = $this->privateKey;
        $encodeStr = md5(implode('&', $encodeData));

        //沒有hmac2就要丟例外，其他的參數在組織加密串的時候驗證過了
        if (!isset($this->options['hmac2'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['hmac2'] != $encodeStr) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['succ'] != 'Y') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['orderId'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['amt'] != $entry['amount']) {
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
        $encodeStr = implode('&', $encodeData);

        return md5($encodeStr);
    }
}

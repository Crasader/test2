<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 雲服務-快匯寶
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
class Cldinpay extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'systemAppID' => '3929805116271',  //內部使用
        'viewID'      => '78881765305982', //內部使用
        'styleID'     => '7888390597618',  //內部使用
        'userID'      => '',               //用戶編號
        'name'        => '',               //交易界面顯示的名稱
        'orderId'     => '',               //訂單編號
        'amt'         => '',               //訂單金額
        'bank'        => '0',              //銀行編碼
        'url'         => '',               //支付成功的接收地扯
        'cur'         => 'RMB',            //幣種
        'des'         => ''                //用戶擴展信息
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
        'des' => 'domain',
        'name' => 'username'
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

        //去除小數點後面的0。(ex: 1.1000 => 1.1)
        $this->requestData['amt'] = round($this->requestData['amt'], 4);

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
        $encodeStr = implode('&', $encodeData);

        //沒有hmac2就要丟例外，其他的參數在組織加密串的時候驗證過了
        if (!isset($this->options['hmac2'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['hmac2'] != md5($encodeStr)) {
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
}

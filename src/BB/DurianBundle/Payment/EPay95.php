<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 95epay支付
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
class EPay95 extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'Amount'      => '', //金額
        'BillNo'      => '', //訂單編號
        'MerNo'       => '', //商家號
        'ReturnURL'   => '', //支付成功導回的URL
        'PayType'     => 'CSPAY', //支付方式，預設CSPAY網銀支付
        'MerRemark'   => '', //商家備註訊息，預設空值
        'PaymentType' => '', //付款廠商id
        'products'    => '', //商品訊息，預設空值
        'MD5info'     => '', //商戶密鑰
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'MerNo' => 'number',
        'Amount' => 'amount',
        'BillNo' => 'orderId',
        'ReturnURL' => 'notify_url',
        'PaymentType' => 'paymentVendorId'
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'MerNo',
        'BillNo',
        'Amount',
        'ReturnURL',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'MerNo' => 1,
        'BillNo' => 1,
        'Amount' => 1,
        'Succeed' => 1
    ];

    /**
     * 應答機制訊息
     *
     * @var string
     */
    protected $msg = 'Succeed';

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        '1' => 'ICBC', //中國工商銀行
        '2' => 'BOCOM', //交通銀行
        '3' => 'ABC', //中國農業銀行
        '4' => 'CCB', //中國建設銀行
        '5' => 'CMB', //招商銀行
        '6' => 'CMBC', //中國民生銀行
        '8' => 'SPDB', //上海浦東發展銀行
        '9' => 'BCCB', //北京銀行
        '10' => 'CIB', //興業銀行
        '11' => 'CNCB', //中信銀行
        '12' => 'CEB', //中國光大銀行
        '13' => 'HXB', //華夏銀行
        '14' => 'GDB', //廣東發展銀行
        '15' => 'PAB', //平安銀行
        '16' => 'PSBC', //中國郵政
        '17' => 'BOCSH', //中國銀行
        '19' => 'BOS', //上海銀行
        '228' => 'SRCB', //上海農村商業銀行
        '234' => 'BRCB' //北京農村商業銀行
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
            '%s?payment_id=%s',
            $this->options['notify_url'],
            $this->options['paymentGatewayId']
        );

        // 從內部給定值到參數
        foreach ($this->requireMap as $paymentKey => $internalKey) {
            $this->requestData[$paymentKey] = $this->options[$internalKey];
        }

        //帶入未支援的銀行就噴例外
        if (!array_key_exists($this->requestData['PaymentType'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        //額外的參數設定
        $this->requestData['PaymentType'] = $this->bankMap[$this->requestData['PaymentType']];
        $this->requestData['Amount'] = sprintf("%.2f", $this->requestData['Amount']);

        //設定支付平台需要的加密串
        $this->requestData['MD5info'] = $this->encode();

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

        $decodeVerifyData = array();

        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options)) {
                $decodeVerifyData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        //進行加密
        ksort($decodeVerifyData);
        $encodeStr = http_build_query($decodeVerifyData);
        $encodeStr .= '&' . strtoupper(md5($this->privateKey));
        $encodeStr = strtoupper(md5($encodeStr));

        //沒有MD5info就要丟例外，其他的參數在組織加密串的時候驗證過了
        if (!isset($this->options['MD5info'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['MD5info'] != $encodeStr) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['Succeed'] != '88') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['BillNo'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['Amount'] != $entry['amount']) {
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
        $encodeData = array();
        sort($this->encodeParams);

        //加密設定
        foreach ($this->encodeParams as $index) {
            $encodeData[$index] = $this->requestData[$index];
        }

        //額外的加密設定
        $encodeStr = urldecode(http_build_query($encodeData));
        $encodeStr .= '&' . strtoupper(md5($this->privateKey));

        return strtoupper(md5($encodeStr));
    }
}

<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 銀支付
 *
 * 支付驗證:
 * 1. 驗證不可為空的參數
 * 2. 設定參數
 * 3. 額外處理的參數
 * 4. 設定encodeStr(加密後的字串)
 *
 * 解密驗證:
 * 1. 驗證key
 * 2. 設定參數
 * 3. 驗證結果是否相符
 */
class Ylkpay extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'p0_Cmd'          => 'Buy', //業務類型，固定值: Buy
        'p1_MerId'        => '', //商號
        'p2_Order'        => '', //訂單號
        'p3_Amt'          => '', //支付金額(精確到分)
        'p4_Cur'          => 'CNY', //幣別，固定值: CNY
        'p5_Pid'          => '', //商品名稱，非必填
        'p6_Pcat'         => '', //商品種類，非必填
        'p7_Pdesc'        => '', //商品描述，非必填
        'p8_Url'          => '', //支付成功返回url
        'p9_SAF'          => '0', //送貨地址 0: 不需要保留
        'pa_MP'           => '', //商戶擴展訊息，非必填
        'pd_FrpId'        => '', //銀行代碼
        'pr_NeedResponse' => '1', //應答機制，固定值: 1(需要應答機制)
        'hmac'            => '' //加密簽名
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'p1_MerId' => 'number',
        'p2_Order' => 'orderId',
        'p3_Amt' => 'amount',
        'p8_Url' => 'notify_url',
        'pd_FrpId' => 'paymentVendorId'
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'p0_Cmd',
        'p1_MerId',
        'p2_Order',
        'p3_Amt',
        'p4_Cur',
        'p5_Pid',
        'p6_Pcat',
        'p7_Pdesc',
        'p8_Url',
        'p9_SAF',
        'pa_MP',
        'pd_FrpId',
        'pr_NeedResponse'
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'p1_MerId' => 1, //商戶編號
        'r0_Cmd' => 1, //業務類型
        'r1_Code' => 1, //支付結果
        'r2_TrxId' => 1, //支付交易流水號
        'r3_Amt' => 1, //支付金額
        'r4_Cur' => 1, //交易幣種
        'r5_Pid' => 1, //商品名稱
        'r6_Order' => 1, //商戶訂單號
        'r7_Uid' => 1, //會員ID
        'r8_MP' => 1, //商戶擴展資訊
        'r9_BType' => 1 //交易結果返回類型
    ];

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        1   => 'ICBC', //中國工商銀行
        2   => 'BOCO', //交通銀行
        3   => 'ABC', //中國農業銀行
        4   => 'CCB', //中國建設銀行
        5   => 'CMBCHINA', //招商銀行
        6   => 'CMBC', //中國民生銀行
        8   => 'SPDB', //上海浦東發展銀行
        9   => 'BCCB', //北京银行
        10  => 'CIB', //興業銀行
        11  => 'ECITIC', //中信銀行
        12  => 'CEB', //中國光大銀行
        13  => 'HXB', //華夏銀行
        14  => 'GDB', //廣東發展銀行
        15  => 'PINGANBANK', //深圳平安銀行
        16  => 'POST', //中國郵政
        17  => 'BOC', //中國銀行
        223 => 'HKBEA', //東亞銀行
        278 => 'OnLine' //銀聯在線
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
        if (!array_key_exists($this->requestData['pd_FrpId'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        //額外的參數設定
        $this->requestData['pd_FrpId'] = $this->bankMap[$this->requestData['pd_FrpId']];
        $this->requestData['p3_Amt'] = sprintf('%.2f', $this->requestData['p3_Amt']);

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

        $encodeStr = '';

        //組織加密串
        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options)) {
                $encodeStr .= $this->options[$paymentKey];
            }
        }

        //沒有hmac就要丟例外，其他的參數在組織加密串的時候驗證過了
        if (!isset($this->options['hmac'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        //getHmac()是銀支付的加密方式
        if ($this->options['hmac'] != $this->getHmac($encodeStr)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['r1_Code'] != '1') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['r6_Order'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['r3_Amt'] != $entry['amount']) {
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
        $encodeStr = '';

        //加密設定
        foreach ($this->encodeParams as $index) {
            $encodeStr .= $this->requestData[$index];
        }

        return $this->getHmac($encodeStr);
    }

    /**
     * 銀支付產生加密簽名的方式
     *
     * @param string $data
     * @return string
     */
    private function getHmac($data)
    {
        $key = $this->privateKey;
        $byteLength = 64;

        if (strlen($key) > $byteLength) {
            $key = pack("H*", md5($key));
        }

        $keyPad = str_pad($key, $byteLength, chr(0x00));
        $ipad = str_pad('', $byteLength, chr(0x36));
        $opad = str_pad('', $byteLength, chr(0x5c));
        $keyIpad = $keyPad ^ $ipad ;
        $keyOpad = $keyPad ^ $opad;

        return md5($keyOpad . pack("H*", md5($keyIpad . $data)));
    }
}

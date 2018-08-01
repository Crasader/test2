<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 易到支付
 */
class YidaoPay extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'p0_Cmd' => 'Buy', // 業務類型
        'p1_MerId' => '', // 商號
        'p2_Order' => '', // 訂單號
        'p3_Amt' => '', // 支付金額(精確到分)
        'p4_Cur' => 'CNY', // 幣別，固定值
        'p5_Pid' => '', // 商品名稱，帶入username方便業主比對
        'p6_Pcat' => '', // 商品種類，可空
        'p7_Pdesc' => '', // 商品描述，可空
        'p8_Url' => '', // 後台通知地址
        'p9_SAF' => '', // 前台返回地址，可空
        'pa_MP' => '', // 商戶擴展訊息，可空
        'pd_FrpId' => '', // 銀行代碼
        'pr_NeedResponse' => '1', // 應答機制，固定值
        'hmac' => '', // 加密簽名
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
        'p5_Pid' => 'username',
        'p8_Url' => 'notify_url',
        'pd_FrpId' => 'paymentVendorId',
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
        'p1_MerId' => 1,
        'r0_Cmd' => 1,
        'r1_Code' => 1,
        'r2_TrxId' => 1,
        'r3_Amt' => 1,
        'r4_Cur' => 1,
        'r5_Pid' => 1,
        'r6_Order' => 1,
        'r7_Uid' => 1,
        'r8_MP' => 1,
        'r9_BType' => 1,
    ];

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        1 => 'ICBC-NET-B2C', // 中國工商銀行
        2 => 'BOCO-NET-B2C', // 交通銀行
        3 => 'ABC-NET-B2C', // 中國農業銀行
        4 => 'CCB-NET-B2C', // 中國建設銀行
        5 => 'CMBCHINA-NET-B2C', // 招商銀行
        6 => 'CMBC-NET-B2C', // 中國民生銀行
        8 => 'SPDB-NET-B2C', // 上海浦東發展銀行
        9 => 'BCCB-NET-B2C', // 北京銀行
        10 => 'CIB-NET-B2C', // 興業銀行
        11 => 'ECITIC-NET-B2C', // 中信銀行
        12 => 'CEB-NET-B2C', // 中國光大銀行
        13 => 'HXB-NET-B2C', // 華夏銀行
        14 => 'GDB-NET-B2C', // 廣東發展銀行
        15 => 'PINGANBANK-NET-B2C', // 平安銀行
        16 => 'POST-NET-B2C', // 中國郵政
        17 => 'BOC-NET-B2C', // 中國銀行
        19 => 'SHB-NET-B2C', // 上海銀行
        234 => 'BJRCB-NET-B2C', // 北京農村商業銀行
        1090 => 'WEIXINPAY', // 微信_二維
        1092 => 'ALIPAY', // 支付寶_二維
        1103 => 'QQPAY', // QQ_二維
        1111 => 'unionpayqr', // 銀聯錢包_二維
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

        // 從內部給定值到參數
        foreach ($this->requireMap as $paymentKey => $internalKey) {
            $this->requestData[$paymentKey] = $this->options[$internalKey];
        }

        // 帶入未支援的銀行就噴例外
        if (!array_key_exists($this->requestData['pd_FrpId'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $this->requestData['pd_FrpId'] = $this->bankMap[$this->requestData['pd_FrpId']];
        $this->requestData['p3_Amt'] = sprintf('%.2f', $this->requestData['p3_Amt']);

        // 設定支付平台需要的加密串
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

        // 組織加密串
        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options)) {
                $encodeStr .= $this->options[$paymentKey];
            }
        }

        // 沒有hmac就要丟例外，其他的參數在組織加密串的時候驗證過了
        if (!isset($this->options['hmac'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

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

        // 加密設定
        foreach ($this->encodeParams as $index) {
            $encodeStr .= $this->requestData[$index];
        }

        return $this->getHmac($encodeStr);
    }

    /**
     * 易寶產生加密簽名的方式
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

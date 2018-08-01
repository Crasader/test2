<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 普訊網絡
 */
class PuSyun extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'p0_Cmd' => 'Buy', // 業務類型，固定值
        'p1_MerId' => '', // 商戶編號
        'p2_Order' => '', // 商戶訂單號
        'p3_Amt' => '', // 支付金額，單位:元，精確到分
        'p4_Cur' => 'CNY', // 交易幣種，固定值
        'p5_Pid' => '', // 商品名稱，非必填
        'p6_Pcat' => '', // 商品種類，非必填
        'p7_Pdesc' => '', // 商品描述，非必填
        'p8_Url' => '', // 商戶接收支付成功資料的位址
        'p9_SAF' => '0', // 送貨地址，默認值0，不需要將送貨地址留在支付平台系統
        'pa_MP' => '', // 商戶擴展資訊，非必填
        'pd_FrpId' => '', // 支付通道編碼
        'pr_NeedResponse' => '1', // 應答機制，固定值
        'hmac' => '', // 簽名數據
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
        'pr_NeedResponse',
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
        1 => 'ICBC-NET', // 工商銀行
        2 => 'BOCO-NET', // 交通銀行
        3 => 'ABC-NET', // 農業銀行
        4 => 'CCB-NET', // 建設銀行
        5 => 'CMBCHINA-NET', // 招商銀行
        6 => 'CMBC-NET', // 民生銀行
        8 => 'SPDB-NET', // 上海浦東發展銀行
        9 => 'BCCB-NET', // 北京銀行
        10 => 'CIB-NET', // 興業銀行
        11 => 'ECITIC-NET', // 中信銀行
        12 => 'CEB-NET', // 光大銀行
        13 => 'HXB-NET', // 華夏銀行
        14 => 'GDB-NET', // 廣東發展銀行
        16 => 'POST-NET', // 中國郵政
        17 => 'BOC-NET', // 中國銀行
        1090 => 'WeiXin', // 微信支付_二維
        1092 => 'AlipayD', // 支付寶_二維
        1096 => 'tenpayd', // 財付通_二維
        1097 => 'wxwap', // 微信_手機支付
        1098 => 'AlipayWap', // 支付寶_手機支付
        1099 => 'tenpaywap', // 財付通_手機支付
        1103 => 'qqpay', // QQ_二維
        1104 => 'qqwap', // QQ_手機支付
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

        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options)) {
                $encodeStr .= $this->options[$paymentKey];
            }
        }

        // 沒有hmac就要丟例外
        if (!isset($this->options['hmac'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['hmac'] != hash_hmac('md5', $encodeStr, $this->privateKey)) {
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

        foreach ($this->encodeParams as $index) {
            $encodeStr .= $this->requestData[$index];
        }

        return hash_hmac('md5', $encodeStr, $this->privateKey);
    }
}

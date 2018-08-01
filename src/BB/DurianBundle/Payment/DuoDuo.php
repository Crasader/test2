<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 多多支付
 */
class DuoDuo extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'MerId' => '', // 商戶號
        'OrdId' => '', // 訂單號
        'OrdAmt' => '', // 金額(精確到小數後兩位)
        'PayType' => 'DT', // 支付類型，默認DT
        'CurCode' => 'CNY', // 幣別，默認CNY
        'BankCode' => '', // 銀行代碼
        'ProductInfo' => '', // 物品信息，帶入username
        'Remark' => '', // 備註，帶入username
        'ReturnURL' => '', // 前台返回網址
        'NotifyURL' => '', // 後台返回網址
        'SignType' => 'MD5', // 簽名方式，默認MD5
        'SignInfo' => '', // 簽名
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'MerId' => 'number',
        'OrdId' => 'orderId',
        'OrdAmt' => 'amount',
        'BankCode' => 'paymentVendorId',
        'ProductInfo' => 'username',
        'Remark' => 'username',
        'ReturnURL' => 'notify_url',
        'NotifyURL' => 'notify_url',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'MerId',
        'OrdId',
        'OrdAmt',
        'PayType',
        'CurCode',
        'BankCode',
        'ProductInfo',
        'Remark',
        'ReturnURL',
        'NotifyURL',
        'SignType',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'MerId' => 1,
        'OrdId' => 1,
        'OrdAmt' => 1,
        'OrdNo' => 1,
        'ResultCode' => 1,
        'Remark' => 1,
        'SignType' => 1,
    ];

    /**
     * 應答機制訊息
     *
     * @var string
     */
    protected $msg = 'success|9999';

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        '1' => 'ICBC', // 中國工商銀行
        '2' => 'BCOM', // 交通銀行
        '3' => 'ABC', // 中國農業銀行
        '4' => 'CCB', // 中國建設銀行
        '5' => 'CMB', // 招商銀行
        '6' => 'CMBC', // 中國民生銀行
        '8' => 'SPDB', // 上海浦東發展銀行
        '10' => 'CIB', // 興業銀行
        '11' => 'ECITIC', // 中信銀行
        '12' => 'CEBB', // 中國光大銀行
        '13' => 'HXB', // 華夏銀行
        '14' => 'GDB', // 廣東發展銀行
        '15' => 'SPABANK', // 平安银行
        '16' => 'PSBC', // 中國郵政
        '17' => 'BOC', // 中國銀行
        '278' => 'QUICKPAY', // 銀聯在線
        '1088' => 'QUICKPAY', // 銀聯在線_手機支付
        '1090' => 'WECHATQR', // 微信_二維
        '1092' => 'ALIPAYQR', // 支付寶_二維
        '1097' => 'WECHATWAP', // 微信_手機支付
        '1098' => 'ALIPAYWAP', // 支付寶_手機支付
        '1103' => 'QQWALLET', // QQ錢包_二維
        '1104' => 'QQWAP', // QQ_手機支付
        '1107' => 'JDWALLET', // 京東錢包
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
        if (!array_key_exists($this->requestData['BankCode'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $this->requestData['OrdAmt'] = sprintf('%.2f', $this->requestData['OrdAmt']);
        $this->requestData['BankCode'] = $this->bankMap[$this->requestData['BankCode']];

        // 設定支付平台需要的加密串
        $this->requestData['SignInfo'] = $this->encode();

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

        // 組織加密串
        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options)) {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        // 依key1=value1&key2=value2&...&keyN=valueN之後做md5
        $encodeStr = urldecode(http_build_query($encodeData));

        // 如果沒有返回簽名要丟例外
        if (!isset($this->options['SignInfo'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['SignInfo'] != md5(md5($encodeStr) . $this->privateKey)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['ResultCode'] != 'success002') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['OrdId'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['OrdAmt'] != $entry['amount']) {
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

        // 加密設定
        foreach ($this->encodeParams as $index) {
            $encodeData[$index] = $this->requestData[$index];
        }

        // 額外的加密設定
        $encodeData['MerKey'] = $this->privateKey;
        $encodeStr = urldecode(http_build_query($encodeData));

        return md5($encodeStr);
    }
}

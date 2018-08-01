<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * XPay
 */
class XPay extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'MerchantID' => '', // 商戶號
        'CustID' => '', // 使用者帳號
        'CustIP' => '', // 使用者IP
        'Curr' => 'CNY', // 幣別
        'Amount' => '', // 交易金額
        'RefID' => '', // 訂單號
        'TransTime' => '', // 訂單時間
        'ReturnURL' => '', // 同步通知URL
        'RequestURL' => '', // 異步通知URL
        'BankCode' => '', // 銀行編號
        'Remarks' => '', // 注意事項, 可空
        'EncryptText' => '', // 加密字串
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'MerchantID' => 'number',
        'CustID' => 'username',
        'CustIP' => 'ip',
        'Amount' => 'amount',
        'RefID' => 'orderId',
        'TransTime' => 'orderCreateDate',
        'ReturnURL' => 'notify_url',
        'RequestURL' => 'notify_url',
        'BankCode' => 'paymentVendorId',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'MerchantID',
        'CustID',
        'Curr',
        'Amount',
        'RefID',
        'TransTime',
        'ReturnURL',
        'RequestURL',
        'BankCode',
        'Remarks',
    ];

    /**
     * Data需要加密的參數
     *
     * @var array
     */
    protected $encryptParams = [
        'MerchantID',
        'CustID',
        'Curr',
        'Amount',
        'RefID',
        'TransTime',
        'ReturnURL',
        'RequestURL',
        'BankCode',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'RefID' => 1,
        'Curr' => 1,
        'Amount' => 1,
        'Status' => 1,
        'TransID' => 1,
        'ValidationKey' => 1,
    ];

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        '1' => 'ICBC', // 工商銀行
        '2' => 'BCOM', // 交通銀行
        '3' => 'ABC', // 農業銀行
        '4' => 'CCB', // 建設銀行
        '5' => 'CMB', // 招商銀行
        '6' => 'CMBC', // 民生銀行
        '7' => 'SDB', // 深圳發展銀行
        '8' => 'SPDB', // 上海浦東發展銀行
        '9' => 'BOBJ', // 北京銀行
        '10' => 'CIB', // 興業銀行
        '11' => 'CITIC', // 中信銀行
        '12' => 'CEB', // 光大銀行
        '13' => 'HXB', // 華夏銀行
        '14' => 'GDB', // 廣東發展銀行
        '15' => 'SZCB', // 平安銀行
        '16' => 'CPSRB', // 中國郵政儲蓄
        '17' => 'BOC', // 中國銀行
        '19' => 'SHB', // 上海銀行
        '217' => 'CBHB', // 渤海銀行
        '226' => 'NJB', // 南京銀行
        '228' => 'SHRCC', // 上海農村商業銀行
        '234' => 'BJRCB', // 北京農村商業銀行
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

        $this->requestData['BankCode'] = $this->bankMap[$this->requestData['BankCode']];
        $date = new \DateTime($this->requestData['TransTime']);
        $this->requestData['TransTime'] = $date->format('Y-m-d H:i:s');
        $this->requestData['Amount'] = sprintf('%.2f', $this->requestData['Amount']);

        $requestData = [
            'Data' => $this->EncryptData(),
            'Remarks' => $this->requestData['Remarks'],
            'EncryptText' => $this->encode(),
        ];

        return $requestData;
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

        $encodeStr = $this->privateKey . ':' . implode(',', $encodeData);

        // 沒有EncryptText就要丟例外，其他的參數在組織加密串的時候驗證過了
        if (!isset($this->options['EncryptText'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['EncryptText'] != strtoupper(md5($encodeStr))) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        // Pending
        if ($this->options['Status'] == '001') {
            throw new PaymentConnectionException('Order Processing', 180059, $this->getEntryId());
        }

        /**
         * 000:支付成功且已通知商戶成功 002:支付成功但通知商戶未成功
         * 兩種狀態回傳皆是支付成功
         */
        if ($this->options['Status'] != '000' && $this->options['Status'] != '002') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['RefID'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['Amount'] != $entry['amount']) {
            throw new PaymentException('Order Amount error', 180058);
        }

        $this->msg = $this->options['TransID'] . '||' . $this->options['ValidationKey'];
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
            $encodeData[] = $this->requestData[$index];
        }

        $encodeStr = $this->privateKey . ':' . implode(',', $encodeData);

        return md5($encodeStr);
    }

    /**
     * 加密XPay Data
     *
     * @return string
     */
    private function EncryptData()
    {
        $encryptData = [];

        foreach ($this->encryptParams as $index) {
            $encryptData[$index] = $this->requestData[$index];
        }

        $encryptStr = urldecode(http_build_query($encryptData));

        $n = 0;
        $strSplit = ['', 'g', 'h', 'G', 'k', 'g', 'J', 'K', 'I', 'h', 'i', 'j', 'H'];
        $strTemp = '';

        $aryUnpack = unpack('C*', str_replace("\r\n", '', utf8_encode($encryptStr)));

        $aryTemp = explode(',', implode(',', $aryUnpack));

        for ($i = 0; $i < count($aryTemp); $i++) {
            $number = (int) $aryTemp[$i];

            if ($n == 12) {
                $n = 1;
            } else {
                $n++;
            }

            $mychar = 'H';

            try {
                $mychar = $strSplit[$n];
            }
            catch (Exception $e) {
                $n = 1;
            }

            $mychar = $strSplit[$n];

            $strEncryptedData = strval(dechex($number) . $mychar);

            $strTemp = $strTemp . $strEncryptedData;
        }

        return $strTemp;
    }
}

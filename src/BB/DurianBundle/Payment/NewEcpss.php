<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 新匯潮支付
 */
class NewEcpss extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'MerNo' => '', // 商號
        'BillNo' => '', // 訂單號
        'Amount' => '', // 金額(精確到小數後兩位)
        'ReturnURL' => '', // 頁面跳轉通知url
        'AdviceURL' => '', // 服務器異步通知url
        'OrderTime' => '', // 請求時間(YmdHis)
        'defaultBankNumber' => '', // 銀行代碼
        'payType' => 'B2CDebit', // 支付方式, B2CDebit: B2C借記卡, noCard:銀聯快捷支付
        'Remark' => '', // 備註(可空)
        'products' => '', // 商品訊息(可空)
        'SignInfo' => '', // 簽名訊息
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'MerNo' => 'number',
        'BillNo' => 'orderId',
        'Amount' => 'amount',
        'ReturnURL' => 'notify_url',
        'AdviceURL' => 'notify_url',
        'OrderTime' => 'orderCreateDate',
        'defaultBankNumber' => 'paymentVendorId',
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
        'OrderTime',
        'ReturnURL',
        'AdviceURL',
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
        'OrderNo' => 1,
        'Amount' => 1,
        'Succeed' => 1,
    ];

    /**
     * 應答機制訊息
     *
     * @var string
     */
    protected $msg = 'ok';

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        '1' => 'ICBC', // 中國工商銀行
        '2' => 'BOCOM', // 交通銀行
        '3' => 'ABC', // 中國農業銀行
        '4' => 'CCB', // 中國建設銀行
        '5' => 'CMB', // 招商銀行
        '6' => 'CMBC', // 中國民生銀行
        '8' => 'SPDB', // 上海浦東發展銀行
        '9' => 'BCCB', // 北京銀行
        '10' => 'CIB', // 興業銀行
        '11' => 'CNCB', // 中信銀行
        '12' => 'CEB', // 中國光大銀行
        '13' => 'HXB', // 華夏銀行
        '14' => 'GDB', // 廣東發展銀行
        '15' => 'PAB', // 平安银行
        '16' => 'PSBC', // 中國郵政
        '17' => 'BOC', // 中國銀行
        '19' => 'BOS', // 上海銀行
        '228' => 'SRCB', // 上海市農商行
        '278' => 'OTHERS', // 銀聯在線
        '279' => 'NOCARD', // 銀聯無卡
        '1088' => 'OTHERS', // 銀聯在線手機支付
        '1093' => 'NOCARD', // 銀聯無卡手機支付
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

        // 帶入未支援的銀行就噴例外
        if (!array_key_exists($this->requestData['defaultBankNumber'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 銀聯無卡需調整支付參數
        if (in_array($this->options['paymentVendorId'], [279, 1093])) {
            $this->requestData['payType'] = 'noCard';
        }

        // 銀聯在線需調整支付參數為空
        if (in_array($this->options['paymentVendorId'], [278, 1088])) {
            $this->requestData['payType'] = '';
        }

        // 額外的參數設定
        $date = new \DateTime($this->requestData['OrderTime']);
        $this->requestData['OrderTime'] = $date->format("YmdHis");
        $this->requestData['Amount'] = sprintf('%.2f', $this->requestData['Amount']);
        $this->requestData['defaultBankNumber'] = $this->bankMap[$this->requestData['defaultBankNumber']];

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

        $encodeStr = urldecode(http_build_query($encodeData));
        $encodeStr .= '&' . $this->privateKey;

        // 沒有SignInfo就要丟例外，其他的參數在組織加密串的時候驗證過了
        if (!isset($this->options['SignInfo'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['SignInfo'] != strtoupper(md5($encodeStr))) {
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
        $encodeData = [];

        foreach ($this->encodeParams as $paymentKey) {
            $encodeData[$paymentKey] = $this->requestData[$paymentKey];
        }

        $encodeStr = urldecode(http_build_query($encodeData));
        $encodeStr .= '&' . $this->privateKey;

        return strtoupper(md5($encodeStr));
    }
}

<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 雲服務-Ips
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
class CloudIps extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'Mer_code'        => '', //用戶編號
        'Billno'          => '', //訂單號
        'Amount'          => '', //訂單金額
        'Date'            => '', //訂單日期
        'Currency_Type'   => 'RMB', //幣別
        'Gateway_Type'    => '01', //支付卡別
        'test'            => '0', //環境(1:測試環境, 0:正式環境)
        'Merchanturl'     => '', //支付成功返回網址
        'FailUrl'         => '', //支付失敗返回網址
        'OrderEncodeType' => '5', //支付加密方式
        'RetEncodeType'   => '17', //返回加密方式
        'DoCredit'        => '1', //是否直連(1:是, 2:否)
        'Bankco'          => '', //銀行代碼
        'Rettype'         => '1', //返回方式
        'pay_prority'     => '1', //通道優先級
        'Mer_key'         => '', //金鑰
        'SignMD5'         => '' //加密串
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'Mer_code' => 'number',
        'Billno' => 'orderId',
        'Amount' => 'amount',
        'Date' => 'orderCreateDate',
        'Merchanturl' => 'notify_url',
        'FailUrl' => 'notify_url',
        'Bankco' => 'paymentVendorId'
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'Billno',
        'Currency_Type',
        'Amount',
        'Date',
        'OrderEncodeType'
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'billno' => 1,
        'Currency_type' => 1,
        'amount' => 1,
        'date' => 1,
        'succ' => 1,
        'ipsbillno' => 1,
        'retencodetype' => 1
    ];

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        '1'   => '00004', //中國工商銀行
        '2'   => '00005', //交通銀行
        '3'   => '00017', //中國農業銀行
        '4'   => '00015', //中國建設銀行
        '5'   => '00021', //招商銀行
        '6'   => '00013', //中國民生銀行
        '8'   => '00032', //上海浦東發展銀行
        '9'   => '00050', //北京銀行
        '10'  => '00016', //興業銀行
        '11'  => '00054', //中信銀行
        '12'  => '00057', //中國光大銀行
        '13'  => '00041', //華夏銀行
        '14'  => '00052', //廣東發展銀行
        '15'  => '00087', //深圳平安銀行
        '16'  => '00051', //中國郵政
        '17'  => '00083', //中國銀行
        '19'  => '00084', //上海銀行
        '217' => '00095', //渤海銀行
        '220' => '00081', //杭州銀行
        '221' => '00086', //浙商銀行
        '222' => '00085', //寧波銀行
        '223' => '00096', //東亞銀行
        '234' => '00056'  //北京農商行
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

        // 額外的參數設定
        $this->requestData['Mer_key'] = $this->privateKey;

        //帶入未支援的銀行就噴例外
        if (!array_key_exists($this->requestData['Bankco'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        //額外的參數設定
        $date = new \DateTime($this->requestData['Date']);
        $this->requestData['Date'] = $date->format("Ymd");
        $this->requestData['Amount'] = sprintf("%.2f", $this->requestData['Amount']);
        $this->requestData['Bankco'] = $this->bankMap[$this->requestData['Bankco']];

        //設定支付平台需要的加密串
        $this->requestData['SignMD5'] = $this->encode();

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

        $encodeArray = [];

        //組織加密串
        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (!array_key_exists($paymentKey, $this->options)) {
                continue;
            }
            //這邊是因為index是Currency_type的時候加密串要是currencytype.$value[Currency_type]
            if ($paymentKey == 'Currency_type') {
                $encodeArray[] = 'currencytype' . $this->options[$paymentKey];
            } else {
                $encodeArray[] = $paymentKey . $this->options[$paymentKey];
            }
        }

        //進行加密
        $encodeStr = implode('&', $encodeArray) . $this->privateKey;
        $encodeStr = md5($encodeStr);

        //沒有signature就要丟例外，其他的參數在組織加密串的時候驗證過了
        if (!isset($this->options['signature'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['signature'] != $encodeStr) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['succ'] != 'Y') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['billno'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['amount'] != $entry['amount']) {
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
        $encodeArray = [];

        //加密設定
        foreach ($this->encodeParams as $index) {
            /*
             * 這邊是因為index是Currency_Type的時候加密串要是currencytype.$value[Currency_type]
             * 其他的部分要回傳給支付平台的index首字都是大寫，
             * 但加密串需要小寫amount.$value[Amount]
             */
            if ($index == 'Currency_Type') {
                $encodeArray[] = 'currencytype' . $this->requestData[$index];
            } else {
                $encodeArray[] = strtolower($index) . $this->requestData[$index];
            }
        }

        //額外的加密設定
        $encodeStr = implode('&', $encodeArray) . $this->privateKey;

        return md5($encodeStr);
    }
}

<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 匯潮支付
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
class Ecpss extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'MerNo'             => '', //商號
        'BillNo'            => '', //訂單號
        'Amount'            => '', //金額(精確到小數後兩位)
        'ReturnURL'         => '', //頁面跳轉通知url
        'AdviceURL'         => '', //服務器通知url
        'orderTime'         => '', //請求時間(YmdHis)
        'defaultBankNumber' => '', //銀行代碼
        'Remark'            => '', //備註
        'products'          => '', //商品訊息
        'SignInfo'          => '' //簽名訊息
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
        'ReturnURL' => 'notify_url', //支付完成後跳轉url
        'AdviceURL' => 'notify_url', //伺服器通知url, 接收到ok則不再發送
        'orderTime' => 'orderCreateDate',
        'defaultBankNumber' => 'paymentVendorId'
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
        'ReturnURL'
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'BillNo' => 1,
        'Amount' => 1,
        'Succeed' => 1
    ];

    /**
     * 應答機制訊息
     *
     * @var string
     */
    protected $msg = 'ok';

    protected $bankMap = [
        '1'   => 'ICBC', //中國工商銀行
        '2'   => 'BOCOM', //交通銀行
        '3'   => 'ABC', //中國農業銀行
        '4'   => 'CCB', //中國建設銀行
        '5'   => 'CMB', //招商銀行
        '6'   => 'CMBC', //中國民生銀行
        '8'   => 'SPDB', //上海浦東發展銀行
        '9'   => 'BCCB', //北京銀行
        '10'  => 'CIB', //興業銀行
        '11'  => 'CNCB', //中信銀行
        '12'  => 'CEB', //中國光大銀行
        '13'  => 'HXB', //華夏銀行
        '14'  => 'GDB', //廣東發展銀行
        '15'  => 'PAB', //平安银行
        '16'  => 'PSBC', //中國郵政
        '17'  => 'BOCSH', //中國銀行
        '19'  => 'BOS', //上海銀行
        '228' => 'SRCB', //上海市農商行
        '278' => 'UNIONPAY', // 銀聯在線
        '279' => 'NOCARD', // 銀聯無卡
        '1088' => 'UNIONPAY', // 銀聯在線手機支付
        '1093' => 'NOCARD', // 銀聯無卡手機支付
    ];

    /**
     * 查詢時要傳給平台驗證的參數
     * 將以下參數轉成xml格式再base64_encode後用requestDomain當key回傳
     *
     * @var array
     */
    protected $trackingRequestData = [
        'merCode'     => '', //商號
        'orderNumber' => '', //訂單號
        'beginTime'   => '', //查詢起始時間(提交的交易時間YmdHis)
        'endTime'     => '', //查詢結束時間(提交的交易時間YmdHis)
        'pageIndex'   => '1', //查詢結果頁碼
        'sign'        => '' //簽名
    ];

    /**
     * 查詢時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $trackingRequireMap = [
        'merCode' => 'number',
        'orderNumber' => 'orderId',
        'beginTime' => 'orderCreateDate'
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
        if (!array_key_exists($this->requestData['defaultBankNumber'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        //額外的參數設定
        $date = new \DateTime($this->requestData['orderTime']);
        $this->requestData['orderTime'] = $date->format("YmdHis");
        $this->requestData['Amount'] = sprintf('%.2f', $this->requestData['Amount']);
        $this->requestData['defaultBankNumber'] = $this->bankMap[$this->requestData['defaultBankNumber']];

        //設定支付平台需要的加密串
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

        //組織加密串
        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options)) {
                $encodeData[] = $this->options[$paymentKey];
            }
        }

        //進行加密
        $encodeData[] = $this->privateKey;
        $encodeStr = implode('&', $encodeData);

        //沒有SignMD5info就要丟例外，其他的參數在組織加密串的時候驗證過了
        if (!isset($this->options['SignMD5info'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['SignMD5info'] != strtoupper(md5($encodeStr))) {
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
     * 訂單查詢
     */
    public function paymentTracking()
    {
        // 驗證私鑰
        $this->verifyPrivateKey();
        $this->trackingVerify();

        // 從內部給定值到參數
        foreach ($this->trackingRequireMap as $paymentKey => $internalKey) {
            $this->trackingRequestData[$paymentKey] = $this->options[$internalKey];
        }

        // 額外的參數設定
        $date = new \DateTime($this->trackingRequestData['beginTime']);
        $this->trackingRequestData['beginTime'] = $date->format('YmdHis');
        $this->trackingRequestData['endTime'] = $date->format('YmdHis');
        $this->trackingRequestData = $this->trackingEncode();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'method' => 'POST',
            'uri' => '/merchantBatchQueryAPI',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => http_build_query($this->trackingRequestData),
            'header' => []
        ];

        // 取得訂單查詢結果
        $result = $this->curlRequest($curlParam);
        $parseData = $this->parseData($result);

        // 如果沒有resultCode丟例外
        if (!isset($parseData['resultCode'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        if ($parseData['resultCode'] == '11') {
            throw new PaymentConnectionException(
                'PaymentGateway error, IP have no binding',
                180125,
                $this->getEntryId()
            );
        }

        if ($parseData['resultCode'] == '22') {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($parseData['resultCode'] == '33') {
            throw new PaymentConnectionException('Transaction kind error', 180085, $this->getEntryId());
        }

        if ($parseData['resultCode'] == '44') {
            throw new PaymentConnectionException('Order does not exist', 180060, $this->getEntryId());
        }

        // 如果沒有orderStatus丟例外
        if (!isset($parseData['lists']['list']['orderStatus'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        if ($parseData['lists']['list']['orderStatus'] != '1' || $parseData['resultCode'] != '00') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($parseData['lists']['list']['orderAmount'] != $this->options['amount']) {
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

        return strtoupper(md5($encodeStr));
    }

    /**
     * 訂單查詢時的加密
     *
     * @return array
     */
    protected function trackingEncode()
    {
        $sign = md5($this->trackingRequestData['merCode'].$this->privateKey);
        $this->trackingRequestData['sign'] = strtoupper($sign);

        //'@tx' => '1001'是要設定<root tx="1001">
        $data = $this->trackingRequestData;
        $data['@tx'] = '1001';

        //設定version和encoding
        $context = [
            'xml_version'  => '1.0',
            'xml_encoding' => 'utf-8'
        ];

        $xml = $this->arrayToXml($data, $context, 'root');

        return ['requestDomain' => base64_encode($xml)];
    }

    /**
     * 分析支付平台回傳的入款查詢參數
     *
     * @param string $content xml格式的回傳值
     * @return array
     */
    private function parseData($content)
    {
        return $this->xmlToArray($content);
    }
}

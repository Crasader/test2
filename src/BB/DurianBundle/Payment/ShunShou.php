<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 *  順手支付
 */
class ShunShou extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'consumerNo'  => '', //商號
        'merOrderNum' => '', //訂單號
        'tranAmt'     => '', //金額
        'bankCode'    => '', //銀行代碼
        'callbackUrl' => '', //回傳網址
        'merRemark1'  => '', //備註
        'signValue'   => '' //商戶訂單數據進行MD5加密後的字串
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'consumerNo' => 'number',
        'merOrderNum' => 'orderId',
        'tranAmt' => 'amount',
        'bankCode' => 'paymentVendorId',
        'callbackUrl' => 'notify_url'
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'consumerNo',
        'merOrderNum',
        'tranAmt',
        'callbackUrl'
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'consumerNo' => 1,
        'merOrderNum' => 1,
        'tranAmt' => 1,
        'callbackUrl' => 1
    ];

    /**
     * 支付平台支援銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        '1'  => 'ICBC', //工商銀行
        '2'  => 'BOCOM', //交通銀行
        '3'  => 'ABC', //农业银行
        '4'  => 'CCB', //建设银行
        '5'  => 'CMB', //招商银行
        '6'  => 'CMBC', //民生银行总行
        '7'  => 'SDB', //深圳发展银行
        '8'  => 'SPDB', //上海浦东发展银行
        '9'  => 'BOBJ', //北京银行
        '10' => 'CIB', //兴业银行
        '11' => 'CITIC', //中信银行
        '12' => 'CEB', //光大银行
        '13' => 'HXBC', //华夏银行
        '14' => 'GDB', //广东发展银行
        '16' => 'PSBC', //中国邮政
        '17' => 'BOC' //中国银行
    ];

    /**
     * 補單時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $trackingRequestData = [
        'consumerNo'  => '', //商號
        'merOrderNum' => '', //訂單號
        'sign'        => '' //商戶訂單數據進行MD5加密後的字串
    ];

    /**
     * 查詢時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $trackingRequireMap = [
        'consumerNo' => 'number',
        'merOrderNum' => 'orderId'
    ];

    /**
     * 補單時需要加密的參數
     *
     * @var array
     */
    protected $trackingEncodeParams = [
        'consumerNo',
        'merOrderNum'
    ];

    /**
     * 查詢解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $trackingDecodeParams = [
        'consumerNo' => 1,
        'merOrderNum' => 1,
        'requestAmt' => 1,
        'tranAmt' => 1,
        'requestTime' => 1,
        'transTime' => 1,
        'orderStatus' => 1,
        'bankCode' => 1
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

        //帶入未支援的銀行就噴例外
        if (!array_key_exists($this->requestData['bankCode'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        //額外的參數設定
        $this->requestData['tranAmt'] = sprintf("%.2f", $this->requestData['tranAmt']);
        $this->requestData['bankCode'] = $this->bankMap[$this->requestData['bankCode']];
        $this->requestData['merRemark1'] = $this->options['merchantId']. '_' .$this->options['domain'];

        //設定支付平台需要的加密串
        $this->requestData['signValue'] = $this->encode();

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

        $encodeStr = '';

        if (!isset($this->options['callbackUrl'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        $this->options['callbackUrl'] = urldecode($this->options['callbackUrl']);

        $this->payResultVerify();

        //組加密串
        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options)) {
                $encodeStr .= $paymentKey . '=[' . $this->options[$paymentKey] . ']';
            }
        }

        //進行加密
        $encodeStr .= 'userKey=[' . $this->privateKey . ']';

        //沒有signValue就要丟例外，其他的參數在組織加密串的時候驗證過了
        if (!isset($this->options['signValue'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['signValue'] != md5($encodeStr)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if (strtoupper($this->options['respCode']) != 'OK') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['merOrderNum'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['tranAmt'] != $entry['amount']) {
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
        $this->trackingRequestData['sign'] = $this->trackingEncode();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'method' => 'POST',
            'uri' => '/query_bank_order.do',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => http_build_query($this->trackingRequestData),
            'header' => []
        ];

        // 取得訂單查詢結果
        $result = $this->curlRequest($curlParam);
        $parseData = $this->parseData($result);
        $encodeStr = '';

        foreach (array_keys($this->trackingDecodeParams) as $index) {
            if (array_key_exists($index, $parseData)) {
                $encodeStr .= $index . '=[' . $parseData[$index] . ']';
            }
        }
        $encodeStr .= 'key=[' . $this->privateKey . ']';

        if (md5($encodeStr) != $parseData['sign']) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($parseData['orderStatus'] == '0') {
            throw new PaymentConnectionException('Order Processing', 180059, $this->getEntryId());
        }

        if ($parseData['orderStatus'] != '1') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($parseData['tranAmt'] != $this->options['amount']) {
            throw new PaymentException('Order Amount error', 180058);
        }
    }

    /**
     * 取得訂單查詢時需要的參數
     *
     * @return array
     */
    public function getPaymentTrackingData()
    {
        // 驗證私鑰
        $this->verifyPrivateKey();
        $this->trackingVerify();

        // 從內部給定值到參數
        foreach ($this->trackingRequireMap as $paymentKey => $internalKey) {
            $this->trackingRequestData[$paymentKey] = $this->options[$internalKey];
        }
        $this->trackingRequestData['sign'] = $this->trackingEncode();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'verify_ip' => $this->options['verify_ip'],
            'path' => '/query_bank_order.do',
            'method' => 'POST',
            'form' => $this->trackingRequestData,
            'headers' => [
                'Host' => $this->options['verify_url']
            ]
        ];

        return $curlParam;
    }

    /**
     * 驗證訂單查詢是否成功
     */
    public function paymentTrackingVerify()
    {
        // 驗證私鑰
        $this->verifyPrivateKey();

        $parseData = $this->parseData($this->options['content']);
        $encodeStr = '';

        foreach (array_keys($this->trackingDecodeParams) as $index) {
            if (array_key_exists($index, $parseData)) {
                $encodeStr .= $index . '=[' . $parseData[$index] . ']';
            }
        }
        $encodeStr .= 'key=[' . $this->privateKey . ']';

        if (md5($encodeStr) != $parseData['sign']) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($parseData['orderStatus'] == '0') {
            throw new PaymentConnectionException('Order Processing', 180059, $this->getEntryId());
        }

        if ($parseData['orderStatus'] != '1') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($parseData['tranAmt'] != $this->options['amount']) {
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
            $encodeStr .= $index . '=[' . $this->requestData[$index] . ']';
        }

        $encodeStr .= 'userKey=[' . $this->privateKey . ']';

        return md5($encodeStr);
    }

    /**
     * 訂單查詢時的加密
     *
     * @return string
     */
    protected function trackingEncode()
    {
        $encodeStr = '';

        //加密設定
        foreach ($this->trackingEncodeParams as $index) {
            $encodeStr .= $index . '=[' . $this->trackingRequestData[$index] . ']';
        }

        //額外的加密設定
        $encodeStr .= 'key=[' . $this->privateKey . ']';

        return md5($encodeStr);
    }

    /**
     * 分析支付平台回傳的入款查詢參數
     *
     * @param array $content
     * @return array
     */
    private function parseData($content)
    {
        $content = explode('|', trim(urldecode($content)));

        //補單回傳數據包的參數名稱及順序
        $examineDataKey = [
            'consumerNo',
            'merOrderNum',
            'requestAmt',
            'tranAmt',
            'requestTime',
            'transTime',
            'orderStatus',
            'bankCode',
            'orderId',
            'returnCode',
            'merRemark1',
            'sign'
        ];

        //這邊有驗證數量，因此不會有不存在index的問題，所以index不用再驗證
        if (count($content) != count($examineDataKey)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        //組合成補單數據
        $parseData = array_combine($examineDataKey, $content);

        return $parseData;
    }
}

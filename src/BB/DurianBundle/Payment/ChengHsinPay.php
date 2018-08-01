<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 誠信支付
 */
class ChengHsinPay extends PaymentBase
{
    /**
     * 支付提交參數
     *
     * @var array
     */
    protected $requestData = [
        'versionId' => '1.0', // 服務版本號，固定值
        'orderAmount' => '', // 金額，單位：分
        'orderDate' => '', // 交易日期，格式：YmdHis
        'currency' => 'RMB', // 貨幣類型，固定值，人民幣:RMB
        'accountType' => '0', // 銀行卡類型，固定值，借記卡:0
        'transType' => '0008', // 交易類型，固定值
        'asynNotifyUrl' => '', // 異步通知URL
        'synNotifyUrl' => '', // 同步通知URL
        'signType' => 'MD5', // 加密方式，固定值
        'merId' => '', // 商戶號
        'prdOrdNo' => '', // 商戶訂單號
        'payMode' => '00020', // 支付方式，銀行卡:00020
        'tranChannel' => '', // 銀行編碼
        'receivableType' => '', // 到帳類型，商家額外欄位供業主填寫
        'prdAmt' => '', // 商品價格，非必填
        'prdDisUrl' => '', // 商品展示網址，非必填
        'prdName' => '', // 商品名稱，設定username方便業主比對
        'prdShortName' => '', // 商品簡稱，非必填
        'prdDesc' => '', // 商品描述，設定username方便業主比對
        'pnum' => '1', // 商品數量，固定值
        'merParam' => '', // 擴展參數，非必填
        'signData' => '', // 簽名
    ];

    /**
     * 支付提交參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'orderAmount' => 'amount',
        'orderDate' => 'orderCreateDate',
        'asynNotifyUrl' => 'notify_url',
        'synNotifyUrl' => 'notify_url',
        'merId' => 'number',
        'prdOrdNo' => 'orderId',
        'tranChannel' => 'paymentVendorId',
        'prdName' => 'username',
        'prdDesc' => 'username',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'versionId',
        'orderAmount',
        'orderDate',
        'currency',
        'accountType',
        'transType',
        'asynNotifyUrl',
        'synNotifyUrl',
        'signType',
        'merId',
        'prdOrdNo',
        'payMode',
        'tranChannel',
        'receivableType',
        'prdAmt',
        'prdDisUrl',
        'prdName',
        'prdShortName',
        'prdDesc',
        'pnum',
        'merParam',
    ];

    /**
     * 返回驗證時需要加密的參數
     *     0: 有返回就要加密的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'versionId' => 1,
        'transType' => 1,
        'asynNotifyUrl' => 1,
        'synNotifyUrl' => 1,
        'merId' => 1,
        'orderStatus' => 1,
        'orderAmount' => 1,
        'prdOrdNo' => 1,
        'payId' => 1,
        'payTime' => 1,
        'signType' => 1,
        'merParam' => 0,
    ];

    /**
     * 應答機制訊息
     *
     * @var string
     */
    protected $msg = 'SUCCESS';

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        '1' => '102', // 工商銀行
        '2' => '301', // 交通銀行
        '3' => '103', // 農業銀行
        '4' => '105', // 建設銀行
        '5' => '308', // 招商銀行
        '6' => '305', // 民生銀行
        '8' => '310', // 上海浦東發展銀行
        '9' => '314', //北京銀行
        '10' => '309', // 興業銀行
        '11' => '302', // 中信銀行
        '12' => '303', // 光大銀行
        '13' => '304', // 華夏銀行
        '14' => '306', // 廣發銀行
        '15' => '307', // 平安銀行
        '16' => '403', // 中國郵政
        '17' => '104', // 中國銀行
        '19' => '325', //上海銀行
        '1090' => '00022', // 微信_二維
        '1092' => '00021', // 支付寶_二維
        '1103' => '00024', // QQ_二維
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

        // 驗證支付參數
        $this->payVerify();

        // 從內部設值到支付參數
        foreach ($this->requireMap as $paymentKey => $internalKey) {
            $this->requestData[$paymentKey] = $this->options[$internalKey];
        }

        // 檢查銀行代碼是否支援
        if (!array_key_exists($this->requestData['tranChannel'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $this->requestData['tranChannel'] = $this->bankMap[$this->requestData['tranChannel']];
        $this->requestData['orderAmount'] = round($this->requestData['orderAmount'] * 100);
        $createAt = new \Datetime($this->requestData['orderDate']);
        $this->requestData['orderDate'] = $createAt->format('YmdHis');

        // 取得商家附加設定值
        $merchantExtras = $this->getMerchantExtraValue(['receivableType']);
        $this->requestData['receivableType'] = $merchantExtras['receivableType'];


        // 二維支付
        if (in_array($this->options['paymentVendorId'], [1090, 1092, 1103])) {
            $this->requestData['payMode'] = $this->requestData['tranChannel'];

            // 移除二維不需要的參數
            unset($this->requestData['accountType']);
            unset($this->requestData['tranChannel']);
            unset($this->requestData['pnum']);

            $this->requestData['signData'] = $this->encode();

            if (trim($this->options['verify_url']) == '') {
                throw new PaymentException('No verify_url specified', 180140);
            }

            $curlParam = [
                'method' => 'POST',
                'uri' => '/payment/ScanPayApply.do',
                'ip' => $this->options['verify_ip'],
                'host' => $this->options['verify_url'],
                'param' => http_build_query($this->requestData),
                'header' => ['Port' => 8070],
            ];

            $result = $this->curlRequest($curlParam);
            $parseData = json_decode($result, true);

            if (!isset($parseData['retCode']) || !isset($parseData['retMsg'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            if ($parseData['retCode'] != '1' ) {
                throw new PaymentConnectionException($parseData['retMsg'], 180130, $this->getEntryId());
            }

            if (!isset($parseData['qrcode'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            $this->setQrcode($parseData['qrcode']);

            return [];
        }

        $this->requestData['signData'] = $this->encode();

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

        // 驗證返回參數
        $this->payResultVerify();

        // 組合參數驗證加密簽名
        $encodeData = [];

        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options)) {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        ksort($encodeData);

        $encodeData['key'] = $this->privateKey;

        $encodeStr = urldecode(http_build_query($encodeData));

        if (!isset($this->options['signData'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['signData'] != strtoupper(md5($encodeStr))) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['orderStatus'] == '02') {
            throw new PaymentConnectionException('Order Processing', 180059, $this->getEntryId());
        }

        if ($this->options['orderStatus'] !== '01') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['prdOrdNo'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['orderAmount'] != round($entry['amount'] * 100)) {
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
            if (isset($this->requestData[$index]) && $this->requestData[$index] != '') {
                $encodeData[$index] = $this->requestData[$index];
            }
        }

        ksort($encodeData);

        $encodeData['key'] = $this->privateKey;

        $encodeStr = urldecode(http_build_query($encodeData));

        return md5($encodeStr);
    }
}

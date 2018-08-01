<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 云盛支付
 */
class YunSheng extends PaymentBase
{
    /**
     * 支付提交參數
     *
     * @var array
     */
    protected $requestData = [
        'versionId' => '001', // 版本號，固定值
        'businessType' => '1100', // 交易類型
        'insCode' => '', // 機構號，非必填，網銀參數
        'transChanlName' => '', // 掃碼支付類型
        'openBankName' => '', // 開戶銀行，非必填，網銀參數
        'merId' => '', // 商戶號
        'orderId' => '', // 訂單號
        'transDate' => '', // 訂單日期 YmdHis
        'transAmount' => '', // 支付金額，單位：元
        'transCurrency' => '156', // 交易幣別，網銀參數
        'pageNotifyUrl' => '', // 前台頁面通知地址，網銀參數
        'backNotifyUrl' => '', // 後台通知網址
        'orderDesc' => '', // 訂單描述，帶入username
        'dev' => '', // 商戶自定義域，可空
        'signType' => 'MD5', // 簽名類型，不納簽
        'signData' => '', // 簽名數據
    ];

    /**
     * 支付提交參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'transChanlName' => 'paymentVendorId',
        'merId' => 'number',
        'orderId' => 'orderId',
        'transDate' => 'orderCreateDate',
        'transAmount' => 'amount',
        'pageNotifyUrl' => 'notify_url',
        'backNotifyUrl' => 'notify_url',
        'orderDesc' => 'username',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'versionId',
        'businessType',
        'insCode',
        'merId',
        'orderId',
        'transDate',
        'transAmount',
        'transCurrency',
        'transChanlName',
        'openBankName',
        'pageNotifyUrl',
        'backNotifyUrl',
        'orderDesc',
        'dev',
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
        'businessType' => 1,
        'insCode' => 1,
        'merId' => 1,
        'transDate' => 1,
        'transAmount' => 1,
        'transCurrency' => 1,
        'transChanlName' => 1,
        'openBankName' => 1,
        'orderId' => 1,
        'ksPayOrderId' => 1,
        'payStatus' => 1,
        'payMsg' => 1,
        'pageNotifyUrl' => 1,
        'backNotifyUrl' => 1,
        'orderDesc' => 1,
        'dev' => 1,
    ];

    /**
     * 二維支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $scanDecodeParams = [
        'versionId' => 1,
        'businessType' => 1,
        'transChanlName' => 1,
        'orderId' => 1,
        'transDate' => 1,
        'ksPayOrderId' => 1,
        'transAmount' => 1,
        'refcode' => 1,
        'refMsg' => 1,
        'orderDesc' => 1,
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
        1 => '1001', // 工商銀行
        2 => '1005', // 交通銀行
        3 => '1002', // 農業銀行
        4 => '1004', // 建設銀行
        5 => '1012', // 招商銀行
        6 => '1010', // 民生銀行總行
        8 => '1014', // 上海浦東發展銀行
        9 => '1016', // 北京銀行
        10 => '1013', // 興業銀行
        11 => '1007', // 中信銀行
        12 => '1008', // 光大銀行
        13 => '1009', // 華夏銀行
        14 => '1017', // 廣東發展銀行
        15 => '1011', // 平安銀行
        16 => '1006', // 中國郵政
        17 => '1003', // 中國銀行
        19 => '1025', // 上海銀行
        234 => '1103', // 北京農商行
        1090 => '0002', // 微信_二維
        1092 => '0003', // 支付寶_二維
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
        if (!array_key_exists($this->requestData['transChanlName'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $this->requestData['transChanlName'] = $this->bankMap[$this->requestData['transChanlName']];
        $this->requestData['transAmount'] = sprintf('%.2f', $this->requestData['transAmount']);
        $createAt = new \Datetime($this->requestData['transDate']);
        $this->requestData['transDate'] = $createAt->format('YmdHis');

        // 二維支付
        if (in_array($this->options['paymentVendorId'], [1090, 1092])) {
            // 移除不需要的參數
            unset($this->requestData['transCurrency']);
            unset($this->requestData['pageNotifyUrl']);
            unset($this->requestData['insCode']);
            unset($this->requestData['openBankName']);

            $this->requestData['signData'] = $this->scanEncode();

            if (trim($this->options['verify_url']) == '') {
                throw new PaymentException('No verify_url specified', 180140);
            }

            $curlParam = [
                'method' => 'POST',
                'uri' => '/api/pay/onlinepay.json',
                'ip' => $this->options['verify_ip'],
                'host' => $this->options['verify_url'],
                'param' => http_build_query($this->requestData),
                'header' => [],
            ];

            $result = $this->curlRequest($curlParam);
            $parseData = json_decode($result, true);

            if (!isset($parseData['status']) || !isset($parseData['refMsg'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            // 轉字串編碼
            $detach = ['GB2312', 'UTF-8', 'GBK'];
            $charset = mb_detect_encoding(urldecode($parseData['refMsg']), $detach);
            $str = iconv($charset, 'utf-8', (urldecode($parseData['refMsg'])));

            if ($parseData['status'] !== '00' ) {
                throw new PaymentConnectionException($str, 180130, $this->getEntryId());
            }

            if (!isset($parseData['codeImgUrl'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            return [
                'post_url' => $parseData['codeImgUrl'],
                'params' => [],
            ];
        }

        // 設定加密簽名
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

        // 調整二維解密驗證時需要加密的參數
        if (in_array($entry['payment_vendor_id'], [1090, 1092])) {
            $this->decodeParams = $this->scanDecodeParams;
        }

        // 驗證返回參數
        $this->payResultVerify();

        $encodeData = [];
        // 組合二維參數驗證加密簽名
        if (in_array($entry['payment_vendor_id'], [1090, 1092])) {
            foreach (array_keys($this->decodeParams) as $paymentKey) {
                if (array_key_exists($paymentKey, $this->options) && $this->options[$paymentKey] !== '') {
                    $encodeData[$paymentKey] = $this->options[$paymentKey];
                }
            }

            ksort($encodeData);
            $encodeData['key'] = $this->privateKey;
            $encodeStr = urldecode(http_build_query($encodeData));
        }

        // 組合網銀參數驗證加密簽名
        if (!in_array($entry['payment_vendor_id'], [1090, 1092])) {
            foreach (array_keys($this->decodeParams) as $paymentKey) {
                if (array_key_exists($paymentKey, $this->options)) {
                    $encodeData[$paymentKey] = $this->options[$paymentKey];
                }
            }

            $encodeStr = urldecode(http_build_query($encodeData));
            $encodeStr .= $this->privateKey;
        }

        // 沒有返回簽名就要丟例外
        if (!isset($this->options['signData'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['signData'] != strtoupper(md5($encodeStr))) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        // 調整支付狀態參數，網銀:payStatus，二維:refcode
        if (isset($this->options['payStatus'])) {
            $status = $this->options['payStatus'];
        }

        if (isset($this->options['refcode'])) {
            $status = $this->options['refcode'];
        }

        // 檢查是否支付成功
        if ($status !== '00') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['orderId'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['transAmount'] != $entry['amount']) {
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

        $encodeStr = urldecode(http_build_query($encodeData));
        $encodeStr .= $this->privateKey;

        return strtoupper(md5($encodeStr));
    }

    /**
     * 二維支付時的加密
     *
     * @return string
     */
    protected function scanEncode()
    {
        $encodeData = [];

        // 加密設定，參數為空不納簽
        foreach ($this->encodeParams as $index) {
            if (isset($this->requestData[$index]) && $this->requestData[$index] !== '') {
                $encodeData[$index] = $this->requestData[$index];
            }
        }

        ksort($encodeData);
        $encodeData['key'] = $this->privateKey;
        $encodeStr = urldecode(http_build_query($encodeData));

        return strtoupper(md5($encodeStr));
    }
}

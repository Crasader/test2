<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 百錢付
 */
class BaiQianPay extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'X1_Amount' => '', // 支付金額，保留小數點兩位，單位：元
        'X2_BillNo' => '', // 訂單號
        'X3_MerNo' => '', // 商戶號
        'X4_ReturnURL' => '', // 通知網址
        'X6_MD5info' => '', // 簽名
        'X7_PaymentType' => '', // 銀行代碼
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'X1_Amount' => 'amount',
        'X2_BillNo' => 'orderId',
        'X3_MerNo' => 'number',
        'X4_ReturnURL' => 'notify_url',
        'X7_PaymentType' => 'paymentVendorId',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'X1_Amount',
        'X2_BillNo',
        'X3_MerNo',
        'X4_ReturnURL',
    ];

    /**
     * 返回驗證時需要加密的參數
     *     0: 有返回就要加密的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'MerNo' => 1,
        'Amount' => 1,
        'BillNo' => 1,
        'Succeed' => 1,
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
        1 => 'ICBC', // 工商銀行
        2 => 'BOCO', // 交通銀行
        3 => 'ABC', // 農業銀行
        4 => 'CCB', // 建設銀行
        5 => 'CMBCHINA', // 招商銀行
        6 => 'CMBC', // 民生銀行總行
        7 => 'SDB', // 深圳發展銀行
        8 => 'SPDB', // 上海浦東發展銀行
        9 => 'BCCB', // 北京銀行
        10 => 'CIB', // 興業銀行
        11 => 'ECITIC', // 中信銀行
        12 => 'CEB', // 光大銀行
        13 => 'HXB', // 華夏銀行
        14 => 'GDB', // 廣東發展銀行
        15 => 'PINGANBANK', // 平安銀行
        16 => 'POST', // 中國郵政
        17 => 'BOC', // 中國銀行
        19 => 'SHB', // 上海銀行
        217 => 'CBHB', // 渤海銀行
        220 => 'HZBANK', // 杭州銀行
        221 => 'CZ', // 浙商銀行
        223 => 'HKBEA', // 東亞銀行
        226 => 'NJCB', // 南京銀行
        1090 => 'WXSM', // 微信_二維
        1092 => 'ALIPAYSM', // 支付寶_二維
        1097 => 'WXH5', // 微信_手機
        1103 => 'QQSM', // QQ_二維
        1107 => 'JDSM', // 京東_二維
        1111 => 'BSM', // 銀聯快捷_二維
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
        if (!array_key_exists($this->requestData['X7_PaymentType'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $this->requestData['X7_PaymentType'] = $this->bankMap[$this->requestData['X7_PaymentType']];
        $this->requestData['X1_Amount'] = sprintf('%.2f', $this->requestData['X1_Amount']);

        // 設定加密簽名
        $this->requestData['X6_MD5info'] = $this->encode();

        // 二維
        if (in_array($this->options['paymentVendorId'], [1090, 1092, 1103, 1107, 1111])) {
            // 額外的參數設定
            $this->requestData['isApp'] = 'app';

            if (trim($this->options['verify_url']) == '') {
                throw new PaymentException('No verify_url specified', 180140);
            }

            $curlParam = [
                'method' => 'POST',
                'uri' => '/webezf/web/?app_act=openapi/bq_pay/pay',
                'ip' => $this->options['verify_ip'],
                'host' => $this->options['verify_url'],
                'param' => http_build_query($this->requestData),
                'header' => [],
            ];

            $result = $this->curlRequest($curlParam);
            $parseData = json_decode($result, true);

            if (!isset($parseData['status']) || !isset($parseData['msg'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            if ($parseData['status'] !== 88) {
                throw new PaymentConnectionException($parseData['msg'], 180130, $this->getEntryId());
            }

            if (!isset($parseData['imgUrl'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            $this->setQrcode($parseData['imgUrl']);

            return [];
        }

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

        $encodeStr = urldecode(http_build_query($encodeData)) . '&';
        $encodeStr .= strtoupper(md5($this->privateKey));

        // 沒有返回簽名就要丟例外
        if (!isset($this->options['MD5info'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if (strcasecmp($this->options['MD5info'], strtoupper(md5($encodeStr))) != 0) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['Succeed'] !== '88') {
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

        foreach ($this->encodeParams as $index) {
            if (isset($this->requestData[$index])) {
                $encodeData[$index] = $this->requestData[$index];
            }
        }

        ksort($encodeData);

        // 依key1=value1&key2=value2&...&keyN=valueN之後做md5
        $encodeStr = urldecode(http_build_query($encodeData)) . '&';
        $encodeStr .= strtoupper(md5($this->privateKey));

        return strtoupper(md5($encodeStr));
    }
}

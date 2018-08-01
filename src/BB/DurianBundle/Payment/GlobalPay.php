<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 全球付
 */
class GlobalPay extends PaymentBase
{
    /**
     * 支付提交參數
     *
     * @var array
     */
    protected $requestData = [
        'mer_no' => '', // 商號
        'mer_order_no' => '', // 訂單號
        'channel_code' => '', // 銀行代碼
        'trade_amount' => '', // 交易金額，單位:元
        'service_type' => 'b2c', // 業務類型，預設b2c網銀
        'order_date' => '', // 訂單提交時間，格式:yyyy-MM-dd HH:mm:ss
        'page_url' => '', // 同步通知網址
        'back_url' => '', // 異步通知網址
        'sign_type' => 'MD5', // 簽名類型
        'sign' => '', // 簽名
    ];

    /**
     * 支付提交參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'mer_no' => 'number',
        'mer_order_no' => 'orderId',
        'channel_code' => 'paymentVendorId',
        'trade_amount' => 'amount',
        'order_date' => 'orderCreateDate',
        'page_url' => 'notify_url',
        'back_url' => 'notify_url',

    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'mer_no',
        'mer_order_no',
        'channel_code',
        'trade_amount',
        'service_type',
        'order_date',
        'page_url',
        'back_url',
    ];

    /**
     * 返回驗證時需要加密的參數
     *     0: 有返回就要加密的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'trade_result' => 1,
        'mer_no' => 1,
        'mer_return_msg' => 0,
        'mer_order_no' => 1,
        'notify_type' => 1,
        'currency' => 1,
        'trade_amount' => 1,
        'order_date' => 1,
        'pay_date' => 0,
        'order_no' => 1,
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
        2 => 'BCM', // 交通銀行
        3 => 'ABC', // 農業銀行
        4 => 'CCB', // 建設銀行
        5 => 'CMB', // 招商銀行
        6 => 'CMBC', // 民生銀行總行
        8 => 'SPDB', // 上海浦東發展銀行
        9 => 'BOB', // 北京銀行
        10 => 'CIB', // 興業銀行
        11 => 'CITIC', // 中信銀行
        12 => 'CEB', // 光大銀行
        13 => 'HXB', // 華夏銀行
        14 => 'GDB', // 廣東發展銀行
        15 => 'PAB', // 平安銀行
        16 => 'PSBC', // 中國郵政
        17 => 'BOC', // 中國銀行
        19 => 'SHBANK', // 上海銀行
        278 => 'quick-web', // 銀聯在線
        1088 => 'quick-web', // 銀聯在線_H5
        1103 => 'qq_scan', // QQ_二維
        1107 => 'jd_scan', // 京東錢包_二維
    ];

    /**
     * 回傳支付時要給支付平台驗證的資料
     *
     * @return array
     */
    public function getVerifyData()
    {
        $this->verifyPrivateKey();
        $this->payVerify();

        // 從內部設值到支付參數
        foreach ($this->requireMap as $paymentKey => $internalKey) {
            $this->requestData[$paymentKey] = $this->options[$internalKey];
        }

        // 檢查銀行代碼是否支援
        if (!array_key_exists($this->requestData['channel_code'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $this->requestData['channel_code'] = $this->bankMap[$this->requestData['channel_code']];
        $this->requestData['trade_amount'] = sprintf('%.2f', $this->requestData['trade_amount']);
        $createAt = new \Datetime($this->requestData['order_date']);
        $this->requestData['order_date'] = $createAt->format('Y-m-d H:i:s');

        // 銀聯在線
        if (in_array($this->options['paymentVendorId'], [278, 1088])) {
            $this->requestData['service_type'] = $this->bankMap[$this->options['paymentVendorId']];

            unset($this->requestData['channel_code']);
        }

        // 二維支付
        if (in_array($this->options['paymentVendorId'], [1103, 1107])) {
            $this->requestData['service_type'] = $this->bankMap[$this->options['paymentVendorId']];

            unset($this->requestData['channel_code']);

            $this->requestData['sign'] = $this->encode();

            if (trim($this->options['verify_url']) == '') {
                throw new PaymentException('No verify_url specified', 180140);
            }

            $curlParam = [
                'method' => 'POST',
                'uri' => '/payment/api/scanpay',
                'ip' => $this->options['verify_ip'],
                'host' => $this->options['verify_url'],
                'param' => urldecode(http_build_query($this->requestData)),
                'header' => [],
            ];

            $result = $this->curlRequest($curlParam);
            $parseData = json_decode($result, true);

            if (!isset($parseData['auth_result']) || !isset($parseData['trade_result'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            if (($parseData['auth_result'] != 'SUCCESS' || $parseData['trade_result'] != 3) &&
                !isset($parseData['error_msg'])
            ) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            if ($parseData['auth_result'] != 'SUCCESS' || $parseData['trade_result'] != 3) {
                throw new PaymentConnectionException($parseData['error_msg'], 180130, $this->getEntryId());
            }

            if (!isset($parseData['trade_return_msg']) || $parseData['trade_return_msg'] == '') {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            $this->setQrcode($parseData['trade_return_msg']);

            return [];
        }

        // 設定加密簽名
        $this->requestData['sign'] = $this->encode();

        return $this->requestData;
    }

    /**
     * 驗證線上支付是否成功
     *
     * @param array $entry
     */
    public function verifyOrderPayment($entry)
    {
        $this->verifyPrivateKey();
        $this->payResultVerify();

        // 組合參數驗證加密簽名
        $encodeData = [];

        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options) && trim($this->options[$paymentKey]) !== '') {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        ksort($encodeData);
        $encodeData['key'] = $this->privateKey;

        $encodeStr = urldecode(http_build_query($encodeData));

        // 沒有返回sign就要丟例外
        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['sign'] != strtoupper(md5($encodeStr))) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['trade_result'] == '0') {
            throw new PaymentConnectionException('Unpaid order', 180062, $this->getEntryId());
        }

        if ($this->options['trade_result'] != '1') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['mer_order_no'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['trade_amount'] != $entry['amount']) {
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
            if (array_key_exists($index, $this->requestData) && trim($this->requestData[$index] !== '')) {
                $encodeData[$index] = $this->requestData[$index];
            }
        }

        ksort($encodeData);

        $encodeData['key'] = $this->privateKey;

        $encodeStr = urldecode(http_build_query($encodeData));

        return strtoupper(md5($encodeStr));
    }
}

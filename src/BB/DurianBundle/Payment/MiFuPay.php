<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 密付支付
 */
class MiFuPay extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'merchant' => '', // 商戶號
        'billno' => '', // 訂單號
        'amount' => '',  // 金額，單位：元
        'notify_url' => '', // 異步通知網址
        'return_url' => '', // 同步通知網址
        'sign_type' => 'MD5', // 簽名類型
        'bank' => '', // 交易類型
        'pay_time' => '', // 請求時間，格式YmdHis
        'sign' => '', // 簽名
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'merchant' => 'number',
        'billno' => 'orderId',
        'amount' => 'amount',
        'notify_url' => 'notify_url',
        'return_url' => 'notify_url',
        'bank' => 'paymentVendorId',
        'pay_time' => 'orderCreateDate',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'merchant',
        'billno',
        'amount',
        'sign_type',
        'bank',
        'pay_time',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'amount' => 1,
        'bank' => 1,
        'billno' => 1,
        'code' => 1,
        'merchant' => 1,
        'msg' => 1,
        'pay_time' => 1,
        'sign_type' => 1,
        'status' => 1,
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
        '1' => 'ICBC', // 工商銀行
        '2' => 'BOCOM', // 交通銀行
        '3' => 'ABOC', // 中國農業銀行
        '4' => 'CCB', // 中國建設銀行
        '5' => 'CMB', // 招商銀行
        '6' => 'CMBC', // 中國民生銀行
        '8' => 'SPDB', // 上海浦東發展銀行
        '9' => 'BOBJ', // 北京銀行
        '10' => 'CIB', // 興業銀行
        '11' => 'ECITIC', // 中信銀行
        '12' => 'CEB', // 中國光大銀行
        '13' => 'HXB', // 華夏銀行
        '14' => 'CGB', // 廣東發展銀行
        '15'  => 'SDB', //平安銀行
        '16' => 'PSBC', // 中國郵政
        '17' => 'BOC', // 中國銀行
        '19' => 'BOS', // 上海銀行
        '1090' => 'WECHAT', // 微信_二維
        '1092' => 'ALIPAY', // 支付寶_二維
        '1098' => 'H5_ALIPAY', // 支付寶_手機
        '1103' => 'QQ', // QQ_二維
        '1111' => 'UNION', // 銀聯錢包_二維
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

        // 從內部給定值到參數
        foreach ($this->requireMap as $paymentKey => $internalKey) {
            $this->requestData[$paymentKey] = $this->options[$internalKey];
        }

        // 檢查銀行代碼是否支援
        if (!array_key_exists($this->requestData['bank'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $this->requestData['amount'] = sprintf('%.2f', $this->requestData['amount']);
        $this->requestData['bank'] = $this->bankMap[$this->requestData['bank']];
        $orderCreateDate = new \DateTime($this->requestData['pay_time']);
        $this->requestData['pay_time'] = $orderCreateDate->format('YmdHis');

        // 設定支付平台需要的加密串
        $this->requestData['sign'] = $this->encode();

        // 支付寶手機
        if (in_array($this->options['paymentVendorId'], [1098])) {
            if (trim($this->options['verify_url']) == '') {
                throw new PaymentException('No verify_url specified', 180140);
            }

            $curlParam = [
                'method' => 'POST',
                'uri' => '/api/pay',
                'ip' => $this->options['verify_ip'],
                'host' => $this->options['verify_url'],
                'param' => http_build_query($this->requestData),
                'header' => [],
            ];

            $result = $this->curlRequest($curlParam);
            $parseData = json_decode($result, true);

            if (!isset($parseData['code'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            if ($parseData['code'] !== '1000') {
                throw new PaymentConnectionException('Pay error', 180130, $this->getEntryId());
            }

            if (!isset($parseData['qrCode'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            $urlData = $this->parseUrl($parseData['qrCode']);

            // Form使用GET才能正常跳轉
            $this->payMethod = 'GET';

            return [
                'post_url' => $urlData['url'],
                'params' => $urlData['params'],
            ];

            $this->setQrcode(urldecode($parseData['qrCode']));

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
        $this->verifyPrivateKey();

        $this->payResultVerify();

        $encodeData = [];

        // 組織加密串
        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options)) {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        // 針對$encodeData按字母做升序排列
        ksort($encodeData);

        $encodeData['key'] = $this->privateKey;

        // 依key1=value1&key2=value2&...&keyN=valueN
        $encodeStr = urldecode(http_build_query($encodeData));

        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['sign'] != md5($encodeStr)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['status'] !== '110') {
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
        $encodeData = [];

        foreach ($this->encodeParams as $key) {
            $encodeData[$key] = $this->requestData[$key];
        }

        // 針對$encodeData按字母做升序排列
        ksort($encodeData);

        $encodeData['key'] = $this->privateKey;

        // 依key1=value1&key2=value2&...&keyN=valueN
        $encodeStr = urldecode(http_build_query($encodeData));

        return md5($encodeStr);
    }
}

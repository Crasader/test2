<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 銀號支付
 */
class YinHaoPay extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'assCode' => '', // 商號
        'assPayOrderNo' => '', // 商戶訂單號
        'assPayMoney' => '', // 支付金額，單位為分
        'assNotifyUrl' => '', // 回調網址
        'assReturnUrl' => '', // 返回地址
        'assCancelUrl' => '', // 取消地址
        'paymentType' => 'gate_web_direct', // 支付類型，網銀直連：gate_web_direct
        'subPayCode' => '', // 銀行編碼，網銀直連用
        'sign' => '', // MD5簽名
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'assCode' => 'number',
        'assPayOrderNo' => 'orderId',
        'assPayMoney' => 'amount',
        'assNotifyUrl' => 'notify_url',
        'assReturnUrl' => 'notify_url',
        'assCancelUrl' => 'notify_url',
        'subPayCode' => 'paymentVendorId',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'assCode',
        'assPayOrderNo',
        'assPayMoney',
        'assNotifyUrl',
        'assReturnUrl',
        'assCancelUrl',
        'paymentType',
        'subPayCode',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'assCode' => 1,
        'sysPayOrderNo' => 1,
        'assPayOrderNo' => 1,
        'succTime' => 1,
        'assPayMoney' => 1,
        'respCode' => 1,
        'respMsg' => 1,
        'assPayMessage' => 1,
    ];

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        '1' =>'ICBC', // 中國工商銀行
        '2' =>'BOCOM', // 交通銀行
        '3' =>'ABC', // 中國農業銀行
        '4' =>'CCB', // 中國建設銀行
        '5' =>'CMB', // 招商銀行
        '6' =>'CMBC', // 中國民生銀行
        '8' =>'SPDB', // 上海浦東發展銀行
        '9' =>'BOB', // 北京銀行
        '10' =>'CIB', // 興業銀行
        '11' =>'ECITIC', // 中信銀行
        '12' =>'CEB', // 中國光大銀行
        '14' =>'CGB', // 廣東發展銀行
        '15' =>'PAB', // 深圳平安銀行
        '16' =>'PSBC', // 中國郵政
        '17' =>'BOC', // 中國銀行
        '1088' => 'gate_h5', // 銀聯在線_手機支付
        '1092' => 'ali_qrcode', // 支付寶_二維
        '1098' => 'ali_h5_wake', // 支付寶_手機支付
        '1102' => 'gate_web_syt', // 網銀_收銀台
        '1111' => 'gate_qrcode', // 銀聯_二維
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
        if (!array_key_exists($this->requestData['subPayCode'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $this->requestData['assPayMoney'] = round($this->requestData['assPayMoney'] * 100);
        $this->requestData['subPayCode'] = $this->bankMap[$this->requestData['subPayCode']];

        // 手機支付、二維支付、網銀收銀台參數設定
        if (in_array($this->options['paymentVendorId'], [1088, 1092, 1098, 1102, 1111])) {
            $this->requestData['paymentType'] = $this->requestData['subPayCode'];
        }

        $this->requestData['sign'] = $this->encode();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'method' => 'POST',
            'uri' => '/ebank/pay/BgTrans',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => http_build_query($this->requestData),
            'header' => [],
        ];

        $result = $this->curlRequest($curlParam);
        $parseData = json_decode($result, true);

        if (!isset($parseData['code']) || !isset($parseData['message'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        if ($parseData['code'] !== '10000') {
            throw new PaymentConnectionException($parseData['message'], 180130, $this->getEntryId());
        }

        if (!isset($parseData['payUrl'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        // 支付寶二維
        if ($this->options['paymentVendorId'] == '1092') {
            $this->setQrcode($parseData['payUrl']);

            return [];
        }

        $urlData = $this->parseUrl($parseData['payUrl']);

        // Form使用GET才能正常跳轉
        $this->payMethod = 'GET';

        return [
            'post_url' => $urlData['url'],
            'params' => $urlData['params'],
        ];
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

        // 組織加密串
        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options)) {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        ksort($encodeData);
        $encodeStr = '&';
        $encodeStr .= urldecode(http_build_query($encodeData));
        $encodeStr .= $this->privateKey;

        // 沒有sign就要丟例外
        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['sign'] != strtoupper(md5($encodeStr))) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['respCode'] != '60006') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['assPayOrderNo'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['assPayMoney'] != round($entry['amount'] * 100)) {
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
            $encodeData[$index] = $this->requestData[$index];
        }

        ksort($encodeData);
        $encodeStr = '&';
        $encodeStr .= urldecode(http_build_query($encodeData));
        $encodeStr .= $this->privateKey;

        return strtoupper(md5($encodeStr));
    }
}

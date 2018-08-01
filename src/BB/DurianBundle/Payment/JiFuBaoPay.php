<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 集付寶支付
 */
class JiFuBaoPay extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'transTypeNo' => '', // 交易類型編號
        'signature' => '', // 簽名
        'merchantNum' => '', // 商戶編碼
        'backUrl' => '', // 異步通知網址
        'orderId' => '', // 訂單號
        'txnAmt' => '', // 訂單交易，單位：分
        'txnTime' => '', // 訂單發送時間，格式YmdHis
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'transTypeNo' => 'paymentVendorId',
        'merchantNum' => 'number',
        'backUrl' => 'notify_url',
        'orderId' => 'orderId',
        'txnAmt' => 'amount',
        'txnTime' => 'orderCreateDate',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'transTypeNo',
        'merchantNum',
        'orderId',
        'txnTime',
        'txnAmt',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'transTypeNo' => 1,
        'merchantNum' => 1,
        'orderId' => 1,
        'txnTime' => 1,
        'txnAmt' => 1,
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
        278 => 'T0000301', // 銀聯在線(快捷)
        1088 => 'T0000301', // 銀連在線_手機支付(快捷)
        1090 => 'T0000101', // 微信_二維
        1092 => 'T0000102', // 支付寶_二維
        1097 => 'T0000201', // 微信_手機支付
        1098 => 'T0000202', // 支付寶_手機支付
        1100 => 'T0000401', // 手機收銀台
        1102 => 'T0000401', // 網銀收銀台
        1103 => 'T0000103', // QQ_二維
        1107 => 'T0000204', // 京東_手機支付
        1111 => 'T0000104', // 銀聯_二維
        1120 => 'T0000203', // 銀聯_手機支付
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

        // 帶入未支援的銀行就噴例外
        if (!array_key_exists($this->requestData['transTypeNo'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $this->requestData['transTypeNo'] = $this->bankMap[$this->requestData['transTypeNo']];
        $this->requestData['txnAmt'] = round($this->requestData['txnAmt'] * 100);
        $createAt = new \Datetime($this->requestData['txnTime']);
        $this->requestData['txnTime'] = $createAt->format('YmdHis');

        // 設定支付平台需要的加密串
        $this->requestData['signature'] = $this->encode();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'method' => 'POST',
            'uri' => '/order/pre',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => http_build_query($this->requestData),
            'header' => [],
        ];
        $result = $this->curlRequest($curlParam);
        $parseData = json_decode($result, true);

        if (!isset($parseData['respCode'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        if ($parseData['respCode'] !== '66') {
            if (isset($parseData['respMsg'])) {
                throw new PaymentConnectionException($parseData['respMsg'], 180130, $this->getEntryId());
            }

            throw new PaymentConnectionException('Pay error', 180130, $this->getEntryId());
        }

        if (!isset($parseData['payInfo'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        // 二維支付
        if (in_array($this->options['paymentVendorId'], [1090, 1092, 1103, 1111])) {
            $this->setQrcode($parseData['payInfo']);

            return [];
        }

        $urlData = $this->parseUrl($parseData['payInfo']);

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

        $encodeData = [];

        foreach (array_keys($this->decodeParams) as $paymentKey) {
            $encodeData[$paymentKey] = $this->options[$paymentKey];
        }

        // 密鑰需要放在第五個位置
        array_splice($encodeData, 4, 0, $this->privateKey);

        $encodeStr = implode('', $encodeData);

        // 若沒有返回簽名需丟例外
        if (!isset($this->options['signature'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if (strcasecmp($this->options['signature'], md5($encodeStr)) != 0) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['respCode'] !== '00') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['orderId'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['txnAmt'] != round($entry['amount'] * 100)) {
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
            $encodeData[] = $this->requestData[$index];
        }

        // 密鑰需要放在第五個位置
        array_splice($encodeData, 4, 0, $this->privateKey);

        $encodeStr = implode('', $encodeData);

        return strtoupper(md5($encodeStr));
    }
}

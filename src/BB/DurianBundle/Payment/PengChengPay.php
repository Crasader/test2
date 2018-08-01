<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 鵬城支付
 */
class PengChengPay extends PaymentBase
{
    /**
     * 支付提交參數
     *
     * @var array
     */
    protected $requestData = [
        'requestBody' => '',
    ];

    /**
     * requestBody參數
     *
     * @var array
     */
    private $requestBody = [
        'mchNo' => '', // 商號
        'orderID' => '', // 訂單號
        'money' => '', // 支付金額，單位:元，精確到小數兩位
        'body' => '', // 商品描述
        'payType' => '', // 支付類型
        'notifyUrl' => '', // 回調地址，base64編碼
        'callbackurl' => '', // 返回地址，base64編碼
        'sign' => '', // 簽名
    ];

    /**
     * 支付提交參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'mchNo' => 'number',
        'orderID' => 'orderId',
        'money' => 'amount',
        'body' => 'orderId',
        'payType' => 'paymentVendorId',
        'notifyUrl' => 'notify_url',
        'callbackurl' => 'notify_url',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'mchNo',
        'orderID',
        'money',
        'body',
        'payType',
        'notifyUrl',
        'callbackurl',
    ];

    /**
     * 返回驗證時需要加密的參數
     *     0: 有返回就要加密的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'orderID' => 1,
        'money' => 1,
        'status' => 1,
    ];

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        '278' => 'qpay', // 銀聯在線
        '1088' => 'qpay', // 銀聯在線_手機支付
        '1090' => 'wxsm', // 微信_二維
        '1102' => 'wy', // 網銀收銀台
        '1103' => 'qqsm', // QQ_二維
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
            $this->requestBody[$paymentKey] = $this->options[$internalKey];
        }

        // 檢查銀行代碼是否支援
        if (!array_key_exists($this->requestBody['payType'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $this->requestBody['payType'] = $this->bankMap[$this->requestBody['payType']];
        $this->requestBody['money'] = strval(sprintf('%.2f', $this->requestBody['money']));
        $this->requestBody['notifyUrl'] = base64_encode($this->requestBody['notifyUrl']);
        $this->requestBody['callbackurl'] = base64_encode($this->requestBody['callbackurl']);

        // 設定支付平台需要的加密串
        $this->requestBody['sign'] = $this->encode();

        $this->requestData['requestBody'] = json_encode($this->requestBody);

        // 二維
        if (in_array($this->options['paymentVendorId'], ['1090', '1103'])) {
            if (trim($this->options['verify_url']) == '') {
                throw new PaymentException('No verify_url specified', 180140);
            }

            $curlParam = [
                'method' => 'POST',
                'uri' => '/Payapi_Index_Pay.html',
                'ip' => $this->options['verify_ip'],
                'host' => $this->options['verify_url'],
                'param' => http_build_query($this->requestData),
                'header' => [],
            ];

            $result = $this->curlRequest($curlParam);
            $parseData = json_decode($result, true);

            if (!isset($parseData['resultCode'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            if ($parseData['resultCode'] !== '0000' && isset($parseData['resultMsg'])) {
                throw new PaymentConnectionException($parseData['resultMsg'], 180130, $this->getEntryId());
            }

            if ($parseData['resultCode'] !== '0000') {
                throw new PaymentConnectionException('Pay error', 180130, $this->getEntryId());
            }

            if (!isset($parseData['codeImageUrl']) || $parseData['codeImageUrl'] == '') {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            $this->setQrcode($parseData['codeImageUrl']);

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

        // 組合參數驗證加密簽名
        $encodeStr = $this->privateKey;

        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options)) {
                $encodeStr .= $this->options[$paymentKey];
            }
        }

        // 沒有返回sign就要丟例外
        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['sign'] !== md5($encodeStr)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['status'] != 'TRADE_FINISHED') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['orderID'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['money'] != $entry['amount']) {
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
            if (array_key_exists($index, $this->requestBody)) {
                $encodeData[$index] = $this->requestBody[$index];
            }
        }

        ksort($encodeData);

        $encodeData['key'] = $this->privateKey;

        $encodeStr = urldecode(http_build_query($encodeData));

        return md5($encodeStr);
    }
}

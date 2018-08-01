<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * ip-pay
 */
class IpPay extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'merchno' => '', // 商戶號
        'goodsName' => '', // 商品名稱，設定username方便業主比對(二維參數)
        'amount' => '', // 交易金額，整數，單位：元
        'traceno' => '', // 商戶訂單號
        'payType' => '', // 支付方式(二維參數)
        'bankCode' => '', // 銀行代碼
        'notifyUrl' => '', // 通知地址
        'signature' => '', // 簽名
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'merchno' => 'number',
        'goodsName' => 'username',
        'amount' => 'amount',
        'traceno' => 'orderId',
        'bankCode' => 'paymentVendorId',
        'notifyUrl' => 'notify_url',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'merchno',
        'goodsName',
        'amount',
        'traceno',
        'bankCode',
        'payType',
        'notifyUrl',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'transDate' => 1,
        'transTime' => 1,
        'merchno' => 1,
        'amount' => 1,
        'traceno' => 1,
        'payType' => 1,
        'orderno' => 1,
        'status' => 1,
    ];

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        '1' => '3002', // 工商銀行
        '2' =>'3020', // 交通銀行
        '3' =>'3005', // 中國農業銀行
        '4' =>'3003', // 中國建設銀行
        '5' =>'3001', // 招商銀行
        '6' =>'3006', // 中國民生銀行
        '8' =>'3004', // 上海浦東發展銀行
        '9' =>'3032', // 北京銀行
        '10' =>'3009', // 興業銀行
        '11' =>'3039', // 中信銀行
        '12' =>'3022', // 中國光大銀行
        '14' =>'3036', // 廣東發展銀行
        '15' =>'3035', // 深圳平安銀行
        '17' =>'3026', // 中國銀行
        '1090' => '2', // 微信_二維
        '1103' => '4', // QQ_二維
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

        // 從內部給定值到參數
        foreach ($this->requireMap as $paymentKey => $internalKey) {
            $this->requestData[$paymentKey] = $this->options[$internalKey];
        }

        // 帶入未支援的銀行就噴例外
        if (!array_key_exists($this->requestData['bankCode'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 商家額外的參數設定
        $this->requestData['bankCode'] = $this->bankMap[$this->requestData['bankCode']];

        // 二維支付
        if (in_array($this->options['paymentVendorId'], [1090, 1103])) {
            // 額外的參數設定
            $this->requestData['payType'] = $this->requestData['bankCode'];
            unset($this->requestData['bankCode']);

            // 設定支付平台需要的加密串
            $this->requestData['signature'] = $this->encode();

            if (trim($this->options['verify_url']) == '') {
                throw new PaymentException('No verify_url specified', 180140);
            }

            $curlParam = [
                'method' => 'POST',
                'uri' => '/passivePay.php',
                'ip' => $this->options['verify_ip'],
                'host' => $this->options['verify_url'],
                'param' => urldecode(http_build_query($this->requestData)),
                'header' => [],
            ];

            $result = $this->curlRequest($curlParam);
            $parseData = json_decode($result, true);

            if (!isset($parseData['respCode']) || !isset($parseData['message'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            if ($parseData['respCode'] !== '00') {
                throw new PaymentConnectionException($parseData['message'], 180130, $this->getEntryId());
            }

            if (!isset($parseData['barCode'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            $this->setQrcode($parseData['barCode']);

            return [];
        }

        // 移除二維支付使用的參數
        unset($this->requestData['goodsName']);
        unset($this->requestData['payType']);

        $this->requestData['signature'] = $this->encode();

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

        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options) && trim($this->options[$paymentKey]) != '') {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        ksort($encodeData);

        $encodeStr = urldecode(http_build_query($encodeData));
        $encodeStr .= '&' . $this->privateKey;

        // 若沒有返回簽名需丟例外
        if (!isset($this->options['signature'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if (strcasecmp($this->options['signature'], md5($encodeStr)) != 0) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['status'] == '0') {
            throw new PaymentConnectionException('Unpaid order', 180062, $this->getEntryId());
        }

        if ($this->options['status'] != '1') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['traceno'] != $entry['id']) {
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

        foreach ($this->encodeParams as $index) {
            if (isset($this->requestData[$index]) && $this->requestData[$index] != '') {
                $encodeData[$index] = $this->requestData[$index];
            }
        }

        ksort($encodeData);

        $encodeStr = urldecode(http_build_query($encodeData));
        $encodeStr .= '&' . $this->privateKey;

        return md5($encodeStr);
    }
}

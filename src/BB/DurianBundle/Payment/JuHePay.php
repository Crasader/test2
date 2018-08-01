<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 聚合支付
 */
class JuHePay extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'merchantOutOrderNo' => '', // 訂單編號
        'merid' => '', // 商戶號
        'noncestr' => '', // 隨機參數，長度小於32，設定username方便業主比對
        'notifyUrl' => '', // 異步通知網址
        'orderMoney' => '', // 交易金額，單位：元，保留到小數點第二位
        'orderTime' => '', // 訂單時間
        'sign' => '', // 簽名
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'merchantOutOrderNo' => 'orderId',
        'merid' => 'number',
        'noncestr' => 'username',
        'notifyUrl' => 'notify_url',
        'orderMoney' => 'amount',
        'orderTime' => 'orderCreateDate',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'merchantOutOrderNo',
        'merid',
        'noncestr',
        'notifyUrl',
        'orderMoney',
        'orderTime',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'merchantOutOrderNo' => 1,
        'merid' => 1,
        'msg' => 1,
        'noncestr' => 1,
        'orderNo' => 1,
        'payResult' => 1,
    ];

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        1090 => '/api/createWeixinOrder', // 微信_二維
        1092 => '/api/createPcOrder', // 支付寶_二維
        1098 => '/api/createOrder', // 支付寶_手機支付
        1100 => '/api/createQuickOrder', // 收銀檯(手機端)
        1102 => '/api/createQuickOrder', // 收銀檯(PC端)
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
        if (!array_key_exists($this->options['paymentVendorId'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 商家額外的參數設定
        $this->requestData['orderMoney'] = sprintf('%.2f', $this->requestData['orderMoney']);
        $date = new \DateTime($this->requestData['orderTime']);
        $this->requestData['orderTime'] = $date->format('YmdHis');

        // 依支付方式設定提交網址
        $uri = $this->bankMap[$this->options['paymentVendorId']];

        $this->requestData['sign'] = $this->encode();

        // 二維支付
        if (in_array($this->options['paymentVendorId'], [1090, 1092])) {

            if (trim($this->options['verify_url']) == '') {
                throw new PaymentException('No verify_url specified', 180140);
            }

            $curlParam = [
                'method' => 'POST',
                'uri' => $uri,
                'ip' => $this->options['verify_ip'],
                'host' => $this->options['verify_url'],
                'param' => http_build_query($this->requestData),
                'header' => [],
            ];

            $result = $this->curlRequest($curlParam);
            $parseData = json_decode($result, true);

            // 提交對外失敗才會回傳錯誤代碼與錯誤訊息
            if (isset($parseData['code']) && isset($parseData['msg'])) {
                throw new PaymentConnectionException($parseData['msg'], 180130, $this->getEntryId());
            }

            if (!isset($parseData['url'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            $this->setQrcode($parseData['url']);

            return [];
        }

        // 設定提交網址
        $postUrl = $this->options['postUrl'] . $uri;

        return [
            'post_url' => $postUrl,
            'params' => $this->requestData,
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
            if (array_key_exists($paymentKey, $this->options)) {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        ksort($encodeData);

        $encodeData['key'] = $this->privateKey;

        $encodeStr = urldecode(http_build_query($encodeData));

        // 若沒有返回簽名需丟例外
        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['sign'] != md5($encodeStr)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['payResult'] !== '1') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['merchantOutOrderNo'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        $msg = json_decode($this->options['msg'], true);

        if ($msg['payMoney'] != round($entry['amount'])) {
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

        // 組織加密簽名
        foreach ($this->encodeParams as $index) {
            $encodeData[$index] = $this->requestData[$index];
        }

        ksort($encodeData);

        $encodeData['key'] = $this->privateKey;

        $encodeStr = urldecode(http_build_query($encodeData));

        return md5($encodeStr);
    }
}

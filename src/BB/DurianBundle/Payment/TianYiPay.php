<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 天奕支付
 */
class TianYiPay extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'app_id' => '', // 商號
        'card_type' => '1', // 銀行卡類型，1:借記卡
        'bank_code' => '', // 銀行縮寫，網銀參數
        'order_id' => '', // 訂單號
        'order_amt' => '', // 金額，單位元，精確到分
        'notify_url' => '', // 異步通知地址，不能串參數
        'return_url' => '', // 同步通知地址
        'goods_name' => '', // 商品名稱，必填
        'extends' => '', // 備註，可空
        'time_stamp' => '', // 提交時間戳，格式:YmdHis
        'sign' => '', // 簽名
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'app_id' => 'number',
        'bank_code' => 'paymentVendorId',
        'order_id' => 'orderId',
        'order_amt' => 'amount',
        'notify_url' => 'notify_url',
        'return_url' => 'notify_url',
        'goods_name' => 'orderId',
        'time_stamp' => 'orderCreateDate',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'app_id',
        'bank_code',
        'pay_type',
        'order_id',
        'order_amt',
        'notify_url',
        'return_url',
        'time_stamp',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'app_id' => 1,
        'order_id' => 1,
        'pay_seq' => 1,
        'pay_amt' => 1,
        'pay_result' => 1,
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
        '1' => 'ICBC', // 工商銀行
        '2' => 'BOCO', // 交通銀行
        '3' => 'ABC', // 農業銀行
        '4' => 'CCB', // 建設銀行
        '5' => 'CMBCHINA', // 招商銀行
        '6' => 'CMBC', // 民生銀行總行
        '8' => 'SPDB', // 上海浦東發展銀行
        '9' => 'BCCB', // 北京銀行
        '10' => 'CIB', // 興業銀行
        '11' => 'ECITIC', // 中信銀行
        '12' => 'CEB', // 光大銀行
        '13' => 'HXB', // 華夏銀行
        '14' => 'CGB', // 廣東發展銀行
        '15' => 'PINGAN', // 平安銀行
        '16' => 'POST', // 中國郵政
        '17' => 'BOC', // 中國銀行
        '19' => 'SHB', // 上海銀行
        '221' => 'CZB', // 浙商银行
        '222' => 'NBCB', // 寧波銀行
        '234' => 'BJRCB', // 北京農村商業銀行
        '1088' => 'YLBILLWAP', // 銀聯在線_手機支付
        '1097' => '2', // 微信_手機支付
        '1098' => '2', // 支付寶_手機支付
        '1103' => '4', // QQ_二維
        '1104' => '6', // QQ_手機支付
        '1115' => '7', // 微信_條碼
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
        $this->payVerify();

        // 從內部給定值到參數
        foreach ($this->requireMap as $paymentKey => $internalKey) {
            $this->requestData[$paymentKey] = $this->options[$internalKey];
        }

        // 帶入未支援的銀行就噴例外
        if (!array_key_exists($this->requestData['bank_code'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        // 額外的參數設定
        $this->requestData['order_amt'] = sprintf("%.2f", $this->requestData['order_amt']);
        $this->requestData['bank_code'] = $this->bankMap[$this->requestData['bank_code']];
        $createAt = new \Datetime($this->requestData['time_stamp']);
        $this->requestData['time_stamp'] = $createAt->format('YmdHis');

        // 網銀uri
        $uri = '/Pay/GateWayUnionPay.aspx';

        // 二維支付、微信手機支付、QQ手機支付需調整參數及uri
        if (in_array($this->options['paymentVendorId'], [1097, 1103, 1104, 1115])) {
            // 條碼支付需要auth_code參數
            if ($this->options['paymentVendorId'] == 1115) {
                $this->requestData['auth_code'] = $this->requestData['order_id'];
            }

            $this->requestData['pay_type'] = $this->requestData['bank_code'];

            unset($this->requestData['card_type']);
            unset($this->requestData['bank_code']);

            $uri = '/Pay/GateWayTencent.aspx';
        }

        // 支付寶須調整參數及uri
        if (in_array($this->options['paymentVendorId'], [1098])) {
            $this->requestData['pay_type'] = $this->requestData['bank_code'];

            unset($this->requestData['card_type']);
            unset($this->requestData['bank_code']);

            $uri = '/Pay/GateWayAliPay.aspx';
        }

        // 設定支付平台需要的加密串
        $this->requestData['sign'] = $this->encode();

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

        if (!isset($parseData['status_code']) || !isset($parseData['status_msg'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        if ($parseData['status_code'] !== 0) {
            throw new PaymentConnectionException($parseData['status_msg'], 180130, $this->getEntryId());
        }

        if (!isset($parseData['pay_url'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        // QQ二維
        if ($this->options['paymentVendorId'] == 1103) {
            $this->setQrcode($parseData['pay_url']);

            return [];
        }

        $parseResult = $this->parseUrl($parseData['pay_url']);

        $this->payMethod = 'GET';

        return [
            'post_url' => $parseResult['url'],
            'params' => $parseResult['params'],
        ];
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
        $this->payResultVerify();

        $encodeData = [];

        // 組織加密串
        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options)) {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        $encodeData['key'] = md5($this->privateKey);
        $encodeStr = urldecode(http_build_query($encodeData));

        // 如果沒有返回簽名要丟例外
        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['sign'] != md5($encodeStr)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['pay_result'] !== '20') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['order_id'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['pay_amt'] != sprintf("%.2f", $entry['amount'])) {
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
            if (array_key_exists($index, $this->requestData)) {
                $encodeData[$index] = $this->requestData[$index];
            }
        }

        $encodeData['key'] = md5($this->privateKey);
        $encodeStr = urldecode(http_build_query($encodeData));

        return md5($encodeStr);
    }
}

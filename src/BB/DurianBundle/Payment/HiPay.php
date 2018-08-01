<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 合付支付
 */
class HiPay extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'merId' => '', // 商戶編號
        'orderNo' => '', // 訂單號
        'amount' => '', // 金額
        'payType' => '', // 支付類型
        'goodsName' => '', // 商品名稱，這邊放username
        'returnUrl' => '', // 頁面回調網址
        'notifyUrl' => '', // 異步通知網址
        'sign' => '', // 簽名
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'merId' => 'number',
        'orderNo' => 'orderId',
        'amount' => 'amount',
        'payType' => 'paymentVendorId',
        'goodsName' => 'username',
        'returnUrl' => 'notify_url',
        'notifyUrl' => 'notify_url',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'merId',
        'orderNo',
        'amount',
        'payType',
        'goodsName',
        'returnUrl',
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
        'merId' => 1,
        'orderNo' => 1,
        'amount' => 1,
        'status' => 1,
        'trxNo' => 1,
        'payTime' => 1,
        'mp' => 1,
    ];

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        1 => 'B2C_ICBC', // 工商銀行
        2 => 'B2C_BOCO', // 交通銀行
        3 => 'B2C_ABC', // 農業銀行
        4 => 'B2C_CCB', // 建設銀行
        5 => 'B2C_CMBCHINA', // 招商銀行
        6 => 'B2C_CMBC', // 民生銀行總行
        7 => 'B2C_SDB', // 深圳發展銀行
        8 => 'B2C_SPDB', // 上海浦東發展銀行
        9 => 'B2C_BCCB', // 北京銀行
        10 => 'B2C_CIB', // 興業銀行
        11 => 'B2C_ECITIC', // 中信銀行
        12 => 'B2C_CEB', // 光大銀行
        13 => 'B2C_HXB', // 華夏銀行
        15 => 'B2C_PINGANBANK', // 平安銀行
        16 => 'B2C_POST', // 中國郵政
        17 => 'B2C_BOC', // 中國銀行
        279 => 'NO_CARD', // 銀聯無卡
        222 => 'B2C_NBCB', // 寧波銀行
        223 => 'B2C_NBCB', // 東亞銀行
        226 => 'B2C_NJCB', // 南京銀行
        1090 => 'WEIXIN_NATIVE', // 微信_二維
        1092 => 'ALIPAY_NATIVE', // 支付寶_二維
        1093 => 'NO_CARD', // 銀聯無卡_手機支付
        1097 => 'WEIXIN_H5', // 微信_手機支付
        1103 => 'QQ_NATIVE', // QQ_二維
        1104 => 'QQ_H5', // QQ_手機支付
        1107 => 'JD_NATIVE', // 京東_二維
        1108 => 'JD_H5', // 京東_手機支付
        1109 => 'BAIDU_NATIVE', // 百度_二維
        1111 => 'UNIONPAY_NATIVE', // 銀聯快捷_二維
    ];

    /**
     * 回傳支付時要給支付平台驗證的資料
     *
     * @return array
     */
    public function getVerifyData()
    {
        $this->payVerify();

        // 從內部給定值到提交參數
        foreach ($this->requireMap as $paymentKey => $internalKey) {
            $this->requestData[$paymentKey] = $this->options[$internalKey];
        }

        // 帶入未支援的支付方式就噴例外
        if (!array_key_exists($this->options['paymentVendorId'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        $this->requestData['amount'] = sprintf('%.2f', $this->requestData['amount']);
        $this->requestData['payType'] = $this->bankMap[$this->requestData['payType']];

        if (in_array($this->options['paymentVendorId'], [1090, 1092, 1103, 1107, 1109, 1111])) {
            unset($this->requestData['returnUrl']);
            $this->requestData['sign'] = $this->encode();

            if (trim($this->options['verify_url']) == '') {
                throw new PaymentException('No verify_url specified', 180140);
            }

            $curlParam = [
                'method' => 'POST',
                'uri' => '/trade/jhpay',
                'ip' => $this->options['verify_ip'],
                'host' => $this->options['verify_url'],
                'param' => http_build_query($this->requestData),
                'header' => ['Port' => 9001],
            ];

            $result = $this->curlRequest($curlParam);
            $parseData = json_decode($result, true);

            if (!isset($parseData['code'], $parseData['data'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            if ($parseData['code'] != 1 && isset($parseData['msg'])) {
                throw new PaymentConnectionException($parseData['msg'], 180130, $this->getEntryId());
            }

            if ($parseData['code'] != 1 || !isset($parseData['data']['url'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            $this->setQrcode($parseData['data']['url']);

            return [];
        }

        $postUrl = $this->options['postUrl'] . '/gateway/init';

        if (in_array($this->options['paymentVendorId'], [279, 1093, 1097, 1104, 1108], false)) {
            $postUrl = $this->options['postUrl'] . '/trade/jhpay';
            unset($this->requestData['returnUrl']);
        }

        $this->requestData['sign'] = $this->encode();

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
        $this->payResultVerify();

        $encodeData = [];

        // 組織加密串
        foreach (array_keys($this->decodeParams) as $paymentKey) {
            $encodeData[$paymentKey] = $this->options[$paymentKey];
        }

        ksort($encodeData);

        $encodeStr = implode('', $encodeData);

        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        $sign = base64_decode($this->options['sign']);

        if (!openssl_verify($encodeStr, $sign, $this->getRsaPublicKey(), OPENSSL_ALGO_MD5)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['status'] !== '1') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['orderNo'] != $entry['id']) {
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
            if (isset($this->requestData[$index])) {
                $encodeData[$index] = $this->requestData[$index];
            }
        }

        ksort($encodeData);

        $encodeStr = implode('', $encodeData);

        $sign = '';

        if (!openssl_sign($encodeStr, $sign, $this->getRsaPrivateKey(), OPENSSL_ALGO_MD5)) {
            throw new PaymentException('Generate signature failure', 180144);
        }

        return base64_encode($sign);
    }
}

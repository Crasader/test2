<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 香蕉支付
 */
class BananaPay extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'cid' => '', // 商戶號
        'total_fee' => '', // 交易金額，單位：分
        'title' => '', // 商品標題，設定username方便業主比對
        'attach' => '', // 用戶自訂義參數
        'platform' => '', // 支付方式
        'orderno' => '', // 訂單編號
        'cburl' => '', // 同步通知網址(微信手機支付)
        'sign' => '', // 簽名
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'cid' => 'number',
        'total_fee' => 'amount',
        'title' => 'username',
        'attach' => 'username',
        'platform' => 'paymentVendorId',
        'orderno' => 'orderId',
        'cburl' => 'notify_url',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'attach',
        'cburl',
        'cid',
        'orderno',
        'title',
        'total_fee',
        'platform',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'attach' => 1,
        'errcode' => 1,
        'orderno' => 1,
        'total_fee' => 1,
    ];

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        1090 => 'CR', // 微信_二維
        1092 => 'CR_ALI', // 支付寶_二維
        1097 => 'CR_WAP', // 微信_手機支付
        1103 => 'TEN_CR', // QQ_二維
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
        if (!array_key_exists($this->requestData['platform'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 商家額外的參數設定
        $this->requestData['platform'] = $this->bankMap[$this->requestData['platform']];
        $this->requestData['total_fee'] = round($this->requestData['total_fee'] * 100);

        // 移除二微支付不需要的參數
        if (in_array($this->options['paymentVendorId'], [1090, 1092, 1103])) {
            unset($this->requestData['cburl']);
        }

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        // 設定支付平台需要的加密串
        $this->requestData['sign'] = $this->encode();

        $curlParam = [
            'method' => 'POST',
            'uri' => '/xjpay',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => json_encode($this->requestData),
            'header' => [],
        ];

        $result = $this->curlRequest($curlParam);
        $parseData = json_decode($result, true);

        if (!isset($parseData['err'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        if ($parseData['err'] !== 0) {
            throw new PaymentConnectionException('Pay error', 180130, $this->getEntryId());
        }

        // 微信WAP
        if ($this->options['paymentVendorId'] == '1097') {
            if (!isset($parseData['code_img_url'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            $parseUrl = parse_url($parseData['code_img_url']);

            $parseUrlValues = [
                'scheme',
                'host',
                'path',
            ];

            foreach ($parseUrlValues as $key) {
                if (!isset($parseUrl[$key])) {
                    throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
                }
            }

            $params = [];

            if (isset($parseUrl['query'])) {
                parse_str($parseUrl['query'], $params);
            }

            $postUrl = sprintf(
                '%s://%s%s',
                $parseUrl['scheme'],
                $parseUrl['host'],
                $parseUrl['path']
            );

            // Form使用GET才能正常跳轉
            $this->payMethod = 'GET';

            return [
                'post_url' => $postUrl,
                'params' => $params,
            ];
        }

        if (!isset($parseData['code_url'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        $this->setQrcode($parseData['code_url']);

        return [];
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

        $encodeStr = '';

        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options)) {
                $encodeStr .= $this->options[$paymentKey];
            }
        }

        $encodeStr .= $this->privateKey;

        // 若沒有返回簽名需丟例外
        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if (strcasecmp($this->options['sign'], md5($encodeStr)) != 0) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['errcode'] !== '0') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['orderno'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['total_fee'] != round($entry['amount'] * 100)) {
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
        $encodeStr = '';

        foreach ($this->encodeParams as $index) {
            if (isset($this->requestData[$index])) {
                $encodeStr .= $this->requestData[$index];
            }
        }

        $encodeStr .= $this->privateKey;

        return strtoupper(md5($encodeStr));
    }
}

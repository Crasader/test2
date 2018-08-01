<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 易路通
 */
class YiLuTung extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'appid' => '', // appid
        'method' => 'masget.pay.compay.router.font.pay', // 接口名稱
        'format' => 'json', // 響應格式，固定值
        'data' => '', // 業務參數
        'v' => '2.0', // API協議版本
        'timestamp' => '', // 時間戳 Y-m-d H:i:s
        'session' => '', // 會話狀態，非必填
        'sign' => '', // 簽名
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'appid' => 'number',
        'amount' => 'amount',
        'payordernumber' => 'orderId',
        'Body' => 'username',
        'backurl' => 'notify_url',
    ];

    /**
     * 支付時data的參數設定
     *
     * @var array
     */
    protected $encodeParams = [
        'amount' => '', // 支付金額
        'payordernumber' => '', // 訂單號
        'fronturl' => '', // 前台通知網址，非必填
        'backurl' => '', // 後台通知網址，非必填
        'Body' => '', // 交易信息，存 username 方便業主對帳
        'ExtraParams' => '', // 擴展信息，非必填
        'PayType' => '', // 交易方式，非必填
        'SubpayType' => '', // 附加交易方式，非必填
        'PayParams' => '', // 支付附加參數，非必填
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'ordernumber' => 1,
        'amount' => 1,
        'payorderid' => 1,
        'businesstime' => 1,
        'respcode' => 1,
        'extraparams' => 1,
        'respmsg' => 1,
    ];

    /**
     * 應答機制訊息
     *
     * @var string
     */
    protected $msg = '{"message":"成功","response":"00"}';

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        1102 => '', // 收银台
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

        $this->options['notify_url'] = sprintf(
            '%s?order_id=%s',
            $this->options['notify_url'],
            $this->options['orderId']
        );

        // 從內部給定值到參數
        foreach ($this->requireMap as $paymentKey => $internalKey) {
            if ($internalKey == 'number') {
                $this->requestData[$paymentKey] = $this->options[$internalKey];

                continue;
            }

            $this->encodeParams[$paymentKey] = $this->options[$internalKey];
        }

        // 帶入未支援的銀行就噴例外
        if (!array_key_exists($this->options['paymentVendorId'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 取得商家附加設定值
        $merchantExtraValues = $this->getMerchantExtraValue(['session']);
        $this->requestData['session'] = $merchantExtraValues['session'];

        //額外的參數設定
        $this->encodeParams['amount'] = round($this->encodeParams['amount'] * 100);

        // 設定支付平台需要的加密串
        $this->requestData['data'] = $this->aesEncode();
        $this->requestData['sign'] = $this->encode();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'method' => 'POST',
            'uri' => '/pay/Rest',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => urldecode(http_build_query($this->requestData)),
            'header' => [],
        ];

        $result = $this->curlRequest($curlParam);
        $parseData = json_decode($result, true);

        if (!isset($parseData['ret']) || !isset($parseData['message'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        if (trim($parseData['ret']) !== '0') {
            throw new PaymentConnectionException($parseData['message'], 180130, $this->getEntryId());
        }

        if (!isset($parseData['data'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        $parseUrl = parse_url(urldecode($parseData['data']));

        $parseUrlValues = [
            'scheme',
            'host',
            'path',
            'query',
        ];

        foreach ($parseUrlValues as $key) {
            if (!isset($parseUrl[$key])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }
        }

        $param = [];
        parse_str($parseUrl['query'], $param);

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
            'params' => $param,
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

        if (!isset($this->options['Data']) || !isset($this->options['Sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['Sign'] != md5($this->options['Data'] . $this->privateKey)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        $this->setOptions($this->aesDecode($this->options['Data']));

        $this->payResultVerify();

        // 訂單未成功
        if (trim($this->options['respcode']) != '2') {
            throw new PaymentConnectionException($this->options['respmsg'], 180130, $this->getEntryId());
        }

        if ($this->options['ordernumber'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['amount'] != round($entry['amount'] * 100)) {
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
        ksort($this->requestData);

        $encodeStr = '';
        foreach (array_keys($this->requestData) as $paymentKey) {
            if ($paymentKey != 'sign') {
                $encodeStr .= $this->requestData[$paymentKey];
            }
        }

        return md5($this->privateKey . $encodeStr . $this->privateKey);
    }

    /**
     * AES加密
     *
     * @return string
     */
    protected function aesEncode()
    {
        $res = openssl_encrypt(
            json_encode($this->encodeParams),
            'AES-128-CBC',
            $this->privateKey,
            OPENSSL_RAW_DATA,
            $this->privateKey
        );

        return str_replace(['+', '/'], ['-', '_'], base64_encode($res));
    }

    /**
     * AES解密
     *
     * @param string $data
     * @return string
     */
    protected function aesDecode($data)
    {
        $str = str_replace('-', '+', $data);
        $decodeStr = str_replace('_', '/', $str);

        $ret = openssl_decrypt(
            $decodeStr,
            'AES-128-CBC',
            $this->privateKey,
            OPENSSL_ZERO_PADDING,
            $this->privateKey
        );

        return json_decode(rtrim($ret, "\0"), true);
    }
}

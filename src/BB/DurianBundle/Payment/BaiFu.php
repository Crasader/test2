<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 佰富
 */
class BaiFu extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'merchantNo' => '', // 商戶號
        'netwayCode' => '', // 通道代碼
        'randomNum' => '', // 隨機數，單日內唯一
        'orderNum' => '', // 訂單編號
        'payAmount' => '', // 交易金額，單位：分
        'goodsName' => '', // 商品訊息，必填
        'callBackUrl' => '', // 異步通知URL
        'frontBackUrl' => '', // 同步通知URL
        'requestIP' => '', // 交易請求IP
        'sign' => '', // 簽名
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'merchantNo' => 'number',
        'netwayCode' => 'paymentVendorId',
        'orderNum' => 'orderId',
        'payAmount' => 'amount',
        'goodsName' => 'orderId',
        'callBackUrl' => 'notify_url',
        'frontBackUrl' => 'notify_url',
        'requestIP' => 'ip',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'merchantNo',
        'netwayCode',
        'randomNum',
        'orderNum',
        'payAmount',
        'goodsName',
        'callBackUrl',
        'frontBackUrl',
        'requestIP',
        'scanType',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'merchantNo' => 1,
        'netwayCode' => 1,
        'orderNum' => 1,
        'payAmount' => 1,
        'goodsName' => 1,
        'resultCode' => 1,
        'payDate' => 1,
    ];

    /**
     * 應答機制訊息
     *
     * @var string
     */
    protected $msg = '000000';

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        '278' => 'KJ', // 銀聯在線
        '1088' => 'KJ', // 銀聯在線_手機支付
        '1090' => 'WX', // 微信_二維
        '1092' => 'ZFB', // 支付寶_二維
        '1097' => 'WX_WAP', // 微信_手機支付
        '1098' => 'ZFB_WAP', // 支付寶_手機支付
        '1103' => 'QQ', // QQ_二維
        '1104' => 'QQ_WAP', // QQ_手機支付
        '1107' => 'JDQB', // 京東_二維
        '1108' => 'JDQB_WAP', // 京東_手機支付
        '1111' => 'YL', // 銀聯_二維
        '1115' => 'WX_VERSA_SCAN', // 微信支付_條碼
        '1116' => 'ZFB_VERSA_SCAN', // 支付寶_條碼
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
        if (!array_key_exists($this->requestData['netwayCode'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 商家額外的參數設定
        $this->requestData['netwayCode'] = $this->bankMap[$this->requestData['netwayCode']];
        $this->requestData['payAmount'] = strval(round($this->requestData['payAmount'] * 100));
        $this->requestData['randomNum'] = substr($this->requestData['orderNum'], -6);

        // 調整uri以及提交網址
        $uri = '/api/smPay.action';
        $postUrl = 'payment.http.defray.' . $this->options['verify_url'];

        // 條碼需調整參數設定
        if (in_array($this->options['paymentVendorId'], [1115, 1116])) {
            $this->requestData['scanType'] = 'Page';

            // 調整uri以及提交網址
            $uri = '/api/preScanPay.action';
            $postUrl = 'payment.http.scan.' . $this->options['verify_url'];
        }

        $this->requestData['sign'] = $this->encode();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        ksort($this->requestData);

        $curlParam = [
            'method' => 'POST',
            'uri' => $uri,
            'ip' => $this->options['verify_ip'],
            'host' => $postUrl,
            'param' => 'paramData=' . json_encode($this->requestData),
            'header' => ['Port' => '8188'],
        ];

        $result = $this->curlRequest($curlParam);
        $parseData = json_decode($result, true);

        if (!isset($parseData['resultCode']) || !isset($parseData['resultMsg'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        if ($parseData['resultCode'] !== '00') {
            throw new PaymentConnectionException($parseData['resultMsg'], 180130, $this->getEntryId());
        }

        if (!isset($parseData['CodeUrl'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        if (in_array($this->options['paymentVendorId'], [1090, 1092, 1103, 1107, 1111])) {
            $this->setQrcode($parseData['CodeUrl']);

            return [];
        }

        $urlData = $this->parseUrl($parseData['CodeUrl']);

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
            if (array_key_exists($paymentKey, $this->options) && trim($this->options[$paymentKey]) !== '') {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        ksort($encodeData);
        $encodeStr = json_encode($encodeData, true);
        $encodeStr .= $this->privateKey;

        // 若沒有返回簽名需丟例外
        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['sign'] !== strtoupper(md5($encodeStr))) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['resultCode'] !== '00') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['orderNum'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['payAmount'] != round($entry['amount'] * 100)) {
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
            if (array_key_exists($index, $this->requestData) && trim($this->requestData[$index]) !== '') {
                $encodeData[$index] = $this->requestData[$index];
            }
        }

        ksort($encodeData);
        $encodeStr = json_encode($encodeData);
        $encodeStr = str_replace('\\', '', $encodeStr);
        $encodeStr .= $this->privateKey;

        return strtoupper(md5($encodeStr));
    }
}

<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 迅捷通
 */
class ShiunJieTung extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'merchno' => '', // 商戶號
        'amount' => '', // 交易金額，單位：元
        'traceno' => '', // 商戶訂單號
        'payType' => '', // 支付方式
        'goodsName' => '', // 商品名稱，設定username方便業主比對
        'notifyUrl' => '', // 通知地址
        'settleType' => '1', // 結算類型，固定值
        'signature' => '', // 簽名
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'merchno' => 'number',
        'amount' => 'amount',
        'traceno' => 'orderId',
        'payType' => 'paymentVendorId',
        'notifyUrl' => 'notify_url',
        'goodsName' => 'username',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'merchno',
        'amount',
        'traceno',
        'payType',
        'notifyUrl',
        'settleType',
        'goodsName',
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
        'merchName' => 1,
        'customerno' => 0,
        'amount' => 1,
        'traceno' => 1,
        'payType' => 1,
        'orderno' => 1,
        'channelOrderno' => 1,
        'channelTraceno' => 1,
        'openId' => 0,
        'status' => 1,
    ];

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        '1090' => '2', // 微信_二維
        '1092' => '1', // 支付寶_二維
        '1097' => '2', // 微信_手機支付
        '1098' => '1', // 支付寶_手機支付
        '1103' => '4', // QQ_二維
        '1104' => '4', // QQ_手機支付
        '1107' => '5', // 京東_二維
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
        if (!array_key_exists($this->requestData['payType'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 商家額外的參數設定
        $this->requestData['payType'] = $this->bankMap[$this->requestData['payType']];
        $this->requestData['amount'] = sprintf('%.2f', $this->requestData['amount']);

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $uri = '/payapi/passivePay';

        // 手機支付需調整uri及參數
        if (in_array($this->options['paymentVendorId'], [1097, 1098, 1104])) {
            $uri = '/payapi/wapPay';

            unset($this->requestData['settleType']);
        }

        // 設定支付平台需要的加密串
        $this->requestData['signature'] = $this->encode();

        $curlParam = [
            'method' => 'POST',
            'uri' => $uri,
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => urldecode(http_build_query($this->requestData)),
            'header' => ['Port' => 8209],
            'charset' => 'GBK', // 需指定用GBK對數據進行編碼
        ];

        $result = $this->curlRequest($curlParam);
        $parseData = json_decode($result, true);

        if (!isset($parseData['respCode']) || !isset($parseData['message'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        if ($parseData['respCode'] != '00') {
            throw new PaymentConnectionException($parseData['message'], 180130, $this->getEntryId());
        }

        if (!isset($parseData['barCode'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        // 手機支付
        if (in_array($this->options['paymentVendorId'], [1097, 1098, 1104])) {
            return [
                'post_url' => $parseData['barCode'],
                'params' => [],
            ];
        }

        $this->setQrcode($parseData['barCode']);

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

        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options) && trim($this->options[$paymentKey]) != '') {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        ksort($encodeData);

        $encodeStr = urldecode(http_build_query($encodeData));
        $encodeStr .= '&' . $this->privateKey;

        // 轉字串編碼
        $detach = ['GB2312', 'UTF-8', 'GBK'];
        $charset = mb_detect_encoding($encodeStr, $detach);
        $str = iconv($charset, 'GBK', $encodeStr);

        // 若沒有返回簽名需丟例外
        if (!isset($this->options['signature'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if (strcasecmp($this->options['signature'], md5($str)) != 0) {
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
            // 非空參數才要參與簽名
            if (isset($this->requestData[$index]) && trim($this->requestData[$index]) != '') {
                $encodeData[$index] = $this->requestData[$index];
            }
        }

        ksort($encodeData);

        $encodeStr = urldecode(http_build_query($encodeData));
        $encodeStr .= '&' . $this->privateKey;

        return md5($encodeStr);
    }
}

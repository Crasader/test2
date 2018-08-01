<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 好支付3C
 */
class HaoPay3C extends PaymentBase
{
    /**
     * 支付提交參數
     *
     * @var array
     */
    protected $requestData = [
        'service' => '', // 服務類型
        'merchantCode' => '', // 商號
        'orderNum' => '', // 商戶訂單號
        'transMoney' => '', // 金額，單位：分
        'notifyUrl' => '', // 異步通知網址
        'merchantName' => '', // 收款商戶名稱(建議填商號)
        'merchantNum' => '', // 商戶門店編號(建議填商號)
        'sign' => '', // 簽名
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'service' => 'paymentVendorId',
        'merchantCode' => 'number',
        'orderNum' => 'orderId',
        'transMoney' => 'amount',
        'merchantName' => 'number',
        'merchantNum' => 'number',
        'notifyUrl' => 'notify_url',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'service',
        'merchantCode',
        'orderNum',
        'transMoney',
        'notifyUrl',
        'merchantName',
        'merchantNum',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'orderNum' => 1,
        'pl_orderNum' => 1,
        'pl_payState' => 1,
        'pl_payMessage' => 1,
        'pl_transMoney' => 1,
    ];

    /**
     * 應答機制訊息
     *
     * @var string
     */
    protected $msg = '{"success":"true"}';

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        278 => 'cj005', // 銀聯在線
        1088 => 'cj005', // 銀聯在線_手機支付
        1090 => 'cj102', // 微信_二維
        1092 => 'cj101', // 支付寶_二維
        1102 => 'cj007', // 網銀收銀台
        1103 => 'cj006', // QQ_二維
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

        // 從內部設值到支付參數
        foreach ($this->requireMap as $paymentKey => $internalKey) {
            $this->requestData[$paymentKey] = $this->options[$internalKey];
        }

        // 帶入未支援的銀行就噴例外
        if (!array_key_exists($this->requestData['service'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $this->requestData['service'] = $this->bankMap[$this->requestData['service']];
        $this->requestData['transMoney'] = round($this->requestData['transMoney'] * 100);

        // 設定加密簽名
        $this->requestData['sign'] = $this->encode();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'method' => 'POST',
            'uri' => '',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => urldecode(http_build_query($this->requestData)),
            'header' => [],
        ];

        $result = $this->curlRequest($curlParam);
        $parseData = json_decode($result, true);

        if (!isset($parseData['Code']) || !isset($parseData['Message'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        if ($parseData['Code'] !== '0000') {
            throw new PaymentConnectionException($parseData['Message'], 180130, $this->getEntryId());
        }

        if (!isset($parseData['pl_url'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        return [
            'post_url' => $parseData['pl_url'],
            'params' => [],
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

        $encodeData['key'] = $this->privateKey;
        $encodeStr = urldecode(http_build_query($encodeData));

        // 沒有返回Sign就要丟例外
        if (!isset($this->options['Sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['Sign'] != md5($encodeStr)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['pl_payState'] != '4') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['orderNum'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['pl_transMoney'] != round($entry['amount'] * 100)) {
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

        foreach ($this->encodeParams as $key) {
            $encodeData[$key] = $this->requestData[$key];
        }

        $encodeStr = urldecode(http_build_query($encodeData));
        $encodeStr .= $this->privateKey;

        return md5($encodeStr);
    }
}

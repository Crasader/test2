<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 億通
 */
class YiTong extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證參數
     *
     * @var array
     */
    protected $requestData = [
        'merchNo' => '', // 商戶號
        'orderNo' => '', // 訂單號
        'tradeDate' => '', // 訂單日期
        'amt' => '', // 訂單金額，單位：分
        'merchUrl' => '', // 異步返回網址
        'tradeSummary' => '', // 交易摘要
        'sign' => '', // 簽名
        'apiName' => '', // 支付方式
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'merchNo' => 'number',
        'orderNo' => 'orderId',
        'tradeDate' => 'orderCreateDate',
        'amt' => 'amount',
        'merchUrl' => 'notify_url',
        'tradeSummary' => 'username',
        'apiName' => 'paymentVendorId',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'merchNo',
        'orderNo',
        'tradeDate',
        'amt',
        'merchUrl',
        'tradeSummary',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0:可不返口的參數
     *     1:必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'API' => 1,
        'merchNo' => 1,
        'orderNo' => 1,
        'Amt' => 1,
        'status' => 1,
        'merchParam' => 1,
        'notify_time' => 1,
    ];

    /**
     * 應答機制訊息
     *
     * @var string
     */
    protected $msg = 'SUCCESS';

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        '1090' => 'WX_SCAN', // 微信_二維
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

        foreach ($this->requireMap as $paymentKey => $internalKey) {
            $this->requestData[$paymentKey] = $this->options[$internalKey];
        }

        if (!array_key_exists($this->requestData['apiName'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 商家額外的參數設定
        $this->requestData['apiName'] = $this->bankMap[$this->requestData['apiName']];
        $this->requestData['amt'] = round(sprintf('%.2f', $this->requestData['amt']) * 100);
        $createAt = new \Datetime($this->requestData['tradeDate']);
        $this->requestData['tradeDate'] = $createAt->format('Ymd');

        $this->requestData['sign'] = $this->encode();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'method' => 'POST',
            'uri' => '/public/index.php/index/index/pay',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => urldecode(http_build_query($this->requestData)),
            'header' => [],
        ];

        $result = $this->curlRequest($curlParam);
        $parseData = json_decode($result, true);

        if (!isset($parseData['code']) || !isset($parseData['msg'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        if ($parseData['code'] != '100') {
            throw new PaymentConnectionException($parseData['msg'], 180130, $this->getEntryId());
        }

        if (!isset($parseData['data'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        if (!isset($parseData['data']['payCode'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        $this->setQrcode($parseData['data']['payCode']);

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

        $encodeData = [];

        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options)) {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        $encodeStr = urldecode(http_build_query($encodeData));
        $encodeStr .= $this->privateKey;

        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['sign'] != md5($encodeStr)) {
            throw new PaymentConnectionException('Signature verification failed', 180134, $this->getEntryId());
        }

        if ($this->options['status'] != 1) {
            throw new PaymentConnectionException('Payment failure', 180135, $this->getEntryId());
        }

        if ($this->options['orderNo'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['Amt'] != round($entry['amount'] * 100)) {
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
            $encodeData[$index] = $this->requestData[$index];
        }

        $encodeStr = urldecode(http_build_query($encodeData));
        $encodeStr .= $this->privateKey;

        return md5($encodeStr);
    }
}

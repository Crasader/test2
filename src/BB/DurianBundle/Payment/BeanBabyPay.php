<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 豆芽支付
 */
class BeanBabyPay extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'parter' => '', // 商戶號
        'ptype' => 'web', // wap:h5支付、web:掃碼支付
        'ptoid' => '', // 商戶訂單號
        'gdn' => '', // 商品名稱，帶入username
        'attach' => '', // 附加信息
        'money' => '', // 單位:元，最多保留小數兩位
        'ip' => '', // 终端IP
        'cburl' => '', // 異步通知地址
        'hrefurl' => '', //同步通知地址
        'paltform' => '', // 支付方式
        'sign' => '', // 簽名
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'parter' => 'number',
        'ptoid' => 'orderId',
        'gdn' => 'username',
        'attach' => 'username',
        'money' => 'amount',
        'ip' => 'ip',
        'cburl' => 'notify_url',
        'hrefurl' => 'notify_url',
        'paltform' => 'paymentVendorId',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'parter',
        'ptype',
        'ptoid',
        'gdn',
        'attach',
        'money',
        'ip',
        'cburl',
        'hrefurl',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'error' => 1,
        'message' => 1,
        'parter' => 1,
        'ptoid' => 1,
        'oid' => 1,
        'attach' => 1,
        'money' => 1,
        'stime' => 1,
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
        '278' => 'yl', // 銀聯在線
        '1102' => 'yl', // 網銀收銀台
        '1104' => 'qq', // QQ_手機支付
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
        if (!array_key_exists($this->requestData['paltform'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $this->requestData['money'] = sprintf('%.2f', $this->requestData['money']);
        $this->requestData['paltform'] = $this->bankMap[$this->requestData['paltform']];

        if ($this->options['paymentVendorId'] == '1104') {
            $this->requestData['ptype'] = 'wap';
        }

        // 設定支付平台需要的加密串
        $this->requestData['sign'] = $this->encode();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'method' => 'POST',
            'uri' => '/bigpay/sysauth/prePay',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => urldecode(http_build_query($this->requestData)),
            'header' => ['Port' => 8180],
        ];

        $result = $this->curlRequest($curlParam);
        $parseData = json_decode($result, true);

        if (!isset($parseData['code']) || !isset($parseData['msg'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        if ($parseData['code'] !== '0000') {
            throw new PaymentConnectionException($parseData['msg'], 180130, $this->getEntryId());
        }

        if (!isset($parseData['data']['token_id'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        return [
            'post_url' => $parseData['data']['token_id'],
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
        // 驗證私鑰
        $this->verifyPrivateKey();

        $this->payResultVerify();

        $encodeStr = '';

        // 組織加密串
        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options)) {
                $encodeStr .= $this->options[$paymentKey];
            }
        }

        $encodeStr .= $this->privateKey;

        // 如果沒有返回簽名要丟例外
        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['sign'] !== md5($encodeStr)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['error'] !== '1') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['ptoid'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['money'] != $entry['amount']) {
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

        // 加密設定
        foreach ($this->encodeParams as $index) {
            if (isset($this->requestData[$index])) {
                $encodeStr .= $this->requestData[$index];
            }
        }

        $encodeStr .= $this->privateKey;

        return md5($encodeStr);
    }
}

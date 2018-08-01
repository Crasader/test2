<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 信譽付
 */
class XinYuFu extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'V' => 'V4.0', // 版本號，固定值
        'UserNo' => '', // 商戶號
        'ordNo' => '', // 訂單號
        'ordTime' => '', // 提交訂單時間
        'amount' => '', // 金額，以分為單位
        'pid' => '', // 支付編碼
        'notifyUrl' => '', // 異步通知網址
        'frontUrl' => '', // 同步通知網址
        'remark' => '', // 擴展字段，設定username方便業主比對
        'ip' => '', // 用戶IP
        'sign' => '', // 簽名
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'UserNo' => 'number',
        'ordNo' => 'orderId',
        'ordTime' => 'orderCreateDate',
        'amount' => 'amount',
        'pid' => 'paymentVendorId',
        'notifyUrl' => 'notify_url',
        'frontUrl' => 'notify_url',
        'remark' => 'username',
        'ip' => 'ip',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'V',
        'UserNo',
        'ordNo',
        'ordTime',
        'amount',
        'pid',
        'notifyUrl',
        'frontUrl',
        'remark',
        'ip',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0:可不返回的參數
     *     1:必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'V' => 1,
        'UserNo' => 1,
        'ordNo' => 1,
        'amount' => 1,
        'status' => 1,
        'reqTime' => 1,
        'reqNo' => 1,
        'remark' => 1,
    ];

    /**
     * 應答機制訊息
     *
     * @var string
     */
    protected $msg = 'OK';

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        '278' => 'cxkzf', // 銀聯在線
        '1092' => 'apzf', // 支付寶_二維
        '1103' => 'qqzf', // QQ_二維
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

        if (!array_key_exists($this->requestData['pid'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        $this->requestData['pid'] = $this->bankMap[$this->requestData['pid']];
        $this->requestData['amount'] = round(sprintf('%.2f', $this->requestData['amount']) * 100);
        $createAt = new \Datetime($this->requestData['ordTime']);
        $this->requestData['ordTime'] = $createAt->format('YmdHis');

        $this->requestData['sign'] = $this->encode();

        // 二維支付
        if (in_array($this->options['paymentVendorId'], [1092, 1103])) {
            if (trim($this->options['verify_url']) == '') {
                throw new PaymentException('No verify_url specified', 180140);
            }

            $curlParam = [
                'method' => 'POST',
                'uri' => '/API/PayRequest.aspx',
                'ip' => $this->options['verify_ip'],
                'host' => $this->options['verify_url'],
                'param' => urldecode(http_build_query($this->requestData)),
                'header' => [],
            ];

            $result = $this->curlRequest($curlParam);
            $parseData = json_decode($result, true);

            if (!isset($parseData['resCode'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            if ($parseData['resCode'] != '10000' && !isset($parseData['resMsg'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            if ($parseData['resCode'] != '10000') {
                throw new PaymentConnectionException($parseData['resMsg'], 180130, $this->getEntryId());
            }

            if (!isset($parseData['Payurl'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            $this->setQrcode($parseData['Payurl']);

            return [];
        }

        return $this->requestData;
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

        foreach (array_keys($this->decodeParams) as $index) {
            if (array_key_exists($index, $this->options) && trim($this->options[$index]) !== '') {
                $encodeData[$index] = $this->options[$index];
            }
        }

        $encodeData['privateKey'] = $this->privateKey;

        $encodeStr = implode('|', $encodeData);

        if (!isset($this->options['Sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['Sign'] != strtoupper(md5($encodeStr))) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['status'] != '1001') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['ordNo'] != $entry['id']) {
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
        $encodeData = [];

        foreach ($this->encodeParams as $index) {
            if (isset($this->requestData[$index]) && $this->requestData[$index] != '') {
                $encodeData[$index] = $this->requestData[$index];
            }
        }

        $encodeData['privateKey'] = $this->privateKey;

        $encodeStr = implode('|', $encodeData);

        return strtoupper(md5($encodeStr));
    }
}

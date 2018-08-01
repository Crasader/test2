<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 金銀支付
 */
class JinYinPay extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'versionId' => '1.0', // 版號，固定值
        'orderAmount' => '', // 支付金額，單位為分
        'orderDate' => '', // 訂單日期
        'currency' => 'RMB', // 貨幣類型，固定值
        'accountType' => '0', // 銀行卡類型，0:借記卡
        'transType' => '008', // 交易類型，固定值
        'asynNotifyUrl' => '', // 異步通知地址
        'synNotifyUrl' => '', // 同步返回地址
        'signType' => 'MD5', // 加密方式，固定值
        'merId' => '', // 商戶編號
        'prdOrdNo' => '', // 商戶訂單號
        'payMode' => '00020', // 支付方式，00020：網銀
        'receivableType' => 'D00', // 到帳類型
        'prdAmt' => '', // 商品價格，單位為分
        'prdName' => '', // 商品名稱
        'tranChannel' => '', // 銀行編碼
        'signData' => '', // MD5簽名
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'orderAmount' => 'amount',
        'orderDate' => 'orderCreateDate',
        'asynNotifyUrl' => 'notify_url',
        'synNotifyUrl' => 'notify_url',
        'merId' => 'number',
        'prdOrdNo' => 'orderId',
        'prdAmt' => 'amount',
        'prdName' => 'orderId',
        'tranChannel' => 'paymentVendorId',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'versionId',
        'orderAmount',
        'orderDate',
        'currency',
        'accountType',
        'transType',
        'asynNotifyUrl',
        'synNotifyUrl',
        'signType',
        'merId',
        'prdOrdNo',
        'payMode',
        'receivableType',
        'prdAmt',
        'prdName',
        'tranChannel',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'versionId' => 1,
        'transType' => 1,
        'asynNotifyUrl' => 1,
        'synNotifyUrl' => 1,
        'merId' => 1,
        'orderAmount' => 1,
        'prdOrdNo' => 1,
        'orderStatus' => 1,
        'payId' => 1,
        'payTime' => 1,
        'signType' => 1,
    ];

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        '1' => '102', // 中國工商銀行
        '2' => '301', // 交通銀行
        '3' => '103', // 中國農業銀行
        '4' => '105', // 中國建設銀行
        '5' => '308', // 招商銀行
        '6' => '305', // 中國民生銀行
        '8' => '310', // 上海浦東發展銀行
        '9' => '313', // 北京銀行
        '10' => '309', // 興業銀行
        '11' => '302', // 中信銀行
        '12' => '303', // 中國光大銀行
        '13' => '304', // 華夏銀行
        '14' => '306', // 廣東發展銀行
        '15' => '307', // 深圳平安銀行
        '16' => '403', // 中國郵政
        '17' => '104', // 中國銀行
        '19' => '325', // 上海銀行
        '217' => '318', // 渤海銀行
        '221' => '316', // 浙商银行
        '308' => '440', // 徽商銀行
        '311' => '315', // 恒丰银行
        '1090' => '00022', // 微信_二維
        '1092' => '00021', // 支付寶_二維
        '1098' => '00026', // 支付寶_手機支付
    ];

    /**
     * 應答機制訊息
     *
     * @var string
     */
    protected $msg = 'SUCCESS';

    /**
     * 回傳支付時要給支付平台驗證的資料
     *
     * @return array
     */
    public function getVerifyData()
    {
        $this->verifyPrivateKey();

        $this->payVerify();

        // 從內部給定值到參數
        foreach ($this->requireMap as $paymentKey => $internalKey) {
            $this->requestData[$paymentKey] = $this->options[$internalKey];
        }

        // 檢查銀行代碼是否支援
        if (!array_key_exists($this->requestData['tranChannel'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $this->requestData['tranChannel'] = $this->bankMap[$this->requestData['tranChannel']];
        $this->requestData['orderAmount'] = round($this->requestData['orderAmount'] * 100);
        $this->requestData['prdAmt'] = $this->requestData['orderAmount'];
        $date = new \DateTime($this->requestData['orderDate']);
        $this->requestData['orderDate'] = $date->format('YmdHis');

        // 二維支付
        if (in_array($this->options['paymentVendorId'], [1090, 1092])) {
            $this->requestData['payMode'] = $this->requestData['tranChannel'];

            unset($this->requestData['accountType']);
            unset($this->requestData['tranChannel']);

            $this->requestData['signData'] = $this->encode();

            if (trim($this->options['verify_url']) == '') {
                throw new PaymentException('No verify_url specified', 180140);
            }

            $curlParam = [
                'method' => 'POST',
                'uri' => '/payment/ScanPayApply.do',
                'ip' => $this->options['verify_ip'],
                'host' => $this->options['verify_url'],
                'param' => http_build_query($this->requestData),
                'header' => ['Port' => '8080'],
            ];

            $result = $this->curlRequest($curlParam);
            $parseData = json_decode($result, true);

            if (!isset($parseData['retCode']) || !isset($parseData['retMsg'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            if ($parseData['retCode'] !== '1') {
                throw new PaymentConnectionException($parseData['retMsg'], 180130, $this->getEntryId());
            }

            if (!isset($parseData['qrcode'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            $this->setQrcode($parseData['qrcode']);

            return [];
        }

        // 手機支付調整參數
        if ($this->options['paymentVendorId'] == 1098) {
            $this->requestData['payMode'] = $this->requestData['tranChannel'];
            $this->requestData['tranChannel'] = '103';
        }

        $this->requestData['signData'] = $this->encode();

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

        // 組織加密串
        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options) && $this->options[$paymentKey] !== '') {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        ksort($encodeData);
        $encodeData['key'] = $this->privateKey;

        $encodeStr = urldecode(http_build_query($encodeData));

        // 沒有signData就要丟例外
        if (!isset($this->options['signData'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['signData'] != strtoupper(md5($encodeStr))) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['orderStatus'] !== '01') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['prdOrdNo'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['orderAmount'] != round($entry['amount'] * 100)) {
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
            if (isset($this->requestData[$index]) && $this->requestData[$index] !== '') {
                $encodeData[$index] = $this->requestData[$index];
            }
        }

        ksort($encodeData);
        $encodeData['key'] = $this->privateKey;

        $encodeStr = urldecode(http_build_query($encodeData));

        return strtoupper(md5($encodeStr));
    }
}
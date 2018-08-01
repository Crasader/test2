<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * Paysec二代
 */
class PaySec2 extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'token' => '', // 令牌
    ];

    /**
     * 取得token時送給支付平台的參數
     *
     * @var array
     */
    private $tokenParams = [
        'header' => [
            'version' => '3.0', // 版本號，固定值
            'merchantCode' => '', // 商號
            'signature' => '', // 簽名
        ],
        'body' => [
            'channelCode' => 'BANK_TRANSFER', // 交易付款選項，網銀直連: BANK_TRANSFER
            'bankCode' => '', // 銀行代碼
            'notifyURL' => '', // 異步通知地址
            'returnURL' => '', // 同步通知地址
            'orderAmount' => '', // 單位:元，精確到小數兩位
            'orderTime' => '', // 交易生成時間，毫秒為單位的時間戳
            'cartId' => '', // 訂單號
            'currency' => 'CNY', // 幣別
        ],
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'merchantCode' => 'number',
        'outTradeNo' => 'orderId',
        'bankCode' => 'paymentVendorId',
        'notifyURL' => 'notify_url',
        'returnURL' => 'notify_url',
        'orderAmount' => 'amount',
        'orderTime' => 'orderCreateDate',
        'cartId' => 'orderId',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'cartId',
        'orderAmount',
        'currency',
        'merchantCode',
        'version',
    ];

    /**
     * 返回驗簽時需要加密的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'cartId' => 1,
        'orderAmount' => 1,
        'currency' => 1,
        'merchantCode' => 0,
        'version' => 1,
        'status' => 1,
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
        '1' => 'ICBC', // 工商銀行
        '2' => 'BCOM', // 交通銀行
        '3' => 'ABC', // 農業銀行
        '4' => 'CCB', // 建設銀行
        '5' => 'CMB', // 招商銀行
        '6' => 'CMBC', // 民生銀行總行
        '8' => 'SPDB', // 上海浦東發展銀行
        '10' => 'CIB', // 興業銀行
        '11' => 'CITIC', // 中信銀行
        '12' => 'CEB', // 光大銀行
        '14' => 'GDB', // 廣東發展銀行
        '15' => 'PAB', // 平安銀行
        '16' => 'PSBC', // 中國郵政
        '17' => 'BOC', // 中國銀行
        '278' => 'QUICKPAY', // 銀聯在線
        '1103' => 'QQPAY', // QQ_二維
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

        // 去除巢狀
        $params = array_merge($this->tokenParams['header'], $this->tokenParams['body']);

        // 從內部設值到支付參數
        foreach ($this->requireMap as $paymentKey => $internalKey) {
            $params[$paymentKey] = $this->options[$internalKey];
        }

        // 帶入未支援的支付方式就噴例外
        if (!array_key_exists($params['bankCode'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        // 額外的參數設定
        $params['orderTime'] = strval(strtotime($params['orderTime']) * 1000);
        $params['orderAmount'] = sprintf('%.2f', $params['orderAmount']);
        $params['bankCode'] = $this->bankMap[$params['bankCode']];

        // 調整二維支付參數
        if ($this->options['paymentVendorId'] == '1103') {
            $params['channelCode'] = $params['bankCode'];
            unset($params['bankCode']);
        }

        // 將參數分別放回提交參數陣列
        foreach (array_keys($params) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->tokenParams['header'])) {
                $this->tokenParams['header'][$paymentKey] = $params[$paymentKey];
            }

            if (array_key_exists($paymentKey, $this->tokenParams['body'])) {
                $this->tokenParams['body'][$paymentKey] = $params[$paymentKey];
            }
        }

        // 設定支付平台需要的加密串
        $this->tokenParams['header']['signature'] = $this->encode();

        $this->requestData['token'] = $this->getToken();

        return $this->requestData;
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

        // 因商號不會返回，須從訂單紀錄取
        $this->options['merchantCode'] = $entry['merchant_number'];

        // 產生加密串
        $hashData = '';
        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options)) {
                $hashData .= $this->options[$paymentKey] . ';';
            }
        }

        $hashData = substr($hashData, 0, strlen($hashData) - 1);
        $hashData = hash('sha256', utf8_encode($hashData));
        $signature = crypt($hashData, $this->privateKey);
        $signature = str_replace($this->privateKey, "", $signature);

        // 如果沒有簽名也要丟例外(其他參數都在前面驗證過了，所以不須要驗證)
        if (!isset($this->options['signature'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['signature'] !== $signature) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['status'] !== 'SUCCESS') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['cartId'] !== $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['orderAmount'] !== sprintf('%.2f', $entry['amount'])) {
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
        $params = array_merge($this->tokenParams['header'], $this->tokenParams['body']);

        $hashData = '';
        foreach ($this->encodeParams as $index) {
            if (array_key_exists($index, $params) && trim($params[$index]) !== '') {
                $hashData .= $params[$index] . ';';
            }
        }

        $hashData = substr($hashData, 0, strlen($hashData) - 1);
        $hashData = hash('sha256', utf8_encode($hashData));
        $signature = crypt($hashData, $this->privateKey);

        return str_replace($this->privateKey, "", $signature);
    }

    /**
     * 取得token
     *
     * @return string
     */
    private function getToken()
    {
        $curlParam = [
            'method' => 'POST',
            'uri' => '/Intrapay/paysec/v1/payIn/requestToken',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => json_encode($this->tokenParams),
            'header' => ['Content-Type' => 'application/json'],
        ];

        $result = $this->curlRequest($curlParam);
        $parseData = json_decode($result, true);

        // 檢查返回參數
        if (!isset($parseData['header']['status'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        if ($parseData['header']['status'] != 'SUCCESS') {
            if (!isset($parseData['header']['statusMessage']['statusMessage'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            throw new PaymentConnectionException(
                $parseData['header']['statusMessage']['statusMessage'],
                180130,
                $this->getEntryId()
            );
        }

        if (!isset($parseData['body']['token'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        return $parseData['body']['token'];
    }
}

<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 盛祥支付
 */
class ShengXiangPay extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'userid' => '', // 商號
        'orderid' => '', // 訂單號
        'money' => '', // 支付金額，單位:元，精確到小數後兩位
        'url' => '', // 異步通知網址
        'aurl' => '', // 同步通知網址，非必填
        'bankid' => '', // 銀行編號
        'sign' => '', // 簽名
        'ext' => '', // 商戶擴展信息，非必填
        'sign2' => '', // 簽名2(金額有加入簽名串)
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'userid' => 'number',
        'orderid' => 'orderId',
        'money' => 'amount',
        'url' => 'notify_url',
        'bankid' => 'paymentVendorId',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'userid',
        'orderid',
        'bankid',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'money' => 1,
        'returncode' => 1,
        'userid' => 1,
        'orderid' => 1,
    ];

    /**
     * 應答機制訊息
     *
     * @var string
     */
    protected $msg = 'ok';

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        '1' => '1002', // 中國工商銀行
        '2' => '1020', // 交通銀行
        '3' => '1005', // 中國農業銀行
        '4' => '1003', // 中國建設銀行
        '5' => '1001', // 招商銀行
        '6' => '1006', // 中國民生銀行
        '8' => '1004', // 上海浦東發展銀行
        '9' => '1032', // 北京銀行
        '10' => '1009', // 興業銀行
        '11' => '1021', // 中信銀行
        '12' => '1022', // 中國光大銀行
        '13' => '1025', // 華夏銀行
        '14' => '1027', // 廣東發展銀行
        '15' => '1010', // 平安銀行
        '16' => '1028', // 中國郵政
        '17' => '1052', // 中國銀行
        '19' => '1024', // 上海銀行
        '1090' => '2001', // 微信_二維
        '1092' => '2003', // 支付寶_二維
        '1098' => '2007', // 支付寶_手機支付
        '1103' => '2008', // QQ_二維
        '1104' => '2009', // QQ_手機支付
        '1111' => '2012', // 銀聯_二維
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
        if (!array_key_exists($this->requestData['bankid'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $this->requestData['bankid'] = $this->bankMap[$this->requestData['bankid']];
        $this->requestData['money'] = sprintf('%.2f', $this->requestData['money']);

        // 設定支付平台需要的加密串
        $this->requestData['sign'] = $this->encode();

        // 設定sign2加密參數
        $this->encodeParams = [
            'money',
            'userid',
            'orderid',
            'bankid',
        ];
        $this->requestData['sign2'] = $this->encode();

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

        // 組織加密串sign2
        $encodeData = [];

        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options)) {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }
        $encodeData['keyvalue'] = $this->privateKey;

        $encodeStr2 = urldecode(http_build_query($encodeData));

        // sign加密方式為不加入money參數
        unset($encodeData['money']);
        $encodeStr = urldecode(http_build_query($encodeData));

        // 沒有sign或sign2就要丟例外
        if (!isset($this->options['sign']) || !isset($this->options['sign2'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['sign'] != md5($encodeStr)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['sign2'] != md5($encodeStr2)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['returncode'] != '1') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['orderid'] != $entry['id']) {
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
        $encodeData = [];

        foreach ($this->encodeParams as $key) {
            $encodeData[$key] = $this->requestData[$key];
        }
        $encodeData['keyvalue'] = $this->privateKey;

        // 依key1=value1&key2=value2&...&keyN=valueN
        $encodeStr = urldecode(http_build_query($encodeData));

        return md5($encodeStr);
    }
}
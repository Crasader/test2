<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 益和信
 */
class YiHeXin extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'userid' => '', // 商號
        'orderid' => '', // 訂單編號
        'money' => '', // 金額，精確到小數後兩位
        'url' => '', // 支付成功返回url
        'aurl' => '', // 跳轉到取貨地址，非必填
        'bankid' => '', // 銀行編號
        'sign' => '', // 簽名數據
        'ext' => '', // 額外參數，非必填
        'sign2' => '', // 簽名數據2
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
        '1097' => '2005', // 微信_手機支付
        '1098' => '2007', // 支付寶_手機支付
        '1103' => '2008', // QQ_二維
        '1104' => '2009', // QQ_手機支付
        '1107' => '2010', // 京東_二維
        '1108' => '2011', // 京東_手機支付
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
        $this->requestData['money'] = sprintf('%.2f', $this->requestData['money']);
        $this->requestData['bankid'] = $this->bankMap[$this->requestData['bankid']];

        // 設定支付平台需要的加密串
        $this->requestData['sign'] = $this->encode();

        // 設定sign2加密方式
        $signParams = [
            'money',
            'userid',
            'orderid',
            'bankid',
        ];
        $this->encodeParams = $signParams;
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

        $encodeData = [];

        // 組織加密串sign2
        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options)) {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        $encodeData['keyvalue'] = $this->privateKey;
        $encodeStr2 = http_build_query($encodeData);

        // Sign加密方式為不加入money參數
        unset($encodeData['money']);
        $encodeStr = http_build_query($encodeData);

        // 沒有sign就要丟例外
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

        // 組織加密簽名
        foreach ($this->encodeParams as $index) {
            $encodeData[$index] = $this->requestData[$index];
        }

        $encodeData['keyvalue'] = $this->privateKey;
        $encodeStr = http_build_query($encodeData);

        return md5($encodeStr);
    }
}


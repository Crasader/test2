<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 盈利支付
 */
class YingLiPay extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'pay_memberid' => '', // 商號
        'pay_orderid' => '', // 訂單號
        'pay_amount' => '', // 金額，單位元，精確到分
        'pay_applydate' => '', // 訂單提交時間，格式Y-m-d H:i:s
        'pay_bankcode' => '0', // 銀行編號，收銀台模式:0
        'pay_notifyurl' => '', // 回調地址
        'pay_callbackurl' => '', // 返回地址
        'tongdao' => '0', // 通道編碼，收銀台模式:0
        'cashier' => '', // 收銀台代碼
        'pay_md5sign' => '', // 加密簽名
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'pay_memberid' => 'number',
        'pay_orderid' => 'orderId',
        'pay_amount' => 'amount',
        'pay_applydate' => 'orderCreateDate',
        'cashier' => 'paymentVendorId',
        'pay_notifyurl' => 'notify_url',
        'pay_callbackurl' => 'notify_url',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'pay_memberid',
        'pay_orderid',
        'pay_amount',
        'pay_applydate',
        'pay_bankcode',
        'pay_notifyurl',
        'pay_callbackurl',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'memberid' => 1,
        'orderid' => 1,
        'amount' => 1,
        'datetime' => 1,
        'returncode' => 1,
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
        '6' => 'CMBC', // 民生銀行
        '9' => 'BJBANK', // 北京銀行
        '12' => 'CEB', // 光大銀行
        '14' => 'GDB', // 廣發銀行
        '16' => 'PSBC', // 郵儲銀行
        '17' => 'BOC', // 中國銀行
        '19' => 'BOS', // 上海銀行
        '278' => '14', // 銀聯在線(快捷)
        '1090' => '2', // 微信_二維
        '1092' => '3', // 支付寶_二維
        '1097' => '8', // 微信_手機支付
        '1098' => '9', // 支付寶_手機支付
        '1102' => '5', // 網銀收銀台
        '1103' => '4', // QQ_二維
        '1104' => '10', // QQ_手機支付
        '1107' => '7', // 京東_二維
        '1111' => '11', // 銀聯_二維
        '1115' => '15', // 微信條碼
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
        if (!array_key_exists($this->requestData['cashier'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $date = new \DateTime($this->requestData['pay_applydate']);
        $this->requestData['pay_applydate'] = $date->format('Y-m-d H:i:s');
        $this->requestData['pay_amount'] = sprintf('%.2f', $this->requestData['pay_amount']);
        $this->requestData['cashier'] = $this->bankMap[$this->requestData['cashier']];

        // 網銀調整額外設定
        $notBank = [278, 1090, 1092, 1097, 1098, 1102, 1103, 1104, 1107, 1111, 1115];
        if (!in_array($this->options['paymentVendorId'], $notBank)) {
            $this->requestData['pay_bankcode'] = $this->requestData['cashier'];
            $this->requestData['tongdao'] = 'ZL';
            $this->requestData['cashier'] = 5;
        }

        // 設定支付平台需要的加密串
        $this->requestData['pay_md5sign'] = $this->encode();

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

        // 組織加密串
        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options)) {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        ksort($encodeData);

        $encodeStr = '';

        foreach ($encodeData as $key => $val) {
            $encodeStr .= $key . '=>' . $val . '&';
        }

        $encodeStr .= 'key=' . $this->privateKey;

        // 如果沒有返回簽名要丟例外
        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['sign'] != strtoupper(md5($encodeStr))) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['returncode'] !== '00') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['orderid'] != $entry['id']) {
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

        // 加密設定
        foreach ($this->encodeParams as $index) {
            $encodeData[$index] = $this->requestData[$index];
        }

        ksort($encodeData);

        $encodeStr = '';

        foreach ($encodeData as $key => $val) {
            $encodeStr .= $key . '=>' . $val . '&';
        }

        $encodeStr .= 'key=' . $this->privateKey;

        return strtoupper(md5($encodeStr));
    }
}

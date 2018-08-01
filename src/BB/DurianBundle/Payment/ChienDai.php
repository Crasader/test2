<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 錢袋支付
 */
class ChienDai extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'pay_memberid' => '', // 商號
        'pay_orderid' => '', // 訂單號，需串上商號
        'pay_applydate' => '', // 訂單提交時間，格式YYYY-MM-DD HH:MM:SS
        'pay_bankcode' => '', // 銀行編號
        'pay_notifyurl' => '', // 回調地址
        'pay_callbackurl' => '', // 頁面跳轉通知地址
        'pay_amount' => '', // 金額，單位元，精確到分
        'pay_attach' => '', // 附加字段，可空
        'pay_productname' => '', // 商品名稱，這邊帶入username
        'pay_productnum' => '', // 商品數量，可空
        'pay_productdesc' => '', // 商品描述，可空
        'pay_producturl' => '', // 商品連接地址，可空
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
        'pay_bankcode' => 'paymentVendorId',
        'pay_notifyurl' => 'notify_url',
        'pay_callbackurl' => 'notify_url',
        'pay_productname' => 'username',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'pay_memberid',
        'pay_orderid',
        'pay_applydate',
        'pay_bankcode',
        'pay_notifyurl',
        'pay_callbackurl',
        'pay_amount',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'amount' => 1,
        'datetime' => 1,
        'memberid' => 1,
        'orderid' => 1,
        'returncode' => 1,
        'transaction_id' => 1,
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
        '1090' => '902', // 微信支付_二維
        '1092' => '903', // 支付寶_二維
        '1102' => '907', // 網銀收銀台
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
        if (!array_key_exists($this->requestData['pay_bankcode'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $date = new \DateTime($this->requestData['pay_applydate']);
        $this->requestData['pay_applydate'] = $date->format('Y-m-d H:i:s');
        $this->requestData['pay_amount'] = sprintf('%.2f', $this->requestData['pay_amount']);
        $this->requestData['pay_bankcode'] = $this->bankMap[$this->requestData['pay_bankcode']];

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

        $encodeData['key'] = $this->privateKey;

        // 依key1=value1&key2=value2&...&keyN=valueN之後做md5
        $encodeStr = urldecode(http_build_query($encodeData));

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
        $encodeData['key'] = $this->privateKey;

        // 依key1=value1&key2=value2&...&keyN=valueN之後做md5
        $encodeStr = urldecode(http_build_query($encodeData));

        return strtoupper(md5($encodeStr));
    }
}

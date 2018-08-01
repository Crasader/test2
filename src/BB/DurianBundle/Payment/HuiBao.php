<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 匯寶支付
 */
class HuiBao extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'merId' => '', // 商戶號
        'terId' => '', // 終端號
        'businessOrdid' => '', // 商戶訂單號
        'orderName' => '', // 訂單名稱
        'tradeMoney' => '', // 訂單金額，單位為分
        'selfParam' => '', // 自定義參數，可空
        'payType' => '', // 支付方式
        'appSence' => '1002', // 應用場景
        'syncURL' => '', // 同步通知地址，可空
        'asynURL' => '', // 異步通知地址
        'sign' => '', // 簽名
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'merId' => 'number',
        'businessOrdid' => 'orderId',
        'orderName' => 'username',
        'tradeMoney' => 'amount',
        'payType' => 'paymentVendorId',
        'asynURL' => 'notify_url',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'merId',
        'terId',
        'businessOrdid',
        'orderName',
        'tradeMoney',
        'selfParam',
        'payType',
        'appSence',
        'syncURL',
        'asynURL',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'merId' => 1,
        'orderId' => 1,
        'payOrderId' => 1,
        'order_state' => 1,
        'money' => 1,
        'payReturnTime' => 1,
        'selfParam' => 0,
        'payType' => 1,
        'notifyType' => 1,
        'payCurrName' => 1,
        'balanceCurrName' => 1,
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
        '1090' => '1005', // 微信_二維
        '1092' => '1006', // 支付寶_二維
        '1097' => '1011', // 微信_WAP
        '1098' => '1010', // 支付寶_WAP
        '1100' => '1003', // 網銀收銀台
        '1103' => '1013', // QQ_二維
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

        // 從內部給定值到參數
        foreach ($this->requireMap as $paymentKey => $internalKey) {
            $this->requestData[$paymentKey] = $this->options[$internalKey];
        }

        // 帶入未支援的銀行就噴例外
        if (!array_key_exists($this->options['paymentVendorId'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 取得商家附加設定值
        $merchantExtraValues = $this->getMerchantExtraValue(['terId']);

        // 額外的參數設定
        $this->requestData['tradeMoney'] = round($this->requestData['tradeMoney'] * 100);
        $this->requestData['terId'] = $merchantExtraValues['terId'];
        $this->requestData['payType'] = $this->bankMap[$this->requestData['payType']];

        // 設定支付平台需要的加密串
        $this->requestData['sign'] = $this->encode();

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

        // 加密設定
        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options) && $this->options[$paymentKey] !== '') {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        ksort($encodeData);

        // 額外的加密設定
        $encodeData['key'] = $this->privateKey;
        $encodeStr = urldecode(http_build_query($encodeData));

        // 檢查簽名
        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['sign'] !== md5($encodeStr)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        // 支付狀態不為成功
        if ($this->options['order_state'] != '1003') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        // 檢查單號
        if ($this->options['orderId'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        // 檢查金額
        if ($this->options['money'] != round($entry['amount'] * 100)) {
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
            if (trim($this->requestData[$index]) !== '') {
                $encodeData[$index] = $this->requestData[$index];
            }
        }

        ksort($encodeData);

        $encodeData['key'] = $this->privateKey;
        $encodeStr = urldecode(http_build_query($encodeData));

        return md5($encodeStr);
    }
}

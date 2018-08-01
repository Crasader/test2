<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 新匯合支付
 */
class XinHuiHe extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'payKey' => '', // 商戶支付key，等同商號
        'orderPrice' => '', // 訂單交易，單位：元，保留小數點兩位
        'outTradeNo' => '', // 商戶訂單號
        'productType' => '', // 產品類型
        'orderTime' => '', // 下單時間，格式YmdHis
        'productName' => '', // 支付產品名稱，帶入orderid
        'orderIp' => '', // 下單IP
        'returnUrl' => '', // 頁面通知地址
        'notifyUrl' => '', // 異步通知地址
        'sign' => '', // 簽名
        'remark' => '', // 備註，可空
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'payKey' => 'number',
        'orderPrice' => 'amount',
        'outTradeNo' => 'orderId',
        'productType' => 'paymentVendorId',
        'orderTime' => 'orderCreateDate',
        'productName' => 'orderId',
        'orderIp' => 'ip',
        'returnUrl' => 'notify_url',
        'notifyUrl' => 'notify_url',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'payKey',
        'orderPrice',
        'outTradeNo',
        'productType',
        'orderTime',
        'productName',
        'orderIp',
        'returnUrl',
        'notifyUrl',
        'remark',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'payKey' => 1,
        'orderPrice' => 1,
        'outTradeNo' => 1,
        'productType' => 1,
        'orderTime' => 1,
        'productName' => 1,
        'tradeStatus' => 1,
        'successTime' => 1,
        'remark' => 0,
        'trxNo' => 1,
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
        '1092' => '20000304', // 支付寶_二維
        '1098' => '20000305', // 支付寶_手機支付
        '1100' => '50000103', // 手機收銀台
        '1102' => '50000103', // 網銀收銀台
        '1111' => '60000104', // 銀聯_二維
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

        // 從內部給定值到參數
        foreach ($this->requireMap as $paymentKey => $internalKey) {
            $this->requestData[$paymentKey] = $this->options[$internalKey];
        }

        // 帶入未支援的銀行就噴例外
        if (!array_key_exists($this->requestData['productType'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 商家額外的參數設定
        $this->requestData['productType'] = $this->bankMap[$this->requestData['productType']];
        $this->requestData['orderPrice'] = sprintf('%.2f', $this->requestData['orderPrice']);
        $createAt = new \Datetime($this->requestData['orderTime']);
        $this->requestData['orderTime'] = $createAt->format('YmdHis');

        // 設定支付平台所需加密簽名
        $this->requestData['sign'] = $this->encode();

        // 收銀台uri
        $uri = '/netPayApi/pay';

        // 調整二維、手機支付uri
        if (in_array($this->options['paymentVendorId'], [1092, 1098, 1111])) {
            $uri = '/gateWayApi/pay';
        }

        // 調整提交網址
        $postUrl = $this->options['postUrl'] . $uri;

        return [
           'post_url' => $postUrl,
           'params' => $this->requestData,
       ];
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

        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options) && trim($this->options[$paymentKey]) !== '') {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        ksort($encodeData);
        $encodeData['paySecret'] = $this->privateKey;
        $encodeStr = urldecode(http_build_query($encodeData));

        // 若沒有返回簽名需丟例外
        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if (strcasecmp($this->options['sign'], md5($encodeStr)) !== 0) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['tradeStatus'] == 'WAITING_PAYMENT') {
            throw new PaymentConnectionException('Unpaid order', 180062, $this->getEntryId());
        }

        if ($this->options['tradeStatus'] != 'SUCCESS') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['outTradeNo'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['orderPrice'] != $entry['amount']) {
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
            if (isset($this->requestData[$index]) && trim($this->requestData[$index]) !== '') {
                $encodeData[$index] = $this->requestData[$index];
            }
        }

        ksort($encodeData);
        $encodeData['paySecret'] = $this->privateKey;
        $encodeStr = urldecode(http_build_query($encodeData));

        return strtoupper(md5($encodeStr));
    }
}

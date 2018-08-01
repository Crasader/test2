<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 新幹線支付
 */
class XgxPay extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'merchant_id' => '', // 商戶號
        'payment_way' => '3', // 支付方式，3:網銀
        'sign' => '', // 簽名
        'return_url' => '', // 同步通知網址，不可為空
        'client_ip' => '', // 客戶端IP
        'goods_name' => '', // 商品名稱，不可為空
        'source_order_id' => '', // 商戶訂單號
        'notify_url' => '', // 異步通知網址
        'order_amount' => '', // 訂單金額，單位:元，保留到小數第二位
        'bank_code' => '', // 銀行代碼(非網銀為空)
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'merchant_id' => 'number',
        'return_url' => 'notify_url',
        'client_ip' => 'ip',
        'goods_name' => 'orderId',
        'source_order_id' => 'orderId',
        'notify_url' => 'notify_url',
        'order_amount' => 'amount',
        'bank_code' => 'paymentVendorId',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'merchant_id',
        'payment_way',
        'return_url',
        'client_ip',
        'goods_name',
        'source_order_id',
        'notify_url',
        'order_amount',
        'bank_code',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'merchant_id' => 1,
        'source_order_id' => 1,
        'order_amount' => 1,
        'goods_name' => 1,
        'payTime' => 1,
        'order_code' => 0,
        'status' => 1,
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
        '1' => 'ICBC', // 中國工商銀行
        '2' => 'BCOM', // 交通銀行
        '3' => 'ABC', // 中國農業銀行
        '4' => 'CCB', // 中國建設銀行
        '5' => 'CMB', // 招商銀行
        '6' => 'CMBC', // 中國民生銀行
        '8' => 'SPDB', // 上海浦東發展銀行
        '9' => 'BOB', // 北京銀行
        '10' => 'CIB', // 興業銀行
        '11' => 'ECITIC', // 中信銀行
        '12' => 'CEBB', // 中國光大銀行
        '13' => 'HXB', // 華夏銀行
        '14' => 'GDB', // 廣東發展銀行
        '15' => 'SPABANK', // 平安銀行
        '16' => 'PSBC', // 中國郵政
        '17' => 'BOC', // 中國銀行
        '19' => 'SHB', // 上海銀行
        '217' => 'CBHB', // 渤海銀行
        '222' => 'NBB', // 寧波銀行
        '223' => 'BEA', // 東亞銀行
        '226' => 'BON', // 南京銀行
        '228' => 'RCB', // 上海農村商業銀行
        '278' => '10', // 銀聯在線
        '308' => 'HSB', // 徽商銀行
        '312' => 'BOCD', // 成都銀行
        '321' => 'TCCB', // 天津銀行
        '1088' => '21', // 銀聯_手機支付
        '1092' => '43', // 支付寶_二維
        '1097' => '24', // 微信_手機支付
        '1098' => '25', // 支付寶_手機支付
        '1103' => '49', // QQ_二維
        '1104' => '22', // QQ_手機支付
        '1107' => '47', // 京東錢包_二維
        '1111' => '45', // 銀聯_二維
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
        if (!array_key_exists($this->requestData['bank_code'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $this->requestData['bank_code'] = $this->bankMap[$this->requestData['bank_code']];
        $this->requestData['order_amount'] = sprintf('%.2f', $this->requestData['order_amount']);

        // 銀聯、二維、手機支付需調整參數
        if (in_array($this->options['paymentVendorId'], [278, 1088, 1092, 1097, 1098, 1103, 1104, 1107, 1111])) {
            $this->requestData['payment_way'] = $this->requestData['bank_code'];
            $this->requestData['bank_code'] = '';
        }

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
        // 驗證私鑰
        $this->verifyPrivateKey();
        $this->payResultVerify();

        $encodeData = [];

        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options)) {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }
        $encodeData['token'] = $this->privateKey;

        ksort($encodeData);

        $encodeStr = urldecode(http_build_query($encodeData));

        // 若沒有返回簽名需丟例外
        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['sign'] != md5($encodeStr)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['status'] != '1') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['source_order_id'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['order_amount'] != $entry['amount']) {
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
            $encodeData[$index] = $this->requestData[$index];
        }
        $encodeData['token'] = $this->privateKey;

        ksort($encodeData);

        $encodeStr = urldecode(http_build_query($encodeData));

        return md5($encodeStr);
    }
}

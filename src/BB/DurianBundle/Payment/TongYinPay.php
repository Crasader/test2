<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 同銀支付
 */
class TongYinPay extends PaymentBase
{
    /**
     * 支付提交參數
     *
     * @var array
     */
    protected $requestData = [
        'requestId' => '', // 訂單號
        'orgId' => '', // 機構號
        'timestamp' => '', // 請求時間
        'productId' => '0500', // 產品ID，網銀:0500
        'businessData' => '', // 業務交互數據
        'signData' => '', // 簽名
        'dataSignType' => '0', // 業務數據加密方式，0:不加密，1:DES加密
    ];

    /**
     * 支付提交參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'requestId' => 'orderId',
        'timestamp' => 'orderCreateDate',
    ];

    /**
     * 業務交互數據參數
     *
     * @var array
     */
    private $businessData = [
        'merno' => '', // 商戶號
        'bus_no' => '0499', // 業務編號，網銀:固定0499
        'amount' => '', // 交易金額，單位分
        'goods_info' => '', // 商品訊息
        'order_id' => '', // 訂單號
        'cardname' => '', // 銀行名稱
        'bank_code' => '', // 銀行編碼
        'notify_url' => '', // 異步通知地址
        'card_type' => '1', // 卡類型，1:儲蓄卡
        'channelid' => '1', // 帳戶類型，1:對私
    ];

    /**
     * 業務交互數據參數內部參數的對應
     *
     * @var array
     */
    private $businessDataMap = [
        'merno' => 'number',
        'amount' => 'amount',
        'goods_info' => 'orderId',
        'order_id' => 'orderId',
        'cardname' => 'paymentVendorId',
        'bank_code' => 'paymentVendorId',
        'notify_url' => 'notify_url',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'requestId',
        'orgId',
        'timestamp',
        'productId',
        'businessData',
        'dataSignType',
    ];

    /**
     * 返回驗證時需要加密的參數
     *     0: 有返回就要加密的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'orgid' => 1,
        'merno' => 1,
        'amount' => 1,
        'goods_info' => 1,
        'trade_date' => 1,
        'trade_status' => 1,
        'order_id' => 1,
        'plat_order_id' => 1,
        'timestamp' => 1,
    ];

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        '1' => '102', // 中國工商銀行
        '3' => '103', // 中國農業銀行
        '4' => '105', // 中國建設銀行
        '5' => '308', // 招商銀行
        '8' => '310', // 上海浦東發展銀行
        '10' => '309', // 興業銀行
        '11' => '302', // 中信銀行
        '12' => '303', // 中國光大銀行
        '13' => '304', // 華夏銀行
        '14' => '306', // 廣東發展銀行
        '16' => '403', // 中國郵政儲蓄銀行
        '17' => '104', // 中國銀行
        '1092' => '0201', // 支付寶_二維
        '1098' => '0203', // 支付寶_手機支付
    ];

    /**
     * 支付平台支援的銀行對應名稱
     *
     * @var array
     */
    private $bankNameMap = [
        '1' => '工商银行',
        '3' => '农业银行',
        '4' => '建设银行',
        '5' => '招商银行',
        '8' => '浦发银行',
        '10' => '兴业银行',
        '11' => '中信银行',
        '12' => '光大银行',
        '13' => '华夏银行',
        '14' => '广发银行',
        '16' => '邮储银行',
        '17' => '中国银行',
        '1092' => '',
        '1098' => '',
    ];

    /**
     * 應答機制訊息
     *
     * @var string
     */
    protected $msg = '{"responseCode":"0000"}';

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

        // 從內部設值到支付參數
        foreach ($this->requireMap as $paymentKey => $internalKey) {
            $this->requestData[$paymentKey] = $this->options[$internalKey];
        }

        // 驗證業務交互數據參數
        foreach (array_values($this->businessDataMap) as $internalKey) {
            if (!isset($this->options[$internalKey]) || trim($this->options[$internalKey]) === '') {
                throw new PaymentException('No pay parameter specified', 180145);
            }
        }

        // 從內部設值到業務交互數據參數
        foreach ($this->businessDataMap as $paymentKey => $internalKey) {
            $this->businessData[$paymentKey] = $this->options[$internalKey];
        }

        // 帶入未支援的支付方式就噴例外
        if (!array_key_exists($this->options['paymentVendorId'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $createAt = new \Datetime($this->requestData['timestamp']);
        $this->requestData['timestamp'] = $createAt->format('YmdHis');
        $extra = $this->getMerchantExtraValue(['orgId']);
        $this->requestData['orgId'] = $extra['orgId'];

        // 調整業務交互數據參數
        $this->businessData['cardname'] = $this->bankNameMap[$this->businessData['cardname']];
        $this->businessData['bank_code'] = $this->bankMap[$this->businessData['bank_code']];
        $this->businessData['amount'] = round($this->businessData['amount'] * 100);

        // 非網銀調整業務交互數據參數
        if (in_array($this->options['paymentVendorId'], [1092, 1098])) {
            $this->requestData['productId'] = '0100';
            $this->businessData['bus_no'] = $this->businessData['bank_code'];
            $this->businessData['return_url'] = $this->options['notify_url'];
            unset($this->businessData['cardname']);
            unset($this->businessData['bank_code']);
            unset($this->businessData['card_type']);
            unset($this->businessData['channelid']);
        }

        $this->requestData['businessData'] = json_encode($this->businessData);

        // 設定加密簽名
        $this->requestData['signData'] = $this->encode();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'method' => 'POST',
            'uri' => '/open-gateway/trade/invoke',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => http_build_query($this->requestData),
            'header' => ['Port' => 18888],
        ];

        $result = $this->curlRequest($curlParam);
        $parseData = json_decode($result, true);

        if (!isset($parseData['respCode']) || !isset($parseData['respMsg'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        if ($parseData['respCode'] !== '00') {
            throw new PaymentConnectionException($parseData['respMsg'], 180130, $this->getEntryId());
        }

        if (!isset($parseData['key']) || !isset($parseData['msg'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        if ($parseData['key'] !== '05') {
            throw new PaymentConnectionException($parseData['msg'], 180130, $this->getEntryId());
        }

        if (!isset($parseData['result'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        $url = json_decode($parseData['result'], true);

        if (!isset($url['url']) || $url['url'] == '') {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        // 二維
        if ($this->options['paymentVendorId'] == 1092) {
            $this->setQrcode($url['url']);

            return [];
        }

        $urlData = $this->parseUrl($url['url']);

        // Form使用GET才能正常跳轉
        $this->payMethod = 'GET';

        return [
            'post_url' => $urlData['url'],
            'params' => $urlData['params'],
        ];
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

        // 驗證返回參數
        $this->payResultVerify();

        // 組合參數驗證加密簽名
        $encodeData = [];
        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options)) {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        ksort($encodeData);

        $encodeStr = urldecode(http_build_query($encodeData));
        $encodeStr .= $this->privateKey;

        // 沒有返回sign_data就要丟例外(其他參數都在前面驗證過了，所以不須要驗證)
        if (!isset($this->options['sign_data'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['sign_data'] != md5($encodeStr)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['trade_status'] !== '0') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['order_id'] != $entry['id']) {
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

        foreach ($this->encodeParams as $paymentKey) {
           if (array_key_exists($paymentKey, $this->requestData)) {
               $encodeData[$paymentKey] = $this->requestData[$paymentKey];
           }
       }

        ksort($encodeData);

        $encodeStr = urldecode(http_build_query($encodeData));
        $encodeStr .= $this->privateKey;

        return md5($encodeStr);
    }
}

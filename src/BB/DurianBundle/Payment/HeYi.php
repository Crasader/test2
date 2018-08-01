<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 合益支付
 */
class HeYi extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'amount' => '', // 金額，單位元，精確到分
        'mId' => '', // 商號
        'orderNumber' => '', // 訂單號
        'payType' => '', // 發起付款的場景
        'returnUrl' => '', // 支付成功跳轉地址
        'notifyUrl' => '', // 支付成功回調地址
        'extend' => '', // 擴展參數，非必填
        'sign' => '', // 加密簽名
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'amount' => 'amount',
        'mId' => 'number',
        'orderNumber' => 'orderId',
        'payType' => 'paymentVendorId',
        'returnUrl' => 'notify_url',
        'notifyUrl' => 'notify_url',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'amount',
        'mId',
        'payType',
        'returnUrl',
        'notifyUrl',
        'orderNumber',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'mId' => 1,
        'orderNumber' => 1,
        'sysTradeNumber' => 1,
        'amount' => 1,
        'dealTime' => 1,
        'dealCode' => 1,
    ];

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        1 => 'yinlianPc#10001', // 中國工商銀行
        2 => 'yinlianPc#10008', // 交通銀行
        3 => 'yinlianPc#10002', // 中國農業銀行
        4 => 'yinlianPc#10005', // 建設銀行
        5 => 'yinlianPc#10003', // 招商銀行
        6 => 'yinlianPc#10006', // 中國民生銀行
        8 => 'yinlianPc#10015', // 上海浦東發展銀行
        9 => 'yinlianPc#10013', // 北京銀行
        10 => 'yinlianPc#10009', // 興業銀行
        11 => 'yinlianPc#10007', // 中信銀行
        12 => 'yinlianPc#10010', // 中國光大銀行
        13 => 'yinlianPc#10025', // 華夏銀行
        14 => 'yinlianPc#10016', // 廣東發展銀行
        15 => 'yinlianPc#10014', // 平安銀行
        16 => 'yinlianPc#10012', // 中國郵政儲蓄銀行
        17 => 'yinlianPc#10004', // 中國銀行
        19 => 'yinlianPc#10023', // 上海銀行
        217 => 'yinlianPc#10017', // 渤海銀行
        220 => 'yinlianPc#10026', // 杭州銀行
        221 => 'yinlianPc#10022', // 浙商銀行
        222 => 'yinlianPc#10019', // 寧波銀行
        223 => 'yinlianPc#10018', // 東亞銀行
        226 => 'yinlianPc#10021', // 南京銀行
        228 => 'yinlianPc#10024', // 上海農村商業銀行
        233 => 'yinlianPc#10027', // 浙江江稠商業銀行
        234 => 'yinlianPc#10020', // 北京農村商業銀行
        1088 => 'yinlianWap', // 銀聯_手機支付
        1090 => 'weixinQRCode', // 微信_二維
        1092 => 'alipayQRCode', // 支付寶_二維
        1097 => 'weixinWap', // 微信_手機支付
        1098 => 'alipayWap', // 支付寶_手機支付
        1103 => 'qqQRCode', // QQ_二維
        1104 => 'qqWap', // QQ_手機支付
        1107 => 'JdQRCode', // 京東_二維
        1111 => 'yinlianQRCode', // 銀聯_二維
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

        // 檢查銀行代碼是否支援
        if (!array_key_exists($this->requestData['payType'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $this->requestData['payType'] = $this->bankMap[$this->requestData['payType']];
        $this->requestData['amount'] = sprintf('%.2f', $this->requestData['amount']);
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

        // 組織加密串
        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options)) {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        $encodeData['key'] = $this->privateKey;
        $encodeStr = urldecode(http_build_query($encodeData));

        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['sign'] != md5($encodeStr)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['dealCode'] != '10000') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['orderNumber'] != $entry['id']) {
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

        foreach ($this->encodeParams as $index) {
            $encodeData[$index] = $this->requestData[$index];
        }

        $encodeData['key'] = $this->privateKey;
        $encodeStr = urldecode(http_build_query($encodeData));

        return md5($encodeStr);
    }
}

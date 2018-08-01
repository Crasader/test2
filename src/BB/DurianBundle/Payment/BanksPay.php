<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 新寶支付
 */
class BanksPay extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'version' => 'V1.0', // 接口版本
        'partner_id' => '', // 商號
        'pay_type' => '0001', // 支付類型(預設網銀)
        'bank_code' => '', // 銀行代碼
        'order_no' => '', // 訂單號
        'amount' => '', // 交易金額
        'return_url' => '', // 前台跳轉網址
        'notify_url' => '', // 異步通知
        'summary' => '', // 商品描述(可空)
        'attach' => '', // 回傳參數(可空)
        'sign' => '', // 簽名
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'partner_id' => 'number',
        'bank_code' => 'paymentVendorId',
        'order_no' => 'orderId',
        'amount' => 'amount',
        'return_url' => 'notify_url',
        'notify_url' => 'notify_url',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'version',
        'partner_id',
        'pay_type',
        'bank_code',
        'order_no',
        'amount',
        'return_url',
        'notify_url',
        'summary',
        'attach',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'code' => 1,
        'message' => 1,
        'order_no' => 1,
        'trade_no' => 1,
        'amount' => 1,
        'partner_id' => 0,
        'attach' => 0,
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
        '1' => 'ICBC', // 工商銀行
        '2' => 'BOCOM', // 交通銀行
        '3' => 'ABC', // 農業銀行
        '4' => 'CCB', // 建設銀行
        '5' => 'CMB', // 招商銀行
        '6' => 'CMBC', // 民生銀行
        '8' => 'SPDB', // 浦發銀行
        '9' => 'BCCB', // 北京銀行
        '10' => 'CIB', // 興業銀行
        '11' => 'CITIC', // 中信銀行
        '12' => 'CEB', // 光大銀行
        '13' => 'HXB', // 華夏銀行
        '14' => 'CGB', // 廣發銀行
        '15' => 'PINGAN', // 平安銀行
        '16' => 'POST', // 郵政儲蓄銀行
        '17' => 'BOC', // 中國銀行
        '19' => 'SHB', // 上海銀行
        '234' => 'BJRCB', // 北京農村商業銀行
        '1090' => '0002', // 微信_二維
        '1092' => '0003', // 支付寶_二維
        '1097' => '0004', // 微信_WAP
        '1098' => '0005', // 支付寶_WAP
        '1103' => '0006', // QQ_二維
        '1104' => '0007', // QQ_WAP
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

        // 檢查銀行是否支援
        if (!array_key_exists($this->options['paymentVendorId'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $this->requestData['amount'] = sprintf('%.2f', $this->requestData['amount']);
        $this->requestData['bank_code'] = $this->bankMap[$this->requestData['bank_code']];

        // 非網銀支付不帶入銀行代碼
        if (in_array($this->options['paymentVendorId'], [1090, 1092, 1097, 1098, 1103, 1104])) {
            $this->requestData['pay_type'] = $this->requestData['bank_code'];
            $this->requestData['bank_code'] = '';
        }

        // 產生加密字串
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

        // 組織加密串
        $encodeData = [];
        foreach (array_keys($this->decodeParams) as $key) {
            if (array_key_exists($key, $this->options) && $this->options[$key] !== '') {
                $encodeData[$key] = $this->options[$key];
            }
        }

        ksort($encodeData);

        $encodeStr = urldecode(http_build_query($encodeData));
        $encodeStr .= '&' . $this->privateKey;

        // 檢查簽名
        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if (strcasecmp($this->options['sign'], md5($encodeStr))) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        // 檢查支付狀態
        if ($this->options['code'] !== '00') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        // 檢查單號
        if ($this->options['order_no'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        // 檢查金額
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

        // 空值不加密
        foreach ($this->encodeParams as $index) {
            if ($this->requestData[$index] !== '') {
                $encodeData[$index] = $this->requestData[$index];
            }
        }

        ksort($encodeData);

        $encodeStr = urldecode(http_build_query($encodeData));
        $encodeStr .= '&' . $this->privateKey;

        return strtolower(md5($encodeStr));
    }
}

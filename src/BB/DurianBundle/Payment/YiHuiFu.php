<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 億惠付
 */
class YiHuiFu extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'app_id' => '', // 商號
        'method' => 'gateway', // 接口名稱，網銀:gateway
        'sign' => '', // 簽名
        'sign_type' => 'MD5', // 簽名類型
        'version' => '1.0', // 接口版本，固定:1.0
        'content' => '', // 業務參數
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'app_id' => 'number',
        'out_trade_no' => 'orderId',
        'order_name' => 'orderId',
        'total_amount' => 'amount',
        'subject' => 'orderId',
        'order_name' => 'orderId',
        'spbill_create_ip' => 'ip',
        'bank_code' => 'paymentVendorId',
        'notify_url' => 'notify_url',
        'return_url' => 'notify_url',
    ];

    /**
     * 支付時的業務參數
     *
     * @var array
     */
    private $businessData = [
        'out_trade_no' => '', // 訂單號
        'order_name' => '', // 商品描述
        'total_amount' => '', // 金額，單位元，精確到分
        'channel_type' => '07', // 渠道類型，07:互連網，08:移動端
        'subject' => '', // 訂單標題
        'spbill_create_ip' => '', // 提交用戶端ip
        'bank_code' => '', // 銀行編碼
        'notify_url' => '', // 異步通知地址，不能串參數
        'return_url' => '', // 同步通知地址
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'out_trade_no' => 1,
        'trade_no' => 1,
        'total_amount' => 1,
        'status' => 1,
        'pay_time' => 1,
        'trade_type' => 1,
    ];

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        '1' => 'ICBC', // 工商銀行
        '2' => 'COMM', // 交通銀行
        '3' => 'ABC', // 農業銀行
        '4' => 'CCB', // 建設銀行
        '5' => 'CMB', // 招商銀行
        '6' => 'CMBC', // 民生銀行總行
        '8' => 'SPDB', // 上海浦東發展銀行
        '10' => 'CIB', // 興業銀行
        '11' => 'CITIC', // 中信銀行
        '12' => 'CEBBANK', // 光大銀行
        '13' => 'HXB', // 華夏銀行
        '14' => 'GDB', // 廣東發展銀行
        '15' => 'SPABANK', // 平安銀行
        '16' => 'PSBC-DEBIT', // 中國郵政
        '17' => 'BOCB2C', // 中國銀行
        '19' => 'SHBANK', // 上海銀行
        '217' => 'CBHB', // 渤海银行
        '222' => 'NBCB', // 寧波銀行
        '223' => 'HKBEA', // 東亞銀行
        '226' => 'NJCB', // 南京銀行
        '228' => 'SRCB', // 上海農商銀行
        '234' => 'BJRCB', // 北京農商銀行
        '1092' => 'alipay', // 支付寶_二維
        '1098' => 'alipaywap', // 支付寶_手機支付
        '1103' => 'qqrcode', // QQ_二維
        '1104' => 'qqqb', // QQ_手機支付
        '1108' => 'jdwap', // 京東_手機支付
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
            if (array_key_exists($paymentKey, $this->requestData)) {
                $this->requestData[$paymentKey] = $this->options[$internalKey];
            }

            if (array_key_exists($paymentKey, $this->businessData)) {
                $this->businessData[$paymentKey] = $this->options[$internalKey];
            }
        }

        // 帶入未支援的銀行就噴例外
        if (!array_key_exists($this->businessData['bank_code'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $this->businessData['bank_code'] = $this->bankMap[$this->businessData['bank_code']];
        $this->businessData['total_amount'] = sprintf("%.2f", $this->businessData['total_amount']);

        // 調整非網銀參數
        if (in_array($this->options['paymentVendorId'], ['1092', '1098', '1103', '1104', '1108'])) {
            $this->requestData['method'] = $this->businessData['bank_code'];

            unset($this->businessData['channel_type']);
            unset($this->businessData['subject']);
            unset($this->businessData['bank_code']);
        }

        // 提交參數設定
        $this->requestData['content'] = json_encode($this->businessData);

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

        // 組織加密串
        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options) && trim($this->options[$paymentKey]) !== '') {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        ksort($encodeData);
        $encodeData['key'] = $this->privateKey;
        $encodeStr = urldecode(http_build_query($encodeData));

        // 如果沒有返回簽名要丟例外
        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['sign'] != md5($encodeStr)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['status'] !== '1') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['out_trade_no'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['total_amount'] != sprintf("%.6f", $entry['amount'])) {
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
        foreach ($this->requestData as $key => $value) {
            if ($key != 'sign' && $key != 'sign_type' && trim($value) !== '') {
                $encodeData[$key] = $value;
            }
        }

        ksort($encodeData);
        $encodeData['key'] = $this->privateKey;
        $encodeStr = urldecode(http_build_query($encodeData));

        return md5($encodeStr);
    }
}

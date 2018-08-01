<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 全富通-贏魚
 */
class YingYu extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'messageid' => '200002', // 網銀:200002
        'out_trade_no' => '', // 訂單號
        'branch_id' => '', // 商號
        'pay_type' => '30', // 支付渠道編號，30:網銀
        'total_fee' => '', // 訂單金額，單位:分
        'prod_name' => '', // 訂單標題
        'prod_desc' => '', // 產品描述
        'back_notify_url' => '', // 異步通知地址，不能攜帶參數
        'front_notify_url' => '', // 同步通知地址，不能攜帶參數
        'bank_code' => '', // 銀行編碼
        'bank_flag' => '0', // 0:借記卡
        'nonce_str' => '', // 隨機字符串(不能長於32位元)
        'attach_content' => '', // 備註
        'sign' => '', // 簽名
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'out_trade_no' => 'orderId',
        'branch_id' => 'number',
        'total_fee' => 'amount',
        'prod_name' => 'orderId',
        'prod_desc' => 'orderId',
        'back_notify_url' => 'notify_url',
        'front_notify_url' => 'notify_url',
        'bank_code' => 'paymentVendorId',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'messageid',
        'out_trade_no',
        'branch_id',
        'pay_type',
        'total_fee',
        'prod_name',
        'prod_desc',
        'back_notify_url',
        'front_notify_url',
        'bank_code',
        'bank_flag',
        'nonce_str',
        'attach_content',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'resultCode' => 1,
        'resultDesc' => 1,
        'resCode' => 1,
        'resDesc' => 1,
        'nonceStr' => 1,
        'branchId' => 1,
        'createTime' => 1,
        'orderAmt' => 1,
        'orderNo' => 1,
        'outTradeNo' => 1,
        'productDesc' => 1,
        'payType' => 0,
        'status' => 1,
    ];

    /**
     * 應答機制訊息
     *
     * @var string
     */
    protected $msg = '{"resCode":"00","resDesc":"SUCCESS"}';

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        '1' => 'ICBCD', // 工商銀行
        '2' => 'BOCOM', // 交通銀行
        '3' => 'ABCD', // 農業銀行
        '4' => 'CCBD', // 建設銀行
        '5' => 'CMB', // 招商銀行
        '6' => 'CMBCD', // 民生銀行總行
        '8' => 'SPDB', // 上海浦東發展銀行
        '9' => 'BCCB', // 北京銀行
        '10' => 'CIB', // 興業銀行
        '11' => 'CNCB', // 中信銀行
        '12' => 'CEBD', // 光大銀行
        '13' => 'HXB', // 華夏銀行
        '14' => 'GDB', // 廣東發展銀行
        '15' => 'PAB', // 平安銀行
        '16' => 'PSBCD', // 中國郵政
        '17' => 'BOCSH', // 中國銀行
        '19' => 'BOS', // 上海銀行
        '228' => 'SRCB', // 上海農商銀行
        '234' => 'BRCB', // 北京農商銀行
        '1088' => '65', // 銀聯在線_手機支付(快捷)
        '1090' => '10', // 微信_二維
        '1092' => '20', // 支付寶_二維
        '1097' => '61', // 微信_手機支付
        '1098' => '62', // 支付寶_手機支付
        '1103' => '50', // QQ_二維
        '1104' => '63', // QQ_手機支付
        '1107' => '40', // 京東_二維
        '1108' => '64', // 京東_手機支付
        '1111' => '70', // 銀聯_二維
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
        if (!array_key_exists($this->requestData['bank_code'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外參數設定
        $this->requestData['bank_code'] = $this->bankMap[$this->requestData['bank_code']];
        $this->requestData['nonce_str'] = md5(uniqid(rand(), true));
        $this->requestData['total_fee'] = round($this->requestData['total_fee'] * 100);

        // 二維、手機支付
        $vendors = [1088, 1090, 1092, 1097, 1098, 1103, 1104, 1107, 1108, 1111, 1120];

        if (in_array($this->options['paymentVendorId'], $vendors)) {
            $this->requestData['pay_type'] = $this->requestData['bank_code'];
            unset($this->requestData['bank_code']);
            unset($this->requestData['bank_flag']);

            // 調整二維提交參數
            if (in_array($this->options['paymentVendorId'], [1090, 1092, 1103, 1107, 1111])) {
                $this->requestData['messageid'] = '200001';
                unset($this->requestData['front_notify_url']);
            }

            // 調整手機支付提交參數
            if (in_array($this->options['paymentVendorId'], [1088, 1097, 1098, 1104, 1108, 1120])) {
                $this->requestData['messageid'] = '200004';
            }
        }

        // 設定支付平台需要的加密串
        $this->requestData['sign'] = $this->encode();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'method' => 'POST',
            'uri' => '/yypay',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => json_encode($this->requestData),
            'header' => ['Port' => 31006],
        ];

        $result = json_decode($this->curlRequest($curlParam), true);

        if (!isset($result['resultCode']) || !isset($result['resultDesc'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        if ($result['resultCode'] !== '00') {
            throw new PaymentConnectionException($result['resultDesc'], 180130, $this->getEntryId());
        }

        if (!isset($result['resCode']) || !isset($result['resDesc'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        if ($result['resCode'] !== '00') {
            throw new PaymentConnectionException($result['resDesc'], 180130, $this->getEntryId());
        }

        if (!isset($result['payUrl'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        // 二維支付
        if (in_array($this->options['paymentVendorId'], [1090, 1092, 1103, 1107, 1111])) {
            $this->setQrcode($result['payUrl']);

            return [];
        }

        $parsedUrl = $this->parseUrl($result['payUrl']);

        $this->payMethod = 'GET';

        return [
            'post_url' => $parsedUrl['url'],
            'params' => $parsedUrl['params'],
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

        $encodeData = [];

        // 組織加密串
        foreach (array_keys($this->decodeParams) as $paymentKey) {
            // 如果有key而且不是空值的參數才需要做加密
            if (array_key_exists($paymentKey, $this->options) && $this->options[$paymentKey] != '') {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        ksort($encodeData);
        $encodeData['key'] = $this->privateKey;
        $encodeStr = urldecode(http_build_query($encodeData));

        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if (strcasecmp($this->options['sign'], md5($encodeStr)) != 0) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['resultCode'] !== '00') {
            throw new PaymentConnectionException($this->options['resultDesc'], 180130, $this->getEntryId());
        }

        if ($this->options['resCode'] !== '00') {
            throw new PaymentConnectionException($this->options['resDesc'], 180130, $this->getEntryId());
        }

        if ($this->options['status'] === '00') {
            throw new PaymentConnectionException('Unpaid order', 180062, $this->getEntryId());
        }

        if ($this->options['status'] === '01') {
            throw new PaymentConnectionException('Order Processing', 180059, $this->getEntryId());
        }

        if ($this->options['status'] !== '02') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['outTradeNo'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['orderAmt'] != round($entry['amount'] * 100)) {
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
            if (array_key_exists($index, $this->requestData) && trim($this->requestData[$index]) !== '') {
                $encodeData[$index] = $this->requestData[$index];
            }
        }

        ksort($encodeData);
        $encodeData['key'] = $this->privateKey;
        $encodeStr = urldecode(http_build_query($encodeData));

        return strtoupper(md5($encodeStr));
    }
}

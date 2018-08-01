<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 易樂享支付
 */
class YiLeXiangPay extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'sign' => '', // 簽名
        'appId' => '', // appId
        'custNo' => '', // 商户號
        'payChannel' => '04', // 支付方式，網銀:04
        'money' => '', // 交易金額，單位：元
        'attach' => '', // 附加参数，可放訂單號
        'callBackUrl' => '', // 回調地址
        'bankCode' => '', // 銀行編碼，網銀專用
        'mchOrderNo' => '', // 商戶訂單號
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'custNo' => 'number',
        'money' => 'amount',
        'attach' => 'orderId',
        'callBackUrl' => 'notify_url',
        'bankCode' => 'paymentVendorId',
        'mchOrderNo' => 'orderId',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'appId',
        'custNo',
        'payChannel',
        'money',
        'attach',
        'callBackUrl',
        'bankCode',
        'mchOrderNo',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'attach' => 1,
        'cust_no' => 1,
        'money' => 1,
        'order_id' => 1,
        'pay_channel' => 1,
        'pay_status' => 1,
        'return_code' => 1,
        'return_msg' => 1,
        'trade_no' => 1,
    ];

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        '1' => '01020000', // 工商銀行
        '2' => '03010000', // 交通銀行
        '3' => '01030000', // 農業銀行
        '4' => '01050000', // 建設銀行
        '5' => '03080000', // 招商银行
        '6' => '03050000', // 民生銀行
        '7' => '03070000', // 深圳發展銀行
        '8' => '03100000', // 浦發銀行
        '10' => '03090000', // 興業銀行
        '11' => '03020000', // 中信銀行
        '12' => '03030000', // 光大銀行
        '13' => '03040000', // 華夏銀行
        '14' => '03060000', // 廣發銀行
        '15' => '04100000', // 平安銀行
        '17' => '01040000', // 中國銀行
        '278' => '05', // 銀聯在線
        '1088' => '05', // 銀聯在線_手機支付
        '1103' => '03', // QQ_二維
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
        if (!array_key_exists($this->requestData['bankCode'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 取得商家附加設定值
        $merchantExtraValues = $this->getMerchantExtraValue(['appId']);

        // 商家額外的參數設定
        $this->requestData['appId'] = $merchantExtraValues['appId'];
        $this->requestData['bankCode'] = $this->bankMap[$this->requestData['bankCode']];
        $this->requestData['money'] = sprintf('%.2f', $this->requestData['money']);

        // 非網銀調整支付方式參數
        if (in_array($this->options['paymentVendorId'], [278, 1088, 1103])) {
            $this->requestData['payChannel'] = $this->requestData['bankCode'];
            unset($this->encodeParams['bankCode']);
        }

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        // 設定支付平台需要的加密串
        $this->requestData['sign'] = $this->encode();

        $curlParam = [
            'method' => 'POST',
            'uri' => '/open/pay/scanCodePayChannel',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => urldecode(http_build_query($this->requestData)),
            'header' => [],
        ];

        $result = $this->curlRequest($curlParam);
        $parseData = json_decode($result, true);

        if (!isset($parseData['code'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        if ($parseData['code'] != '1') {
            throw new PaymentConnectionException($parseData['msg'], 180130, $this->getEntryId());
        }

        if (!isset($parseData['pay_url'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        // 二維支付
        if ($this->options['paymentVendorId'] == 1103) {
            $this->setQrcode($parseData['pay_url']);

            return [];
        }

        return [
            'post_url' => $parseData['pay_url'],
            'params' => [],
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
            if (array_key_exists($paymentKey, $this->options) && trim($this->options[$paymentKey]) != '') {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        ksort($encodeData);

        $encodeStr = urldecode(http_build_query($encodeData));
        $encodeStr .= $this->privateKey;

        // 若沒有返回簽名需丟例外
        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['sign'] != md5($encodeStr)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['pay_status'] != 'success') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['attach'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['money'] != $entry['amount']) {
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
            // 非空參數才要參與簽名
            if (isset($this->requestData[$index]) && trim($this->requestData[$index]) != '') {
                $encodeData[$index] = $this->requestData[$index];
            }
        }

        ksort($encodeData);

        $encodeStr = urldecode(http_build_query($encodeData));
        $encodeStr .= $this->privateKey;

        return md5($encodeStr);
    }
}

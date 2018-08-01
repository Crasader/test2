<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 暢支付
 */
class ChangPay extends PaymentBase
{
    /**
     * 支付提交參數
     *
     * @var array
     */
    protected $requestData = [
        'sign_type' => 'md5', // 加密方式，默認:md5
        'mch_id' => '', // 商戶號
        'mch_order' => '', // 訂單號
        'amt' => '', // 支付金額，單位:厘
        'remark' => '', // 訂單內容，不可為空
        'created_at' => '', // 訂單建立時間
        'client_ip' => '', // 用戶端IP
        'notify_url' => '', // 異步通知地址
        'bank_card_type' => '10', // 儲蓄卡:10
        'bank_code' => '', // 銀行
        'sign' => '', // 簽名
    ];

    /**
     * 支付提交參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'mch_id' => 'number',
        'mch_order' => 'orderId',
        'amt' => 'amount',
        'remark' => 'orderId',
        'created_at' => 'orderCreateDate',
        'client_ip' => 'ip',
        'notify_url' => 'notify_url',
        'bank_code' => 'paymentVendorId',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'sign_type',
        'mch_id',
        'mch_order',
        'amt',
        'remark',
        'created_at',
        'client_ip',
        'notify_url',
        'bank_card_type',
        'bank_code',
    ];

    /**
     * 返回驗證時需要加密的參數
     *     0: 有返回就要加密的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'mch_id' => 1,
        'service' => 1,
        'mch_order' => 1,
        'amt' => 1,
        'mch_amt' => 1,
        'sign_type' => 1,
        'amt_type' => 1,
        'status' => 1,
        'created_at' => 1,
        'success_at' => 1,
    ];

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        '1' => '1001', // 中國工商銀行
        '3' => '1006', // 中國農業銀行
        '4' => '1004', // 中國建設銀行
        '6' => '1009', // 民生銀行
        '9' => '1015', // 北京銀行
        '12' => '1011', // 光大銀行
        '16' => '1007', // 中國郵政
        '19' => '1016', // 上海銀行
        '278' => '', // 銀聯在線
        '1088' => '', // 銀聯在線_手機支付
        '1090' => '', // 微信_二維
        '1092' => '', // 支付寶_二維
        '1098' => '', // 支付寶_手機支付
        '1103' => '', // QQ_二維
        '1108' => '', // 京東_手機支付
        '1111' => '', // 銀聯_二維
    ];

    /**
     * 非網銀提交網址
     *
     * @var array
     */
    protected $nonOnlineBankUri = [
        '278' => '/api/v1/quick_page.api',
        '1088' => '/api/v1/quick_page.api',
        '1090' => '/api/v1/wx_qrcode.api',
        '1092' => '/api/v1/ali_qrcode.api',
        '1098' => '/api/v1/ali_h5.api',
        '1103' => '/api/v1/qq_qrcode.api',
        '1108' => '/api/v1/jd_wap.api',
        '1111' => '/api/v1/union_qrcode.api',
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

        // 從內部設值到支付參數
        foreach ($this->requireMap as $paymentKey => $internalKey) {
            $this->requestData[$paymentKey] = $this->options[$internalKey];
        }

        // 檢查銀行代碼是否支援
        if (!array_key_exists($this->requestData['bank_code'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $this->requestData['bank_code'] = $this->bankMap[$this->requestData['bank_code']];
        $this->requestData['amt'] = round($this->requestData['amt'] * 1000);
        $date = new \DateTime($this->requestData['created_at']);
        $this->requestData['created_at'] = $date->getTimestamp();

        // 調整網銀提交網址
        $uri = '/api/v1/union.api';

        // 非網銀調整提交參數與提交網址
        if (array_key_exists($this->options['paymentVendorId'], $this->nonOnlineBankUri)) {
            $uri = $this->nonOnlineBankUri[$this->options['paymentVendorId']];
            unset($this->requestData['bank_card_type']);
            unset($this->requestData['bank_code']);
        }

        // 設定支付平台需要的加密串
        $this->requestData['sign'] = $this->encode();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'method' => 'POST',
            'uri' => $uri,
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => http_build_query($this->requestData),
            'header' => [],
        ];

        $result = $this->curlRequest($curlParam);
        $parseData = json_decode($result, true);

        if (!isset($parseData['code'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        if ($parseData['code'] !== 1 && isset($parseData['msg'])) {
            throw new PaymentConnectionException($parseData['msg'], 180130, $this->getEntryId());
        }

        if ($parseData['code'] !== 1) {
            throw new PaymentConnectionException('Pay error', 180130, $this->getEntryId());
        }

        $urlIndex = 'pay_info';

        // 二維
        if (in_array($this->options['paymentVendorId'], ['1090', '1092', '1103', '1111'])) {
            $urlIndex = 'code_url';
        }

        // 京東手機支付
        if ($this->options['paymentVendorId'] == '1108') {
            $urlIndex = 'pay_url';
        }

        if (!isset($parseData['data'][$urlIndex])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        // 二維
        if (in_array($this->options['paymentVendorId'], ['1090', '1092', '1103', '1111'])) {
            $this->setQrcode($parseData['data']['code_url']);

            return [];
        }

        $urlData = $this->parseUrl($parseData['data'][$urlIndex]);

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
        $this->verifyPrivateKey();
        $this->payResultVerify();

        // 組合參數驗證加密簽名
        $encodeData = [];

        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options)) {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        $encodeData['mch_key'] = $this->privateKey;

        ksort($encodeData);

        $encodeStr = urldecode(http_build_query($encodeData));

        // 沒有返回sign就要丟例外
        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['sign'] !== md5($encodeStr)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['status'] !== '2') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['mch_order'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['mch_amt'] != round($entry['amount'] * 1000)) {
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
            if (array_key_exists($index, $this->requestData)) {
                $encodeData[$index] = $this->requestData[$index];
            }
        }

        $encodeData['mch_key'] = $this->privateKey;

        ksort($encodeData);

        $encodeStr = urldecode(http_build_query($encodeData));

        return md5($encodeStr);
    }
}

<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 創客支付
 */
class CkePay extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'store_id' => '', // 機構號，為商號的前7碼
        'mch_id' => '', // 商戶號
        'pay_type' => '37', // 交易類型，37:網銀
        'out_trade_no' => '', // 訂單號
        'trans_amt' => '', // 金額，單位元
        'bank_english_code' => '', // 銀行代碼，網銀用參數
        'card_type' => '0', // 卡類型，網銀用參數，0:借記卡
        'notify_url' => '', // 返回地址
        'body' => '', // 商品信息
        'sign' => '', // 簽名
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'mch_id' => 'number',
        'out_trade_no' => 'orderId',
        'trans_amt' => 'amount',
        'bank_english_code' => 'paymentVendorId',
        'notify_url' => 'notify_url',
        'body' => 'orderId',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'store_id',
        'mch_id',
        'pay_type',
        'out_trade_no',
        'trans_amt',
        'bank_english_code',
        'card_type',
        'notify_url',
        'body',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'store_id' => 1,
        'mch_id' => 1,
        'pay_type' => 1,
        'out_trade_no' => 1,
        'order_no' => 1,
        'trans_amt' => 1,
        'body' => 1,
        'ret_code' => 1,
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
        '1' => 'ICBC', // 工商銀行
        '2' => 'BCOM', // 交通銀行
        '3' => 'ABC', // 農業銀行
        '4' => 'CCB', // 建設銀行
        '5' => 'CMB', // 招商銀行
        '6' => 'CMBC', // 民生銀行
        '8' => 'SPDB', // 浦發銀行
        '9' => 'BJB', // 北京銀行
        '10' => 'FIB', // 興業銀行
        '11' => 'CITIC', // 中信銀行
        '12' => 'CEB', // 光大銀行
        '13' => 'HXB', // 華夏銀行
        '14' => 'CGB', // 廣發銀行
        '15' => 'PABC', // 平安銀行
        '16' => 'PSBC', // 中國郵政
        '17' => 'BOC', // 中國銀行
        '19' => 'SHB', // 上海銀行
        '278' => '38', // 銀聯在線(快捷)
        '1088' => '38', // 銀聯在線_手機支付(快捷)
        '1092' => '41', // 支付寶_二維
        '1098' => '41', // 支付寶_手機支付
        '1103' => '35', // QQ_二維
        '1104' => '35', // QQ_手機支付
        '1111' => '06', // 銀聯錢包_二維
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
        if (!array_key_exists($this->requestData['bank_english_code'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $this->requestData['trans_amt'] = strval($this->requestData['trans_amt']);
        $this->requestData['bank_english_code'] = $this->bankMap[$this->requestData['bank_english_code']];
        $this->requestData['store_id'] = substr($this->requestData['mch_id'], 0, 7);

        // 調整銀聯在線、二維、手機支付提交參數
        if (in_array($this->options['paymentVendorId'], [278, 1088, 1092, 1098, 1103, 1104, 1111])) {
            $this->requestData['pay_type'] = $this->requestData['bank_english_code'];
            unset($this->requestData['bank_english_code']);
            unset($this->requestData['card_type']);
        }

        // 設定支付平台需要的加密串
        $this->requestData['sign'] = $this->encode();

        // 銀聯掃碼
        if ($this->options['paymentVendorId'] == 1111) {
            if (trim($this->options['verify_url']) == '') {
                throw new PaymentException('No verify_url specified', 180140);
            }

            $curlParam = [
                'method' => 'POST',
                'uri' => '/pay/qrCodePay',
                'ip' => $this->options['verify_ip'],
                'host' => $this->options['verify_url'],
                'param' => 'reqData=' . urlencode(json_encode($this->requestData)),
                'header' => ['Port' => 8089],
            ];

            $result = $this->curlRequest($curlParam);
            $parseData = json_decode($result, true);

            if (!isset($parseData['ret_code'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            // 返回非 00 都為失敗
            if ($parseData['ret_code'] != '00') {
                throw new PaymentConnectionException('Pay error', 180130, $this->getEntryId());
            }

            if (!isset($parseData['img_url'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            $urlData = $this->parseUrl($parseData['img_url']);

            // Form使用GET才能正常跳轉
            $this->payMethod = 'GET';

            return [
               'post_url' => $urlData['url'],
               'params' => $urlData['params'],
            ];
        }

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
            if (array_key_exists($paymentKey, $this->options)) {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        ksort($encodeData);
        $encodeData['key'] = $this->privateKey;

        // 依key1=value1&key2=value2&...&keyN=valueN之後做md5
        $encodeStr = urldecode(http_build_query($encodeData));

        // 如果沒有返回簽名要丟例外
        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if (strcasecmp($this->options['sign'], strtoupper(md5($encodeStr))) != 0) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['ret_code'] !== '00') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['out_trade_no'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['trans_amt'] != $entry['amount']) {
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
        foreach ($this->encodeParams as $key) {
            if (isset($this->requestData[$key])) {
                $encodeData[$key] = $this->requestData[$key];
            }
        }

        ksort($encodeData);
        $encodeData['key'] = $this->privateKey;

        // 依key1=value1&key2=value2&...&keyN=valueN之後做md5
        $encodeStr = urldecode(http_build_query($encodeData));

        return strtoupper(md5($encodeStr));
    }
}

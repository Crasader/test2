<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 支付寶APP
 */
class AliPayApp extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'app_id' => '', // 應用id
        'charset' => 'utf-8', // 編碼格式
        'method' => 'alipay.trade.app.pay', // 接口名稱
        'timestamp' => '', // 當前時間
        'sign_type' => 'RSA', // 簽名算法類型
        'sign' => '', // 簽名串
        'version' => '1.0', // 接口版本
        'notify_url' => '', // 主動通知網址
        'biz_content' => '', // 商品數據
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'app_id' => 'number',
        'notify_url' => 'notify_url',
        'biz_content' => 'amount',
        'biz_content' => 'username',
        'biz_content' => 'orderId',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'app_id',
        'biz_content',
        'charset',
        'method',
        'sign_type',
        'timestamp',
        'version',
        'notify_url',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'notify_time' => 1,
        'notify_type' => 1,
        'notify_id' => 1,
        'app_id' => 1,
        'charset' => 1,
        'version' => 1,
        'trade_no' => 1,
        'out_trade_no' => 1,
        'out_biz_no' => 0,
        'buyer_id' => 0,
        'buyer_logon_id' => 0,
        'seller_id' => 0,
        'seller_email' => 0,
        'trade_status' => 1,
        'total_amount' => 1,
        'receipt_amount' => 0,
        'invoice_amount' => 0,
        'buyer_pay_amount' => 0,
        'point_amount' => 0,
        'refund_fee' => 0,
        'subject' => 0,
        'body' => 0,
        'gmt_create' => 0,
        'gmt_payment' => 0,
        'gmt_refund' => 0,
        'gmt_close' => 0,
        'fund_bill_list' => 0,
        'passback_params' => 0,
        'voucher_detail_list' => 0,
        'auth_app_id' => 0,
    ];

    /**
     * 查詢時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $trackingRequestData = [
        'app_id' => '', // 應用id
        'method' => 'alipay.trade.query', // 接口名稱
        'charset' => 'utf-8', // 編碼格式
        'sign_type' => 'RSA', // 簽名算法類型
        'sign' => '', // 簽名串
        'timestamp' => '', // 當前時間
        'version' => '1.0', // 接口版本
        'biz_content' => '', // 商品數據
    ];

    /**
     * 查詢時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $trackingRequireMap = [
        'app_id' => 'number',
    ];

    /**
     * 查詢時需要加密的參數
     *
     * @var array
     */
    protected $trackingEncodeParams = [
        'app_id',
        'method',
        'charset',
        'sign_type',
        'timestamp',
        'version',
        'biz_content',
    ];

    /**
     * 查詢解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $trackingDecodeParams = [
        'code' => 1,
        'msg' => 1,
        'sub_code' => 0,
        'sub_msg' => 0,
        'trade_no' => 1,
        'out_trade_no' => 1,
        'open_id' => 0,
        'buyer_logon_id' => 1,
        'trade_status' => 1,
        'total_amount' => 1,
        'receipt_amount' => 1,
        'buyer_pay_amount' => 0,
        'point_amount' => 0,
        'invoice_amount' => 0,
        'send_pay_date' => 1,
        'alipay_store_id' => 0,
        'store_id' => 0,
        'terminal_id' => 0,
        'fund_bill_list' => 0,
        'store_name' => 0,
        'buyer_user_id' => 1,
        'discount_goods_detail' => 0,
        'industry_sepc_detail' => 0,
    ];

    /**
     * 回傳支付時要給支付平台驗證的資料
     *
     * @return array
     */
    public function getVerifyData()
    {
        $this->payVerify();

        // 從內部給定值到參數
        foreach ($this->requireMap as $paymentKey => $internalKey) {
            $this->requestData[$paymentKey] = $this->options[$internalKey];
        }

        // 額外的參數設定
        $date = new \DateTime('now');
        $this->requestData['timestamp'] = $date->format('Y-m-d H:i:s');
        $this->requestData['biz_content'] = json_encode([
            'seller_id' => '', // 收款用戶ID，非必填
            'total_amount' => sprintf("%.2f", $this->options['amount']), // 訂單總金額，單位元，小數點後二位
            'subject' => $this->options['username'], // 商品標題
            'out_trade_no' => $this->options['orderId'], // 訂單號
            'product_code' => 'QUICK_MSECURITY_PAY', // 銷售產品碼，固定值
        ]);

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
        $this->payResultVerify();

        $encodeData = [];

        // 組織加密串
        foreach (array_keys($this->decodeParams) as $paymentKey) {
            // 如果有key而且不是空值的參數才需要做加密
            if (array_key_exists($paymentKey, $this->options) && $this->options[$paymentKey] != '') {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        // 針對$encodeData按字母做升序排列
        ksort($encodeData);

        $encodeStr = urldecode(http_build_query($encodeData));

        // 沒有sign就要丟例外，其他的參數在組織加密串的時候驗證過了
        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        $signMsg = base64_decode(rawurldecode(urlencode($this->options['sign'])));
        $status = openssl_verify($encodeStr, $signMsg, $this->getRsaPublicKey());

        if (!$status) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['trade_status'] != 'TRADE_FINISHED' && $this->options['trade_status'] != 'TRADE_SUCCESS') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['out_trade_no'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['total_amount'] != sprintf("%.2f", $entry['amount'])) {
            throw new PaymentException('Order Amount error', 180058);
        }
    }

    /**
     * 訂單查詢
     */
    public function paymentTracking()
    {
        $this->trackingVerify();

        // 從內部給定值到參數
        foreach ($this->trackingRequireMap as $paymentKey => $internalKey) {
            $this->trackingRequestData[$paymentKey] = $this->options[$internalKey];
        }

        // 額外的參數設定
        $date = new \DateTime('now');
        $this->trackingRequestData['timestamp'] = $date->format('Y-m-d H:i:s');
        $this->trackingRequestData['biz_content'] = json_encode([
            'out_trade_no' => $this->options['orderId'],
        ]);

        $this->trackingRequestData['sign'] = $this->trackingEncode();

        // 執行訂單查詢
        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'method' => 'POST',
            'uri' => '/gateway.do',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => http_build_query($this->trackingRequestData),
            'header' => [],
        ];

        $result = $this->curlRequest($curlParam);
        $parseData = json_decode($result, true);

        // 沒有返回alipay_trade_query_response就要丟例外
        if (!isset($parseData['alipay_trade_query_response'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        $resultData = $parseData['alipay_trade_query_response'];

        if (!isset($resultData['code']) && !isset($resultData['msg'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        if ($resultData['code'] != '10000') {
            throw new PaymentConnectionException($resultData['msg'], 180130, $this->getEntryId());
        }

        $this->trackingResultVerify($resultData);

        if ($resultData['trade_status'] == 'WAIT_BUYER_PAY') {
            throw new PaymentConnectionException('Unpaid order', 180062, $this->getEntryId());
        }

        if ($resultData['trade_status'] != 'TRADE_FINISHED' && $resultData['trade_status'] != 'TRADE_SUCCESS') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($resultData['total_amount'] != sprintf("%.2f", $this->options['amount'])) {
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

        foreach ($this->encodeParams as $key) {
            if ($this->requestData[$key] !== '') {
                $encodeData[$key] = $this->requestData[$key];
            }
        }
        ksort($encodeData);

        $encodeStr = urldecode(http_build_query($encodeData));
        $sign = '';

        if (!openssl_sign($encodeStr, $sign, $this->getRsaPrivateKey(), OPENSSL_ALGO_SHA1)) {
            throw new PaymentException('Generate signature failure', 180144);
        }

        return base64_encode($sign);
    }

    /**
     * 訂單查詢時的加密
     *
     * @return string
     */
    protected function trackingEncode()
    {
        $encodeData = [];

        foreach ($this->trackingEncodeParams as $key) {
            if ($this->trackingRequestData[$key] !== '') {
                $encodeData[$key] = $this->trackingRequestData[$key];
            }
        }
        ksort($encodeData);

        $encodeStr = urldecode(http_build_query($encodeData));
        $sign = '';

        if (!openssl_sign($encodeStr, $sign, $this->getRsaPrivateKey(), OPENSSL_ALGO_SHA1)) {
            throw new PaymentException('Generate signature failure', 180144);
        }

        return base64_encode($sign);
    }
}

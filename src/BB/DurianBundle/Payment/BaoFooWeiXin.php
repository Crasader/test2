<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 新寶付微信支付
 */
class BaoFooWeiXin extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'version' => '4.0.0.0', // 版本號
        'terminal_id' => '', // 終端號
        'txn_type' => '10199', // 交易類型
        'txn_sub_type' => '01', // 交易子類。01:微信掃碼支付
        'member_id' => '', // 商戶號
        'data_type' => 'json', // 加密數據類型
        'data_content' => '', // 加密數據
        'trans_id' => '', // 商戶訂單號
        'trans_serial_no' => '', // 商戶流水號
        'txn_amt' => '', // 交易金額，單位為分
        'trade_date' => '', // 訂單日期
        'commodity_name' => '', // 商品名稱(這邊塞username方便業主比對)
        'commodity_amount' => '1',// 商品數量
        'user_id' => '',// 用戶ID，可空
        'user_name' => '',// 用戶名，可空
        'notice_type' => '0', // 通知類型。0-僅服務器通知
        'page_url' => '', // 頁面通知地址
        'return_url' => '', // 服務器通知地址
        'additional_info' => '', // 附加字段，可空
        'req_reserved' => '', // 請求方保留域，可空
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'member_id' => 'number',
        'page_url' => 'notify_url',
        'return_url' => 'notify_url',
        'trans_id' => 'orderId',
        'trans_serial_no' => 'orderId',
        'trade_date' => 'orderCreateDate',
        'txn_amt' => 'amount',
        'commodity_name' => 'username',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'txn_sub_type',
        'terminal_id',
        'member_id',
        'trans_id',
        'trans_serial_no',
        'txn_amt',
        'trade_date',
        'commodity_name',
        'commodity_amount',
        'user_id',
        'user_name',
        'notice_type',
        'page_url',
        'return_url',
        'additional_info',
        'req_reserved',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'data_content' => 1,
    ];

    /**
     * 應答機制訊息
     *
     * @var string
     */
    protected $msg = 'OK';

    /**
     * 查詢時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $trackingRequestData = [
        'version' => '4.0.0.0', // 版本號
        'terminal_id' => '', // 終端號
        'txn_type' => '20199', // 交易類型
        'txn_sub_type' => '03', // 交易子類
        'member_id' => '', // 商戶號
        'data_type' => 'json', // 加密數據類型
        'data_content' => '', // 加密數據
        'trans_serial_no' => '', // 商戶流水號
        'orig_trans_id' => '', // 原始商戶訂單號
        'trade_date' => '', // 訂單日期
        'additional_info' => '', // 附加字段，可空
        'req_reserved' => '', // 請求方保留域，可空
    ];

    /**
     * 查詢時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $trackingRequireMap = [
        'member_id' => 'number',
        'orig_trans_id' => 'orderId',
        'trade_date' => 'orderCreateDate',
    ];

    /**
     * 查詢時需要加密的參數
     *
     * @var array
     */
    protected $trackingEncodeParams = [
        'txn_sub_type',
        'terminal_id',
        'member_id',
        'trans_serial_no',
        'orig_trans_id',
        'trade_date',
        'additional_info',
        'req_reserved',
    ];

    /**
     * 查詢解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $trackingDecodeParams = [
        'resp_code' => 1,
    ];

    /**
     * 回傳支付時要給支付平台驗證的資料
     *
     * @return array
     */
    public function getVerifyData()
    {
        $this->payVerify();

        $this->options['notify_url'] = sprintf(
            '%s?trans_id=%s',
            $this->options['notify_url'],
            $this->options['orderId']
        );

        // 從內部給定值到參數
        foreach ($this->requireMap as $paymentKey => $internalKey) {
            $this->requestData[$paymentKey] = $this->options[$internalKey];
        }

        // 取得商家附加設定值
        $merchantExtraValues = $this->getMerchantExtraValue(['terminal_id']);

        // 額外的參數設定
        $date = new \DateTime($this->requestData['trade_date']);
        $this->requestData['trade_date'] = $date->format('YmdHis');
        $this->requestData['txn_amt'] = round($this->requestData['txn_amt'] * 100);
        $this->requestData['terminal_id'] = $merchantExtraValues['terminal_id'];

        // 設定支付平台需要的加密串
        $this->requestData['data_content'] = $this->encode();

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

        $decryptData = $this->decryptData($this->options['data_content']);

        if (!isset($decryptData['resp_code']) || !isset($decryptData['trans_id']) || !isset($decryptData['succ_amt'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($decryptData['resp_code'] != '0000') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($decryptData['trans_id'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($decryptData['succ_amt'] != round($entry['amount'] * 100)) {
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

        // 取得商家附加設定值
        $merchantExtraValues = $this->getMerchantExtraValue(['terminal_id']);

        // 額外的參數設定
        $createAt = new \Datetime($this->trackingRequestData['trade_date']);
        $this->trackingRequestData['trade_date'] = $createAt->format('YmdHis');
        $this->trackingRequestData['trans_serial_no'] = substr(md5(rand()), 8, 20);
        $this->trackingRequestData['terminal_id'] = $merchantExtraValues['terminal_id'];

        // 設定加密簽名
        $this->trackingRequestData['data_content'] = $this->trackingEncode();

        // 執行訂單查詢
        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'method' => 'POST',
            'uri' => '/platform/gateway/back',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => http_build_query($this->trackingRequestData),
            'header' => []
        ];

        $result = $this->curlRequest($curlParam);
        $decryptData = $this->decryptData($result);

        $this->trackingResultVerify($decryptData);

        // 訂單不存在
        if ($decryptData['resp_code'] == '0310') {
            throw new PaymentConnectionException('Order does not exist', 180060, $this->getEntryId());
        }

        // 訂單未支付
        if ($decryptData['resp_code'] == '0312') {
            throw new PaymentConnectionException('Unpaid order', 180062, $this->getEntryId());
        }

        // 其他錯誤需丟例外
        if ($decryptData['resp_code'] != '0000' && isset($decryptData['resp_msg'])) {
            throw new PaymentConnectionException($decryptData['resp_msg'], 180123, $this->getEntryId());
        }

        // 支付失敗
        if ($decryptData['resp_code'] != '0000') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        // 交易成功會返回金額
        if (!isset($decryptData['succ_amt'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        if ($decryptData['succ_amt'] != round($this->options['amount'] * 100)) {
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

        $encodeStr = str_replace("\\/", "/", json_encode($encodeData, true));
        $content = base64_encode($encodeStr);
        $totalLen = strlen($content);

        $pos = 0;
        $data = '';
        $encrypted = '';

        while ($pos < $totalLen) {
            $substr = substr($content, $pos, 117);

            $status = openssl_private_encrypt($substr, $data, $this->getRsaPrivateKey());

            if (!$status) {
                throw new PaymentException('Generate signature failure', 180144);
            }

            $encrypted .= bin2hex($data);
            $pos += 117;
        }

        return $encrypted;
    }

    /**
     * 訂單查詢時的加密
     *
     * @return string
     */
    protected function trackingEncode()
    {
        $encodeData = [];

        foreach ($this->trackingEncodeParams as $index) {
            $encodeData[$index] = $this->trackingRequestData[$index];
        }

        $encodeStr = str_replace("\\/", "/", json_encode($encodeData, true));
        $content = base64_encode($encodeStr);
        $totalLen = strlen($content);

        $pos = 0;
        $data = '';
        $encrypted = '';

        while ($pos < $totalLen) {
            $substr = substr($content, $pos, 117);

            $status = openssl_private_encrypt($substr, $data, $this->getRsaPrivateKey());

            if (!$status) {
                throw new PaymentException('Generate signature failure', 180144);
            }

            $encrypted .= bin2hex($data);
            $pos += 117;
        }

        return $encrypted;
    }

    /**
     * 回傳RSA私鑰
     *
     * @return resource
     */
    public function getRsaPrivateKey()
    {
        // privateKey為解開RSA私鑰的口令，所以要驗證
        $this->verifyPrivateKey();

        $passphrase = $this->privateKey;

        $content = base64_decode($this->options['rsa_private_key']);

        if (!$content) {
            throw new PaymentException('Rsa private key is empty', 180092);
        }

        $privateCert = [];

        $status = openssl_pkcs12_read($content, $privateCert, $passphrase);

        if (!$status) {
            throw new PaymentException('Get rsa private key failure', 180093);
        }

        return $privateCert['pkey'];
    }

    /**
     * 解密Data
     *
     * @return array
     */
    private function decryptData($result)
    {
        $pos = 0;
        $totalLen = strlen($result);
        $data = '';
        $decrypt = '';

        while ($pos < $totalLen) {
            $status = openssl_public_decrypt(hex2bin(substr($result, $pos, 256)), $data, $this->getRsaPublicKey());

            if (!$status) {
                throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
            }

            $decrypt .= $data;
            $pos += 256;
        }

        return json_decode(base64_decode($decrypt), true);
    }
}

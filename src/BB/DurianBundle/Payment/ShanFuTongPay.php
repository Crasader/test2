<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 閃付通支付
 */
class ShanFuTongPay extends PaymentBase
{
    /**
     * RSA公鑰加密最大明文區塊大小
     */
    const RSA_PUBLIC_ENCODE_BLOCKSIZE = 117;

    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'member_code' => '', // 商號
        'sign' => '', // 簽名
        'type_code' => 'gateway', // 支付類型，網關:gateway
        'down_sn' => '', // 商戶訂單號
        'subject' => '', // 主題，不可空
        'amount' => '', // 訂單金額，單位元，精確到小數點後2位
        'app_id' => '', // 應用ID，非必填
        'notify_url' => '', // 異步通知網址
        'return_url' => '', // 同步通知網址，非必填
        'card_type' => '1', // 銀行卡類型，網關必填，固定值
        'bank_segment' => '', // 銀行代號，網關必填
        'user_type' => '1', // 用戶類型，網關必填，固定值
        'agent_type' => '1', // 渠道，網關必填，固定值
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'member_code' => 'number',
        'down_sn' => 'orderId',
        'subject' => 'orderId',
        'amount' => 'amount',
        'notify_url' => 'notify_url',
        'bank_segment' => 'paymentVendorId',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'type_code',
        'down_sn',
        'subject',
        'amount',
        'app_id',
        'notify_url',
        'return_url',
        'card_type',
        'bank_segment',
        'user_type',
        'agent_type',
    ];

    /**
     * 支付時需要加密的業務參數
     *
     * @var array
     */
    private $cipherData = [
        'sign',
        'type_code',
        'down_sn',
        'subject',
        'amount',
        'app_id',
        'notify_url',
        'return_url',
        'card_type',
        'bank_segment',
        'user_type',
        'agent_type',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'order_sn' => 1,
        'down_sn' => 1,
        'status' => 1,
        'amount' => 1,
        'fee' => 1,
        'trans_time' => 1,
        'remark' => 0,
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
        '1' => '102', // 中國工商銀行
        '2' => '301', // 交通銀行
        '3' => '103', // 中國農業銀行
        '4' => '105', // 中國建設銀行
        '5' => '308', // 招商銀行
        '6' => '305', // 中國民生銀行
        '8' => '310', // 上海浦東發展銀行
        '10' => '309', // 興業銀行
        '11' => '302', // 中信銀行
        '12' => '303', // 中國光大銀行
        '13' => '304', // 華夏銀行
        '14' => '306', // 廣東發展銀行
        '16' => '403', // 中國郵政
        '17' => '104', // 中國銀行
        '217' => '318', // 渤海銀行
        '223' => '502', // 東亞銀行
        '308' => '319', // 徽商銀行
        '311' => '315', // 恒豐銀行
        '1092' => 'zfbbs', // 支付寶_二維
        '1098' => 'zfbh5', // 支付寶_手機支付
        '1103' => 'qqbs', // QQ_二維
        '1111' => 'ylbs', // 銀聯_二維
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
        if (!array_key_exists($this->requestData['bank_segment'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $this->requestData['bank_segment'] = $this->bankMap[$this->requestData['bank_segment']];
        $this->requestData['amount'] = sprintf('%.2f', $this->requestData['amount']);

        // 非網銀
        if (in_array($this->options['paymentVendorId'], [1092, 1098, 1103, 1111])) {
            $this->requestData['type_code'] = $this->requestData['bank_segment'];

            // 移除非網銀不需要的提交參數
            unset($this->requestData['card_type']);
            unset($this->requestData['bank_segment']);
            unset($this->requestData['user_type']);
            unset($this->requestData['agent_type']);
        }

        // 設定加密簽名
        $this->requestData['sign'] = $this->encode();

        // 設定業務參數
        $this->requestData['cipher_data'] = $this->getCipherData();

        // 移除組成業務參數的參數
        foreach ($this->cipherData as $removeParam) {
            if (isset($this->requestData[$removeParam])) {
                unset($this->requestData[$removeParam]);
            }
        }

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'method' => 'POST',
            'uri' => '/api/trans/pay',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => http_build_query($this->requestData),
            'header' => [],
        ];
        $result = $this->curlRequest($curlParam);
        $parseData = json_decode($result, true);

        if (!isset($parseData['code']) || !isset($parseData['msg'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        if ($parseData['code'] !== '0000') {
            throw new PaymentConnectionException($parseData['msg'], 180130, $this->getEntryId());
        }

        if (!isset($parseData['code_url'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        // 二維支付
        if (in_array($this->options['paymentVendorId'], [1092, 1103, 1111])) {
            $this->setQrcode($parseData['code_url']);

            return [];
        }

        $urlData = $this->parseUrl($parseData['code_url']);

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
        $this->payResultVerify();

        if (!isset($this->options['code']) || !isset($this->options['msg'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['code'] !== '0000') {
            throw new PaymentConnectionException($this->options['msg'], 180130, $this->getEntryId());
        }

        $encodeData = [];

        // 組織加密串
        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options) && $this->options[$paymentKey] != '') {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        ksort($encodeData);

        $encodeData['key'] = $this->privateKey;

        $encodeStr = urldecode(http_build_query($encodeData));

        // 沒有sign就要丟例外
        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if (strcasecmp($this->options['sign'], md5($encodeStr)) != 0) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['status'] === '0') {
            throw new PaymentConnectionException('Unpaid order', 180062, $this->getEntryId());
        }

        if ($this->options['status'] === '1') {
            throw new PaymentConnectionException('Order Processing', 180059, $this->getEntryId());
        }

        if ($this->options['status'] !== '2') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['down_sn'] != $entry['id']) {
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

        // 組織加密簽名
        foreach ($this->encodeParams as $index) {
            if (isset($this->requestData[$index]) && $this->requestData[$index] != '') {
                $encodeData[$index] = $this->requestData[$index];
            }
        }

        ksort($encodeData);

        $encodeData['key'] = $this->privateKey;

        $encodeStr = urldecode(http_build_query($encodeData));

        return md5($encodeStr);
    }

    /**
     * 回傳支付時的業務參數
     */
    private function getCipherData()
    {
        $cipherData = [];
        $publicKey = $this->getRsaPublicKey();

        // 業務參數
        foreach ($this->cipherData as $index) {
            if (isset($this->requestData[$index])) {
                $cipherData[$index] = $this->requestData[$index];
            }
        }

        $crypto = '';
        foreach (str_split(json_encode($cipherData), self::RSA_PUBLIC_ENCODE_BLOCKSIZE) as $chunk) {
            $encryptData = '';
            openssl_public_encrypt($chunk, $encryptData, $publicKey);
            $crypto .= $encryptData;
        }

        return base64_encode($crypto);
    }
}

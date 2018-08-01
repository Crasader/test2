<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 久通支付
 */
class JiuTongPay extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'version' => 'V3.1.0.0', // 版本號固定值
        'merNo' => '', // 商戶號
        'netway' => '', // 支付方式
        'random' => '', // 隨機數，參數長度:4
        'orderNum' => '', // 訂單號
        'amount' => '', // 金額，單位:分
        'goodsName' => '', // 商品名稱
        'callBackUrl' => '', // 異步通知網址
        'callBackViewUrl' => '', // 支付成功轉跳網址
        'charset' => 'UTF-8', // 編碼格式
        'sign' => '', // 簽名，字母大寫
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'merNo' => 'number',
        'netway' => 'paymentVendorId',
        'orderNum' => 'orderId',
        'amount' => 'amount',
        'goodsName' => 'orderId',
        'callBackUrl' => 'notify_url',
        'callBackViewUrl' => 'notify_url',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'version',
        'merNo',
        'netway',
        'random',
        'orderNum',
        'amount',
        'goodsName',
        'callBackUrl',
        'callBackViewUrl',
        'charset',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *      0: 可不返回的參數
     *      1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'merNo' => 1,
        'netway' => 1,
        'orderNum' => 1,
        'amount' => 1,
        'goodsName' => 1,
        'payResult' => 1,
        'payDate' => 1,
    ];

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        1 => 'E_BANK_ICBC', // 工商銀行
        2 => 'E_BANK_BCM', // 交通銀行
        3 => 'E_BANK_ABC', // 農業銀行
        4 => 'E_BANK_CCB', // 建設銀行
        5 => 'E_BANK_CMB', // 招商銀行
        6 => 'E_BANK_CMBC', // 民生銀行總行
        7 => 'E_BANK_SDB', // 深圳發展銀行
        8 => 'E_BANK_SPDB', // 上海浦東發展銀行
        9 => 'E_BANK_BOB', // 北京銀行
        10 => 'E_BANK_CIB', // 興業銀行
        11 => 'E_BANK_CNCB', // 中信銀行
        12 => 'E_BANK_CEB', // 光大銀行
        13 => 'E_BANK_HXB', // 華夏銀行
        14 => 'E_BANK_GDB', // 廣發銀行
        15 => 'E_BANK_PAB', // 平安銀行
        16 => 'E_BANK_PSBC', // 中國郵政
        17 => 'E_BANK_BOC', // 中國銀行
        19 => 'E_BANK_BOS', // 上海銀行
        222 => 'E_BANK_BON', // 寧波銀行
        228 => 'E_BANK_SRCB', // 上海農商銀行
        311 => 'E_BANK_HFB', // 恆豐銀行
    ];

    /**
     * 應答機制
     *
     * @var string
     */
    protected $msg = '0';

    /**
     * 出款時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $withdrawRequestData = [
        'version' => 'V3.1.0.0', // 版本號固定值
        'merNo' => '', // 商戶號
        'orderNum' => '', // 訂單號
        'amount' => '', // 金額，單位:分
        'bankCode' => '', // 銀行代碼
        'bankAccountName' => '', // 帳戶名
        'bankAccountNo' => '', // 銀行卡號
        'callBackUrl' => '', // 結果通知網址
        'charset' => 'UTF-8', // 編碼格式
        'sign' => '', // 簽名，字母大寫
    ];

    /**
     * 出款時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $withdrawRequireMap = [
        'merNo' => 'number',
        'orderNum' => 'orderId',
        'amount' => 'amount',
        'bankCode' => 'bank_info_id',
        'bankAccountName' => 'nameReal',
        'bankAccountNo' => 'account',
        'callBackUrl' => 'shop_url',
    ];

    /**
     * 出款時需要加密的參數
     *
     * @var array
     */
    protected $withdrawEncodeParams = [
        'version',
        'merNo',
        'orderNum',
        'amount',
        'bankCode',
        'bankAccountName',
        'bankAccountNo',
        'callBackUrl',
        'charset',
    ];

    /**
     * 出款返回驗證需要加密的參數
     *
     * @var array
     */
    protected $withdrawDecodeParams = [
        'stateCode' => 1,
        'msg' => 1,
    ];

    /**
     * 出款支援的銀行對應編號
     *
     * @var array
     */
    protected $withdrawBankMap = [
        1 => 'ICBC', // 中國工商銀行
        2 => 'BCM', // 交通銀行
        3 => 'ABC', // 中國農業銀行
        4 => 'CCB', // 中國建設銀行
        5 => 'CMB', // 招商銀行
        6 => 'CMBC', // 中國民生銀行
        8 => 'SPDB', // 上海浦東發展銀行
        10 => 'CIB', // 興業銀行
        11 => 'CNCB', // 中信銀行
        12 => 'CEB', // 中國光大銀行
        13 => 'HXB', // 華夏銀行
        16 => 'PSBC', // 中國郵政儲蓄
        17 => 'BOC', // 中國銀行
    ];

    /**
     * 回傳支付時要給支付平台驗證的資料
     *
     * @var array
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
        if (!array_key_exists($this->requestData['netway'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定，參數均為字串
        $this->requestData['random'] = strval(rand(1000, 9999));
        $this->requestData['amount'] = strval(round($this->requestData['amount'] * 100));
        $this->requestData['netway'] = $this->bankMap[$this->requestData['netway']];

        // 設定支付平台需要的加密串
        $this->requestData['sign'] = $this->encode();

        $paramData = [
            'data' => urlencode($this->getRSAEncode()),
            'merchNo' => $this->options['number'],
            'version' => 'V3.1.0.0',
        ];

        $curlParam = [
            'method' => 'POST',
            'uri' => '/api/pay.action',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => urldecode(http_build_query($paramData)),
            'header' => [],
        ];

        $result = $this->curlRequest($curlParam);
        $parseData = json_decode($result, true);

        if (!isset($parseData['stateCode']) || !isset($parseData['msg'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        if ($parseData['stateCode'] !== '00') {
            throw new PaymentConnectionException($parseData['msg'], 180130, $this->getEntryId());
        }

        if (!isset($parseData['qrcodeUrl'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        $parseUrl = parse_url($parseData['qrcodeUrl']);

        $parseUrlValues = [
            'scheme',
            'host',
            'path',
        ];

        foreach ($parseUrlValues as $key) {
            if (!isset($parseUrl[$key])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }
        }

        $params = [];

        if (isset($parseUrl['query'])) {
            parse_str($parseUrl['query'], $params);
        }

        $postUrl = sprintf(
            '%s://%s%s',
            $parseUrl['scheme'],
            $parseUrl['host'],
            $parseUrl['path']
        );

        return [
            'post_url' => $postUrl,
            'params' => $params,
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

        // 如果沒有返回data
        if (!isset($this->options['data'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        // RSA解碼
        $this->options = $this->getRSADecode($this->options['data']);

        // 如果沒有返回簽名要丟例外
        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        $this->payResultVerify();

        $encodeData = [];

        // 組織加密簽名，排除sign(加密簽名)
        foreach ($this->options as $key => $value) {
            if ($key != 'sign') {
                $encodeData[$key] = $value;
            }
        }

        // 針對$encodeData按字母做升序排列
        ksort($encodeData);

        $encodeStr = json_encode($encodeData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $encodeStr .= $this->privateKey;

        if ($this->options['sign'] != strtoupper(md5($encodeStr))) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['payResult'] !== '00') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['orderNum'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['amount'] != round($entry['amount'] * 100)) {
            throw new PaymentException('Order Amount error', 180058);
        }
    }

    /**
     * 線上出款
     */
    public function withdrawPayment()
    {
        // 驗證私鑰
        $this->verifyPrivateKey();

        $this->withdrawVerify();

        // 從內部給定值到參數
        foreach ($this->withdrawRequireMap as $paymentKey => $internalKey) {
            $this->withdrawRequestData[$paymentKey] = $this->options[$internalKey];
        }

        // 額外的參數設定
        $this->withdrawRequestData['bankCode'] = $this->withdrawBankMap[$this->withdrawRequestData['bankCode']];
        $this->withdrawRequestData['amount'] = strval(round($this->withdrawRequestData['amount'] * 100));

        // 設定出款需要的加密串
        $this->withdrawRequestData['sign'] = $this->withdrawEncode();

        $paramData = [
            'data' => urlencode($this->getWithdrawRSAEncode()),
            'merchNo' => $this->options['number'],
            'version' => 'V3.1.0.0',
        ];

        // 出款
        $curlParam = [
            'method' => 'POST',
            'uri' => '/api/remit.action',
            'ip' => $this->options['verify_ip'],
            'host' => trim($this->options['withdraw_host']),
            'param' => urldecode(http_build_query($paramData)),
            'header' => [],
            'timeout' => 60,
        ];

        $result = $this->curlRequest($curlParam);
        $parseData = json_decode($result, true);

        // 對返回結果做檢查
        $this->withdrawResultVerify($parseData);

        // 非00即為出款提交失敗
        if ($parseData['stateCode'] !== '00') {
            throw new PaymentConnectionException($parseData['msg'], 180124, $this->getEntryId());
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

        ksort($encodeData);

        $encodeStr = json_encode($encodeData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $encodeStr .= $this->privateKey;

        return strtoupper(md5($encodeStr));
    }

    /**
     * 支付參數RSA加密
     *
     * @return string
     */
    private function getRSAEncode()
    {
        $encodeData = [];

        foreach ($this->encodeParams as $key) {
            $encodeData[$key] = $this->requestData[$key];
        }

        // 針對$encodeData按字母做升序排列
        ksort($encodeData);
        $encodeData['sign'] = $this->requestData['sign'];

        $encodeStr = json_encode($encodeData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $publicKey = $this->getRsaPublicKey();
        $encParam = '';
        foreach (str_split($encodeStr, 117) as $str) {
            $data = '';
            openssl_public_encrypt($str, $data, $publicKey);
            $encParam .= $data;
        }

        return base64_encode($encParam);
    }

    /**
     * 回調參數RSA解密
     *
     * @param string $response
     * @return array
     */
    private function getRSADecode($response)
    {
        // 先base64解碼
        $encodeStr = base64_decode(rawurldecode(urlencode($response)));

        $privateKey = $this->getRsaPrivateKey();

        // 待解密串長度大於128字元需分段解密，每128字元為一段，解密後再按照順序拼接成字串
        $dataStr = '';
        foreach (str_split($encodeStr, 128) as $chunk) {
            $decryptData = '';
            openssl_private_decrypt($chunk, $decryptData, $privateKey);
            $dataStr .= $decryptData;
        }

        return json_decode($dataStr, true);
    }

    /**
     * 出款時的加密
     *
     * @return string
     */
    protected function withdrawEncode()
    {
        $encodeData = [];

        // 加密設定
        foreach ($this->withdrawEncodeParams as $index) {
            $encodeData[$index] = $this->withdrawRequestData[$index];
        }

        // 針對$encodeData按字母做升序排列
        ksort($encodeData);

        // 額外的加密設定
        $encodeStr = json_encode($encodeData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $encodeStr .= $this->privateKey;

        return strtoupper(md5($encodeStr));
    }

    /**
     * 支付參數RSA加密
     *
     * @return string
     */
    private function getWithdrawRSAEncode()
    {
        $encodeData = [];

        foreach ($this->withdrawEncodeParams as $key) {
            $encodeData[$key] = $this->withdrawRequestData[$key];
        }

        // 針對$encodeData按字母做升序排列
        ksort($encodeData);
        $encodeData['sign'] = $this->withdrawRequestData['sign'];

        $encodeStr = json_encode($encodeData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $publicKey = $this->getRsaPublicKey();
        $encParam = '';
        foreach (str_split($encodeStr, 117) as $str) {
            $data = '';
            openssl_public_encrypt($str, $data, $publicKey);
            $encParam .= $data;
        }

        return base64_encode($encParam);
    }
}

<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 信譽支付
 */
class XinYuZhiFu extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'version' => '1.0', // 版本號
        'charset' => 'UTF-8', // 字符集
        'sign_type' => 'MD5', // 簽名方式
        'mch_id' => '', // 商號
        'out_trade_no' => '', // 訂單號
        'bank_id' => '', // 銀行代號
        'body' => '', // 商品描述
        'total_fee' => '', // 金額，單位：分
        'mch_create_ip' => '', // 支付IP
        'notify_url' => '', // 異步通知網址，長度最長 255
        'nonce_str' => '', // 隨機字串，長度最長 32
        'sign' => '', // 加密簽名
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'mch_id' => 'number',
        'out_trade_no' => 'orderId',
        'bank_id' => 'paymentVendorId',
        'body' => 'orderId',
        'total_fee' => 'amount',
        'mch_create_ip' => 'ip',
        'notify_url' => 'notify_url',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'version',
        'charset',
        'sign_type',
        'mch_id',
        'out_trade_no',
        'bank_id',
        'body',
        'total_fee',
        'mch_create_ip',
        'notify_url',
        'nonce_str',
    ];

    /**
     * 返回驗證時需要加密的參數
     *     0: 有返回就要加密的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'version' => 1,
        'charset' => 1,
        'sign_type' => 1,
        'status' => 1,
        'message' => 0,
    ];

    /**
     * 取得token時送給支付平台的參數
     *
     * @var array
     */
    private $getTokenParams = [
        'appid' => '', // 應用ID，與商號一致
        'secretid' => '', // 密碼，同接口密鑰
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
        '11' => 'CITIC', // 中信銀行
        '12' => 'CEB', // 光大銀行
        '13' => 'HXB', // 華夏銀行
        '14' => 'GDB', // 廣東發展銀行
        '16' => 'PSBC', // 中國郵政
        '17' => 'BOC', // 中國銀行
        '278' => '/sig/v1/union/quick', // 銀聯在線
        '1088' => '/sig/v1/union/quick', // 銀聯在線_手機支付
        '1090' => '/sig/v1/wx/native', // 微信二維
        '1092' => '/sig/v1/alipay/native', // 支付寶二維
        '1097' => '/sig/v1/wx/wappay', // 微信_手機支付
        '1098' => '/sig/v1/alipay/wap', // 支付寶_手機支付
        '1103' => '/sig/v1/qq/native', // QQ二維
        '1104' => '/sig/v1/qq/wap', // QQ_手機支付
        '1107' => '/sig/v1/jd/native', // 京東二維
        '1111' => '/sig/v1/union/native', // 銀聯二維
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

        // 從內部設值到支付參數
        foreach ($this->requireMap as $paymentKey => $internalKey) {
            $this->requestData[$paymentKey] = $this->options[$internalKey];
        }

        // 帶入未支援的支付方式就噴例外
        if (!array_key_exists($this->requestData['bank_id'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $this->requestData['total_fee'] = round($this->requestData['total_fee'] * 100);
        $this->requestData['nonce_str'] = md5(uniqid(rand(), true));
        $this->requestData['bank_id'] = $this->bankMap[$this->requestData['bank_id']];

        // 網銀uri
        $uri = '/sig/v1/union/net';

        // 二維、手機支付參數設定
        if (in_array($this->options['paymentVendorId'], [1090, 1092, 1097, 1098, 1103, 1104, 1107, 1111])) {
            $uri = $this->requestData['bank_id'];
            unset($this->requestData['bank_id']);
        }

        // 銀聯在線參數設定
        if (in_array($this->options['paymentVendorId'], [278, 1088])) {
            $uri = $this->requestData['bank_id'];
            $this->requestData['bank_id'] = 'onlineBankPay';
        }

        // 設定加密簽名
        $this->requestData['sign'] = $this->encode();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $param = $this->arrayToXml($this->requestData, [], 'xml');
        $param = str_replace('<?xml version="1.0"?>', '', $param);

        // 需要攜帶登入取得的token
        $curlParam = [
            'method' => 'POST',
            'uri' => $uri,
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => $param,
            'header' => [
                'Authorization' => 'Bearer ' . $this->loginToGetToken(),
            ],
        ];

        $result = $this->curlRequest($curlParam);

        // 檢查返回參數
        $parseData = $this->xmlToArray(urlencode($result));

        if (!isset($parseData['status'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        if ($parseData['status'] !== '0' && isset($parseData['message'])) {
            throw new PaymentConnectionException($parseData['message'], 180130, $this->getEntryId());
        }

        if (!isset($parseData['result_code'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        // status為0的時候才會返回result_code，status 和 result_code 都為0時，才會返回提交支付時需要的網址
        if ($parseData['status'] !== '0' || $parseData['result_code'] !== '0') {
            throw new PaymentConnectionException('Pay error', 180130, $this->getEntryId());
        }

        // 二維支付
        if (in_array($this->options['paymentVendorId'], [1090, 1092, 1103, 1107, 1111])) {
            if (!isset($parseData['code_url'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            $html = sprintf('<img src="%s"/>', $parseData['code_url']);
            $this->setHtml($html);

            return [];
        }

        // 網銀、手機提交網址
        if (!isset($parseData['pay_info'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        $urlData = $this->parseUrl($parseData['pay_info']);

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

        // 先驗證平台回傳的必要參數
        if (!isset($this->options['content'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        // 解析xml驗證相關參數
        $this->options = $this->xmlToArray($this->options['content']);

        if (!isset($this->options['status'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['status'] !== '0' && isset($this->options['message'])) {
            throw new PaymentConnectionException($this->options['message'], 180130, $this->getEntryId());
        }

        if (!isset($this->options['result_code'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['result_code'] !== '0' && isset($this->options['err_msg'])) {
            throw new PaymentConnectionException($this->options['err_msg'], 180130, $this->getEntryId());
        }

        // 正常情況為返回參數 status 和 result_code 都為0
        if ($this->options['status'] !== '0' || $this->options['result_code'] !== '0') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        // 驗證返回參數
        $this->payResultVerify();

        $encodeData = [];

        // 組織加密串，以支付平台返回為主
        foreach ($this->options as $paymentKey => $value) {
            // 除了 sign 欄位以外的非空值欄位皆須加密
            if ($paymentKey != 'sign' && $value !== '') {
                $encodeData[$paymentKey] = $value;
            }
        }

        ksort($encodeData);
        $encodeData['key'] = $this->privateKey;
        $encodeStr = urldecode(http_build_query($encodeData));

        // 如果沒有簽名也要丟例外(其他參數都在前面驗證過了，所以不須要驗證)
        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['sign'] !== strtoupper(md5($encodeStr))) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['pay_result'] !== '0') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['out_trade_no'] !== $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['total_fee'] !== trim(round($entry['amount'] * 100))) {
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
            if (array_key_exists($index, $this->requestData)  && trim($this->requestData[$index]) !== '') {
                $encodeData[$index] = $this->requestData[$index];
            }
        }

        ksort($encodeData);
        $encodeData['key'] = $this->privateKey;
        $encodeStr = urldecode(http_build_query($encodeData));

        return strtoupper(md5($encodeStr));
    }

    /**
     * 登入取得token
     *
     * @return string
     */
    private function loginToGetToken()
    {
        // 登入參數設定
        $this->getTokenParams['appid'] = $this->requestData['mch_id'];
        $this->getTokenParams['secretid'] = $this->privateKey;

        // 登入取得token
        $curlParam = [
            'method' => 'POST',
            'uri' => '/auth/access-token',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => urldecode(http_build_query($this->getTokenParams)),
            'header' => [],
        ];

        $result = $this->curlRequest($curlParam);

        // 檢查返回參數
        $parseData = $this->xmlToArray($result);

        if (isset($parseData['status']) && isset($parseData['message'])) {
            throw new PaymentConnectionException($parseData['message'], 180130, $this->getEntryId());
        }

        if (!isset($parseData['token'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        return $parseData['token'];
    }
}

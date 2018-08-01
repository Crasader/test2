<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 核心支付
 */
class HeXinPay extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'account_no' => '', // 商號
        'version' => 'v1.0', // 版本號，固定值
        'method' => '00000012', // 接口代碼，固定值
        'productId' => '01', // 接口編號，網銀01
        'nonce_str' => '', // 隨機字符串，32位元內，存放username方便業主對帳
        'pay_tool' => 'wgzftfb', // 交易服務碼，固定值
        'order_sn' => '', // 商戶訂單號
        'return_url' => '', // 支付成功跳轉網址
        'bankNo' => '', // 銀行代碼
        'channel' => '1', // 渠道類型，網銀專用，1：PC端
        'money' => '', // 支付金額
        'body' => '', // 商品描述，存放username方便業主對帳
        'ex_field' => '', // 擴充字段
        'notify' => '', // 通知網址
        'signature' => '', // MD5簽名
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'account_no' => 'number',
        'nonce_str' => 'username',
        'order_sn' => 'orderId',
        'money' => 'amount',
        'body' => 'username',
        'bankNo' => 'paymentVendorId',
        'notify' => 'notify_url',
        'return_url' => 'notify_url',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'account_no',
        'method',
        'productId',
        'nonce_str',
        'pay_tool',
        'order_sn',
        'money',
        'body',
        'bankCode',
        'notify',
        'return_url',
        'signature',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'res_code' => 1,
        'res_msg' => 1,
        'nonce_str' => 1,
        'status' => 1,
        'order_sn' => 1,
        'money' => 1,
    ];

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        1 => '1001', //工商銀行
        3 => '1002', // 中國農業銀行
        4 => '1004', // 中國建設銀行
        5 => '1012', // 招商銀行
        6 => '1010', // 中國民生銀行
        9 => '1016', // 北京銀行
        11 => '1007', // 中信銀行
        12 => '1008', // 中國光大銀行
        14 => '1017', // 廣東發展銀行
        15 => '1011', // 深圳平安銀行
        16 => '1006', // 中國郵政
        17 => '1003', // 中國銀行
        1090 => '02', // 微信二維
        1103 => '06', // QQ二維
        1104 => '09', // QQ手機支付

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

        // 檢查銀行代碼是否支援
        if (!array_key_exists($this->requestData['bankNo'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $this->requestData['bankNo'] = $this->bankMap[$this->requestData['bankNo']];

        // 二維支付
        if (in_array($this->options['paymentVendorId'], [1090, 1103])) {
            return $this->getQrcodePayData();
        }

        // 手機支付
        if ($this->options['paymentVendorId'] == 1104) {
            return $this->getPhonePayData();
        }

         return $this->getBankPayData();
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

        // 組織加密串
        $encodeData = [];
        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options)) {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        ksort($encodeData);

        $encodeStr = urldecode(http_build_query($encodeData));
        $encodeStr .= $this->privateKey;

        // 沒有signature就要丟例外
        if (!isset($this->options['signature'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if (strcasecmp($this->options['signature'], md5($encodeStr)) != 0) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['status'] != '1') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['order_sn'] != $entry['id']) {
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

        // 組織加密簽名
        foreach ($this->requestData as $key => $value) {
            if (trim($value) != '') {
                $encodeData[$key] = $value;
            }
        }

        // 針對$encodeData按字母做升序排列
        ksort($encodeData);

        // 依key1=value1&key2=value2&...&keyN=valueN之後做md5
        $encodeStr = urldecode(http_build_query($encodeData));
        $encodeStr .= $this->privateKey;

        return md5($encodeStr);
    }

    /**
     * 取得支付對外返回參數
     *
     * @return array
     */
    private function getPayReturnData()
    {
        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'method' => 'POST',
            'uri' => '/api/core.php',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => http_build_query($this->requestData),
            'header' => [],
        ];

        $result = $this->curlRequest($curlParam);
        $parseData = json_decode($result, true);

        return $parseData;
    }

    /**
     * 驗證支付對外返回是否成功
     *
     * @param array $parseData
     */
    private function verifyPayReturn($parseData)
    {
        if (!isset($parseData['res_code']) || !isset($parseData['res_msg'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        if ($parseData['res_code'] !== 'P000') {
            throw new PaymentConnectionException($parseData['res_msg'], 180130, $this->getEntryId());
        }
    }

    /**
     * 取得二維支付參數
     *
     * @return array
     */
    private function getQrcodePayData()
    {
        // 額外的參數設定
        $this->requestData['method'] = '00000003';
        $this->requestData['productId'] = $this->requestData['bankNo'];
        unset($this->requestData['bankNo']);
        unset($this->requestData['return_url']);
        unset($this->requestData['channel']);

        // 微信二維
        if ($this->options['paymentVendorId'] == 1090) {
            $this->requestData['pay_tool'] = 'wxzfxf';
        }

        // QQ二維
        if ($this->options['paymentVendorId'] == 1103) {
            $this->requestData['pay_tool'] = 'qqsmxf';
        }

        // 設定加密簽名
        $this->requestData['signature'] = $this->encode();

        // 取得支付對外返回參數
        $parseData = $this->getPayReturnData();

        // 驗證支付對外返回是否成功
        $this->verifyPayReturn($parseData);

        if (!isset($parseData['codeUrl'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        $this->setQrcode($parseData['codeUrl']);

        return [];
    }

    /**
     * 取得手機支付參數
     *
     * @return array
     */
    private function getPhonePayData()
    {
        // 額外的參數設定
        $this->requestData['method'] = '00000003';
        $this->requestData['productId'] = $this->requestData['bankNo'];
        unset($this->requestData['bankNo']);
        unset($this->requestData['return_url']);
        unset($this->requestData['channel']);

        // QQ手機
        $this->requestData['pay_tool'] = 'qqwapxf';

        // 設定加密簽名
        $this->requestData['signature'] = $this->encode();

        return $this->requestData;
    }

    /**
     * 取得網銀支付參數
     *
     * @return array
     */
    private function getBankPayData()
    {
        // 設定加密簽名
        $this->requestData['signature'] = $this->encode();

        // 取得支付對外返回參數
        $parseData = $this->getPayReturnData();

        // 驗證支付對外返回是否成功
        $this->verifyPayReturn($parseData);

        if (!isset($parseData['payUrl'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        $parseUrl = parse_url($parseData['payUrl']);

        $parseUrlValues = [
            'scheme',
            'host',
            'path',
            'query',
        ];

        foreach ($parseUrlValues as $key) {
            if (!isset($parseUrl[$key])) {
                throw new PaymentException('Get pay parameters failed', 180128);
            }
        }

        $param = [];
        parse_str($parseUrl['query'], $param);

        if (!isset($param['cipher_data'])) {
            throw new PaymentException('Get pay parameters failed', 180128);
        }

        $postUrl = sprintf(
            '%s://%s%s',
            $parseUrl['scheme'],
            $parseUrl['host'],
            $parseUrl['path']
        );

        return [
            'post_url' => $postUrl,
            'params' => [
                'cipher_data' => $param['cipher_data'],
            ],
        ];
    }
}

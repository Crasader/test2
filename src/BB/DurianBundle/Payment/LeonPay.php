<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * Leon Pay
 */
class LeonPay extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'merchant' => '', // 商號
        'tradeNo' => '', // 商戶訂單號
        'bankSwiftCode' => '', // 銀行代碼
        'amount' => '', // 金額
        'curType' => 'CNY', // 幣別，固定值
        'userId' => '', // 用戶代號，純數字，帶入merchantId
        'deviceType' => '0', // 提交裝置，0:WEB、1:手機
        'ip' => '', // IP
        'notifyUrl' => '', // 異步通知地址
        'returnUrl' => '', // 頁面回調網址(網銀)
        'reqTime' => '', // 交易時間
        'sign' => '', // 簽名
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'tradeNo' => 'orderId',
        'merchant' => 'number',
        'amount' => 'amount',
        'bankSwiftCode' => 'paymentVendorId',
        'userId' => 'merchantId',
        'notifyUrl' => 'notify_url',
        'returnUrl' => 'notify_url',
        'ip' => 'ip',
        'reqTime' => 'orderCreateDate',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'merchant',
        'tradeNo',
        'bankSwiftCode',
        'amount',
        'curType',
        'userId',
        'deviceType',
        'ip',
        'notifyUrl',
        'returnUrl',
        'reqTime',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'merchant' => 1,
        'tradeNo' => 1,
        'ordernumber' => 1,
        'payType' => 1,
        'amount' => 1,
        'curType' => 1,
        'status' => 1,
        'tradeTime' => 1,
    ];

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        '1' => 'ICBK', // 中國工商銀行
        '2' => 'COMM', // 交通銀行
        '3' => 'ABOC', // 中國農業銀行
        '4' => 'PCBC', // 中國建設銀行
        '5' => 'CMBC', // 招商銀行
        '6' => 'MSBC', // 中國民生銀行
        '8' => 'SPDB', // 上海浦東發展銀行
        '9' => 'BJCN', // 北京銀行
        '10' => 'FJIB', // 興業銀行
        '11' => 'CIBK', // 中信銀行
        '12' => 'EVER', // 中國光大銀行
        '13' => 'HXBK', // 華夏銀行
        '14' => 'GDBK', // 廣東發展銀行
        '15' => 'SZDB', // 平安银行
        '16' => 'PSBC', // 中國郵政
        '17' => 'BKCH', // 中國銀行
        '19' => 'BOSH', //上海銀行
        '1090' => '1', // 微信_二維
        '1092' => '2', // 支付寶_二維
        '1103' => '4', // QQ_二維
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
        if (!array_key_exists($this->requestData['bankSwiftCode'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $this->requestData['amount'] = sprintf('%.2f', $this->requestData['amount']);
        $this->requestData['bankSwiftCode'] = $this->bankMap[$this->requestData['bankSwiftCode']];

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        // 二維支付
        if (in_array($this->options['paymentVendorId'], [1090, 1092, 1103])) {
            $this->requestData['payType'] = $this->requestData['bankSwiftCode'];
            $this->encodeParams[] = 'payType';

            $removeParams = [
                'bankSwiftCode',
                'returnUrl',
            ];

            // 移除二維不需要的參數
            foreach ($removeParams as $removeParam) {
                unset($this->requestData[$removeParam]);
                $encodeParamsKey = array_search($removeParam, $this->encodeParams);
                unset($this->encodeParams[$encodeParamsKey]);
            }

            // 設定支付平台需要的加密串
            $this->requestData['sign'] = $this->encode();

            $curlParam = [
                'method' => 'POST',
                'uri' => '/pay/qrcode/v1',
                'ip' => $this->options['verify_ip'],
                'host' => $this->options['verify_url'],
                'param' => json_encode($this->requestData),
                'header' => ['Content-Type' => 'application/json'],
            ];
            $result = $this->curlRequest($curlParam);
            $parseData = json_decode($result, true);

            if (!isset($parseData['code']) || !isset($parseData['message'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            if ($parseData['code'] !== '0000') {
                throw new PaymentConnectionException($parseData['message'], 180130, $this->getEntryId());
            }

            if (!isset($parseData['data']['qrcode'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            $this->setQrcode($parseData['data']['qrcode']);

            return [];
        }

        // 設定網銀需要的加密串
        $this->requestData['sign'] = $this->encode();

        $curlParam = [
            'method' => 'POST',
            'uri' => '/pay/bank/v1',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => json_encode($this->requestData),
            'header' => ['Content-Type' => 'application/json'],
        ];
        $result= $this->curlRequest($curlParam);

        $getUrl = [];
        preg_match('/action="([^"]+)/', $result, $getUrl);

        if (!isset($getUrl[1])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        $out = [];
        $pattern = '/<input.*name="(.*)".*value+="([^"]*)"/U';
        preg_match_all($pattern, $result, $out);

        return [
            'post_url' => $getUrl[1],
            'params' => array_combine($out[1], $out[2])
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

        $encodeData = [];

        // 組織加密串
        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options)) {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }
        ksort($encodeData);
        $encodeData['key'] = $this->privateKey;
        $encodeStr = urldecode(http_build_query($encodeData));

        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['sign'] != md5($encodeStr)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['status'] == '0') {
            throw new PaymentConnectionException('Order Processing', 180059, $this->getEntryId());
        }

        if ($this->options['status'] !== '1') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['tradeNo'] != $entry['id']) {
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

        foreach ($this->encodeParams as $index) {
            $encodeData[$index] = $this->requestData[$index];
        }

        ksort($encodeData);
        $encodeData['key'] = $this->privateKey;
        $encodeStr = urldecode(http_build_query($encodeData));

        return md5($encodeStr);
    }
}

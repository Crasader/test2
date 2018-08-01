<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 *　金海哲
 */
class JingHaiZhe extends PaymentBase
{
    /**
     * 支付提交參數
     *
     * @var array
     */
    protected $requestData = [
        'merchantNo' => '', // 商戶號
        'requestNo' => '', // 訂單號
        'amount' => '', // 金額，單位:分
        'payMethod' => '6002', // 業務編號，預設網銀
        'pageUrl' => '', // 同步返回地址
        'backUrl' => '', // 異步返回地址
        'payDate' => '', // 請求日期，時間戳
        'agencyCode' => '', // 分支機構號
        'remark1' => '', // 備註1，必填
        'remark2' => '', // 備註2，必填
        'remark3' => '', // 備註3，必填
        'signature' => '', // 簽名
        'bankType' => '', // 銀行行別, 網銀用參數
        'bankAccountType' => '11', // 帳戶類型, 網銀用參數, 11:借記卡
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'merchantNo' => 'number',
        'requestNo' => 'orderId',
        'amount' => 'amount',
        'pageUrl' => 'notify_url',
        'backUrl' => 'notify_url',
        'payDate' => 'orderCreateDate',
        'remark1' => 'orderId',
        'remark2' => 'orderId',
        'remark3' => 'orderId',
        'bankType' => 'paymentVendorId',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'merchantNo',
        'requestNo',
        'amount',
        'pageUrl',
        'backUrl',
        'payDate',
        'agencyCode',
        'remark1',
        'remark2',
        'remark3',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'ret' => 1,
        'msg' => 1,
    ];

    /**
     * 返回時要檢查的參數
     *
     * @var array
     */
    private $returnParams = [
        'code',
        'msg',
        'money',
        'no',
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
        '1' => '1021000', // 工商銀行
        '2' => '3012900', // 交通銀行
        '3' => '1031000', // 農業銀行
        '4' => '1051000', // 建設銀行
        '5' => '3085840', // 招商銀行
        '6' => '3051000', // 民生銀行總行
        '8' => '3102900', // 上海浦東發展銀行
        '9' => '3131000', // 北京銀行
        '10' => '3093910', // 興業銀行
        '11' => '3021000', // 中信銀行
        '12' => '3031000', // 光大銀行
        '13' => 'HXBANK', //  華夏銀行
        '14' => '3065810', // 廣東發展銀行
        '15' => '3071000', // 深圳平安銀行
        '16' => '4031000', // 中國郵政
        '19' => 'SHBANK', //  上海銀行
        '222' => '3133320', // 寧波銀行
        '223' => '5021000', // 東亞銀行
        '226' => '3133010', // 南京銀行
        '228' => '3222900', // 上海農村商業銀行
        '1092' => '6003', // 支付寶_二維
        '1103' => '6011', // QQ_二維
        '1104' => '6016', // QQ_手機支付
        '1107' => '6010', // 京東_二維
        '1108' => '6018', // 京東_手機支付
        '1111' => '6012', // 銀聯錢包_二維
    ];

    /**
     * 回傳支付時要給支付平台驗證的資料
     *
     * @return array
     */
    public function getVerifyData()
    {
        $this->payVerify();

        // 從內部設值到支付參數
        foreach ($this->requireMap as $paymentKey => $internalKey) {
            $this->requestData[$paymentKey] = $this->options[$internalKey];
        }

        // 帶入未支援的銀行就噴例外
        if (!array_key_exists($this->requestData['bankType'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $this->requestData['amount'] = round($this->requestData['amount'] * 100);
        $this->requestData['payDate'] = strtotime($this->requestData['payDate']);
        $this->requestData['bankType'] = $this->bankMap[$this->requestData['bankType']];

        // 非網銀調整額外設定
        if (in_array($this->options['paymentVendorId'], [1092, 1103, 1104, 1107, 1108, 1111])) {
            $this->requestData['payMethod'] = $this->requestData['bankType'];
            unset($this->requestData['bankType']);
            unset($this->requestData['bankAccountType']);
        }

        // 設定加密簽名
        $this->requestData['signature'] = $this->encode();

        // 二維、QQ手機需要對外
        if (in_array($this->options['paymentVendorId'], [1092, 1103, 1104, 1107, 1111])) {
            $curlParam = [
                'method' => 'POST',
                'uri' => '/ownPay/pay',
                'ip' => $this->options['verify_ip'],
                'host' => $this->options['verify_url'],
                'param' => http_build_query($this->requestData),
                'header' => [],
            ];

            $result = $this->curlRequest($curlParam);
            $parseData = json_decode($result, true);

            if (isset($parseData['msg'])) {
                throw new PaymentConnectionException($parseData['msg'], 180130, $this->getEntryId());
            }

            if (!isset($parseData['backQrCodeUrl'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            // QQ手機支付
            if ($this->options['paymentVendorId'] == 1104) {
                $parsedUrl = $this->parseUrl($parseData['backQrCodeUrl']);
                $this->payMethod = 'GET';

                return [
                    'post_url' => $parsedUrl['url'],
                    'params' => $parsedUrl['params'],
                ];
            }

            $this->setQrcode($parseData['backQrCodeUrl']);

            return [];
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
        $this->payResultVerify();

        $params = array_merge(json_decode($this->options['ret'], true), json_decode($this->options['msg'], true));

        // 檢查剩餘返回參數
        foreach ($this->returnParams as $require) {
            if (!isset($params[$require])) {
                throw new PaymentException('No return parameter specified', 180137);
            }
        }

        $encodeData = [];

        // 組織加密串
        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options)) {
                $encodeData[] = $this->options[$paymentKey];
            }
        }
        $encodeStr = implode('|', $encodeData);

        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        $sign = base64_decode($this->options['sign']);

        if (!openssl_verify($encodeStr, $sign, $this->getRsaPublicKey())) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($params['msg'] != 'SUCCESS' || $params['code'] != '1000') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($params['no'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($params['money'] != round($entry['amount'] * 100)) {
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
            $encodeData[] = $this->requestData[$index];
        }

        $encodeStr = implode('|', $encodeData);

        $sign = '';
        if (!openssl_sign($encodeStr, $sign, $this->getRsaPrivateKey())) {
            throw new PaymentException('Generate signature failure', 180144);
        }
        $sign = base64_encode($sign);

        return $sign;
    }
}

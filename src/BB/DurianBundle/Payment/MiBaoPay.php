<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 米寶付
 */
class MiBaoPay extends PaymentBase
{
    /**
     * 支付提交參數
     *
     * @var array
     */
    protected $requestData = [
        'merchantCode' => '', // 商戶號
        'outOrderId' => '', // 訂單號
        'totalAmount' => '', // 支付金額，單位分，需整數
        'goodsName' => '', // 產品名稱，非必填
        'goodsExplain' => '', // 產品描述，非必填
        'orderCreateTime' => '', // 訂單成立時間，格式:YmdHis
        'merUrl' => '', // 同步通知網址
        'noticeUrl' => '', // 異步通知網址
        'bankCode' => '', // 支付銀行代碼
        'bankCardType' => '800', // 支付銀行卡類型，固定值
        'ext' => '', // 擴展字段，非必填
        'sign' => '', // 簽名
    ];

    /**
     * 支付提交參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'merchantCode' => 'number',
        'outOrderId' => 'orderId',
        'totalAmount' => 'amount',
        'orderCreateTime' => 'orderCreateDate',
        'merUrl' => 'notify_url',
        'noticeUrl' => 'notify_url',
        'bankCode' => 'paymentVendorId',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'merchantCode',
        'outOrderId',
        'totalAmount',
        'goodsName',
        'goodsExplain',
        'orderCreateTime',
        'merUrl',
        'noticeUrl',
        'bankCode',
        'bankCardType',
        'ext',
        'deviceNo',
        'amount',
        'goodsMark',
        'arrivalType',
        'payChannel',
        'ip',
        'payType',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'merchantCode' => 1,
        'instructCode' => 1,
        'outOrderId' => 1,
        'transTime' => 1,
        'totalAmount' => 1,
        'ext' => 1,
    ];

    /**
     * 應答機制訊息
     *
     * @var string
     */
    protected $msg = "{'code':'00'}";

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        '1' => 'ICBC', // 工商銀行
        '2' => 'BCM', // 交通銀行
        '3' => 'ABC', // 農業銀行
        '4' => 'CCB', // 建設銀行
        '5' => 'CMB', // 招商銀行
        '6' => 'CMBC', // 民生銀行
        '8' => 'SPDB', // 上海浦東發展銀行
        '9' => 'BCCB', // 北京銀行
        '10' => 'CIB', // 興業銀行
        '11' => 'CITIC', // 中信銀行
        '12' => 'CEB', // 光大銀行
        '13' => 'HXB', // 華夏銀行
        '14' => 'GDB', // 廣發銀行
        '15' => 'PAB', // 平安銀行
        '16' => 'PSBC', // 中國郵政
        '17' => 'BOC', // 中國銀行
        '1090' => '12', // 微信_二維
        '1092' => '13', // 支付寶_二維
        '1103' => '14', // QQ_二維
        '1104' => '19', // QQ_手機支付
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
        if (!array_key_exists($this->requestData['bankCode'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        $this->requestData['totalAmount'] = round($this->requestData['totalAmount'] * 100);
        $date = new \DateTime($this->requestData['orderCreateTime']);
        $this->requestData['orderCreateTime'] = $date->format('YmdHis');
        $this->requestData['bankCode'] = $this->bankMap[$this->requestData['bankCode']];

        // 二維支付
        if (in_array($this->options['paymentVendorId'], [1090, 1092, 1103])) {
            if (!isset($this->options['ip']) || trim($this->options['ip']) === '') {
                throw new PaymentException('No pay parameter specified', 180145);
            }

            $this->requestData['deviceNo'] = '';
            $this->requestData['amount'] = $this->requestData['totalAmount'];
            $this->requestData['goodsMark'] = '';
            $this->requestData['arrivalType'] = '1100';
            $this->requestData['payChannel'] = $this->requestData['bankCode'];
            $this->requestData['ip'] = $this->options['ip'];

            // 移除二維支付不需傳遞的參數
            unset($this->requestData['totalAmount']);
            unset($this->requestData['merUrl']);
            unset($this->requestData['bankCode']);
            unset($this->requestData['bankCardType']);

            // 設定支付平台需要的加密串
            $this->requestData['sign'] = $this->encode();

            if (trim($this->options['verify_url']) == '') {
                throw new PaymentException('No verify_url specified', 180140);
            }

            $curlParam = [
                'method' => 'POST',
                'uri' => '/scan/pay',
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

            if ($parseData['code'] !== '00') {
                throw new PaymentConnectionException($parseData['msg'], 180130, $this->getEntryId());
            }

            if (!isset($parseData['data']['url'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            $this->setQrcode($parseData['data']['url']);

            return [];
        }

        // QQ手機支付
        if ($this->options['paymentVendorId'] == 1104) {
            $this->requestData['payType'] = $this->requestData['bankCode'];
            $this->requestData['arrivalType'] = '1100';

            // 移除QQ手機支付不需傳遞的參數
            unset($this->requestData['bankCode']);
            unset($this->requestData['bankCardType']);

            // 設定支付平台需要的加密串
            $this->requestData['sign'] = $this->encode();

            // 調整QQ手機支付提交網址
            $postUrl = $this->options['postUrl'] . '/wap/pay';

            return [
                'post_url' => $postUrl,
                'params' => $this->requestData,
            ];
        }

        // 設定支付平台需要的加密串
        $this->requestData['sign'] = $this->encode();

        // 調整網銀提交網址
        $postUrl = $this->options['postUrl'] . '/ebank/pay';

        return [
            'post_url' => $postUrl,
            'params' => $this->requestData,
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

        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options)) {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        ksort($encodeData);

        $encodeData['KEY'] = $this->privateKey;

        $encodeStr = urldecode(http_build_query($encodeData));

        // 沒有返回sign就要丟例外
        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['sign'] != md5($encodeStr)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['outOrderId'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['totalAmount'] != round($entry['amount'] * 100)) {
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
            if (isset($this->requestData[$key])) {
                $encodeData[$key] = $this->requestData[$key];
            }
        }

        // 針對$encodeData按字母做升序排列
        ksort($encodeData);

        $encodeData['KEY'] = $this->privateKey;

        $encodeStr = urldecode(http_build_query($encodeData));

        return md5($encodeStr);
    }
}

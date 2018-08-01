<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 全銀支付
 */
class QuanYinPay extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'payKey' => '', // 商戶號
        'productName' => '', // 商品名，設定username方便業主比對
        'orderNo' => '', // 訂單編號
        'orderPrice' => '', // 訂單金額，單位:元，精確到小數點第二位
        'payWayCode' => 'ZITOPAY', // 支付方式編碼，固定值
        'payTypeCode' => 'ZITOPAY_BANK_SCAN', // 支付類型編碼
        'orderIp' => '', // 商戶IP，非必填
        'orderDate' => '', // 訂單日期，格式:YMD
        'orderTime' => '', // 訂單具體時間，格式:YMDHIS
        'returnUrl' => '', // 同步通知網址
        'notifyUrl' => '', // 異步通知網址
        'orderPeriod' => '120', // 訂單有效期限，單位:分鐘
        'remark' => '', // 訂單備註，非必填
        'field1' => '', // 預留字段，非必填
        'field2' => '', // 預留字段，非必填
        'field3' => '', // 預留字段，非必填
        'field4' => '', // 預留字段，非必填
        'field5' => '', // 預留字段，非必填
        'sign' => '', // 簽名
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'payKey' => 'number',
        'productName' => 'username',
        'orderNo' => 'orderId',
        'orderPrice' => 'amount',
        'field5' => 'paymentVendorId',
        'orderDate' => 'orderCreateDate',
        'orderTime' => 'orderCreateDate',
        'returnUrl' => 'notify_url',
        'notifyUrl' => 'notify_url',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'payKey',
        'productName',
        'orderNo',
        'orderPrice',
        'payWayCode',
        'payTypeCode',
        'orderDate',
        'orderTime',
        'returnUrl',
        'notifyUrl',
        'orderPeriod',
        'field5',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'payKey' => 1,
        'productName' => 1,
        'orderNo' => 1,
        'orderPrice' => 1,
        'payWayCode' => 1,
        'payPayCode' => 1,
        'orderDate' => 1,
        'orderTime' => 1,
        'remark' => 0,
        'trxNo' => 1,
        'field1' => 0,
        'field2' => 0,
        'field3' => 0,
        'field4' => 0,
        'field5' => 0,
        'tradeStatus' => 1,
    ];

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        '1' => 'ICBC', // 工商銀行
        '2' => 'BOCM', // 交通銀行
        '3' => 'ABC', // 農業銀行
        '4' => 'CCB', // 建設銀行
        '5' => 'CMB', // 招商銀行
        '6' => 'CMBC', // 民生銀行
        '9' => 'BCCB', // 北京銀行
        '11' => 'CITIC', // 中信銀行
        '12' => 'CEB', // 光大銀行
        '13' => 'HXB', // 華夏銀行
        '14' => 'CGB', // 廣發銀行
        '16' => 'PSBC', // 中國郵政
        '17' => 'BOC', // 中國銀行
        '278' => 'OPSPAY_QUICKPAY_PC', // 銀聯在線
        '1088' => 'BEWPAY_QUICKPAY', // 銀聯在線_手機支付
        '1090' => 'ZITOPAY_WX_SCAN', // 微信_二維
        '1092' => 'ZITOPAY_ALI_SCAN', // 支付寶_二維
        '1100' => 'ZITOPAY_BANK_SCAN', // 手機收銀檯
        '1102' => 'ZITOPAY_BANK_SCAN', // 網銀收銀檯
        '1103' => 'ZITOPAY_QQ_SCAN', // QQ_二維
        '1107' => 'JPAY_JDPAY', // 京東錢包_二維
        '1111' => 'MOBPAY_UNION_SCAN', // 銀聯_二維
        '1115' => 'REASON_PAY01_WX_INPUT', // 微信_條碼
        '1118' => 'REASON_PAY01_WX_INPUT', // 微信_條碼手機支付
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
        if (!array_key_exists($this->requestData['field5'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $this->requestData['orderPrice'] = sprintf('%.2f', $this->requestData['orderPrice']);
        $this->requestData['field5'] = $this->bankMap[$this->requestData['field5']];
        $date = new \DateTime($this->options['orderCreateDate']);
        $this->requestData['orderDate'] = $date->format('Ymd');
        $this->requestData['orderTime'] = $date->format('YmdHis');

        $vendor = [278, 1088, 1090, 1092, 1100, 1102, 1103, 1107, 1111, 1115, 1118];

        // 調整銀聯在線、條碼、手機支付提交參數
        if (in_array($this->options['paymentVendorId'], $vendor)) {
            $this->requestData['payTypeCode'] = $this->requestData['field5'];
            unset($this->requestData['field5']);
        }

        $this->requestData['sign'] = $this->encode();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'method' => 'POST',
            'uri' => '/rb-pay-web-gateway/scanPay/initPayIntf',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => urldecode(http_build_query($this->requestData)),
            'header' => ['Port' => 8050],
        ];

        $result = $this->curlRequest($curlParam);
        $parseData = json_decode($result, true);

        if (!isset($parseData['result']) || !isset($parseData['msg'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        if ($parseData['result'] != 'success') {
            throw new PaymentConnectionException($parseData['msg'], 180130, $this->getEntryId());
        }

        if (!isset($parseData['code_url'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        // 二維
        if (in_array($this->options['paymentVendorId'], [1090, 1092, 1103, 1107, 1111])) {
            $this->setQrcode($parseData['code_url']);

            return [];
        }

        // 解析跳轉網址
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

        $encodeData = [];

        // 組織加密串
        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options)) {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        ksort($encodeData);

        $encodeData['paySecret'] = $this->privateKey;

        $encodeStr = urldecode(http_build_query($encodeData));

        if ($this->options['tradeStatus'] !== 'SUCCESS') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['sign'] != strtoupper(md5($encodeStr))) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['orderNo'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['orderPrice'] != $entry['amount']) {
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
        foreach ($this->encodeParams as $index) {
            if (array_key_exists($index, $this->requestData)) {
                $encodeData[$index] = $this->requestData[$index];
            }
        }

        ksort($encodeData);

        $encodeData['paySecret'] = $this->privateKey;

        $encodeStr = urldecode(http_build_query($encodeData));

        return strtoupper(md5($encodeStr));
    }
}

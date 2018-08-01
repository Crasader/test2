<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 安快支付
 */
class AnKuaiPay extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'partner' => '', // 商號
        'banktype' => '', // 銀行類型
        'paymoney' => '', // 金額，單位:元
        'ordernumber' => '', // 商戶訂單號
        'callbackurl' => '', // 異步通知網址
        'hrefbackurl' => '', // 同步通知網址，非必填
        'attach' => '', // 備註信息，非必填
        'isshow' => '1', // 是否顯示收銀台，默認:1，二維掃碼使用:0，返回Qrcode網址
        'sign' => '', // MD5簽名
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'partner' => 'number',
        'ordernumber' => 'orderId',
        'paymoney' => 'amount',
        'callbackurl' => 'notify_url',
        'banktype' => 'paymentVendorId',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'partner',
        'banktype',
        'paymoney',
        'ordernumber',
        'callbackurl',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'partner' => 1,
        'ordernumber' => 1,
        'orderstatus' => 1,
        'paymoney' => 1,
    ];

    /**
     * 應答機制訊息
     *
     * @var string
     */
    protected $msg = 'ok';

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        '1' => 'ICBC', // 中國工商銀行
        '2' => 'BOCO', // 交通銀行
        '3' => 'ABC', // 中國農業銀行
        '4' => 'CCB', // 中國建設銀行
        '5' => 'CMB', // 招商銀行
        '6' => 'CMBC', // 中國民生銀行
        '8' => 'SPDB', // 上海浦東發展銀行
        '9' => 'BCCB', // 北京銀行
        '10' => 'CIB', // 興業銀行
        '11' => 'CTTIC', // 中信銀行
        '12' => 'CEB', // 中國光大銀行
        '13' => 'HXB', // 華夏銀行
        '14' => 'GDB', // 廣東發展銀行
        '15' => 'PINGANBANK', // 平安銀行
        '16' => 'PSBS', // 中國郵政
        '17' => 'BOC', // 中國銀行
        '19' => 'SHB', // 上海銀行
        '217' => 'CBHB', // 渤海銀行
        '222' => 'NBCB', // 寧波銀行
        '223' => 'HKBEA', // 東亞銀行
        '226' => 'NJCB', // 南京銀行
        '228' => 'SRCB', // 上海市農村商業銀行
        '233' => 'CZB', // 浙江稠州商業銀行
        '234' => 'BJRCB', // 北京農村商業銀行
        '1090' => 'WEIXIN', // 微信_二維
        '1092' => 'ALIPAY', // 支付寶_二維
        '1098' => 'ALIPAYWAP', // 支付寶_手機支付
        '1103' => 'QQ', // QQ_二維
        '1104' => 'QQWAP', // QQ_手機支付
        '1107' => 'JD', // 京東錢包_二維
        '1108' => 'JDWAP', // 京東錢包_手機支付
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
        if (!array_key_exists($this->requestData['banktype'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $this->requestData['banktype'] = $this->bankMap[$this->requestData['banktype']];
        $this->requestData['paymoney'] = sprintf('%.2f', $this->requestData['paymoney']);

        // 二維支付
        if (in_array($this->options['paymentVendorId'], [1092, 1103])) {
            return $this->getQrcodePayData();
        }

        // 設定支付平台需要的加密串
        $this->requestData['sign'] = $this->encode();

        // 檢查是否有postUrl(支付平台提交的url)
        if (trim($this->options['postUrl']) == '') {
            throw new PaymentException('No pay parameter specified', 180145);
        }

        $params = urldecode(http_build_query($this->requestData));
        $this->requestData['act_url'] = $this->options['postUrl'] . '?' . $params;

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

        // 組織加密串
        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options)) {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        $encodeStr = urldecode(http_build_query($encodeData));
        $encodeStr .= $this->privateKey;

        // 沒有sign就要丟例外
        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['sign'] != md5($encodeStr)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['orderstatus'] != '1') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['ordernumber'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['paymoney'] != $entry['amount']) {
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
            $encodeData[$index] = $this->requestData[$index];
        }

        $encodeStr = urldecode(http_build_query($encodeData));
        $encodeStr .= $this->privateKey;

        return md5($encodeStr);
    }

    /**
     * 取得二維支付參數
     *
     * @return array
     */
    private function getQrcodePayData()
    {
        // 修改二維支付額外提交參數
        $this->requestData['isshow'] = '0';

        // 設定支付平台需要的加密串
        $this->requestData['sign'] = $this->encode();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'method' => 'GET',
            'uri' => '/PayBank.aspx',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => urldecode(http_build_query($this->requestData)),
            'header' => [],
        ];

        $result = $this->curlRequest($curlParam);

        $data = parse_url($result, PHP_URL_QUERY);

        if (!$data) {
            throw new PaymentConnectionException($result, 180130, $this->getEntryId());
        }

        // 支付寶_二維
        if ($this->options['paymentVendorId'] == 1092) {
            $parseData = [];

            // 回傳格式為query string，因此直接用parse_str來做分解
            parse_str($data, $parseData);

            if (!isset($parseData['data']) || $parseData['data'] == '') {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            $this->setQrcode($parseData['data']);
        }

        // QQ_二維
        if ($this->options['paymentVendorId'] == 1103) {
            // 回傳網址為Qrcode圖片網址，因此src帶入圖片網址，直接印出Qrcode
            $html = sprintf('<img src="%s"/>', $result);

            $this->setHtml($html);
        }

        return [];
    }
}

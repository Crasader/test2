<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 卡誠直連
 */
class KcPayScan extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'partner' => '', // 商戶ID
        'banktype' => '', // 銀行類型
        'paymoney' => '', // 金額，單位元
        'ordernumber' => '', // 商戶訂單號
        'callbackurl' => '', // 異步通知地址
        'hrefbackurl' => '', // 同步通知地址，可空
        'attach' => '', // 備註訊息，可空
        'isshow' => '0', // 是否顯示收銀台，默認為1，二維圖片為0
        'sign' => '', // MD5簽名
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'partner' => 'number',
        'paymoney' => 'amount',
        'ordernumber' => 'orderId',
        'callbackurl' => 'notify_url',
        'banktype' => 'paymentVendorId'
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
        '1090' => 'WEIXIN', // 微信
        '1092' => 'ALIPAYSCAN', // 支付寶
        '1097' => 'WEIXINWAP', // 微信_手機支付
        '1098' => 'ALIPAYWAP', // 支付寶_手機支付
        '1103' => 'QQ', // QQ_二維
        '1104' => 'QQWAP', // QQ_手機支付
        '1107' => 'JDPAY', // 京東錢包_二維
    ];

    /**
     * 對外到支付平台的掃碼提交網址
     *
     * @var array
     */
    protected $scanPostUrl = [
        '1090' => '/PayWeiXin_System.aspx', // 微信
        '1092' => '/PayAli_System.aspx', // 支付寶
        '1103' => '/PayQQ_System.aspx', // QQ
        '1107' => '/PayJD_System.aspx', // 京東
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
        if (!array_key_exists($this->requestData['banktype'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $this->requestData['paymoney'] = sprintf('%.2f', $this->requestData['paymoney']);
        $this->requestData['banktype'] = $this->bankMap[$this->requestData['banktype']];

        // 設定支付平台需要的加密串
        $this->requestData['sign'] = $this->encode();

        // 微信、支付寶、QQ手機支付
        if (in_array($this->options['paymentVendorId'], [1097, 1098, 1104])) {
            // 檢查是否有postUrl(支付平台提交的url)
            if (trim($this->options['postUrl']) == '') {
                throw new PaymentException('No pay parameter specified', 180145);
            }

            $params = http_build_query($this->requestData);
            $this->requestData['act_url'] = $this->options['postUrl'] . '?' . $params;

            return $this->requestData;
        }

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'method' => 'GET',
            'uri' => $this->scanPostUrl[$this->options['paymentVendorId']],
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => urldecode(http_build_query($this->requestData)),
            'header' => []
        ];

        $result = $this->curlRequest($curlParam);
        $data = parse_url($result, PHP_URL_QUERY);

        if (!$data) {
            throw new PaymentConnectionException($result, 180130, $this->getEntryId());
        }

        $parseData = [];

        // 回傳格式為query string，因此直接用parse_str來做分解
        parse_str($data, $parseData);

        if (!isset($parseData['data']) || $parseData['data'] == '') {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        $this->setQrcode($parseData['data']);

        return [];
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

        // 依key1=value1&key2=value2&...&keyN=valueN之後做md5
        $encodeStr = urldecode(http_build_query($encodeData));
        $encodeStr .= $this->privateKey;

        // 沒有sign就要丟例外，其他的參數在組織加密串的時候驗證過了
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

        // 加密設定
        foreach ($this->encodeParams as $index) {
            $encodeData[$index] = $this->requestData[$index];
        }

        // 依key1=value1&key2=value2&...&keyN=valueN之後做md5
        $encodeStr = urldecode(http_build_query($encodeData));
        $encodeStr .= $this->privateKey;

        return md5($encodeStr);
    }
}

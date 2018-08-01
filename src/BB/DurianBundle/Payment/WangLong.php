<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;
use Symfony\Component\DomCrawler\Crawler;

/**
 * 網龍支付
 */
class WangLong extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'service' => 'ebankPay', // 接口名稱
        'signType' => 'MD5', // 簽名類型
        'sign' => '', // 簽名
        'inputCharset' => 'UTF-8', // 字符集
        'sysMerchNo' => '', // 商號
        'outOrderNo' => '', // 訂單號
        'orderTime' => '', // 訂單時間(格式：YmdHis)
        'orderAmt' => '', // 支付金額，單位:元，精確到小數後兩位
        'orderTitle' => '', // 訂單標題，不可空
        'clientIp' => '', // 客戶端IP
        'frontUrl' => '', // 同步通知網址
        'backUrl' => '', // 異步通知網址
        'selectFinaCode' => '', // 銀行編碼
        'tranAttr' => 'DEBIT', // 交易屬性，DEBIT:借記卡
        'settleCycle' => '', // 結算週期
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'sysMerchNo' => 'number',
        'outOrderNo' => 'orderId',
        'orderTime' => 'orderCreateDate',
        'orderAmt' => 'amount',
        'orderTitle' => 'orderId',
        'clientIp' => 'ip',
        'frontUrl' => 'notify_url',
        'backUrl' => 'notify_url',
        'selectFinaCode' => 'paymentVendorId',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'service',
        'inputCharset',
        'sysMerchNo',
        'outOrderNo',
        'orderTime',
        'orderAmt',
        'orderTitle',
        'clientIp',
        'frontUrl',
        'backUrl',
        'selectFinaCode',
        'tranAttr',
        'settleCycle',
    ];

    /**
     * 返回驗證時需要加密的參數
     *     0: 有返回就要加密的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'inputCharset' => 1,
        'tranNo' => 1,
        'tranTime' => 1,
        'oriTranNo' => 0,
        'sysMerchNo' => 1,
        'outOrderNo' => 1,
        'oriOrderNo' => 0,
        'orderTime' => 1,
        'orderAmt' => 1,
        'tranCode' => 0,
        'tranAttr' => 0,
        'tranSubAttr' => 0,
        'tranAmt' => 1,
        'tranFeeAmt' => 0,
        'tranResult' => 1,
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
        '1' => 'ICBC', // 工商銀行
        '2' => 'COMM', // 交通銀行
        '3' => 'ABC', // 農業銀行
        '4' => 'CCB', // 建設銀行
        '5' => 'CMB', // 招商銀行
        '6' => 'CMBC', // 民生銀行總行
        '8' => 'SPDB', // 上海浦東發展銀行
        '9' => 'BJBANK', // 北京銀行
        '10' => 'CIB', // 興業銀行
        '11' => 'CITIC', // 中信銀行
        '12' => 'CEB', // 光大銀行
        '13' => 'HXBANK', // 華夏銀行
        '14' => 'GDB', // 廣東發展銀行
        '15' => 'SPABANK', // 平安銀行
        '16' => 'PSBC', // 中國郵政
        '17' => 'BOC', // 中國銀行
        '19' => 'SHBANK', // 上海銀行
        '278' => 'ICBC', // 銀聯在線（快捷）
        '1088' => 'ICBC', // 銀聯在線_手機支付（快捷）
        '1090' => 'WEIXIN', // 微信_二維
        '1092' => 'ALIPAY', // 支付寶_二維
        '1098' => 'ALIPAY', // 支付寶_手機支付
        '1103' => 'QQPAY', // QQ_二維
        '1107' => 'JDPAY', // 京東_二維
        '1111' => 'UNIPAY', // 銀聯_二維
        '1115' => 'WEIXIN', // 微信支付_條碼
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

        // 從內部設值到支付參數
        foreach ($this->requireMap as $paymentKey => $internalKey) {
            $this->requestData[$paymentKey] = $this->options[$internalKey];
        }

        // 帶入未支援的支付方式就噴例外
        if (!array_key_exists($this->requestData['selectFinaCode'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $this->requestData['selectFinaCode'] = $this->bankMap[$this->requestData['selectFinaCode']];
        $this->requestData['orderAmt'] = sprintf('%.2f', $this->requestData['orderAmt']);
        $createAt = new \Datetime($this->requestData['orderTime']);
        $this->requestData['orderTime'] = $createAt->format('YmdHis');

        // 取得商家附加設定值
        $merchantExtras = $this->getMerchantExtraValue([
            'OnlineBankSettleCycle', // 網銀結算週期
            'QuickPaySettleCycle', // 快捷結算週期
            'QrcodeSettleCycle', // 二維結算週期
            'PhonePaySettleCycle', // 手機支付結算週期
        ]);

        $this->requestData['settleCycle'] = $merchantExtras['OnlineBankSettleCycle'];

        // 網銀 uri
        $uri = '/trade/api/ebankPay';

        // 調整二維、手機支付 uri 及提交參數
        if (in_array($this->options['paymentVendorId'], [1090, 1092, 1098, 1103, 1107, 1111, 1115])) {
            $uri = '/trade/api/unifiedOrder';

            $this->requestData['service'] = 'unifiedOrder';
            $this->requestData['tranAttr'] = 'NATIVE';
            $this->requestData['settleCycle'] = $merchantExtras['QrcodeSettleCycle'];

            if ($this->options['paymentVendorId'] == 1098) {
                $this->requestData['settleCycle'] = $merchantExtras['PhonePaySettleCycle'];
            }

            if (in_array($this->options['paymentVendorId'], [1098, 1115])) {
                $this->requestData['tranAttr'] = 'H5';
            }
        }

        // 調整銀聯在線、銀聯在線手機支付 uri 及提交參數
        if (in_array($this->options['paymentVendorId'], [278, 1088])) {
            $uri = '/trade/api/placeQuickPay';

            $this->requestData['service'] = 'placeQuickPay';
            $this->requestData['userId'] = substr($this->requestData['outOrderNo'], -10);
            $this->requestData['tranSubAttr'] = 'FRONT';
            $this->requestData['settleCycle'] = $merchantExtras['QuickPaySettleCycle'];
        }

        // 設定加密簽名
        $this->requestData['sign'] = $this->encode();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'method' => 'POST',
            'uri' => $uri,
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => urldecode(http_build_query($this->requestData)),
            'header' => [],
        ];

        $result = $this->curlRequest($curlParam);
        $parseData = json_decode($result, true);

        if (!isset($parseData['retCode']) || !isset($parseData['retMsg'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        if ($parseData['retCode'] !== '0000') {
            throw new PaymentConnectionException($parseData['retMsg'], 180130, $this->getEntryId());
        }

        // 微信二維、支付寶二維、QQ二維、京東二維、銀聯二維
        if (in_array($this->options['paymentVendorId'], [1090, 1092, 1103, 1107, 1111])) {
            if (!isset($parseData['codeUrl'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            $this->setQrcode($parseData['codeUrl']);

            return [];
        }

        // 支付寶手機支付、微信支付條碼
        if (in_array($this->options['paymentVendorId'], [1098, 1115])) {
            if (!isset($parseData['jumpUrl'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }
            $parseResult = $this->parseUrl($parseData['jumpUrl']);

            $this->payMethod = 'GET';

            return [
                'post_url' => $parseResult['url'],
                'params' => $parseResult['params'],
            ];
        }

        if (!isset($parseData['autoSubmitForm'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        // 跳轉網銀、銀聯在線、銀聯在線手機支付的 html，需解析 form 取得提交的網址及參數
        $html = $parseData['autoSubmitForm'];

        $crawler = new Crawler();
        $crawler->addHtmlContent($html);

        $formNode = $crawler->filterXPath('//form[1]');

        if (count($formNode) == 0) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        $postUrl = trim($formNode->attr('action'));

        if ($postUrl == '') {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        // 取出所有 hidden 類型 input 元素的 name、value 屬性值
        $inputDatas = $formNode->filterXPath('//input[@type="hidden"]')->extract(['name', 'value']);
        $params = [];

        foreach ($inputDatas as $inputData) {
            $params[$inputData[0]] = $inputData[1];
        }

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
        // 驗證私鑰
        $this->verifyPrivateKey();
        $this->payResultVerify();

        $encodeData = [];

        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options) && trim($this->options[$paymentKey]) != '') {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        ksort($encodeData);

        $encodeStr = urldecode(http_build_query($encodeData));
        $encodeStr .= $this->privateKey;

        // 若沒有返回簽名需丟例外
        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if (strcasecmp($this->options['sign'], md5($encodeStr)) != 0) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['tranResult'] != 'SUCCESS') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['outOrderNo'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['tranAmt'] != $entry['amount']) {
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

        /**
         * 組織加密簽名，排除sign(加密簽名)、signType(簽名方式)，
         * 其他非空的參數都要納入加密
         */
        foreach ($this->requestData as $key => $value) {
            if ($key != 'sign' && $key != 'signType' && trim($value) != '') {
                $encodeData[$key] = $value;
            }
        }

        ksort($encodeData);

        $encodeStr = urldecode(http_build_query($encodeData));
        $encodeStr .= $this->privateKey;

        return md5($encodeStr);
    }
}

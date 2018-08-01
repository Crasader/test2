<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 銳付支付
 */
class Rfupay extends PaymentBase
{
    /**
     * 支付提交參數
     *
     * @var array
     */
    protected $requestData = [
        'partyId' => '', // 商戶編號
        'accountId' => '', // 商戶編號
        'appType' => '', // 應用類型(網關不用填, 微信支付:WECHAT)
        'orderNo' => '', // 訂單號, 前面須加上商戶首碼(交易單號字頭)
        'orderAmount' => '', // 訂單金額(保留兩位小數)
        'goods' => '', // 商戶首碼(交易單號字頭)
        'returnUrl' => '', // 返回位址
        'checkUrl' => '', // 回調地址
        'cardType' => '01', // 支付卡種(01:人民幣轉帳卡, 02:信用卡)
        'bank' => '', // 銀行代碼
        'encodeType' => 'Md5', // 簽名方式
        'refCode' => '', // 子商戶參考編號
        'signMD5' => '', // 簽名
    ];

    /**
     * 支付提交參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'partyId' => 'number',
        'orderNo' => 'orderId',
        'orderAmount' => 'amount',
        'returnUrl' => 'notify_url',
        'checkUrl' => 'notify_url',
        'bank' => 'paymentVendorId',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'orderNo',
        'appType',
        'orderAmount',
        'encodeType',
    ];

    /**
     * 返回驗證時需要加密的參數
     *     0: 有返回就要加密的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'orderNo' => 1,
        'appType' => 1,
        'orderAmount' => 1,
        'succ' => 1,
        'encodeType' => 1,
    ];

    /**
     * 應答機制訊息
     *
     * @var string
     */
    protected $msg = 'checkok';

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        1 => '00004', // 工商銀行
        2 => '00005', // 交通銀行
        3 => '00017', // 農業銀行
        4 => '00003', // 建設銀行
        5 => '00021', // 招商銀行
        6 => '00013', // 民生銀行
        8 => '00032', // 上海浦東發展銀行
        9 => '00050', // 北京銀行
        10 => '00016', // 興業銀行
        11 => '00054', // 中信銀行
        12 => '00057', // 光大銀行
        13 => '00041', // 華夏銀行
        14 => '00052', // 廣東發展銀行
        15 => '00006', // 平安銀行
        16 => '00051', // 中國郵政
        17 => '00083', // 中國銀行
        19 => '00102', // 上海銀行
        222 => '00103', // 寧波銀行
        226 => '00104', // 南京銀行
        1088 => 'MWEB', // 銀聯在線手機支付
        1090 => 'wechat', // 微信_二維
        1092 => 'alipay', // 支付寶_二維
        1103 => '', // QQ_二維
        1104 => '', // QQ_手機支付
    ];

    /**
     * 查詢時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $trackingRequestData = [
        'partyId' => '', // 商戶編號
        'accountId' => '', // 商戶編號
        'orderNo' => '', // 訂單號, 前面須加上商戶首碼(交易單號字頭)
        'mtaTransIdFrm' => '', // 流水訂單號開始於
        'mtaTransIdTo' => '', // 流水訂單號結束於
        'signMD5' => '' // 簽名
    ];

    /**
     * 查詢時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $trackingRequireMap = [
        'partyId' => 'number',
        'orderNo' => 'orderId',
    ];

    /**
     * 查詢時需要加密的參數
     *
     * @var array
     */
    protected $trackingEncodeParams = [
        'orderNo',
        'mtaTransIdFrm',
        'mtaTransIdTo',
    ];

    /**
     * 查詢解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $trackingDecodeParams = [
        'orderNo' => 1,
        'mtaTransId' => 1,
        'result' => 1,
        'respCode' => 1,
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

        // 檢查銀行代碼是否支援
        if (!array_key_exists($this->requestData['bank'], $this->bankMap)) {
            throw new PaymentException(
                'PaymentVendor is not supported by PaymentGateway',
                180066
            );
        }

        // 支付銀行若為銀聯在線，需調整應用類型
        if ($this->options['paymentVendorId'] == 1088) {
            $this->requestData['appType'] = 'MWEB';
        }

        // 支付銀行若為微信_二維，需調整應用類型
        if ($this->options['paymentVendorId'] == 1090) {
            $this->requestData['appType'] = 'WECHAT';
        }

        // 支付銀行若為支付寶_二維，需調整應用類型
        if ($this->options['paymentVendorId'] == 1092) {
            $this->requestData['appType'] = 'ALIPAY';
        }

        // 支付銀行若為QQ_二維或QQ_手機支付，需調整應用類型
        if (in_array($this->options['paymentVendorId'], [1103, 1104])) {
            $this->requestData['appType'] = 'QPAY';
        }

        // 額外的參數設定
        $this->requestData['bank'] = $this->bankMap[$this->requestData['bank']];
        $this->requestData['orderAmount'] = sprintf('%.2f', $this->requestData['orderAmount']);

        // 商家額外的參數設定
        $names = ['accountId', 'goods', 'refCode'];
        $extra = $this->getMerchantExtraValue($names);

        foreach ($names as $name) {
            $this->requestData[$name] = $extra[$name];
        }

        // 訂單號前加上商戶首碼
        $this->requestData['orderNo'] = $this->requestData['goods'] . $this->requestData['orderNo'];

        // 設定加密簽名
        $this->requestData['signMD5'] = $this->encode();

        return $this->requestData;
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

        // 驗證返回參數
        $this->payResultVerify();

        // 組合參數驗證加密簽名
        $encodeStr = '';
        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options)) {
                $encodeStr .= $paymentKey . $this->options[$paymentKey];
            }
        }

        $encodeStr .= $this->privateKey;

        // 沒有返回signMD5就要丟例外
        if (!isset($this->options['signMD5'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if (strcasecmp($this->options['signMD5'], strtolower(md5($encodeStr))) != 0) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['succ'] !== 'Y') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        // 沒有返回goods(商戶首碼)就要丟例外
        if (!isset($this->options['goods'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['orderNo'] != $this->options['goods'] . $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['orderAmount'] != $entry['amount']) {
            throw new PaymentException('Order Amount error', 180058);
        }
    }

    /**
     * 訂單查詢
     */
    public function paymentTracking()
    {
        // 驗證私鑰
        $this->verifyPrivateKey();

        // 驗證訂單查詢參數
        $this->trackingVerify();

        // 從內部給定值到參數
        foreach ($this->trackingRequireMap as $paymentKey => $internalKey) {
            $this->trackingRequestData[$paymentKey] = $this->options[$internalKey];
        }

        // 商家額外的參數設定
        $names = ['accountId', 'goods'];
        $extra = $this->getMerchantExtraValue($names);

        $this->trackingRequestData['accountId'] = $extra['accountId'];

        // 訂單號前加上商戶首碼
        $this->trackingRequestData['orderNo'] = $extra['goods'] . $this->trackingRequestData['orderNo'];

        // 設定加密簽名
        $this->trackingRequestData['signMD5'] = strtolower($this->trackingEncode());

        if (trim($this->options['reopUrl']) == '') {
            throw new PaymentException('No reopUrl specified', 180141);
        }

        // 因通過對外機 proxy 到銳付會 timeout，改為此方式對外
        $params = [
            'url' => $this->options['reopUrl'],
            'data' => http_build_query($this->trackingRequestData),
        ];

        $curlParam = [
            'method' => 'GET',
            'uri' => '/pay/curl.php',
            'ip' => [$this->container->getParameter('payment_ip')],
            'host' => $this->container->getParameter('payment_ip'),
            'param' => http_build_query($params),
            'header' => [],
        ];

        $result = $this->curlRequest($curlParam);

        // 檢查訂單查詢返回參數
        $parseData = $this->parseData($result);

        $this->trackingResultVerify($parseData);

        // 訂單查詢時提交的參數錯誤
        if ($parseData['result'] == '0500') {
            throw new PaymentConnectionException('Submit the parameter error', 180075, $this->getEntryId());
        }

        // 商戶發送的簽名驗證錯誤
        if ($parseData['result'] == '1020') {
            throw new PaymentConnectionException(
                'PaymentGateway error, Merchant sign error',
                180127,
                $this->getEntryId()
            );
        }

        // 訂單不存在
        if ($parseData['result'] == '1010') {
            throw new PaymentConnectionException('Order does not exist', 180060, $this->getEntryId());
        }

        // 如果訂單查詢返回結果異常就丟例外
        if ($parseData['result'] != '0000') {
            throw new PaymentConnectionException('Payment tracking failed', 180081, $this->getEntryId());
        }

        // 組合參數驗證加密簽名
        $encodeStr = '';
        foreach (array_keys($this->trackingDecodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $parseData)) {
                $encodeStr .= $parseData[$paymentKey];
            }
        }

        $encodeStr .= $this->privateKey;

        // 如果沒有signMD5丟例外
        if (!isset($parseData['signMD5'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        // 驗證簽名
        if (strcasecmp($parseData['signMD5'], strtolower(md5($encodeStr))) != 0) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        // 訂單狀態為0則代表未支付
        if ($parseData['respCode'] == '0') {
            throw new PaymentConnectionException('Unpaid order', 180062, $this->getEntryId());
        }

        // 訂單狀態不為1則代表支付失敗
        if ($parseData['respCode'] != '1') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        // 如果沒有transAmt丟例外
        if (!isset($parseData['transAmt'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        if ($parseData['transAmt'] != $this->options['amount']) {
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
        $encodeStr = '';

        // 加密設定
        foreach ($this->encodeParams as $index) {
            $encodeStr .= $index . $this->requestData[$index];
        }

        $encodeStr .= $this->privateKey;

        return strtolower(md5($encodeStr));
    }

    /**
     * 解析訂單查詢結果
     *
     * @param string $content xml格式的回傳值
     * @return array
     */
    private function parseData($content)
    {
        // 因RESPONSE前有一段亂碼, 故需找xml的開頭與結尾
        $match = [];
        preg_match('/<\?xml version.*?<\/documents>/', $content, $match);

        if (!isset($match[0])) {
            throw new PaymentConnectionException('Invalid response', 180148, $this->getEntryId());
        }

        $parseData = $this->xmlToArray($match[0]);

        if (!isset($parseData['MtapayResp'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        return $parseData['MtapayResp'];
    }
}

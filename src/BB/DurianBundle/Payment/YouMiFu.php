<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 優米付
 */
class YouMiFu extends PaymentBase
{
    /**
     * 支付提交參數
     *
     * @var array
     */
    protected $requestData = [
        'apiName' => 'WEB_PAY_B2C', // 接口名字
        'apiVersion' => '1.0.0.0', // 接口版本
        'platformID' => '', // 平台號ID
        'merchNo' => '', // 商號
        'orderNo' => '', // 訂單號
        'tradeDate' => '', // 交易日期(格式：Ymd)
        'amt' => '', // 支付金額，保留小數點兩位，單位：元
        'merchUrl' => '', // 支付結果通知網址, 不能串參數
        'merchParam' => '', // 商戶參數
        'tradeSummary' => '', // 交易摘要，不可為空，顯示在後台，設定username方便業主比對
        'signMsg' => '', // 加密簽名
        'bankCode' => '', // 銀行代碼，不納入加密簽名
        'choosePayType' => '1', // 預設網銀:1，不納入加密簽名
        'customerIP' => '', // 客戶端 IP(網銀不納簽，二維要)
    ];

    /**
     * 支付提交參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'merchNo' => 'number',
        'orderNo' => 'orderId',
        'tradeDate' => 'orderCreateDate',
        'amt' => 'amount',
        'merchUrl' => 'notify_url',
        'bankCode' => 'paymentVendorId',
        'tradeSummary' => 'username',
        'customerIP' => 'ip',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'apiName',
        'apiVersion',
        'platformID',
        'merchNo',
        'orderNo',
        'tradeDate',
        'amt',
        'merchUrl',
        'merchParam',
        'tradeSummary'
    ];

    /**
     * 返回驗證時需要加密的參數
     *     0: 有返回就要加密的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'apiName' => 1,
        'notifyTime' => 1,
        'tradeAmt' => 1,
        'merchNo' => 1,
        'merchParam' => 1,
        'orderNo' => 1,
        'tradeDate' => 1,
        'accNo' => 1,
        'accDate' => 1,
        'orderStatus' => 1
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
        1 => 'ICBC', // 工商銀行
        2 => 'COMM', // 交通銀行
        3 => 'ABC', // 農業銀行
        4 => 'CCB', // 建設銀行
        5 => 'CMB', // 招商銀行
        6 => 'CMBC', // 民生銀行總行
        8 => 'SPDB', // 上海浦東發展銀行
        9 => 'BOBJ', // 北京銀行
        10 => 'CIB', // 興業銀行
        11 => 'CNCB', // 中信銀行
        12 => 'CEB', // 光大銀行
        13 => 'HXB', // 華夏銀行
        14 => 'GDB', // 廣東發展銀行
        15 => 'PAB', // 平安銀行
        16 => 'PSBC', // 中國郵政
        17 => 'BOC', // 中國銀行
        222 => 'BONB', // 寧波銀行
        278 => '12', // 銀聯在線
        1088 => '12', // 銀聯在線_手機支付
        1090 => 'WECHAT_PAY', // 微信_二維
        1092 => '4', // 支付寶_二維
        1097 => '13', // 微信_手機支付
        1098 => '9', // 支付寶_手機支付
        1103 => 'QQWLT_PAY', // QQ_二維
        1104 => '15', // QQ_手機支付
        1107 => 'JDWLT_PAY', // 京東_二維
        1108 => '21', // 京東_手機支付
        1111 => '17', // 銀聯_二維
    ];

    /**
     * 出款時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $withdrawRequestData = [
        'apiName' => 'SINGLE_ENTRUST_SETT', // 接口名稱，固定值
        'apiVersion' => '1.0.0.0', // 接口版本，固定值
        'platformID' => '', // 平台號ID
        'merchNo' => '', // 商戶號
        'orderNo' => '', // 商戶訂單號
        'tradeDate' => '', // 交易日期
        'merchUrl' => '', // 通知地址
        'merchParam' => '', // 商戶參數，可空
        'bankAccNo' => '', // 銀行卡卡號
        'bankAccName' => '', // 銀行卡戶名
        'bankCode' => '', // 銀行卡銀行代碼
        'bankName' => '', // 銀行卡開戶行名稱
        'province' => '', // 開戶銀行所在省
        'city' => '', // 開戶銀行所在市
        'Amt' => '', // 結算金額
        'tradeSummary' => '', // 交易摘要
        'signMsg' => '', // 簽名
    ];

    /**
     * 出款時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $withdrawRequireMap = [
        'merchNo' => 'number',
        'orderNo' => 'orderId',
        'tradeDate' => 'orderCreateDate',
        'merchUrl' => 'shop_url',
        'bankAccNo' => 'account',
        'bankAccName' => 'nameReal',
        'bankCode' => 'bank_info_id',
        'bankName' => 'bank_name',
        'province' => 'bank_name',
        'city' => 'bank_name',
        'Amt' => 'amount',
    ];

    /**
     * 出款支援的銀行對應編號
     *
     * @var array
     */
    protected $withdrawBankMap = [
        1 => 'ICBC', // 工商銀行
        2 => 'COMM', // 交通銀行
        3 => 'ABC', // 農業銀行
        4 => 'CCB', // 建設銀行
        5 => 'CMB', // 招商銀行
        6 => 'CMBC', // 民生銀行總行
        8 => 'SPDB', // 上海浦東發展銀行
        9 => 'BOBJ', // 北京銀行
        10 => 'CIB', // 興業銀行
        11 => 'CNCB', // 中信銀行
        12 => 'CEB', // 光大銀行
        13 => 'HXB', // 華夏銀行
        14 => 'GDB', // 廣東發展銀行
        15 => 'PAB', // 平安銀行
        16 => 'PSBC', // 中國郵政
        17 => 'BOC', // 中國銀行
        222 => 'BONB', // 寧波銀行
    ];

    /**
     * 出款時需要加密的參數
     *
     * @var array
     */
    protected $withdrawEncodeParams = [
        'apiName',
        'apiVersion',
        'platformID',
        'merchNo',
        'orderNo',
        'tradeDate',
        'merchUrl',
        'merchParam',
        'bankAccNo',
        'bankAccName',
        'bankCode',
        'bankName',
        'province',
        'city',
        'Amt',
        'tradeSummary',
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
        if (!array_key_exists($this->requestData['bankCode'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $this->requestData['bankCode'] = $this->bankMap[$this->requestData['bankCode']];
        $this->requestData['amt'] = sprintf('%.2f', $this->requestData['amt']);
        $createAt = new \Datetime($this->requestData['tradeDate']);
        $this->requestData['tradeDate'] = $createAt->format('Ymd');

        // 商家額外的參數設定
        $names = ['platformID'];
        $extra = $this->getMerchantExtraValue($names);
        $this->requestData['platformID'] = $extra['platformID'];

        // 微信二維、QQ二維、京東二維支付
        if (in_array($this->options['paymentVendorId'], [1090, 1103, 1107])) {
            // 修改需要提交和加密的參數
            $this->requestData['apiName'] = $this->requestData['bankCode'];
            $this->encodeParams[] = 'customerIP';

            unset($this->requestData['bankCode']);
            unset($this->requestData['choosePayType']);

            $this->requestData['signMsg'] = $this->encode();

            if (trim($this->options['verify_url']) == '') {
                throw new PaymentException('No verify_url specified', 180140);
            }

            $curlParam = [
                'method' => 'POST',
                'uri' => '/cgi-bin/netpayment/pay_gate.cgi',
                'ip' => $this->options['verify_ip'],
                'host' => $this->options['verify_url'],
                'param' => urldecode(http_build_query($this->requestData)),
                'header' => [],
            ];

            $result = $this->curlRequest($curlParam);
            $parseData = $this->xmlToArray($result);

            if (!isset($parseData['respData']['respCode']) || !isset($parseData['respData']['respDesc'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            // 返回非 00 都為失敗
            if ($parseData['respData']['respCode'] != '00') {
                throw new PaymentConnectionException($parseData['respData']['respDesc'], 180130, $this->getEntryId());
            }

            if (!isset($parseData['respData']['codeUrl'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            $this->setQrcode(base64_decode($parseData['respData']['codeUrl']));

            return [];
        }

        // 支付寶二維、微信手機支付、支付寶手機支付、QQ手機支付、京東手機支付、銀聯二維，需調整渠道類型
        if (in_array($this->options['paymentVendorId'], [1092, 1097, 1098, 1104, 1108, 1111])) {
            $this->requestData['apiName'] = 'WAP_PAY_B2C';
            $this->requestData['choosePayType'] = $this->requestData['bankCode'];
            $this->requestData['bankCode'] = '';
        }

        // 銀聯在線、銀聯在線手機支付調整提交參數
        if (in_array($this->options['paymentVendorId'], [278, 1088])) {
            $this->requestData['choosePayType'] = $this->requestData['bankCode'];
        }

        // 設定加密簽名
        $this->requestData['signMsg'] = $this->encode();

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
        $encodeData = [];
        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options)) {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        $encodeStr = urldecode(http_build_query($encodeData));
        $encodeStr .= $this->privateKey;

        // 沒有返回signMsg就要丟例外
        if (!isset($this->options['signMsg'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if (strcasecmp($this->options['signMsg'], md5($encodeStr)) != 0) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['orderStatus'] !== '1') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['orderNo'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['tradeAmt'] != $entry['amount']) {
            throw new PaymentException('Order Amount error', 180058);
        }
    }

    /**
     * 線上出款
     */
    public function withdrawPayment()
    {
        $this->verifyPrivateKey();
        $this->withdrawVerify();

        // 從內部給定值到參數
        foreach ($this->withdrawRequireMap as $paymentKey => $internalKey) {
            $this->withdrawRequestData[$paymentKey] = $this->options[$internalKey];
        }

        if (trim($this->options['province']) != '') {
            $this->withdrawRequestData['province'] = $this->options['province'];
        }

        if (trim($this->options['city']) != '') {
            $this->withdrawRequestData['city'] = $this->options['city'];
        }

        $withdrawHost = trim($this->options['withdraw_host']);

        // 設定返回網址
        $this->withdrawRequestData['merchUrl'] .= 'withdraw_return.php';

        // 取得商家附加設定值
        $merchantExtraValues = $this->getMerchantExtraValue(['platformID']);
        $this->withdrawRequestData['platformID'] = $merchantExtraValues['platformID'];

        $this->withdrawRequestData['bankCode'] = $this->withdrawBankMap[$this->withdrawRequestData['bankCode']];
        $this->withdrawRequestData['Amt'] = sprintf('%.2f', $this->withdrawRequestData['Amt']);
        $createAt = new \Datetime($this->withdrawRequestData['tradeDate']);
        $this->withdrawRequestData['tradeDate'] = $createAt->format('Ymd');

        // bankName需串上支行
        $this->withdrawRequestData['bankName'] .= $this->options['branch'];

        // 設定出款需要的加密串
        $this->withdrawRequestData['signMsg'] = $this->withdrawEncode();

        // 出款
        $curlParam = [
            'method' => 'POST',
            'uri' => '/cgi-bin/netpayment/pay_gate.cgi',
            'ip' => $this->options['verify_ip'],
            'host' => $withdrawHost,
            'param' => http_build_query($this->withdrawRequestData),
            'header' => [],
            'timeout' => 60,
        ];

        $result = $this->curlRequest($curlParam);
        $parseData = $this->xmlToArray($result);

        // 對返回結果做檢查
        if (!isset($parseData['respData']['respCode']) || !isset($parseData['respData']['respDesc'])) {
            throw new PaymentException('No withdraw return parameter specified', 150180209);
        }

        if ($parseData['respData']['respCode'] !== '00') {
            throw new PaymentConnectionException($parseData['respData']['respDesc'], 180124, $this->getEntryId());
        }

        if (isset($parseData['respData']['batchNo'])) {
            // 紀錄出款明細的支付平台參考編號
            $this->setCashWithdrawEntryRefId($parseData['respData']['batchNo']);
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

        $encodeStr = urldecode(http_build_query($encodeData));
        $encodeStr .= $this->privateKey;

        return md5($encodeStr);
    }

    /**
     * 出款時的加密
     *
     * @return string
     */
    protected function withdrawEncode()
    {
        $encodeData = [];

        foreach ($this->withdrawEncodeParams as $key) {
            $encodeData[$key] = $this->withdrawRequestData[$key];
        }

        $encodeStr = urldecode(http_build_query($encodeData));
        $encodeStr .= $this->privateKey;

        return strtoupper(md5($encodeStr));
    }
}

<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * AI支付
 */
class AIPay extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'version' => 'v1', // 接口版本，固定值v1
        'merchant_no' => '', // 商戶號
        'order_no' => '', // 商戶訂單號
        'goods_name' => '', // 商品名稱
        'order_amount' => '', // 金額，精確到小數點後兩位
        'backend_url' => '', // 異步通知地址
        'frontend_url' => '', // 同步通知地址，非必填
        'reserve' => '', // 商戶保留信息，非必填
        'pay_mode' => '01', // 支付模式，01:web(網銀)
        'bank_code' => '', // 銀行編碼
        'card_type' => '0', // 允許支付的卡類型，0:借記卡
        'sign' => '', // 簽名
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'merchant_no' => 'number',
        'order_no' => 'orderId',
        'goods_name' => 'orderId',
        'order_amount' => 'amount',
        'backend_url' => 'notify_url',
        'bank_code' => 'paymentVendorId',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'version',
        'merchant_no',
        'order_no',
        'goods_name',
        'order_amount',
        'backend_url',
        'frontend_url',
        'reserve',
        'pay_mode',
        'bank_code',
        'card_type',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'merchant_no' => 1,
        'order_no' => 1,
        'order_amount' => 1,
        'original_amount' => 1,
        'upstream_settle' => 1,
        'result' => 1,
        'pay_time' => 1,
        'trace_id' => 1,
        'reserve' => 0,
    ];

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        '1' => 'ICBC', // 工商銀行
        '2' => 'BOCOM', // 交通銀行
        '3' => 'ABC', // 中國農業銀行
        '4' => 'CCB', // 中國建設銀行
        '5' => 'CMB', // 招商銀行
        '6' => 'CMBC', // 中國民生銀行
        '8' => 'SPDB', // 上海浦東發展銀行
        '9' => 'BCCB', // 北京銀行
        '10' => 'CIB', // 興業銀行
        '11' => 'CNCB', // 中信銀行
        '12' => 'CEB', // 中國光大銀行
        '13' => 'HXB', // 華夏銀行
        '14' => 'GDB', // 廣東發展銀行
        '15' => 'PAB', // 平安銀行
        '16' => 'PSBC', // 中國郵政
        '17' => 'BOC', // 中國銀行
        '19' => 'BOS', // 上海銀行
        '1092' => 'ALIPAY', // 支付寶_二維
        '1103' => 'QQSCAN', // QQ_二維
        '1111' => 'UNIONPAY', // 銀聯_二維
        '1119' => 'ALIPAYCODE', // 支付寶條碼_手機支付
    ];

    /**
     * 出款時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $withdrawRequestData = [
        'merchant_no' => '', // 商戶號
        'order_no' => '', // 商戶訂單號
        'card_no' => '', // 銀行卡號
        'account_name' => '', // 銀行開戶名，使用base64進行編碼
        'bank_branch' => '', // 銀行支行名稱，非必填
        'cnaps_no' => '', // 銀行聯行號，非必填
        'bank_code' => '', // 銀行代碼
        'bank_name' => '', // 銀行名稱，使用base64進行編碼
        'amount' => '',  // 金額，精確到小數點後兩位
        'sign' => '', // 簽名
    ];

    /**
     * 出款時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $withdrawRequireMap = [
        'merchant_no' => 'number',
        'order_no' => 'orderId',
        'card_no' => 'account',
        'account_name' => 'nameReal',
        'bank_code' => 'bank_info_id',
        'amount' => 'amount',
    ];

    /**
     * 出款支援的銀行對應編號
     *
     * @var array
     */
    protected $withdrawBankMap = [
        1 => 'ICBC', // 工商銀行
        2 => 'BOCOM', // 交通銀行
        3 => 'ABC', // 農業銀行
        4 => 'CCB', // 建設銀行
        5 => 'CMB', // 招商銀行
        6 => 'CMBC', // 民生銀行
        8 => 'SPDB', // 浦發銀行
        9 => 'BCCB', // 北京銀行
        10 => 'CIB', // 興業銀行
        11 => 'CNCB', // 中信銀行
        12 => 'CEB', // 光大銀行
        13 => 'HXB', // 華夏銀行
        14 => 'GDB', // 廣發銀行
        15 => 'PAB', // 平安銀行
        16 => 'PSBC', // 中國郵政儲蓄銀行
        17 => 'BOC', // 中國銀行
        220 => 'HZB', // 杭州銀行
        222 => 'NBB', // 寧波銀行
        226 => 'NJB', // 南京銀行
    ];

    /**
     * 出款支援的銀行對應名稱
     *
     * @var array
     */
    protected $withdrawBankNameMap = [
        1 => '中国工商银行', // 工商銀行
        2 => '交通银行', // 交通銀行
        3 => '中国农业银行', // 農業銀行
        4 => '中国建设银行', // 建設銀行
        5 => '招商银行', // 招商銀行
        6 => '中国民生银行', // 民生銀行
        8 => '浦发银行', // 浦發銀行
        9 => '北京银行', // 北京銀行
        10 => '兴业银行', // 興業銀行
        11 => '中信银行', // 中信銀行
        12 => '中国光大银行', // 光大銀行
        13 => '华夏银行', // 華夏銀行
        14 => '广发银行', // 廣發銀行
        15 => '平安银行', // 平安銀行
        16 => '中国邮政储蓄银行', // 中國郵政儲蓄銀行
        17 => '中国银行', // 中國銀行
        220 => '杭州银行', // 杭州銀行
        222 => '宁波银行', // 寧波銀行
        226 => '南京银行', // 南京銀行
    ];

    /**
     * 出款時需要加密的參數
     *
     * @var array
     */
    protected $withdrawEncodeParams = [
        'merchant_no',
        'order_no',
        'card_no',
        'account_name',
        'bank_branch',
        'cnaps_no',
        'bank_code',
        'bank_name',
        'amount',
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
        if (!array_key_exists($this->requestData['bank_code'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $this->requestData['order_amount'] = sprintf('%.2f', $this->requestData['order_amount']);
        $this->requestData['bank_code'] = $this->bankMap[$this->requestData['bank_code']];

        // 二維支付
        if (in_array($this->options['paymentVendorId'], [1092, 1103, 1111])) {
            // 額外的參數設定
            $this->requestData['pay_mode'] = '09';

            $this->requestData['sign'] = $this->encode();

            if (trim($this->options['verify_url']) == '') {
                throw new PaymentException('No verify_url specified', 180140);
            }

            $curlParam = [
                'method' => 'POST',
                'uri' => '/gateway/pay.jsp',
                'ip' => $this->options['verify_ip'],
                'host' => $this->options['verify_url'],
                'param' => http_build_query($this->requestData),
                'header' => [],
            ];

            $result = $this->curlRequest($curlParam);
            $parseData = json_decode($result, true);

            if (!isset($parseData['result_code']) || !isset($parseData['result_msg'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            if ($parseData['result_code'] !== '00') {
                throw new PaymentConnectionException($parseData['result_msg'], 180130, $this->getEntryId());
            }

            if (!isset($parseData['code_url'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            $this->setQrcode($parseData['code_url']);

            return [];
        }

        // 手機支付調整提交參數
        if ($this->options['paymentVendorId'] == 1119) {
            $this->requestData['pay_mode'] = '12';
        }

        // 設定支付平台需要的加密串
        $this->requestData['sign'] = $this->encode();

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

        // 組織加密串
        $encodeStr = [];
        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options)) {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        $encodeData['key'] = $this->privateKey;

        $encodeStr = urldecode(http_build_query($encodeData));

        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['sign'] != strtolower(md5($encodeStr))) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['result'] !== 'S') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['order_no'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['order_amount'] != $entry['amount']) {
            throw new PaymentException('Order Amount error', 180058);
        }
    }

    /**
     * 線上出款
     */
    public function withdrawPayment()
    {
        // 驗證私鑰
        $this->verifyPrivateKey();

        $this->withdrawVerify();

        // 從內部給定值到參數
        foreach ($this->withdrawRequireMap as $paymentKey => $internalKey) {
            $this->withdrawRequestData[$paymentKey] = $this->options[$internalKey];
        }

        $withdrawHost = trim($this->options['withdraw_host']);

        // 驗證出款時支付平台對外設定
        if ($withdrawHost == '') {
            throw new PaymentException('No withdraw_host specified', 150180194);
        }

        $this->withdrawRequestData['bank_name'] = $this->withdrawBankNameMap[$this->withdrawRequestData['bank_code']];
        $this->withdrawRequestData['bank_code'] = $this->withdrawBankMap[$this->withdrawRequestData['bank_code']];
        $this->withdrawRequestData['amount'] = sprintf('%.2f', $this->withdrawRequestData['amount']);

        // 使用base64進行編碼
        $this->withdrawRequestData['account_name'] = base64_encode($this->withdrawRequestData['account_name']);
        $this->withdrawRequestData['bank_name'] = base64_encode($this->withdrawRequestData['bank_name']);

        // 取得商家附加設定值
        $merchantExtraValues = $this->getMerchantExtraValue(['pay_pwd']);

        // 設定出款需要的加密串
        $this->withdrawRequestData['sign'] = $this->withdrawEncode($merchantExtraValues['pay_pwd']);

        // 出款
        $curlParam = [
            'method' => 'POST',
            'uri' => '/withdraw/singleWithdraw',
            'ip' => $this->options['verify_ip'],
            'host' => $withdrawHost,
            'param' => http_build_query($this->withdrawRequestData),
            'header' => [],
            'timeout' => 60,
        ];

        $result = $this->curlRequest($curlParam);
        $parseData = json_decode($result, true);

        if (!isset($parseData['result_code']) || !isset($parseData['result_msg'])) {
            throw new PaymentException('No withdraw return parameter specified', 150180209);
        }

        // 餘額不足
        if ($parseData['result_code'] == 'TRS001') {
            throw new PaymentException('Insufficient balance', 150180197);
        }

        // 000000:表示代付申请成功，失敗則印出訊息
        if ($parseData['result_code'] !== '000000') {
            throw new PaymentConnectionException($parseData['result_msg'], 180124, $this->getEntryId());
        }

        // 紀錄出款明細的支付平台參考編號
        if (isset($parseData['order_no'])) {
            $this->setCashWithdrawEntryRefId($parseData['order_no']);
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

        // 組織加密簽名
        foreach ($this->encodeParams as $index) {
            $encodeData[$index] = $this->requestData[$index];
        }

        $encodeData['key'] = $this->privateKey;

        $encodeStr = urldecode(http_build_query($encodeData));

        return strtolower(md5($encodeStr));
    }

    /**
     * 出款時的加密
     *
     * @param string $paypwd 支付密鑰
     * @return string
     */
    protected function withdrawEncode($paypwd)
    {
        $encodeData = [];

        foreach ($this->withdrawEncodeParams as $key) {
            $encodeData[$key] = $this->withdrawRequestData[$key];
        }

        $encodeData['pay_pwd'] = $paypwd;
        $encodeData['key'] = $this->privateKey;
        $encodeStr = urldecode(http_build_query($encodeData));

        return md5($encodeStr);
    }
}

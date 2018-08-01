<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 天付寶
 */
class TianFuBao extends PaymentBase
{
    /**
     * 支付提交參數
     *
     * @var array
     */
    protected $requestData = [
        'spid' => '', // 商號
        'sp_userid' => '', // 用戶號，為純數字，帶入訂單號
        'spbillno' => '', // 訂單號
        'money' => '', // 金額
        'cur_type' => '1', // 金額類型，1:人民幣，單位分
        'return_url' => '', // 頁面回調地址，不能串參數
        'notify_url' => '', // 後台回調地址，不能串參數
        'errpage_url' => '', // 錯誤頁，可空
        'memo' => '', // 訂單備註，帶入username
        'expire_time' => '', // 訂單有效時長，可空
        'attach' => '', // 附加數據，可空
        'card_type' => '1', // 銀行卡類型，1:借記卡
        'bank_segment' => '', // 銀行代號
        'user_type' => '1', // 用戶類型，1:個人
        'channel' => '1', // 渠道類型，1:PC端
        'encode_type' => 'MD5', // 簽名類型
        'risk_ctrl' => '', // 風險控制數據，可空
        'sign' => '', // 簽名
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'spid' => 'number',
        'sp_userid' => 'orderId',
        'spbillno' => 'orderId',
        'money' => 'amount',
        'return_url' => 'notify_url',
        'notify_url' => 'notify_url',
        'memo' => 'username',
        'bank_segment' => 'paymentVendorId',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'spid',
        'sp_userid',
        'spbillno',
        'money',
        'cur_type',
        'return_url',
        'notify_url',
        'errpage_url',
        'memo',
        'expire_time',
        'attach',
        'card_type',
        'bank_segment',
        'user_type',
        'channel',
        'encode_type',
        'risk_ctrl',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'spid' => 1,
        'spbillno' => 1,
        'listid' => 1,
        'money' => 1,
        'cur_type' => 1,
        'result' => 1,
        'pay_type' => 1,
        'user_type' => 1,
        'encode_type' => 1,
    ];

    /**
     * 應答機制訊息
     *
     * @var string
     */
    protected $msg = '<retcode>00</retcode>';

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        '1' => '1001', // 中國工商銀行
        '2' => '1005', // 交通銀行
        '3' => '1002', // 農業銀行
        '4' => '1004', // 中國建設銀行
        '5' => '1012', // 招商銀行
        '6' => '1010', // 中國民生銀行
        '8' => '1014', // 上海浦東發展銀行
        '9' => '1016', // 北京銀行
        '10' => '1013', // 興業銀行
        '11' => '1007', // 中信銀行
        '12' => '1008', // 中國光大銀行
        '13' => '1009', // 華夏銀行
        '14' => '1017', // 廣東發展銀行
        '15' => '1011', // 平安银行
        '16' => '1006', // 中國郵政
        '17' => '1003', // 中國銀行
        '19' => '1025', // 上海銀行
        '234' => '1103', // 北京農村商業銀行
    ];

    /**
     * 出款時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $withdrawRequestData = [
        'version' => '1.0', // 網關版本，固定值
        'spid' => '', // 商號
        'sp_serialno' => '', // 訂單號
        'sp_reqtime' => '', // 訂單時間，YmdHis
        'tran_amt' => '', // 金額，單位分
        'cur_type' => '1', // 貨幣類型，1:人民幣
        'pay_type' => '1', // 付款方式，1:餘額支付
        'acct_name' => '', // 帳戶名稱
        'acct_id' => '', // 銀行帳號
        'acct_type' => '0', // 帳號類型，0:借記卡
        'bank_name' => '', // 銀行名稱
        'business_type' => '20101', // 業務類型，默認20101
        'memo' => '1', // 摘要訊息
        'sign' => '', // 簽名
    ];

    /**
     * 出款時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $withdrawRequireMap = [
        'spid' => 'number',
        'sp_serialno' => 'orderId',
        'sp_reqtime' => 'orderCreateDate',
        'tran_amt' => 'amount',
        'acct_name' => 'nameReal',
        'acct_id' => 'account',
        'bank_name' => 'bank_info_id',
    ];

    /**
     * 出款支援的銀行對應編號
     *
     * @var array
     */
    protected $withdrawBankMap = [
        '1' => '工商银行', // 工商銀行
        '2' => '交通银行', // 交通銀行
        '3' => '农业银行', // 農業銀行
        '4' => '建设银行', // 建設銀行
        '5' => '招商银行', // 招商銀行
        '6' => '民生银行', // 民生銀行
        '8' => '浦发银行', // 上海浦東發展銀行
        '9' => '北京银行', // 北京銀行
        '10' => '兴业银行', // 興業銀行
        '11' => '中信银行', // 中信銀行
        '12' => '光大银行', // 光大銀行
        '13' => '华夏银行', // 華夏銀行
        '14' => '广发银行', // 廣東發展銀行
        '15' => '平安银行', // 平安銀行
        '16' => '邮储银行', // 中國郵政
        '17' => '中国银行', // 中國銀行
        '19' => '上海银行', // 上海銀行
    ];

    /**
     * 出款時需要加密的參數
     *
     * @var array
     */
    protected $withdrawEncodeParams = [
        'version',
        'spid',
        'sp_serialno',
        'sp_reqtime',
        'tran_amt',
        'cur_type',
        'pay_type',
        'acct_name',
        'acct_id',
        'acct_type',
        'bank_name',
        'business_type',
        'memo',
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

        // 帶入未支援的銀行就噴例外
        if (!array_key_exists($this->requestData['bank_segment'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $this->requestData['bank_segment'] = $this->bankMap[$this->requestData['bank_segment']];
        $this->requestData['money'] = round($this->requestData['money'] * 100);

        // 設定加密簽名
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

        $this->payResultVerify();

        $encodeData = [];

        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options) && $this->options[$paymentKey] !== '') {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        ksort($encodeData);
        $encodeData['key'] = $this->privateKey;
        $encodeStr = urldecode(http_build_query($encodeData));

        // 沒有返回sign就要丟例外
        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if (($this->options['sign']) != md5($encodeStr)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['result'] != '1') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['spbillno'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['money'] != round($entry['amount'] * 100)) {
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

        $withdrawHost = trim($this->options['withdraw_host']);

        $this->withdrawRequestData['bank_name'] = $this->withdrawBankMap[$this->withdrawRequestData['bank_name']];
        $this->withdrawRequestData['tran_amt'] = round($this->withdrawRequestData['tran_amt'] * 100);
        $createAt = new \Datetime($this->withdrawRequestData['sp_reqtime']);
        $this->withdrawRequestData['sp_reqtime'] = $createAt->format('YmdHis');

        // 設定出款需要的加密串
        $this->withdrawRequestData['sign'] = $this->withdrawEncode();

        // 出款
        $curlParam = [
            'method' => 'POST',
            'uri' => '/cgi-bin/v2.0/api_pay_single.cgi',
            'ip' => $this->options['verify_ip'],
            'host' => $withdrawHost,
            'param' => http_build_query($this->withdrawRequestData),
            'header' => [],
            'timeout' => 60,
        ];

        $result = $this->curlRequest($curlParam);
        $parseData = $this->xmlToArray($result);

        // 對返回結果做檢查
        if (!isset($parseData['retcode']) || !isset($parseData['retmsg'])) {
            throw new PaymentException('No withdraw return parameter specified', 150180209);
        }

        // 餘額不足
        if ($parseData['retcode'] == '207538') {
            throw new PaymentException('Insufficient balance', 150180197);
        }

        if ($parseData['retcode'] !== '00') {
            throw new PaymentConnectionException($parseData['retmsg'], 180124, $this->getEntryId());
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
            if ($this->requestData[$key] != '') {
                $encodeData[$key] = $this->requestData[$key];
            }
        }

        ksort($encodeData);
        $encodeData['key'] = $this->privateKey;
        $encodeStr = urldecode(http_build_query($encodeData));

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
            if ($this->withdrawRequestData[$key] != '') {
                $encodeData[$key] = $this->withdrawRequestData[$key];
            }
        }

        ksort($encodeData);
        $encodeData['key'] = $this->privateKey;
        $encodeStr = urldecode(http_build_query($encodeData));

        return md5($encodeStr);
    }
}

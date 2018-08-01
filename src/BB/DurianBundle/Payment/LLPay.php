<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 連連支付
 *
 * 支付驗證：
 * 1. 驗證不可為空的參數
 * 2. 設定參數
 * 3. 額外處理的參數
 * 4. 設定encodeStr(加密後的字串)
 *
 * 解密驗證：
 * 1. 驗證key
 * 2. 設定參數
 * 3. 驗證結果是否相符
 */
class LLPay extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'version'       => '1.7', //版本號
        'oid_partner'   => '', //商號
        'user_id'       => '', //使用者id
        'timestamp'     => '', //時間戳，服務器時間(格式為YmdHis)
        'sign_type'     => 'MD5', //簽名方式
        'sign'          => '', //加密簽名
        'busi_partner'  => '', //商戶服務類型(只能開放一種設定)
        'no_order'      => '', //訂單號
        'dt_order'      => '', //訂單時間(格式為YmdHis)
        'name_goods'    => '', //商品名稱，可空。
        'info_order'    => '', //訂單描述，可空。
        'money_order'   => '', //交易金額
        'notify_url'    => '', //伺服器通知URL
        'url_return'    => '', //網頁跳轉URL，可空。
        'userreq_ip'    => '', //會員瀏覽器IP
        'url_order'     => '', //訂單url，可空。
        'valid_order'   => '', //訂單有效時間，可空。
        'bank_code'     => '', //銀行代碼，可空。
        'pay_type'      => '', //支付方式，可空。
        'no_agree'      => '', //簽約協議號，可空。
        'shareing_data' => '', //分帳信息數據，可空。
        'risk_item'     => '', //風險控制參數，可空。
        'id_type'       => '', //證件類型，可空。
        'id_no'         => '', //證件號碼，可空。
        'acct_name'     => '', //銀行帳號姓名，可空。
        'flag_modify'   => '' //修改標記，可空。
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'oid_partner' => 'number',
        'user_id' => 'username',
        'no_order' => 'orderId',
        'dt_order' => 'orderCreateDate',
        'money_order' => 'amount',
        'notify_url' => 'notify_url',
        'userreq_ip' => 'ip',
        'bank_code' => 'paymentVendorId'
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'oid_partner' => 1,
        'sign_type' => 1,
        'dt_order' => 1,
        'no_order' => 1,
        'oid_paybill' => 1,
        'money_order' => 1,
        'result_pay' => 1,
        'settle_date' => 0,
        'info_order' => 0,
        'pay_type' => 0,
        'bank_code' => 0
    ];

    /**
     * 應答機制訊息
     *
     * @var string
     */
    protected $msg = '{"ret_code":"0000","ret_msg":"交易成功"}';

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        '1'   => '01020000', //中國工商銀行
        '2'   => '03010000', //交通銀行
        '3'   => '01030000', //中國農業銀行
        '4'   => '01050000', //中國建設銀行
        '5'   => '03080000', //招商銀行
        '6'   => '03050000', //中國民生銀行
        '8'   => '03100000', //上海浦東發展銀行
        '9'   => '04031000', //北京銀行
        '10'  => '03090000', //興業銀行
        '11'  => '03020000', //中信銀行
        '12'  => '03030000', //中國光大銀行
        '13'  => '03040000', //華夏銀行
        '14'  => '03060000', //廣東發展銀行
        '15'  => '03070000', //平安銀行
        '16'  => '01000000', //中國郵政
        '17'  => '01040000', //中國銀行
        '19'  => '04012900', //上海銀行
        '222' => '04083320', //寧波銀行
        '224' => '04123330', //温州銀行
        '226' => '04243010' //南京銀行
    ];

    /**
     * 查詢時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $trackingRequestData = [
        'oid_partner'   => '', //商號
        'sign_type'     => 'MD5', //簽名方式
        'sign'          => '', //加密簽名
        'no_order'      => '', //商戶訂單號
        'dt_order'      => '', //訂單時間，可空。
        'oid_paybill'   => '', //連連支付平台訂單號，可空。
        'query_version' => '1.7' //版本號，可空(默認為1.0)。
    ];

    /**
     * 查詢時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $trackingRequireMap = [
        'oid_partner' => 'number',
        'no_order' => 'orderId',
        'dt_order' => 'orderCreateDate'
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

        $this->options['notify_url'] = sprintf(
            '%s?pay_system=%s&hallid=%s',
            $this->options['notify_url'],
            $this->options['merchantId'],
            $this->options['domain']
        );

        // 從內部給定值到參數
        foreach ($this->requireMap as $paymentKey => $internalKey) {
            $this->requestData[$paymentKey] = $this->options[$internalKey];
        }

        //帶入未支援的銀行就噴例外
        if (!array_key_exists($this->requestData['bank_code'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 取得商家附加設定值
        $merchantExtraValues = $this->getMerchantExtraValue(['businessType']);

        /**
         * 1. 因目前訂單時間是伺服器的時間產生，timestamp需要的也是伺服器的時間，
         *    所以這邊把dt_order和timestamp設同一個值，只要誤差30分鐘內都可以正常提交
         * 2. 金額精確到分(保留小數點後兩位)
         * 3. 商戶業務類型(busi_partner)，記錄在merchant_extra裡
         */
        $date = new \DateTime($this->requestData['dt_order']);
        $this->requestData['dt_order'] = $date->format("YmdHis");
        $this->requestData['timestamp'] = $date->format("YmdHis");
        $this->requestData['money_order'] = sprintf('%.2f', $this->requestData['money_order']);
        $this->requestData['busi_partner'] = $merchantExtraValues['businessType'];
        $this->requestData['bank_code'] = $this->bankMap[$this->requestData['bank_code']];

        //設定支付平台需要的加密串
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

        //組織加密串
        foreach (array_keys($this->decodeParams) as $paymentKey) {
            //如果有key而且不是空值的參數才需要做加密
            if (array_key_exists($paymentKey, $this->options) && $this->options[$paymentKey] != '') {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        //針對$encodeData按字母做升序排列
        ksort($encodeData);

        $encodeData['key'] = $this->privateKey;

        //依key1=value1&key2=value2&...&keyN=valueN之後做md5
        $encodeStr = urldecode(http_build_query($encodeData));

        //如果沒有簽名擋也要丟例外(其他參數都在前面驗證過了，所以不須要驗證)
        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['sign'] != md5($encodeStr)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['result_pay'] != 'SUCCESS') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['no_order'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['money_order'] != $entry['amount']) {
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
        $this->trackingVerify();

        // 從內部給定值到參數
        foreach ($this->trackingRequireMap as $paymentKey => $internalKey) {
            $this->trackingRequestData[$paymentKey] = $this->options[$internalKey];
        }

        // 訂單時間格式為(YmdHis)
        $date = new \DateTime($this->trackingRequestData['dt_order']);
        $this->trackingRequestData['dt_order'] = $date->format("YmdHis");
        $this->trackingRequestData['sign'] = $this->trackingEncode();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'method' => 'POST',
            'uri' => '/traderapi/orderquery.htm',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => json_encode($this->trackingRequestData),
            'header' => ['Content-Type' => 'application/json']
        ];

        // 取得訂單查詢結果
        $result = $this->curlRequest($curlParam);
        $parseData = $this->parseData($result);

        // 如果沒有result_pay要丟例外
        if (!isset($parseData['result_pay'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        // 訂單未支付
        if ($parseData['result_pay'] == 'WAITING') {
            throw new PaymentConnectionException('Unpaid order', 180062, $this->getEntryId());
        }

        // 訂單處理中
        if ($parseData['result_pay'] == 'PROCESSING') {
            throw new PaymentConnectionException('Order Processing', 180059, $this->getEntryId());
        }

        // 訂單已退款
        if ($parseData['result_pay'] == 'REFUND') {
            throw new PaymentConnectionException('Order has been refunded', 180078, $this->getEntryId());
        }

        // SUCCESS為成功，防止有其他的錯誤碼，因此設定非SUCCESS即為失敗
        if ($parseData['result_pay'] != 'SUCCESS') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        // 與技術確認，以下參數都不納入加密
        $excludeKey = [
            'sign',
            'bank_name',
            'memo'
        ];

        // 組織加密簽名，排除$excludeKey，其他非空的參數都要納入加密
        foreach ($parseData as $key => $value) {
            if (!in_array($key, $excludeKey) && trim($value) != '') {
                $encodeData[$key] = $value;
            }
        }

        // 針對$encodeData按字母做升序排列
        ksort($encodeData);
        $encodeData['key'] = $this->privateKey;

        // 依key1=value1&key2=value2&...&keyN=valueN之後做md5
        $encodeStr = urldecode(http_build_query($encodeData));

        // 如果沒有Sign丟例外
        if (!isset($parseData['sign'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        if ($parseData['sign'] != md5($encodeStr)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($parseData['money_order'] != $this->options['amount']) {
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

        //組織加密簽名，排除sign(加密簽名)，其他非空的參數都要納入加密
        foreach ($this->requestData as $key => $value) {
            if ($key != 'sign' && trim($value) != '') {
                $encodeData[$key] = $value;
            }
        }

        //針對$encodeData按字母做升序排列
        ksort($encodeData);

        $encodeData['key'] = $this->privateKey;

        //依key1=value1&key2=value2&...&keyN=valueN之後做md5
        $encodeStr = urldecode(http_build_query($encodeData));
        return md5($encodeStr);
    }

    /**
     * 訂單查詢時的加密
     *
     * @return string
     */
    protected function trackingEncode()
    {
        $encodeData = [];

        //組織加密簽名，排除sign(加密簽名)，其他非空的參數都要納入加密
        foreach ($this->trackingRequestData as $key => $value) {
            if ($key != 'sign' && trim($value) != '') {
                $encodeData[$key] = $value;
            }
        }

        //針對$encodeData按字母做升序排列
        ksort($encodeData);

        $encodeData['key'] = $this->privateKey;

        //依key1=value1&key2=value2&...&keyN=valueN之後做md5
        $encodeStr = urldecode(http_build_query($encodeData));
        return md5($encodeStr);
    }

    /**
     * 入款查詢時使用，用來分解訂單查詢時回傳的json格式
     *
     * @param string $content json格式的回傳值
     * @return array
     */
    private function parseData($content)
    {
        $parseData = json_decode(urldecode($content), true);

        return $parseData;
    }
}

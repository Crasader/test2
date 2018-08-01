<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 寶金
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
class Baokimpay extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'api_username'           => '',              //api名稱
        'api_password'           => '',              //api密碼
        'merchant_id'            => '',              //商家編號
        'bk_seller_email'        => '',              //寶金賣家email
        'order_id'               => '',              //訂單標號
        'total_amount'           => '',              //總金額
        'tax_fee'                => 0,               //稅費
        'shipping_fee'           => 0,               //運費
        'order_description'      => '',              //訂單描述
        'currency_code'          => 'VND',           //currency code
        'bank_payment_method_id' => '',              //銀行id
        'payment_mode'           => 1,               //支付模式
        'payer_name'             => '',              //支付者名稱
        'payer_email'            => 'example@email', //支付者email
        'payer_phone_no'         => '1557813549',    //支付者電話號碼
        'shipping_address'       => '',              //商品運送地址
        'payer_message'          => '',              //支付者訊息
        'escrow_timeout'         => 7,               //第三方逾時
        'extra_fields_value'     => '',              //額外欄位值
        'url_return'             => ''               //支付成功的接收地扯
    ];

    /**
     * 用來驗證支付是否成功的BPN參數
     *
     * @var array
     */
    protected $bpnFields = [
        'created_on'            => '', //建立交易的時間點
        'customer_address'      => '', //支付者地址
        'customer_email'        => '', //支付者EMAIL
        'customer_name'         => '', //支付者姓名
        'customer_phone'        => '', //支付者電話
        'fee_amount'            => '', //寶金收取之手續費
        'merchant_address'      => '', //接金流網站地址
        'merchant_email'        => '', //接金流網站mail
        'merchant_id'           => '', //接金流網站編號
        'merchant_name'         => '', //接金流網站名稱
        'merchant_phone'        => '', //接金流網站電話
        'net_amount'            => '', //賣家實際收取金額
        'order_amount'          => '', //訂單金額
        'order_id'              => '', //支付訂單號
        'payment_type'          => '', //支付方式
        'total_amount'          => '', //買家支付總金額
        'transaction_id'        => '', //流水號
        'transaction_status'    => '', //交易狀態
        'usd_vnd_exchange_rate' => '', //貨幣匯率
        'verify_sign'           => '', //加密碼用來確認寶金系統資料
        'bpn_id'                => ''  //bpn編號
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'merchant_id' => 'number',
        'order_id' => 'orderId',
        'total_amount' => 'amount',
        'payer_name' => 'username',
        'order_description' => 'username',
        'payer_message' => 'username',
        'bank_payment_method_id' => 'paymentVendorId',
        'url_return' => 'notify_url',
        'shipping_address' => 'domain'
    ];

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        237 => '30',  //Vietcombank
        238 => '36',  //TECHCOMBANK
        239 => '65',  //MILITARY BANK
        240 => '47',  //VIB BANK
        241 => '59',  //EXIMBANK
        242 => '54',  //ASIA COMMERICAL BANK
        243 => '96',  //NAM A BANK
        244 => '29',  //JCB
        245 => '85',  //MasterCard
        246 => '86',  //Visa
        247 => '105', //MARITIME BANK
        248 => '32',  //DONGA BANK
        249 => '94',  //HDBank
        250 => '102', //PG BANK
        251 => '97',  //Saigobank
        252 => '106'  //NAVIBANK
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

        //額外的驗證項目
        if (trim($this->options['merchantId']) == '') {
            throw new PaymentException('No pay parameter specified', 180145);
        }

        if (trim($this->options['postUrl']) == '') {
            throw new PaymentException('No pay parameter specified', 180145);
        }

        //帶入未支援的銀行就噴例外
        if (!array_key_exists($this->requestData['bank_payment_method_id'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        //額外的參數設定
        $this->requestData['order_id'] = sprintf(
            '%s_%s_%s',
            $this->requestData['order_id'],
            $this->options['merchantId'],
            $this->requestData['shipping_address']
        );

        $this->requestData['bank_payment_method_id'] = $this->bankMap[$this->requestData['bank_payment_method_id']];

        // 取得商家附加設定值
        $names = ['api_username', 'api_password', 'bk_seller_email'];
        $merchantExtraValues = $this->getMerchantExtraValue($names);

        $this->requestData['api_username'] = $merchantExtraValues['api_username'];
        $this->requestData['api_password'] = $merchantExtraValues['api_password'];
        $this->requestData['bk_seller_email'] = $merchantExtraValues['bk_seller_email'];

        // 取得跳轉網址
        $nusoapParam = [
            'serverIp' => $this->options['verify_ip'],
            'host' => $this->options['postUrl'],
            'uri' => '/services/payment_pro_2/init?wsdl',
            'function' => 'DoPaymentPro',
            'callParams' => [$this->requestData],
            'wsdl' => false,
        ];

        $result = $this->soapRequest($nusoapParam);

        //驗證回傳參數
        if (!isset($result['error_code'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        if (!isset($result['error_message'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        if (!isset($result['url_redirect'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        $errorCode = $result['error_code'];
        $errorMsg  = $result['error_message'];
        $returnUrl = $result['url_redirect'];

        //如果取得url異常就丟出錯誤訊息
        if ($errorCode !== '0') {
            throw new PaymentConnectionException($errorMsg, 180130, $this->getEntryId());
        }

        $this->requestData['act_url'] = $returnUrl;

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

        //確認需要驗證的參數都有帶入$bpnFields
        if (!isset($this->options['pay_system'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if (!isset($this->options['hallid'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        foreach ($this->bpnFields as $key => $value) {
            if (!array_key_exists($key, $this->options)) {
                throw new PaymentException('No return parameter specified', 180137);
            }

            $this->bpnFields[$key] = $this->options[$key];
        }

        $this->bpnFields['order_id'] = sprintf(
            '%s_%s_%s',
            $this->options['order_id'],
            $this->options['pay_system'],
            $this->options['hallid']
        );

        /**
         * 用BPN做驗證是否為寶金的返回
         * url: https://www.baokim.vn/bpn/verify
         */
        $curlParam = [
            'method' => 'GET',
            'uri' => '/bpn/verify',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => http_build_query($this->bpnFields),
            'header' => []
        ];

        $result = $this->curlRequest($curlParam);

        if ($result == '' || !strstr($result, 'VERIFIED')) {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        $verify = explode(',', $result);
        $verifyStatus = $verify[0];
        $tranStatus = $verify[1];
        $returnOrderId = $verify[2];

        if ($verifyStatus != 'VERIFIED' || ($tranStatus != 4 && $tranStatus != 13)) {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($returnOrderId != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['total_amount'] != $entry['amount']) {
            throw new PaymentException('Order Amount error', 180058);
        }
    }
}

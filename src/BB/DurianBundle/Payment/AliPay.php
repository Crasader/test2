<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 支付寶支付
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
class AliPay extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'service'            => 'create_direct_pay_by_user', //接口名稱
        'payment_type'       => '1', //支付類型
        'partner'            => '', //合作夥伴ID
        'seller_email'       => '', //賣家Email
        'return_url'         => '', //返回URL
        'notify_url'         => '', //通知URL
        '_input_charset'     => 'utf-8', //參數編碼
        'show_url'           => '', //商品展示URL
        'out_trade_no'       => '', //外部交易號
        'subject'            => 'subject', //商品標題(只需要非空，因此把值跟key設定成一樣)
        'body'               => 'body', //商品描述(只需要非空，因此把值跟key設定成一樣)
        'total_fee'          => '', //交易金額
        'paymethod'          => 'bankPay', //支付方式, bankPay: 網銀直連
        'defaultbank'        => '', //網銀代碼
        'anti_phishing_key'  => '', //防釣魚時間戳
        'exter_invoke_ip'    => '', //公用回傳參數
        'buyer_email'        => '', //買家Email
        'extra_common_param' => '', //公用回傳參數
        'sign'               => '', //簽名
        'sign_type'          => 'MD5', //簽名方式
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'partner' => 'number',
        'total_fee' => 'amount',
        'out_trade_no' => 'orderId',
        'notify_url' => 'notify_url',
        'defaultbank' => 'paymentVendorId'
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'service',
        'payment_type',
        'partner',
        'seller_email',
        'return_url',
        'notify_url',
        '_input_charset',
        'show_url',
        'out_trade_no',
        'subject',
        'body',
        'total_fee',
        'paymethod',
        'defaultbank',
        'anti_phishing_key',
        'exter_invoke_ip',
        'buyer_email',
        'extra_common_param'
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'trade_no' => 0,
        'out_trade_no' => 1,
        'total_fee' => 1,
        'subject' => 0,
        'seller_email' => 0,
        'seller_id' => 0,
        'buyer_email' => 0,
        'buyer_id' => 0,
        'trade_status' => 1,
        'notify_id' => 0,
        'notify_time' => 0,
        'notify_type' => 0,
        'payment_type' => 0,
        'body' => 0,
        'price' => 0,
        'quantity' => 0,
        'discount' => 0,
        'gmt_create' => 0,
        'gmt_payment' => 0,
        'gmt_close' => 0,
        'is_total_fee_adjust' => 0,
        'use_coupon' => 0,
        'exterface' => 0,
        'is_success' => 0,
        'refund_status' => 0,
        'gmt_refund' => 0,
        'error_code' => 0,
        'extra_common_param' => 0,
        'bank_seq_no' => 0
    ];

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        '1'  => 'ICBCB2C', //中國工商銀行.
        '2'  => 'COMM', //交通銀行.
        '3'  => 'ABC', //中國農業銀行.
        '4'  => 'CCB', //中國建設銀行.
        '5'  => 'CMB', //招商銀行.
        '6'  => 'CMBC', //中國民生銀行.
        '7'  => 'SDB', //深圳發展銀行.
        '8'  => 'SPDB', //上海浦東發展銀行.
        '10' => 'CIB', //興業銀行.
        '11' => 'CITIC', //中信銀行.
        '12' => 'CEBBANK', //中國光大銀行.
        '14' => 'GDB', //廣東發展銀行.
        '15' => 'SPABANK', //深圳平安銀行.
        '17' => 'BOCB2C', //中國銀行
        '19' => 'SHBANK', //上海銀行.
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
        if (!array_key_exists($this->requestData['defaultbank'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 取得商家附加設定值
        $merchantExtraValues = $this->getMerchantExtraValue(['alipayUserName']);

        //額外的參數設定
        $this->requestData['total_fee'] = sprintf('%.2f', $this->requestData['total_fee']);
        $this->requestData['defaultbank'] = $this->bankMap[$this->requestData['defaultbank']];
        $this->requestData['seller_email'] = $merchantExtraValues['alipayUserName'];

        //空值參數不傳遞
        foreach ($this->requestData as $key => $value) {
            if (trim($value) == '') {
                unset($this->requestData[$key]);
            }
        }

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

        //依key1=value1&key2=value2&...&keyN=valueN之後做md5
        $encodeStr = urldecode(http_build_query($encodeData));
        $encodeStr .= $this->privateKey;

        //沒有sign就要丟例外，其他的參數在組織加密串的時候驗證過了
        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['sign'] != md5($encodeStr)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['trade_status'] != 'TRADE_FINISHED' && $this->options['trade_status'] != 'TRADE_SUCCESS') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['out_trade_no'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['total_fee'] != $entry['amount']) {
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
         * 組織加密簽名，排除sign(加密簽名)、sign_type(簽名方式)，
         * 其他非空的參數都要納入加密
         */
        foreach ($this->requestData as $key => $value) {
            if ($key != 'sign' && $key != 'sign_type' && trim($value) != '') {
                $encodeData[$key] = $value;
            }
        }

        //針對$encodeData按字母做升序排列
        ksort($encodeData);

        //依key1=value1&key2=value2&...&keyN=valueN之後做md5
        $encodeStr = urldecode(http_build_query($encodeData));
        $encodeStr .= $this->privateKey;

        return md5($encodeStr);
    }
}

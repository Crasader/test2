<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 易生支付
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
class EasyPay extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'service'        => 'create_direct_pay_by_user', //接口名稱
        'partner'        => '', //合作夥伴ID
        'notify_url'     => '', //通知URL
        'return_url'     => '', //返回URL
        '_input_charset' => 'utf-8', //參數編碼
        'subject'        => 'subject', //商品標題(只需要非空，因此把值跟key設定成一樣)
        'body'           => 'body', //商品描述(只需要非空，因此把值跟key設定成一樣)
        'out_trade_no'   => '', //外部交易號
        'total_fee'      => '', //交易金額
        'payment_type'   => '1', //支付類型
        'paymethod'      => 'bankDirect', //支付方式, bankDirect: 網銀直連
        'defaultbank'    => '', //網銀代碼
        'seller_email'   => '', //賣家email
        'sign'           => '', //簽名
        'sign_type'      => 'MD5' //簽名方式
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'partner' => 'number',
        'notify_url' => 'notify_url',
        'return_url' => 'notify_url',
        'out_trade_no' => 'orderId',
        'total_fee' => 'amount',
        'defaultbank' => 'paymentVendorId'
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'service',
        'partner',
        'notify_url',
        'return_url',
        '_input_charset',
        'subject',
        'body',
        'out_trade_no',
        'total_fee',
        'payment_type',
        'paymethod',
        'defaultbank',
        'seller_email'
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'body' => 0,
        'subject' => 0,
        'is_total_fee_adjust' => 0,
        'notify_type' => 0,
        'out_trade_no' => 1,
        'buyer_email' => 0,
        'total_fee' => 1,
        'seller_actions' => 0,
        'quantity' => 0,
        'buyer_id' => 0,
        'trade_no' => 0,
        'notify_time' => 0,
        'gmt_payment' => 0,
        'trade_status' => 1,
        'discount' => 0,
        'is_success' => 1,
        'gmt_create' => 0,
        'price' => 0,
        'seller_id' => 0,
        'seller_email' => 0,
        'notify_id' => 0,
        'gmt_logistics_modify' => 0,
        'payment_type' => 0
    ];

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        '1'   => 'ICBC', //中國工商銀行
        '2'   => 'HSBC', //交通銀行
        '3'   => 'ABC', //中國農業銀行
        '4'   => 'CCB', //中國建設銀行
        '5'   => 'CMB', //招商銀行
        '6'   => 'CMBC', //中國民生銀行
        '7'   => 'SDB', //深圳發展銀行
        '8'   => 'SPDB', //上海浦東發展銀行
        '10'  => 'CIB', //興業銀行
        '11'  => 'CITIC', //中信銀行
        '12'  => 'CEB', //中國光大銀行
        '13'  => 'HXB', //華夏銀行
        '14'  => 'GDB', //廣東發展銀行
        '15'  => 'SPA', //平安银行
        '16'  => 'PSBC', //中國郵政
        '17'  => 'BOC', //中國銀行
        '217' => 'BHBK', //渤海銀行
        '218' => 'DGCBK', //東莞銀行
        '219' => 'GZCBK', //廣州銀行
        '220' => 'HZBANK', //杭州銀行
        '222' => 'NBBK', //寧波銀行
        '223' => 'BEA', //東亞銀行
        '224' => 'WZMBK', //溫州銀行
        '226' => 'NJB', //南京銀行
        '228' => 'SHRCB' //上海市農商行
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
        $merchantExtraValues = $this->getMerchantExtraValue(['seller_email']);

        //額外的參數設定
        $this->requestData['total_fee'] = sprintf("%.2f", $this->requestData['total_fee']);
        $this->requestData['defaultbank'] = $this->bankMap[$this->requestData['defaultbank']];
        $this->requestData['seller_email'] = $merchantExtraValues['seller_email'];

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

        //以下參數必須驗證有index，如果沒有index就丟例外
        $verifyIndex = [
            'is_success', //返回時不可空
            'sign', //返回時不可空
            'sign_type', //返回時不可空
            'trade_status', //如果缺少無法驗證，所以要檢查
            'out_trade_no', //如果缺少無法驗證，所以要檢查
            'total_fee' //如果缺少無法驗證，所以要檢查
        ];

        foreach ($verifyIndex as $index) {
            if (!isset($this->options[$index])) {
                throw new PaymentException('No return parameter specified', 180137);
            }
        }

        $encodeData = [];

        //組織加密串
        foreach (array_keys($this->decodeParams) as $index) {
            //如果有index，而且不是空值的參數才需要做加密
            if (array_key_exists($index, $this->options) && $this->options[$index] != '') {
                $encodeData[$index] = $this->options[$index];
            }
        }

        //進行加密
        ksort($encodeData);
        $encodeStr = urldecode(http_build_query($encodeData));
        $encodeStr .= $this->privateKey;

        if ($this->options['sign'] != md5($encodeStr)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['trade_status'] != 'TRADE_FINISHED') {
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
        sort($this->encodeParams);
        $encodeData = [];

        foreach ($this->encodeParams as $value) {
            $encodeData[$value] = $this->requestData[$value];
        }

        $encodeStr = urldecode(http_build_query($encodeData));
        $encodeStr .= $this->privateKey;

        return md5($encodeStr);
    }
}

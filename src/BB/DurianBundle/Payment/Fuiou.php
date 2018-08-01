<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 富友
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
class Fuiou extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'mchnt_cd'          => '',      //商戶代碼
        'order_id'          => '',      //商戶訂單號
        'order_amt'         => '',      //交易金額
        'order_pay_type'    => 'B2C',   //支付類型
        'page_notify_url'   => '',      //頁面跳轉URL
        'back_notify_url'   => '',      //後台通知URL
        'order_valid_time'  => '',      //超時時間
        'iss_ins_cd'        => '',      //銀行代碼
        'goods_name'        => '',      //商品名稱
        'goods_display_url' => '',      //商品展示網址
        'rem'               => '',      //備註
        'ver'               => '1.0.1', //版本號
        'md5'               => ''       //MD5摘要數據
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'mchnt_cd' => 'number',
        'order_id' => 'orderId',
        'order_amt' => 'amount',
        'page_notify_url' => 'notify_url',
        'back_notify_url' => 'notify_url',
        'iss_ins_cd' => 'paymentVendorId',
        'goods_name' => 'username'
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'mchnt_cd',
        'order_id',
        'order_amt',
        'order_pay_type',
        'page_notify_url',
        'back_notify_url',
        'order_valid_time',
        'iss_ins_cd',
        'goods_name',
        'goods_display_url',
        'rem',
        'ver'
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'mchnt_cd' => 1,
        'order_id' => 1,
        'order_date' => 1,
        'order_amt' => 1,
        'order_st' => 1,
        'order_pay_code' => 1,
        'order_pay_error' => 1,
        'resv1' => 1,
        'fy_ssn' => 1
    ];

    /**
     * 應答機制訊息
     *
     * @var string
     */
    protected $msg = '[Succeed]';

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        '1'    => '0801020000', //中國工商銀行
        '2'    => '0803010000', //交通銀行
        '3'    => '0801030000', //中國農業銀行
        '4'    => '0801050000', //中國建設銀行
        '5'    => '0803080000', //招商銀行
        '6'    => '0803050000', //中國民生銀行
        '8'    => '0803100000', //上海浦東發展銀行
        '10'   => '0803090000', //興業銀行
        '11'   => '0803020000', //中信銀行
        '12'   => '0803030000', //中國光大銀行
        '13'   => '0803040000', //華夏銀行
        '14'   => '0803060000', //廣東發展銀行
        '16'   => '0801000000', //中國郵政
        '17'   => '0801040000', //中國銀行
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
        if (!array_key_exists($this->requestData['iss_ins_cd'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        //額外的參數設定
        $this->requestData['order_amt'] = round($this->options['amount'] * 100);
        $this->requestData['iss_ins_cd'] = $this->bankMap[$this->requestData['iss_ins_cd']];

        //設定支付平台需要的加密串
        $this->requestData['md5'] = $this->encode();

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
            if (array_key_exists($paymentKey, $this->options)) {
                $encodeData[] = $this->options[$paymentKey];
            }
        }

        $encodeData[] = $this->privateKey;
        $encodeStr = implode('|', $encodeData);

        //如果沒有md5也要丟例外(其他參數都在前面驗證過了，所以不須要驗證)
        if (!isset($this->options['md5'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['md5'] != md5($encodeStr)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['order_st'] != '11') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['order_id'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['order_amt'] != round($entry['amount'] * 100)) {
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

        //加密設定
        foreach ($this->encodeParams as $index) {
            $encodeData[] = $this->requestData[$index];
        }

        //額外的加密設定
        $encodeData[] = $this->privateKey;
        $encodeStr = implode('|', $encodeData);

        //存入加密後的字串
        return md5($encodeStr);
    }
}

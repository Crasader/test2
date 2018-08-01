<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 中付寶
 */
class ZhongFuBao extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'version' => '1.0', // 版本號
        'customerid' => '', // 商戶號
        'sdorderno' => '', // 訂單號
        'total_fee' => '', // 付款金額
        'paytype' => 'bank', // 支付類型，預設網銀bank
        'bankcode' => '', // 銀行編碼，網銀直連不可空，其他支付方式可空
        'notifyurl' => '', // 異步通知
        'returnurl' => '', // 同步通知
        'remark' => '', // 附加參數，可空
        'sign' => '', // 簽名
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'customerid' => 'number',
        'sdorderno' => 'orderId',
        'total_fee' => 'amount',
        'bankcode' => 'paymentVendorId',
        'notifyurl' => 'notify_url',
        'returnurl' => 'notify_url',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'version',
        'customerid',
        'total_fee',
        'sdorderno',
        'notifyurl',
        'returnurl',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'customerid' => 1,
        'status' => 1,
        'sdpayno' => 1,
        'sdorderno' => 1,
        'total_fee' => 1,
        'paytype' => 1,
    ];

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        1 => 'ICBC', // 中國工商銀行
        2 => 'BOCOM', // 交通銀行
        3 => 'ABC', // 中國農業銀行
        4 => 'CCB', // 中國建設銀行
        5 => 'CMB', // 招商銀行
        6 => 'CMBC', // 中國民生銀行
        8 => 'SPDB', // 上海浦東發展銀行
        9 => 'BCCB', // 北京銀行
        10 => 'CIB', // 興業銀行
        11 => 'CNCB', // 中信銀行
        12 => 'CEB', // 中國光大銀行
        13 => 'HXB', // 華夏銀行
        14 => 'GDB', // 廣東發展銀行
        15 => 'PAB', // 平安銀行
        16 => 'PSBC', // 中國郵政儲蓄
        17 => 'BOCSH', // 中國銀行
        19 => 'BOS', // 上海銀行
        228 => 'SRCB', // 上海農村商業銀行
        1090 => 'weixin', // 微信_二維
        1092 => 'alipay', // 支付寶_二維
        1097 => 'wxh5', // 微信_手機支付
        1103 => 'qqrcode', // QQ_二維
        1104 => 'qqwallet', // QQ_手機支付
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
        if (!array_key_exists($this->requestData['bankcode'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外參數設定
        $this->requestData['bankcode'] = $this->bankMap[$this->requestData['bankcode']];
        $this->requestData['total_fee'] = sprintf('%.2f', $this->requestData['total_fee']);

        // 二維、手機支付參數設定
        if (in_array($this->options['paymentVendorId'], [1090, 1092, 1097, 1103, 1104])) {
            $this->requestData['paytype'] = $this->requestData['bankcode'];
            $this->requestData['bankcode'] = '';
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

        $this->payResultVerify();

        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options)) {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        $encodeStr = urldecode(http_build_query($encodeData));
        $encodeStr .=  '&' . $this->privateKey;

        //如果沒有返回簽名要丟例外
        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['sign'] != md5($encodeStr)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['status'] != '1') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['sdorderno'] != $entry['id']) {
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

        foreach ($this->encodeParams as $paymentKey) {
            $encodeData[$paymentKey] = $this->requestData[$paymentKey];
        }

        $encodeStr = urldecode(http_build_query($encodeData));
        $encodeStr .= '&' . $this->privateKey;

        return md5($encodeStr);
    }
}

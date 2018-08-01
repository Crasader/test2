<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 御投支付
 */
class YuTouPay extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'merNo' => '', // 商戶編號
        'orderNo' => '', // 訂單號
        'amount' => '', // 訂單金額整數，單位:元
        'returnUrl' => '', // 同步返回地址
        'notifyUrl' => '', // 異步返回地址
        'payType' => 'WY', // 支付類型，網銀:WY
        'sign' => '', // 簽名
        'isDirect' => '0', // 是否直連預設0非直連
        'bankSegment' => '', // 銀行編碼，網銀必填
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'merNo' => 'number',
        'orderNo' => 'orderId',
        'amount' => 'amount',
        'returnUrl' => 'notify_url',
        'notifyUrl' => 'notify_url',
        'bankSegment' => 'paymentVendorId',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'merNo',
        'merSecret',
        'amount',
        'payType',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'status' => 1,
        'payType' => 1,
        'orderNo' => 1,
        'orderStatus' => 1,
        'orderAmount' => 1,
        'payoverTime' => 1,
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
        '1' => '102', // 中國工商銀行
        '2' => '301', // 交通銀行
        '3' => '103', // 中國農業銀行
        '4' => '105', // 中國建設銀行
        '5' => '308', // 招商銀行
        '6' => '305', // 中國民生銀行
        '8' => '310', // 上海浦東發展銀行
        '9' => '313', // 北京銀行
        '10' => '309', // 興業銀行
        '11' => '302', // 中信銀行
        '12' => '303', // 中國光大銀行
        '13' => '304', // 華夏銀行
        '14' => '306', // 廣東發展銀行
        '15' => '307', // 平安銀行
        '16' => '403', // 中國郵政
        '17' => '104', // 中國銀行
        '19' => '325', // 上海銀行
        '217' => '318', // 渤海銀行
        '221' => '316', // 浙商银行
        '278' => 'KUAIJIE', // 銀聯在線
        '308' => '440', // 徽商銀行
        '311' => '315', // 恒丰银行
        '1088' => 'KUAIJIE', // 銀聯在線_手機支付
        '1090' => 'WEIXIN', // 微信支付_二維
        '1092' => 'ALIPAY', // 支付寶_二維
        '1097' => 'WXH5', // 微信_手機支付
        '1098' => 'ALIH5', // 支付寶_手機支付
        '1104' => 'QQH5', // QQ_手機支付
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
        if (!array_key_exists($this->requestData['bankSegment'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $this->requestData['bankSegment'] = $this->bankMap[$this->requestData['bankSegment']];

        // 手機支付、二維支付、銀聯在線調整提交參數
        if (in_array($this->options['paymentVendorId'], ['278', '1088', '1090', '1092', '1097', '1098', '1104'])) {
            $this->requestData['payType'] = $this->requestData['bankSegment'];
            unset($this->requestData['bankSegment']);
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

        $encodeData = [];

        // 組織加密串
        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options)) {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        $encodeData['merSecret'] = $this->privateKey;

        $encodeStr = urldecode(http_build_query($encodeData));

        // 沒有sign就要丟例外
        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if (strcasecmp($this->options['sign'], md5($encodeStr)) != 0) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['status'] !== '200') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['orderNo'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['orderAmount'] != $entry['amount']) {
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

        // 組織加密簽名
        foreach ($this->encodeParams as $index) {
            if ($index == 'merSecret') {
                $encodeData['merSecret'] = $this->privateKey;

                continue;
            }

            $encodeData[$index] = $this->requestData[$index];
        }

        $encodeStr = urldecode(http_build_query($encodeData));

        return md5($encodeStr);
    }
}

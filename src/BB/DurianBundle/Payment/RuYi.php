<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 如意支付
 */
class RuYi extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'merId' => '', // 商戶號
        'merOrdId' => '', // 訂單號
        'merOrdAmt' => '', // 訂單金額，精確到小數後兩位
        'payType' => '10', // 支付類型，10: 網銀
        'bankCode' => '', // 銀行代碼
        'remark' => '', // 備註訊息，必填
        'returnUrl' => '', // 同步通知網址
        'notifyUrl' => '', // 異步通知網址
        'signType' => 'MD5', // 簽名方式，默認MD5
        'signMsg' => '', // 簽名數據
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'merId' => 'number',
        'merOrdId' => 'orderId',
        'merOrdAmt' => 'amount',
        'bankCode' => 'paymentVendorId',
        'remark' => 'orderId',
        'returnUrl' => 'notify_url',
        'notifyUrl' => 'notify_url',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'merId',
        'merOrdId',
        'merOrdAmt',
        'payType',
        'bankCode',
        'remark',
        'returnUrl',
        'notifyUrl',
        'signType',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'merId' => 1,
        'merOrdId' => 1,
        'merOrdAmt' => 1,
        'sysOrdId' => 1,
        'tradeStatus' => 1,
        'remark' => 1,
        'signType' => 1,
    ];

    /**
     * 應答機制訊息
     *
     * @var string
     */
    protected $msg = 'stopnotify';

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        '1' => 'ICBC', // 中國工商銀行
        '2' => 'BCOM', // 交通銀行
        '3' => 'ABC', // 中國農業銀行
        '4' => 'CCB', // 中國建設銀行
        '5' => 'CMB', // 招商銀行
        '6' => 'CMBC', // 中國民生銀行
        '8' => 'SPDB', // 上海浦東發展銀行
        '10' => 'CIB', // 興業銀行
        '11' => 'ECITIC', // 中信銀行
        '12' => 'CEBB', // 中國光大銀行
        '13' => 'HXB', // 華夏銀行
        '14' => 'GDB', // 廣東發展銀行
        '15' => 'SPABANK', // 平安銀行
        '16' => 'PSBC', // 中國郵政
        '17' => 'BOC', // 中國銀行
        '1103' => 'QQWALLET', // QQ_二维
        '1104' => 'QQWAP', // QQ_手機支付
        '1111' => 'UNIONQR', // 銀聯_二維
    ];

    /**
     * 支付平台支援的銀行對應支付型態編號
     *
     * @var array
     */
    protected $payTypeMap = [
        '1103' => '50', // QQ_二维
        '1104' => '51', // QQ_手機支付
        '1111' => '11', // 銀聯_二維
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
        if (!array_key_exists($this->requestData['bankCode'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 非網銀
        if (in_array($this->requestData['bankCode'], [1103, 1104, 1111])) {
            $this->requestData['payType'] = $this->payTypeMap[$this->requestData['bankCode']];
        }

        // 額外的參數設定
        $this->requestData['bankCode'] = $this->bankMap[$this->requestData['bankCode']];
        $this->requestData['merOrdAmt'] = sprintf('%.2f', $this->requestData['merOrdAmt']);

        // 設定支付平台需要的加密串
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

        $this->payResultVerify();

        $encodeData = [];

        // 組織加密串
        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options)) {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        $encodeData['merKey'] = $this->privateKey;
        $encodeStr = urldecode(http_build_query($encodeData));

        // 如果沒有返回簽名要丟例外
        if (!isset($this->options['signMsg'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['signMsg'] != md5($encodeStr)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['tradeStatus'] != 'success002') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['merOrdId'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['merOrdAmt'] != $entry['amount']) {
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

        // 加密設定
        foreach ($this->encodeParams as $index) {
            if (isset($this->requestData[$index])) {
                $encodeData[$index] = $this->requestData[$index];
            }
        }

        $encodeData['merKey'] = $this->privateKey;
        $encodeStr = urldecode(http_build_query($encodeData));

        return md5($encodeStr);
    }
}

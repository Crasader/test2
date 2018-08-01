<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 新貝支付
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
class XBeiPay extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'Version' => 'V1.0', // 版本號, 固定值
        'MerchantCode' => '', // 商戶編碼
        'OrderId' => '', // 訂單號
        'Amount' => '', // 交易金額
        'AsyNotifyUrl' => '', // 異步通知URL
        'SynNotifyUrl' => '', // 同步通知URL
        'OrderDate' => '', // 訂單時間(格式為YmdHis)
        'TradeIp' => '', // 客戶IP
        'PayCode' => '', // 交易類型編碼
        'GoodsName' => '', // 商品名稱, 可空 (這邊帶入username方便業主比對)
        'SignValue' => '', // 加密字串
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'MerchantCode' => 'number',
        'OrderId' => 'orderId',
        'Amount' => 'amount',
        'AsyNotifyUrl' => 'notify_url',
        'SynNotifyUrl' => 'notify_url',
        'OrderDate' => 'orderCreateDate',
        'TradeIp' => 'ip',
        'PayCode' => 'paymentVendorId',
        'GoodsName' => 'username',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'Version',
        'MerchantCode',
        'OrderId',
        'Amount',
        'AsyNotifyUrl',
        'SynNotifyUrl',
        'OrderDate',
        'TradeIp',
        'PayCode',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'Version' => 1,
        'MerchantCode' => 1,
        'OrderId' => 1,
        'OrderDate' => 1,
        'TradeIp' => 1,
        'SerialNo' => 1,
        'Amount' => 1,
        'PayCode' => 1,
        'State' => 1,
        'FinishTime' => 1,
    ];

    /**
     * 應答機制訊息
     *
     * @var string
     */
    protected $msg = 'OK';

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        '1' => '100012', // 工商銀行
        '2' => '100015', // 交通銀行
        '3' => '100013', // 農業銀行
        '4' => '100014', // 建設銀行
        '5' => '100016', // 招商銀行
        '6' => '100018', // 民生銀行
        '8' => '100021', // 上海浦東發展銀行
        '9' => '100026', // 北京銀行
        '10' => '100020', // 興業銀行
        '11' => '100023', // 中信銀行
        '12' => '100024', // 光大銀行
        '13' => '100019', // 華夏銀行
        '14' => '100022', // 廣東發展銀行
        '15' => '100030', // 平安銀行
        '16' => '100025', // 中國郵政儲蓄
        '17' => '100017', // 中國銀行
        '19' => '100028', // 上海銀行
        '228' => '100029', // 上海農村商業銀行
        '234' => '100031', // 北京農村商業銀行
        '1090' => '100040', // 微信支付
        '1092' => '100067', // 支付寶
        '1103' => '100068', // QQ錢包
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

        // 帶入未支援的銀行就噴例外
        if (!array_key_exists($this->requestData['PayCode'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定(要送去支付平台的參數)
        $date = new \DateTime($this->requestData['OrderDate']);
        $this->requestData['OrderDate'] = $date->format('YmdHis');
        $this->requestData['PayCode'] = $this->bankMap[$this->requestData['PayCode']];

        // 設定支付平台需要的加密串
        $this->requestData['SignValue'] = $this->encode();

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

        $encodeStr = '';

        // 加密設定
        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options)) {
                $encodeStr .= $paymentKey . '=[' . $this->options[$paymentKey] . ']';
            }
        }

        $encodeStr .= 'TokenKey=[' . $this->privateKey . ']';

        // 沒有signValue就要丟例外，其他的參數在組織加密串的時候驗證過了
        if (!isset($this->options['SignValue'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['SignValue'] != strtoupper(md5($encodeStr))) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['State'] != '8888') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['OrderId'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['Amount'] != $entry['amount']) {
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
        $encodeStr = '';

        // 加密設定
        foreach ($this->encodeParams as $index) {
            $encodeStr .= $index . '=[' . $this->requestData[$index] . ']';
        }

        $encodeStr .= 'TokenKey=[' . $this->privateKey . ']';

        return strtoupper(md5($encodeStr));
    }
}

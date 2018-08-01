<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 樂寶支付
 */
class Lelpay extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'parter' => '', // 商號
        'type' => '', // 銀行代碼
        'value' => '', // 金額，精確到小數後兩位
        'orderid' => '', // 訂單號
        'callbackurl' => '', // 異步通知url, 不能串參數
        'hrefbackurl' => '', // 同步通知url, 可空
        'payerIp' => '', // 支付用戶ip, 可空
        'attach' => '', // 備註, 可空
        'sign' => '', // 加密簽名
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'parter' => 'number',
        'type' => 'paymentVendorId',
        'value' => 'amount',
        'orderid' => 'orderId',
        'callbackurl' => 'notify_url',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'parter',
        'type',
        'value',
        'orderid',
        'callbackurl',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'orderid' => 1,
        'opstate' => 1,
        'ovalue' => 1,
    ];

    /**
     * 應答機制訊息
     *
     * @var string
     */
    protected $msg = 'opstate=0';

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        '1' => '967', // 中國工商銀行
        '2' => '981', // 交通銀行
        '3' => '964', // 中國農業銀行
        '4' => '965', // 中國建設銀行
        '5' => '970', // 招商銀行
        '6' => '980', // 中國民生銀行
        '8' => '977', // 上海浦東發展銀行
        '9' => '989', // 北京銀行
        '10' => '972', // 興業銀行
        '11' => '962', // 中信銀行
        '12' => '986', // 中國光大銀行
        '13' => '982', // 華夏銀行
        '14' => '985', // 廣東發展銀行
        '15' => '978', // 平安銀行
        '16' => '971', // 中國郵政
        '17' => '963', // 中國銀行
        '19' => '975', // 上海銀行
        '217' => '988', // 渤海銀行
        '220' => '983', // 杭州銀行
        '221' => '968', // 浙商银行
        '223' => '987', // 東亞銀行
        '226' => '979', // 南京銀行
        '227' => '984', // 廣州市農村信用合作社
        '228' => '976', // 上海市農村商業銀行
        '231' => '973', // 順德農村信用合作社
        '233' => '969', // 浙江稠州商業銀行
        '234' => '990', // 北京農商行
        '297' => '993', // 財付通
        '1090' => '1004', // 微信支付_二維
        '1092' => '1003', // 支付寶_二維
        '1097' => '1006', // 微信_手機支付
        '1098' => '1007', // 支付寶_手機支付
        '1103' => '1005', // QQ_二維
        '1104' => '1008', // QQ_手機支付
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
        if (!array_key_exists($this->requestData['type'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $this->requestData['value'] = sprintf('%.2f', $this->requestData['value']);
        $this->requestData['type'] = $this->bankMap[$this->requestData['type']];

        // 設定支付平台需要的加密串
        $this->requestData['sign'] = $this->encode();

        // 檢查是否有postUrl(支付平台提交的url)
        if (trim($this->options['postUrl']) == '') {
            throw new PaymentException('No pay parameter specified', 180145);
        }

        $params = http_build_query($this->requestData);
        $this->requestData['act_url'] = $this->options['postUrl'] . '?' . $params;

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

        // 依key1=value1&key2=value2&...&keyN=valueN之後做md5
        $encodeStr = urldecode(http_build_query($encodeData));
        $encodeStr .= $this->privateKey;

        // 如果沒有返回簽名要丟例外
        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['sign'] != md5($encodeStr)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        // 請求參數無效
        if ($this->options['opstate'] == '-1') {
            throw new PaymentConnectionException('Invalid pay parameters', 180129, $this->getEntryId());
        }

        // 簽名錯誤
        if ($this->options['opstate'] == '-2') {
            throw new PaymentConnectionException(
                'PaymentGateway error, Merchant sign error',
                180127,
                $this->getEntryId()
            );
        }

        if ($this->options['opstate'] !== '0') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['orderid'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['ovalue'] != $entry['amount']) {
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
            $encodeData[$index] = $this->requestData[$index];
        }

        // 依key1=value1&key2=value2&...&keyN=valueN之後做md5
        $encodeStr = urldecode(http_build_query($encodeData));
        $encodeStr .= $this->privateKey;

        return md5($encodeStr);
    }
}

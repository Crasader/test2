<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * SDpay
 */
class SDPay extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'cmd' => '6006', // 存款編碼，6006:個人計算機
        'merchantid' => '', // 商戶號
        'language' => 'zh-cn', // 語言編碼，zh-cn:簡體中文
        'userinfo' => '', // 用戶資料
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'merchantid' => 'number',
    ];

    /**
     * 用戶資料參數
     *
     * @var array
     */
    private $userinfoData = [
        'order' => '', // 訂單號
        'username' => '', // 用戶名稱
        'money' => '', // 金額，精確到小數點兩位
        'unit' => '1', // 貨幣單位，人民幣:1
        'time' => '', // 提交時間
        'remark' => '', // 備註
        'backurl' => '', // 異步通知地址
        'backurlbrowser' => '', // 同步通知地址，可空
    ];

    /**
     * 用戶資料參數與內部參數的對應
     *
     * @var array
     */
    private $userinfoDataMap = [
        'order' => 'orderId',
        'username' => 'orderId',
        'money' => 'amount',
        'time' => 'orderCreateDate',
        'remark' => 'orderId',
        'backurl' => 'notify_url',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'cmd',
        'merchantid',
        'language',
        'userinfo',
    ];

    /**
     * 返回驗證時需要加密的參數
     *     0: 有返回就要加密的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'cmd' => 1,
        'merchantid' => 1,
        'order' => 1,
        'username' => 1,
        'money' => 1,
        'time' => 1,
        'call' => 1,
        'result' => 1,
        'remark' => 1,
    ];

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        '278' => '', // 銀聯在線
        '1088' => '', // 銀聯在線_手機支付
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

        // 驗證支付參數
        $this->payVerify();

        // 回調網址串訂單號
        $this->options['notify_url'] = sprintf(
            '%s?order_id=%s',
            $this->options['notify_url'],
            $this->options['orderId']
        );

        // 從內部設值到支付參數
        foreach ($this->requireMap as $paymentKey => $internalKey) {
            $this->requestData[$paymentKey] = $this->options[$internalKey];
        }

        // 驗證用戶資料參數
        foreach (array_values($this->userinfoDataMap) as $internalKey) {
            if (!isset($this->options[$internalKey]) || trim($this->options[$internalKey]) === '') {
                throw new PaymentException('No pay parameter specified', 180145);
            }
        }

        // 從內部設值到支付參數
        foreach ($this->userinfoDataMap as $paymentKey => $internalKey) {
            $this->userinfoData[$paymentKey] = $this->options[$internalKey];
        }

        // 帶入未支援的支付方式就噴例外
        if (!array_key_exists($this->options['paymentVendorId'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $this->userinfoData['money'] = sprintf('%.2f', $this->userinfoData['money']);
        $this->requestData['userinfo'] = $this->userinfoData;

        // 調整提交網址
        $url = 'http://api.pc.' . $this->options['postUrl'] . ':11103/ToService.aspx';

        // 手機支付調整請求參數
        if ($this->options['paymentVendorId'] == '1088') {
            $this->requestData['cmd'] = '6010';
            $url = 'http://api.m.' . $this->options['postUrl'] . ':11403/PMToService2.aspx';
        }

        return [
            'post_url' => $url,
            'params' => [
                'pid' => $this->requestData['merchantid'],
                'des' => $this->encode(),
            ],
        ];
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

        // 先驗證平台回傳的必要參數
        if (!isset($this->options['res'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        // des解密
        $res = $this->decode($this->options['res']);

        // 取得md5字串
        $sign = substr($res, - 32);

        // 沒有sign，判斷不是md5字串就要丟例外
        if (!isset($sign) || !preg_match('/^[a-f0-9]{32}$/', $sign)) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        // xml字串
        $xmlData = substr($res, 0, strlen($res) - 32);

        // 解析xml驗證相關參數
        $this->options = $this->xmlToArray($xmlData);

        // 驗證返回參數
        $this->payResultVerify();

        $encodeStr = '';

        // 組織加密字串
        $encodeStr = $xmlData . $this->privateKey;

        if ($sign !== md5($encodeStr)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['result'] !== '1') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['order'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['money'] != $entry['amount']) {
            throw new PaymentException('Order Amount error', 180058);
        }

        $response = [
            'cmd' => '60071',
            'merchantid' => $this->options['merchantid'],
            'order' => $this->options['order'],
            'username' => $this->options['order'],
            'result' => '100',
        ];

        // 應答機制訊息
        $this->msg = $this->arrayToXml($response, ['xml_encoding' => 'utf-8'], 'message');

    }

    /**
     * 支付時的加密
     *
     * @return string
     */
    protected function encode()
    {
        // 陣列轉XML
        $param = $this->arrayToXml($this->requestData, ['xml_encoding' => 'utf-8'], 'message');

        // 特殊欄位值
        $names = ['Key1', 'Key2'];
        $extra = $this->getMerchantExtraValue($names);
        $key = base64_decode($extra['Key1']);
        $iv = base64_decode($extra['Key2']);

        $md5Str = md5($param . $this->privateKey);
        $tempStr = $param . $md5Str;

        $date = $this->userinfoData['time'];
        $md5hash = md5($date . $this->userinfoData['time']);
        $value = $tempStr . $md5hash;

        $encodeStr = openssl_encrypt($value, "des-ede3-cbc", $key, 0, $iv);

        return $encodeStr;
    }

    /**
     * 回調參數解密
     *
     * @return string
     */
    protected function decode($res)
    {
        // 取得特殊欄位值
        $names = ['Key1', 'Key2'];
        $extra = $this->getMerchantExtraValue($names);
        $key = base64_decode($extra['Key1']);
        $iv = base64_decode($extra['Key2']);

        $ret = openssl_decrypt ($res, 'des-ede3-cbc', $key, 0, $iv);

        return substr($ret, 0, strlen($ret) - 32);
    }
}

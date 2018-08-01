<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 弘寶支付
 */
class HongBao extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'expireTime' => '', // 超時時間，單位：秒，可空
        'summary' => '', // 交易摘要，設定username方便業主比對
        'amount' => '', // 支付金額，保留小數點兩位，單位：元
        'tradeDate' => '', // 交易日期 Ymd
        'tradeNo' => '', // 訂單號
        'extra' => '', // 支付完成原樣回調，可空
        'service' => 'B2C', // 接口名稱，網銀:B2C
        'merId' => '', // 商戶號
        'bankName' => '', // 銀行代碼
        'clientIp' => '', // 客戶端IP
        'notifyUrl' => '', // 通知網址
        'version' => '1.0.0.0', // 接口版本，固定值
        'sign' => '', // 簽名
    ];

    /**
     * 支付方式為銀聯二維時，要傳給平台驗證的參數
     *
     * @var array
     */
    protected $allScanRequestData = [
        'merchantId' => '', // 商戶號
        'merchantOrderNo' => '', // 訂單號
        'merchantUserId' => '', // 用戶號，這邊帶入訂單號
        'payAmount' => '', // 支付金額，保留小數點兩位，單位：元
        'notifyUrl' => '', // 通知網址
        'description' => '', // 商品描述，這邊帶入訂單號
        'userIp' => '', //　客戶端IP
        'sign' => '', // 簽名
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'summary' => 'username',
        'amount' => 'amount',
        'tradeDate' => 'orderCreateDate',
        'tradeNo' => 'orderId',
        'merId' => 'number',
        'bankName' => 'paymentVendorId',
        'clientIp' => 'ip',
        'notifyUrl' => 'notify_url',
    ];

    /**
     * 支付方式為銀聯二維時，支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $allScanRequireMap = [
        'merchantId' => 'number',
        'merchantOrderNo' => 'orderId',
        'merchantUserId' => 'orderId',
        'payAmount' => 'amount',
        'notifyUrl' => 'notify_url',
        'description' => 'orderId',
        'userIp' => 'ip',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'expireTime',
        'summary',
        'amount',
        'tradeDate',
        'tradeNo',
        'extra',
        'service',
        'merId',
        'bankName',
        'clientIp',
        'notifyUrl',
        'typeId',
        'version',
    ];

    /**
     * 支付方式為銀聯二維時，需要加密的參數
     *
     * @var array
     */
    protected $allScanEncodeParams = [
        'merchantId',
        'merchantOrderNo',
        'merchantUserId',
        'payAmount',
        'notifyUrl',
        'description',
        'userIp',
    ];

    /**
     * 返回驗證時需要加密的參數
     *     0: 有返回就要加密的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'merchantNo' => 1,
        'tradeNo' => 1,
        'payNo' => 1,
        'tradeDate' => 1,
        'amount' => 1,
        'status' => 1,
        'summary' => 1,
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
        '1' => '中国工商银行', // 工商銀行
        '2' => '交通银行', // 交通銀行
        '3' => '中国农业银行', // 農業銀行
        '4' => '中国建设银行', // 建設銀行
        '5' => '招商银行', // 招商銀行
        '6' => '民生银行', // 民生銀行總行
        '8' => '浦发银行', // 上海浦東發展銀行
        '10' => '兴业银行', // 興業銀行
        '11' => '中信银行', // 中信銀行
        '12' => '光大银行', // 光大銀行
        '13' => '华夏银行', // 華夏銀行
        '14' => '广发银行', // 廣東發展銀行
        '15' => '平安银行', // 平安銀行
        '16' => '中国邮政储蓄银行', // 中國郵政
        '17' => '中国银行', // 中國銀行
        '217' => '渤海银行', // 渤海銀行
        '278' => '银联WAP', // 銀聯在線
        '1088' => '银联WAP', // 銀聯在線_手機支付
        '1090' => '1', // 微信_二維
        '1092' => '2', // 支付寶_二維
        '1103' => '3', // QQ_二維
        '1114' => '', // 一碼付
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

        $functionName = 'b2c';

        // 一碼付要改提交參數
        if ($this->options['paymentVendorId'] == 1114) {
            $functionName = 'allScan';
            $this->requestData = $this->allScanRequestData;
            $this->requireMap = $this->allScanRequireMap;
            $this->encodeParams = $this->allScanEncodeParams;
            $this->requestData['payAmount'] = sprintf('%.2f', $this->requestData['payAmount']);
        }

        // 從內部設值到支付參數
        foreach ($this->requireMap as $paymentKey => $internalKey) {
            $this->requestData[$paymentKey] = $this->options[$internalKey];
        }

        if ($this->options['paymentVendorId'] != 1114) {
            // 檢查銀行代碼是否支援
            if (!array_key_exists($this->requestData['bankName'], $this->bankMap)) {
                throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
            }

            // 額外的參數設定
            $this->requestData['bankName'] = $this->bankMap[$this->requestData['bankName']];
            $this->requestData['amount'] = sprintf('%.2f', $this->requestData['amount']);

            $createAt = new \Datetime($this->requestData['tradeDate']);
            $this->requestData['tradeDate'] = $createAt->format('Ymd');

            // 二維支付，需調整參數
            if (in_array($this->options['paymentVendorId'], [1090, 1092, 1103])) {
                $this->requestData['service'] = 'SCANPAY';
                $this->requestData['typeId'] = $this->requestData['bankName'];
                unset($this->requestData['bankName']);

                $functionName = 'scan';
            }

            // 銀聯支付，需調整參數
            if (in_array($this->options['paymentVendorId'], [278, 1088])) {
                $this->requestData['bankCardType'] = 'SAVING';
            }
        }

        $this->requestData['sign'] = $this->encode();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $merchant = isset($this->requestData['merId']) ? $this->requestData['merId'] : $this->requestData['merchantId'];

        $callParams = [
            'merchantId' => $merchant,
            'paramsMaps' => json_encode($this->requestData),
        ];

        $nusoapParam = [
            'serverIp' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'uri' => '/ws/trade?wsdl',
            'function' => $functionName,
            'callParams' => $callParams,
            'wsdl' => true,
        ];

        $result = $this->soapRequest($nusoapParam);

        if (!isset($result['result'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        // 二維支付
        if (in_array($this->options['paymentVendorId'], [1090, 1092, 1103, 1114])) {
            $this->setQrcode($result['result']);

            return [];
        }

        return [
            'post_url' => $this->getPostUrlFromResult($result['result']),
            'params' => [],
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

        // 驗證返回參數
        $this->payResultVerify();

        // 組合參數驗證加密簽名
        $encodeData = [];
        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options)) {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        ksort($encodeData);
        $encodeData['key'] = $this->privateKey;
        $encodeStr = urldecode(http_build_query($encodeData));

        // 沒有返回sign就要丟例外
        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if (strcasecmp($this->options['sign'], md5($encodeStr)) != 0) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['status'] !== '2') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['tradeNo'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['amount'] != $entry['amount']) {
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

        foreach ($this->encodeParams as $index) {
            if (isset($this->requestData[$index])) {
                $encodeData[$index] = $this->requestData[$index];
            }
        }

        $encodeStr = urldecode(http_build_query($encodeData, '', ', '));
        $encodeStr = '{' . $encodeStr . '}' . $this->privateKey;

        return md5($encodeStr);
    }

    /**
     * 從對外回來result字串擷取提交網址
     *
     * @param string $result
     * @return string
     */
    private function getPostUrlFromResult($result)
    {
        $fetchedUrl = [];
        preg_match("/href=\"([^\"]+)/", $result, $fetchedUrl);

        if (!isset($fetchedUrl[1])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        return $fetchedUrl[1];
    }
}

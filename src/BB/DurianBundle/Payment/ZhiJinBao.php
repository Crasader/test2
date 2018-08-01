<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 *　智金寶支付
 */
class ZhiJinBao extends PaymentBase
{
    /**
     * 支付提交參數
     *
     * @var array
     */
    protected $requestData = [
        'service' => 'B2C', // 接口名稱
        'version' => '1.0.0.0', // 接口版本，固定值
        'merId' => '', // 商戶號
        'tradeNo' => '', // 商戶訂單號
        'tradeDate' => '', // 交易日期 YMD
        'amount' => '', // 訂單金額，保留小數點兩位，單位：元
        'notifyUrl' => '', // 異步通知網址
        'extra' => '', // 擴展字段，非必填
        'summary' => '', // 交易摘要，設定username方便業主比對
        'expireTime' => '', // 訂單限制時間，非必填
        'clientIp' => '', // 客戶端IP
        'bankName' => '', // 銀行代碼
        'sign' => '', // 簽名
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'merId' => 'number',
        'tradeNo' => 'orderId',
        'tradeDate' => 'orderCreateDate',
        'amount' => 'amount',
        'notifyUrl' => 'notify_url',
        'summary' => 'username',
        'clientIp' => 'ip',
        'bankName' => 'paymentVendorId',
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
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
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
        '3' => '中国农业银行', // 農業銀行
        '4' => '中国建设银行', // 建設銀行
        '12' => '中国光大银行', // 光大銀行
        '14' => '广发银行', // 廣發銀行
        '16' => '中国邮政储蓄银行', // 中國郵政
        '278' => '银联WAP', // 銀聯在線
        '1088' => '银联WAP', // 銀聯在線_手機支付
        '1090' => '1', // 微信_二維
        '1092' => '2', // 支付寶_二維
        '1103' => '3', // QQ_二維
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

        // 從內部設值到支付參數
        foreach ($this->requireMap as $paymentKey => $internalKey) {
            $this->requestData[$paymentKey] = $this->options[$internalKey];
        }

        // 帶入未支援的銀行就噴例外
        if (!array_key_exists($this->requestData['bankName'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $this->requestData['bankName'] = $this->bankMap[$this->requestData['bankName']];
        $this->requestData['amount'] = sprintf('%.2f', $this->requestData['amount']);
        $date = new \DateTime($this->requestData['tradeDate']);
        $this->requestData['tradeDate'] = $date->format('Ymd');

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $functionName = 'b2c';

        // 二維支付
        if (in_array($this->options['paymentVendorId'], [1090, 1092, 1103])) {
            $this->requestData['service'] = 'SCANPAY';
            $this->requestData['typeId'] = $this->requestData['bankName'];
            // 移除二維不需要的參數
            unset($this->requestData['bankName']);

            $functionName = 'scan';
        }

        $this->requestData['sign'] = $this->encode();

        $callParams = [
            'merchantId' => $this->requestData['merId'],
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
        if (in_array($this->options['paymentVendorId'], [1090, 1092, 1103])) {
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

        $this->payResultVerify();

        $encodeData = [];

        // 組織加密串
        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options)) {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        ksort($encodeData);

        $encodeData['key'] = $this->privateKey;
        $encodeStr = urldecode(http_build_query($encodeData));

        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['sign'] != md5($encodeStr)) {
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

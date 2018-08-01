<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 云寶
 */
class YunBao extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'P_UserId' => '', // 商號
        'P_OrderId' => '', // 訂單號
        'P_FaceValue' => '', // 支付金額(整數)
        'P_CustormId' => '', // 用戶編號
        'P_Type' => '', // 充值渠道
        'P_SDKVersion' => '3.1.3', // 默認3.1.3
        'P_RequestType' => '0', // 0:web 1:wap 2:iPhone 3:Android
        'P_Subject' => '', // 產品名稱（存username方便業主對帳）
        'P_Result_URL' => '', // 同步通知網址
        'P_Notify_URL' => '', // 異步通知網址
        'P_PostKey' => '', // 簽名認證字串
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'P_UserId' => 'number',
        'P_OrderId' => 'orderId',
        'P_FaceValue' => 'amount',
        'P_Type' => 'paymentVendorId',
        'P_Subject' => 'username',
        'P_Result_URL' => 'notify_url',
        'P_Notify_URL' => 'notify_url',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'P_UserId',
        'P_OrderId',
        'P_FaceValue',
        'P_Type',
        'P_SDKVersion',
        'P_RequestType',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'P_UserId' => 1,
        'P_OrderId' => 1,
        'P_SMPayId' => 1,
        'P_FaceValue' => 1,
        'P_ChannelId' => 1,
    ];

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        '278' => '5', // 銀聯快捷
        '1088' => '5', // 銀聯_手機支付
        '1090' => '2', // 微信_二維
        '1092' => '4', // 支付寶_二維
        '1103' => '6', // QQ錢包_二維
        '1111' => '9', // 銀聯_二維
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

        // 檢查銀行是否支援
        if (!array_key_exists($this->requestData['P_Type'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $this->requestData['P_Type'] = $this->bankMap[$this->requestData['P_Type']];

        // 組織用戶編號
        $random = strval(rand(100, 999));
        $custormId = sprintf(
            '%s|%s|%s',
            $this->requestData['P_UserId'],
            $this->privateKey,
            $random
        );
        $this->requestData['P_CustormId'] = $random . '_' . md5($custormId);

        // 產生加密字串
        $this->requestData['P_PostKey'] = $this->encode();

        // 非二維支付不需對外
        if (in_array($this->options['paymentVendorId'], ['278', '1088'])) {
            return $this->requestData;
        }

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'method' => 'POST',
            'uri' => '/doPay.do',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => urldecode(http_build_query($this->requestData)),
            'header' => [],
        ];

        $result = $this->curlRequest($curlParam);
        $parseData = json_decode($result, true);

        if (!isset($parseData['status'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        if ($parseData['status'] != '0' && !isset($parseData['message'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        if ($parseData['status'] != '0') {
            throw new PaymentConnectionException($parseData['message'], 180130, $this->getEntryId());
        }

        // 檢查通訊成功時，是否有回傳result_code狀態，沒有則噴錯
        if (!isset($parseData['result_code'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        // result_code 狀態不為0的時候，是否有回傳錯誤訊息，沒有則噴錯
        if ($parseData['result_code'] != '0' && !isset($parseData['err_msg'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        if ($parseData['result_code'] != '0') {
            throw new PaymentConnectionException($parseData['err_msg'], 180130, $this->getEntryId());
        }

        if (!isset($parseData['code_url'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        $this->setQrcode($parseData['code_url']);

        return [];
    }

    /**
     * 驗證線上支付是否成功
     *
     * @param array $entry
     */
    public function verifyOrderPayment($entry)
    {
        $this->verifyPrivateKey();
        $this->payResultVerify();

        if (!isset($this->options['P_PostKey'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        // 檢查加密字串
        $encodeData = [];
        foreach (array_keys($this->decodeParams) as $key) {
            if (array_key_exists($key, $this->options)) {
                $encodeData[] = $this->options[$key];
            }
        }

        $encodeData[] = $this->privateKey;

        $encodeStr = implode('|', $encodeData);

        if ($this->options['P_PostKey'] !== md5($encodeStr)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if (!isset($this->options['P_ErrCode'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        // 檢查支付結果（只有 P_ErrCode 為 0 才是支付成功）
        if ($this->options['P_ErrCode'] != '0') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        // 檢查訂單號
        if ($this->options['P_OrderId'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        // 檢查支付金額
        if ($this->options['P_FaceValue'] != $entry['amount']) {
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
        $encodeStr = [];

        foreach ($this->encodeParams as $index) {
            $encodeStr[] = $this->requestData[$index];
        }

        $encodeStr[] = $this->privateKey;

        return md5(implode('|', $encodeStr));
    }
}

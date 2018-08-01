<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Entity\CashWithdrawEntry;
use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 曼島Neteller支付
 */
class Neteller extends PaymentBase
{
    /**
     * 額外的支付欄位
     *
     * @var array
     */
    protected $extraParams = [
        'email_account_id',
        'secure_id'
    ];

    /**
     * 回傳支付時要給支付平台驗證的資料
     *
     * @return array
     */
    public function getVerifyData()
    {
        if (trim($this->options['shop_url']) == '') {
            throw new PaymentException('No shop_url specified', 180157);
        }

        $actUrl = sprintf(
            '%sreturn.php?payment_id=%s&ref_id=%s',
            $this->options['shop_url'],
            $this->options['paymentGatewayId'],
            $this->options['orderId']
        );

        return ['act_url' => $actUrl];
    }

    /**
     * 驗證線上支付是否成功
     *
     * @param array $entry
     */
    public function verifyOrderPayment($entry)
    {
        $paymentValue = '';

        if (isset($this->options['email_account_id']) && trim($this->options['email_account_id']) !== '') {
            $paymentValue = $this->options['email_account_id'];
        }

        // 等對外接過來後再移除
        if (isset($this->options['email']) && trim($this->options['email']) !== '') {
            $paymentValue = $this->options['email'];
        }

        if (isset($this->options['account_id']) && trim($this->options['account_id']) !== '') {
            $paymentValue = $this->options['account_id'];
        }

        //沒帶入account&email要丟例外
        if (trim($paymentValue) == '') {
            throw new PaymentException('No return parameter specified', 180137);
        }

        // 如果沒有secure_id要丟例外
        if (!isset($this->options['secure_id']) || trim($this->options['secure_id']) == '') {
            throw new PaymentException('No return parameter specified', 180137);
        }
        $secureId = $this->options['secure_id'];

        $verifyUrl = $this->options['verify_url'];
        $verifyIp = $this->options['verify_ip'];

        //驗證支付平台對外設定
        if (trim($verifyUrl) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        // 驗證幣別是否支援
        $this->validateCurrency($entry['currency']);

        //取得商號額外項目
        $names = ['client_id', 'client_secret'];
        $merchantExtraValues = $this->getMerchantExtraValue($names);

        $clientId = $merchantExtraValues['client_id'];
        $clientSecret = $merchantExtraValues['client_secret'];

        $clientData = $clientId . ':' . $clientSecret;
        $token = base64_encode($clientData);
        $tokenType = 'Basic';

        //先取得token
        //url: https://api.neteller.com/v1/oauth2/token?grant_type=client_credentials
        $header = [
            'Content-Type' => 'application/json',
            'Authorization' => "{$tokenType} {$token}"
        ];

        $curlParam = [
            'method' => 'POST',
            'uri' => '/v1/oauth2/token?grant_type=client_credentials',
            'ip' => $verifyIp,
            'host' => $verifyUrl,
            'param' => '',
            'header' => $header
        ];

        $result = $this->curlRequestWithoutValidStatusCode($curlParam);

        $token = json_decode($result, true);

        //輸出結果為不成功
        if (isset($token['error']) && trim($token['error']) !== '') {
            throw new PaymentConnectionException($token['error'], 180130, $this->getEntryId());
        }

        //檢查回傳狀態
        if (!isset($token['accessToken'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if (!isset($token['tokenType'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        $newToken = $token['accessToken'];
        $newTokenType = $token['tokenType'];

        //支付驗證
        $params = [
            'paymentMethod' => [
                'type' => 'neteller',
                'value' => $paymentValue
            ],
            'transaction' => [
                'merchantRefId' => $entry['id'],
                'amount' => $entry['amount'] * 100,
                'currency' => $entry['currency']
            ],
            'verificationCode' => $secureId
        ];
        $params = json_encode($params);

        //url: https://api.neteller.com/v1/transferIn
        $header = [
            'Content-Type' => 'application/json',
            'Authorization' => "{$newTokenType} {$newToken}"
        ];

        $curlParam = [
            'method' => 'POST',
            'uri' => '/v1/transferIn',
            'ip' => $verifyIp,
            'host' => $verifyUrl,
            'param' => $params,
            'header' => $header
        ];

        $res = $this->curlRequestWithoutValidStatusCode($curlParam);

        $result = json_decode($res, true);

        //輸出結果為不成功
        if (isset($result['error']['message']) && trim($result['error']['message']) !== '') {
            throw new PaymentConnectionException($result['error']['message'], 180130, $this->getEntryId());
        }

        //檢查回傳狀態
        if (!isset($result['transaction']['status'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        $status = trim($result['transaction']['status']);

        if ($status == 'pending') {
            throw new PaymentConnectionException('Order Processing', 180059, $this->getEntryId());
        }

        if ($status == 'cancelled' || $status == 'declined') {
            throw new PaymentConnectionException('Order has been cancelled', 180063, $this->getEntryId());
        }

        if ($status !== 'accepted') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        // 檢查是否回傳Neteller訂單號
        if (!isset($result['transaction']['id'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        // 紀錄入款明細的支付平台參考編號
        $this->setCashDepositEntryRefId($result['transaction']['id']);
    }

    /**
     * 線上出款
     *
     * @param array $entry
     * @return array
     */
    public function withdrawPayment($entry)
    {
        $verifyUrl = $this->options['verify_url'];
        $verifyIp = $this->options['verify_ip'];

        //驗證支付平台對外設定
        if (trim($verifyUrl) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $accountId = $entry['account'];
        $orderId = $entry['id'];
        $amount = $entry['auto_withdraw_amount']; // 金額為自動出款金額

        // 驗證幣別是否支援
        $this->validateCurrency($entry['currency']);

        //取得商號額外項目
        $names = ['client_id', 'client_secret'];
        $merchantExtraValues = $this->getMerchantExtraValue($names);

        $clientId = $merchantExtraValues['client_id'];
        $clientSecret = $merchantExtraValues['client_secret'];

        $clientData = $clientId . ':' . $clientSecret;

        $token = base64_encode($clientData);
        $tokenType = 'Basic';

        //先取得token
        //url: https://api.neteller.com/v1/oauth2/token?grant_type=client_credentials
        $header = [
            'Content-Type' => 'application/json',
            'Authorization' => "{$tokenType} {$token}"
        ];

        $curlParam = [
            'method' => 'POST',
            'uri' => '/v1/oauth2/token?grant_type=client_credentials',
            'ip' => $verifyIp,
            'host' => $verifyUrl,
            'param' => '',
            'header' => $header
        ];

        $result = $this->curlRequestWithoutValidStatusCode($curlParam);

        $token = json_decode($result, true);

        //輸出結果為不成功
        if (isset($token['error']) && trim($token['error']) !== '') {
            throw new PaymentConnectionException($token['error'], 180124, $this->getEntryId());
        }

        //檢查回傳狀態
        if (!isset($token['accessToken'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if (!isset($token['tokenType'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        $newToken = $token['accessToken'];
        $newTokenType = $token['tokenType'];

        //出款
        $params = [
            'payeeProfile' => [
                'accountId' => $accountId
            ],
            'transaction' => [
                'merchantRefId' => $orderId,
                'amount' => abs($amount * 100), // 出款金額需為正數
                'currency' => $entry['currency']
            ]
        ];
        $params = json_encode($params);

        //url: https://api.neteller.com/v1/transferOut
        $header = [
            'Content-Type' => 'application/json',
            'Authorization' => "{$newTokenType} {$newToken}"
        ];

        $curlParam = [
            'method' => 'POST',
            'uri' => '/v1/transferOut',
            'ip' => $verifyIp,
            'host' => $verifyUrl,
            'param' => $params,
            'header' => $header
        ];

        $res = $this->curlRequestWithoutValidStatusCode($curlParam);

        $result = json_decode($res, true);

        //輸出結果為不成功
        if (isset($result['error']['message']) && trim($result['error']['message']) !== '') {
            throw new PaymentConnectionException($result['error']['message'], 180124, $this->getEntryId());
        }

        //檢查回傳狀態
        if (!isset($result['transaction']['status'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        $status = trim($result['transaction']['status']);

        if ($status == 'pending') {
            throw new PaymentConnectionException('Order Processing', 180059, $this->getEntryId());
        }

        if ($status == 'cancelled' || $status == 'declined') {
            throw new PaymentConnectionException('Order has been cancelled', 180063, $this->getEntryId());
        }

        if ($status !== 'accepted') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        // 檢查是否回傳Neteller訂單號
        if (!isset($result['transaction']['id'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        // 紀錄出款明細的支付平台參考編號
        $this->setCashWithdrawEntryRefId($result['transaction']['id']);
    }

    /**
     * 驗證幣別是否支援
     *
     * @param string currency
     */
    private function validateCurrency($currency)
    {
        $netellerCurrency = [
            'AUD', // 澳幣
            'GBP', // 英鎊
            'BGN', // 保加利亞列弗
            'CAD', // 加拿大幣
            'DKK', // 丹麥克朗
            'EUR', // 歐元
            'HUF', // 匈牙利福林
            'INR', // 印度盧比
            'JPY', // 日幣
            'MYR', // 馬來西亞幣
            'MAD', // 摩洛哥迪拉姆
            'MXN', // 墨西哥披索
            'NGN', // 尼日利亞奈拉
            'NOK', // 挪威克朗
            'PLN', // 波蘭茲羅提
            'RON', // 羅馬尼亞列伊
            'RUB', // 俄羅斯盧布
            'SGD', // 新加坡幣
            'SEK', // 瑞典克朗
            'CHF', // 瑞士法郎
            'TWD', // 台幣
            'TND', // 突尼西亞第納爾
            'USD', // 美金
        ];

        //不支援的幣別丟例外
        if (!in_array($currency, $netellerCurrency)) {
            throw new PaymentConnectionException('Illegal Order currency', 180083, $this->getEntryId());
        }
    }
}

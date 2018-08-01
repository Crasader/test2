<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * Paypal 支付平台
 */
class Paypal extends PaymentBase
{
    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'USER' => 'number',
        'PAYMENTREQUEST_0_INVNUM' => 'orderId',
        'PAYMENTREQUEST_0_AMT' => 'amount'
    ];

    /**
     * 回傳支付時要給支付平台驗證的資料
     *
     * @return array
     */
    public function getVerifyData()
    {
        $params = [];

        // 驗證私鑰
        $this->verifyPrivateKey();

        $params['SIGNATURE'] = $this->privateKey;

        $this->payVerify();

        // 從內部給定值到參數
        foreach ($this->requireMap as $paymentKey => $internalKey) {
            $params[$paymentKey] = $this->options[$internalKey];
        }

        // 如果沒有notify_url要丟例外
        if (!isset($this->options['notify_url']) || $this->options['notify_url'] === '') {
            throw new PaymentException('No pay parameter specified', 180145);
        }

        // 如果沒有paymentGatewayId要丟例外
        if (!isset($this->options['paymentGatewayId']) || $this->options['paymentGatewayId'] === '') {
            throw new PaymentException('No pay parameter specified', 180145);
        }

        $notifyUrl = sprintf(
            '%s?payment_id=%s&order_id=%s',
            $this->options['notify_url'],
            $this->options['paymentGatewayId'],
            $this->options['orderId']
        );
        $params['cancelUrl'] = $notifyUrl;
        $params['returnUrl'] = $notifyUrl;

        // 如果沒有merchantId要丟例外
        if (!isset($this->options['merchantId']) || $this->options['merchantId'] === '') {
            throw new PaymentException('No pay parameter specified', 180145);
        }

        // 取得商家附加設定值
        $merchantExtraValues = $this->getMerchantExtraValue(['Password']);

        $params['PWD'] = $merchantExtraValues['Password'];
        $params['METHOD'] = 'SetExpressCheckout';
        $params['VERSION'] = '78';
        $params['PAYMENTREQUEST_0_PAYMENTACTION'] = 'SALE';
        $params['PAYMENTREQUEST_0_CURRENCYCODE'] = 'USD';

        //驗證支付平台對外設定
        if (!isset($this->options['verify_url']) || $this->options['verify_url'] === '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        // 檢查是否有postUrl(支付平台提交網址)
        if (!isset($this->options['postUrl']) || $this->options['postUrl'] === '') {
            throw new PaymentException('No pay parameter specified', 180145);
        }

        //curl對外驗證 - 取得token
        //url: https://api-3t.sandbox.paypal.com/nvp
        $curlParam = [
            'method' => 'POST',
            'uri' => '/nvp',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => http_build_query($params),
            'header' => []
        ];

        $res = $this->curlRequest($curlParam);

        $token = [];

        parse_str(urldecode($res), $token);

        //檢查回傳狀態
        if (!isset($token['ACK']) || $token['ACK'] === '') {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        if ($token['ACK'] !== 'Success') {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        if (!isset($token['TOKEN']) || $token['TOKEN'] === '') {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        $actUrl = sprintf(
            '%s?cmd=_express-checkout&token=%s',
            $this->options['postUrl'],
            $token['TOKEN']
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
        $params = [];

        // 如果沒有TOKEN要丟例外
        if (!isset($this->options['token']) || trim($this->options['token']) == '') {
            throw new PaymentException('No return parameter specified', 180137);
        }
        $params['TOKEN'] = $this->options['token'];

        $params['USER'] = $entry['merchant_number'];
        $params['SIGNATURE'] = $this->privateKey;

        //取得商號額外項目
        $merchantExtraValues = $this->getMerchantExtraValue(['Password']);
        $params['PWD'] = $merchantExtraValues['Password'];

        $params['METHOD'] = 'GetExpressCheckoutDetails';
        $params['VERSION'] = '89';

        //驗證支付平台對外設定
        $verifyUrl = $this->options['verify_url'];

        if (trim($verifyUrl) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        //curl對外驗證 - 取得交易明細
        //url: https://api-3t.sandbox.paypal.com/nvp
        $curlParam = [
            'method' => 'POST',
            'uri' => '/nvp',
            'ip' => $this->options['verify_ip'],
            'host' => $verifyUrl,
            'param' => http_build_query($params),
            'header' => []
        ];

        $res = $this->curlRequest($curlParam);

        $detail = [];

        parse_str(urldecode($res), $detail);

        //檢查回傳狀態
        if (!isset($detail['PAYERID']) || $detail['PAYERID'] === '') {
            throw new PaymentException('No return parameter specified', 180137);
        }

        // 用取得的明細訂單號來驗證是否相同
        if (!isset($detail['INVNUM']) || $detail['INVNUM'] === '') {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($detail['INVNUM'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        //進行付款確認
        $params['PAYERID'] = $detail['PAYERID'];
        $params['METHOD'] = 'DoExpressCheckoutPayment';
        $params['VERSION'] = '63';
        $params['PAYMENTREQUEST_0_AMT'] = sprintf('%.2f', $entry['amount']);
        $params['PAYMENTREQUEST_0_CURRENCYCODE'] = 'USD';

        //curl對外驗證
        //url: https://api-3t.sandbox.paypal.com/nvp
        $curlParam = [
            'method' => 'POST',
            'uri' => '/nvp',
            'ip' => $this->options['verify_ip'],
            'host' => $verifyUrl,
            'param' => http_build_query($params),
            'header' => []
        ];

        $res = $this->curlRequest($curlParam);

        $result = [];

        parse_str(urldecode($res), $result);

        //檢查回傳狀態
        if (!isset($result['ACK']) || $result['ACK'] === '') {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($result['ACK'] !== 'Success') {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        if (!isset($result['PAYMENTINFO_0_PAYMENTSTATUS']) || $result['PAYMENTINFO_0_PAYMENTSTATUS'] === '') {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($result['PAYMENTINFO_0_PAYMENTSTATUS'] !== 'Completed') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        // 驗證支付回傳的金額來判斷金額是否正確
        if (!isset($result['PAYMENTINFO_0_AMT']) || $result['PAYMENTINFO_0_AMT'] === '') {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($result['PAYMENTINFO_0_AMT'] != $entry['amount']) {
            throw new PaymentException('Order Amount error', 180058);
        }
    }
}

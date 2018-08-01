<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Entity\PaymentGateway;
use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 越南 NganLuong 支付平台
 */
class NganLuong extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'merchant_site_code' => '', // 商號
        'receiver' => '', // 商號註冊email
        'order_code' => '', // 訂單號
        'amount' => '', // 金額
        'currency_code' => 'VND', // 幣別，目前先用VND就好。(VND: 越南盾, USD: 美金)
        'tax_amount' => '0', // 稅
        'discount_amount' => '0', // 優惠金額
        'fee_shipping' => '0', // 運費
        'request_confirm_shipping' => '0', // 確認貨運請求
        'no_shipping' => '1', // 是否不需要貨運
        'return_url' => '', // 返回訂單通知url
        'cancel_url' => '', // 取消訂單通知url
        'language' => 'vn', // (vn: 越南語, en: 英語)
        'checksum' => '' // 加密簽名
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'merchant_site_code' => 'number',
        'order_code' => 'orderId',
        'amount' => 'amount',
        'return_url' => 'notify_url',
        'cancel_url' => 'notify_url'
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'receiver',
        'order_code',
        'amount',
        'currency_code',
        'tax_amount',
        'discount_amount',
        'fee_shipping',
        'request_confirm_shipping',
        'no_shipping',
        'return_url',
        'cancel_url',
        'language'
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'merchant_site_code' => 1,
        'receiver' => 1,
        'order_code' => 1,
        'amount' => 1,
        'currency_code' => 1,
        'ref_id' => 1
    ];

    /**
     * 應答機制訊息
     *
     * @var string
     */
    protected $msg = 'Transaction is Success';

    /**
     * 查詢時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $trackingRequireMap = [
        'merchant_site_code' => 'number',
        'order_code' => 'orderId',
        'amount' => 'amount'
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

        // 取得商家附加設定值
        $merchantExtraValues = $this->getMerchantExtraValue(['receiver']);
        $this->requestData['receiver'] = $merchantExtraValues['receiver'];

        $this->requestData['checksum'] = $this->encode();

        // 取得付款資訊
        $xmlStr = '';
        foreach ($this->encodeParams as $key) {
            $xmlStr .= "<$key>" . $this->requestData[$key] . "</$key>";
        }

        $callParams = [
            'merchant_site_code' => $this->requestData['merchant_site_code'],
            'checksum' => $this->requestData['checksum'],
            'params' => "<params>$xmlStr</params>"
        ];

        $nusoapParam = [
            'serverIp' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'uri' => '/micro_checkout_api.php?wsdl',
            'function' => 'SetExpressCheckoutPayment',
            'callParams' => $callParams,
            'wsdl' => false,
        ];

        $result = $this->soapRequest($nusoapParam);

        $paymentReply = $this->xmlToArray($result);

        // 驗證必要回傳的參數
        if (!isset($paymentReply['result_code'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        if (!isset($paymentReply['result_description'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        if (!isset($paymentReply['link_checkout'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        if ($paymentReply['result_code'] !== '00') {
            $errorMsg = sprintf(
                'error_code: %s, result_description: %s.',
                $paymentReply['result_code'],
                $paymentReply['result_description']
            );

            throw new PaymentConnectionException($errorMsg, 180130, $this->getEntryId());
        }

        if (!isset($paymentReply['token'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        // 紀錄入款明細的支付平台參考編號
        if ($this->getPayway() == self::PAYWAY_CASH) {
            $this->setCashDepositEntryRefId($paymentReply['token']);
        }

        if ($this->getPayway() == self::PAYWAY_CARD) {
            $this->setCardDepositEntryRefId($paymentReply['token']);
        }

        return ['act_url' => $paymentReply['link_checkout']];
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

        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options)) {
                $encodeStr .= $this->options[$paymentKey];
            }
        }

        $encodeStr .= $this->privateKey;

        if (!isset($this->options['checksum'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['checksum'] !== md5($encodeStr)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        // 取得交易明細
        $merchantSiteCode = $this->options['merchant_site_code'];
        $checksum = md5($this->options['ref_id'] . $this->privateKey);
        $xmlStr = '<params><token>' . $this->options['ref_id'] . '</token></params>';

        $callParams = [
            'merchant_site_code' => $merchantSiteCode,
            'checksum' => $checksum,
            'params' => $xmlStr
        ];

        $nusoapParam = [
            'serverIp' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'uri' => '/micro_checkout_api.php?wsdl',
            'function' => 'GetExpressCheckout',
            'callParams' => $callParams,
            'wsdl' => false,
        ];

        $result = $this->soapRequest($nusoapParam);

        $detail = $this->xmlToArray($result);

        // 驗證必要的參數
        if (!isset($detail['result_code'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if (!isset($detail['result_description'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if (!isset($detail['transaction_status'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if (!isset($detail['amount'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($detail['result_code'] !== '00') {
            $errorMsg = sprintf(
                'error_code: %s, result_description: %s.',
                $detail['result_code'],
                $detail['result_description']
            );

            throw new PaymentConnectionException($errorMsg, 180130, $this->getEntryId());
        }

        // 訂單未支付
        if ($detail['transaction_status'] === '1') {
            throw new PaymentConnectionException('Unpaid order', 180062, $this->getEntryId());
        }

        // 訂單處理中
        if ($detail['transaction_status'] === '2') {
            throw new PaymentConnectionException('Order Processing', 180059, $this->getEntryId());
        }

        // 狀態不為4即為支付失敗
        if ($detail['transaction_status'] !== '4') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['order_code'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        // 用取得的明細金額來判斷金額是否正確
        if ($detail['amount'] != $entry['amount']) {
            throw new PaymentException('Order Amount error', 180058);
        }
    }

    /**
     * 訂單查詢
     */
    public function paymentTracking()
    {
        // 驗證私鑰
        $this->verifyPrivateKey();
        $this->trackingVerify();

        // 取得交易明細
        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $merchantSiteCode = $this->options['number'];
        $checksum = md5($this->options['ref_id'] . $this->privateKey);
        $xmlStr = '<params><token>' . $this->options['ref_id'] . '</token></params>';

        $callParams = [
            'merchant_site_code' => $merchantSiteCode,
            'checksum' => $checksum,
            'params' => $xmlStr
        ];

        $nusoapParam = [
            'serverIp' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'uri' => '/micro_checkout_api.php?wsdl',
            'function' => 'GetExpressCheckout',
            'callParams' => $callParams,
            'wsdl' => false,
        ];

        $result = $this->soapRequest($nusoapParam);

        $detail = $this->xmlToArray($result);

        // 驗證必要回傳的參數
        if (!isset($detail['result_code'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        if (!isset($detail['result_description'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        if (!isset($detail['transaction_status'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        if (!isset($detail['amount'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        // 訂單不存在
        if ($detail['result_code'] == '06') {
            throw new PaymentConnectionException('Order does not exist', 180060, $this->getEntryId());
        }

        if ($detail['result_code'] !== '00') {
            $errorMsg = sprintf(
                'error_code: %s, result_description: %s.',
                $detail['result_code'],
                $detail['result_description']
            );

            throw new PaymentConnectionException($errorMsg, 180123, $this->getEntryId());
        }

        // 訂單未支付
        if ($detail['transaction_status'] === '1') {
            throw new PaymentConnectionException('Unpaid order', 180062, $this->getEntryId());
        }

        // 訂單處理中
        if ($detail['transaction_status'] === '2') {
            throw new PaymentConnectionException('Order Processing', 180059, $this->getEntryId());
        }

        // 狀態不為4即為支付失敗
        if ($detail['transaction_status'] !== '4') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        // 用取得的明細金額來判斷金額是否正確
        if ($detail['amount'] != $this->options['amount']) {
            throw new PaymentException('Order Amount error', 180058);
        }
    }
}

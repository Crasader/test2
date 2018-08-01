<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 利盈支付
 */
class LiYingPay extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'mch_id' => '', // 商戶號
        'trade_type' => '10', // 交易類型，預設填網銀
        'out_trade_no' => '', // 訂單號
        'body' => '', // 商品描述(可空)
        'attach' => '', // 附加訊息(可空)
        'total_fee' => '', // 訂單金額，單位:分
        'bank_id' => '', // 銀行代碼
        'notify_url' => '', // 異步通知網址
        'return_url' => '', // 同步通知網址
        'time_start' => '', // 訂單時間，格式:YmdHis
        'nonce_str' => '', // 隨機字符串，不大於32位
        'sign' => '', // 簽名
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'mch_id' => 'number',
        'out_trade_no' => 'orderId',
        'total_fee' => 'amount',
        'bank_id' => 'paymentVendorId',
        'notify_url' => 'notify_url',
        'return_url' => 'notify_url',
        'time_start' => 'orderCreateDate',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'mch_id',
        'trade_type',
        'out_trade_no',
        'total_fee',
        'bank_id',
        'notify_url',
        'return_url',
        'time_start',
        'nonce_str',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'mch_id' => 1,
        'out_trade_no' => 1,
        'trade_no' => 1,
        'trade_type' => 1,
        'trade_state' => 1,
        'total_fee' => 1,
        'time_end' => 1,
        'nonce_str' => 1,
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
        '1' => '01020000', // 中國工商銀行
        '2' => '03010000', // 交通銀行
        '3' => '01030000', // 中國農業銀行
        '4' => '01050000', // 中國建設銀行
        '5' => '03080000', // 招商銀行
        '6' => '03050000', // 中國民生銀行
        '8' => '03100000', // 上海浦東發展銀行
        '9' => '03130011', // 北京銀行
        '10' => '03090000', // 興業銀行
        '11' => '03020000', // 中信銀行
        '12' => '03030000', // 中國光大銀行
        '13' => '03040000', // 華夏銀行
        '14' => '03060000', // 廣東發展銀行
        '15' => '03070000', // 平安銀行
        '16' => '04030000', // 中國郵政
        '17' => '01040000', // 中國銀行
        '1090' => '01', // 微信支付_二維
        '1092' => '02', // 支付寶_二維
        '1097' => '08', // 微信_手機支付
        '1098' => '02', // 支付寶_手機支付
        '1103' => '05', // QQ_二维
        '1104' => '06', // QQ_手機支付
        '1107' => '07', // 京東_二維
        '1108' => '13', // 京東_手機支付
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
        if (!array_key_exists($this->requestData['bank_id'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $this->requestData['bank_id'] = $this->bankMap[$this->requestData['bank_id']];
        $this->requestData['total_fee'] = round($this->requestData['total_fee'] * 100);
        $date = new \DateTime($this->options['orderCreateDate']);
        $this->requestData['time_start'] = $date->format('YmdHis');
        $this->requestData['nonce_str'] = md5(uniqid(rand(), true));

        // 非網銀支付參數設定
        if (in_array($this->options['paymentVendorId'], ['1090', '1092', '1097', '1098', '1103', '1107', '1111'])) {
            $this->requestData['trade_type'] = $this->requestData['bank_id'];
            $this->requestData['bank_id'] = '';
        }

        // QQ手機支付、京東手機支付
        if (in_array($this->options['paymentVendorId'], ['1104', '1108'])) {
            $this->requestData['trade_type'] = $this->requestData['bank_id'];
            unset($this->requestData['bank_id']);
            unset($this->requestData['return_url']);

            // 設定支付平台需要的加密串
            $this->requestData['sign'] = $this->encode();

            if (trim($this->options['verify_url']) == '') {
                throw new PaymentException('No verify_url specified', 180140);
            }

            $curlParam = [
                'method' => 'POST',
                'uri' => '/v1/trade/index.php',
                'ip' => $this->options['verify_ip'],
                'host' => $this->options['verify_url'],
                'param' => urldecode(http_build_query($this->requestData)),
                'header' => [],
            ];

            $result = $this->curlRequest($curlParam);
            $parseData = json_decode($result, true);

            if (!isset($parseData['status']) || !isset($parseData['message'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            if ($parseData['status'] != 'SUCCESS') {
                throw new PaymentConnectionException($parseData['message'], 180130, $this->getEntryId());
            }

            if (!isset($parseData['result_code']) && !isset($parseData['err_msg'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            if ($parseData['result_code'] != 'SUCCESS') {
                throw new PaymentConnectionException($parseData['err_msg'], 180130, $this->getEntryId());
            }

            if (!isset($parseData['code_url'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            return ['act_url' => $parseData['code_url']];
        }

        // 設定支付平台需要的加密串
        $this->requestData['sign'] = $this->encode();

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

        ksort($encodeData);

        $encodeData['key'] = $this->privateKey;

        // 依key1=value1&key2=value2&...&keyN=valueN之後做md5
        $encodeStr = urldecode(http_build_query($encodeData));

        // 如果沒有返回簽名要丟例外
        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['sign'] != strtoupper(md5($encodeStr))) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['trade_state'] == 'NOTPAY') {
            throw new PaymentConnectionException('Unpaid order', 180062, $this->getEntryId());
        }

        if ($this->options['trade_state'] != 'SUCCESS') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['out_trade_no'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['total_fee'] != round($entry['amount'] * 100)) {
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

        ksort($encodeData);

        $encodeData['key'] = $this->privateKey;

        // 依key1=value1&key2=value2&...&keyN=valueN之後做md5
        $encodeStr = http_build_query($encodeData);

        return strtoupper(md5($encodeStr));
    }
}

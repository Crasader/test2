<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 博樂支付
 */
class BoLePay extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'partner' => '', // 商戶號
        'service' => '', // 業務參數
        'input_charset' => 'UTF-8', // 編碼格式，固定值
        'sign_type' => 'MD5', // 簽名方式
        'sign' => '', // 簽名
        'request_time' => '', // 送出請求時間(YmdHis)
        'content' => '', // 業務請求參數
        'out_trade_no' => '', // 業務參數，商戶訂單號，產生業務請求參數後unset
        'amount_str' => '', // 業務參數，訂單金額，產生業務請求參數後unset
        'return_url' => '', // 業務參數，後台通知地址，產生業務請求參數後unset
        'subject' => '', // 業務參數，商品名稱，設定username方便業主比對，產生業務請求參數後unset
        'sub_body' => '', // 業務參數，商品描述，設定username方便業主比對，產生業務請求參數後unset
        'remark' => '', // 業務參數，備註，非必填，產生業務請求參數後unset
    ];

    /**
     * 網銀支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $bankRequestData = [
        'partner' => '', // 商戶號
        'service' => 'gateway_pay', // 網銀:gateway_pay
        'input_charset' => 'UTF-8', // 編碼格式，固定值
        'sign_type' => 'MD5', // 簽名方式
        'sign' => '', // 簽名
        'request_time' => '', // 送出請求時間(YmdHis)
        'out_trade_no' => '', // 商戶訂單號
        'amount_str' => '', // 訂單金額
        'return_url' => '', // 後台通知地址
        'tran_time' => '', // 交易時間，非必填
        'tran_ip' => '', // 交易IP
        'buyer_name' => '', // 買家姓名，非必填
        'buyer_contact' => '', // 買家聯繫方式，非必填
        'good_name' => '', // 商品名稱，設定username方便業主比對
        'goods_detail' => '', // 商品詳細，設定username方便業主比對
        'bank_code' => '', // 銀行編碼
        'receiver_address' => '', // 收貨地址，非必填
        'redirect_url' => '', // 前台通知網址
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'partner' => 'number',
        'request_time' => 'orderCreateDate',
        'service' => 'paymentVendorId',
        'out_trade_no' => 'orderId',
        'amount_str' => 'amount',
        'return_url' => 'notify_url',
        'subject' => 'username',
        'sub_body' => 'username',
    ];

    /**
     * 支付時支付平台網銀參數與內部參數的對應
     *
     * @var array
     */
    protected $bankRequireMap = [
        'partner' => 'number',
        'request_time' => 'orderCreateDate',
        'out_trade_no' => 'orderId',
        'amount_str' => 'amount',
        'return_url' => 'notify_url',
        'tran_ip' => 'ip',
        'good_name' => 'username',
        'goods_detail' => 'username',
        'bank_code' => 'paymentVendorId',
        'redirect_url' => 'notify_url',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'out_trade_no',
        'amount_str',
        'return_url',
        'subject',
        'sub_body',
    ];

    /**
     * 網銀支付時需要加密的參數
     *
     * @var array
     */
    protected $bankEncodeParams = [
        'partner',
        'service',
        'out_trade_no',
        'amount_str',
        'tran_ip',
        'buyer_name',
        'buyer_contact',
        'good_name',
        'request_time',
        'return_url',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'content' => 1,
    ];

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        '1' => 'ICBC', // 工商銀行
        '2' => 'BOCM', // 交通銀行
        '3' => 'ABC', // 農業銀行
        '4' => 'CCB', // 建設銀行
        '5' => 'CMB', // 招商銀行
        '10' => 'CIB', // 興業銀行
        '11' => 'CITIC', // 中信銀行
        '13' => 'HXB', // 華夏銀行
        '14' => 'CGB', // 廣發銀行
        '16' => 'PSBC', // 中國郵政
        '17' => 'BOC', // 中國銀行
        '1090' => 'wx_pay', // 微信_二維
        '1092' => 'ali_pay', // 支付寶_二維
        '1098' => 'ali_pay', // 支付寶_手機支付
        '1103' => 'qq_pay', // QQ_二維
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

        // 非網銀
        if (in_array($this->options['paymentVendorId'], ['1090', '1092', '1098', '1103'])) {
            $this->payVerify();

            // 從內部給定值到參數
            foreach ($this->requireMap as $paymentKey => $internalKey) {
                $this->requestData[$paymentKey] = $this->options[$internalKey];
            }

            // 額外的參數設定
            $this->requestData['service'] = $this->bankMap[$this->requestData['service']];
            $date = new \DateTime($this->requestData['request_time']);
            $this->requestData['request_time'] = $date->format('YmdHis');
            $this->requestData['amount_str'] = sprintf('%.2f', $this->requestData['amount_str']);

            // 設定支付時的業務參數
            $this->requestData['content'] = $this->getPayContentParam();

            $this->requestData['sign'] = $this->encode();

            // 移除支付時的業務參數
            $removeParams = [
                'out_trade_no',
                'amount_str',
                'subject',
                'sub_body',
                'remark',
                'return_url',
            ];

            foreach ($removeParams as $removeParam) {
                unset($this->requestData[$removeParam]);
            }

            if (trim($this->options['verify_url']) == '') {
                throw new PaymentException('No verify_url specified', 180140);
            }

            $curlParam = [
                'method' => 'POST',
                'uri' => '/api/md5/gateway',
                'ip' => $this->options['verify_ip'],
                'host' => $this->options['verify_url'],
                'param' => http_build_query($this->requestData),
                'header' => [],
            ];

            $result = $this->curlRequest($curlParam);
            $parseData = json_decode($result, true);

            if (!isset($parseData['is_succ'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            if ($parseData['is_succ'] != 'T' && isset($parseData['fail_reason'])) {
                throw new PaymentConnectionException($parseData['fail_reason'], 180130, $this->getEntryId());
            }

            if ($parseData['is_succ'] != 'T') {
                throw new PaymentConnectionException('Pay error', 180130, $this->getEntryId());
            }

            if (!isset($parseData['result_json'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            $responseData = json_decode($parseData['result_json'], true);

            if (!isset($responseData['wx_pay_sm_url']) &&
                !isset($responseData['ali_pay_sm_url']) &&
                !isset($responseData['qq_pay_sm_url'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            if ($this->options['paymentVendorId'] == '1098') {
                return ['act_url' => $responseData['ali_pay_sm_url']];
            }

            if ($this->options['paymentVendorId'] == '1090') {
                $this->setQrcode($responseData['wx_pay_sm_url']);
            }

            if ($this->options['paymentVendorId'] == '1092') {
                $this->setQrcode($responseData['ali_pay_sm_url']);
            }

            if ($this->options['paymentVendorId'] == '1103') {
                $this->setQrcode($responseData['qq_pay_sm_url']);
            }

            return [];
        }

        // 網銀
        $this->requestData = $this->bankRequestData;
        $this->requireMap = $this->bankRequireMap;
        $this->encodeParams = $this->bankEncodeParams;

        $this->payVerify();

        // 從內部給定值到參數
        foreach ($this->requireMap as $paymentKey => $internalKey) {
            $this->requestData[$paymentKey] = $this->options[$internalKey];
        }

        // 帶入未支援的銀行就噴例外
        if (!array_key_exists($this->requestData['bank_code'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 網銀額外的參數設定
        $date = new \DateTime($this->requestData['request_time']);
        $this->requestData['request_time'] = $date->format('YmdHis');
        $this->requestData['amount_str'] = sprintf('%.2f', $this->requestData['amount_str']);
        $this->requestData['bank_code'] = $this->bankMap[$this->requestData['bank_code']];

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

        // 若沒有返回簽名需丟例外
        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        $content = urldecode($this->options['content']);

        $encodeStr = $content . '&verfication_code=' . $this->privateKey;

        // 驗證簽名
        if ($this->options['sign'] !== md5($encodeStr)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        $parseData = [];

        // 回傳格式為query string，因此直接用parse_str來做分解
        parse_str($content, $parseData);

        $returnValues = [
            'status',
            'out_trade_no',
            'amount_str',
        ];

        foreach ($returnValues as $paymentKey) {
            if (!array_key_exists($paymentKey, $parseData)) {
                throw new PaymentException('No return parameter specified', 180137);
            }
        }

        if ($parseData['status'] === '0') {
            throw new PaymentConnectionException('Order Processing', 180059, $this->getEntryId());
        }

        if ($parseData['status'] !== '1') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($parseData['out_trade_no'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($parseData['amount_str'] != $entry['amount']) {
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
        // 網銀
        if (!in_array($this->options['paymentVendorId'], ['1090', '1092', '1098', '1103'])) {
            $encodeData = [];

            // 加密設定
            foreach ($this->encodeParams as $index) {
                if ($this->requestData[$index] != '') {
                    $encodeData[$index] = $this->requestData[$index];
                }
            }

            $encodeData['verfication_code'] = $this->privateKey;

            $encodeStr = urldecode(http_build_query($encodeData));

            return md5($encodeStr);
        }

        $encodeStr = urldecode($this->requestData['content']) . '&' . $this->privateKey;

        return md5($encodeStr);
    }

    /**
     * 設定支付時的業務參數
     *
     * @return string
     */
    private function getPayContentParam()
    {
        $contentData = [];

        foreach ($this->encodeParams as $index) {
            $contentData[$index] = $this->requestData[$index];
        }

        if ($this->options['paymentVendorId'] == '1090') {
            $contentData['wx_pay_type'] = 'wx_sm';
        }

        if (in_array($this->options['paymentVendorId'], ['1092', '1098'])) {
            $contentData['ali_pay_type'] = 'ali_sm';
        }

        if ($this->options['paymentVendorId'] == '1103') {
            $contentData['qq_pay_type'] = 'qq_sm';
        }

        ksort($contentData);

        return http_build_query($contentData);
    }
}



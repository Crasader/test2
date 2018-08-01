<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * BeeCloud
 */
class BeeCloud extends PaymentBase
{
    /**
     * 支付提交參數
     *
     * @var array
     */
    protected $requestData = [
        'app_id' => '', // BeeCloud平台的AppID
        'timestamp' => '', // 簽名生成時間
        'app_sign' => '', // 加密簽名
        'channel' => 'BC_GATEWAY', // 渠道類型(網連支付)
        'total_fee' => '', // 訂單總金額
        'bill_no' => '', // 商戶訂單號
        'title' => '', // 訂單標題(方便業主比對 這邊帶入username)
        'return_url' => '', // 同步返回頁面
        'bank' => '', // 銀行代碼
    ];

    /**
     * 支付提交參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'app_id' => 'number',
        'total_fee' => 'amount',
        'bill_no' => 'orderId',
        'title' => 'username',
        'return_url' => 'notify_url',
        'bank' => 'paymentVendorId',
    ];

    /**
     * 支付時提交的實名認證參數與內部參數的對應
     *
     * @var array
     */
    protected $requestRealNameAuthMap = [
        'card_no' => 'card_no',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'app_id',
        'timestamp',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'timestamp' => 1,
        'trade_success' => 1,
        'transaction_id' => 1,
        'transaction_fee' => 1,
    ];

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        1 => 'ICBC', // 中國工商銀行
        2 => 'BOCM', // 交通銀行
        3 => 'ABC', // 中國農業銀行
        5 => 'CMB', // 招商銀行
        6 => 'CMBC', // 中國民生銀行
        8 => 'SPDB', // 上海浦東發展銀行
        10 => 'CIB', // 興業銀行
        11 => 'CITIC', // 中信銀行
        12 => 'CEB', // 中國光大銀行
        14 => 'GDB', // 廣東發展銀行
        15 => 'SDB', // 平安銀行
        17 => 'BOC', // 中國銀行
        278 => '', // 銀聯在線
        1088 => '', // 銀聯在線手機支付
    ];

    /**
     * 查詢時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $trackingRequestData = [
        'app_id' => '', // 商戶號
        'timestamp' => '', // 簽名生成時間
        'app_sign' => '', // 簽名
        'bill_no' => '', // 訂單號
        'channel' => 'BC', // 支付方式
    ];

    /**
     * 查詢時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $trackingRequireMap = [
        'app_id' => 'number',
        'bill_no' => 'orderId',
    ];

    /**
     * 查詢時需要加密的參數
     *
     * @var array
     */
    protected $trackingEncodeParams = [
        'app_id',
        'timestamp',
    ];

    /**
     * 查詢解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $trackingDecodeParams = [
        'result_code' => 1,
        'result_msg' => 1,
        'bills' => 1,
    ];

    /**
     * 實名認證所需的參數欄位
     *
     * @var array
     */
    protected $realNameAuthParams = [
        'name',
        'id_no',
        'card_no',
    ];

    /**
     * 實名認證時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $realNameAuthRequestData = [
        'app_id' => '', // 商戶號
        'timestamp' => '', // 簽名生成時間
        'app_sign' => '', // 簽名
        'name' => '', // 身分證姓名
        'id_no' => '', // 身分證號
        'card_no' => '', // 用戶銀行卡卡號
    ];

    /**
     * 實名認證時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $realNameAuthRequireMap = [
        'app_id' => 'number',
        'name' => 'name',
        'id_no' => 'id_no',
        'card_no' => 'card_no',
    ];

    /**
     * 實名認證時需要加密的參數
     *
     * @var array
     */
    protected $realNameAuthEncodeParams = [
        'app_id',
        'timestamp',
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

        $this->options['notify_url'] = sprintf(
            '%s?payment_id=%s',
            $this->options['notify_url'],
            $this->options['paymentGatewayId']
        );

        // 從內部給定值到參數
        foreach ($this->requireMap as $paymentKey => $internalKey) {
            $this->requestData[$paymentKey] = $this->options[$internalKey];
        }

        // 帶入未支援的銀行就噴例外
        if (!array_key_exists($this->requestData['bank'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        $this->requestData['bank'] = $this->bankMap[$this->requestData['bank']];
        $this->requestData['timestamp'] = time() * 1000;
        $this->requestData['total_fee'] = round($this->requestData['total_fee'] * 100);

        // 支付銀行若為銀聯在線，需調整渠道類型(银联快捷)
        if (in_array($this->options['paymentVendorId'], [278, 1088])) {
            $this->requestData['channel'] = 'BC_EXPRESS';

            // 檢查是否需實名認證
            $isRealNameAuth = false;

            if (isset($this->options['merchant_extra']['real_name_auth'])) {
                $isRealNameAuth = $this->options['merchant_extra']['real_name_auth'];
            }

            if ($isRealNameAuth) {
                // 檢查需提交的實名認證參數
                $this->payRealNameAuthVerify();

                foreach ($this->requestRealNameAuthMap as $paymentKey => $internalKey) {
                    $this->requestData[$paymentKey] = $this->options['real_name_auth_params'][$internalKey];
                }
            }
        }

        // 設定支付平台需要的加密串
        $this->requestData['app_sign'] = $this->encode();

        // 取得跳轉html
        $curlParam = [
            'method' => 'POST',
            'uri' => '/2/rest/bill',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => json_encode($this->requestData),
            'header' => ['Content-Type' => 'application/json'],
        ];

        $result = $this->curlRequest($curlParam);

        $retArray = json_decode($result, true);

        $returnValues = [
            'result_msg' => 1,
            'resultCode' => 1,
            'errMsg' => 1,
        ];

        foreach ($returnValues as $paymentKey => $require) {
            if ($require && !array_key_exists($paymentKey, $retArray)) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }
        }

        if ($retArray['result_msg'] != 'OK' || $retArray['resultCode'] != '0') {
            throw new PaymentConnectionException($retArray['errMsg'], 180130, $this->getEntryId());
        }

        if (!isset($retArray['html'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        $getUrl = [];
        preg_match('/action="([^"]+)/', $retArray['html'], $getUrl);

        if (!isset($getUrl[1])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        $out = [];
        $pattern = '/<input.*name="(.*)".*value+="([^"]*)"/U';
        preg_match_all($pattern, $retArray['html'], $out);

        return [
            'post_url' => $getUrl[1],
            'params' => array_combine($out[1], $out[2])
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

        $encodeStr = '';
        $encodeStr .= $entry['merchant_number'];
        $encodeStr .= $this->privateKey;
        $encodeStr .= $this->options['timestamp'];

        // 沒有返回sign就要丟例外
        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['sign'] != md5($encodeStr)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if (!$this->options['trade_success']) {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['transaction_id'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['transaction_fee'] != round($entry['amount'] * 100)) {
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

        // 驗證訂單查詢參數
        $this->trackingVerify();

        // 從內部給定值到參數
        foreach ($this->trackingRequireMap as $paymentKey => $internalKey) {
            $this->trackingRequestData[$paymentKey] = $this->options[$internalKey];
        }

        $this->trackingRequestData['timestamp'] = time() * 1000;
        $this->trackingRequestData['app_sign'] = $this->trackingEncode();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'method' => 'GET',
            'uri' => '/2/rest/bills',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => 'para=' . urlencode(json_encode($this->trackingRequestData)),
            'header' => ['Content-Type' => 'application/json'],
        ];

        $result = $this->curlRequest($curlParam);

        $parseData = json_decode($result, true);

        $this->trackingResultVerify($parseData);

        if ($parseData['result_code'] != '0' || $parseData['result_msg'] != 'OK') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        $returnValues = [
            'spay_result' => 1,
            'bill_no' => 1,
            'total_fee' => 1,
        ];

        foreach ($returnValues as $paymentKey => $require) {
            if ($require && !isset($parseData['bills'][0][$paymentKey])) {
                throw new PaymentException('No tracking return parameter specified', 180139);
            }
        }

        if (!$parseData['bills'][0]['spay_result']) {
            throw new PaymentConnectionException('Unpaid order', 180062, $this->getEntryId());
        }

        if ($parseData['bills'][0]['bill_no'] != $this->options['orderId']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($parseData['bills'][0]['total_fee'] != round($this->options['amount'] * 100)) {
            throw new PaymentException('Order Amount error', 180058);
        }
    }

    /**
     * 實名認證
     */
    public function realNameAuth()
    {
        // 驗證私鑰
        $this->verifyPrivateKey();

        $this->authenticationVerify();

        // 從內部給定值到參數
        foreach ($this->realNameAuthRequireMap as $authKey => $internalKey) {
            $this->realNameAuthRequestData[$authKey] = $this->options[$internalKey];
        }

        $this->realNameAuthRequestData['timestamp'] = time() * 1000;
        $this->realNameAuthRequestData['app_sign'] = $this->realNameAuthEncode();

        // 支付銀行若為銀聯在線，才需要實名認證
        if (!in_array($this->options['paymentVendorId'], [278, 1088])) {
            throw new PaymentException('PaymentVendor have no need to authenticate', 150180186);
        }

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'method' => 'POST',
            'uri' => '/2/auth',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => json_encode($this->realNameAuthRequestData),
            'header' => ['Content-Type' => 'application/json'],
        ];
        $result = $this->curlRequest($curlParam);

        $parseData = json_decode($result, true);

        $returnValues = [
            'card_id' => 1,
            'auth_result' => 1,
        ];

        foreach ($returnValues as $authKey => $require) {
            if ($require && !array_key_exists($authKey, $parseData)) {
                throw new PaymentException('No authentication return parameter specified', 150180181);
            }
        }

        // 檢查認證是否成功
        if (!$parseData['auth_result']) {
            throw new PaymentException('Real Name Authentication failure', 150180182);
        }
    }

    /**
     * 提交實名認證時的加密
     *
     * @return string
     */
    protected function realNameAuthEncode()
    {
        $encodeStr = '';

        // 加密設定
        foreach ($this->realNameAuthEncodeParams as $index) {
            $encodeStr .= $this->realNameAuthRequestData[$index];
        }
        $encodeStr .= $this->privateKey;

        return md5($encodeStr);
    }
}

<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 神州付
 */
class Telepay extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'scode' => '', // 商家代號
        'orderid' => '', // 訂單號
        'paytype' => '', // 支付方式
        'amount' => '', // 支付金額(小數點後兩位)
        'productname' => '', // 商品名稱(username)
        'currcode' => 'CNY', // 支付幣別，固定值
        'userid' => '', // 用戶編號或帳號
        'memo' => '', // 備註，可空
        'callbackurl' => '', // 商家接收交易結果網址
        'sign' => '', // 簽名
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'scode' => 'number',
        'orderid' => 'orderId',
        'paytype' => 'paymentVendorId',
        'amount' => 'amount',
        'productname' => 'username',
        'userid' => 'username',
        'callbackurl' => 'notify_url',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'scode',
        'orderid',
        'amount',
        'currcode',
        'callbackurl',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'scode' => 1,
        'orderid' => 1,
        'orderno' => 1,
        'paytype' => 1,
        'productname' => 1,
        'amount' => 1,
        'currcode' => 1,
        'memo' => 0,
        'resptime' => 0,
        'status' => 1,
        'respcode' => 1,
        'rmbrate' => 0,
    ];

    /**
     * 應答機制訊息
     *
     * @var string
     */
    protected $msg = 'OK';

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        279 => 'unionpay2', // 銀聯無卡
        1090 => 'wechat2', // 微信_二維
        1092 => 'alipay', // 支付寶_二維
        1093 => 'unionpay2', // 銀聯無卡_手機支付
        1097 => 'wechat_h5', // 微信_手機支付
        1098 => 'alipay_h5', // 支付寶_手機支付
        1103 => 'qq', // QQ_二維
    ];

    /**
     * 查詢時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $trackingRequestData = [
        'scode' => '', // 商家代號
        'orderid' => '', // 訂單號
        'sign' => '', // 簽名
    ];

    /**
     * 查詢時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $trackingRequireMap = [
        'scode' => 'number',
        'orderid' => 'orderId',
    ];

    /**
     * 查詢時需要加密的參數
     *
     * @var array
     */
    protected $trackingEncodeParams = [
        'scode',
        'orderid',
    ];

    /**
     * 查詢解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $trackingDecodeParams = [
        'scode' => 1,
        'orderid' => 1,
        'orderno' => 1,
        'paytype' => 1,
        'amount' => 1,
        'productname' => 1,
        'currcode' => 1,
        'memo' => 0,
        'resptime' => 0,
        'status' => 1,
        'respcode' => 1,
        'rmbrate' => 0,
    ];

    /**
     * 出款時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $withdrawRequestData = [
        'scode' => '', // 商家代號
        'orderno' => '', // 交易序號
        'money' => '', // 支付金額
        'accountno' => '', // 銀行帳號
        'accountname' => '', // 持有人姓名
        'bankno' => '', // 銀行代碼
        'timestamp' => '', // unix時間戳記(整數)
        'notifyurl' => '', // 代發結果通知url，選填
        'sign' => '', // 驗證碼
    ];

    /**
     * 出款時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $withdrawRequireMap = [
        'scode' => 'number',
        'orderno' => 'orderId',
        'money' => 'amount',
        'accountno' => 'account',
        'accountname' => 'nameReal',
        'bankno' => 'bank_info_id',
    ];

    /**
     * 出款支援的銀行對應編號
     *
     * @var array
     */
    protected $withdrawBankMap = [
        1 => 'ICBC', // 工商銀行
        2 => 'BOCOM', // 交通銀行
        3 => 'ABC', // 農業銀行
        4 => 'CCB', // 建設銀行
        5 => 'CMB', // 招商銀行
        6 => 'CMBC', // 民生銀行
        8 => 'SPDB', // 浦發銀行
        9 => 'BCCB', // 北京銀行
        10 => 'CIB', // 興業銀行
        11 => 'CNCB', // 中信銀行
        12 => 'CEB', // 光大銀行
        13 => 'HXB', // 華夏銀行
        14 => 'GDB', // 廣發銀行
        15 => 'PAB', // 平安銀行
        16 => 'PSBC', // 中國郵政儲蓄銀行
        17 => 'BOC', // 中國銀行
        19 => 'BOS', // 上海銀行
        217 => 'BOHC', // 渤海銀行
        218 => 'DGBC', // 東莞銀行
        220 => 'HZBC', // 杭州銀行
        221 => 'ZSBC', // 浙商銀行
        222 => 'NBBC', // 寧波銀行
        223 => 'BEAI', // 東亞銀行
        224 => 'WZBC', // 溫州銀行
        225 => 'JSHBC', // 晋商銀行
        226 => 'NJBC', // 南京銀行
        227 => 'GZBC', // 廣州農村商業銀行
        228 => 'SRCB', // 上海農村商業銀行
    ];

    /**
     * 出款時需要加密的參數
     *
     * @var array
     */
    protected $withdrawEncodeParams = [
        'scode',
        'orderno',
        'money',
        'timestamp',
    ];

    /**
     * 出款返回驗證需要加密的參數
     *
     * @var array
     */
    protected $withdrawDecodeParams = [
        'prc' => 1,
        'errcode' => 1,
        'msg' => 1,
        'tradeno' => 1,
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
        if (!array_key_exists($this->requestData['paytype'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外參數設定
        $this->requestData['paytype'] = $this->bankMap[$this->requestData['paytype']];

        // 手機支付
        if (in_array($this->options['paymentVendorId'], ['1097', '1098'])) {
            // 金額單位調整為分
            $this->requestData['amount'] = round($this->requestData['amount'] *100);
            $this->requestData['sign'] = $this->encode();

            if (trim($this->options['verify_url']) == '') {
                throw new PaymentException('No verify_url specified', 180140);
            }

            $curlParam = [
                'method' => 'POST',
                'uri' => '/tpay/pay.aspx',
                'ip' => $this->options['verify_ip'],
                'host' => $this->options['verify_url'],
                'param' => http_build_query($this->requestData),
                'header' => [],
            ];

            $result = $this->curlRequest($curlParam);
            $parseData = json_decode($result, true);

            if (!isset($parseData['status']) || !isset($parseData['respmsg'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            if ($parseData['status'] !== '1') {
                throw new PaymentConnectionException($parseData['respmsg'], 180130, $this->getEntryId());
            }

            if (!isset($parseData['url'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            return [
                'post_url' => $parseData['url'],
                'params' => [],
            ];
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

        // 如果沒有返回簽名檔要丟例外
        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        // 組織加密串 scode | orderno & orderid & amount | currcode & status & respcode | key
        $encodeStr = sprintf(
            '%s|%s&%s&%s|%s&%s&%s|%s',
            $this->options['scode'],
            $this->options['orderno'],
            $this->options['orderid'],
            $this->options['amount'],
            $this->options['currcode'],
            $this->options['status'],
            $this->options['respcode'],
            $this->privateKey
        );

        if ($this->options['sign'] != md5($encodeStr)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['status'] != '1') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['orderid'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        $amount = $entry['amount'];

        // 手機支付的金額單位為分
        if (in_array($this->options['paytype'], ['wechat_h5', 'alipay_h5'])) {
            $amount = round($amount * 100);
        }

        if ($this->options['amount'] != $amount) {
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

        // 從內部給定值到參數
        foreach ($this->trackingRequireMap as $paymentKey => $internalKey) {
            $this->trackingRequestData[$paymentKey] = $this->options[$internalKey];
        }

        // 設定加密簽名
        $this->trackingRequestData['sign'] = $this->trackingEncode();

        if (trim($this->options['reopUrl']) == '') {
            throw new PaymentException('No reopUrl specified', 180141);
        }

        // 因通過對外機 proxy 會 timeout，改為此方式對外
        $params = [
            'url' => $this->options['reopUrl'],
            'data' => http_build_query($this->trackingRequestData),
        ];

        $curlParam = [
            'method' => 'GET',
            'uri' => '/pay/curl.php',
            'ip' => [$this->container->getParameter('payment_ip')],
            'host' => $this->container->getParameter('payment_ip'),
            'param' => http_build_query($params),
            'header' => [],
        ];

        // 取得訂單查詢結果
        $this->options['content'] = $this->curlRequest($curlParam);

        $this->paymentTrackingVerify();
    }

    /**
     * 取得訂單查詢時需要的參數
     *
     * @return array
     */
    public function getPaymentTrackingData()
    {
        // 驗證私鑰
        $this->verifyPrivateKey();

        // 驗證訂單查詢參數
        $this->trackingVerify();

        // 從內部給定值到參數
        foreach ($this->trackingRequireMap as $paymentKey => $internalKey) {
            $this->trackingRequestData[$paymentKey] = $this->options[$internalKey];
        }

        $this->trackingRequestData['sign'] = $this->trackingEncode();

        if (trim($this->options['reopUrl']) == '') {
            throw new PaymentException('No reopUrl specified', 180141);
        }

        // 因通過對外機 proxy 會 timeout，改為此方式對外
        $params = [
            'url' => $this->options['reopUrl'],
            'data' => http_build_query($this->trackingRequestData),
        ];

        $curlParam = [
            'verify_ip' => [$this->container->getParameter('payment_ip')],
            'path' => '/pay/curl.php?' . http_build_query($params),
            'method' => 'GET',
            'headers' => [
                'Host' => $this->container->getParameter('payment_ip'),
            ],
        ];

        return $curlParam;
    }

    /**
     * 驗證訂單查詢是否成功
     */
    public function paymentTrackingVerify()
    {
        $data = json_decode($this->options['content'], true);

        $this->trackingResultVerify($data);

        // 加密方式 scode | orderno & orderid & amount | currcode & status & respcode | key
        $encodeStr = sprintf(
            '%s|%s&%s&%s|%s&%s&%s|%s',
            $data['scode'],
            $data['orderno'],
            $data['orderid'],
            $data['amount'],
            $data['currcode'],
            $data['status'],
            $data['respcode'],
            $this->privateKey
        );

        if (!isset($data['sign'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        if ($data['sign'] != md5($encodeStr)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($data['status'] != '1') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($data['orderid'] != $this->options['orderId']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($data['amount'] != $this->options['amount']) {
            throw new PaymentException('Order Amount error', 180058);
        }
    }

    /**
     * 線上出款
     */
    public function withdrawPayment()
    {
        // 驗證私鑰
        $this->verifyPrivateKey();

        $this->withdrawVerify();

        // 從內部給定值到參數
        foreach ($this->withdrawRequireMap as $paymentKey => $internalKey) {
            $this->withdrawRequestData[$paymentKey] = $this->options[$internalKey];
        }

        $withdrawHost = trim($this->options['withdraw_host']);

        // 額外的參數設定
        $this->withdrawRequestData['bankno'] = $this->withdrawBankMap[$this->withdrawRequestData['bankno']];
        $this->withdrawRequestData['timestamp'] = time();
        $this->withdrawRequestData['money'] = sprintf('%.2f', $this->withdrawRequestData['money']);

        // 設定出款需要的加密串
        $this->withdrawRequestData['sign'] = $this->withdrawEncode();

        // 出款
        $curlParam = [
            'method' => 'POST',
            'uri' => '/daifa/df.aspx',
            'ip' => $this->options['verify_ip'],
            'host' => $withdrawHost,
            'param' => http_build_query($this->withdrawRequestData),
            'header' => [],
            'timeout' => 60,
        ];

        $result = $this->curlRequest($curlParam);
        $parseData = json_decode($result, true);

        // 對返回結果做檢查
        $this->withdrawResultVerify($parseData);

        // prc=1, errcode=00才是成功
        if ($parseData['prc'] !== '1' || $parseData['errcode'] !== '00') {
            throw new PaymentConnectionException($parseData['msg'], 180124, $this->getEntryId());
        }

        // 紀錄出款明細的支付平台參考編號
        $this->setCashWithdrawEntryRefId($parseData['tradeno']);
    }

    /**
     * 支付時的加密
     *
     * @return string
     */
    protected function encode()
    {
        // 加密方式 scode | orderid & amount & currcode | callbackurl & key
        $encodeStr = sprintf(
            '%s|%s&%s&%s|%s&%s',
            $this->requestData['scode'],
            $this->requestData['orderid'],
            $this->requestData['amount'],
            $this->requestData['currcode'],
            $this->requestData['callbackurl'],
            $this->privateKey
        );

        return md5($encodeStr);
    }

    /**
     * 訂單查詢時的加密
     *
     * @return string
     */
    protected function trackingEncode()
    {
        // 加密格式 scode | orderid & key
        $encodeStr = sprintf(
            '%s|%s&%s',
            $this->trackingRequestData['scode'],
            $this->trackingRequestData['orderid'],
            $this->privateKey
        );

        return md5($encodeStr);
    }

    /**
     * 出款時的加密
     *
     * @return string
     */
    protected function withdrawEncode()
    {
        // 加密格式 scode | orderno & money | timestamp & key
        $encodeStr = sprintf(
            '%s|%s&%s|%s&%s',
            $this->withdrawRequestData['scode'],
            $this->withdrawRequestData['orderno'],
            $this->withdrawRequestData['money'],
            $this->withdrawRequestData['timestamp'],
            $this->privateKey
        );

        return md5($encodeStr);
    }
}

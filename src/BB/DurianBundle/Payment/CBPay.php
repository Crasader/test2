<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 網銀在線
 *
 * 支付驗證：
 * 1. 驗證不可為空的參數
 * 2. 設定參數
 * 3. 額外處理的參數
 * 4. 設定encodeStr(加密後的字串)
 *
 * 解密驗證：
 * 1. 驗證key
 * 2. 設定參數
 * 3. 驗證結果是否相符
 */
class CBPay extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'v_mid'         => '',    //商戶編號
        'v_oid'         => '',    //訂單編號
        'v_amount'      => '',    //訂單總金額
        'v_moneytype'   => 'CNY', //幣別
        'v_url'         => '',    //支付成功導回的URL
        'pmode_id'      => '',    //支付方式
        'remark1'       => '',    //備註1
        'remark2'       => '',    //備註2
        'v_rcvname'     => '',    //收貨人姓名
        'v_rcvaddr'     => '',    //收貨人地址
        'v_rcvtel'      => '',    //收貨人電話
        'v_rcvpost'     => '',    //收貨人郵編
        'v_rcvemail'    => '',    //收貨人Email
        'v_rcvmobile'   => '',    //收貨人手機
        'v_ordername'   => '',    //訂貨人姓名
        'v_orderaddr'   => '',    //訂貨人地址
        'v_ordertel'    => '',    //訂貨人電話
        'v_orderpost'   => '',    //訂貨人郵編
        'v_orderemail'  => '',    //訂貨人Email
        'v_ordermobile' => '',    //訂貨人手機
        'v_md5info'     => ''     //商戶密鑰
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'v_mid' => 'number',
        'v_amount' => 'amount',
        'v_oid' => 'orderId',
        'v_url' => 'notify_url',
        'pmode_id' => 'paymentVendorId',
        'v_ordername' => 'username'
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'v_amount',
        'v_moneytype',
        'v_oid',
        'v_mid',
        'v_url'
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'v_oid' => 1,
        'v_pstatus' => 1,
        'v_amount' => 1,
        'v_moneytype' => 1,
    ];

    /**
     * 應答機制訊息
     *
     * @var string
     */
    protected $msg = 'ok';

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        '1' => '1025', //中國工商銀行
        '2' => '301', //交通銀行
        '3' => '103', //中國農業銀行
        '4' => '1051', //中國建設銀行
        '5' => '3080', //招商銀行
        '6' => '305', //中國民生銀行
        '8' => '314', //上海浦東發展銀行
        '9' => '310', //北京銀行
        '10' => '309', //興業銀行
        '11' => '313', //中信銀行
        '12' => '312', //中國光大銀行
        '13' => '311', //華夏銀行
        '14' => '3061', //廣東發展銀行
        '15' => '307', //平安銀行
        '16' => '3230', //郵政儲蓄銀行
        '17' => '104', //中國銀行
        '19' => '326', //上海銀行
        '220' => '324', //杭州銀行
        '222' => '302', //寧波銀行
        '226' => '316', //南京銀行
        '228' => '343', //上海農村商業銀行
        '234' => '335' //北京農村商業銀行
    ];

    /**
     * 查詢時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $trackingRequestData = [
        'v_oid'      => '', //訂單編號
        'v_mid'      => '', //商戶編號
        'v_url'      => '', //支付成功導回的URL
        'billNo_md5' => ''  //商戶密鑰
    ];

    /**
     * 查詢時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $trackingRequireMap = [
        'v_oid' => 'orderId',
        'v_mid' => 'number'
    ];

    /**
     * 查詢時需要加密的參數
     *
     * @var array
     */
    protected $trackingEncodeParams = ['v_oid'];

    /**
     * 查詢解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $trackingDecodeParams = [
        'v_oid' => 1,
        'v_pstatus' => 1,
        'v_amount' => 1,
        'v_moneytype' => 1,
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
            '%s?pay_system=%s&hallid=%s',
            $this->options['notify_url'],
            $this->options['merchantId'],
            $this->options['domain']
        );

        // 從內部給定值到參數
        foreach ($this->requireMap as $paymentKey => $internalKey) {
            $this->requestData[$paymentKey] = $this->options[$internalKey];
        }

        //帶入未支援的銀行就噴例外
        if (!array_key_exists($this->requestData['pmode_id'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor not support by PaymentGateway', 180066);
        }

        //額外的參數設定
        $this->requestData['v_amount'] = sprintf('%.2f', $this->requestData['v_amount']);
        $date = new \DateTime($this->options['orderCreateDate']);
        $this->requestData['v_oid'] = sprintf(
            '%s-%s-%s',
            $date->format('Ymd'),
            $this->requestData['v_mid'],
            $this->requestData['v_oid']
        );
        $this->requestData['pmode_id'] = $this->bankMap[$this->requestData['pmode_id']];
        $this->requestData['remark2'] = sprintf('[url:=%s]', $this->requestData['v_url']);

        //設定支付平台需要的加密串
        $this->requestData['v_md5info'] = strtoupper($this->encode());

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

        $encodeStr = '';

        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options)) {
                $encodeStr .= $this->options[$paymentKey];
            }
        }

        //進行加密
        $encodeStr .= $this->privateKey;

        //如果沒有v_md5str也要丟例外(其他參數都在前面驗證過了，所以不須要驗證)
        if (!isset($this->options['v_md5str'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['v_md5str'] != strtoupper(md5($encodeStr))) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['v_pstatus'] != '20') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        //取得訂單號，返回的格式為：時間(Ymd)-商號-訂單號
        $orderMessage = explode('-', $this->options['v_oid']);
        $replyOrderId = $orderMessage[2];

        if ($replyOrderId != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['v_amount']!= $entry['amount']) {
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

        // 設定訂單查詢提交參數
        $date = new \DateTime($this->options['orderCreateDate']);
        $this->trackingRequestData['v_oid'] = sprintf(
            '%s-%s-%s',
            $date->format('Ymd'),
            $this->trackingRequestData['v_mid'],
            $this->trackingRequestData['v_oid']
        );

        $this->trackingRequestData['billNo_md5'] = strtoupper($this->trackingEncode());

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'method' => 'GET',
            'uri' => '/receiveorder.jsp',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => http_build_query($this->trackingRequestData),
            'header' => []
        ];

        // 取得訂單查詢結果
        $result = $this->curlRequest($curlParam);

        $returnData = [];

        $pattern = '/name="(.*)" value="([^"]*)"/';
        $input = strip_tags($result, '<input>');
        $out = [];
        preg_match_all($pattern, $input, $out);

        if (isset($out[1]) && isset($out[2])) {
            $returnData = array_combine($out[1], $out[2]);
        }

        // 如果訂單查詢返回結果解析異常就丟例外
        if (!$returnData) {
            throw new PaymentConnectionException('Payment tracking failed', 180081, $this->getEntryId());
        }

        $this->trackingResultVerify($returnData);

        $encodeStr = '';

        foreach (array_keys($this->trackingDecodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $returnData)) {
                $encodeStr .= $returnData[$paymentKey];
            }
        }

        // 額外的加密設定
        $encodeStr .= $this->privateKey;

        // 如果交易狀態為0代表訂單不存在
        if ($returnData['v_pstatus'] === '0') {
            throw new PaymentConnectionException('Order does not exist', 180060, $this->getEntryId());
        }

        // 如果交易狀態為10代表訂單未支付
        if ($returnData['v_pstatus'] === '10') {
            throw new PaymentConnectionException('Unpaid order', 180062, $this->getEntryId());
        }

        // 如果交易狀態非20(支付成功)代表交易失敗
        if ($returnData['v_pstatus'] !== '20') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($returnData['v_md5str'] != strtoupper(md5($encodeStr))) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($returnData['v_amount'] != $this->options['amount']) {
            throw new PaymentException('Order Amount error', 180058);
        }

        if ($returnData['v_oid'] != $this->trackingRequestData['v_oid']) {
            throw new PaymentException('Order Id error', 180061);
        }
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

        $this->trackingVerify();

        // 從內部給定值到參數
        foreach ($this->trackingRequireMap as $paymentKey => $internalKey) {
            $this->trackingRequestData[$paymentKey] = $this->options[$internalKey];
        }

        // 設定訂單查詢提交參數
        $date = new \DateTime($this->options['orderCreateDate']);
        $this->trackingRequestData['v_oid'] = sprintf(
            '%s-%s-%s',
            $date->format('Ymd'),
            $this->trackingRequestData['v_mid'],
            $this->trackingRequestData['v_oid']
        );

        $this->trackingRequestData['billNo_md5'] = strtoupper($this->trackingEncode());

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'verify_ip' => $this->options['verify_ip'],
            'path' => '/receiveorder.jsp?' . http_build_query($this->trackingRequestData),
            'method' => 'GET',
            'headers' => [
                'Host' => $this->options['verify_url']
            ]
        ];

        return $curlParam;
    }

    /**
     * 驗證訂單查詢是否成功
     */
    public function paymentTrackingVerify()
    {
        // 驗證私鑰
        $this->verifyPrivateKey();
        $this->trackingVerify();

        // 從內部給定值到參數
        foreach ($this->trackingRequireMap as $paymentKey => $internalKey) {
            $this->trackingRequestData[$paymentKey] = $this->options[$internalKey];
        }

        // 設定訂單查詢提交參數
        $date = new \DateTime($this->options['orderCreateDate']);
        $this->trackingRequestData['v_oid'] = sprintf(
            '%s-%s-%s',
            $date->format('Ymd'),
            $this->trackingRequestData['v_mid'],
            $this->trackingRequestData['v_oid']
        );

        $pattern = '/name="(.*)" value="([^"]*)"/';
        $input = strip_tags($this->options['content'], '<input>');
        $out = [];
        $decodeData = null;
        preg_match_all($pattern, $input, $out);

        if (isset($out[1]) && isset($out[2])) {
            $decodeData = array_combine($out[1], $out[2]);
        }

        // 如果訂單查詢返回結果解析異常就丟例外
        if (!$decodeData) {
            throw new PaymentConnectionException('Payment tracking failed', 180081, $this->getEntryId());
        }

        $this->trackingResultVerify($decodeData);

        $encodeStr = '';

        foreach (array_keys($this->trackingDecodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $decodeData)) {
                $encodeStr .= $decodeData[$paymentKey];
            }
        }

        // 額外的加密設定
        $encodeStr .= $this->privateKey;

        // 如果交易狀態為0代表訂單不存在
        if ($decodeData['v_pstatus'] === '0') {
            throw new PaymentConnectionException('Order does not exist', 180060, $this->getEntryId());
        }

        // 如果交易狀態為10代表訂單未支付
        if ($decodeData['v_pstatus'] === '10') {
            throw new PaymentConnectionException('Unpaid order', 180062, $this->getEntryId());
        }

        // 如果交易狀態非20(支付成功)代表交易失敗
        if ($decodeData['v_pstatus'] !== '20') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($decodeData['v_md5str'] != strtoupper(md5($encodeStr))) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($decodeData['v_amount'] != $this->options['amount']) {
            throw new PaymentException('Order Amount error', 180058);
        }

        if ($decodeData['v_oid'] != $this->trackingRequestData['v_oid']) {
            throw new PaymentException('Order Id error', 180061);
        }
    }
}

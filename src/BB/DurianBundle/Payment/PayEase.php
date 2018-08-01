<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 首信易
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
class PayEase extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'v_mid'         => '',  //商戶編號
        'v_oid'         => '',  //訂單編號
        'v_rcvname'     => '',  //收貨人姓名
        'v_rcvaddr'     => '',  //收貨人地址
        'v_rcvtel'      => '',  //收貨人電話
        'v_rcvpost'     => '',  //收貨人郵編
        'v_amount'      => '',  //訂單總金額
        'v_ymd'         => '',  //訂單產生日期
        'v_orderstatus' => '1', //配貨狀態
        'v_ordername'   => '',  //訂貨人姓名
        'v_moneytype'   => '0', //幣種
        'v_url'         => '',  //URL地址
        'v_md5info'     => ''   //MD5校驗碼
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'v_mid' => 'number',
        'v_ymd' => 'orderCreateDate',
        'v_oid' => 'orderId',
        'v_amount' => 'amount',
        'v_url' => 'notify_url',
        'v_ordername' => 'username'
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'v_moneytype',
        'v_ymd',
        'v_amount',
        'v_rcvname',
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
        'v_amount' => 1, //訂單總金額
        'v_moneytype' => 1 //幣種
    ];

    /**
     * 應答機制訊息
     *
     * @var string
     */
    protected $msg = 'sent';

    /**
     * 查詢時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $trackingRequestData = [
        'v_mid' => '', //商戶編號
        'v_oid' => '', //商戶訂單號
        'v_mac' => ''  //簽名數據
    ];

    /**
     * 查詢時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $trackingRequireMap = [
        'v_mid' => 'number',
        'v_oid' => 'orderId'
    ];

    /**
     * 查詢時需要加密的參數
     *
     * @var array
     */
    protected $trackingEncodeParams = [
        'v_mid',
        'v_oid'
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

        //額外的參數設定
        $date = new \DateTime($this->requestData['v_ymd']);
        $this->requestData['v_ymd'] = $date->format("Ymd");
        $this->requestData['v_oid'] = sprintf(
            '%s-%s-%s',
            $this->requestData['v_ymd'],
            $this->requestData['v_mid'],
            $this->requestData['v_oid']
        );
        $this->requestData['v_amount'] = sprintf('%.2f', $this->requestData['v_amount']);

        //設定支付平台需要的加密串
        $this->requestData['v_md5info'] = $this->encode();

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

        //組織加密串
        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options)) {
                $encodeStr .= $this->options[$paymentKey];
            }
        }

        //沒有v_md5money就要丟例外，其他的參數在組織加密串的時候驗證過了
        if (!isset($this->options['v_md5money'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        //getHamc()是首信易的加密方式
        if ($this->options['v_md5money'] != $this->getHamc($encodeStr)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        //沒有v_pstatus就要丟例外，其他的參數在組織加密串的時候驗證過了
        if (!isset($this->options['v_pstatus'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['v_pstatus'] != 20 && $this->options['v_pstatus'] != 1) {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        $orderMessage = explode('-', $this->options['v_oid']);
        $replyOrderId = $orderMessage[2]; //返回訂單號格式為：日期(Ymd)-商號-訂單號

        if ($replyOrderId != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['v_amount'] != $entry['amount']) {
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

        if ($this->options['orderCreateDate'] == '') {
            throw new PaymentException('No tracking parameter specified', 180138);
        }

        $date = new \DateTime($this->options['orderCreateDate']);
        $this->trackingRequestData['v_oid'] = sprintf(
            '%s-%s-%s',
            $date->format('Ymd'),
            $this->trackingRequestData['v_mid'],
            $this->trackingRequestData['v_oid']
        );
        $this->trackingRequestData['v_mac'] = $this->trackingEncode();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'method' => 'POST',
            'uri' => '/merchant/order/order_ack_oid_list.jsp',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => http_build_query($this->trackingRequestData),
            'header' => []
        ];

        // 取得訂單查詢結果
        $result = $this->curlRequest($curlParam);
        $parseData = $this->parseData($result);

        // 如果沒有$parseData['messagebody']['order']要丟例外
        if (!isset($parseData['messagebody']['order'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        // 如果沒有pstatus要丟例外
        if (!isset($parseData['messagebody']['order']['pstatus'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        if ($parseData['messagebody']['order']['pstatus'] == 2) {
            throw new PaymentConnectionException('Unpaid order', 180062, $this->getEntryId());
        }

        // 防止有其他狀態的情形發生，不等於1即為付款失敗
        if ($parseData['messagebody']['order']['pstatus'] != 1) {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($parseData['messagebody']['order']['amount'] != $this->options['amount']) {
            throw new PaymentException('Order Amount error', 180058);
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

        if ($this->options['orderCreateDate'] == '') {
            throw new PaymentException('No tracking parameter specified', 180138);
        }

        $date = new \DateTime($this->options['orderCreateDate']);
        $this->trackingRequestData['v_oid'] = sprintf(
            '%s-%s-%s',
            $date->format('Ymd'),
            $this->trackingRequestData['v_mid'],
            $this->trackingRequestData['v_oid']
        );
        $this->trackingRequestData['v_mac'] = $this->trackingEncode();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'verify_ip' => $this->options['verify_ip'],
            'path' =>'/merchant/order/order_ack_oid_list.jsp',
            'method' => 'POST',
            'form' => $this->trackingRequestData,
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

        $parseData = $this->parseData($this->options['content']);

        // 如果沒有$parseData['messagebody']['order']要丟例外
        if (!isset($parseData['messagebody']['order'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        // 如果沒有pstatus要丟例外
        if (!isset($parseData['messagebody']['order']['pstatus'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        if ($parseData['messagebody']['order']['pstatus'] == 2) {
            throw new PaymentConnectionException('Unpaid order', 180062, $this->getEntryId());
        }

        // 防止有其他狀態的情形發生，不等於1即為付款失敗
        if ($parseData['messagebody']['order']['pstatus'] != 1) {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($parseData['messagebody']['order']['amount'] != $this->options['amount']) {
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
        $encodeStr = '';

        //加密設定
        foreach ($this->encodeParams as $index) {
            $encodeStr .= $this->requestData[$index];
        }

        return $this->getHamc($encodeStr);
    }

    /**
     * 訂單查詢時的加密
     *
     * @return string
     */
    protected function trackingEncode()
    {
        $encodeStr = '';

        //加密設定
        foreach ($this->trackingEncodeParams as $index) {
            $encodeStr .= $this->trackingRequestData[$index];
        }

        return $this->getHamc($encodeStr);
    }

    /**
     * 首信易產生加密簽名的方式
     *
     * @param string $data
     * @return string
     */
    private function getHamc($data)
    {
        $key = $this->privateKey;
        $byteLength = 64;

        if (strlen($key) > $byteLength) {
            $key = pack("H*", md5($key));
        }

        $keyPad = str_pad($key, $byteLength, chr(0x00));
        $ipad = str_pad('', $byteLength, chr(0x36));
        $opad = str_pad('', $byteLength, chr(0x5c));
        $keyIpad = $keyPad ^ $ipad ;
        $keyOpad = $keyPad ^ $opad;

        return md5($keyOpad . pack("H*", md5($keyIpad . $data)));
    }

    /**
     * 入款查詢時使用，用來分解訂單查詢(補單)時回傳的XML格式
     *
     * @param string $content xml格式的回傳值
     * @return array
     */
    private function parseData($content)
    {
        return $this->xmlToArray($content);
    }
}

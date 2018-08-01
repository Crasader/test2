<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 新國付寶
 */
class NewGofPay extends PaymentBase
{
    /**
     * 支付提交參數
     *
     * @var array
     */
    protected $requestData = [
        'version' => '2.1', // 版本號
        'charset' => '1', // 字符集，可空。預設為1(GBK)
        'language' => '1', // 語言版本。預設為1(中文)
        'signType' => '1', // 加密方式，可空。預設為1(MD5)
        'tranCode' => '8888', // 交易代碼。支付網關接口必須為8888
        'merchantID' => '', // 商戶代碼
        'merOrderNum' => '', // 訂單號
        'tranAmt' => '', // 交易金額
        'feeAmt' => '0', // 手續費，可空。預設為0
        'currencyType' => '156', // 幣種。156為人民幣
        'frontMerUrl' => '', // 商戶前台通知地址
        'backgroundMerUrl' => '', // 商戶後台通知地址
        'tranDateTime' => '', // 交易時間
        'virCardNoIn' => '', // 國付寶轉入帳戶
        'tranIP' => '', // 用戶IP
        'isRepeatSubmit' => '0', // 訂單是否允許重複提交，可空。預設為0(不允許重複)
        'goodsName' => '', // 商品名稱，可空
        'goodsDetail' => '', // 商品詳情，可空
        'buyerName' => '', // 買方姓名，手機支付必填MWEB
        'buyerContact' => '', // 買方聯繫方式，可空
        'merRemark1' => '', // 商戶備用信息字段，可空
        'merRemark2' => '', // 商戶備用信息字段，可空
        'signValue' => '', // 密文串
        'gopayServerTime' => '', // 服務器時間
        'bankCode' => '', // 銀行代碼
        'userType' => '1', // 用戶類型。預設為1(個人支付)
    ];

    /**
     * 支付提交參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'merchantID' => 'number',
        'merOrderNum' => 'orderId',
        'tranAmt' => 'amount',
        'backgroundMerUrl' => 'notify_url',
        'tranDateTime' => 'orderCreateDate',
        'tranIP' => 'ip',
        'buyerName' => 'username',
        'bankCode' => 'paymentVendorId',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'version',
        'tranCode',
        'merchantID',
        'merOrderNum',
        'tranAmt',
        'feeAmt',
        'tranDateTime',
        'frontMerUrl',
        'backgroundMerUrl',
        'orderId',
        'gopayOutOrderId',
        'tranIP',
        'respCode',
        'gopayServerTime',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'version' => 1,
        'tranCode' => 1,
        'merchantID' => 1,
        'merOrderNum' => 1,
        'tranAmt' => 1,
        'feeAmt' => 0,
        'tranDateTime' => 1,
        'frontMerUrl' => 0,
        'backgroundMerUrl' => 1,
        'orderId' => 0,
        'gopayOutOrderId' => 0,
        'tranIP' => 1,
        'respCode' => 0,
        'gopayServerTime' => 0,
    ];

    /**
     * 應答機制訊息
     *
     * @var string
     */
    protected $msg = 'RespCode=0000|JumpURL=';

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        1 => 'ICBC', // 中國工商銀行
        2 => 'BOCOM', // 交通銀行
        3 => 'ABC', // 中國農業銀行
        4 => 'CCB', // 中國建設銀行
        5 => 'CMB', // 招商銀行
        6 => 'CMBC', // 中國民生銀行
        8 => 'SPDB', // 上海浦東發展銀行
        9 => 'BOBJ', // 北京銀行
        10 => 'CIB', // 興業銀行
        11 => 'CITIC', // 中信銀行
        12 => 'CEB', // 光大銀行
        13 => 'HXBC', // 華夏銀行
        14 => 'GDB', // 廣東發展銀行
        15 => 'PAB', // 平安銀行
        16 => 'PSBC', // 中國郵政儲蓄銀行
        17 => 'BOC', // 中國銀行
        19 => 'BOS', // 上海銀行
        222 => 'NBCB', // 寧波銀行
        226 => 'NJCB', // 南京銀行
        1003 => 'ICBC', // 工商銀行(快)
        1005 => 'ABC', // 農業銀行(快)
        1006 => 'CCB', // 建設銀行(快)
        1007 => 'CMB', // 招商銀行(快)
        1009 => 'SPDB', // 浦發銀行(快)
        1011 => 'CIB', // 興業銀行(快)
        1013 => 'CEB', // 光大銀行(快)
        1015 => 'GDB', // 廣發銀行(快)
        1017 => 'PSBC', // 郵政儲蓄銀行(快)
        1018 => 'BOC', // 中國銀行(快)
    ];

    /**
     * 手機支付銀行
     *
     * @var array
     */
    protected $wapBank = [
        1003,
        1005,
        1006,
        1007,
        1009,
        1011,
        1013,
        1015,
        1017,
        1018,
    ];

    /**
     * 查詢時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $trackingRequestData = [
        'tranCode' => '4020', // 交易代码
        'tranDateTime' => '', // 交易時間
        'merOrderNum' => '', // 訂單號
        'merchantID' => '', // 商戶ID
        'orgOrderNum' => '', // 原訂單號
        'orgtranDateTime' => '', // 原交易時間
        'tranIP' => '', // 用戶IP
        'msgExt' => '', // 附加信息
        'orgtranAmt' => '', // 原交易金額
        'signValue' => '', // 加密簽名
    ];

    /**
     * 查詢時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $trackingRequireMap = [
        'merchantID' => 'number',
        'merOrderNum' => 'orderId',
        'orgOrderNum' => 'orderId',
        'orgtranDateTime' => 'orderCreateDate',
        'orgtranAmt' => 'amount',
    ];

    /**
     * 查詢時需要加密的參數
     *
     * @var array
     */
    protected $trackingEncodeParams = [
        'tranCode',
        'merchantID',
        'merOrderNum',
        'tranAmt',
        'ticketAmt',
        'tranDateTime',
        'currencyType',
        'merURL',
        'customerEMail',
        'authID',
        'orgOrderNum',
        'orgtranDateTime',
        'orgtranAmt',
        'orgTxnType',
        'orgTxnStat',
        'msgExt',
        'virCardNo',
        'virCardNoIn',
        'tranIP',
        'isLocked',
        'feeAmt',
        'respCode',
    ];

    /**
     * 查詢解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $trackingDecodeParams = [
        'tranCode' => 1,
        'merchantID' => 1,
        'merOrderNum' => 1,
        'tranAmt' => 1,
        'ticketAmt' => 0,
        'tranDateTime' => 1,
        'currencyType' => 1,
        'merURL' => 1,
        'customerEMail' => 1,
        'authID' => 1,
        'orgOrderNum' => 1,
        'orgtranDateTime' => 1,
        'orgtranAmt' => 1,
        'orgTxnType' => 1,
        'orgTxnStat' => 1,
        'msgExt' => 0,
        'virCardNo' => 1,
        'virCardNoIn' => 0,
        'tranIP' => 1 ,
        'isLocked'=> 1,
        'feeAmt' => 1,
        'respCode' => 1,
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

        // 驗證支付參數
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

        // 額外的驗證項目
        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        // 檢查銀行代碼是否支援
        if (!array_key_exists($this->requestData['bankCode'], $this->bankMap)) {
            throw new PaymentException(
                'PaymentVendor is not supported by PaymentGateway',
                180066
            );
        }

        $curlParam = [
            'method' => 'GET',
            'uri' => '/time.do',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => '',
            'header' => [],
        ];

        $getPaymentServerTime = $this->curlRequest($curlParam);

        // 取得商家附加設定值
        $merchantExtraValues = $this->getMerchantExtraValue(['virCardNoIn']);

        // 額外的參數設定
        $tranDateTime = new \DateTime($this->requestData['tranDateTime']);
        $this->requestData['virCardNoIn'] = $merchantExtraValues['virCardNoIn'];
        $this->requestData['tranDateTime'] = $tranDateTime->format('YmdHis');
        $this->requestData['tranAmt'] = sprintf('%.2f', $this->requestData['tranAmt']);
        $this->requestData['gopayServerTime'] = $getPaymentServerTime;
        $this->requestData['bankCode'] = $this->bankMap[$this->requestData['bankCode']];

        // 預設網銀提交網址
        $postUrl =  $this->options['postUrl'] . '/Trans/WebClientAction.do';

        // 手機支付需調整參數
        if (in_array($this->options['paymentVendorId'], $this->wapBank)) {
            $this->requestData['version'] = '2.2';
            $this->requestData['buyerName'] = 'MWEB';

            // 調整手機支付提交網址
            $postUrl =  $this->options['postUrl'] . '/Trans/MobileClientAction.do';
        }

        // 設定加密簽名
        $this->requestData['signValue'] = $this->encode();

        return [
            'post_url' => $postUrl,
            'params' => $this->requestData,
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

        // 國付寶後台回傳會少 hallid 參數，從這裡加回去。
        if (!strpos($this->options['backgroundMerUrl'], 'hallid') && isset($this->options['hallid'])) {
            $this->options['backgroundMerUrl'] .= "&hallid={$this->options['hallid']}";
        }

        $encodeStr = '';

        // 若$paymentKey不存在也需要加密，格式為$paymentKey=[]
        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options)) {
                $encodeStr .= $paymentKey . '=[' . $this->options[$paymentKey] . ']';
                continue;
            }

            $encodeStr .= $paymentKey . '=[]';
        }

        // 進行加密
        $encodeStr .= 'VerficationCode=[' . $this->privateKey . ']';

        // 沒有signValue就要丟例外
        if (!isset($this->options['signValue'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['signValue'] != md5($encodeStr)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['respCode'] != '0000') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['merOrderNum'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['tranAmt'] != $entry['amount']) {
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

        // 額外的參數設定
        $createAt = new \Datetime($this->trackingRequestData['orgtranDateTime']);
        $this->trackingRequestData['orgtranDateTime'] = $createAt->format('YmdHis');
        $this->trackingRequestData['tranDateTime'] = $this->trackingRequestData['orgtranDateTime'];

        // 強制使用第一組對外ip
        $this->trackingRequestData['tranIP'] = $this->options['verify_ip'][0];

        // 設定加密簽名
        $this->trackingRequestData['signValue'] = $this->trackingEncode();

        // 執行訂單查詢
        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'method' => 'POST',
            'uri' => '/Trans/WebClientAction.do',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => http_build_query($this->trackingRequestData),
            'header' => [],
        ];

        $result = $this->curlRequest($curlParam);

        // 檢查訂單查詢返回參數
        $parseData = $this->parseData($result);

        // 訂單不存在
        if (isset($parseData['errMessage']) && $parseData['errMessage'] == '订单不存在') {
            throw new PaymentConnectionException('Order does not exist', 180060, $this->getEntryId());
        }

        // 沒有respCode就要丟例外
        if (!isset($parseData['respCode'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        // 訂單查詢異常
        if ($parseData['respCode'] != '0000') {
            throw new PaymentConnectionException('Payment tracking failed', 180081, $this->getEntryId());
        }

        $this->trackingResultVerify($parseData);

        $encodeStr = '';

        // 返回的msgExt和virCardNoIn在加密時必須為空
        $parseData['msgExt'] = '';
        $parseData['virCardNoIn'] = '';

        // 組織加密串
        foreach (array_keys($this->trackingDecodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $parseData)) {
                $encodeStr .= $paymentKey . '=[' . $parseData[$paymentKey] . ']';
                continue;
            }

            $encodeStr .= $paymentKey . '=[]';
        }

        $encodeStr .= 'VerficationCode=[' . $this->privateKey . ']';

        // 沒有signValue就要丟例外，其他的參數在組織加密串的時候驗證過了
        if (!isset($parseData['signValue'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        if ($parseData['signValue'] != md5($encodeStr)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($parseData['orgTxnStat'] != '20000') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($parseData['orgtranAmt'] != $this->options['amount']) {
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

        // 驗證訂單查詢參數
        $this->trackingVerify();

        // 從內部給定值到參數
        foreach ($this->trackingRequireMap as $paymentKey => $internalKey) {
            $this->trackingRequestData[$paymentKey] = $this->options[$internalKey];
        }

        // 額外的參數設定
        $createAt = new \Datetime($this->trackingRequestData['orgtranDateTime']);
        $this->trackingRequestData['orgtranDateTime'] = $createAt->format('YmdHis');
        $this->trackingRequestData['tranDateTime'] = $this->trackingRequestData['orgtranDateTime'];

        // 強制使用第一組對外ip
        $this->trackingRequestData['tranIP'] = $this->options['verify_ip'][0];

        // 設定加密簽名
        $this->trackingRequestData['signValue'] = $this->trackingEncode();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'verify_ip' => $this->options['verify_ip'],
            'path' => '/Trans/WebClientAction.do',
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

        // 檢查訂單查詢返回參數
        $parseData = $this->parseData($this->options['content']);

        // 訂單不存在
        if (isset($parseData['errMessage']) && $parseData['errMessage'] == '订单不存在') {
            throw new PaymentConnectionException('Order does not exist', 180060, $this->getEntryId());
        }

        // 沒有respCode就要丟例外
        if (!isset($parseData['respCode'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        // 訂單查詢異常
        if ($parseData['respCode'] != '0000') {
            throw new PaymentConnectionException('Payment tracking failed', 180081, $this->getEntryId());
        }

        $this->trackingResultVerify($parseData);

        $encodeStr = '';

        // 返回的msgExt和virCardNoIn在加密時必須為空
        $parseData['msgExt'] = '';
        $parseData['virCardNoIn'] = '';

        // 組織加密串
        foreach (array_keys($this->trackingDecodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $parseData)) {
                $encodeStr .= $paymentKey . '=[' . $parseData[$paymentKey] . ']';
                continue;
            }

            $encodeStr .= $paymentKey . '=[]';
        }

        $encodeStr .= 'VerficationCode=[' . $this->privateKey . ']';

        // 沒有signValue就要丟例外，其他的參數在組織加密串的時候驗證過了
        if (!isset($parseData['signValue'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        if ($parseData['signValue'] != md5($encodeStr)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($parseData['orgTxnStat'] != '20000') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($parseData['orgtranAmt'] != $this->options['amount']) {
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

        // 加密設定
        foreach ($this->encodeParams as $index) {
            if (isset($this->requestData[$index])) {
                $encodeStr .= $index . '=[' . $this->requestData[$index] . ']';
                continue;
            }

            $encodeStr .= $index . '=[]';
        }

        $encodeStr .= 'VerficationCode=[' . $this->privateKey . ']';

        return md5($encodeStr);
    }

    /**
     * 訂單查詢時的加密
     *
     * @return string
     */
    protected function trackingEncode()
    {
        $encodeStr = '';

        // 加密設定
        foreach ($this->trackingEncodeParams as $index) {
            if (isset($this->trackingRequestData[$index])) {
                $encodeStr .= $index . '=[' . $this->trackingRequestData[$index] . ']';
                continue;
            }

            $encodeStr .= $index . '=[]';
        }

        $encodeStr .= 'VerficationCode=[' . $this->privateKey . ']';

        return md5($encodeStr);
    }

    /**
     * 分析支付平台回傳的入款查詢參數
     *
     * @param string $content
     * @return array
     */
    private function parseData($content)
    {
        return $this->xmlToArray($content);
    }
}

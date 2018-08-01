<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 口袋支付
 */
class KdPay extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'P_UserId' => '', // 商號
        'P_OrderId' => '', // 訂單號
        'P_CardId' => '', // 卡類充值時的卡號（非卡類交易時非必填）
        'P_CardPass' => '', // 卡類充值時的卡密（非卡類交易時非必填）
        'P_FaceValue' => '', // 支付金額，精確到小數第二位
        'P_ChannelId' => '1', // 充值類型（1: 網銀, 2: 支付寶, 21: 微信, 33: 微信_手機支付, 36: 支付寶_手機支付）
        'P_Subject' => '', // 產品名稱（存 username 方便業主對帳）
        'P_Price' => '', // 產品價格
        'P_Quantity' => '1', // 產品數量
        'P_Description' => '', // 銀行代碼（非網銀時非必填）
        'P_Notic' => '', // 用戶附加訊息（非必填）
        'P_Result_URL' => '', // 非同步通知網址
        'P_Notify_URL' => '', // 同步跳轉網址（非必填）
        'P_PostKey' => '', // 簽名認證字串
        'P_IsSmart' => '', // WAP 版快捷支付判斷（非必填）
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'P_UserId' => 'number',
        'P_OrderId' => 'orderId',
        'P_FaceValue' => 'amount',
        'P_Price' => 'amount',
        'P_Result_URL' => 'notify_url',
        'P_Subject' => 'username',
        'P_Description' => 'paymentVendorId',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'P_UserId',
        'P_OrderId',
        'P_CardId',
        'P_CardPass',
        'P_FaceValue',
        'P_ChannelId',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'P_UserId' => 1,
        'P_OrderId' => 1,
        'P_CardId' => 1,
        'P_CardPass' => 1,
        'P_FaceValue' => 1,
        'P_ChannelId' => 1,
        'P_PayMoney' => 1,
        'P_ErrCode' => 1,
    ];

    /**
     * 應答機制訊息
     *
     * @var string
     */
    protected $msg = 'errCode=0';

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        '1' => '10001', // 中國工商銀行
        '2' => '10008', // 交通銀行
        '3' => '10002', // 中國農業銀行
        '4' => '10005', // 中國建設銀行
        '5' => '10003', // 招商銀行
        '6' => '10006', // 中國民生銀行
        '7' => '10011', // 深圳發展銀行
        '8' => '10015', // 上海浦東發展銀行
        '9' => '10013', // 北京銀行
        '10' => '10009', // 興業銀行
        '11' => '10007', // 中信銀行
        '12' => '10010', // 中國光大銀行
        '13' => '10025', // 華夏銀行
        '14' => '10016', // 廣東發展銀行
        '15' => '10014', // 平安銀行
        '16' => '10012', // 中國郵政
        '17' => '10004', // 中國銀行
        '19' => '10023', // 上海銀行
        '217' => '10017', // 渤海銀行
        '220' => '10027', // 杭州銀行
        '221' => '10022', // 浙商银行
        '222' => '10019', // 寧波銀行
        '223' => '10018', // 東亞銀行
        '226' => '10021', // 南京銀行
        '228' => '10024', // 上海市農村商業銀行
        '233' => '10028', // 浙江稠州商業銀行
        '234' => '10020', // 北京農商行
        '1090' => '', // 微信（非網銀時不用傳銀行代碼，所以設定為空字串）
        '1092' => '', // 支付寶（非網銀時不用傳銀行代碼，所以設定為空字串）
        '1097' => '', // 微信_手機支付（非網銀時不用傳銀行代碼，所以設定為空字串）
        '1098' => '', // 支付寶_手機支付（非網銀時不用傳銀行代碼，所以設定為空字串）
    ];

    /**
     * 查詢時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $trackingRequestData = [
        'P_UserId' => '', // 商號
        'P_OrderId' => '', // 訂單號
        'P_ChannelId' => '1', // 充值類型（1: 網銀, 2: 支付寶, 21: 微信, 33: 微信_手機支付, 36: 支付寶_手機支付）
        'P_CardId' => '', // 卡類充值時的卡號（非卡類交易時非必填）
        'P_FaceValue' => '', // 支付金額，精確到小數第二位
        'P_PostKey' => '', // 簽名認證字串
    ];

    /**
     * 查詢時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $trackingRequireMap = [
        'P_UserId' => 'number',
        'P_OrderId' => 'orderId',
        'P_FaceValue' => 'amount',
    ];

    /**
     * 查詢時需要加密的參數
     *
     * @var array
     */
    protected $trackingEncodeParams = [
        'P_UserId',
        'P_OrderId',
        'P_ChannelId',
        'P_CardId',
        'P_FaceValue',
    ];

    /**
     * 查詢解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $trackingDecodeParams = [
        'P_UserId' => '1',
        'P_OrderId' => '1',
        'P_ChannelId' => '1',
        'P_CardId' => '1',
        'P_payMoney' => '1',
        'P_flag' => '1',
        'P_status' => '1',
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

        // 使用微信時修改為 21
        if ($this->options['paymentVendorId'] == 1090) {
            $this->requestData['P_ChannelId'] = 21;
        }

        // 使用支付寶時修改為 2
        if ($this->options['paymentVendorId'] == 1092) {
            $this->requestData['P_ChannelId'] = 2;
        }

        // 使用微信_手機支付時修改為 33
        if ($this->options['paymentVendorId'] == 1097) {
            $this->requestData['P_ChannelId'] = 33;
        }

        // 使用支付寶_手機支付時修改為 36
        if ($this->options['paymentVendorId'] == 1098) {
            $this->requestData['P_ChannelId'] = 36;
        }

        // 檢查銀行是否支援
        if (!array_key_exists($this->requestData['P_Description'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 轉成支付平台支援的銀行編碼
        $this->requestData['P_Description'] = $this->bankMap[$this->requestData['P_Description']];

        // 確保小數點只到第二位
        $this->requestData['P_FaceValue'] = sprintf('%.2f', $this->requestData['P_FaceValue']);
        $this->requestData['P_Price'] = sprintf('%.2f', $this->requestData['P_Price']);

        // 產生加密字串
        $this->requestData['P_PostKey'] = $this->encode();

        // 微信直連
        if (in_array($this->options['paymentVendorId'], [1090, 1097])) {
            // 取得微信直連對外返回參數
            $parseData = $this->getWeixinData();

            if (!isset($parseData['errcode']) || !isset($parseData['errmsg'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            if ($parseData['errcode'] != '0') {
                throw new PaymentConnectionException($parseData['errmsg'], 180130, $this->getEntryId());
            }

            if (!isset($parseData['qrcode'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            if ($this->options['paymentVendorId'] == 1090) {
                $this->setQrcode($parseData['qrcode']);

                return [];
            }

            if ($this->options['paymentVendorId'] == 1097) {
                return ['act_url' => $parseData['qrcode']];
            }
        }

        return $this->requestData;
    }

    /**
     * 驗證線上支付是否成功
     *
     * @param array $entry
     */
    public function verifyOrderPayment($entry)
    {
        $this->verifyPrivateKey();
        $this->payResultVerify();

        if (!isset($this->options['P_PostKey'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        // 檢查加密字串
        $encodeData = [];
        foreach (array_keys($this->decodeParams) as $key) {
            if (array_key_exists($key, $this->options)) {
                $encodeData[] = $this->options[$key];
            }
        }

        $encodeData[] = $this->privateKey;

        $encodeStr = implode('|', $encodeData);

        if ($this->options['P_PostKey'] !== md5($encodeStr)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if (!isset($this->options['P_ErrCode'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        // 檢查支付結果（只有 P_ErrCode 為 0 才是支付成功）
        if ($this->options['P_ErrCode'] != 0) {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        // 檢查訂單號
        if ($this->options['P_OrderId'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        // 檢查支付金額
        if ($this->options['P_FaceValue'] != $entry['amount'] || $this->options['P_PayMoney'] != $entry['amount']) {
            throw new PaymentException('Order Amount error', 180058);
        }
    }

    /**
     * 訂單查詢
     */
    public function paymentTracking()
    {
        $this->verifyPrivateKey();
        $this->trackingVerify();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        // 從內部給定值到參數
        foreach ($this->trackingRequireMap as $paymentKey => $internalKey) {
            $this->trackingRequestData[$paymentKey] = $this->options[$internalKey];
        }

        // 使用微信時修改為 21
        if ($this->options['paymentVendorId'] == 1090) {
            $this->trackingRequestData['P_ChannelId'] = 21;
        }

        // 使用支付寶時修改為 2
        if ($this->options['paymentVendorId'] == 1092) {
            $this->trackingRequestData['P_ChannelId'] = 2;
        }

        // 使用微信_手機支付時修改為 33
        if ($this->options['paymentVendorId'] == 1097) {
            $this->trackingRequestData['P_ChannelId'] = 33;
        }

        // 使用支付寶_手機支付時修改為 36
        if ($this->options['paymentVendorId'] == 1098) {
            $this->trackingRequestData['P_ChannelId'] = 36;
        }

        // 確保小數點只到第二位
        $this->trackingRequestData['P_FaceValue'] = sprintf('%.2f', $this->trackingRequestData['P_FaceValue']);

        $this->trackingRequestData['P_PostKey'] = $this->trackingEncode();

        $curlParam = [
            'method' => 'POST',
            'uri' => '/pay/query.aspx',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => http_build_query($this->trackingRequestData),
            'header' => [],
        ];

        // 取得訂單查詢結果
        $result = $this->curlRequest($curlParam);

        // 回傳內容為 key1=val1&key2=val2，要轉換成陣列
        $parseData = [];
        parse_str($result, $parseData);

        // 檢查回傳內容
        $this->trackingResultVerify($parseData);

        if (!isset($parseData['P_PostKey'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        // 驗證加密字串是否相符
        $encodeData = [];
        foreach (array_keys($this->trackingDecodeParams) as $key) {
            if (array_key_exists($key, $parseData)) {
                $encodeData[$key] = $parseData[$key];
            }
        }

        $encodeData['P_PostKey'] = $this->privateKey;
        $encodeStr = urldecode(http_build_query($encodeData));

        if ($parseData['P_PostKey'] !== md5($encodeStr)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        // 訂單狀態
        $pFlag = $parseData['P_flag'];
        $pStatus = $parseData['P_status'];

        // 訂單處理中
        if ($pFlag == 0 && $pStatus == 0) {
            throw new PaymentConnectionException('Order Processing', 180059, $this->getEntryId());
        }

        // 訂單支付失敗
        if ($pFlag != 1 || $pStatus != 1) {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        // 檢查訂單號
        if ($this->options['orderId'] != $parseData['P_OrderId']) {
            throw new PaymentException('Order Id error', 180061);
        }

        // 檢查金額是否相符
        if ($this->options['amount'] != $parseData['P_payMoney']) {
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
        $encodeStr = [];

        foreach ($this->encodeParams as $index) {
            $encodeStr[] = $this->requestData[$index];
        }

        $encodeStr[] = $this->privateKey;

        return md5(implode('|', $encodeStr));
    }

    /**
     * 訂單查詢時的加密
     *
     * @return string
     */
    protected function trackingEncode()
    {
        $encodeStr = [];

        foreach ($this->trackingEncodeParams as $index) {
            $encodeStr[$index] = $this->trackingRequestData[$index];
        }

        $encodeStr['P_PostKey'] = $this->privateKey;

        return md5(urldecode(http_build_query($encodeStr)));
    }

    /**
     * 取得微信直連對外返回參數
     *
     * @return array
     */
    private function getWeixinData()
    {
        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        if (!isset($this->options['shop_url']) || trim($this->options['shop_url']) == '') {
            throw new PaymentException('No shop_url specified', 180157);
        }

        $curlParam = [
            'method' => 'POST',
            'uri' => '/pay/KDQRSrc.aspx',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => urldecode(http_build_query($this->requestData)),
            'header' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:28.0) Gecko/20100101 Firefox/28.0',
                'Referer' => $this->options['shop_url'],
            ],
        ];

        $result = $this->curlRequest($curlParam);
        $parseData = json_decode($result, true);

        return $parseData;
    }
}

<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 32pay
 */
class Pay32 extends PaymentBase
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
        'P_ChannelId' => '1', // 充值類型
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
     * 取提交網址要傳給平台驗證的參數
     *
     * @var array
     */
    protected $getUrlData = [
        'P_UserId' => '', // 商號
        'P_ServiceName' => 'GetBankGateway', // 獲取地址類型
        'P_TimesTamp' => '', // 時間戳
        'P_Description' => '', // 描述說明
        'P_Notic' => '', // 商戶備註
        'P_PostKey' => '', // 簽名
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
     * 取提交網址時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $getUrlMap = [
        'P_UserId' => 'number',
        'P_TimesTamp' => 'orderCreateDate',
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
     * 取提交網址時需要加密的參數
     *
     * @var array
     */
    protected $getUrlParams = [
        'P_UserId',
        'P_ServiceName',
        'P_TimesTamp',
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
    protected $msg = 'ErrCode=0';

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
        '297' => '3', // 財付通
        '1090' => '21', // 微信
        '1092' => '2', // 支付寶
        '1097' => '33', // 微信_手機支付
        '1098' => '36', // 支付寶_手機支付
        '1103' => '89', // QQ
        '1104' => '92', // QQ_手機支付
        '1107' => '91', // 京東錢包
        '1109' => '90', // 百度錢包
        '1111' => '95', // 銀聯_二維
        '1115' => '121', // 微信_條碼
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

        // 檢查銀行是否支援
        if (!array_key_exists($this->requestData['P_Description'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 轉成支付平台支援的銀行編碼
        $this->requestData['P_Description'] = $this->bankMap[$this->requestData['P_Description']];

        // 非網銀需調整參數
        $payList = [297, 1090, 1092, 1097, 1098, 1103, 1104, 1107, 1109, 1111, 1115];
        if (in_array($this->options['paymentVendorId'], $payList)) {
            $this->requestData['P_ChannelId'] = $this->requestData['P_Description'];
            unset($this->requestData['P_Description']);
        }

        // 金額小數點到第二位
        $this->requestData['P_FaceValue'] = sprintf('%.2f', $this->requestData['P_FaceValue']);
        $this->requestData['P_Price'] = sprintf('%.2f', $this->requestData['P_Price']);

        // 產生加密字串
        $this->requestData['P_PostKey'] = $this->encode();

        // 對外取提交網址
        $parseData = $this->getPostUrl();

        // 驗證取支付網址返回是否成功
        $this->verifyGetUrlReturn($parseData);

        $postUrl = $parseData['P_SubmitUrl'];

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
        $this->verifyPrivateKey();
        $this->payResultVerify();

        // 組織加密串
        $encodeData = [];
        foreach (array_keys($this->decodeParams) as $key) {
            if (array_key_exists($key, $this->options)) {
                $encodeData[] = $this->options[$key];
            }
        }

        $encodeData[] = $this->privateKey;

        $encodeStr = implode('|', $encodeData);

        if (!isset($this->options['P_PostKey'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['P_PostKey'] !== md5($encodeStr)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
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
     * 對外取提交網址
     *
     * @return string
     */
    protected function getPostUrl()
    {
        // 從內部給定值到參數
        foreach ($this->getUrlMap as $paymentKey => $internalKey) {
            $this->getUrlData[$paymentKey] = $this->options[$internalKey];
        }

        // 調整參數
        $date = new \DateTime($this->getUrlData['P_TimesTamp']);
        $this->getUrlData['P_TimesTamp'] = $date->getTimestamp();

        $this->getUrlData['P_PostKey'] = $this->getPostUrlEncode();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'method' => 'POST',
            'uri' => '/Pay/KDSubmitUrl.aspx',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => http_build_query($this->getUrlData),
            'header' => [],
        ];

        $result = $this->curlRequest($curlParam);
        $parseData = json_decode($result, true);

        return $parseData;
    }

    /**
     * 驗證取支付網址返回是否成功
     *
     * @param array $parseData
     */
    private function verifyGetUrlReturn($parseData)
    {
        if (!isset($parseData['P_Errcode']) || !isset($parseData['P_ErrMsg'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        if ($parseData['P_Errcode'] !== '0') {
            throw new PaymentConnectionException($parseData['P_ErrMsg'], 180130, $this->getEntryId());
        }

        if (!isset($parseData['P_SubmitUrl'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }
    }

    /**
     * 對外取提交網址的加密
     *
     * @return string
     */
    protected function getPostUrlEncode()
    {
        $getPostUrlStr = [];

        foreach ($this->getUrlParams as $index) {
            $getPostUrlStr[] = $this->getUrlData[$index];
        }

        $getPostUrlStr[] = $this->privateKey;

        return md5(implode('|', $getPostUrlStr));
    }
}

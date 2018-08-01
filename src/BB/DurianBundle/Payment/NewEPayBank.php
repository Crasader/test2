<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 新E付
 */
class NewEPayBank extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'orderNo' => '', // 訂單號
        'commodityName' => '', // 商品名稱
        'commodityDesc' => '', // 商品描述
        'transAmt' => '', // 交易金額(以分為單位)
        'cardType' => '01', // 銀行卡類型(01:借記卡、02:貸記卡)
        'bankCode' => '', // 銀行縮寫
        'merchId' => '', // 商戶號
        'clientType' => '', // 客戶端類別(非必填)
        'returnUrl' => '', // 頁面通知網址
        'notifyUrl' => '', // 異步通知網址
        'sign' => '', // 簽名
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'orderNo' => 'orderId',
        'commodityName' => 'username',
        'commodityDesc' => 'username',
        'transAmt' => 'amount',
        'bankCode' => 'paymentVendorId',
        'merchId' => 'number',
        'returnUrl' => 'notify_url',
        'notifyUrl' => 'notify_url',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'cipherData' => 1,
    ];

    /**
     * 應答機制訊息
     *
     * @var string
     */
    protected $msg = '000000';

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        1 => 'ICBC', // 工商銀行
        2 => 'BOCO', // 交通銀行
        3 => 'ABC', // 農業銀行
        4 => 'CCB', // 建設銀行
        5 => 'CMB', // 招商銀行
        6 => 'CMBC', // 民生銀行
        8 => 'SPDB', // 浦發銀行
        9 => 'BCCB', // 北京銀行
        10 => 'CIB', // 興業銀行
        11 => 'ECITIC', // 中信銀行
        12 => 'CEB', // 光大銀行
        13 => 'HXB', // 華夏銀行
        14 => 'CGB', // 廣發銀行
        15 => 'PAB', // 平安銀行
        16 => 'PSBC', // 郵政儲蓄銀行
        17 => 'BOC', // 中國銀行
        19 => 'SHB', // 上海銀行
        1003 => 'ICBC', // 工商銀行(快)
        1004 => 'BOCO', // 交通銀行(快)
        1005 => 'ABC', // 農業銀行(快)
        1006 => 'CCB', // 建設銀行(快)
        1007 => 'CMB', // 招商銀行(快)
        1008 => 'CMBC', // 民生銀行(快)
        1009 => 'SPDB', // 浦發銀行(快)
        1010 => 'BCCB', // 北京銀行(快)
        1011 => 'CIB', // 興業銀行(快)
        1012 => 'ECITIC', // 中信銀行(快)
        1013 => 'CEB', // 光大銀行(快)
        1014 => 'HXB', // 華夏銀行(快)
        1015 => 'CGB', // 廣發銀行(快)
        1016 => 'PAB', // 平安銀行(快)
        1017 => 'PSBC', // 郵政儲蓄銀行(快)
        1018 => 'BOC', // 中國銀行(快)
        1019 => 'SHB', // 上海銀行(快)
    ];

    /**
     * 手機快捷支付銀行
     *
     * @var array
     */
    protected $quickBank = [
        1003,
        1004,
        1005,
        1006,
        1007,
        1008,
        1009,
        1010,
        1011,
        1012,
        1013,
        1014,
        1015,
        1016,
        1017,
        1018,
        1019,
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
        if (!array_key_exists($this->requestData['bankCode'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $this->requestData['bankCode'] = $this->bankMap[$this->requestData['bankCode']];
        $this->requestData['transAmt'] = strval(round($this->requestData['transAmt'] * 100));

        if ($this->options['orderCreateDate'] == '') {
            throw new PaymentException('No pay parameter specified', 180145);
        }

        // 設定最後實際傳給支付平台的資料
        $orderCreateDate = new \DateTime($this->options['orderCreateDate']);

        // 手機快捷支付銀行
        if (in_array($this->options['paymentVendorId'], $this->quickBank)) {
            // 移除手機快捷不需傳遞的參數
            unset($this->requestData['commodityName']);
            unset($this->requestData['commodityDesc']);
            unset($this->requestData['cardType']);

            $requestParams = [
                'merchId' => $this->requestData['merchId'],
                'msgId' => $this->requestData['orderNo'],
                'cipherData' => $this->encode(),
                'reqTime' => $orderCreateDate->format('YmdHis'),
                'encryptType' => 'rsa',
                'accountNo' => $this->options['username'],
            ];

            // 調整銀聯手機支付提交網址
            $postUrl = 'https://quick.' . $this->options['postUrl'] . 'quick/order/V2.0';

            return [
                'post_url' => $postUrl,
                'params' => $requestParams,
            ];
        }

        $requestParams = [
            'merchId' => $this->requestData['merchId'],
            'msgId' => $this->requestData['orderNo'],
            'cipherData' => $this->encode(),
            'reqTime' => $orderCreateDate->format('YmdHis'),
        ];

        // 調整網銀支付提交網址
        $postUrl = 'https://gateway.' . $this->options['postUrl'] . 'gateway/carpay/V2.0';

        return [
            'post_url' => $postUrl,
            'params' => $requestParams,
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

        // response解碼
        $response = $this->decryptResponse($this->options['cipherData']);

        // 如果沒有返回簽名要丟例外
        if (!isset($response['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        // 組織加密簽名，排除sign(加密簽名)
        foreach ($response as $key => $value) {
            if ($key != 'sign') {
                $encodeData[$key] = $value;
            }
        }

        // 針對$encodeData按字母做升序排列
        ksort($encodeData);

        // 依key1=value1&key2=value2&...&keyN=valueN組成字串
        $encodeStr = urldecode(http_build_query($encodeData));

        // utf-8轉gbk
        $str = iconv('UTF-8', 'gbk', $encodeStr);

        // md5兩次
        $md5StrOne = md5($str . '&key=' . $this->privateKey);
        $md5StrAgain = md5($md5StrOne . '&key=' . $this->privateKey);

        // 檢查驗簽
        if ($response['sign'] != $md5StrAgain) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        // 檢查支付狀態
        if ($response['origRespCode'] !== '0000') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        // 檢查訂單號
        if ($response['orderNo'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        // 檢查金額
        if ($response['transAmt'] != round($entry['amount'] * 100)) {
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

        // 組織加密簽名，排除sign(加密簽名)
        foreach ($this->requestData as $key => $value) {
            if ($key != 'sign') {
                $encodeData[$key] = $value;
            }
        }

        // 針對$encodeData按字母做升序排列
        ksort($encodeData);

        // 依key1=value1&key2=value2&...&keyN=valueN
        $encodeStr = urldecode(http_build_query($encodeData));

        // 先md5兩次
        $md5StrOne = md5($encodeStr . '&key=' . $this->privateKey);
        $md5StrAgain = md5($md5StrOne . '&key=' . $this->privateKey);

        // 將md5兩次後的字串加在參數串後，轉json格式，進行rsa加密
        $encodeData['sign'] = $md5StrAgain;
        $strToJson = json_encode($encodeData);

        // 待加密串長度大於117字元需分段加密，每117字元為一段，加密後再按照順序拼接成密串
        $sign = '';
        foreach (str_split($strToJson, 117) as $chunk) {
            $contentData = '';
            openssl_public_encrypt($chunk, $contentData, $this->getRsaPublicKey());
            $sign .= $contentData;
        }

        return base64_encode($sign);
    }

    /**
     * 解密業務參數
     *
     * @return string
     */
    private function decryptResponse($response)
    {
        $privateKey = $this->getRsaPrivateKey();

        // 先base64解碼
        $encodeStr = base64_decode(rawurldecode(urlencode($response)));

        // 待解密串長度大於128字元需分段解密，每128字元為一段，解密後再按照順序拼接成字串
        $dataStr = '';
        foreach (str_split($encodeStr, 128) as $chunk) {
            $decryptData = '';
            openssl_private_decrypt($chunk, $decryptData, $privateKey, OPENSSL_PKCS1_PADDING);
            $dataStr .= $decryptData;
        }

        // 轉字串編碼
        $detach = ['GB2312', 'UTF-8', 'GBK'];
        $trunStr = mb_detect_encoding($dataStr, $detach);
        $returnData = iconv($trunStr, 'utf8', $dataStr);

        return json_decode($returnData, true);
    }
}

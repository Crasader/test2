<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * moneypay(大立光)
 */
class Moneypay extends PaymentBase
{
    /**
     * 支付提交參數
     *
     * @var array
     */
    protected $requestData = [
        'version' => '4.2', // 版本，固定值
        'clientId' => '', // 商號
        'billNumber' => '', // 訂單號
        'money' => '', // 金額，保留小數點兩位，單位：元
        'productName' => '', // 商品名稱，帶入username
        'deviceModel' => 'PC', // 設備類別
        'gameNotifyUrl' => '', // 後台回調地址，不能串參數
        'gameName' => 'GAME', // 遊戲名稱
        'gameUserId' => '', // 遊戲玩家ID，帶入username
        'attach' => '', // 附加數據，帶入username
        'trxDateTime' => '', // 交易時間，格式Y-m-d H:i:s
        'ip' => '', // 客戶端ip
        'paymentMethod' => 'Internet Banking', // 支付方式，預設網銀
        'bankSegment' => '', // 銀行代碼，網銀必填
        'sign' => '', // 簽名
    ];

    /**
     * 支付提交參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'clientId' => 'number',
        'billNumber' => 'orderId',
        'money' => 'amount',
        'productName' => 'username',
        'gameNotifyUrl' => 'notify_url',
        'gameUserId' => 'username',
        'attach' => 'username',
        'trxDateTime' => 'orderCreateDate',
        'ip' => 'ip',
        'bankSegment' => 'paymentVendorId',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'version',
        'clientId',
        'billNumber',
        'money',
        'productName',
        'deviceModel',
        'gameNotifyUrl',
        'gameName',
        'gameUserId',
        'attach',
        'trxDateTime',
        'ip',
        'paymentMethod',
        'bankSegment',
    ];

    /**
     * 返回驗證時需要加密的參數
     *     0: 有返回就要加密的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'client_id' => 1,
        'billNumber' => 1,
        'vendorBillNumber' => 1,
        'ciphertext' => 1,
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
        1 => '1001', // 工商銀行
        2 => '1005', // 交通銀行
        3 => '1002', // 農業銀行
        4 => '1004', // 建設銀行
        5 => '1012', // 招商銀行
        6 => '1010', // 民生銀行總行
        8 => '1014', // 上海浦東發展銀行
        9 => '1016', // 北京銀行
        10 => '1013', // 興業銀行
        11 => '1007', // 中信銀行
        12 => '1008', // 光大銀行
        13 => '1009', // 華夏銀行
        14 => '1017', // 廣東發展銀行
        15 => '1011', // 平安銀行
        16 => '1006', // 中國郵政
        17 => '1003', // 中國銀行
        19 => '1025', // 上海銀行
        1090 => 'WeChat Payment', // 微信_二維
        1092 => 'Alipay', // 支付寶_二維
        1097 => 'WeChatWAP Payment', // 微信_手機支付
        1098 => 'AlipayWAP', // 支付寶_手機支付
        1103 => 'QQ Payment', // QQ_二維
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

        // 從內部設值到支付參數
        foreach ($this->requireMap as $paymentKey => $internalKey) {
            $this->requestData[$paymentKey] = $this->options[$internalKey];
        }

        // 檢查銀行代碼是否支援
        if (!array_key_exists($this->requestData['bankSegment'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $this->requestData['bankSegment'] = $this->bankMap[$this->requestData['bankSegment']];
        $this->requestData['money'] = sprintf('%.2f', $this->requestData['money']);
        $createAt = new \Datetime($this->requestData['trxDateTime']);
        $this->requestData['trxDateTime'] = $createAt->format('Y-m-d H:i:s');

        // 二維、手機支付
        if (in_array($this->options['paymentVendorId'], [1090, 1092, 1097, 1098, 1103])) {
            $this->requestData['paymentMethod'] = $this->requestData['bankSegment'];
            $this->requestData['bankSegment'] = '';

            $this->requestData['sign'] = $this->encode();
            $postData = ['ciphertext' => $this->encryptData()];

            if (trim($this->options['verify_url']) == '') {
                throw new PaymentException('No verify_url specified', 180140);
            }

            $curlParam = [
                'method' => 'POST',
                'uri' => '/formal/payment_v4.2/payment.php',
                'ip' => $this->options['verify_ip'],
                'host' => $this->options['verify_url'],
                'param' => http_build_query($postData),
                'header' => [],
            ];

            $result = $this->curlRequest($curlParam);
            $parseData = json_decode($result, true);

            // 有錯誤訊息直接印出
            if (isset($parseData['error']) && isset($parseData['msg'])) {
               throw new PaymentConnectionException($parseData['msg'], 180130, $this->getEntryId());
            }

            if (!isset($parseData['url'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            return [
                'post_url' => $parseData['url'],
                'params' => [],
            ];
        }

        $this->requestData['sign'] = $this->encode();
        $postData = ['ciphertext' => $this->encryptData()];

        return $postData;
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

        // 驗證返回參數
        $this->payResultVerify();

        // 解密返回參數
        $str = $this->decryptData($this->options['ciphertext']);

        $parseData = [];
        parse_str($str, $parseData);

        // 檢查解密後的參數
        if (!isset($parseData['status']) || !isset($parseData['amount'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($parseData['status'] != 'success') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['vendorBillNumber'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($parseData['amount'] != $entry['amount']) {
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

        foreach ($this->encodeParams as $index) {
            $encodeData[$index] = $this->requestData[$index];
        }

        ksort($encodeData);
        $encodeData['key'] = $this->privateKey;
        $encodeStr = urldecode(http_build_query($encodeData));

        return md5($encodeStr);
    }

    /**
     * 支付時的RSA加密
     *
     * @return string
     */
    private function encryptData()
    {
        $encodeData = [];

        foreach ($this->requestData as $key => $value) {
            $encodeData[$key] = $value;
        }

        ksort($encodeData);
        $encodeStr = urldecode(http_build_query($encodeData));

        $strArray = str_split($encodeStr, 117);
        $pubKey = $this->getRsaPublicKey();

        $ret = '';
        foreach ($strArray as $cip) {
            $result = '';
            openssl_public_encrypt($cip, $result, $pubKey, OPENSSL_PKCS1_PADDING);
            $ret .= $result;
        }

       return base64_encode($ret);
    }

    /**
     * 解密返回的參數
     *
     * @param string $encParam
     * @return array
     */
    private function decryptData($encParam)
    {
        $data = base64_decode($encParam);

        $strArray = str_split($data, 128);
        $priKey = $this->getRsaPrivateKey();

        $ret = '';
        foreach ($strArray as $cip) {
            $result = '';

            // 避免RSA解密錯誤，先檢查解密是否成功
            if (!openssl_private_decrypt($cip, $result, $priKey, OPENSSL_PKCS1_PADDING)) {
                throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
            }
            $ret .= $result;
        }

        return $ret;
    }
}

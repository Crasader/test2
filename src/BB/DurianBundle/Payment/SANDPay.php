<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * SAND代付
 */
class SANDPay extends PaymentBase
{
    /**
     * 出款時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $withdrawRequestData = [
        'version' => '01', // 版本號，固定值
        'productId' => '00000004', // 產品ID，固定值
        'tranTime' => '', // 交易時間
        'orderCode' => '', // 訂單號
        'tranAmt' => '', // 金額，以分為單位
        'currencyCode' => '156', // 幣種，固定值
        'accAttr' => '0', // 帳戶屬性，0:對私
        'accType' => '4', // 帳號類型，4:銀行卡
        'accNo' => '', // 收款人帳戶號
        'accName' => '', // 收款人帳戶名
        'bankName' => '', // 收款帳戶開戶行名稱
        'remark' => '', // 摘要
        'noticeUrl' => '', // 代付結果通知地址
    ];

    /**
     * 出款時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $withdrawRequireMap = [
        'tranTime' => 'orderCreateDate',
        'orderCode' => 'orderId',
        'tranAmt' => 'amount',
        'accNo' => 'account',
        'accName' => 'nameReal',
        'bankName' => 'bank_info_id',
        'remark' => 'orderId',
        'noticeUrl' => 'shop_url',
    ];

    /**
     * 出款時需要加密的參數
     *
     * @var array
     */
    protected $withdrawEncodeParams = [
        'version',
        'productId',
        'tranTime',
        'orderCode',
        'tranAmt',
        'currencyCode',
        'accAttr',
        'accType',
        'accNo',
        'accName',
        'bankName',
        'remark',
        'noticeUrl',
    ];

    /**
     * 出款支援的銀行對應編號
     *
     * @var array
     */
    protected $withdrawBankMap = [
        1 => '中国工商银行', // 中國工商銀行
        2 => '交通银行', // 交通銀行
        3 => '中国农业银行', // 農業銀行
        4 => '中国建设银行', // 建設銀行
        5 => '招商银行', // 招商銀行
        6 => '中国民生银行', // 民生銀行
        8 => '上海浦东发展银行', // 浦發銀行
        9 => '北京银行', // 北京銀行
        10 => '兴业银行', // 興業銀行
        11 => '中信银行', // 中信銀行
        12 => '中国光大银行', // 光大銀行
        13 => '华夏银行', // 華夏銀行
        14 => '广东发展银行', //广东发展银行
        15 => '平安银行', // 平安銀行
        16 => '中国邮政储蓄银行', // 中國郵政儲蓄銀行
        17 => '中国银行', // 中國銀行
        19 => '上海银行', // 上海銀行
        217 => '渤海银行', // 渤海銀行
        221 => '浙商银行', // 浙商銀行
        311 => '恒丰银行', // 恒豐銀行
    ];

    /**
     * 線上出款
     */
    public function withdrawPayment()
    {
        $this->withdrawVerify();

        // 從內部給定值到參數
        foreach ($this->withdrawRequireMap as $paymentKey => $internalKey) {
            $this->withdrawRequestData[$paymentKey] = $this->options[$internalKey];
        }

        // 額外的參數設定
        $this->withdrawRequestData['orderCode'] = str_pad($this->withdrawRequestData['orderCode'], 12, 'x', STR_PAD_LEFT);
        $this->withdrawRequestData['bankName'] = $this->withdrawBankMap[$this->withdrawRequestData['bankName']];
        $this->withdrawRequestData['tranAmt'] = round($this->withdrawRequestData['tranAmt'] * 100);
        $this->withdrawRequestData['tranAmt'] = str_pad($this->withdrawRequestData['tranAmt'], 12, '0', STR_PAD_LEFT);
        $createAt = new \Datetime($this->withdrawRequestData['tranTime']);
        $this->withdrawRequestData['tranTime'] = $createAt->format('YmdHis');

        // 產生AESKey，隨機16碼
        $aESKey = substr(md5(uniqid(rand(), true)), 0, 16);

        // AESKey加密
        $encryptKey = '';
        openssl_public_encrypt($aESKey, $encryptKey, $this->getRsaPublicKey(), OPENSSL_PKCS1_PADDING);

        // 傳送參數加密
        $encryptData = $this->aesEncode($aESKey);

        // 業務參數
        $paramData = [
            'transCode' => 'RTPM',
            'merId' => $this->options['number'],
            'encryptKey' => base64_encode($encryptKey),
            'encryptData' => base64_encode($encryptData),
            'sign' => $this->withdrawEncode(),
        ];

        // 出款
        $curlParam = [
            'method' => 'POST',
            'uri' => '/agent-main/openapi/agentpay',
            'ip' => $this->options['verify_ip'],
            'host' => trim($this->options['withdraw_host']),
            'param' => http_build_query($paramData),
            'header' => [],
            'timeout' => 60,
        ];

        $encryptData = $this->curlRequest($curlParam);
        $parseData = $this->parseData($encryptData);

        // 對返回結果做檢查
        if (!isset($parseData['encryptKey']) || !isset($parseData['encryptData'])) {
            throw new PaymentException('No withdraw return parameter specified', 150180209);
        }

        // response解碼
        $response = $this->decryptResponse($parseData['encryptKey'], $parseData['encryptData']);

        // 非0即為出款提交失敗
        if ($response['resultFlag'] !== '0') {
            throw new PaymentConnectionException($response['respDesc'], 180124, $this->getEntryId());
        }
    }

    /**
     * AES加密
     *
     * @param array $data
     * @return string
     */
    private function aesEncode($key)
    {
        $data = json_encode($this->withdrawRequestData);

        $encryptData = openssl_encrypt($data, 'AES-128-ECB', $key, 1);

        return $encryptData;
    }

    /**
     * 出款時的加密
     *
     * @return string
     */
    protected function withdrawEncode()
    {
        $data = json_encode($this->withdrawRequestData);

        $sign = '';
        if (!openssl_sign($data, $sign, $this->getRsaPrivateKey())) {
            throw new PaymentException('Generate signature failure', 180144);
        }

        return base64_encode($sign);
    }

    /**
     * 回傳RSA憑證公鑰
     *
     * @return resource
     */
    public function getRsaPublicKey()
    {
        $content = $this->options['rsa_public_key'];

        if (!$content) {
            throw new PaymentException('Rsa public key is empty', 180095);
        }

        $cert = sprintf(
            '%s%s%s',
            "-----BEGIN CERTIFICATE-----\n",
            chunk_split($content, 64, "\n"),
            "-----END CERTIFICATE-----\n"
        );

        $publicKey = openssl_pkey_get_public($cert);

        if (!$publicKey) {
            throw new PaymentException('Get rsa public key failure', 180096);
        }

        return $publicKey;
    }

    /**
     * 分析支付平台回傳的入款查詢參數
     *
     * @param string $content
     * @return array
     */
    private function parseData($content)
    {
        $parseData = [];

        parse_str($content, $parseData);

        return $parseData;
    }

    /**
     * 解密業務參數
     *
     * @param string $key AES key 密文
     * @param string $data 業務參數密文
     * @return string
     */
    private function decryptResponse($key, $data)
    {
        // AESKey先base64解碼
        $encryptKey = base64_decode($key);

        // AESKey 解密
        $decryptAESKey = '';
        openssl_private_decrypt($encryptKey, $decryptAESKey, $this->getRsaPrivateKey(), OPENSSL_PKCS1_PADDING);

        // encryptData先base64解碼
        $encryptDataDecode = base64_decode($data);

        // 參數解密
        $encryptData = openssl_decrypt($encryptDataDecode, 'AES-128-ECB', $decryptAESKey, 1);

        return json_decode($encryptData, true);
    }
}

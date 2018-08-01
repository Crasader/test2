<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 快錢
 */
class Bill99 extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'inputCharset'     => '1',    //編碼,1代表UTF-8
        'pageUrl'          => '',     //接受支付结果的頁面地址
        'bgUrl'            => '',     //服務器接受支付结果地址
        'version'          => 'v2.0', //版本
        'language'         => '1',    //語言種類,1代表中文顯示
        'signType'         => '4',    //簽名類型,4代表DSA或RSA簽名方式
        'merchantAcctId'   => '',     //人民幣帳號
        'payerName'        => '',     //支付人姓名
        'payerContactType' => '1',    //支付人聯繫方式類型,1代表電子郵件方式
        'payerContact'     => '',     //支付人聯繫方式
        'payerIdType'      => '',     //指定付款人
        'payerId'          => '',     //付款人標識
        'payerIP'          => '',     //付款人IP
        'orderId'          => '',     //訂單號
        'orderAmount'      => '',     //訂單金額
        'orderTime'        => '',     //訂單提交時間
        'orderTimestamep'  => '',     //快錢時間戳
        'productName'      => '',     //商品名稱
        'productNum'       => '',     //商品數量
        'productId'        => '',     //商品代碼
        'productDesc'      => '',     //商品描述
        'ext1'             => '',     //擴展字段1
        'ext2'             => '',     //擴展自段2
        'payType'          => '10',   //支付方式,10代表只顯示銀行卡支付方式
        'bankId'           => '',     //銀行代碼
        'cardIsssuer'      => '',     //發卡機構
        'cardNum'          => '',     //卡號
        'remitType'        => '',     //線下匯款類型
        'remitCode'        => '',     //匯款識別碼
        'redoFlag'         => '1',    //訂單禁止重複提交標誌
        'pid'              => '',     //快錢的用戶編號
        'submitType'       => '',     //提交方式
        'extDataType'      => '',     //附加信息類型
        'extDataContent'   => '',     //附加信息
        'signMsg'          => ''      //簽名字符串
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'merchantAcctId' => 'number',
        'bankId' => 'paymentVendorId',
        'orderAmount' => 'amount',
        'orderId' => 'orderId',
        'bgUrl' => 'notify_url',
        'payerName' => 'username',
        'productName' => 'username',
        'orderTime' => 'orderCreateDate'
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'inputCharset',
        'pageUrl',
        'bgUrl',
        'version',
        'language',
        'signType',
        'merchantAcctId',
        'payerName',
        'payerContactType',
        'payerContact',
        'orderId',
        'orderAmount',
        'orderTime',
        'productName',
        'productNum',
        'productId',
        'productDesc',
        'ext1',
        'ext2',
        'payType',
        'bankId',
        'redoFlag',
        'pid'
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'merchantAcctId' => 1, //人民幣帳號
        'version' => 1, //版本,固定值:v2.0
        'language' => 1, //網頁顯示語言種類,1為中文
        'signType' => 1, //簽名類型,4代表DSA或RSA簽名方式
        'payType' => 1, //支付方式,10代表只顯示銀行卡支付方式
        'bankId' => 1, //銀行代碼
        'orderId' => 1, //訂單號
        'orderTime' => 1, //訂單提交時間
        'orderAmount' => 1, //訂單金額
        'dealId' => 1, //快錢交易號
        'bankDealId' => 1, //銀行交易號
        'dealTime' => 1, //快錢交易時間
        'payAmount' => 1, //訂單實際支付金額
        'fee' => 1, //手續費
        'ext1' => 1, //擴展字段1
        'ext2' => 1, //擴展字段1
        'payResult' => 1, //處理結果,10:支付成功
        'errCode' => 1 //錯誤代碼
    ];

    /**
     * 應答機制訊息
     *
     * @var string
     */
    protected $msg = '<result>1</result>';

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        1  => 'ICBC',  //工商銀行
        2  => 'BCOM',  //交通銀行
        3  => 'ABC',   //農業銀行
        4  => 'CCB',   //建設銀行
        5  => 'CMB',   //招商銀行
        6  => 'CMBC',  //民生銀行總行
        7  => 'SDB',   //深圳發展銀行
        8  => 'SPDB',  //上海浦東發展銀行
        9  => 'BOB',   //北京銀行
        10 => 'CIB',   //興業銀行
        11 => 'CITIC', //中信銀行
        12 => 'CEB',   //光大銀行
        13 => 'HXB',   //華夏銀行
        14 => 'GDB',   //廣東發展銀行
        16 => 'POST',  //中國郵政
        17 => 'BOC',   //中國銀行
    ];

    /**
     * 回傳支付時要給支付平台驗證的資料
     *
     * @return array
     */
    public function getVerifyData()
    {
        //如果沒有商家id就丟例外
        if (trim($this->options['merchantId']) === '') {
            throw new PaymentException('No pay parameter specified', 180145);
        }

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
        if (!array_key_exists($this->requestData['bankId'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        //額外的參數設定
        $this->requestData['bankId'] = $this->bankMap[$this->requestData['bankId']];

        $orderTime = new \DateTime($this->requestData['orderTime']);
        $this->requestData['orderTime'] = $orderTime->format("YmdHis");

        //金額以分為單位，必須為整數
        $this->requestData['orderAmount'] = round($this->options['amount'] * 100);

        //設定支付平台需要的加密串
        $this->requestData['signMsg'] = $this->encode();

        return $this->requestData;
    }

    /**
     * 驗證線上支付是否成功
     *
     * @param array $entry
     */
    public function verifyOrderPayment($entry)
    {
        $this->payResultVerify();

        $encodeData = [];

        //組織加密串
        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options) && trim($this->options[$paymentKey]) !== '') {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        $encodeStr = urldecode(http_build_query($encodeData));

        //沒有signMsg就要丟例外
        if (!isset($this->options['signMsg'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        $signMsg = base64_decode($this->options['signMsg']);

        if (openssl_verify($encodeStr, $signMsg, $this->getRsaPublicKey()) !== 1) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['payResult'] !== '10') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['orderId'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['orderAmount'] != round($entry['amount'] * 100)) {
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

        //加密設定
        foreach ($this->encodeParams as $index) {
            if (trim($this->requestData[$index]) !== '') {
                $encodeData[$index] = $this->requestData[$index];
            }
        }

        $encodeStr = urldecode(http_build_query($encodeData));

        $sign = '';
        if (!openssl_sign($encodeStr, $sign, $this->getRsaPrivateKey())) {
            throw new PaymentException('Generate signature failure', 180144);
        }

        return base64_encode($sign);
    }
}

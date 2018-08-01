<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 永利博
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
class Betwinpay extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'agentId'             => '',  //代理id(商號)
        'pickupUrl'           => '',  //頁面跳轉URL
        'receiveUrl'          => '',  //服務器返回URL
        'payerName'           => '',  //支付人姓名
        'payerEmail'          => '',  //支付人郵件
        'payerTelephone'      => '',  //支付人電話
        'payerAddress'        => '',  //支付人地址
        'payerIDCard'         => '',  //支付人身分證
        'orderNo'             => '',  //商戶訂單號
        'orderAmount'         => '',  //商戶訂單金額(以分為單位)
        'orderDatetime'       => '',  //商戶訂單提交時間
        'orderExpireDatetime' => '',  //訂單過期時間
        'productName'         => '',  //商品名稱
        'productPrice'        => '',  //商品價格
        'productNum'          => '',  //商品數量
        'productId'           => '',  //商品代碼
        'productDescription'  => '',  //商品描述
        'ext1'                => '',  //擴展字段1
        'ext2'                => '',  //擴展字段2
        'payType'             => '1', //支付方式
        'issuerId'            => '',  //發卡方代碼(銀行代碼)
        'pan'                 => '',  //付款人支付卡(備用欄位)
        'signMsg'             => ''   //簽名字串符
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'agentId' => 'number',
        'receiveUrl' => 'notify_url',
        'orderNo' => 'orderId',
        'orderAmount' => 'amount',
        'orderDatetime' => 'orderCreateDate',
        'issuerId' => 'paymentVendorId'
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'agentId',
        'pickupUrl',
        'receiveUrl',
        'payerName',
        'payerEmail',
        'payerTelephone',
        'payerAddress',
        'payerIDCard',
        'orderNo',
        'orderAmount',
        'orderDatetime',
        'orderExpireDatetime',
        'productName',
        'productPrice',
        'productNum',
        'productId',
        'productDescription',
        'ext1',
        'ext2',
        'payType',
        'issuerId',
        'pan'
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'agentId' => 1,
        'payType' => 1,
        'issuerId' => 1,
        'paymentOrderId' => 1,
        'orderNo' => 1,
        'orderDatetime' => 1,
        'orderAmount' => 1,
        'payDatetime' => 1,
        'payAmount' => 1,
        'ext1' => 1,
        'ext2' => 1,
        'payResult' => 1,
        'errorCode' => 1,
        'returnDatetime' => 1
    ];

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        '1'  => 'icbc',   //工商銀行
        '2'  => 'comm',   //交通銀行
        '3'  => 'abc',    //農業銀行
        '4'  => 'ccb',    //建設銀行
        '5'  => 'cmb',    //招商銀行
        '6'  => 'cmbc',   //中國民生銀行
        '8'  => 'spdb',   //上海浦東發展銀行
        '10' => 'cib',    //興業銀行
        '11' => 'citic',  //中信銀行
        '12' => 'ceb',    //光大銀行
        '13' => 'hxb',    //華夏銀行
        '15' => 'pingan', //深圳平安銀行
        '16' => 'psbc',   //中國郵政儲蓄
        '17' => 'boc',    //中國銀行
        '19' => 'bos'     //上海銀行
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
        if (!array_key_exists($this->requestData['issuerId'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        //額外的參數設定
        $this->requestData['orderAmount'] = round($this->options['amount'] * 100);
        $date = new \DateTime($this->requestData['orderDatetime']);
        $this->requestData['orderDatetime'] = $date->format("YmdHis");
        $this->requestData['issuerId'] = $this->bankMap[$this->requestData['issuerId']];

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
        // 驗證私鑰
        $this->verifyPrivateKey();

        $this->payResultVerify();

        $encodeData = [];

        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options)) {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        $encodeData['agentKey'] = $this->privateKey;
        $encodeStr = urldecode(http_build_query($encodeData));

        //如果沒有signMsg也要丟例外(其他參數都在前面驗證過了，所以不須要驗證)
        if (!isset($this->options['signMsg'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['signMsg'] != md5($encodeStr)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['payResult'] != '1') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['orderNo'] != $entry['id']) {
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
            $encodeData[$index] = $this->requestData[$index];
        }

        //額外的加密設定
        $encodeData['agentKey'] = $this->privateKey;
        $encodeStr = urldecode(http_build_query($encodeData));

        return md5($encodeStr);
    }
}

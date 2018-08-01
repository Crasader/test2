<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 第四方寶付支付
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
class BaoFoo88 extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'Merid' => '', //商戶號
        'pcmd' => 'Buy', //業務類型,固定值 Buy ,區分大小寫
        'Billno' => '', //訂單號
        'Amount' => '', //交易金額 (保留2位小數)
        'Date' => '', //日期 格式 20141118
        'CurrencyType' => 'RMB', //支付幣種
        'Merchanturl' => '', //支付成功返回
        'Attach' => '', //商戶附加數據包
        'Pamp' => '', //備註信息
        'Bankcode' => '', //銀行編號
        'NeedResponse' => 1, //是否提供Server返回方式, 1 = Server to Server, 0 = 瀏覽器通訊
        'hmac' => '' //加密簽章
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'Merid' => 'number',
        'Billno' => 'orderId',
        'Amount' => 'amount',
        'Merchanturl' => 'notify_url',
        'Bankcode' => 'paymentVendorId',
        'Date' => 'orderCreateDate'
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'Merid',
        'pcmd',
        'Billno',
        'Amount',
        'Date',
        'CurrencyType',
        'Merchanturl',
        'Attach',
        'Pamp',
        'Bankcode',
        'NeedResponse'
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'rMercode' => 1, //商家ID
        'rOrder' => 1, //訂單編號
        'rAmt' => 1, //金額
        'rAttach' => 1, //商品信息
        'rPamp' => 1, //備註信息
        'rSucc' => 1, //支付狀態
        'rDate' => 1, //支付時間
        'rBankorder' => 1, //銀行訂單號
        'rBtype' => 1 //應答方式
    ];

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        '1' => 'ICBC-NET-B2C', //中國工商銀行
        '2' => 'BOCO-NET-B2C', //交通銀行
        '3' => 'ABC-NET-B2C', //中國農業銀行
        '4' => 'CCB-NET-B2C', //中國建設銀行
        '5' => 'CMBCHINA-NET-B2C', //招商銀行
        '6' => 'CMBC-NET-B2C', //中國民生銀行
        '8' => 'SPDB-NET-B2C', //上海浦東發展銀行
        '9' => 'BCCB-NET-B2C', //北京銀行
        '10' => 'CIB-NET-B2C', //興業銀行
        '12' => 'CEB-NET-B2C', //中國光大銀行 //404 需確認
        '14' => 'GDB-NET-B2C', //廣東發展銀行
        '15' => 'PINGANBANK-NET', //平安銀行
        '16' => 'POST-NET-B2C', //中國郵政儲蓄銀行
        '17' => 'BOC-NET-B2C', //中國銀行
        '19' => 'SHB-NET-B2C', //上海銀行
        '217' => 'CBHB-NET-B2C', //渤海銀行
        '220' => 'HZBANK-NET-B2C', //杭州銀行
        '221' => 'CZ-NET-B2C', //浙商銀行
        '222' => 'NBCB-NET-B2C', //寧波銀行
        '223' => 'HKBEA-NET-B2C', //東亞銀行
        '234' => 'BJRCB-NET-B2C' //北京農村商業銀行
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

        //帶入未支援的銀行就噴例外
        if (!array_key_exists($this->requestData['Bankcode'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        //額外的參數設定
        $date = new \DateTime($this->requestData['Date']);
        $this->requestData['Date'] = $date->format('Ymd');
        $this->requestData['Bankcode'] = $this->bankMap[$this->requestData['Bankcode']];
        $this->requestData['Pamp'] = $this->options['merchantId'] . '_' . $this->options['domain'];
        $this->requestData['Amount'] = sprintf('%.2f', $this->requestData['Amount']);

        //設定支付平台需要的加密串
        $this->requestData['hmac'] = $this->encode();

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

        // 加密設定
        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options)) {
                $encodeStr .= '[[' . $this->options[$paymentKey] . ']]';
            }
        }

        //進行加密
        $encodeStr .= '[[' . $this->privateKey . ']]';
        $encodeStr = md5(md5($encodeStr));

        //沒有 hcmack 就要丟例外，其他的參數在組織加密串的時候驗證過了
        if (!isset($this->options['hcmack'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if (urldecode($this->options['hcmack']) != $encodeStr) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['rSucc'] != 'Y') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['rOrder'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['rAmt'] != $entry['amount']) {
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
            $encodeStr .= '[' . $this->requestData[$index] . ']';
        }

        $encodeStr .= '[' . $this->privateKey . ']';

        return md5(md5($encodeStr));
    }
}

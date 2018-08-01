<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 快匯寶支付
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
class DinPay extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'OrderMessage' => '', //訂單信息。參數和值的設定在encodeParams
        'M_ID'         => '', //商家號
        'P_Bank'       => '', //銀行代碼
        'digest'       => '' //MD5加密串
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'M_ID' => 'number',
        'MOrderID' => 'orderId',
        'MOAmount' => 'amount',
        'M_URL' => 'notify_url',
        'MOComment' => 'username',
        'MODate' => 'orderCreateDate'
    ];

    /**
     * 支付時OrderMessage(訂單信息)的參數設定
     *
     * @var array
     */
    protected $encodeParams = [
        'M_ID'        => '', //商家號
        'MOrderID'    => '', //訂單號
        'MOAmount'    => '', //金額
        'MOCurrency'  => '1', //幣別 1:人民幣
        'M_URL'       => '', //通知URL
        'M_Language'  => '1', //語言 1:簡體中文
        'S_Name'      => '', //消費者姓名
        'S_Address'   => '', //消費者地址
        'S_PostCode'  => '', //消費者郵遞區號
        'S_Telephone' => '', //消費者電話
        'S_Email'     => '', //消費者電子郵件
        'R_Name'      => '', //收貨人姓名
        'R_Address'   => '', //收貨人地址
        'R_PostCode'  => '', //收貨人郵遞區號
        'R_Telephone' => '', //收貨人電話
        'R_Email'     => '', //收貨人電子郵件
        'MOComment'   => '', //備註
        'State'       => '0', //支付狀態
        'MODate'      => '' //日期
    ];

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        '1'   => 'ICBC', //工商銀行
        '2'   => 'BCOM', //交通銀行
        '3'   => 'ABC', //農業銀行
        '4'   => 'CCB', //建設銀行
        '5'   => 'CMB', //招商銀行
        '6'   => 'CMBC', //民生銀行
        '7'   => 'SDB', //深圳發展銀行
        '8'   => 'SPDB', //上海浦東發展銀行
        '10'  => 'CIB', //興業銀行
        '11'  => 'ECITIC', //中信銀行
        '12'  => 'CEBB', //光大銀行
        '13'  => 'HXB', //華夏銀行
        '14'  => 'GDB', //廣東發展銀行
        '15'  => 'SPABANK', //平安銀行
        '16'  => 'PSBC', //中國郵政儲蓄
        '17'  => 'BOC', //中國銀行
        '223' => 'BEA', //東亞銀行
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
            // 這邊是設定OrderMessage(訂單訊息)的值
            $this->encodeParams[$paymentKey] = $this->options[$internalKey];
        }

        //額外驗證銀行代碼，這個參數沒有在訂單信息裡，所以另外檢查
        if (trim($this->options['paymentVendorId']) == '') {
            throw new PaymentException('No pay parameter specified', 180145);
        }

        //帶入未支援的銀行就噴例外
        if (!array_key_exists($this->options['paymentVendorId'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        //額外的參數設定(要送去支付平台的參數)
        $this->requestData['M_ID'] = $this->options['number'];
        $this->requestData['P_Bank'] = $this->bankMap[$this->options['paymentVendorId']];

        //設定支付平台需要的加密串
        $data = $this->encode();
        $this->requestData['OrderMessage'] = $data['OrderMessage'];
        $this->requestData['digest'] = $data['digest'];

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

        //沒有OrderMessage就丟例外
        if (!isset($this->options['OrderMessage'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        $encodeStr = strtoupper(md5($this->options['OrderMessage'] . $this->privateKey));

        $replyMessage = $this->hexToStr($this->options['OrderMessage']);
        $orderMessage = explode('|', $replyMessage);

        $replyOrderId = $orderMessage[1]; //返回的訂單號
        $replyAmount = $orderMessage[2]; //返回的金額
        $replyState = $orderMessage[17]; //返回的狀態

        //如果沒有簽名擋也要丟例外(其他參數都包在OrderMessage)
        if (!isset($this->options['Digest'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['Digest'] != $encodeStr) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($replyState != '2') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($replyOrderId != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($replyAmount != $entry['amount']) {
            throw new PaymentException('Order Amount error', 180058);
        }
    }

    /**
     * 支付時的加密
     *
     * @return array
     */
    protected function encode()
    {
        //將要加密的字串轉成16進制
        $encodeStr = $this->strToHex(implode('|', $this->encodeParams));

        return [
            'OrderMessage' => $encodeStr,
            'digest' => strtoupper(md5($encodeStr . $this->privateKey))
        ];
    }

    /**
     * 字串轉換成16進制
     *
     * @param string $string
     * @return string
     */
    private function strToHex($string)
    {
        $hex = '';

        for ($i = 0; $i < strlen($string); $i++) {
            $hex .= dechex(ord($string[$i]));
        }

        return strtoupper($hex);
    }

    /**
     * 16進制轉換成字串
     *
     * @param string $hex
     * @return string
     */
    private function hexToStr($hex)
    {
        $string='';

        for ($i = 0; $i < strlen($hex)-1; $i+=2) {
            $string .= chr(hexdec($hex[$i].$hex[$i+1]));
        }

        return $string;
    }
}

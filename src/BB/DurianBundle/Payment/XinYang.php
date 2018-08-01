<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 信易貸
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
class XinYang extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'merchantid'   => '', //商戶編號
        'orderno'      => '', //訂單號
        'amount'       => '', //訂單金額
        'currencycode' => '156', //幣別
        'transtype'    => '1', //交易種類
        'merchant_url' => '', //伺服器通知url
        'pgupUrl'      => '', //前台通知url
        'username'     => '', //訂貨人姓名，非必填
        'goodinfo'     => '', //商品訊息，非必填
        'useremail'    => '', //訂貨人email，非必填
        'note'         => '', //附加訊息，非必填
        'bankid'       => '', //銀行代碼，非必填
        'mac'          => '' //加密訊息
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'merchantid' => 'number',
        'orderno' => 'orderId',
        'amount' => 'amount',
        'merchant_url' => 'notify_url',
        'bankid' => 'paymentVendorId'
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'merchantid',
        'orderno',
        'amount',
        'merchant_url',
        'transtype'
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'merchantid' => 1,
        'orderno' => 1,
        'amount' => 1,
        'date' => 1,
        'succeed' => 1
    ];

    /**
     * 應答機制訊息
     *
     * @var string
     */
    protected $msg = 'OK';

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        '1'  => 'icbc', //中國工商銀行
        '2'  => 'comm', //交通銀行
        '3'  => 'abc', //中國農業銀行
        '4'  => 'ccb', //中國建設銀行
        '5'  => 'cmb', //招商銀行
        '6'  => 'cmbc', //中國民生銀行
        '8'  => 'spdb', //上海浦東發展銀行
        '10' => 'cib', //興業銀行
        '11' => 'citic', //中信銀行
        '12' => 'ceb', //中國光大銀行
        '14' => 'cgb', //廣東發展銀行
        '15' => 'pingan', //平安銀行
        '16' => 'psbc', //中國郵政
        '17' => 'boc', //中國銀行
        '19' => 'bos' //上海銀行
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

        //驗證返回網址是否不合法
        if (preg_match("/[‘’,“”&<>()]/", $this->requestData['merchant_url'])) {
            throw new PaymentException('Invalid notify_url', 180146);
        }

        //帶入未支援的銀行就噴例外
        if (!array_key_exists($this->requestData['bankid'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        //額外的參數設定
        $this->requestData['bankid'] = $this->bankMap[$this->requestData['bankid']];
        $this->requestData['amount'] = sprintf('%.2f', $this->requestData['amount']);
        $this->requestData['note'] = $this->options['merchantId'] . '_' . $this->options['domain'];

        //設定支付平台需要的加密串
        $this->requestData['mac'] = $this->encode();

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

        $decodeVerifyData = [];

        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options)) {
                $decodeVerifyData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        //額外的參數設定
        $decodeVerifyData['merchant_key'] = $this->privateKey;

        //進行加密
        $encodeStr = urldecode(http_build_query($decodeVerifyData));

        //沒有mac就要丟例外，其他的參數在組織加密串的時候驗證過了
        if (!isset($this->options['mac'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['mac'] != strtoupper(md5($encodeStr))) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['succeed'] != 'Y') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['orderno'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['amount'] != $entry['amount']) {
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
        $encodeData['merchant_key'] = $this->privateKey;
        $encodeStr = urldecode(http_build_query($encodeData));

        return strtoupper(md5($encodeStr));
    }
}

<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * CardToPay
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
class CardToPay extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'ver'      => '01', //接口版本號
        'mrch_no'  => '', //商戶代號
        'ord_no'   => '', //訂單編號
        'ord_date' => '', //訂單日期
        'ord_amt'  => '', //訂單金額
        'mac'      => '' //簽名
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'mrch_no' => 'number',
        'ord_no' => 'orderId',
        'ord_amt' => 'amount',
        'ord_date' => 'orderCreateDate'
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'ver',
        'mrch_no',
        'ord_no',
        'ord_date',
        'ord_amt'
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'ver' => 1,
        'mrch_no' => 1,
        'ord_no' => 1,
        'ord_date' => 1,
        'ord_amt' => 1,
        'ord_seq' => 1,
        'sno' => 1,
        'ord_status' => 1,
        'ord_result' => 1,
        'add_msg' => 1
    ];

    /**
     * 應答機制訊息
     *
     * @var string
     */
    protected $msg = '[success]';

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

        //額外的參數設定
        $date = new \DateTime($this->requestData['ord_date']);
        $this->requestData['ord_date'] = $date->format("Ymd");
        $this->requestData['ord_amt'] = sprintf("%.2f", $this->requestData['ord_amt']);

        foreach ($this->requestData as $key => $value) {
            $this->requestData[$key] = base64_encode($value);
        }

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

        $encodeStr = '';

        //組織加密串
        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options)) {
                //加密串的產生技術文件付上的檔案也是以trim()的處理方式來設定
                $this->options[$paymentKey] = trim(base64_decode($this->options[$paymentKey]));
                $encodeStr .= $paymentKey . base64_encode($this->options[$paymentKey]);
            }
        }

        //進行加密
        $encodeStr .= $this->privateKey;
        $encodeStr = strtoupper(md5($encodeStr));

        //沒有mac就要丟例外，其他的參數在組織加密串的時候驗證過了
        if (!isset($this->options['mac'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['mac'] != $encodeStr) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['ord_status'] != '1' || $this->options['ord_result'] != 'success') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['ord_no'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['ord_amt'] != $entry['amount']) {
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

        //加密設定
        foreach ($this->encodeParams as $index) {
            $encodeStr .= $index . $this->requestData[$index];
        }

        //額外的加密設定
        $encodeStr .= $this->privateKey;

        return strtoupper(md5($encodeStr));
    }
}

<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * V付通
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
class VPay extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'P_UserId'      => '', //商家號
        'P_OrderId'     => '', //訂單號
        'P_CardId'      => '', //卡類充值卡號
        'P_CardPass'    => '', //卡類充值的卡片密碼
        'P_FaceValue'   => '', //金額
        'P_ChannelId'   => '1', //充值類型
        'P_Subject'     => '', //產品名稱
        'P_Price'       => '0', //產品價格，不可為空，預設值0
        'P_Quantity'    => '1', //產品數量，不可為空，預設值1
        'P_Description' => '', //產品描述
        'P_Notic'       => '', //用戶附加訊息
        'P_Result_URL'  => '', //充值狀態回傳網址
        'P_Notify_URL'  => '', //充值後網頁跳轉網址
        'P_PostKey'     => ''  //加密字串
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
        'P_Result_URL' => 'notify_url',
        'P_Description' => 'domain'
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
        'P_ChannelId'
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
        'P_ChannelId' => 1
    ];

    /**
     * 應答機制訊息
     *
     * @var string
     */
    protected $msg = 'errCode=0';

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
            '%s?pay_system=%s',
            $this->options['notify_url'],
            $this->options['merchantId']
        );

        // 從內部給定值到參數
        foreach ($this->requireMap as $paymentKey => $internalKey) {
            $this->requestData[$paymentKey] = $this->options[$internalKey];
        }

        //額外的參數設定
        $this->requestData['P_FaceValue'] = sprintf("%.2f", $this->requestData['P_FaceValue']);

        //設定支付平台需要的加密串
        $this->requestData['P_PostKey'] = $this->encode();

        return $this->requestData;
    }

    /**
     * 驗證支付是否成功
     *
     * @param array $entry
     */
    public function verifyOrderPayment($entry)
    {
        // 驗證私鑰
        $this->verifyPrivateKey();

        $this->payResultVerify();

        $encodeData = [];
        $encodeStr = '';

        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options)) {
                $encodeData[] = $this->options[$paymentKey];
            }
        }

        //進行加密，V付通有兩種加密方式
        $encodeData[] = $this->privateKey;
        $encodeStr = implode('|', $encodeData);
        $encodeStrFirst = md5($encodeStr);

        if (isset($this->options['P_ErrCode']) && isset($this->options['P_PayMoney'])) {
            $encodeData[] = $this->options['P_ErrCode'];
            $encodeData[] = $this->options['P_PayMoney'];
            $encodeStr = implode('|', $encodeData);
        }

        $encodeStrSecond = md5($encodeStr);

        if (!isset($this->options['P_PostKey'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['P_ErrCode'] != '0') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        /**
         * V付通資料回傳有兩種模式，因不確定每次回傳屬於哪一種方式，
         * 所以兩種模式都加入判斷
         */
        if ($this->options['P_PostKey'] != $encodeStrFirst && $this->options['P_PostKey'] != $encodeStrSecond) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['P_OrderId'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['P_FaceValue'] != $entry['amount']) {
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
        $encodeStr = '';

        //加密設定
        foreach ($this->encodeParams as $index) {
            $encodeData[] = $this->requestData[$index];
        }

        //額外的加密設定
        $encodeData[] = $this->privateKey;
        $encodeStr = implode('|', $encodeData);

        return md5($encodeStr);
    }
}

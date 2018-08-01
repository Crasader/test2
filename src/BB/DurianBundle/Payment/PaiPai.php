<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 派派支付
 */
class PaiPai extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'pay_version' => 'vb1.0', // 系統接口版本，固定值
        'pay_memberid' => '', // 商號
        'pay_orderid' => '', // 訂單號
        'pay_applydate' => '', // 訂單提交時間，格式YmdHis
        'pay_bankcode' => '', // 銀行編號
        'pay_notifyurl' => '', // 服务端通知
        'pay_amount' => '', // 金額，單位元
        'pay_md5sign' => '', // 簽名
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'pay_memberid' => 'number',
        'pay_orderid' => 'orderId',
        'pay_applydate' => 'orderCreateDate',
        'pay_bankcode' => 'paymentVendorId',
        'pay_notifyurl' => 'notify_url',
        'pay_amount' => 'amount',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'pay_memberid',
        'pay_bankcode',
        'pay_amount',
        'pay_orderid',
        'pay_notifyurl',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'orderid' => 1,
        'opstate' => 1,
        'ovalue' => 1,
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
        '1' => '967', // 中國工商銀行
        '2' => '981', // 交通銀行
        '3' => '964', // 中國農業銀行
        '4' => '965', // 中國建設銀行
        '5' => '970', // 招商銀行
        '6' => '980', // 中國民生銀行
        '8' => '977', // 上海浦東發展銀行
        '9' => '989', // 北京銀行
        '10' => '972', // 興業銀行
        '11' => '962', // 中信銀行
        '12' => '986', // 中國光大銀行
        '13' => '982', // 華夏銀行
        '14' => '985', // 廣東發展銀行
        '15' => '978', // 平安銀行
        '16' => '971', // 中國郵政
        '17' => '963', // 中國銀行
        '19' => '975', // 上海銀行
        '217' => '988', // 渤海銀行
        '220' => '983', // 杭州銀行
        '221' => '968', // 浙商银行
        '223' => '987', // 東亞銀行
        '226' => '979', // 南京銀行
        '227' => '984', // 廣州市農村信用合作社
        '228' => '976', // 上海市農村商業銀行
        '231' => '973', // 順德農村信用合作社
        '233' => '969', // 浙江稠州商業銀行
        '234' => '990', // 北京農商行
        '1090' => '1004', // 微信支付_二維
        '1092' => '992', // 支付寶_二維
        '1103' => '1593', // QQ_二維
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
        if (!array_key_exists($this->requestData['pay_bankcode'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $date = new \DateTime($this->requestData['pay_applydate']);
        $this->requestData['pay_applydate'] = $date->format('YmdHis');
        $this->requestData['pay_amount'] =  sprintf('%.2f', $this->requestData['pay_amount']);
        $this->requestData['pay_bankcode'] = $this->bankMap[$this->requestData['pay_bankcode']];

        // 設定支付平台需要的加密串
        $this->requestData['pay_md5sign'] = $this->encode();

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

        // 組織加密串
        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options)) {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        $encodeStr = urldecode(http_build_query($encodeData));
        $encodeStr .= $this->privateKey;

        // 如果沒有返回簽名要丟例外
        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['sign'] != md5($encodeStr)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['opstate'] !== '0') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['orderid'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['ovalue'] != $entry['amount']) {
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

        // 加密設定
        foreach ($this->encodeParams as $index) {
            $encodeData[$index] = $this->requestData[$index];
        }

        $encodeStr = urldecode(http_build_query($encodeData));
        $encodeStr .= $this->privateKey;

        return md5($encodeStr);
    }
}

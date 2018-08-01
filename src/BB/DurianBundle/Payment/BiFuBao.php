<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 必付寶
 */
class BiFuBao extends PaymentBase
{
    /**
     * 支付提交參數
     *
     * @var array
     */
    protected $requestData = [
        'MERCHANT_ID' => '', // 商戶號
        'TRAN_CODE' => '', // 訂單號
        'TRAN_AMT' => '', // 訂單金額，單位分
        'REMARK' => '', // 描述
        'NO_URL' => '', // 異步通知地址
        'RET_URL' => '', // 支付完成跳轉地址
        'SUBMIT_TIME' => '', // 時間戳，格式：YmdHis
        'BANK_ID' => '', // 銀行代碼
        'VERSION' => '1', // 版本，固定值:1
        'SIGNED_MSG' => '', // 簽名
    ];

    /**
     * 支付提交參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'MERCHANT_ID' => 'number',
        'TRAN_CODE' => 'orderId',
        'TRAN_AMT' => 'amount',
        'REMARK' => 'orderId',
        'NO_URL' => 'notify_url',
        'RET_URL' => 'notify_url',
        'SUBMIT_TIME' => 'orderCreateDate',
        'BANK_ID' => 'paymentVendorId',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'MERCHANT_ID',
        'TRAN_CODE',
        'TRAN_AMT',
        'REMARK',
        'TYPE',
        'NO_URL',
        'RET_URL',
        'SUBMIT_TIME',
        'BANK_ID',
        'VERSION',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'TYPE' => 1,
        'MERCHANT_ID' => 1,
        'TRAN_CODE' => 1,
        'SYS_CODE' => 1,
        'TRAN_AMT' => 1,
        'REMARK' => 1,
        'STATUS' => 1,
        'PAY_TIME' => 1,
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
        '1' => '1001', // 工商銀行
        '2' => '1005', // 交通銀行
        '3' => '1002', // 農業銀行
        '4' => '1004', // 建設銀行
        '5' => '1012', // 招商銀行
        '6' => '1010', // 民生銀行總行
        '8' => '1014', // 上海浦東發展銀行
        '9' => '1016', // 北京銀行
        '10' => '1013', // 興業銀行
        '11' => '1007', // 中信銀行
        '12' => '1008', // 光大銀行
        '13' => '1009', // 華夏銀行
        '14' => '1017', // 廣東發展銀行
        '15' => '1011', // 平安銀行
        '16' => '1006', // 中國郵政
        '17' => '1003', // 中國銀行
        '19' => '1025', // 上海銀行
        '234' => '1103', // 北京農商行
        '1098' => '12', // 支付寶_手機支付
        '1103' => '3', // QQ_二維
        '1108' => '10', // 京東_手機支付
        '1111' => '9', // 銀聯_二維
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
        if (!array_key_exists($this->requestData['BANK_ID'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $this->requestData['BANK_ID'] = $this->bankMap[$this->requestData['BANK_ID']];
        $this->requestData['TRAN_AMT'] = round($this->requestData['TRAN_AMT'] * 100);
        $createAt = new \Datetime($this->requestData['SUBMIT_TIME']);
        $this->requestData['SUBMIT_TIME'] = $createAt->format('YmdHis');

        // 調整網銀提交網址
        $postUrl = $this->options['postUrl'] . 'ebank-pay.htm';

        // 手機支付調整提交參數與提交網址
        if (in_array($this->options['paymentVendorId'], [1098, 1108])) {
            $postUrl = $this->options['postUrl'] . 'h5-pay.htm';
            $this->requestData['TYPE'] = $this->requestData['BANK_ID'];
            unset($this->requestData['BANK_ID']);
        }

        // 二維支付調整提交參數
        if (in_array($this->options['paymentVendorId'], [1103, 1111])) {
            $this->requestData['TYPE'] = $this->requestData['BANK_ID'];
            unset($this->requestData['BANK_ID']);
            unset($this->requestData['RET_URL']);
        }

        $this->requestData['SIGNED_MSG'] = $this->encode();

        // 二維支付
        if (in_array($this->options['paymentVendorId'], [1103, 1111])) {
            if (trim($this->options['verify_url']) == '') {
                throw new PaymentException('No verify_url specified', 180140);
            }

            $curlParam = [
                'method' => 'POST',
                'uri' => '/bifubao-gateway/back-pay/qr-pay',
                'ip' => $this->options['verify_ip'],
                'host' => $this->options['verify_url'],
                'param' => http_build_query($this->requestData),
                'header' => [],
            ];

            $result = $this->curlRequest($curlParam);
            $parseData = json_decode($result, true);

            if (!isset($parseData['RET_CODE'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            if ($parseData['RET_CODE'] != 'SUCCESS' && isset($parseData['RET_MSG'])) {
                throw new PaymentConnectionException($parseData['RET_MSG'], 180130, $this->getEntryId());
            }

            if ($parseData['RET_CODE'] != 'SUCCESS') {
                throw new PaymentConnectionException('Pay error', 180130, $this->getEntryId());
            }

            if (!isset($parseData['QR_CODE'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            $this->setQrcode($parseData['QR_CODE']);

            return [];
        }

        return [
            'post_url' => $postUrl,
            'params' => $this->requestData,
        ];
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

        ksort($encodeData);

        $encodeStr = urldecode(http_build_query($encodeData));

        $encodeStr .= $this->privateKey;

        // 沒有返回sign就要丟例外
        if (!isset($this->options['SIGNED_MSG'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['SIGNED_MSG'] != md5($encodeStr)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['STATUS'] !== '1') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['TRAN_CODE'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['TRAN_AMT'] != round($entry['amount'] * 100)) {
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

        foreach ($this->encodeParams as $key) {
            if (isset($this->requestData[$key])) {
                $encodeData[$key] = $this->requestData[$key];
            }
        }

        // 針對$encodeData按字母做升序排列
        ksort($encodeData);

        $encodeStr = urldecode(http_build_query($encodeData));

        $encodeStr .= $this->privateKey;

        return md5($encodeStr);
    }
}

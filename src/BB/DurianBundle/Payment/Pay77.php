<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 柒柒
 */
class Pay77 extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'UId' => '', // 商號
        'Amount' => '', // 金額
        'Sh_OrderNo' => '', // 訂單號
        'Type' => '',  // 交易類型
        'Msg' => '', // 附加信息，放入單號，返回時驗證單號用
        'Ip' => '', // ip
        'sign' => '', // MD5簽名
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'UId' => 'number',
        'Amount' => 'amount',
        'Sh_OrderNo' => 'orderId',
        'Type' => 'paymentVendorId',
        'Msg' => 'orderId',
        'Ip' => 'ip',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'UId',
        'Amount',
        'Sh_OrderNo',
        'Type',
        'Msg',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'OrderNo' => 1,
        'OrderAmount' => 1,
        'TimeEnd' => 1,
        'Msg' => 1,
    ];

    /**
     * 應答機制訊息
     *
     * @var string
     */
    protected $msg = '0000';

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        '278'  => '15', // 銀聯在線
        '1088' => '15', // 銀聯在線_手機支付
        '1090' => '1', // 微信_二維
        '1092' => '2', // 支付寶_二維
        '1098' => '13', // 支付寶_手機支付
        '1103' => '3', // QQ_二維
        '1104' => '3', // QQ_手機支付
        '1107' => '4', // 京東錢包_二維
        '1111' => '7', // 銀聯錢包_二維
    ];

    /**
     * 回傳支付時要給支付平台驗證的資料
     *
     * @return array
     */
    public function getVerifyData()
    {
        $this->verifyPrivateKey();

        $this->payVerify();

        // 從內部給定值到參數
        foreach ($this->requireMap as $paymentKey => $internalKey) {
            $this->requestData[$paymentKey] = $this->options[$internalKey];
        }

        // 檢查銀行代碼是否支援
        if (!array_key_exists($this->requestData['Type'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $this->requestData['Amount'] = sprintf('%.2f', $this->requestData['Amount']);
        $this->requestData['Type'] = $this->bankMap[$this->requestData['Type']];

        // 二維、QQ手機需要對外
        if (in_array($this->options['paymentVendorId'], ['1090', '1092', '1103', '1104', '1107', '1111'])) {
            if (trim($this->options['verify_url']) == '') {
                throw new PaymentException('No verify_url specified', 180140);
            }
            // 設定支付平台需要的加密串
            $this->requestData['sign'] = $this->encode();

            $curlParam = [
                'method' => 'POST',
                'uri' => '/Pay/df_pay.aspx',
                'ip' => $this->options['verify_ip'],
                'host' => $this->options['verify_url'],
                'param' => http_build_query($this->requestData),
                'header' => [],
            ];

            $result = $this->curlRequest($curlParam);
            $parseData = json_decode($result, true);

            if (!isset($parseData['Status'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            // Status 狀態不為1的時候，是否有回傳錯誤訊息，沒有則噴錯
            if ($parseData['Status'] != 1 && !isset($parseData['Error'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            if ($parseData['Status'] != 1) {
                throw new PaymentConnectionException($parseData['Error'], 180130, $this->getEntryId());
            }

            if (!isset($parseData['Qrcode'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            // QQ手機支付
            if ($this->options['paymentVendorId'] == '1104') {
                $urlData = $this->parseUrl($parseData['Qrcode']);
                $this->payMethod = 'GET';

                return [
                    'post_url' => $urlData['url'],
                    'params' => $urlData['params'],
                ];
            }

            $this->setQrcode($parseData['Qrcode']);

            return [];
        }

        // 走H5通道不對外驗簽需加上Ip
        $this->encodeParams[] = 'Ip';
        // 設定支付平台需要的加密串
        $this->requestData['sign'] = $this->encode();

        return $this->requestData;
    }

    /**
     * 驗證線上支付是否成功
     *
     * @param array $entry
     */
    public function verifyOrderPayment($entry)
    {
        $this->verifyPrivateKey();

        $this->payResultVerify();

        // 組織加密串
        $encodeData = [];
        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options)) {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        ksort($encodeData);

        $encodeData['key'] = $this->privateKey;

        // 依key1=value1&key2=value2&...&keyN=valueN之後做md5
        $encodeStr = urldecode(http_build_query($encodeData));

        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['sign'] != strtoupper(md5($encodeStr))) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['Msg'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['OrderAmount'] != $entry['amount']) {
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

        foreach ($this->encodeParams as $index) {
            if (isset($this->requestData[$index])) {
                $encodeData[$index] = $this->requestData[$index];
            }
        }

        ksort($encodeData);

        $encodeData['key'] = $this->privateKey;

        // 依key1=value1&key2=value2&...&keyN=valueN之後做md5
        $encodeStr = urldecode(http_build_query($encodeData));

        return strtoupper(md5($encodeStr));
    }
}

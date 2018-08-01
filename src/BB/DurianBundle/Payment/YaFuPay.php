<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 雅付
 */
class YaFuPay extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'version' => '3.0', // 版本號，固定值
        'consumerNo' => '', // 商戶編號
        'merOrderNo' => '', // 訂單號
        'transAmt' => '', // 訂單金額，單位:元，保留到小數第二位
        'backUrl' => '', // 異步通知url
        'frontUrl' => '', // 同步通知url
        'bankCode' => '', // 銀行編碼，網銀必填
        'payType' => '0101', // 支付類型，網銀:0101
        'goodsName' => '', // 商品名稱，設定username方便業主比對
        'merRemark' => '', // 訂單備註，設定username方便業主比對
        'buyIp' => '', // 購買人IP，非必填
        'buyPhome' => '', // 購買人電話，非必填
        'shopName' => '', // 店鋪名稱，非必填
        'sign' => '', // 簽名
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'consumerNo' => 'number',
        'merOrderNo' => 'orderId',
        'transAmt' => 'amount',
        'backUrl' => 'notify_url',
        'frontUrl' => 'notify_url',
        'bankCode' => 'paymentVendorId',
        'goodsName' => 'username',
        'merRemark' => 'username',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'version',
        'consumerNo',
        'merOrderNo',
        'transAmt',
        'backUrl',
        'frontUrl',
        'bankCode',
        'payType',
        'goodsName',
        'merRemark',
        'buyIp',
        'buyPhome',
        'shopName',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'version' => 1,
        'consumerNo' => 1,
        'merOrderNo' => 1,
        'orderNo' => 1,
        'transAmt' => 1,
        'orderStatus' => 1,
        'payType' => 1,
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
        '1' => 'ICBC', // 中國工商銀行
        '2' => 'BOCM', // 交通銀行
        '3' => 'ABC', // 中國農業銀行
        '4' => 'CCB', // 中國建設銀行
        '5' => 'CMB', // 招商銀行
        '6' => 'CMBC', // 中國民生銀行
        '8' => 'SPDB', // 上海浦東發展銀行
        '9' => 'BOBJ', // 北京銀行
        '10' => 'CIB', // 興業銀行
        '11' => 'CITIC', // 中信銀行
        '12' => 'CEB', // 中國光大銀行
        '13' => 'HXBC', // 華夏銀行
        '14' => 'CGB', // 廣東發展銀行
        '15' => 'PAB', // 平安銀行
        '16' => 'PSBC', // 中國郵政
        '17' => 'BOC', // 中國銀行
        '19' => 'BOS', // 上海銀行
        '217' => 'CBHB', // 渤海銀行
        '222' => 'NBCB', // 寧波銀行
        '223' => 'BEA', // 東亞銀行
        '226' => 'NJCB', // 南京銀行
        '228' => 'SRCB', // 上海農村商業銀行
        '308' => 'WSB', // 徽商銀行
        '312' => 'BOCD', // 成都銀行
        '1090' => '0202', // 微信支付_二維
        '1092' => '0302', // 支付寶_二維
        '1097' => '0901', // 微信_手機支付
        '1098' => '0303', // 支付寶_手機支付
        '1103' => '0502', // QQ_二維
        '1107' => '0802', // 京東錢包_二維
        '1108' => '0803', // 京東錢包_手機支付
        '1111' => '0701', // 銀聯錢包_二維
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
        if (!array_key_exists($this->requestData['bankCode'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $this->requestData['bankCode'] = $this->bankMap[$this->requestData['bankCode']];
        $this->requestData['transAmt'] = sprintf('%.2f', $this->requestData['transAmt']);

        // 二維支付
        if (in_array($this->options['paymentVendorId'], ['1090', '1092', '1103', '1107'])) {
            $this->requestData['payType'] = $this->requestData['bankCode'];
            unset($this->requestData['bankCode']);

            if (trim($this->options['verify_url']) == '') {
                throw new PaymentException('No verify_url specified', 180140);
            }

            // 設定支付平台需要的加密串
            $this->requestData['sign'] = $this->encode();

            $curlParam = [
                'method' => 'POST',
                'uri' => '/yfpay/cs/pay.ac',
                'ip' => $this->options['verify_ip'],
                'host' => $this->options['verify_url'],
                'param' => http_build_query($this->requestData),
                'header' => [],
            ];

            $result = $this->curlRequest($curlParam);
            $parseData = json_decode($result, true);

            if (!isset($parseData['code']) || !isset($parseData['msg'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            if ($parseData['code'] !== '000000') {
                throw new PaymentConnectionException($parseData['msg'], 180130, $this->getEntryId());
            }

            if (!isset($parseData['busContent'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            $this->setQrcode($parseData['busContent']);

            return [];
        }

        // 手機支付調整提交參數，銀聯錢包為直連收銀台掃碼
        if (in_array($this->options['paymentVendorId'], ['1097', '1098', '1108', '1111'])) {
            $this->requestData['payType'] = $this->requestData['bankCode'];
            unset($this->requestData['bankCode']);
        }

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

        ksort($encodeData);

        $encodeData['key'] = $this->privateKey;

        $encodeStr = urldecode(http_build_query($encodeData));

        // 沒有sign就要丟例外
        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if (strcasecmp($this->options['sign'], md5($encodeStr)) != 0) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['orderStatus'] == '0') {
            throw new PaymentConnectionException('Order Processing', 180059, $this->getEntryId());
        }

        if ($this->options['orderStatus'] !== '1') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['merOrderNo'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['transAmt'] != $entry['amount']) {
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

        // 組織加密簽名
        foreach ($this->encodeParams as $index) {
            if (isset($this->requestData[$index]) && $this->requestData[$index] != '') {
                $encodeData[$index] = $this->requestData[$index];
            }
        }

        ksort($encodeData);

        $encodeData['key'] = $this->privateKey;

        $encodeStr = urldecode(http_build_query($encodeData));

        return md5($encodeStr);
    }
}

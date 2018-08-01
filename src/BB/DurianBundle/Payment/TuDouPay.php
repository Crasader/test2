<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 土豆支付
 */
class TuDouPay extends PaymentBase
{
    /**
     * 支付提交參數
     *
     * @var array
     */
    protected $requestData = [
        'merchantCode' => '', // 商戶號
        'openType' => '1', // 打開類型，PC端:1
        'notifyUrl' => '', // 異步通知地址
        'interfaceVersion' => '1.0', // 接口版本，固定值:1.0
        'clientIp' => '', // 客戶端IP
        'sign' => '', // 簽名
        'orderId' => '', // 訂單號
        'amount' => '', // 訂單總金額，單位元，精確到小數點後兩位
        'productName' => '', // 商品名稱
        'productDesc' => '', // 商品描述
        'productExt' => '', // 商品拓展訊息
        'returnUrl' => '', // 支付完成跳轉地址
        'userType' => '1', // 用戶類型，1:個人
        'cardType' => '1', // 銀行卡類型，1:借記卡
        'bankCode' => '', // 銀行
    ];

    /**
     * 支付提交參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'merchantCode' => 'number',
        'notifyUrl' => 'notify_url',
        'clientIp' => 'ip',
        'orderId' => 'orderId',
        'amount' => 'amount',
        'productName' => 'orderId',
        'productDesc' => 'orderId',
        'productExt' => 'orderId',
        'returnUrl' => 'notify_url',
        'bankCode' => 'paymentVendorId',

    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'merchantCode',
        'openType',
        'serviceType',
        'notifyUrl',
        'interfaceVersion',
        'clientIp',
        'orderId',
        'amount',
        'productName',
        'productDesc',
        'productExt',
        'returnUrl',
        'userType',
        'cardType',
        'bankCode',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'merchantCode' => 1,
        'interfaceVersion' => 1,
        'orderId' => 1,
        'sysOrderId' => 1,
        'status' => 1,
        'amount' => 1,
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
        1 => 'icbc_net_b2c', // 工商銀行
        2 => 'bocom_net_b2c', // 交通銀行
        3 => 'abc_net_b2c', // 農業銀行
        4 => 'ccb_net_b2c', // 建設銀行
        5 => 'cmb_net_b2c', // 招商銀行
        6 => 'cmbc_net_b2c', // 民生銀行總行
        8 => 'spdb_net_b2c', // 上海浦東發展銀行
        9 => 'bccb_net_b2c', // 北京銀行
        10 => 'cib_net_b2c', // 興業銀行
        11 => 'citic_net_b2c', // 中信銀行
        12 => 'ceb_net_b2c', // 光大銀行
        14 => 'cgb_net_b2c', // 廣東發展銀行
        15 => 'pingan_net_b2c', // 平安銀行
        16 => 'post_net_b2c', // 中國郵政
        17 => 'boc_net_b2c', // 中國銀行
        19 => 'shb_net_b2c', // 上海銀行
        220 => 'hzb_net_b2c', // 杭州銀行
        222 => 'nbcb_net_b2c', // 寧波銀行
        223 => 'bea_net_b2c', // 東亞銀行
        226 => 'njcb_net_b2c', // 南京銀行
        228 => 'srcb_net_b2c', // 上海農商銀行
        1092 => 'alipay_pay', // 支付寶_二維
        1098 => 'ali_h5', // 支付寶_手機支付
        1103 => 'qqmobile_pay', // QQ_二維
        1104 => 'qq_h5', // QQ_手機支付
        1107 => 'jdpay_pay', // 京東_二維
        1111 => 'union_pay', // 銀聯_二維
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

        $this->requestData['amount'] = sprintf('%.2f', $this->requestData['amount']);
        $this->requestData['bankCode'] = $this->bankMap[$this->requestData['bankCode']];

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $uri = '/v1/api/ebank/pay';

        // 手機支付與二維調整提交參數與網址
        if (in_array($this->options['paymentVendorId'], [1092, 1098, 1103, 1104, 1107, 1111])) {
            $uri = '/v1/api/scancode/pay';

            // 手機支付調整提交網址
            if (in_array($this->options['paymentVendorId'], [1098, 1104])) {
                $uri = '/v1/api/h5/pay';
            }
            $this->requestData['serviceType'] = $this->requestData['bankCode'];
            unset($this->requestData['openType']);
            unset($this->requestData['returnUrl']);
            unset($this->requestData['userType']);
            unset($this->requestData['cardType']);
            unset($this->requestData['bankCode']);
        }

        $this->requestData['sign'] = $this->encode();

        $curlParam = [
            'method' => 'POST',
            'uri' => $uri,
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => json_encode($this->requestData),
            'header' => ['Content-Type' => 'application/json;charset=utf-8'],
        ];

        $result = $this->curlRequest($curlParam);

        // 二維、手機支付
        if (in_array($this->options['paymentVendorId'], [1092, 1098, 1103, 1104, 1107, 1111])) {
            $parseData = json_decode($result, true);

            if (!isset($parseData['status'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            if ($parseData['status'] != 'SUCCESS') {
                throw new PaymentConnectionException('Pay error', 180130, $this->getEntryId());
            }

            // 手機支付
            if (in_array($this->options['paymentVendorId'], [1098, 1104])) {
                if (!isset($parseData['payUrl'])) {
                    throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
                }
                $urlData = $this->parseUrl($parseData['payUrl']);

                // Form使用GET才能正常跳轉
                $this->payMethod = 'GET';

                return [
                    'post_url' => $urlData['url'],
                    'params' => $urlData['params'],
                ];
            }

            if (!isset($parseData['qrCode'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            $this->setQrcode($parseData['qrCode']);

            return [];
        }

        $getUrl = [];
        preg_match("/action='([^']+)'/", $result, $getUrl);

        if (!isset($getUrl[1])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        $out = [];
        if (!preg_match_all("/input.*name='([^']+)'.*value='([^']*)'/U", $result, $out)) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        return [
            'post_url' => $getUrl[1],
            'params' => array_combine($out[1], $out[2]),
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

        $encodeData['key'] = $this->privateKey;

        $encodeStr = urldecode(http_build_query($encodeData));

        // 沒有返回sign就要丟例外
        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if (strtoupper($this->options['sign']) != strtoupper(md5($encodeStr))) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['status'] !== 'SUCCESS') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['orderId'] != $entry['id']) {
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

        foreach ($this->encodeParams as $key) {
            if (isset($this->requestData[$key])) {
                $encodeData[$key] = $this->requestData[$key];
            }
        }

        // 針對$encodeData按字母做升序排列
        ksort($encodeData);

        $encodeData['key'] = $this->privateKey;

        $encodeStr = urldecode(http_build_query($encodeData));

        return strtoupper(md5($encodeStr));
    }
}

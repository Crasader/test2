<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 686支付
 */
class Pay686 extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'notifyUrl' => '', // 異步通知地址
        'sign' => '', // 簽名
        'outOrderNo' => '', // 訂單號
        'goodsClauses' => '', // 商品名稱，不可空
        'tradeAmount' => '', // 支付金額，單位元，精確到小數後兩位
        'code' => '', // 商戶號
        'payCode' => '', // 支付類型
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'notifyUrl' => 'notify_url',
        'outOrderNo' => 'orderId',
        'goodsClauses' => 'orderId',
        'tradeAmount' => 'amount',
        'code' => 'number',
        'payCode' => 'paymentVendorId',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'notifyUrl',
        'outOrderNo',
        'goodsClauses',
        'tradeAmount',
        'code',
        'payCode',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'outOrderNo' => 1,
        'goodsClauses' => 1,
        'tradeAmount' => 1,
        'shopCode' => 1,
        'code' => 1,
        'nonStr' => 1,
        'msg' => 1,
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
        '1092' => 'alipay', // 支付寶_二維
        '1098' => 'alipay', // 支付寶_手機支付
        '1103' => '8', // QQ_二維
        '1104' => '8', // QQ_手機支付
        '1107' => '16', // 京東_二維
        '1108' => '16', // 京東_手機支付
        '1111' => '32', // 銀聯_二維
        '1115' => '2', // 微信_條碼
        '1118' => '2', // 微信條碼_手機支付
    ];

    /**
     * 不同支付方式對應的uri
     *
     * @var array
     */
    private $uriMap = [
        '1092' => '/index.php/686cz/trade/pay', // 支付寶_二維
        '1098' => '/index.php/686cz/trade/pay', // 支付寶_手機支付
        '1103' => '/index.php/686cz/trade/qqpay', // QQ_二維
        '1104' => '/index.php/686cz/trade/qqpay', // QQ_手機支付
        '1107' => '/index.php/686cz/trade/jdpay', // 京東_二維
        '1108' => '/index.php/686cz/trade/jdpay', // 京東_手機支付
        '1111' => '/index.php/686cz/trade/ylpay', // 銀聯_二維
        '1115' => '/index.php/686cz/trade/wxpay', // 微信_條碼
        '1118' => '/index.php/686cz/trade/wxpay', // 微信條碼_手機支付
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
        if (!array_key_exists($this->requestData['payCode'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        $this->requestData['payCode'] = $this->bankMap[$this->requestData['payCode']];
        $this->requestData['tradeAmount'] = sprintf('%.2f', $this->requestData['tradeAmount']);

        $this->requestData['sign'] = $this->encode();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $uri = $this->uriMap[$this->options['paymentVendorId']];

        $curlParam = [
            'method' => 'POST',
            'uri' => $uri,
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => http_build_query($this->requestData),
            'header' => [],
        ];

        $result = $this->curlRequest($curlParam);

        // 二維支付、手機支付
        if (in_array($this->options['paymentVendorId'], [1092, 1098, 1103, 1104, 1107, 1108, 1111])) {
            $parseData = json_decode($result, true);

            if (!isset($parseData['payState'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            if ($parseData['payState'] != 'success') {
                throw new PaymentConnectionException('Pay error', 180130, $this->getEntryId());
            }

            if (!isset($parseData['url'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            // 二維支付
            if (in_array($this->options['paymentVendorId'], [1092, 1103, 1107, 1111])) {
                $this->setQrcode($parseData['url']);

                return [];
            }

            $urlData = $this->parseUrl($parseData['url']);
        }

        // 微信條碼
        if (in_array($this->options['paymentVendorId'], [1115, 1118])) {
            // 取得跳轉網址
            $urlData = $this->parseUrl($this->getPostUrlFromResult($result));
        }

        // Form使用GET才能正常跳轉
        $this->payMethod = 'GET';

        return [
            'post_url' => $urlData['url'],
            'params' => $urlData['params'],
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
            if (array_key_exists($paymentKey, $this->options) && trim($this->options[$paymentKey]) != '') {
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

        if ($this->options['sign'] != md5($encodeStr)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['code'] !== '0' || $this->options['msg'] !== 'SUCCESS') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['outOrderNo'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['tradeAmount'] != $entry['amount']) {
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
            $encodeData[$key] = $this->requestData[$key];
        }

        // 針對$encodeData按字母做升序排列
        ksort($encodeData);

        $encodeData['key'] = $this->privateKey;

        $encodeStr = urldecode(http_build_query($encodeData));

        return md5($encodeStr);
    }

    /**
     * 從對外回來result字串擷取提交網址
     *
     * @param string $result
     * @return string
     */
    private function getPostUrlFromResult($result)
    {
        $fetchedUrl = [];
        preg_match("/href=\"([^\"]+)/", $result, $fetchedUrl);

        if (!isset($fetchedUrl[1])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        return $fetchedUrl[1];
    }
}

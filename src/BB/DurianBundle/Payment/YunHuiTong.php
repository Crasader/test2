<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;
use Symfony\Component\DomCrawler\Crawler;

/**
 *　雲匯通
 */
class YunHuiTong extends PaymentBase
{
    /**
     * 支付提交參數
     *
     * @var array
     */
    protected $requestData = [
        'appKey' => '', // 商號
        'requestNo' => '', // 訂單號
        'payType' => '', // 支付類型
        'amount' => '', // 金額，單位:元
        'channelCode' => 'GAMEPAY', // 通道編碼，固定值
        'productName' => '', // 產品名稱，必填
        'productDesc' => '', // 產品描述，必填
        'requestIp' => '', // 客戶端請求ip
        'siteName' => '', // 網站名稱，銀聯手機參數
        'siteAddress' => '', // 網站地址，銀聯手機參數
        'payTool' => '', // 支付工具，二維參數
        'serverCallbackUrl' => '', // 回調地址
        'data' => '', // 加密後的資料
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'appKey' => 'number',
        'requestNo' => 'orderId',
        'payType' => 'paymentVendorId',
        'amount' => 'amount',
        'productName' => 'orderId',
        'productDesc' => 'orderId',
        'requestIp' => 'ip',
        'siteName' => 'orderId',
        'siteAddress' => 'notify_url',
        'serverCallbackUrl' => 'notify_url',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'requestNo',
        'payType',
        'amount',
        'channelCode',
        'productName',
        'productDesc',
        'requestIp',
        'siteName',
        'siteAddress',
        'payTool',
        'serverCallbackUrl',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'requestNo' => 1,
        'code' => 1,
        'amount' => 1,
        'bizCode' => 1,
        'bizMsg' => 1,
        'status' => 1,
        'serverRequestNo' => 0,
        'merchantNo' => 0,
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
        '278' => 'BANKPAY', // 銀聯在線(快捷)
        '1088' => 'BANKPAY', // 銀聯在線_手機支付(快捷)
        '1092' => 'ALPAY', // 支付寶_二維
        '1098' => 'ALPAY', // 支付寶_手機支付
        '1111' => 'BANKQR', // 銀聯_二維
    ];

    /**
     * 支付平台的支付工具參數
     *
     * @var array
     */
    private $payToolMap = [
        '1092' => 'q1', // 支付寶_二維
        '1111' => 'q3', // 銀聯_二維
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

        // 因返回的參數均加密，故需串訂單號
        $this->options['notify_url'] = sprintf(
            '%s?order_number=%s',
            $this->options['notify_url'],
            $this->options['orderId']
        );

        // 從內部設值到支付參數
        foreach ($this->requireMap as $paymentKey => $internalKey) {
            $this->requestData[$paymentKey] = $this->options[$internalKey];
        }

        // 帶入未支援的銀行就噴例外
        if (!array_key_exists($this->requestData['payType'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 二維
        if (in_array($this->requestData['payType'], [1092, 1111])) {
            $this->requestData['payTool'] = $this->payToolMap[$this->requestData['payType']];
        }

        // 銀聯在線、銀聯在線手機支付(快捷)移除不需傳遞的參數
        if (!in_array($this->requestData['payType'], [278, 1088])) {
            unset($this->requestData['siteName']);
            unset($this->requestData['siteAddress']);
        }

        // 額外的參數設定
        $this->requestData['payType'] = $this->bankMap[$this->requestData['payType']];

        // 加解密只使用密鑰前16位
        $this->privateKey = substr($this->privateKey, 0, 16);

        // 設定加密簽名
        $this->requestData['data'] = strtoupper($this->encode());

        // 移除已加密的參數
        foreach ($this->encodeParams as $encodeKey) {
            if (array_key_exists($encodeKey, $this->requestData)) {
                unset($this->requestData[$encodeKey]);
            }
        }

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'method' => 'POST',
            'uri' => '/rest/v1.0/paybar/pay',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => urldecode(http_build_query($this->requestData)),
            'header' => ['Port' => '8899'],
        ];

        $result = $this->curlRequest($curlParam);

        $parseData = json_decode($this->aesDecrypt($result), true);

        if (!isset($parseData['bizCode'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        if ($parseData['bizCode'] !== '1') {
            if(isset($parseData['bizMsg'])) {
                throw new PaymentConnectionException($parseData['bizMsg'], 180130, $this->getEntryId());
            }

            throw new PaymentConnectionException('Pay error', 180130, $this->getEntryId());
        }

        if (!isset($parseData['payurl'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        // 支付寶二維、銀聯二維
        if (in_array($this->options['paymentVendorId'], [1092, 1111])) {
            $this->setQrcode($parseData['payurl']);

            return [];
        }

        // 支付寶手機支付
        if (in_array($this->options['paymentVendorId'], [1098])) {
            $parseUrl = $this->parseUrl($parseData['payurl']);

            $this->payMethod = 'GET';

            return [
                'post_url' => $parseUrl['url'],
                'params' => $parseUrl['params'],
            ];
        }

        $html = urldecode($parseData['payurl']);

        $crawler = new Crawler();
        $crawler->addHtmlContent($html);

        $formNode = $crawler->filterXPath('//form[1]');

        if (count($formNode) == 0) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        $postUrl = trim($formNode->attr('action'));

        if ($postUrl == '') {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        // 取出所有 hidden 類型 input 元素的 name、value 屬性值
        $inputDatas = $formNode->filterXPath('//input[@type="hidden"]')->extract(['name', 'value']);
        $params = [];

        foreach ($inputDatas as $inputData) {
            $params[$inputData[0]] = $inputData[1];
        }

        return [
            'post_url' => $postUrl,
            'params' => $params,
        ];
    }

    /**
     * 驗證線上支付是否成功
     *
     * @param array $entry
     */
    public function verifyOrderPayment($entry)
    {
        $this->verifyPrivateKey();

        if (!isset($this->options['data'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        // 返回參數需AES解密
        $verifyData = $this->aesDecrypt($this->options['data']);
        $this->options = json_decode($verifyData, true);

        // 返回參數驗證
        $this->payResultVerify();

        if ($this->options['status'] != 'SUCCESS') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['requestNo'] != $entry['id']) {
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

        foreach ($this->encodeParams as $index) {
            if (array_key_exists($index, $this->requestData)) {
                $encodeData[$index] = $this->requestData[$index];
            }
        }

        $encodeStr = json_encode($encodeData);
        $encodeStr = openssl_encrypt($encodeStr, 'aes-128-ecb', $this->privateKey, OPENSSL_RAW_DATA);

        return bin2hex($encodeStr);
    }

    /**
     * AES解密
     *
     * @param string $data 待解密資料
     * @return string
     */
    private function aesDecrypt($data)
    {
        return openssl_decrypt(hex2bin($data), 'aes-128-ecb', $this->privateKey, OPENSSL_RAW_DATA);
    }
}

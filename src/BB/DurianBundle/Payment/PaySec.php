<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * PaySec
 */
class PaySec extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'version' => '1.0', // 版本號，固定值
        'v_currency' => 'CNY', // 幣別，支援人民幣和泰銖
        'v_amount' => '', // 金額，精確到小數第二位
        'CID' => '', // 商號
        'v_CartID' => '', // 訂單號
        'v_callbackurl' => '', // 後台通知地址
        'v_bank_code' => '', // 銀行編碼
        'signature' => '', // 簽名
    ];

    /**
     * 二維支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $scanRequestData = [
        'version' => '1.0', // 版本號，固定值
        'currency' => 'CNY', // 幣別
        'orderAmount' => '', // 金額，精確到小數第二位
        'merchantCode' => '', // 商號
        'cartId' => '', // 訂單號
        'orderTime' => '', // 訂單時間，Y-m-d H:i:s
        'productName' => '', // 產品名字，帶入username
        'notifyURL' => '', // 後台通知地址
        'returnURL' => '', // 頁面跳轉通知地址
        'signature' => '', // 簽名
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'v_amount' => 'amount',
        'CID' => 'number',
        'v_CartID' => 'orderId',
        'v_callbackurl' => 'notify_url',
        'v_bank_code' => 'paymentVendorId',
    ];

    /**
     * 支付時支付平台二维參數與內部參數的對應
     *
     * @var array
     */
    protected $scanRequireMap = [
        'orderAmount' => 'amount',
        'merchantCode' => 'number',
        'cartId' => 'orderId',
        'orderTime' => 'orderCreateDate',
        'productName' => 'username',
        'notifyURL' => 'notify_url',
        'returnURL' => 'notify_url',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'CID',
        'v_CartID',
        'v_amount',
        'v_currency',
    ];

    /**
     * 二維支付時需要加密的參數
     *
     * @var array
     */
    protected $scanEncodeParams = [
        'merchantCode',
        'cartId',
        'orderAmount',
        'currency',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'mid' => 1,
        'oid' => 1,
        'cartid' => 1,
        'amt' => 1,
        'cur' => 1,
        'status' => 1,
    ];

    /**
     * 二維支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $scanDecodeParams = [
        'merchantCode' => 1,
        'reference' => 1,
        'cartId' => 1,
        'amount' => 1,
        'currency' => 1,
        'status' => 1,
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
        '1' => 'ICBC', // 中國工商銀行
        '2' => 'BCOM', // 交通銀行
        '3' => 'ABC', // 中國農業銀行
        '4' => 'CCB', // 中國建設銀行
        '5' => 'CMB', // 招商銀行
        '6' => 'CMBC', // 中國民生銀行
        '8' => 'SPDB', // 上海浦東發展銀行
        '10' => 'CIB', // 興業銀行
        '11' => 'CITIC', // 中信銀行
        '12' => 'CEB', // 中國光大銀行
        '14' => 'GDB', // 廣東發展銀行
        '15' => 'PAB', // 平安銀行
        '16' => 'PSBC', // 中國郵政
        '17' => 'BOC', // 中國銀行
        '29' => 'BAY', // Bank of Ayudhya
        '30' => 'KTB', // Krung Thai Bank
        '31' => 'SCB', // Siam Commercial Bank
        '257' => 'BAY', // Bank of Ayudhya
        '258' => 'KTB', // Krung Thai Bank
        '259' => 'SCB', // Siam Commercial Bank
        '1090' => '', // 微信二維
        '1112' => 'UOB', // UOBT
    ];

    /**
     * 查詢時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $trackingRequestData = [
        'CID' => '', // 商戶號
        'v_CartID' => '', // 訂單號
        'v_amount' => '', // 訂單號
        'v_currency' => 'CNY', // 幣別
        'signature' => '', // 簽名
    ];

    /**
     * 查詢時需要加密的參數
     *
     * @var array
     */
    protected $trackingEncodeParams = [
        'CID',
        'v_CartID',
        'v_amount',
        'v_currency',
    ];

    /**
     * 查詢時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $trackingRequireMap = [
        'CID' => 'number',
        'v_CartID' => 'orderId',
        'v_amount' => 'amount',
    ];

    /**
     * 查詢解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $trackingDecodeParams = [
        'mid' => 1,
        'oid' => 1,
        'cur' => 1,
        'status' => 1,
        'cartid' => 1,
        'createdDateTime' => 1,
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

        // 檢查是否有postUrl(支付平台提交的url)
        if (trim($this->options['postUrl']) == '') {
            throw new PaymentException('No pay parameter specified', 180145);
        }

        $uri = '/GUX/GPost';
        $postUrl = $this->options['postUrl'] . '/GUX/GPay';

        // 網銀
        if ($this->options['paymentVendorId'] != 1090) {
            $this->payVerify();

            // 從內部給定值到參數
            foreach ($this->requireMap as $paymentKey => $internalKey) {
                $this->requestData[$paymentKey] = $this->options[$internalKey];
            }

            // 帶入未支援的銀行就噴例外
            if (!array_key_exists($this->requestData['v_bank_code'], $this->bankMap)) {
                throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
            }

            // 額外的參數設定
            $this->requestData['v_amount'] = sprintf('%.2f', $this->requestData['v_amount']);
            $this->requestData['v_bank_code'] = $this->bankMap[$this->requestData['v_bank_code']];

            // 泰國的銀行須調整幣別
            if (in_array($this->options['paymentVendorId'], [29, 30, 31, 257, 258, 259, 1111])) {
                $this->requestData['v_currency'] = 'THB';
            }
        }

        // 二維支付要調整提交網址與提交參數
        if ($this->options['paymentVendorId'] == 1090) {
            $uri = '/payin-wechat/request-tokenform';
            $postUrl = $this->options['postUrl'] . '/payin-wechat/send-tokenform';

            $this->requestData = $this->scanRequestData;
            $this->requireMap = $this->scanRequireMap;
            $this->encodeParams = $this->scanEncodeParams;

            $this->payVerify();

            // 從內部給定值到參數
            foreach ($this->requireMap as $paymentKey => $internalKey) {
                $this->requestData[$paymentKey] = $this->options[$internalKey];
            }

            // 額外的參數設定
            $this->requestData['orderAmount'] = sprintf('%.2f', $this->requestData['orderAmount']);
        }

        // 設定支付平台需要的加密串
        $this->requestData['signature'] = $this->encode();

        // 先對外取得token
        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'method' => 'POST',
            'uri' => $uri,
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => urldecode(http_build_query($this->requestData)),
            'header' => [],
        ];
        $result = $this->curlRequest($curlParam);
        $parseData = json_decode($result, true);
        $token = '';

        // 網銀
        if ($this->options['paymentVendorId'] != 1090) {
            if (!isset($parseData['token'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }
            $token = $parseData['token'];
        }

        // 二維
        if ($this->options['paymentVendorId'] == 1090) {
            if (!isset($parseData['body']['token'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }
            $token = $parseData['body']['token'];
        }

        return [
            'post_url' => $postUrl,
            'params' => ['token' => $token],
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

        // 調整二維解密驗證時需要加密的參數
        if ($entry['payment_vendor_id'] == 1090) {
            $this->decodeParams = $this->scanDecodeParams;
        }

        $this->payResultVerify();

        $encodeData = [];
        $encodeData[] = $this->privateKey;

        // 組織加密串
        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options)) {
                // 金額加密時須額外處理
                if ($paymentKey == 'amt' || $paymentKey == 'amount') {
                    $encodeData[] = round($this->options[$paymentKey] * 100);

                    continue;
                }
                $encodeData[] = $this->options[$paymentKey];
            }
        }
        $encodeStr = implode(';', $encodeData);

        if (!isset($this->options['signature'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['signature'] != md5(strtoupper($encodeStr))) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['status'] != 'SUCCESS') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        // 網銀
        if ($entry['payment_vendor_id'] != 1090) {
            if ($this->options['cartid'] != $entry['id']) {
                throw new PaymentException('Order Id error', 180061);
            }

            if ($this->options['amt'] != $entry['amount']) {
                throw new PaymentException('Order Amount error', 180058);
            }
        }

        // 二維
        if ($entry['payment_vendor_id'] == 1090) {
            if ($this->options['cartId'] != $entry['id']) {
                throw new PaymentException('Order Id error', 180061);
            }

            if ($this->options['amount'] != $entry['amount']) {
                throw new PaymentException('Order Amount error', 180058);
            }
        }
    }

    /**
     * 訂單查詢
     */
    public function paymentTracking()
    {
        // 驗證私鑰
        $this->verifyPrivateKey();
        $this->trackingVerify();
        $this->setTrackingRequestData();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'method' => 'POST',
            'uri' => '/GUX/GQueryPayment',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => http_build_query($this->trackingRequestData),
            'header' => [],
        ];

        // 二維須調整提交參數和查詢網址
        if ($this->options['paymentVendorId'] == 1090) {
            $jsonParams = [
                'header' => [
                    'version' => '1.0',
                    'merchantCode' => $this->trackingRequestData['CID'],
                    'signature' => $this->trackingRequestData['signature'],
                ],
                'body' => [
                    'cartId' => $this->trackingRequestData['v_CartID'],
                    'orderAmount' => $this->trackingRequestData['v_amount'],
                    'currency' => $this->trackingRequestData['v_currency'],
                ],
            ];

            $curlParam['header'] = ['Content-Type' => 'application/json'];
            $curlParam['param'] = json_encode($jsonParams);
            $curlParam['uri'] = '/payin-wechat/status';
        }

        // 取得訂單查詢結果
        $this->options['content'] = $this->curlRequest($curlParam);

        $this->paymentTrackingVerify();
    }

    /**
     * 取得訂單查詢時需要的參數
     *
     * @return array
     */
    public function getPaymentTrackingData()
    {
        // 驗證私鑰
        $this->verifyPrivateKey();
        $this->trackingVerify();
        $this->setTrackingRequestData();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'verify_ip' => $this->options['verify_ip'],
            'path' => '/GUX/GQueryPayment',
            'method' => 'POST',
            'form' => $this->trackingRequestData,
            'headers' => [
                'Host' => $this->options['verify_url'],
            ],
        ];

        // 二維須調整提交參數和查詢網址
        if ($this->options['paymentVendorId'] == 1090) {
            $curlParam['json'] = [
                'header' => [
                    'version' => '1.0',
                    'merchantCode' => $this->trackingRequestData['CID'],
                    'signature' => $this->trackingRequestData['signature'],
                ],
                'body' => [
                    'cartId' => $this->trackingRequestData['v_CartID'],
                    'orderAmount' => $this->trackingRequestData['v_amount'],
                    'currency' => $this->trackingRequestData['v_currency'],
                ],
            ];

            $curlParam['path'] = '/payin-wechat/status';
            unset($curlParam['form']);
        }

        return $curlParam;
    }

    /**
     * 驗證訂單查詢是否成功
     */
    public function paymentTrackingVerify()
    {
        $parseData = json_decode($this->options['content'], true);

        // 網銀
        if ($this->options['paymentVendorId'] != 1090) {
            $this->trackingResultVerify($parseData);

            if ($parseData['status'] != 'SUCCESS') {
                throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
            }

            if ($parseData['cartid'] != $this->options['orderId']) {
                throw new PaymentException('Order Id error', 180061);
            }
        }

        // 二維
        if ($this->options['paymentVendorId'] == 1090) {
            if (!isset($parseData['body']['transactionStatus'])) {
                throw new PaymentException('No tracking return parameter specified', 180139);
            }

            if ($parseData['body']['transactionStatus'] != 'COMPLETED') {
                throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
            }
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
        $encodeData[] = $this->privateKey;

        foreach ($this->encodeParams as $index) {
            // 金額加密時須額外處理
            if ($index == 'v_amount' || $index == 'orderAmount') {
                $encodeData[] = round($this->requestData[$index] * 100);

                continue;
            }
            $encodeData[] = $this->requestData[$index];
        }

        $encodeStr = implode(';', $encodeData);

        return md5(strtoupper($encodeStr));
    }

    /**
     * 訂單查詢時的加密
     *
     * @return string
     */
    protected function trackingEncode()
    {
        $encodeData = [];
        $encodeData[] = $this->privateKey;

        foreach ($this->trackingEncodeParams as $index) {
            // 金額加密時須額外處理
            if ($index == 'v_amount') {
                $encodeData[] = round($this->trackingRequestData[$index] * 100);

                continue;
            }
            $encodeData[] = $this->trackingRequestData[$index];
        }

        $encodeStr = implode(';', $encodeData);

        return md5(strtoupper($encodeStr));
    }

    /**
     * 訂單查詢參數設定
     */
    private function setTrackingRequestData()
    {
        // 從內部給定值到參數
        foreach ($this->trackingRequireMap as $paymentKey => $internalKey) {
            $this->trackingRequestData[$paymentKey] = $this->options[$internalKey];
        }

        // 額外的參數設定
        $this->trackingRequestData['v_amount'] = sprintf('%.2f', $this->trackingRequestData['v_amount']);

        // 泰國銀行須調整銀行幣別
        if (in_array($this->options['paymentVendorId'], [29, 30, 31, 257, 258, 259, 1111])) {
            $this->trackingRequestData['v_currency'] = 'THB';
        }

        // 設定加密簽名
        $this->trackingRequestData['signature'] = $this->trackingEncode();
    }
}

<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 吉祥付
 */
class JiXiang extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數(非網銀)
     *
     * @var array
     */
    private $nonOnlineBankRequestData = [
        'outOid' => '', // 訂單號
        'merchantCode' => '', // 商號
        'mgroupCode' => '', // 平台集團商戶編號
        'payType' => '', // 支付類型
        'payAmount' => '', // 支付金額
        'goodName' => '', // 商品名稱
        'goodNum' => '', // 商品數量，非必填
        'busType' => '', // 業務類型，非必填
        'extend1' => '', // 擴展字段，非必填
        'extend2' => '', // 擴展字段，非必填
        'extend3' => '', // 擴展字段，非必填
        'notifyUrl' => '', // 回調地址
        'sign' => '', // 簽名
    ];

    /**
     * 支付時要傳給平台驗證的參數(網銀)
     *
     * @var array
     */
    protected $requestData = [
        'outOid' => '', // 訂單號
        'merchantCode' => '', // 商號
        'mgroupCode' => '', // 平台集團商戶編號
        'transAmount' => '', // 支付金額
        'goodsName' => '', // 商品名稱
        'goodsDesc' => '', // 商品描述
        'terminalType' => '1', // 終端類型
        'pageNotifyUrl' => '', // 支付頁面異步通知網址
        'tradeNotifyUrl' => '', // 支付結果異步通知網址
        'errpageNotifyUrl' => '', // 支付異常頁面通知網址，非必填
        'bankCode' => '', // 銀行代碼
        'userType' => '1', // 用戶類型 1:個人。2:企業。
        'cardType' => '1', // 支付卡類型。1:借記卡。2:貸記卡
        'extend1' => '', // 擴展字段，非必填
        'extend2' => '', // 擴展字段，非必填
        'extend3' => '', // 擴展字段，非必填
        'sign' => '', // 簽名
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'merchantCode' => 'number',
        'payAmount' => 'amount',
        'transAmount' => 'amount',
        'outOid' => 'orderId',
        'notifyUrl' => 'notify_url',
        'pageNotifyUrl' => 'notify_url',
        'tradeNotifyUrl' => 'notify_url',
        'goodName' => 'username',
        'goodsName' => 'username',
        'goodsDesc' => 'username',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'outOid',
        'merchantCode',
        'mgroupCode',
        'payType',
        'payAmount',
        'transAmount',
        'goodName',
        'goodsName',
        'goodNum',
        'goodsDesc',
        'terminalType',
        'busType',
        'bankCode',
        'userType',
        'cardType',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'outOid' => 1,
        'merchantCode' => 1,
        'mgroupCode' => 1,
        'payType' => 1,
        'payAmount' => 1,
        'busType' => 0,
        'tranAmount' => 1,
        'orderStatus' => 1,
        'platformOid' => 1,
        'timestamp' => 1,
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
        '1' => '1001', // 中國工商銀行
        '2' => '1005', // 交通銀行
        '3' => '1002', // 中國農業銀行
        '4' => '1004', // 中國建設銀行
        '5' => '1012', // 招商銀行
        '6' => '1010', // 中國民生銀行
        '8' => '1014', // 上海浦東發展銀行
        '9' => '1016', // 北京銀行
        '10' => '1013', // 興業銀行
        '11' => '1007', // 中信銀行
        '12' => '1008', // 中國光大銀行
        '13' => '1009', // 華夏銀行
        '14' => '1017', // 廣東發展銀行
        '15' => '1011', // 平安銀行
        '16' => '1006', // 中國郵政
        '17' => '1003', // 中國銀行
        '19' => '1025', // 上海銀行
        '234' => '1103', // 北京農村商業銀行
        '1090' => '10', // 微信_二維
        '1092' => '11', // 支付寶_二維
        '1097' => '33', // 微信_手機支付
        '1098' => '35', // 支付寶_手機支付
        '1102' => '1000', // 收銀台
        '1103' => '26', // QQ_二維
        '1104' => '34', // QQ_手機支付
        '1107' => '21', // 京東錢包_二維
    ];

    /**
     * 非網銀支付銀行
     *
     * @var array
     */
    protected $nonOnlineBank = [
        1097,
        1098,
        1104,
        1090,
        1092,
        1103,
        1107,
    ];

    /**
     * 回傳支付時要給支付平台驗證的資料
     *
     * @return array
     */
    public function getVerifyData()
    {
        if (in_array($this->options['paymentVendorId'], $this->nonOnlineBank)) {
            $this->requestData = $this->nonOnlineBankRequestData;
        }

        // 驗證私鑰
        $this->verifyPrivateKey();

        // 驗證支付參數
        $this->payVerify();

        // 帶入未支援的銀行就噴例外
        if (!array_key_exists($this->options['paymentVendorId'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 從內部給定值到參數
        foreach ($this->requireMap as $paymentKey => $internalKey) {
            if (array_key_exists($paymentKey, $this->requestData)) {
                $this->requestData[$paymentKey] = $this->options[$internalKey];
            }
        }

        // 取得商家附加設定值
        $merchantExtras = $this->getMerchantExtraValue(['mgroupCode']);
        $this->requestData['mgroupCode'] = $merchantExtras['mgroupCode'];

        // 非網銀
        if (in_array($this->options['paymentVendorId'], $this->nonOnlineBank)) {
            // 額外的參數設定
            $this->requestData['payType'] = $this->bankMap[$this->options['paymentVendorId']];
            $this->requestData['payAmount'] = round($this->requestData['payAmount'] * 100);

            $this->requestData['sign'] = $this->encode();

            $uri = '/openapi/pay/scanqrcode/qrcodepay';

            // 取得支付對外返回參數
            $parseData = $this->getPayReturnData($uri);

            // 驗證支付對外返回是否成功
            $this->verifyPayReturn($parseData);

            if (!isset($parseData['value']['qrcodeUrl'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            // 手機支付
            if (in_array($this->options['paymentVendorId'], ['1097', '1098', '1104'])) {
                return $this->getPhonePayData($parseData);
            }

            $this->setQrcode($parseData['value']['qrcodeUrl']);

            return [];
        }

        return $this->getBankPayData();
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

        $encodeData = [];

        // 加密設定
        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options) && $this->options[$paymentKey] !== '') {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        ksort($encodeData);

        // 額外的加密設定
        $encodeData['key'] = $this->privateKey;
        $encodeStr = urldecode(http_build_query($encodeData));

        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if (strtoupper($this->options['sign']) !== strtoupper(md5($encodeStr))) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['orderStatus'] != '2') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        // 檢查單號
        if ($this->options['outOid'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        // 檢查金額
        if ($this->options['payAmount'] != round($entry['amount'] * 100)) {
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
            if (array_key_exists($index, $this->requestData) && trim($this->requestData[$index]) !== '') {
                $encodeData[$index] = $this->requestData[$index];
            }
        }

        ksort($encodeData);

        $encodeData['key'] = $this->privateKey;
        $encodeStr = urldecode(http_build_query($encodeData));

        return strtoupper(md5($encodeStr));
    }

    /**
     * 取得支付對外返回參數
     *
     * @return array
     */
    private function getPayReturnData($uri)
    {
        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'method' => 'POST',
            'uri' => $uri,
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => urldecode(http_build_query($this->requestData)),
            'header' => ['Port' => 8081],
        ];

        $result = $this->curlRequest($curlParam);
        $parseData = json_decode($result, true);

        return $parseData;
    }

    /**
     * 驗證支付對外返回是否成功
     *
     * @param array $parseData
     */
    private function verifyPayReturn($parseData)
    {
        if (!isset($parseData['code']) || !isset($parseData['msg'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        if (trim($parseData['code']) !== '000000') {
            throw new PaymentConnectionException($parseData['msg'], 180130, $this->getEntryId());
        }
    }

    /**
     * 取得手機支付參數
     *
     * @return array
     */
    private function getPhonePayData($parseData)
    {
        $parseUrl = parse_url(urldecode($parseData['value']['qrcodeUrl']));

        $parseUrlValues = [
            'scheme',
            'host',
            'path',
            'query',
        ];

        foreach ($parseUrlValues as $key) {
            if (!isset($parseUrl[$key])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }
        }

        $param = [];

        parse_str($parseUrl['query'], $param);

        $postUrl = sprintf(
            '%s://%s%s',
            $parseUrl['scheme'],
            $parseUrl['host'],
            $parseUrl['path']
        );

        // Form使用GET才能正常跳轉
        $this->payMethod = 'GET';

        return [
            'post_url' => $postUrl,
            'params' => $param,
        ];
    }

    /**
     * 取得網銀支付參數
     *
     * @return array
     */
    private function getBankPayData()
    {
        // 額外的參數設定
        $this->requestData['bankCode'] = $this->bankMap[$this->options['paymentVendorId']];
        $this->requestData['transAmount'] = round($this->requestData['transAmount'] * 100);

        $this->requestData['sign'] = $this->encode();

        $uri = '/openapi/pay/cardpay/cardpayapply3';

        // 取得支付對外返回參數
        $parseData = $this->getPayReturnData($uri);

        // 驗證支付對外返回是否成功
        $this->verifyPayReturn($parseData);

        if (!isset($parseData['value']['url']) || !isset($parseData['value']['data'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        parse_str($parseData['value']['data'], $this->options);
        $this->options['sign'] = rawurldecode(urlencode($this->options['sign']));

        return [
            'post_url' => $parseData['value']['url'],
            'params' => $this->options,
        ];
    }
}

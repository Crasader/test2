<?php

namespace BB\PineappleTradeBundle\Payment;

/**
 * 個碼付
 */
class GeMa extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'service' => 'hc.createorder', // 接口路由，固定值
        'merchantcode' => '', // 商戶號
        'merchorder_no' => '', // 訂單號
        'money' => '', // 金額，單位:元，精確到小數點後兩位
        'paytype' => '', // 支付方式
        'backurl' => '', // 異步通知網址
        'returnurl' => '', // 同步通知網址，不帶參數
        'transdate' => '', // 訂單時間，格式yyyyMMddHHmmss
        'sign' => '', // 簽名
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'merchantcode' => 'merchant_number',
        'merchorder_no' => 'order_number',
        'money' => 'amount',
        'paytype' => 'method_id',
        'backurl' => 'notify_url',
        'returnurl' => 'notify_url',
        'transdate' => 'order_create_date',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'service',
        'merchantcode',
        'merchorder_no',
        'money',
        'paytype',
        'backurl',
        'returnurl',
        'transdate',
        'sign',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'retcode' => '1',
        'result' => '1',
        'merchorder_no' => '1',
        'money' => '1',
    ];

    /**
     * 應答機制訊息
     *
     * @var string
     */
    protected $msg = '0000';

    /**
     * 支付平台支援的銀行
     *
     * @var array
     */
    protected $methodMap = [
        201 => '1', // 支付寶_手機支付
    ];

    /**
     * 取得訂單號
     *
     * @param array $verifyData
     * @return string
     */
    public function getOrderNumber($verifyData)
    {
        return $verifyData['merchorder_no'];
    }

    /**
     * 回傳支付時要給支付平台驗證的資料
     *
     * @return array
     */
    public function getDepositParams()
    {
        parent::getDepositParams();

        // 額外的參數設定
        $requestTime = new \DateTime($this->requestData['transdate']);
        $this->requestData['transdate'] = $requestTime->format('YmdHis');
        $this->requestData['paytype'] = $this->methodMap[$this->requestData['paytype']];
        $this->requestData['money'] = sprintf('%.2f', $this->requestData['money']);

        // 因返回的參數均加密，故需串訂單號
        $this->requestData['backurl'] = sprintf(
            '%s?order_number=%s',
            $this->requestData['backurl'],
            $this->requestData['merchorder_no']
        );

        // 設定支付平台需要的加密串
        $this->requestData['sign'] = $this->encode();

        $curlParam = [
            'method' => 'POST',
            'proxy' => $this->options['proxy_ip'],
            'uri' => $this->getUrl(),
            'json' => $this->requestData,
        ];

        $result = $this->curlRequest($curlParam);
        $parseData = json_decode($result, true);

        if (!isset($parseData['retcode'])) {
            throw new \RuntimeException('Get pay parameters failed', 1509020008);
        }

        if ($parseData['retcode'] == 'R9') {
            throw new \RuntimeException('Order Processing', 1509020022);
        }

        if ($parseData['retcode'] !== '00') {
            if (isset($parseData['result'])) {
                throw new \RuntimeException($parseData['result'], 1509020009);
            }

            throw new \RuntimeException('Pay error', 1509020009);
        }

        if (!isset($parseData['transurl'])) {
            throw new \RuntimeException('Get pay parameters failed', 1509020008);
        }

        // 解析提交網址
        $parsedUrl = $this->parseUrl($parseData['transurl']);

        $this->method = 'GET';
        $this->setUrl($parsedUrl['url']);

        return $parsedUrl['params'];
    }

    /**
     * 驗證線上支付是否成功
     */
    public function depositVerify()
    {
        $verifyData = $this->options['verify_data']['respContent'];

        // respContent需先urldecode再base64解密
        $verifyData = base64_decode(urldecode($verifyData));
        $this->options['verify_data'] = $verifyData;

        parent::depositVerify();

        $encodeData = [];

        // 組織加密串
        foreach (array_keys($this->decodeParams) as $paymentKey) {
            $encodeData[$paymentKey] = $verifyData[$paymentKey];
        }

        $encodeStr = json_encode($encodeData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $encodeStr .= $this->privateKey;

        // 如果沒有返回簽名要丟例外
        if (!isset($verifyData['sign'])) {
            throw new \RuntimeException('No return parameter specified', 1509020001);
        }

        if ($verifyData['sign'] != hash("sha512", $encodeStr)) {
            throw new \RuntimeException('Signature verification failed', 1509020011);
        }

        if ($verifyData['retcode'] != '00') {
            throw new \RuntimeException('Payment failure', 1509020012);
        }

        if ($verifyData['merchorder_no'] != $this->options['order_number']) {
            throw new \RuntimeException('Order Id error', 1509020013);
        }

        if ($verifyData['money'] != round($this->options['money'] * 100)) {
            throw new \RuntimeException('Order Amount error', 1509020014);
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
            $encodeData[$index] = $this->requestData[$index];
        }

        // 加密字串必須先處理 1.斜線不要加入反斜線 2.中文不要轉Unicode，否則簽名驗證會失敗
        $encodeStr = json_encode($encodeData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $encodeStr .= $this->privateKey;

        return hash("sha512", $encodeStr);
    }
}
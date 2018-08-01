<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 商易付支付
 */
class ShangYiZhiFu extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'parter' => '', // 商號
        'type' => '', // 銀行代碼
        'value' => '', // 金額(精確到小數後兩位)
        'orderid' => '', // 訂單號
        'callbackurl' => '', // 異步通知url, 不能串參數
        'hrefbackurl' => '', // 同步通知url, 可空
        'payerIp' => '', // 支付用戶ip, 可空
        'attach' => '', // 備註, 可空
        'sign' => '', // 加密簽名，GB2312编碼
        'agent' => '', // 代理ID 如果沒有代理，可以留空
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'parter' => 'number',
        'type' => 'paymentVendorId',
        'value' => 'amount',
        'orderid' => 'orderId',
        'callbackurl' => 'notify_url',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'parter',
        'type',
        'value',
        'orderid',
        'callbackurl',
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
        'systime' => 1,
        'sysorderid' => 1,
    ];

    /**
     * 應答機制訊息
     *
     * @var string
     */
    protected $msg = 'opstate=0';

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
        '1003' => '2004', // 中國工商銀行(快)
        '1004' => '2016', // 交通銀行(快)
        '1005' => '2002', // 中國農業銀行(快)
        '1006' => '2003', // 中國建設銀行(快)
        '1007' => '2006', // 招商銀行(快)
        '1008' => '2015', // 中國民生銀行(快)
        '1009' => '2012', // 上海浦東發展銀行(快)
        '1010' => '2024', // 北京銀行(快)
        '1011' => '2008', // 興業銀行(快)
        '1012' => '2000', // 中信銀行(快)
        '1013' => '2021', // 中國光大銀行(快)
        '1014' => '2017', // 華夏銀行(快)
        '1015' => '2020', // 廣東發展銀行(快)
        '1016' => '2013', // 平安銀行(快)
        '1017' => '2007', // 中國郵政(快)
        '1018' => '2001', // 中國銀行(快)
        '1019' => '2010', // 上海銀行(快)
        '1090' => '8011', // 微信支付_二維
        '1097' => '933', // 微信_手機支付
        '1103' => '993', // QQ_二維
        '1105' => '2027', // 廣州銀行(快)
        '1107' => '911', // 京東_二維
        '1111' => '7011', // 銀聯_二維
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
        if (!array_key_exists($this->requestData['type'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $this->requestData['value'] = sprintf('%.2f', $this->requestData['value']);
        $this->requestData['type'] = $this->bankMap[$this->requestData['type']];

        // 設定支付平台需要的加密串
        $this->requestData['sign'] = $this->encode();

        // 微信手機支付需先取得支付網址
        if (in_array($this->options['paymentVendorId'], [1097])) {
            $curlParam = [
                'method' => 'POST',
                'uri' => '/chargebank.aspx',
                'ip' => $this->options['verify_ip'],
                'host' => $this->options['verify_url'],
                'param' => urldecode(http_build_query($this->requestData)),
                'header' => [],
            ];

            $result = $this->curlRequest($curlParam);
            $parseUrl = parse_url($result);

            $parseUrlValues = [
                'scheme',
                'host',
                'path',
            ];

            foreach ($parseUrlValues as $key) {
                if (!isset($parseUrl[$key])) {
                    throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
                }
            }

            $params = [];

            if (isset($parseUrl['query'])) {
                parse_str($parseUrl['query'], $params);
            }

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
                'params' => $params,
            ];
        }

        // 檢查是否有postUrl(支付平台提交的url)
        if (trim($this->options['postUrl']) == '') {
            throw new PaymentException('No pay parameter specified', 180145);
        }

        $params = http_build_query($this->requestData);

        return [
            'post_url' => $this->options['postUrl'] . '?' . $params,
            'params' => [],
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

        // 組織加密串
        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options)) {
                if ($paymentKey == 'systime') {
                    $encodeData['time'] = $this->options[$paymentKey];

                    continue;
                }
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        // 依key1=value1&key2=value2&...&keyN=valueN之後做md5
        $encodeStr = urldecode(http_build_query($encodeData));
        $encodeStr .= $this->privateKey;

        // 如果沒有返回簽名要丟例外
        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['sign'] != md5($encodeStr)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        // 請求參數無效
        if ($this->options['opstate'] == '-1') {
            throw new PaymentConnectionException('Invalid pay parameters', 180129, $this->getEntryId());
        }

        // 簽名錯誤
        if ($this->options['opstate'] == '-2') {
            throw new PaymentConnectionException(
                'PaymentGateway error, Merchant sign error',
                180127,
                $this->getEntryId()
            );
        }

        if ($this->options['opstate'] != '0') {
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

        // 依key1=value1&key2=value2&...&keyN=valueN之後做md5
        $encodeStr = urldecode(http_build_query($encodeData));
        $encodeStr .= $this->privateKey;

        return md5($encodeStr);
    }
}

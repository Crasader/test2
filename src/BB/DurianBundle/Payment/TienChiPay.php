<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 天吉支付
 */
class TienChiPay extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'merchant_id' => '', // 商戶號
        'order_id' => '', // 訂單號
        'pay_type' => '03', // 交易類型，預設網銀直連:03
        'rsp_type' => '02', // 收銀台類型，01代表收銀台，02代表直連，預設直連
        'trans_amt' => '', // 交易金額，單位:分
        'back_url' => '', // 異步通知網址
        'front_url' => '', // 同步通知地址，默認為空
        'goods_title' => '', // 商品標題
        'goods_desc' => '', // 商品描述
        'send_ip' => '', // 商戶IP
        'send_time' => '', // 發送時間，格式:YmdHis
        'sign' => '', // 簽名
        'pay_desc' => '', // 支付描述
        'bank_id' => '', // 直連銀行代碼
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'merchant_id' => 'number',
        'order_id' => 'orderId',
        'bank_id' => 'paymentVendorId',
        'trans_amt' => 'amount',
        'back_url' => 'notify_url',
        'goods_title' => 'username',
        'goods_desc' => 'username',
        'send_ip' => 'ip',
        'send_time' => 'orderCreateDate',
        'pay_desc' => 'username',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'merchant_id',
        'order_id',
        'pay_type',
        'rsp_type',
        'trans_amt',
        'back_url',
        'goods_title',
        'goods_desc',
        'send_ip',
        'send_time',
        'pay_desc',
        'bank_id',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'merchant_id' => 1,
        'order_id' => 1,
        'trans_amt' => 1,
        'send_time' => 1,
        'goods_desc' => 0,
        'resp_code' => 1,
        'resp_desc' => 1,
    ];

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        1 => '01020000', // 中國工商銀行
        2 => '03010000', // 交通銀行
        3 => '01030000', // 中國農業銀行
        4 => '01050000', // 中國建設銀行
        5 => '03080000', // 招商銀行
        6 => '03050000', // 中國民生銀行
        8 => '03100000', // 上海浦東發展銀行
        9 => '04031000', // 北京銀行
        11 => '03020000', // 中信銀行
        12 => '03030000', // 中國光大銀行
        13 => '03040000', // 華夏銀行
        14 => '03060000', // 廣東發展銀行
        15 => '04100000', // 深圳平安銀行
        16 => '01000000', // 中國郵政
        17 => '01040000', // 中國銀行
        19 => '04012900', // 上海銀行
        1090 => '02', // 微信_二維
        1092 => '01', // 支付寶_二維
        1097 => '05', // 微信_手機支付
        1098 => '06', // 支付寶_手機支付
        1102 => '03', // 網銀收銀台
        1103 => '010500', // QQ_二維
        1104 => '010500', // QQ_手機支付
        1107 => '07', // 京東_二維
        1108 => '08', // 京東_手機支付
        1111 => '09', // 銀聯_二維
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
        if (!array_key_exists($this->requestData['bank_id'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        $createAt = new \Datetime($this->requestData['send_time']);

        // 額外參數設定
        $this->requestData['send_time'] = $createAt->format('YmdHis');
        $this->requestData['bank_id'] = $this->bankMap[$this->requestData['bank_id']];
        $this->requestData['trans_amt'] = round($this->requestData['trans_amt'] * 100);

        $notOnlineBank = ['1090', '1092', '1097', '1098', '1102', '1103', '1104', '1107', '1108', '1111'];
        // 非網銀直連參數設定
        if (in_array($this->options['paymentVendorId'], $notOnlineBank)) {
            $this->requestData['pay_type'] = $this->requestData['bank_id'];
            unset($this->requestData['bank_id']);
        }

        // 微信二維、支付寶二維目前只支援收銀台方式:01
        if (in_array($this->options['paymentVendorId'], ['1090', '1092'])) {
            $this->requestData['rsp_type'] = '01';
        }

        // 設定支付平台需要的加密串
        $this->requestData['sign'] = $this->encode();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'method' => 'POST',
            'uri' => '/pay',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => urldecode(http_build_query($this->requestData)),
            'header' => [],
        ];

        $result = $this->curlRequest($curlParam);
        $parseData = json_decode($result, true);

        if (!isset($parseData['ret_code']) || !isset($parseData['ret_msg'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        if ($parseData['ret_code'] != '200') {
            throw new PaymentConnectionException($parseData['ret_msg'], 180130, $this->getEntryId());
        }

        if (!isset($parseData['result']['pay_link'])) {
            throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
        }

        // 掃碼
        if (in_array($this->options['paymentVendorId'], ['1090', '1092', '1103', '1107', '1111'])) {
            $this->setQrcode($parseData['result']['pay_link']);

            return [];
        }

        // QQ手機支付
        if ($this->options['paymentVendorId'] == '1104') {
            return [
                'post_url' => $parseData['result']['pay_link'],
                'params' => [],
            ];
        }

        $parseUrl = parse_url(urldecode($parseData['result']['pay_link']));

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

        $params = [];

        parse_str($parseUrl['query'], $params);

        // 轉字串編碼
        foreach ($params as $key => $param) {
            $params[$key] = iconv('gb2312', 'utf-8', urldecode($param));
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

        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options) && $this->options[$paymentKey] != '') {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        ksort($encodeData);

        $encodeData['key'] = $this->privateKey;
        $encodeStr = urldecode(http_build_query($encodeData));

        // 如果沒有返回簽名要丟例外
        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['sign'] != md5($encodeStr)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['resp_code'] === '0001') {
            throw new PaymentConnectionException('Unpaid order', 180062, $this->getEntryId());
        }

        if ($this->options['resp_code'] !== '0000') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['order_id'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['trans_amt'] != round($entry['amount'] * 100)) {
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

        foreach ($this->encodeParams as $paymentKey) {
            if (array_key_exists($paymentKey, $this->requestData)) {
                $encodeData[$paymentKey] = $this->requestData[$paymentKey];
            }
        }

        ksort($encodeData);

        $encodeData['key'] = $this->privateKey;
        $encodeStr = urldecode(http_build_query($encodeData));

        return strtoupper(md5($encodeStr));
    }
}

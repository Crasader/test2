<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 眾寶2.0
 */
class ZBPay2 extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'merchantid' => '', // 商號
        'paytype' => '', // 支付類型
        'amount' => '', // 金額，單位元
        'orderid' => '', // 訂單號
        'notifyurl' => '', // 異步通知地址，不能串參數
        'request_time' => '', // 請求時間
        'returnurl' => '', // 同步通知地址，可空
        'israndom' => 'N', // 啟用訂單風控保護規則，帶N關閉風控
        'isqrcode' => 'N', // 是否單獨返回二維碼，預設N走收銀台
        'desc' => '', // 備註，可空
        'sign' => '', // 加密簽名
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'merchantid' => 'number',
        'paytype' => 'paymentVendorId',
        'amount' => 'amount',
        'orderid' => 'orderId',
        'notifyurl' => 'notify_url',
        'request_time' => 'orderCreateDate',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'merchantid',
        'paytype',
        'amount',
        'orderid',
        'notifyurl',
        'request_time',
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
        'result' => 1,
        'amount' => 1,
        'systemorderid' => 1,
        'completetime' => 1,
    ];

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
        '14' => '985', // 廣東發展銀行
        '16' => '971', // 中國郵政
        '17' => '963', // 中國銀行
        '220' => '983', // 杭州銀行
        '223' => '987', // 東亞銀行
        '226' => '979', // 南京銀行
        '228' => '976', // 上海市農村商業銀行
        '278' => '', // 銀聯在線(快捷)
        '1088' => '', // 銀聯在線_手機支付
        '1092' => '1003', // 支付寶_二維
        '1097' => '1002', // 微信_手機支付
        '1098' => '1004', // 支付寶_手機支付
        '1103' => '1005', // QQ_二維
        '1104' => '1006', // QQ_手機支付
        '1107' => '1007', // 京東錢包_二維
        '1108' => '1008', // 京東錢包_手機支付
        '1111' => '1009', // 銀聯_二維
        '1115' => '', // 微信支付_條碼
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
        if (!array_key_exists($this->requestData['paytype'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $date = new \DateTime($this->requestData['request_time']);
        $this->requestData['request_time'] = $date->format('YmdHis');
        $this->requestData['amount'] = sprintf('%.2f', $this->requestData['amount']);
        $this->requestData['paytype'] = $this->bankMap[$this->requestData['paytype']];

        // 銀聯快捷支付、銀聯手機支付需調整參數
        if (in_array($this->options['paymentVendorId'], [278, 1088])) {
            $this->requestData['bankcode'] = $this->requestData['paytype'];

            unset($this->requestData['paytype']);
            unset($this->requestData['israndom']);
            unset($this->requestData['isqrcode']);
        }

        // 條碼支付需調整參數
        if ($this->options['paymentVendorId'] == 1115) {
            unset($this->requestData['paytype']);
            unset($this->requestData['israndom']);
            unset($this->requestData['isqrcode']);
        }

        // 設定支付平台需要的加密串
        $this->requestData['sign'] = $this->encode();

        // 二維支付
        if (in_array($this->options['paymentVendorId'], [1092, 1103, 1107, 1111])) {
            // 調整額外參數
            $this->requestData['isqrcode'] = 'Y';

            if (trim($this->options['verify_url']) == '') {
                throw new PaymentException('No verify_url specified', 180140);
            }

            $curlParam = [
                'method' => 'POST',
                'uri' => '/GateWay/Pay',
                'ip' => $this->options['verify_ip'],
                'host' => $this->options['verify_url'],
                'param' => urldecode(http_build_query($this->requestData)),
                'header' => [],
            ];

            $result = $this->curlRequest($curlParam);
            $parseData = json_decode($result, true);

            if (!isset($parseData['code']) || !isset($parseData['msg'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            if ($parseData['code'] !== 0) {
                throw new PaymentConnectionException($parseData['msg'], 180130, $this->getEntryId());
            }

            if (!isset($parseData['qrinfo']) || !isset($parseData['qrtype'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            // qrtype=qrcode，需自行生成二維碼圖片
            if ($parseData['qrtype'] == 'qrcode') {
                $this->setQrcode($parseData['qrinfo']);

                return [];
            }

            return [
                'post_url' => $parseData['qrinfo'],
                'params' => [],
            ];
        }

        // 檢查是否有postUrl(支付平台提交的url)
        if (trim($this->options['postUrl']) == '') {
            throw new PaymentException('No pay parameter specified', 180145);
        }

        // 網銀收銀台調整提交網址
        $postUrl = $this->options['postUrl'] . '/GateWay/Pay';

        // 銀聯快捷支付、銀聯手機支付調整提交網址
        if (in_array($this->options['paymentVendorId'], [278, 1088])) {
            $postUrl = $this->options['postUrl'] . '/FastPay/Index';
        }

        // 條碼支付調整提交網址
        if ($this->options['paymentVendorId'] == 1115) {
            $postUrl = $this->options['postUrl'] . '/WxPay/BarCodePay';
        }

        return [
           'post_url' => $postUrl,
           'params' => $this->requestData,
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
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        $encodeData['key'] = $this->privateKey;
        $encodeStr = urldecode(http_build_query($encodeData));

        // 如果沒有返回簽名要丟例外
        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['sign'] != md5($encodeStr)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['result'] === '0') {
            throw new PaymentConnectionException('Unpaid order', 180062, $this->getEntryId());
        }

        if ($this->options['result'] !== '1') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['orderid'] != $entry['id']) {
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

        // 加密設定
        foreach ($this->encodeParams as $index) {
            if (array_key_exists($index, $this->requestData)) {
                $encodeData[$index] = $this->requestData[$index];
            }
        }

        $encodeData['key'] = $this->privateKey;
        $encodeStr = urldecode(http_build_query($encodeData));

        return md5($encodeStr);
    }
}

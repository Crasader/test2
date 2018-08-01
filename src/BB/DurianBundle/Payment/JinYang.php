<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 金陽支付
 */
class JinYang extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'p1_mchtid' => '', // 商戶號
        'p2_paytype' => '', // 支付方式
        'p3_paymoney' => '', // 交易金額(單位：元)
        'p4_orderno' => '', // 商戶訂單號
        'p5_callbackurl' => '', // 異步通知地址
        'p6_notifyurl' => '', // 同步通知地址
        'p7_version' => 'v2.8', // 版本號，固定值
        'p8_signtype' => '1', // 加密方式，固定值
        'p9_attach' => '', // 備註，非必填
        'p10_appname' => '', // 分成標示，非必填
        'p11_isshow' => '1', // 是否顯示收銀台
        'p12_orderip' => '', // 會員ip，非必填
        'sign' => '', // 簽名
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'p1_mchtid' => 'number',
        'p2_paytype' => 'paymentVendorId',
        'p3_paymoney' => 'amount',
        'p4_orderno' => 'orderId',
        'p5_callbackurl' => 'notify_url',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'p1_mchtid',
        'p2_paytype',
        'p3_paymoney',
        'p4_orderno',
        'p5_callbackurl',
        'p6_notifyurl',
        'p7_version',
        'p8_signtype',
        'p9_attach',
        'p10_appname',
        'p11_isshow',
        'p12_orderip',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'partner' => 1,
        'ordernumber' => 1,
        'orderstatus' => 1,
        'paymoney' => 1,
    ];

    /**
     * 應答機制訊息
     *
     * @var string
     */
    protected $msg = 'ok';

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        '1' => 'ICBC', // 中國工商銀行
        '2' => 'BOCO', // 交通銀行
        '3' => 'ABC', // 中國農業銀行
        '4' => 'CCB', // 中國建設銀行
        '5' => 'CMB', // 招商銀行
        '6' => 'CMBC', // 中國民生銀行
        '8' => 'SPDB', // 上海浦東發展銀行
        '9' => 'BCCB', // 北京銀行
        '10' => 'CIB', // 興業銀行
        '11' => 'CTTIC', // 中信銀行
        '12' => 'CEB', // 中國光大銀行
        '13' => 'HXB', // 華夏銀行
        '14' => 'GDB', // 廣東發展銀行
        '15' => 'PINGANBANK', // 平安銀行
        '16' => 'PSBS', // 中國郵政
        '17' => 'BOC', // 中國銀行
        '19' => 'SHB', // 上海銀行
        '217' => 'CBHB', // 渤海銀行
        '222' => 'NBCB', // 宁波銀行
        '223' => 'HKBEA', // 東亞銀行
        '226' => 'NJCB', // 南京銀行
        '228' => 'SRCB', // 上海市農村商業銀行
        '233' => 'CZB', // 浙江稠州商業銀行
        '234' => 'BJRCB', // 北京農村商業銀行
        '278' => 'FASTPAY', // 銀聯在線
        '297' => 'TENPAY', // 財付通
        '1088' => 'FASTPAY', // 銀聯在線_手機支付
        '1090' => 'WEIXIN', // 微信_二維
        '1092' => 'ALIPAY', // 支付寶_二維
        '1097' => 'WEIXINWAP', // 微信_手機支付
        '1098' => 'ALIPAYWAP', // 支付寶_手機支付
        '1103' => 'QQPAY', // QQ_二維
        '1104' => 'QQPAYWAP', // QQ_手機支付
        '1107' => 'JDPAY', // 京東錢包_二維
        '1108' => 'JDPAYWAP', // 京東錢包_手機支付
        '1109' => 'BAIDUPAY', // 百度錢包_二維
        '1111' => 'UNIONPAY', // 銀聯錢包_二維
        '1115' => 'WEIXINBARCODE', // 微信_條碼
    ];

    /**
     * 二維支付銀行(需對外)
     *
     * @var array
     */
    protected $qrcodeBank = [
        1090,
        1092,
        1103,
        1107,
        1109,
        1111,
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

        // 驗證支付參數
        $this->payVerify();

        // 從內部給定值到參數
        foreach ($this->requireMap as $paymentKey => $internalKey) {
            $this->requestData[$paymentKey] = $this->options[$internalKey];
        }

        // 帶入未支援的銀行就噴例外
        if (!array_key_exists($this->requestData['p2_paytype'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 商家額外的參數設定
        $this->requestData['p2_paytype'] = $this->bankMap[$this->requestData['p2_paytype']];
        $this->requestData['p3_paymoney'] = sprintf('%.2f', $this->requestData['p3_paymoney']);

        // 銀聯在線調整提交參數與加密參數
        if (in_array($this->options['paymentVendorId'], ['278', '1088'])) {
            $this->requestData['p13_memberid'] = $this->options['username'];
            $this->encodeParams[] = 'p13_memberid';
        }

        $this->requestData['sign'] = $this->encode();

        // 二維支付需要對外
        if (in_array($this->options['paymentVendorId'], $this->qrcodeBank)) {
            if (trim($this->options['verify_url']) == '') {
                throw new PaymentException('No verify_url specified', 180140);
            }

            $curlParam = [
                'method' => 'POST',
                'uri' => '/zfapi/order/pay',
                'ip' => $this->options['verify_ip'],
                'host' => $this->options['verify_url'],
                'param' => urldecode(http_build_query($this->requestData)),
                'header' => [],
            ];

            $result = $this->curlRequest($curlParam);
            $parseData = json_decode($result, true);

            if (!isset($parseData['rspCode']) || !isset($parseData['rspMsg'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            if (trim($parseData['rspCode']) !== '1') {
                throw new PaymentConnectionException($parseData['rspMsg'], 180130, $this->getEntryId());
            }

            if (!isset($parseData['data']['r6_qrcode'])) {
                throw new PaymentConnectionException('Get pay parameters failed', 180128, $this->getEntryId());
            }

            $html = sprintf('<img src="%s"/>', $parseData['data']['r6_qrcode']);

            $this->setHtml($html);

            return [];
        }

        return $this->requestData;
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

        foreach (array_keys($this->decodeParams) as $paymentKey) {
            $encodeData[$paymentKey] = $this->options[$paymentKey];
        }

        $encodeStr = urldecode(http_build_query($encodeData));
        $encodeStr .= $this->privateKey;

        // 若沒有返回簽名需丟例外
        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['sign'] != md5($encodeStr)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['orderstatus'] != '1') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['ordernumber'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['paymoney'] != $entry['amount']) {
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
            $encodeData[$index] = $this->requestData[$index];
        }

        $encodeStr = urldecode(http_build_query($encodeData));
        $encodeStr .= $this->privateKey;

        return md5($encodeStr);
    }
}

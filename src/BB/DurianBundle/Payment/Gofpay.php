<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 國付寶
 *
 * 支付驗證：
 * 1. 驗證不可為空的參數
 * 2. 設定參數
 * 3. 額外處理的參數
 * 4. 設定encodeStr(加密後的字串)
 *
 * 解密驗證：
 * 1. 驗證key
 * 2. 設定參數
 * 3. 驗證結果是否相符
 */
class Gofpay extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'version'          => '2.0', //版本號
        'charset'          => '2', //字符集，可空
        'language'         => '1', //網關語言版本
        'signType'         => '1', //報文加密方式
        'tranCode'         => '8888', //交易代碼
        'merchantID'       => '', //商戶代碼
        'merOrderNum'      => '', //訂單號
        'tranAmt'          => '', //交易金額
        'feeAmt'           => '0', //商戶提取佣金金額，可空
        'currencyType'     => '156', //幣種
        'frontMerUrl'      => '', //商戶前台通知地址
        'backgroundMerUrl' => '', //商戶後台通知地址
        'tranDateTime'     => '', //交易時間
        'virCardNoIn'      => '', //國付寶轉入帳戶
        'tranIP'           => '', //用戶瀏覽器IP
        'isRepeatSubmit'   => '0', //訂單是否允許重複提交，可空
        'goodsName'        => '', //商品名稱，可空
        'goodsDetail'      => '', //商品詳情，可空
        'buyerName'        => '', //買方姓名，可空
        'buyerContact'     => '', //買方聯繫方式，可空
        'merRemark1'       => '', //商戶備用信息字段，可空
        'merRemark2'       => '', //商戶備用信息字段，可空
        'signValue'        => '', //密文串
        'gopayServerTime'  => '', //服務器時間
        'bankCode'         => '', //銀行代碼
        'userType'         => '1', //用戶類型
        'orderId'          => '', //額外的加密參數，加密後會unset
        'respCode'         => '', //額外的加密參數，加密後會unset
        'gopayOutOrderId'  => '' //額外的加密參數，加密後會unset
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'merchantID' => 'number',
        'merOrderNum' => 'orderId',
        'tranAmt' => 'amount',
        'backgroundMerUrl' => 'notify_url',
        'tranDateTime' => 'orderCreateDate',
        'tranIP' => 'ip',
        'goodsName' => 'username',
        'buyerName' => 'username',
        'bankCode' => 'paymentVendorId'
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'version',
        'tranCode',
        'merchantID',
        'merOrderNum',
        'tranAmt',
        'feeAmt',
        'tranDateTime',
        'frontMerUrl',
        'backgroundMerUrl',
        'orderId',
        'gopayOutOrderId',
        'tranIP',
        'respCode',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'version' => 1,
        'tranCode' => 1,
        'merchantID' => 1,
        'merOrderNum' => 1,
        'tranAmt' => 1,
        'feeAmt' => 1,
        'tranDateTime' => 1,
        'frontMerUrl' => 1,
        'backgroundMerUrl' => 1,
        'orderId' => 1,
        'gopayOutOrderId' => 1,
        'tranIP' => 1,
        'respCode' => 1
    ];

    /**
     * 應答機制訊息
     *
     * @var string
     */
    protected $msg = 'RespCode=0000|JumpURL=';

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        1  => 'ICBC', //中國工商銀行
        2  => 'BOCOM', //交通銀行
        3  => 'ABC', //中國農業銀行
        4  => 'CCB', //中國建設銀行
        5  => 'CMB', //招商銀行
        6  => 'CMBC', //中國民生銀行
        8  => 'SPDB', //上海浦東發展銀行
        10 => 'CIB', //興業銀行
        11 => 'CITIC', //中信銀行
        12 => 'CEB', //光大銀行
        13 => 'HXBC', //華夏銀行
        14 => 'GDB', //廣東發展銀行
        16 => 'PSBC', //中國郵政儲蓄銀行
        17 => 'BOC' //中國銀行
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

        $this->options['notify_url'] = sprintf(
            '%s?pay_system=%s&hallid=%s',
            $this->options['notify_url'],
            $this->options['merchantId'],
            $this->options['domain']
        );

        // 從內部給定值到參數
        foreach ($this->requireMap as $paymentKey => $internalKey) {
            $this->requestData[$paymentKey] = $this->options[$internalKey];
        }

        //額外的驗證項目
        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        //帶入未支援的銀行就噴例外
        if (!array_key_exists($this->requestData['bankCode'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        //取得防钓鱼必填字段
        //url: https://www.gopay.com.cn/PGServer/time
        $curlParam = [
            'method' => 'GET',
            'uri' => '/PGServer/time',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => '',
            'header' => []
        ];

        $getPaymentServerTime = $this->curlRequest($curlParam);

        // 取得商家附加設定值
        $merchantExtraValues = $this->getMerchantExtraValue(['virCardNoIn']);

        //額外的參數設定
        $tranDateTime = new \DateTime($this->requestData['tranDateTime']);
        $this->requestData['virCardNoIn'] = $merchantExtraValues['virCardNoIn'];
        $this->requestData['tranDateTime'] = $tranDateTime->format('YmdHis');
        $this->requestData['tranAmt'] = sprintf('%.2f', $this->requestData['tranAmt']);
        $this->requestData['gopayServerTime'] = $getPaymentServerTime;
        $this->requestData['bankCode'] = $this->bankMap[$this->requestData['bankCode']];

        //設定支付平台需要的加密串
        $this->requestData['signValue'] = $this->encode();

        unset($this->requestData['orderId']);
        unset($this->requestData['respCode']);
        unset($this->requestData['gopayOutOrderId']);

        return $this->requestData;
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

        $encodeStr = '';

        // 國付寶後台回傳會少 hallid 參數，從這裡加回去。
        if (!strpos($this->options['backgroundMerUrl'], 'hallid') && isset($this->options['hallid'])) {
            $this->options['backgroundMerUrl'] .= "&hallid={$this->options['hallid']}";
        }

        $this->payResultVerify();

        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options)) {
                $encodeStr .= $paymentKey . '=[' . $this->options[$paymentKey] . ']';
            }
        }

        //進行加密
        $encodeStr .= 'VerficationCode=[' . $this->privateKey . ']';

        //沒有signValue就要丟例外，其他的參數在組織加密串的時候驗證過了
        if (!isset($this->options['signValue'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['signValue'] != md5($encodeStr)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['respCode'] != '0000') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['merOrderNum'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['tranAmt'] != $entry['amount']) {
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
        $encodeStr = '';

        //加密設定
        foreach ($this->encodeParams as $index) {
            $encodeStr .= $index . '=[' . $this->requestData[$index] . ']';
        }

        $encodeStr .= 'VerficationCode=[' . $this->privateKey . ']';

        return md5($encodeStr);
    }
}

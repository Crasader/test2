<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 支付衛士二代
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
class WeishihII extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'userID'  => '', //接口ID
        'orderId' => '', //訂單編號
        'amt'     => '', //訂單金額
        'url'     => '', //支付成功的接收地扯
        'bank'    => '', //銀行編碼
        'name'    => '', //客戶名稱
        'cur'     => 'RMB', //幣種
        'hmac'    => '', //參數加密串
        'userip'  => '', //交易會員的客户端IP地址
        'agent'   => '', // 交易會員的瀏覽器信息
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'userID' => 'number',
        'orderId' => 'orderId',
        'amt' => 'amount',
        'url' => 'notify_url',
        'name' => 'username',
        'userip' => 'ip',
        'bank' => 'paymentVendorId',
        'agent' => 'user_agent',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'userID',
        'orderId',
        'amt'
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'userID' => 1,
        'orderId' => 1,
        'amt' => 1,
        'succ' => 1
    ];

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        1   => 'R', //工商銀行
        2   => 'F', //交通銀行
        3   => 'U', //農業銀行
        4   => 'T', //建設銀行
        5   => 'P', //招商銀行
        6   => 'G', //民生銀行
        7   => 'K', //深圳發展銀行
        8   => 'I', //浦發銀行
        9   => 'A', //北京銀行
        10  => 'N', //興業銀行
        11  => 'W', //中信銀行
        12  => 'S', //光大銀行
        13  => 'E', //華夏銀行
        14  => 'B', //廣發銀行
        15  => 'H', //平安銀行
        16  => 'O', //中國郵政儲蓄銀行
        17  => 'V', //中國銀行
        19  => 'Z', //上海銀行
        217 => 'AC', //渤海銀行
        220 => 'AE', //杭州銀行
        221 => 'Y', //浙商銀行
        222 => 'AA', //寧波銀行
        223 => 'AB', //東亞銀行
        226 => 'AD', //南京銀行
        227 => 'C', //廣州市農村信用合作社
        228 => 'J', //上海農村商業銀行
        231 => 'M', //順德農信社
        234 => 'X', //北京農村商業銀行
        278 => 'R', // 銀聯在線
        1090 => '00', // 微信_二維
        1092 => '04', // 支付寶_二維
        1097 => '00', // 微信_手機支付
        1098 => '04', // 支付寶_手機支付
        1111 => '06', // 銀聯錢包_二維
    ];

    /**
     * 出款時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $withdrawRequestData = [
        'customerID'  => '', // 出款对象会员身份ID，帶入username
        'orderID' => '', // 訂單號
        'amt' => '', // 金額，整數
        'bankCardNum' => '', // 銀行卡號
        'MerchantNum' => '', // 商號
        'userName' => '', // 銀行卡帳戶名
        'bankAddress' => '', // 銀行卡開戶網點，可空
        'province' => '', // 銀行卡開戶區域，可空
        'city' => '', // 銀行卡開戶城市
        'reviewedStatue' => '1', // 下發信息審核狀態：1已審核, 2未審核，固定值1
        'md5Str' => '', // 簽名
        'interfaceID' => 'J-80001', // 發接口ID，固定值
        'bankLineNum' => '', // 聯行號，可空
        'chineseRemark' => '', // 中文備註，可空
        'EnglishRemark' => '', // 英文備註，可空
        'asynURL' => '', // 異步通知URL，可空
        'bank' => '', // 下發目標銀行，可空
        'payCompayType' => '34', // 支付公司類型:34首易信
        'type' => '0', // 下發目標人群類型：0會員, 1非會員
    ];

    /**
     * 出款時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $withdrawRequireMap = [
        'customerID' => 'username',
        'orderID' => 'orderId',
        'amt' => 'amount',
        'bankCardNum' => 'account',
        'MerchantNum' => 'number',
        'userName' => 'nameReal',
        'bank' => 'bank_info_id',
    ];

    /**
     * 出款支援的銀行對應編號
     *
     * @var array
     */
    protected $withdrawBankMap = [
        1 => '中国工商银行', // 工商銀行
        2 => '交通银行', // 交通銀行
        3 => '中国农业银行', // 農業銀行
        4 => '中国建设银行', // 建設銀行
        5 => '招商银行', // 招商銀行
        6 => '中国民生银行', // 民生銀行
        8 => '上海浦东发展银行', // 浦發銀行
        9 => '北京银行', // 北京銀行
        10 => '兴业银行', // 興業銀行
        11 => '中信银行', // 中信銀行
        12 => '中国光大银行', // 光大銀行
        13 => '华夏银行', // 華夏銀行
        14 => '广发银行', // 廣發銀行
        15 => '平安银行', // 平安銀行
        16 => '邮政储蓄银行', // 中國郵政儲蓄銀行
        17 => '中国银行', // 中國銀行
        19 => '上海银行', // 上海銀行
        217 => '渤海银行', // 渤海銀行
        228 => '农村商业银行', // 上海農村商業銀行
        308 => '徽商银行', // 徽商銀行
    ];

    /**
     * 出款時需要加密的參數
     *
     * @var array
     */
    protected $withdrawEncodeParams = [
        'orderID',
        'MerchantNum',
        'bankCardNum',
        'amt',
        'reviewedStatue',
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
        if (!array_key_exists($this->requestData['bank'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        //額外的參數設定
        $this->requestData['amt'] = round($this->requestData['amt'], 4);
        $this->requestData['bank'] = $this->bankMap[$this->requestData['bank']];

        //設定支付平台需要的加密串
        $this->requestData['hmac'] = $this->encode();

        //取得跳轉網址
        //url: https://cloud1.semanticweb.cn/diy/demo/message.jsp
        $curlParam = [
            'method' => 'GET',
            'uri' => '/diy/demo/message.jsp',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => http_build_query($this->requestData),
            'header' => []
        ];

        $result = $this->curlRequest($curlParam);

        //輸出結果為不成功
        if (strpos($result, "error:") > 0) {
            throw new PaymentConnectionException($result, 180130, $this->getEntryId());
        }

        $getUrl = substr($result, strpos($result, "[") + 1, strpos($result, "]") - 1);

        $this->requestData['act_url'] = $getUrl;

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

        $this->payResultVerify();

        $encodeData = [];

        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options)) {
                $encodeData[] = $this->options[$paymentKey];
            }
        }

        //進行加密
        $encodeData[] = $this->privateKey;
        $encodeStr = md5(implode('&', $encodeData));

        //沒有hmac2就要丟例外，其他的參數在組織加密串的時候驗證過了
        if (!isset($this->options['hmac2'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['hmac2'] != $encodeStr) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['succ'] != 'Y') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['orderId'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['amt'] != $entry['amount']) {
            throw new PaymentException('Order Amount error', 180058);
        }
    }

    /**
     * 線上出款
     */
    public function withdrawPayment()
    {
        // 驗證私鑰
        $this->verifyPrivateKey();

        $this->withdrawVerify();

        // 從內部給定值到參數
        foreach ($this->withdrawRequireMap as $paymentKey => $internalKey) {
            $this->withdrawRequestData[$paymentKey] = $this->options[$internalKey];
        }

        $withdrawHost = trim($this->options['withdraw_host']);

        // 額外的參數設定
        $this->withdrawRequestData['bank'] = $this->withdrawBankMap[$this->withdrawRequestData['bank']];

        // 金額必須為整數, 小數點不為0丟例外
        if (round($this->withdrawRequestData['amt']) != $this->withdrawRequestData['amt']) {
            throw new PaymentException('Amount must be an integer', 150180193);
        }
        $this->withdrawRequestData['amt'] = round($this->withdrawRequestData['amt']);

        // 設定出款需要的加密串
        $this->withdrawRequestData['md5Str'] = $this->withdrawEncode();

        // 出款
        $curlParam = [
            'method' => 'POST',
            'uri' => '/diy/everySelect/storedProcess/outPayAmtData.jsp',
            'ip' => $this->options['verify_ip'],
            'host' => $withdrawHost,
            'param' => http_build_query($this->withdrawRequestData),
            'header' => ['Port' => '8080'],
            'timeout' => 60,
        ];

        $result = $this->curlRequest($curlParam);

        if ($result !== 'ok') {
            throw new PaymentConnectionException($result, 180124, $this->getEntryId());
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

        //加密設定
        foreach ($this->encodeParams as $index) {
            $encodeData[] = $this->requestData[$index];
        }

        //額外的加密設定
        $encodeData[] = $this->privateKey;
        $encodeStr = implode('&', $encodeData);

        return md5($encodeStr);
    }

    /**
     * 出款時的加密
     *
     * @return string
     */
    protected function withdrawEncode()
    {
        $encodeData = [];

        // 加密設定
        foreach ($this->withdrawEncodeParams as $index) {
            $encodeData[] = $this->withdrawRequestData[$index];
        }

        // 額外的加密設定
        $encodeData[] = $this->privateKey;
        $encodeStr = implode('&', $encodeData);

        return sha1($encodeStr);
    }
}

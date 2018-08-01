<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

class SuNing extends PaymentBase
{
    /**
     * 出款時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $withdrawRequestData = [
        'merchantNo' => '', // 商號
        'publicKeyIndex' => '', // 公鑰索引值
        'Signature' => '', // 簽名
        'signAlgorithm' => 'RSA', // 簽名算法，目前固定RSA
        'inputCharset' => 'UTF-8', // 字串編碼，固定UTF-8
        'body' => [], // 業務參數，必填
    ];

    /**
     * 出款時要傳給平台的業務參數
     *
     * @var array
     */
    private $body = [
        'batchNo' => '', // 批次號
        'merchantNo' => '', // 付款方商戶號，與批次號組合應唯一
        'productCode' => '', // 產品編碼
        'totalNum' => 1, // 批次付款總比數，至少一筆
        'totalAmount' => '', // 付款總金額，單位:分
        'currency' => 'CNY', // 幣種編碼，固定值CNY
        'payDate' => '', // 支付時間，格式:Ymd
        'detailData' => [], // 付款詳細數據，必填
        'notifyUrl' => '', // 通知URL
        'goodsType' => '', // 商品類型編碼，必填
    ];

    /**
     * 出款時要傳給平台的付款詳細數據參數
     *
     * @var array
     */
    private $detailData = [
        'serialNo' => '', // 流水號
        'receiverCardNo' => '', // 收款方卡號
        'receiverName' => '', // 收款方姓名
        'receiverType' => 'PERSON', // 收款方類型，PERSON：個人，CORP：企業
        'bankName' => '', // 開戶行名稱
        'bankCode' => '', // 開戶行編號
        'amount' => '', // 付款金額，單位分
    ];

    /**
     * 出款時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $withdrawRequireMap = [
        'merchantNo' => 'number',
        'batchNo' => 'orderId',
        'merchantNo' => 'number',
        'totalAmount' => 'amount',
        'payDate' => 'orderCreateDate',
        'serialNo' => 'orderId',
        'receiverCardNo' => 'account',
        'receiverName' => 'nameReal',
        'bankName' => 'bank_name',
        'bankCode' => 'bank_info_id',
        'amount' => 'amount',
        'notifyUrl' => 'shop_url',
    ];

    /**
     * 出款支援的銀行對應編號
     *
     * @var array
     */
    protected $withdrawBankMap = [
        1 => 'ICBC', // 工商銀行
        2 => 'COMM', // 交通銀行
        3 => 'ABC', // 農業銀行
        4 => 'CCB', // 建設銀行
        5 => 'CMB', // 招商銀行
        6 => 'CMBC', // 民生銀行總行
        8 => 'SPDB', // 上海浦東發展銀行
        9 => 'BJB', // 北京銀行
        10 => 'CIB', // 興業銀行
        11 => 'CITIC', // 中信銀行
        12 => 'CEB', // 光大銀行
        13 => 'HXB', // 華夏銀行
        14 => 'CGB', // 廣東發展銀行
        15 => 'PAB', // 平安銀行
        16 => 'PSBC', // 中國郵政
        17 => 'BOC', // 中國銀行
        19 => 'BOSH', // 上海銀行
        217 => 'CBHB', // 渤海銀行
        218 => 'HKBEA', // 東莞銀行
        219 => 'GZB', // 廣州銀行
        220 => 'HZCB', // 杭州銀行
        221 => 'CZB', // 浙商银行
        222 => 'NBCB', // 寧波銀行
        224 => 'WZCB', // 溫州銀行
        225 => 'BOJS', // 晉商銀行
        226 => 'NJCB', // 南京銀行
        227 => 'GRCB', // 廣州農商銀行
        228 => 'SRCB', // 上海市農村商業銀行
        229 => 'HKB', // 漢口銀行
        233 => 'CZCB', // 浙江稠州商業銀行
        234 => 'BJRCB', // 北京農商行
        276 => 'BODG', // 東莞農村商業銀行
        280 => 'ZGCCB', // 自貢市商業銀行
        302 => 'GYGB', // 貴陽銀行
        303 => 'LJB', // 龍江銀行
        305 => 'GLB', // 桂林銀行
        306 => 'BOJZ', // 錦州銀行
        307 => 'DLB', // 大連銀行
        308 => 'HSB', // 徽商銀行
        309 => 'JSBC', // 江蘇銀行
        310 => 'BOIM', // 內蒙古銀行
        311 => 'EGB', // 恒豐銀行
        312 => 'BOCD1', // 成都銀行
        315 => 'HEBB', // 河北銀行
        316 => 'BOLZ', // 柳州銀行
        319 => 'ARCU', // 安徽農村信用社
        321 => 'TCCB', // 天津銀行
        322 => 'BOCZ', // 滄州銀行
        324 => 'BOCD', // 承德銀行
        325 => 'ZJCCB', // 張家口市商業銀行
        327 => 'XTB', // 邢台銀行
        328 => 'LFB', // 廊坊銀行
        330 => 'BOHD', // 邯鄲銀行
        334 => 'JCB', // 晉城銀行
        337 => 'BSB', // 包商銀行
        339 => 'ORDOSB', // 鄂爾多斯銀行
        340 => 'SJB', // 盛京銀行
        341 => 'BOAS', // 鞍山銀行
        345 => 'BOYK', // 營口銀行
        346 => 'BOFX', // 阜新銀行
        350 => 'BOHL', // 葫蘆島銀行
        352 => 'BOJL', // 吉林銀行
        353 => 'HRBCB', // 哈爾濱銀行
        355 => 'BOSZ', // 蘇州銀行
        356 => 'JXB', // 嘉興銀行
        357 => 'BOHZ', // 湖州銀行
        358 => 'BOSX', // 紹興銀行
        360 => 'TZB', // 台州商行
        361 => 'ZJTLCB', // 浙江泰隆商業銀行
        362 => 'MTCB', // 浙江民泰商業銀行
        363 => 'FHB', // 福建海峽銀行
        364 => 'NCB', // 南昌銀行
        365 => 'GZBANK', // 贛州銀行
        366 => 'SRB', // 上饒銀行
        368 => 'QLB', // 齊魯銀行
        369 => 'QSB', // 齊商銀行
        370 => 'YTB', // 煙台銀行
        371 => 'BOWF', // 潍坊银行
        372 => 'BOLS', // 臨商銀行
        373 => 'WHCCB', // 威海市商業銀行
        374 => 'BORZ', // 日照銀行
        375 => 'DZB', // 德州銀行
        376 => 'LSB', // 萊商銀行
        377 => 'DYCCB', // 東營銀行
        378 => 'BOJN', // 濟寧銀行
        379 => 'TACCB', // 泰安市商業銀行
        381 => 'BOZZ', // 鄭州銀行
        382 => 'BOLY', // 洛陽銀行
        384 => 'PDSB', // 平頂山銀行
        386 => 'CSCB', // 長沙銀行
        388 => 'CRBZ', // 珠海滑潤銀行股份有限公司
        389 => 'GNB', // 廣東南粵銀行股份有限公司
        390 => 'GDHXB', // 廣東華興銀行股份有限公司
        391 => 'GBGB', // 廣西北部灣銀行
        392 => 'CQCB', // 重慶銀行
        395 => 'PCCB', // 攀枝花市商業銀行
        398 => 'NCCCB', // 南充市商業銀行
        399 => 'BODY', // 德陽銀行
        401 => 'MYCCB', // 綿陽市商業銀行
        406 => 'FDB', // 富滇銀行
        409 => 'CAB', // 長安銀行
        411 => 'LZYH', // 蘭州銀行
        412 => 'BOQH', // 青海銀行
        413 => 'BONX', // 寧夏銀行
        415 => 'UCCB', // 烏魯木齊市商業銀行
        416 => 'BOK', // 昆侖銀行
        422 => 'XMCCB', // 廈門銀行
        424 => 'QDCCB', // 青島銀行
    ];

    /**
     * 線上出款
     */
    public function withdrawPayment()
    {
        $this->withdrawVerify();

        // 從內部給定值到參數
        foreach ($this->withdrawRequireMap as $paymentKey => $internalKey) {
            if (isset($this->withdrawRequestData[$paymentKey])) {
                $this->withdrawRequestData[$paymentKey] = $this->options[$internalKey];
            }

            if (isset($this->body[$paymentKey])) {
                $this->body[$paymentKey] = $this->options[$internalKey];
            }

            if (isset($this->detailData[$paymentKey])) {
                $this->detailData[$paymentKey] = $this->options[$internalKey];
            }
        }

        $withdrawHost = trim($this->options['withdraw_host']);

        // 驗證出款時支付平台對外設定
        if ($withdrawHost == '') {
            throw new PaymentException('No withdraw_host specified', 150180194);
        }

        // 帶入未支援的銀行就噴例外
        if (!array_key_exists($this->detailData['bankCode'], $this->withdrawBankMap)) {
            throw new PaymentException('BankInfo is not supported by PaymentGateway', 150180195);
        }

        // 設定返回網址
        $this->body['notifyUrl'] .= 'withdraw_return.php';

        // 額外參數設定
        $createAt = new \Datetime($this->body['payDate']);
        $this->body['payDate'] = $createAt->format('Ymd');
        $this->body['totalAmount'] = round($this->detailData['amount'] * 100);
        $this->detailData['amount'] = strval($this->body['totalAmount']);
        $this->detailData['bankCode'] = $this->withdrawBankMap[$this->detailData['bankCode']];

        // 取得商家附加設定值
        $merchantExtraValues = $this->getMerchantExtraValue(['publicKeyIndex', 'productCode', 'goodsType']);
        $this->withdrawRequestData['publicKeyIndex'] = $merchantExtraValues['publicKeyIndex'];
        $this->body['productCode'] = $merchantExtraValues['productCode'];
        $this->body['goodsType'] = $merchantExtraValues['goodsType'];

        // 組織提交參數
        $this->body['detailData'] = json_encode([$this->detailData]);
        $this->withdrawRequestData['body'] = json_encode([$this->body]);

        // 設定加密簽名
        $this->withdrawRequestData['Signature'] = $this->withdrawEncode();

        // 出款
        $curlParam = [
            'method' => 'POST',
            'uri' => '/epps-wag/withdraw.htm',
            'ip' => $this->options['verify_ip'],
            'host' => $withdrawHost,
            'param' => http_build_query($this->withdrawRequestData),
            'header' => ['Content-Type' => 'application/x-www-form-urlencoded'],
            'timeout' => 60,
        ];

        $result = $this->curlRequest($curlParam);
        $parseData = json_decode($result, true);

        // 失敗時的key
        $failKey = sprintf('%s_%s', $this->options['orderId'], $this->options['number']);

        if (isset($parseData[$failKey])) {
            $parseData = $parseData[$failKey];
        }

        // 對返回結果做檢查
        if (!isset($parseData['responseCode'])) {
            throw new PaymentException('No withdraw return parameter specified', 150180209);
        }

        if ($parseData['responseCode'] !== '0000') {
            if (isset($parseData['responseMsg'])) {
                throw new PaymentConnectionException($parseData['responseMsg'], 180124, $this->getEntryId());
            }

            throw new PaymentConnectionException('Withdraw error', 180124, $this->getEntryId());
        }
    }

    /**
     * 出款時的加密
     *
     * @return string
     */
    protected function withdrawEncode()
    {
        $encodeData = [];

        foreach (array_keys($this->withdrawRequestData) as $key) {
            if ($key !== 'Signature' && $key !== 'signAlgorithm' && trim($this->withdrawRequestData[$key]) !== '') {
                $encodeData[$key] = $this->withdrawRequestData[$key];
            }
        }

        ksort($encodeData);
        $encodeStr = strtoupper(MD5(urldecode(http_build_query($encodeData))));

        $sign = '';
        if (!openssl_sign($encodeStr, $sign, $this->getRsaPrivateKey())) {
            throw new PaymentException('Generate signature failure', 180144);
        }

        return base64_encode($sign);
    }
}
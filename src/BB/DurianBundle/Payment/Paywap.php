<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 旺實富
 */
class Paywap extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'p1_usercode' => '', // 商戶號
        'p2_order' => '', // 訂單號
        'p3_money' => '', // 金額，精確到分
        'p4_returnurl' => '', // 跳轉網址
        'p5_notifyurl' => '', // 異步通知地址
        'p6_ordertime' => '', // 訂單時間
        'p7_sign' => '', // 加密串
        'p8_signtype' => '1', // 1、MD5
        'p9_paymethod' => '1', // 商户支付方式1、网银 ，2、快捷支付，3、微信，4、支付宝，5、卡類
        'p10_paychannelnum' => '', // 支付通道編碼
        'p11_cardtype' => '11', // 銀行卡類型 11:貸記卡
        'p12_channel' => 'B2C', // 銀行支付類型 B2C:個人網銀
        'p13_orderfailertime' => '', // 訂單失效時間 可空
        'p14_customname' => '', // 公司名稱，統一帶入username
        'p15_customcontacttype' => '', // 客戶聯繫方式 可空
        'p16_customcontact' => '', // 客戶聯繫方式 可空
        'p17_customip' => '', // 客戶IP地址，规定以192_168_0_253格式
        'p18_product' => '', // 商品名稱，可空
        'p19_productcat' => '', // 商品種類，可空
        'p20_productnum' => '', // 商品數量，預設為0，可空
        'p21_pdesc' => '', // 商品描述，可空
        'p22_version' => '2.0', // 接口版本
        'p23_charset' => 'UTF-8', // 1、UTF-8
        'p24_remark' => '', // 備註
        'p25_terminal' => '1', // 終端設備 1、pc 2、ios 3、安卓 4、windowsphone
        'p26_iswappay' => '1', // wap支付方式  1、普通pc 2、wap内嵌 3、wap瀏覽器
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'p1_usercode' => 'number',
        'p2_order' => 'orderId',
        'p3_money' => 'amount',
        'p4_returnurl' => 'notify_url',
        'p5_notifyurl' => 'notify_url',
        'p6_ordertime' => 'orderCreateDate',
        'p10_paychannelnum' => 'paymentVendorId',
        'p14_customname' => 'username',
        'p17_customip' => 'ip',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'p1_usercode',
        'p2_order',
        'p3_money',
        'p4_returnurl',
        'p5_notifyurl',
        'p6_ordertime',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'p1_usercode' => 1,
        'p2_order' => 1,
        'p3_money' => 1,
        'p4_status' => 1,
        'p5_payorder' => 1,
        'p6_paymethod' => 1,
        'p7_paychannelnum' => 0,
        'p8_charset' => 1,
        'p9_signtype' => 1,
    ];

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        '1' => 'ICBC', // 工商銀行
        '2' => 'COMM', // 交通銀行
        '3' => 'ABC', // 農業銀行
        '4' => 'CCB', // 建設銀行
        '5' => 'CMB', // 招商銀行
        '6' => 'CMBC', // 民生銀行
        '8' => 'SPDB', // 浦發銀行
        '9' => 'BCCB', // 北京銀行
        '10' => 'CIB', // 興業銀行
        '11' => 'ECITIC', // 中信銀行
        '12' => 'CEB', // 光大銀行
        '13' => 'HXB', // 華夏銀行
        '14' => 'GDB', // 廣發銀行
        '15' => 'PINGAN', // 平安銀行
        '16' => 'PSBC', // 郵政儲蓄銀行
        '17' => 'CSH', // 中國銀行
        '19' => 'BOS', // 上海銀行
        '217' => 'CBHB', // 渤海銀行
        '219' => 'BOGH', // 廣州銀行
        '221' => 'CZB', // 浙商銀行
        '222' => 'NBBABK', // 寧波銀行
        '223' => 'BEA', // 東亞銀行
        '224' => 'BOWZ', // 溫州銀行
        '226' => 'NJCB', // 南京銀行
        '228' => 'SRCB', // 上海農商銀行
        '1001' => 'UNICOM', // 聯通充值卡
        '1002' => 'TELECOM', // 中國電信充值卡
        '1073' => 'JUNNET', // 駿網一卡通
        '1074' => 'SNDACARD', // 盛大互動誤樂卡
        '1075' => 'ZHENGTU', // 征途游戲卡
        '1076' => 'QQCARD', // Q幣卡
        '1077' => 'JIUYOU', // 久游一卡通
        '1078' => 'NETEASE', // 網易一卡通
        '1079' => 'WANMEI', // 完美一卡通
        '1080' => 'SOHU', // 搜狐一卡通
        '1081' => 'ZONGYOU', // 縱游一卡通
        '1082' => 'TIANXIA', // 天下一卡通
        '1083' => 'TIANHONG', // 天宏一卡通
        '1090' => '', // 微信
        '1092' => '', // 支付寶
    ];

    /**
     * 卡類支援的銀行對應編號
     *
     * @var array
     */
    private $cardId = [
        '1001', // 聯通充值卡
        '1002', // 中國電信充值卡
        '1073', // 駿網一卡通
        '1074', // 盛大互動誤樂卡
        '1075', // 征途游戲卡
        '1076', // Q幣卡
        '1077', // 久游一卡通
        '1078', // 網易一卡通
        '1079', // 完美一卡通
        '1080', // 搜狐一卡通
        '1081', // 縱游一卡通
        '1082', // 天下一卡通
        '1083', // 天宏一卡通
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
        if (!array_key_exists($this->requestData['p10_paychannelnum'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 支付方式選擇微信二維
        if ($this->requestData['p10_paychannelnum'] == '1090') {
            $this->requestData['p9_paymethod'] = '3';
        }

        // 支付方式選擇支付寶二維
        if ($this->requestData['p10_paychannelnum'] == '1092') {
            $this->requestData['p9_paymethod'] = '4';
        }

        // 支付方式選擇卡類
        if (in_array($this->requestData['p10_paychannelnum'], $this->cardId)) {
            $this->requestData['p11_cardtype'] = '';
            $this->requestData['p12_channel'] = '';
            $this->requestData['p9_paymethod'] = '5';
        }

        // 額外的參數設定
        $date = new \DateTime($this->requestData['p6_ordertime']);
        $this->requestData['p6_ordertime'] = $date->format('YmdHis');
        $this->requestData['p3_money'] = sprintf('%.2f', $this->requestData['p3_money']);
        $this->requestData['p10_paychannelnum'] = $this->bankMap[$this->requestData['p10_paychannelnum']];

        // 須將 ip 中的 . 改為 _
        $this->requestData['p17_customip'] = str_replace('.', '_', $this->requestData['p17_customip']);

        // 設定支付平台需要的加密串
        $this->requestData['p7_sign'] = $this->encode();

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

        // 加密設定
        foreach (array_keys($this->decodeParams) as $paymentKey) {
            // 如果返回值為空補回空字串
            if (!isset($this->options[$paymentKey])) {
                $this->options[$paymentKey] = '';
            }

            $encodeData[] = $this->options[$paymentKey];
        }

        // 額外的加密設定
        $encodeData[] = $this->privateKey;
        $encodeStr = implode('&', $encodeData);

        // 沒有p10_sign就要丟例外，其他的參數在組織加密串的時候驗證過了
        if (!isset($this->options['p10_sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['p10_sign'] != strtoupper(md5($encodeStr))) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['p4_status'] != '1') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['p2_order'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['p3_money'] != $entry['amount']) {
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
            $encodeData[] = $this->requestData[$index];
        }

        // 額外的加密設定
        $encodeStr = implode('&', $encodeData);
        $encodeStr .= $this->privateKey;

        return strtoupper(md5($encodeStr));
    }
}

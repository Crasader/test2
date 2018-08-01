<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 99寶付
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
class BaoFoo99 extends PaymentBase
{
    /**
     * 商戶數據包
     *
     * @var array
     */
    private $merchantData = [
        'MERORDERID' => '', //訂單號
        'MERDATE' => '', //訂單時間
        'AMOUNT' => '', //訂單金額
        'CURTYPE' => '100', //幣種, 100: 人民幣
        'TRADEWAY' => '01', //支付方式, 1: 人民幣借記卡
        'RECVENCTYPE' => '01', //返回加密方式
        'S2SURL' => '', //交易返回url
        'FAILEDURL' => '', //失敗提示url
        'SUCCESSURL' => '', //成功提示url
        'LANG' => 'GB', //GB: 中文
        'SHOWAMOUNT' => '', //顯示金額
        'REMARK' => '' //備註
    ];

    /**
     * 支付時商戶數據包與內部參數的對應
     *
     * @var array
     */
    private $merchantDataMap = [
        'MERORDERID' => 'orderId',
        'MERDATE' => 'orderCreateDate',
        'AMOUNT' => 'amount',
        'S2SURL' => 'notify_url',
        'REMARK' => 'username'
    ];

    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'MerID'      => '', //商戶號
        'APIVersion' => '01', //加密方式
        'MerReqData' => '', //商戶數據包
        'BankID'     => '', //銀行代碼
        'MerSign'    => '' //加密簽名
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'MerID' => 'number',
        'BankID' => 'paymentVendorId'
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'MerID',
        'APIVersion'
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'MerID' => 1, //商號
        'APIVersion' => 1 //返回的加密方式
    ];

    /**
     * 應答機制訊息
     *
     * @var string
     */
    protected $msg = '[Succeed]';

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        '1'  => '330002', //中國工商銀行
        '2'  => '330015', //交通銀行
        '3'  => '330005', //中國農業銀行
        '4'  => '330003', //中國建設銀行
        '5'  => '330001', //招商銀行
        '6'  => '330010', //中國民生銀行
        '7'  => '330006', //深圳發展銀行
        '8'  => '330004', //上海浦東發展銀行
        '9'  => '330008', //北京銀行
        '10' => '330007', //興業銀行
        '11' => '330011', //中信銀行
        '12' => '330009', //中國光大銀行
        '14' => '330012', //廣東發展銀行
        '17' => '330014' //中國銀行
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

        // 驗證必填的項目及設定商戶數據包參數
        foreach ($this->merchantDataMap as $paymentKey => $internalKey) {
            if (!array_key_exists($internalKey, $this->options) || trim($this->options[$internalKey]) === '') {
                throw new PaymentException('No pay parameter specified', 180145);
            }

            $this->merchantData[$paymentKey] = $this->options[$internalKey];
        }

        //帶入未支援的銀行就噴例外
        if (!array_key_exists($this->requestData['BankID'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        //額外的參數設定
        $date = new \DateTime($this->merchantData['MERDATE']);
        $this->merchantData['MERDATE'] = $date->format('Ymd');
        $this->merchantData['AMOUNT'] = round($this->merchantData['AMOUNT'] * 100);
        $this->requestData['BankID'] = $this->bankMap[$this->requestData['BankID']];

        //設定支付平台需要的加密串
        $data = $this->encode();
        $this->requestData['MerReqData'] = $data['MerReqData'];
        $this->requestData['MerSign'] = $data['MerSign'];

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

        //解密商戶數據並切割成陣列
        $data = base64_decode(urldecode($this->options['MerReqData']));
        $merchantDataValue = explode('|', $data);

        //商戶數據包的參數名稱及順序
        $merchantDataKey = [
            'ORDERID',
            'MERORDERID',
            'MERDATE',
            'BANKBILLNO',
            'BANKTIME',
            'AMOUNT',
            'CURTYPE',
            'LANG',
            'ISSUCC',
            'REMARK'
        ];

        //這邊有驗證數量，因此不會有不存在index的問題，所以這些不用再驗證
        if (count($merchantDataValue) != count($merchantDataKey)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        //組合成商戶數據
        $merchantData = array_combine($merchantDataKey, $merchantDataValue);

        $this->payResultVerify();

        $encodeData = [];

        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options)) {
                $encodeData[] = $this->options[$paymentKey];
            }
        }

        //進行加密
        $encodeData[] = $data;
        $encodeData[] = $this->privateKey;
        $encodeStr = md5(implode('|', $encodeData));

        //沒有MerSign就要丟例外，其他的參數在組織加密串的時候驗證過了
        if (!isset($this->options['MerSign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if (urldecode($this->options['MerSign']) != $encodeStr) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($merchantData['ISSUCC'] != 'Y') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($merchantData['MERORDERID'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($merchantData['AMOUNT'] != round($entry['amount'] * 100)) {
            throw new PaymentException('Order Amount error', 180058);
        }
    }

    /**
     * 支付時的加密
     *
     * @return array
     */
    protected function encode()
    {
        //訂單數據設定
        $this->merchantData['SHOWAMOUNT'] = base64_encode($this->merchantData['SHOWAMOUNT']);
        $this->merchantData['REMARK'] = base64_encode($this->merchantData['REMARK']);

        $orderInfo = implode('|', $this->merchantData);

        //加密設定
        $encodeData = [];

        foreach ($this->encodeParams as $index) {
            $encodeData[] = $this->requestData[$index];
        }

        //額外的加密設定
        $encodeData[] = $orderInfo;
        $encodeData[] = $this->privateKey;
        $encodeStr = implode('|', $encodeData);

        return [
            'MerReqData' => base64_encode($orderInfo),
            'MerSign' => md5($encodeStr)
        ];
    }
}

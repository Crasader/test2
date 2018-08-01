<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 貝富支付
 */
class BeiFuPay extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'p_name' => '', // 帳戶名稱
        'p_type' => 'WAY_TYPE_BANK', // 通道類型，網銀:WAY_TYPE_BANK
        'p_oid' => '', // 訂單號
        'p_money' => '', // 金額，保留小數2位
        'p_bank' => '',  // 銀行卡類型，網銀必填
        'p_url' => '', // 回調地址
        'P_surl' => '', // 成功地址
        'p_remarks' => '', // 備註
        'p_syspwd' => '', // 管理密碼
        'uname' => '', // 帳戶名稱
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'p_name' => 'number',
        'p_oid' => 'orderId',
        'p_money' => 'amount',
        'p_bank' => 'paymentVendorId',
        'p_url' => 'notify_url',
        'P_surl' => 'notify_url',
        'uname' => 'number',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'p_name',
        'p_type',
        'p_oid',
        'p_money',
        'p_bank',
        'p_url',
        'p_remarks',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'p_oid' => 1,
        'p_money' => 1,
    ];

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        '1' => '10001', // 工商銀行
        '2' => '10008', // 交通銀行
        '3' => '10002', // 農業銀行
        '4' => '10005', // 建設銀行
        '5' => '10003', // 招商銀行
        '6' => '10006', // 民生銀行
        '8' => '10015', // 上海浦東發展銀行
        '9' => '10013', // 北京銀行
        '10' => '10009', // 興業銀行
        '11' => '10007', // 中信銀行
        '12' => '10010', // 光大銀行
        '13' => '10025', // 華夏銀行
        '14' => '10016', // 廣東發展銀行
        '15' => '10014', // 平安銀行
        '16' => '10012', // 中國郵政
        '17' => '10004', // 中國銀行
        '19' => '10023', // 上海銀行
        '217' => '10017', // 渤海銀行
        '220' => '10027', // 杭州銀行
        '221' => '10022', // 浙商银行
        '222' => '10019', // 寧波銀行
        '223' => '10018', // 東亞銀行
        '226' => '10021', // 南京銀行
        '228' => '10024', // 上海市農村商業銀行
        '233' => '10028', // 浙江稠州商業銀行
        '234' => '10020', // 北京農村商業銀行
        '1090' => 'WAY_TYPE_WEBCAT', // 微信_二維
        '1103' => 'WAY_TYPE_QQ', // QQ_二維
        '1104' => 'WAY_TYPE_QQ_PHONE', // QQ_手機支付
    ];

    /**
     * 回傳支付時要給支付平台驗證的資料
     *
     * @return array
     */
    public function getVerifyData()
    {
        $this->verifyPrivateKey();

        $this->payVerify();

        // 從內部給定值到參數
        foreach ($this->requireMap as $paymentKey => $internalKey) {
            $this->requestData[$paymentKey] = $this->options[$internalKey];
        }

        // 檢查銀行代碼是否支援
        if (!array_key_exists($this->requestData['p_bank'], $this->bankMap)) {
            throw new PaymentException('PaymentVendor is not supported by PaymentGateway', 180066);
        }

        // 額外的參數設定
        $this->requestData['p_money'] = sprintf('%.2f', $this->requestData['p_money']);
        $this->requestData['p_bank'] = $this->bankMap[$this->requestData['p_bank']];

        // 二維支付、手機支付須調整參數
        if (in_array($this->options['paymentVendorId'], [1090, 1103, 1104])) {
            $this->requestData['p_type'] = $this->requestData['p_bank'];
            unset($this->requestData['p_bank']);
        }

        // 設定支付平台需要的加密串
        $this->requestData['params'] = $this->encode();

        $requestParams = [
            'params' => $this->requestData['params'],
            'uname' => $this->requestData['uname'],
        ];

        $postUrl = $this->options['postUrl'] . '?' . http_build_query($requestParams);

        return [
            'post_url' => $postUrl,
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
        $this->verifyPrivateKey();

        $this->payResultVerify();

        // 未返回商號因此補回商號
        $encodeData = ['p_name' => $entry['merchant_number']];

        // 組織加密串
        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options)) {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        // 取商家額外的參數設定
        $extra = $this->getMerchantExtraValue(['pSyspwd']);
        $encodeData['p_syspwd'] = $extra['pSyspwd'];

        $encodeStr = implode($encodeData);
        $encodeStr .= $this->privateKey;

        if (!isset($this->options['p_md5'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['p_md5'] != strtolower(md5($encodeStr))) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['p_code'] != '1') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['p_oid'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['p_money'] != $entry['amount']) {
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

        foreach ($this->encodeParams as $index) {
            if (isset($this->requestData[$index])) {
                $encodeStr .= $index . '=' . $this->requestData[$index] . '!';
            }
        }

        // 商家額外的參數設定
        $names = ['desKey', 'pSyspwd'];
        $extra = $this->getMerchantExtraValue($names);
        $pSyspwd = $extra['pSyspwd'];
        $desKey = $extra['desKey'];

        $encodeStr .= 'p_syspwd=' . md5($pSyspwd . $this->privateKey);

        return $this->encrypt($desKey, $encodeStr);
    }
}

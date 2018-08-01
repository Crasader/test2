<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * Wpay
 */
class Wpay extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'paytype' => '', // 支付方式
        'out_trade_no' => '', // 商戶訂單號
        'body' => '', // 商品描述
        'attach' => '', // 附加訊息，網銀支付輸入通道編碼可直達
        'total_fee' => '', // 總金額，單位:分
        'create_ip' => '', // 終端IP
        'time_start' => '', // 訂單生成時間，格式:YmdHis
        'time_expire' => '', // 訂單超時時間，格式:YmdHis
        'cid' => '', // 商戶
        'return_url' => '', // 同步通知網址
        'notify_url' => '', // 異步通知網址
        'sign' => '', // 簽名
        'isfast' => '1', // 定向方式，1為直接快速定向
        'isqrcode' => '0', // 返回數據方式
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'paytype' => 'paymentVendorId',
        'out_trade_no' => 'orderId',
        'body' => 'orderId',
        'total_fee' => 'amount',
        'create_ip' => 'ip',
        'time_start' => 'orderCreateDate',
        'cid' => 'number',
        'return_url' => 'notify_url',
        'notify_url' => 'notify_url',
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'paytype',
        'out_trade_no',
        'body',
        'attach',
        'total_fee',
        'create_ip',
        'time_start',
        'time_expire',
        'cid',
        'return_url',
        'notify_url',
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'bank_type' => 1,
        'cid' => 1,
        'orderid' => 1,
        'out_trade_no' => 1,
        'out_transaction_id' => 1,
        'paytype' => 1,
        'result_code' => 1,
        'status' => 1,
        'total_fee' => 1,
        'transaction_id' => 1,
    ];

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        '1088' => 'HPAY_YLWAP_', // 銀聯在線_手機支付
        '1090' => 'wx', // 微信_二維
        '1092' => 'zfb', // 支付寶_二維
        '1100' => 'YDALL_', // 手機收銀台
        '1102' => 'YDALL_', // 網銀收銀台
        '1103' => 'qq', // QQ_二维
        '1104' => 'qq', // QQ_手機支付
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
        $this->requestData['total_fee'] = round($this->requestData['total_fee'] * 100);
        $date = new \DateTime($this->requestData['time_start']);
        $this->requestData['time_start'] = $date->format('YmdHis');
        $this->requestData['paytype'] = $this->bankMap[$this->requestData['paytype']];

        // 銀聯在線手機支付、收銀台需調整提交參數
        if (in_array($this->options['paymentVendorId'], [1088, 1100, 1102])) {
            $this->requestData['attach'] = $this->requestData['paytype'];
            $this->requestData['paytype'] = 'bank';
        }

        // 設定支付平台需要的加密串
        $this->requestData['sign'] = $this->encode();

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

        // 組織加密串
        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $this->options)) {
                $encodeData[$paymentKey] = $this->options[$paymentKey];
            }
        }

        $encodeData['ckey'] = $this->privateKey;
        $encodeStr = urldecode(http_build_query($encodeData));

        // 如果沒有返回簽名要丟例外
        if (!isset($this->options['sign'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['sign'] != md5(strtolower($encodeStr))) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($this->options['result_code'] != '0' || $this->options['status'] != '0') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['out_trade_no'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['total_fee'] != round($entry['amount'] * 100)) {
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
            if (isset($this->requestData[$index])) {
                $encodeData[$index] = $this->requestData[$index];
            }
        }

        ksort($encodeData);
        $encodeData['ckey'] = $this->privateKey;
        $encodeStr = urldecode(http_build_query($encodeData));

        return md5(strtolower($encodeStr));
    }
}

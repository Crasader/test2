<?php

namespace BB\DurianBundle\Payment;

use BB\DurianBundle\Exception\PaymentException;
use BB\DurianBundle\Exception\PaymentConnectionException;

/**
 * 環迅
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
class IPS extends PaymentBase
{
    /**
     * 支付時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $requestData = [
        'Mer_code'        => '', //商號
        'Billno'          => '', //訂單號
        'Amount'          => '', //金額
        'Date'            => '', //訂單日期
        'Currency_Type'   => 'RMB', //幣別
        'Gateway_Type'    => '01', //支付種類 01: 人民幣借記卡
        'Lang'            => 'GB', //語言 GB: 中文
        'Merchanturl'     => '', //支付成功導向url
        'FailUrl'         => '', //支付失敗導向url
        'ErrorUrl'        => '', //支付錯誤導向url
        'Attach'          => '', //商戶數據包
        'OrderEncodeType' => '5', //訂單接口加密方式
        'RetEncodeType'   => '17', //交易返回接口加密方式
        'Rettype'         => '1', //返回方式
        'ServerUrl'       => '', //伺服器返回url
        'SignMD5'         => '', //簽名數據
        'DoCredit'        => '1', //網銀直連選項 1: 使用網銀直連
        'Bankco'          => '' //銀行代碼
    ];

    /**
     * 支付時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $requireMap = [
        'Mer_code' => 'number',
        'Billno' => 'orderId',
        'Amount' => 'amount',
        'Date' => 'orderCreateDate',
        'ServerUrl' => 'notify_url',
        'Attach' => 'username'
    ];

    /**
     * 支付時需要加密的參數
     *
     * @var array
     */
    protected $encodeParams = [
        'Billno',
        'Currency_Type',
        'Amount',
        'Date',
        'OrderEncodeType'
    ];

    /**
     * 支付解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $decodeParams = [
        'billno' => 1, //訂單號
        'Currency_type' => 1, //幣別
        'amount' => 1, //支付金額
        'date' => 1, //支付日期
        'succ' => 1, //支付狀態
        'ipsbillno' => 1 //環迅的訂單號
    ];

    /**
     * 應答機制訊息
     *
     * @var string
     */
    protected $msg = 'ipscheckok';

    /**
     * 支付平台支援的銀行對應編號
     *
     * @var array
     */
    protected $bankMap = [
        '1'    => '00004', //中國工商銀行
        '2'    => '00005', //交通銀行
        '3'    => '00017', //中國農業銀行
        '4'    => '00015', //中國建設銀行
        '5'    => '00021', //招商銀行
        '6'    => '00013', //中國民生銀行
        '7'    => '00023', //深圳發展銀行
        '8'    => '00032', //上海浦東發展銀行
        '9'    => '00050', //北京銀行
        '10'   => '00016', //興業銀行
        '11'   => '00054', //中信銀行
        '12'   => '00057', //中國光大銀行
        '13'   => '00041', //華夏銀行
        '14'   => '00052', //廣東發展銀行
        '15'   => '00087', //深圳平安銀行
        '16'   => '00051', //中國郵政
        '17'   => '00083', //中國銀行
        '19'   => '00084', //上海銀行
        '217'  => '00095', //渤海銀行
        '220'  => '00081', //杭州銀行
        '221'  => '00086', //浙商銀行
        '222'  => '00085', //寧波銀行
        '223'  => '00096', //東亞銀行
        '1000' => '00077', //移動儲值卡
        '1001' => '10016', //聯通儲值卡
        '1002' => '10018' //電信儲值卡
    ];

    /**
     * 查詢時要傳給平台驗證的參數
     *
     * @var array
     */
    protected $trackingRequestData = [
        'MerCode'   => '', //商號
        'Flag'      => '3', //對帳請求標誌 3: 所有交易
        'TradeType' => 'NT', //交易類型(預設值: NT)
        'StartNo'   => '', //查詢起始訂單號
        'EndNo'     => '', //查詢結束訂單號
        'Page'      => '1', //頁數
        'Max'       => '1', //最大筆數
        'Sign'      => '' //簽名數據
    ];

    /**
     * 查詢時支付平台參數與內部參數的對應
     *
     * @var array
     */
    protected $trackingRequireMap = [
        'MerCode' => 'number',
        'StartNo' => 'orderId',
        'EndNo' => 'orderId'
    ];

    /**
     * 查詢時需要加密的參數
     *
     * @var array
     */
    protected $trackingEncodeParams = [
        'MerCode',
        'Flag',
        'TradeType',
        'StartNo',
        'EndNo',
        'Page',
        'Max'
    ];

    /**
     * 查詢解密驗證時需要加密的參數
     *     0: 可不返回的參數
     *     1: 必要返回的參數
     *
     * @var array
     */
    protected $trackingDecodeParams = [
        'OrderNo' => 1,
        'IPSOrderNo' => 1,
        'Trd_Code' => 1,
        'Cr_Code' => 1,
        'Amount' => 1,
        'MerchantOrderTime' => 1,
        'IPSOrderTime' => 1,
        'Flag' => 1
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

        //額外的參數設定
        $date = new \DateTime($this->requestData['Date']);
        $this->requestData['Date'] = $date->format("Ymd");
        $this->requestData['Amount'] = sprintf('%.2f', $this->requestData['Amount']);

        //如果有銀行代碼才能直接連到銀行頁面，沒有就保持空值讓使用者自己選擇
        if (array_key_exists($this->options['paymentVendorId'], $this->bankMap)) {
            $this->requestData['Bankco'] = $this->bankMap[$this->options['paymentVendorId']];
        }

        //設定支付平台需要的加密串
        $this->requestData['SignMD5'] = $this->encode();

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

        $encodeStr = '';

        foreach (array_keys($this->decodeParams) as $paymentKey) {
            if (!array_key_exists($paymentKey, $this->options)) {
                continue;
            }
            //這邊是因為index是Currency_type的時候加密串要是currencytype.$value[Currency_type]
            if ($paymentKey == 'Currency_type') {
                $encodeStr .= 'currencytype' . $this->options[$paymentKey];
            } else {
                $encodeStr .= $paymentKey . $this->options[$paymentKey];
            }
        }

        //RetEncodeType是提交時所提交出去的，讓支付平台知道返回的加密方式是用md5
        $encodeStr .= 'retencodetype' . $this->requestData['RetEncodeType'];
        $encodeStr .= $this->privateKey;

        //如果沒有簽名擋也要丟例外(其他參數都在前面驗證過了，所以不須要驗證)
        if (!isset($this->options['signature'])) {
            throw new PaymentException('No return parameter specified', 180137);
        }

        if ($this->options['signature'] != md5($encodeStr)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        /*
         * succ == '1'是研A原本有判斷的，他們無法確定會不會用到，
         * 因此先加上去，如果之後檢查後沒有用到再拿掉
         */
        if ($this->options['succ'] != 'Y' && $this->options['succ'] != '1') {
            throw new PaymentConnectionException('Payment failure', 180035, $this->getEntryId());
        }

        if ($this->options['billno'] != $entry['id']) {
            throw new PaymentException('Order Id error', 180061);
        }

        if ($this->options['amount'] != $entry['amount']) {
            throw new PaymentException('Order Amount error', 180058);
        }
    }

    /**
     * 訂單查詢
     */
    public function paymentTracking()
    {
        // 驗證私鑰
        $this->verifyPrivateKey();
        $this->trackingVerify();

        // 從內部給定值到參數
        foreach ($this->trackingRequireMap as $paymentKey => $internalKey) {
            $this->trackingRequestData[$paymentKey] = $this->options[$internalKey];
        }
        $this->trackingRequestData['Sign'] = $this->trackingEncode();

        if (trim($this->options['verify_url']) == '') {
            throw new PaymentException('No verify_url specified', 180140);
        }

        $curlParam = [
            'method' => 'POST',
            'uri' => '/Sinopay/Standard/IpsCheckTrade.asmx/GetOrderByNo',
            'ip' => $this->options['verify_ip'],
            'host' => $this->options['verify_url'],
            'param' => http_build_query($this->trackingRequestData),
            'header' => []
        ];

        // 取得訂單查詢結果
        $result = $this->curlRequest($curlParam);
        $parseData = $this->parseData($result);

        // 如果沒有ErrCode要丟例外
        if (!isset($parseData['ErrCode'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        // 身分驗證失敗，商戶不存在
        if ($parseData['ErrCode'] == '1001') {
            throw new PaymentConnectionException(
                'PaymentGateway error, Merchant is not exist',
                180086,
                $this->getEntryId()
            );
        }

        // 商戶憑證不存在
        if ($parseData['ErrCode'] == '1002') {
            throw new PaymentConnectionException(
                'PaymentGateway error, Merchant Certificate is not exist',
                180087,
                $this->getEntryId()
            );
        }

        // 商戶發送的簽名驗證錯誤
        if ($parseData['ErrCode'] == '1003') {
            throw new PaymentConnectionException(
                'PaymentGateway error, Merchant sign error',
                180127,
                $this->getEntryId()
            );
        }

        // 訂單時間格式不合法
        if ($parseData['ErrCode'] == '1004') {
            throw new PaymentConnectionException(
                'PaymentGateway error, Invalid Order date',
                180131,
                $this->getEntryId()
            );
        }

        // 起始時間 > 結束時間
        if ($parseData['ErrCode'] == '1005') {
            throw new PaymentConnectionException(
                'PaymentGateway error, Begin time large than End time',
                180132,
                $this->getEntryId()
            );
        }

        // 沒有輸入時間
        if ($parseData['ErrCode'] == '1006') {
            throw new PaymentConnectionException(
                'PaymentGateway error, No date specified',
                180133,
                $this->getEntryId()
            );
        }

        // 請求服務失敗
        if ($parseData['ErrCode'] == '1007') {
            throw new PaymentConnectionException(
                'Connection error, please try again later or contact customer service',
                180077,
                $this->getEntryId()
            );
        }

        // 沒有滿足條件的單號
        if ($parseData['ErrCode'] == '1008') {
            throw new PaymentConnectionException('Order does not exist', 180060, $this->getEntryId());
        }

        // 訂單號不合法
        if ($parseData['ErrCode'] == '1009') {
            throw new PaymentException('Order Id error', 180061);
        }

        // 起始訂單號 > 結束訂單號
        if ($parseData['ErrCode'] == '1010') {
            throw new PaymentConnectionException(
                'PaymentGateway error, StartNo large than EndNo',
                180134,
                $this->getEntryId()
            );
        }

        // 合約過期
        if ($parseData['ErrCode'] == '2000') {
            throw new PaymentConnectionException(
                'PaymentGateway error, Merchant has been expired',
                180126,
                $this->getEntryId()
            );
        }

        // 0000為查詢成功，防止有其他的錯誤碼，因此設定非0000即為查詢失敗
        if ($parseData['ErrCode'] != '0000') {
            throw new PaymentConnectionException('Payment tracking failed', 180081, $this->getEntryId());
        }

        // 如果沒有$parseData['OrderRecords']['OrderRecord']要丟例外
        if (!isset($parseData['OrderRecords']['OrderRecord'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        // 以下開始驗證加密串
        $verifyData = $parseData['OrderRecords']['OrderRecord'];
        $this->trackingResultVerify($verifyData);

        // flag不為1表示支付失敗
        if ($verifyData['Flag'] != '1') {
            throw new PaymentConnectionException(
                'Payment failure',
                180035,
                $this->getEntryId()
            );
        }

        // 確保Amount精準到小數後兩位
        $verifyData['Amount'] = sprintf('%.2f', $verifyData['Amount']);

        $encodeStr = '';

        foreach (array_keys($this->trackingDecodeParams) as $paymentKey) {
            if (array_key_exists($paymentKey, $verifyData)) {
                $encodeStr .= $verifyData[$paymentKey];
            }
        }

        $encodeStr .= $this->privateKey;

        // 如果沒有Sign丟例外
        if (!isset($verifyData['Sign'])) {
            throw new PaymentException('No tracking return parameter specified', 180139);
        }

        if ($verifyData['Sign'] != md5($encodeStr)) {
            throw new PaymentConnectionException('Signature verification failed', 180034, $this->getEntryId());
        }

        if ($verifyData['Amount'] != $this->options['amount']) {
            throw new PaymentException('Order Amount error', 180058);
        }
    }

    /**
     * 批次訂單查詢
     *
     * @return $ret 訂單查詢結果
     */
    public function batchTracking()
    {
        try {
            // 驗證私鑰
            $this->verifyPrivateKey();

            // 傳入參數驗證
            if (!isset($this->options['number']) || trim($this->options['number']) === '') {
                throw new PaymentException('No tracking parameter specified', 180138);
            }

            if (trim($this->options['verify_url']) == '') {
                throw new PaymentException('No verify_url specified', 180140);
            }

            // 取得批次訂單查詢開始(最小)/結束(最大)訂單號
            $startNo = min(array_column($this->options['entries'], 'entry_id'));
            $endNo = max(array_column($this->options['entries'], 'entry_id'));

            // 給定訂單查詢值
            $this->trackingRequestData['MerCode'] = $this->options['number'];
            $this->trackingRequestData['StartNo'] = $startNo;
            $this->trackingRequestData['EndNo'] = $endNo;
            $this->trackingRequestData['Page'] = 1;
            $this->trackingRequestData['Max'] = '100';
            $this->trackingRequestData['Sign'] = $this->trackingEncode();

            $curlParam = [
                'method' => 'POST',
                'uri' => '/Sinopay/Standard/IpsCheckTrade.asmx/GetOrderByNo',
                'ip' => $this->options['verify_ip'],
                'host' => $this->options['verify_url'],
                'param' => http_build_query($this->trackingRequestData),
                'header' => []
            ];

            // 取得訂單查詢結果
            $result = $this->curlRequest($curlParam);
            $parseData = $this->parseData($result);

            // 如果沒有ErrCode要丟例外
            if (!isset($parseData['ErrCode'])) {
                throw new PaymentException('No tracking return parameter specified', 180139);
            }

            // 身分驗證失敗，商戶不存在
            if ($parseData['ErrCode'] == '1001') {
                throw new PaymentConnectionException(
                    'PaymentGateway error, Merchant is not exist',
                    180086,
                    $this->getEntryId()
                );
            }

            // 商戶憑證不存在
            if ($parseData['ErrCode'] == '1002') {
                throw new PaymentConnectionException(
                    'PaymentGateway error, Merchant Certificate is not exist',
                    180087,
                    $this->getEntryId()
                );
            }

            // 商戶發送的簽名驗證錯誤
            if ($parseData['ErrCode'] == '1003') {
                throw new PaymentConnectionException(
                    'PaymentGateway error, Merchant sign error',
                    180127,
                    $this->getEntryId()
                );
            }

            // 訂單時間格式不合法
            if ($parseData['ErrCode'] == '1004') {
                throw new PaymentConnectionException(
                    'PaymentGateway error, Invalid Order date',
                    180131,
                    $this->getEntryId()
                );
            }

            // 起始時間 > 結束時間
            if ($parseData['ErrCode'] == '1005') {
                throw new PaymentConnectionException(
                    'PaymentGateway error, Begin time large than End time',
                    180132,
                    $this->getEntryId()
                );
            }

            // 沒有輸入時間
            if ($parseData['ErrCode'] == '1006') {
                throw new PaymentConnectionException(
                    'PaymentGateway error, No date specified',
                    180133,
                    $this->getEntryId()
                );
            }

            // 請求服務失敗
            if ($parseData['ErrCode'] == '1007') {
                throw new PaymentConnectionException(
                    'Connection error, please try again later or contact customer service',
                    180077,
                    $this->getEntryId()
                );
            }

            // 沒有滿足條件的單號
            if ($parseData['ErrCode'] == '1008') {
                throw new PaymentConnectionException('Order does not exist', 180060, $this->getEntryId());
            }

            // 訂單號不合法
            if ($parseData['ErrCode'] == '1009') {
                throw new PaymentException('Order Id error', 180061);
            }

            // 起始訂單號 > 結束訂單號
            if ($parseData['ErrCode'] == '1010') {
                throw new PaymentConnectionException(
                    'PaymentGateway error, StartNo large than EndNo',
                    180134,
                    $this->getEntryId()
                );
            }

            // 合約過期
            if ($parseData['ErrCode'] == '2000') {
                throw new PaymentConnectionException(
                    'PaymentGateway error, Merchant has been expired',
                    180126,
                    $this->getEntryId()
                );
            }

            // 0000為查詢成功，防止有其他的錯誤碼，因此設定非0000即為查詢失敗
            if ($parseData['ErrCode'] != '0000') {
                throw new PaymentConnectionException('Payment tracking failed', 180081, $this->getEntryId());
            }

            // 如果沒有$parseData['Total']要丟例外
            if (!isset($parseData['Total'])) {
                throw new PaymentException('No tracking return parameter specified', 180139);
            }

            // Total大於100時, 環迅回傳分頁檔大於一頁
            if ($parseData['Total'] > 100) {
                throw new PaymentException('The number of return entries exceed the restriction', 150180173);
            }

            // 如果沒有$parseData['OrderRecords']['OrderRecord']要丟例外
            if (!isset($parseData['OrderRecords']['OrderRecord'])) {
                throw new PaymentException('No tracking return parameter specified', 180139);
            }

            // 如果沒有$parseData['Count']要丟例外
            if (!isset($parseData['Count'])) {
                throw new PaymentException('No tracking return parameter specified', 180139);
            }
        } catch (\Exception $e) {
            // 訂單查詢階段失敗
            $ret = [
                'result' => 'error',
                'code' => $e->getCode(),
                'msg' => $e->getMessage()
            ];

            return $ret;
        }

        // 因環迅只回傳一筆訂單時, xmlToArray會將訂單轉為一維陣列, 須調整為二維陣列
        if ($parseData['Count'] == 1) {
            $parseData['OrderRecords']['OrderRecord'] = [$parseData['OrderRecords']['OrderRecord']];
        }

        $ret = [];
        // 訂單查詢成功, 確認每一筆訂單查詢結果
        foreach ($this->options['entries'] as $entry) {
            $id = $entry['entry_id'];
            $amount = $entry['amount'];

            // 預設OK
            $ret[$id]['result'] = 'ok';

            // 檢查環迅回傳結果中有無此筆訂單
            $key = array_search($id, array_column($parseData['OrderRecords']['OrderRecord'], 'OrderNo'));

            if ($key !== false) {
                // 有訂單
                $verifyData = $parseData['OrderRecords']['OrderRecord'][$key];
                $this->trackingResultVerify($verifyData);

                if ($verifyData['Flag'] != 1) {
                    $ret[$id]['result'] = 'error';
                    $ret[$id]['code'] = '180035'; // 支付失敗
                    $ret[$id]['msg'] = 'Payment failure';
                    continue;
                }

                // 確保Amount精準到小數後兩位
                $verifyData['Amount'] = sprintf('%.2f', $verifyData['Amount']);

                $encodeStr = '';

                foreach (array_keys($this->trackingDecodeParams) as $paymentKey) {
                    if (array_key_exists($paymentKey, $verifyData)) {
                        $encodeStr .= $verifyData[$paymentKey];
                    }
                }

                $encodeStr .= $this->privateKey;

                // 如果沒有Sign丟例外
                if (!isset($verifyData['Sign'])) {
                    $ret[$id]['result'] = 'error';
                    $ret[$id]['code'] = '180139'; // 缺少返回訂單資訊
                    $ret[$id]['msg'] = 'No tracking return parameter specified';
                    continue;
                }

                if ($verifyData['Sign'] != md5($encodeStr)) {
                    $ret[$id]['result'] = 'error';
                    $ret[$id]['code'] = '180034'; // 簽名驗證錯誤
                    $ret[$id]['msg'] = 'Signature verification failed';
                    continue;
                }

                if ($verifyData['Amount'] != $amount) {
                    $ret[$id]['result'] = 'error';
                    $ret[$id]['code'] = '180058'; // 商戶訂單金額錯誤
                    $ret[$id]['msg'] = 'Order Amount error';
                    continue;
                }
            } else {
                // 訂單不存在
                $ret[$id]['result'] = 'error';
                $ret[$id]['code'] = '180060'; // 訂單不存在
                $ret[$id]['msg'] = 'Order does not exist';
                continue;
            }
        }

        return $ret;
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
            /*
             * 這邊是因為index是Currency_Type的時候加密串如果是
             * currencytype.$value[Currency_type]，
             * 其他的部分要回傳給支付平台的index首字都是小寫，
             * 但加密串需要大寫amount.$value[Amount]
             */
            if ($index == 'Currency_Type') {
                $encodeStr .= 'currencytype' . $this->requestData[$index];
            } else {
                $encodeStr .= strtolower($index) . $this->requestData[$index];
            }
        }

        //額外的加密設定
        $encodeStr .= $this->privateKey;

        return md5($encodeStr);
    }

    /**
     * 入款查詢時使用，用來分解訂單查詢(補單)時回傳的XML格式
     *
     * @param string $content xml格式的回傳值
     * @return array
     */
    private function parseData($content)
    {
        return $this->xmlToArray($content);
    }
}

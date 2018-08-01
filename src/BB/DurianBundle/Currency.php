<?php

namespace BB\DurianBundle;

class Currency
{
    /**
     * 目前系統支援幣別
     *
     * code與num(index)來源參考
     * http://en.wikipedia.org/wiki/ISO_4217
     *
     * 新增支援幣別時務必新增相關Exchange資料
     *
     * @var array
     */
    private $available = [
        156 => 'CNY', /* 人民幣 */
        978 => 'EUR', /* 歐元 */
        826 => 'GBP', /* 英鎊 */
        344 => 'HKD', /* 港幣 */
        360 => 'IDR', /* 印尼盾 */
        392 => 'JPY', /* 日幣 */
        410 => 'KRW', /* 韓圜 */
        458 => 'MYR', /* 馬來西亞幣 */
        702 => 'SGD', /* 新加坡幣 */
        764 => 'THB', /* 泰銖 */
        901 => 'TWD', /* 台幣 */
        840 => 'USD', /* 美金 */
        704 => 'VND'  /* 越南幣 */
    ];


    /**
     * 虛擬幣別:
     * 虛擬幣的code與num皆需自定義，不與真實幣的重複及可。參考ISO_4217的建隔，
     * 挑選905 ~ 930為虛擬幣區間。
     *
     * @var array
     */
    private $virtual = [];

    /**
     * 新註冊優惠幣別限額
     *
     * @var array
     */
    private $registerBonus = [
        'CNY' => 200, /* 人民幣 */
        'EUR' => 0, /* 歐元 */
        'GBP' => 0, /* 英鎊 */
        'HKD' => 0, /* 港幣 */
        'IDR' => 300000, /* 印尼盾 */
        'JPY' => 3000, /* 日幣 */
        'KRW' => 30000, /* 韓圜 */
        'MYR' => 0, /* 馬來西亞幣 */
        'SGD' => 0, /* 新加坡幣 */
        'THB' => 1000, /* 泰銖 */
        'TWD' => 0, /* 台幣 */
        'USD' => 30, /* 美金 */
        'VND' => 600000 /* 越南幣 */
    ];

    /**
     * 回傳支援的幣別列表
     *
     * @return array
     */
    public function getAvailable()
    {
        $available = array();
        foreach ($this->available as $num => $code) {
            $available[$num]['code'] = $code;
            $available[$num]['is_virtual'] = $this->isVirtual($num);
        }

        return $available;
    }

    /**
     * 檢查是否有支援傳入的幣別
     *
     * @param string $currency
     * @return boolean
     */
    public function isAvailable($currency)
    {
        return in_array($currency, $this->available);
    }

    /**
     * 檢查是否為虛擬幣別
     *
     * @param integer $num
     * @return boolean
     */
    public function isVirtual($num)
    {
        return in_array($num, $this->virtual);
    }

    /**
     * 取得對應的幣別編號
     *
     * @param string $code
     * @return integer
     */
    public function getMappedNum($code)
    {
        if (!$this->isAvailable($code)) {
            return null;
        }

        return array_search($code, $this->available);
    }

    /**
     * 取得對應的幣別代碼
     *
     * @param integer $id
     * @return string
     */
    public function getMappedCode($id)
    {
        if (!array_key_exists($id, $this->available)) {
            return null;
        }

        return $this->available[$id];
    }

    /**
     * 取得註冊優惠幣別限額
     *
     * @param integer $currency
     * @return integer
     */
    public function getRegiterBonus($currency)
    {
        $code = $this->getMappedCode($currency);

        if (!$code) {
            return null;
        }

        return $this->registerBonus[$code];
    }

    /**
     * 回傳所有註冊優惠幣別限額
     *
     * @return array
     */
    public function getAllRegisterBonus()
    {
        return $this->registerBonus;
    }
}

<?php

namespace BB\DurianBundle\Tests;

class CurrencyTest extends DurianTestCase
{
    /**
     * 測試回傳支援的幣別列表
     */
    public function testGetAvailable()
    {
        $expected = [
            156 => [
                'code' => 'CNY', /* 人民幣 */
                'is_virtual' => false
            ],
            978 => [
                'code' => 'EUR', /* 歐元 */
                'is_virtual' => false
            ],
            826 => [
                'code' => 'GBP', /* 英鎊 */
                'is_virtual' => false
            ],
            344 => [
                'code' => 'HKD', /* 港幣 */
                'is_virtual' => false
            ],
            360 => [
                'code' => 'IDR', /* 印尼盾 */
                'is_virtual' => false
            ],
            392 => [
                'code' => 'JPY', /* 日幣 */
                'is_virtual' => false
            ],
            410 => [
                'code' => 'KRW', /* 韓圜 */
                'is_virtual' => false
            ],
            458 => [
                'code' => 'MYR', /* 馬來西亞幣 */
                'is_virtual' => false
            ],
            702 => [
                'code' => 'SGD', /* 新加坡幣 */
                'is_virtual' => false
            ],
            764 => [
                'code' => 'THB', /* 泰銖 */
                'is_virtual' => false
            ],
            901 => [
                'code' => 'TWD', /* 台幣 */
                'is_virtual' => false
            ],
            840 => [
                'code' => 'USD', /* 美金 */
                'is_virtual' => false
            ],
            704 => [
                'code' => 'VND', /* 越南幣 */
                'is_virtual' => false
            ]
        ];

        $currency = new \BB\DurianBundle\Currency();

        $actual = $currency->getAvailable();
        $this->assertEquals($expected, $actual);
    }

    /**
     * 測試幣別是否支援
     */
    public function testIsAvailable()
    {
        $currency = new \BB\DurianBundle\Currency();

        $this->assertTrue($currency->isAvailable('CNY'));
        $this->assertTrue($currency->isAvailable('TWD'));
        $this->assertTrue($currency->isAvailable('USD'));
        $this->assertFalse($currency->isAvailable('ABC'));
    }

    /**
     * 測試幣別編號及代碼轉換
     */
    public function testMappedCurrency()
    {
        $currency = new \BB\DurianBundle\Currency();

        $this->assertEquals(156, $currency->getMappedNum('CNY'));
        $this->assertEquals('TWD', $currency->getMappedCode(901));
    }

    /**
     * 測試取得註冊優惠幣別限額，幣別為不存在的情況
     */
    public function testGetRegisterBonusWithoutExistCurrency()
    {
        $currency = new \BB\DurianBundle\Currency();

        $this->assertNull($currency->getRegiterBonus(036));
    }

    /**
     * 測試取得註冊優惠幣別限額
     */
    public function testGetRegisterBonus()
    {
        $currency = new \BB\DurianBundle\Currency();

        $this->assertEquals(200, $currency->getRegiterBonus(156)); //人民幣
        $this->assertEquals(300000, $currency->getRegiterBonus(360)); //印尼盾
        $this->assertEquals(3000, $currency->getRegiterBonus(392)); //日幣
        $this->assertEquals(30000, $currency->getRegiterBonus(410)); //韓圜
        $this->assertEquals(1000, $currency->getRegiterBonus(764)); //泰銖
        $this->assertEquals(30, $currency->getRegiterBonus(840)); //美金
        $this->assertEquals(600000, $currency->getRegiterBonus(704)); //越南幣

        // 預設幣別限額為0
        $this->assertEquals(0, $currency->getRegiterBonus(901)); //台幣
        $this->assertEquals(0, $currency->getRegiterBonus(978)); //歐元
        $this->assertEquals(0, $currency->getRegiterBonus(826)); //英鎊
        $this->assertEquals(0, $currency->getRegiterBonus(458)); //馬來西亞幣
        $this->assertEquals(0, $currency->getRegiterBonus(702)); //新加坡幣
        $this->assertEquals(0, $currency->getRegiterBonus(344)); //港幣
    }

    /**
     * 測試取得所有幣別的最大註冊優惠限額
     */
    public function testGetAllRegisterBonus()
    {
        $currency = new \BB\DurianBundle\Currency();

        $allCurrency = $currency->getAllRegisterBonus();

        $this->assertEquals(200, $allCurrency['CNY']); //人民幣
        $this->assertEquals(300000, $allCurrency['IDR']); //印尼盾
        $this->assertEquals(3000, $allCurrency['JPY']); //日幣
        $this->assertEquals(30000, $allCurrency['KRW']); //韓圜
        $this->assertEquals(1000, $allCurrency['THB']); //泰銖
        $this->assertEquals(30, $allCurrency['USD']); //美金
        $this->assertEquals(600000, $allCurrency['VND']); //越南幣
        $this->assertEquals(0, $allCurrency['EUR']); //歐元
        $this->assertEquals(0, $allCurrency['GBP']); //英鎊
        $this->assertEquals(0, $allCurrency['HKD']); //港幣
        $this->assertEquals(0, $allCurrency['MYR']); //馬來西亞幣
        $this->assertEquals(0, $allCurrency['SGD']); //新加坡幣
        $this->assertEquals(0, $allCurrency['TWD']); //台幣
    }
}

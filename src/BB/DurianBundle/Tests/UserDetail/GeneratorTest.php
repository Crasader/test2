<?php

namespace BB\DurianBundle\Tests\UserDetail;

use BB\DurianBundle\Tests\Functional\WebTestCase;

class GeneratorTest extends WebTestCase
{
    /**
     * 測試新增使用者詳細資料
     */
    public function testUserDetailCreate()
    {
        $generator = $this->getContainer()->get('durian.userdetail_generator');

        $user = $this->getMockBuilder('BB\DurianBundle\Entity\User')
                     ->setMethods(array('getId'))
                     ->getMock();

        $user->expects($this->any())
             ->method('getId')
             ->will($this->returnValue(52));

        $criteria = [
            'email' => 'Davanci@yahoo.com',
            'nickname' => 'MG149',
            'name_real' => 'DaVanCi',
            'name_chinese' => '文希哥',
            'name_english' => 'DaVanCi',
            'country' => 'ROC',
            'passport' => 'PA123456',
            'birthday' => '2001-01-01',
            'telephone' => '3345678',
            'password' => '12345',
            'qq_num' => '485163154787',
            'note' => 'Hello durian'
        ];

        $detail = $generator->create($user, $criteria);
        $output = $detail->toArray();

        //驗證new出來的$detail資料
        $this->assertEquals(52, $output['user_id']);
        $this->assertEquals('MG149', $output['nickname']);
        $this->assertEquals('DaVanCi', $output['name_real']);
        $this->assertEquals('文希哥', $output['name_chinese']);
        $this->assertEquals('DaVanCi', $output['name_english']);
        $this->assertEquals('ROC', $output['country']);
        $this->assertEquals('PA123456', $output['passport']);
        $this->assertEquals('2001-01-01', $output['birthday']);
        $this->assertEquals('3345678', $output['telephone']);
        $this->assertEquals('12345', $output['password']);
        $this->assertEquals('485163154787', $output['qq_num']);
        $this->assertEquals('Hello durian', $output['note']);

        //測試使用身分證字號新增使用者
        $detail = $generator->create($user, ['identity_card' => '2204438']);
        $output = $detail->toArray();

        $this->assertEquals('2204438', $output['identity_card']);

        //使用駕照新增使用者
        $detail = $generator->create($user, ['driver_license' => '2204439']);
        $output = $detail->toArray();

        $this->assertEquals('2204439', $output['driver_license']);

        //使用保險新增使用者
        $detail = $generator->create($user, ['insurance_card' => '2204440']);
        $output = $detail->toArray();

        $this->assertEquals('2204440', $output['insurance_card']);

        //使用健保卡新增使用者
        $detail = $generator->create($user, ['health_card' => '2204441']);
        $output = $detail->toArray();

        $this->assertEquals('2204441', $output['health_card']);
    }

    /**
     * 測試輸入的name_real編碼不正確情況
     */
    public function testNameRealInvalidEncode()
    {
        $this->setExpectedException('InvalidArgumentException', 'String must use utf-8 encoding', 150610002);

        $generator = $this->getContainer()->get('durian.userdetail_generator');

        $user = $this->getMockBuilder('BB\DurianBundle\Entity\User')
                     ->setMethods(array('getId'))
                     ->getMock();

        $user->expects($this->any())
             ->method('getId')
             ->will($this->returnValue(52));

        $criteria = [
            'email' => 'Davanci@yahoo.com',
            'nickname' => 'MG149',
            'name_real' => mb_convert_encoding('e龜龍鱉', 'GB2312', 'UTF-8'),
            'name_chinese' => '文希哥',
            'name_english' => 'DaVanCi',
            'country' => 'ROC',
            'passport' => 'PA123456',
            'birthday' => '2001-01-01',
            'telephone' => '3345678',
            'password' => '12345',
            'qq_num' => '485163154787',
            'note' => 'Hello durian'
        ];

        $generator->create($user, $criteria);
    }

    /**
     * 測試輸入的name_chinese編碼不正確情況
     */
    public function testNameChineseInvalidEncode()
    {
        $this->setExpectedException('InvalidArgumentException', 'String must use utf-8 encoding', 150610002);

        $generator = $this->getContainer()->get('durian.userdetail_generator');

        $user = $this->getMockBuilder('BB\DurianBundle\Entity\User')
                     ->setMethods(array('getId'))
                     ->getMock();

        $user->expects($this->any())
             ->method('getId')
             ->will($this->returnValue(52));

        $criteria = [
            'email' => 'Davanci@yahoo.com',
            'name_real' => 'DaVanCi',
            'nickname' => 'MG149',
            'name_chinese' => mb_convert_encoding('e龜龍鱉', 'GB2312', 'UTF-8'),
            'name_english' => 'DaVanCi',
            'country' => 'ROC',
            'passport' => 'PA123456',
            'birthday' => '2001-01-01',
            'telephone' => '3345678',
            'password' => '12345',
            'qq_num' => '485163154787',
            'note' => 'Hello durian'
        ];

        $generator->create($user, $criteria);
    }

    /**
     * 測試輸入的name_english編碼不正確情況
     */
    public function testNameEnglishInvalidEncode()
    {
        $this->setExpectedException('InvalidArgumentException', 'String must use utf-8 encoding', 150610002);

        $generator = $this->getContainer()->get('durian.userdetail_generator');

        $user = $this->getMockBuilder('BB\DurianBundle\Entity\User')
                     ->setMethods(array('getId'))
                     ->getMock();

        $user->expects($this->any())
             ->method('getId')
             ->will($this->returnValue(52));

        $criteria = [
            'email' => 'Davanci@yahoo.com',
            'name_real' => 'DaVanCi',
            'nickname' => 'MG149',
            'name_chinese' => '文希哥',
            'name_english' => mb_convert_encoding('e龜龍鱉', 'GB2312', 'UTF-8'),
            'country' => 'ROC',
            'passport' => 'PA123456',
            'birthday' => '2001-01-01',
            'telephone' => '3345678',
            'password' => '12345',
            'qq_num' => '485163154787',
            'note' => 'Hello durian'
        ];

        $generator->create($user, $criteria);
    }

    /**
     * 測試輸入的note編碼不正確情況
     */
    public function testNoteInvalidEncode()
    {
        $this->setExpectedException('InvalidArgumentException', 'String must use utf-8 encoding', 150610002);

        $generator = $this->getContainer()->get('durian.userdetail_generator');

        $user = $this->getMockBuilder('BB\DurianBundle\Entity\User')
                     ->setMethods(array('getId'))
                     ->getMock();

        $user->expects($this->any())
             ->method('getId')
             ->will($this->returnValue(52));

        $criteria = [
            'email' => 'Davanci@yahoo.com',
            'nickname' => 'MG149',
            'name_real' => 'DaVanCi',
            'name_chinese' => '文希哥',
            'name_english' => 'DaVanCi',
            'country' => 'ROC',
            'passport' => 'PA123456',
            'birthday' => '2001-01-01',
            'telephone' => '3345678',
            'password' => '12345',
            'qq_num' => '485163154787',
            'note' => mb_convert_encoding('e龜龍鱉', 'GB2312', 'UTF-8')
        ];

        $generator->create($user, $criteria);
    }

    /**
     * 測試輸入暱稱的長度不符資料表格式
     */
    public function testNicknameInvalidLength()
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid nickname length given', 150090026);

        $generator = $this->getContainer()->get('durian.userdetail_generator');

        $user = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->setMethods(['getId'])
            ->getMock();

        $user->expects($this->any())
            ->method('getId')
            ->will($this->returnValue(52));

        $criteria = ['nickname' => '我是用來測試暱稱長度是否過長的字串我是用來測試暱稱長度是否過長的字串'
            . '我是用來測試暱稱長度是否過長的字串我是用來測試暱稱長度是否過長的字串'];

        $generator->create($user, $criteria);
    }

    /**
     * 測試輸入真實姓名的長度不符資料表格式
     */
    public function testNameRealInvalidLength()
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid name_real length given', 150090027);

        $generator = $this->getContainer()->get('durian.userdetail_generator');

        $user = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->setMethods(['getId'])
            ->getMock();

        $user->expects($this->any())
            ->method('getId')
            ->will($this->returnValue(52));

        $criteria = ['name_real' => '我是用來測試真實姓名長度是否過長的字串，我是用來測試真實姓名長度是否過長的字串！'
            . '我是用來測試真實姓名長度是否過長的字串，我是用來測試真實姓名長度是否過長的字串！'
            . '我是用來測試真實姓名長度是否過長的字串，我是用來測試真實姓名長度是否過長的字串！'];

        $generator->create($user, $criteria);
    }

    /**
     * 測試輸入中文姓名的長度不符資料表格式
     */
    public function testNameChineseInvalidLength()
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid name_chinese length given', 150090028);

        $generator = $this->getContainer()->get('durian.userdetail_generator');

        $user = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->setMethods(['getId'])
            ->getMock();

        $user->expects($this->any())
            ->method('getId')
            ->will($this->returnValue(52));

        $criteria = ['name_chinese' => '我是用來測試中文姓名長度是否過長的字串！我是用來測試中文姓名長度是否過長的字串！'
            . '我是用來測試中文姓名長度是否過長的字串！我是用來測試中文姓名長度是否過長的字串！'];

        $generator->create($user, $criteria);
    }

    /**
     * 測試輸入英文姓名的長度不符資料表格式
     */
    public function testNameEnglishInvalidLength()
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid name_english length given', 150090029);

        $generator = $this->getContainer()->get('durian.userdetail_generator');

        $user = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->setMethods(['getId'])
            ->getMock();

        $user->expects($this->any())
            ->method('getId')
            ->will($this->returnValue(52));

        $criteria = ['name_english' => 'name_english,name_english,name_english,name_english,name_english'];

        $generator->create($user, $criteria);
    }

    /**
     * 測試輸入國家的長度不符資料表格式
     */
    public function testCountryInvalidLength()
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid country length given', 150090030);

        $generator = $this->getContainer()->get('durian.userdetail_generator');

        $user = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->setMethods(['getId'])
            ->getMock();

        $user->expects($this->any())
            ->method('getId')
            ->will($this->returnValue(52));

        $criteria = ['country' => '那美克星達爾悟空聯邦共和國那美克星達爾悟空聯邦共和國那美克星達爾悟空聯邦共和國'];

        $generator->create($user, $criteria);
    }

    /**
     * 測試輸入護照號碼的長度不符資料表格式
     */
    public function testPassportInvalidLength()
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid passport length given', 150090031);

        $generator = $this->getContainer()->get('durian.userdetail_generator');

        $user = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->setMethods(['getId'])
            ->getMock();

        $user->expects($this->any())
            ->method('getId')
            ->will($this->returnValue(52));

        $criteria = ['passport' => 'RX258971558412567412555411223645632'];

        $generator->create($user, $criteria);
    }

    /**
     * 測試輸入身分證代碼的長度不符資料表格式
     */
    public function testIdentityCardInvalidLength()
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid identity_card length given', 150090032);

        $generator = $this->getContainer()->get('durian.userdetail_generator');

        $user = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->setMethods(['getId'])
            ->getMock();

        $user->expects($this->any())
            ->method('getId')
            ->will($this->returnValue(52));

        $criteria = ['identity_card' => 'RX258971558412567412555411223645632'];

        $generator->create($user, $criteria);
    }

    /**
     * 測試輸入駕照號碼的長度不符資料表格式
     */
    public function testDriverLicenseInvalidLength()
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid driver_license length given', 150090033);

        $generator = $this->getContainer()->get('durian.userdetail_generator');

        $user = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->setMethods(['getId'])
            ->getMock();

        $user->expects($this->any())
            ->method('getId')
            ->will($this->returnValue(52));

        $criteria = ['driver_license' => 'RX258971558412567412555411223645632'];

        $generator->create($user, $criteria);
    }

    /**
     * 測試輸入保險證字號的長度不符資料表格式
     */
    public function testInsuranceCardInvalidLength()
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid insurance_card length given', 150090034);

        $generator = $this->getContainer()->get('durian.userdetail_generator');

        $user = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->setMethods(['getId'])
            ->getMock();

        $user->expects($this->any())
            ->method('getId')
            ->will($this->returnValue(52));

        $criteria = ['insurance_card' => 'RX258971558412567412555411223645632'];

        $generator->create($user, $criteria);
    }

    /**
     * 測試輸入健保卡字號的長度不符資料表格式
     */
    public function testHealthCardInvalidLength()
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid health_card length given', 150090035);

        $generator = $this->getContainer()->get('durian.userdetail_generator');

        $user = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->setMethods(['getId'])
            ->getMock();

        $user->expects($this->any())
            ->method('getId')
            ->will($this->returnValue(52));

        $criteria = ['health_card' => 'RX258971558412567412555411223645632'];

        $generator->create($user, $criteria);
    }

    /**
     * 測試輸入電話號碼的長度不符資料表格式
     */
    public function testTelephoneInvalidLength()
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid telephone length given', 150090036);

        $generator = $this->getContainer()->get('durian.userdetail_generator');

        $user = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->setMethods(['getId'])
            ->getMock();

        $user->expects($this->any())
            ->method('getId')
            ->will($this->returnValue(52));

        $criteria = ['telephone' => '19558971558412567412555411223645632'];

        $generator->create($user, $criteria);
    }

    /**
     * 測試輸入QQ號碼的長度不符資料表格式
     */
    public function testQQNumInvalidLength()
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid qq_num length given', 150090037);

        $generator = $this->getContainer()->get('durian.userdetail_generator');

        $user = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->setMethods(['getId'])
            ->getMock();

        $user->expects($this->any())
            ->method('getId')
            ->will($this->returnValue(52));

        $criteria = ['qq_num' => '19558971558412567412555411223645632'];

        $generator->create($user, $criteria);
    }

    /**
     * 測試輸入密碼的長度不符資料表格式
     */
    public function testPasswordInvalidLength()
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid password length given', 150090038);

        $generator = $this->getContainer()->get('durian.userdetail_generator');

        $user = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->setMethods(['getId'])
            ->getMock();

        $user->expects($this->any())
            ->method('getId')
            ->will($this->returnValue(52));

        $criteria = ['password' => 'testpasswordisinvaliddqwesmlfsefresfeesefkqwke'];

        $generator->create($user, $criteria);
    }

    /**
     * 測試輸入備註的長度不符資料表格式
     */
    public function testNoteInvalidLength()
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid note length given', 150090039);

        $generator = $this->getContainer()->get('durian.userdetail_generator');

        $user = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->setMethods(['getId'])
            ->getMock();

        $user->expects($this->any())
            ->method('getId')
            ->will($this->returnValue(52));

        $criteria = [
            'note' => '在此測試備註長度不符資料庫規範時，會發生例外。在此測試備註長度不符資料庫規範時，會發生例外。'
            . '在此測試備註長度不符資料庫規範時，會發生例外。在此測試備註長度不符資料庫規範時，會發生例外。'
            . '在此測試備註長度不符資料庫規範時，會發生例外。在此測試備註長度不符資料庫規範時，會發生例外。'
            . '在此測試備註長度不符資料庫規範時，會發生例外。在此測試備註長度不符資料庫規範時，會發生例外。'
            . '在此測試備註長度不符資料庫規範時，會發生例外。在此測試備註長度不符資料庫規範時，會發生例外。'
        ];

        $generator->create($user, $criteria);
    }

    /**
     * 測試輸入生日不符資料表格式
     */
    public function testBirthdayInvalid()
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid birthday given', 150090025);

        $generator = $this->getContainer()->get('durian.userdetail_generator');

        $user = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->setMethods(['getId'])
            ->getMock();

        $user->expects($this->any())
            ->method('getId')
            ->will($this->returnValue(52));

        $criteria = ['birthday' => '2011/12/32'];

        $generator->create($user, $criteria);
    }
}

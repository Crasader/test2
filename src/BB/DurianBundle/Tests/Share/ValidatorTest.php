<?php

namespace BB\DurianBundle\Tests\Share;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\User;
use BB\DurianBundle\Entity\ShareLimit;

class ValidatorTest extends DurianTestCase
{
    /**
     * @var User
     */
    private $company;

    /**
     * @var User
     */
    private $member;

    public function setUp()
    {
        $parent = new User();

        $this->company = new User();
        $this->company->setParent($parent);

        $this->member = new User();
        $this->member->setParent($this->company);
    }

    /**
     * 測試佔成在'確定新增/修改'前的檢查是否正確
     */
    public function testValidatePrePersist()
    {
        new ShareLimit($this->company, 1);
        $share = new ShareLimit($this->member, 1);
        $share->setUpper(20);

        //有修改後changed會被設為true
        $this->assertTrue($share->isChanged());

        $scheduler = $this
            ->getMockBuilder('BB\DurianBundle\Share\ScheduledForUpdate')
            ->disableOriginalConstructor()
            ->getMock();

        $validator = $this
            ->getMockBuilder('BB\DurianBundle\Share\Validator')
            ->disableOriginalConstructor()
            ->setMethods(array('getEntityManager'))
            ->getMock();

        $validator->setScheduler($scheduler);
        $validator->prePersist($share);

        //做完處理後changed會被設為false
        $this->assertFalse($share->isChanged());
    }

    /**
     * 測試佔成(包含下層)上限不能null
     */
    public function testUpperCanNotBeNull()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Upper can not be null',
            150080001
        );

        $share = new ShareLimit($this->member, 1);
        $share->setUpper(null);

        $validator = $this
            ->getMockBuilder('BB\DurianBundle\Share\Validator')
            ->disableOriginalConstructor()
            ->setMethods(array('getEntityManager'))
            ->getMock();


        $validator->validateLimit($share);
    }

    /**
     * 測試佔成(包含下層)下限不能null
     */
    public function testLowerCanNotBeNull()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Lower can not be null',
            150080002
        );

        $share = new ShareLimit($this->member, 1);
        $share->setLower(null);

        $validator = $this
            ->getMockBuilder('BB\DurianBundle\Share\Validator')
            ->disableOriginalConstructor()
            ->setMethods(array('getEntityManager'))
            ->getMock();

        $validator->validateLimit($share);
    }

    /**
     * 測試上層的佔成(不包含下層)上限不能null
     */
    public function testParentUpperCanNotBeNull()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'ParentUpper can not be null',
            150080003
        );

        $share = new ShareLimit($this->member, 1);
        $share->setParentUpper(null);

        $validator = $this
            ->getMockBuilder('BB\DurianBundle\Share\Validator')
            ->disableOriginalConstructor()
            ->setMethods(array('getEntityManager'))
            ->getMock();

        $validator->validateLimit($share);
    }

    /**
     * 測試上層的佔成(不包含下層)下限不能null
     */
    public function testParentLowerCanNotBeNull()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'ParentLower can not be null',
            150080004
        );

        $share = new ShareLimit($this->member, 1);
        $share->setParentLower(null);

        $validator = $this
            ->getMockBuilder('BB\DurianBundle\Share\Validator')
            ->disableOriginalConstructor()
            ->setMethods(array('getEntityManager'))
            ->getMock();

        $validator->validateLimit($share);
    }

    /**
     * 測試佔成(包含下層)上限不能超過100
     */
    public function testUpperCanNotExceedLimitMax()
    {
        $this->setExpectedException(
            'RangeException',
            'Upper can not be set over 100',
            150080005
        );

        $share = new ShareLimit($this->member, 1);
        $share->setUpper(101);

        $validator = $this
            ->getMockBuilder('BB\DurianBundle\Share\Validator')
            ->disableOriginalConstructor()
            ->setMethods(array('getEntityManager'))
            ->getMock();

        $validator->validateLimit($share);
    }

    /**
     * 測試佔成(包含下層)上限不能低於0
     */
    public function testUpperCanNotBelowLimitMin()
    {
        $this->setExpectedException(
            'RangeException',
            'Upper can not be set below 0',
            150080006
        );

        $share = new ShareLimit($this->member, 1);
        $share->setUpper(-1);

        $validator = $this
            ->getMockBuilder('BB\DurianBundle\Share\Validator')
            ->disableOriginalConstructor()
            ->setMethods(array('getEntityManager'))
            ->getMock();

        $validator->validateLimit($share);
    }

    /**
     * 測試佔成(包含下層)下限不能超過100
     */
    public function testLowerCanNotExceedLimitMax()
    {
        $this->setExpectedException(
            'RangeException',
            'Lower can not be set over 100',
            150080007
        );

        $share = new ShareLimit($this->member, 1);
        $share->setLower(101);

        $validator = $this
            ->getMockBuilder('BB\DurianBundle\Share\Validator')
            ->disableOriginalConstructor()
            ->setMethods(array('getEntityManager'))
            ->getMock();

        $validator->validateLimit($share);
    }

    /**
     * 測試佔成(包含下層)下限不能低於0
     */
    public function testLowerCanNotBelowLimitMin()
    {
        $this->setExpectedException(
            'RangeException',
            'Lower can not be set below 0',
            150080008
        );

        $share = new ShareLimit($this->member, 1);
        $share->setLower(-1);

        $validator = $this
            ->getMockBuilder('BB\DurianBundle\Share\Validator')
            ->disableOriginalConstructor()
            ->setMethods(array('getEntityManager'))
            ->getMock();

        $validator->validateLimit($share);
    }

    /**
     * 測試佔成(包含下層)下限不能超過100
     */
    public function testParentUpperCanNotExceedLimitMax()
    {
        $this->setExpectedException(
            'RangeException',
            'ParentUpper can not be set over 100',
            150080009
        );

        $share = new ShareLimit($this->member, 1);
        $share->setParentUpper(101);

        $validator = $this
            ->getMockBuilder('BB\DurianBundle\Share\Validator')
            ->disableOriginalConstructor()
            ->setMethods(array('getEntityManager'))
            ->getMock();

        $validator->validateLimit($share);
    }

    /**
     * 測試佔成(包含下層)下限不能低於0
     */
    public function testParentUpperCanNotBelowLimitMin()
    {
        $this->setExpectedException(
            'RangeException',
            'ParentUpper can not be set below 0',
            150080010
        );

        $share = new ShareLimit($this->member, 1);
        $share->setParentUpper(-1);

        $validator = $this
            ->getMockBuilder('BB\DurianBundle\Share\Validator')
            ->disableOriginalConstructor()
            ->setMethods(array('getEntityManager'))
            ->getMock();

        $validator->validateLimit($share);
    }

    /**
     * 測試佔成(包含下層)下限不能超過100
     */
    public function testParentLowerCanNotExceedLimitMax()
    {
        $this->setExpectedException(
            'RangeException',
            'ParentLower can not be set over 100',
            150080011
        );

        $share = new ShareLimit($this->member, 1);
        $share->setParentLower(101);

        $validator = $this
            ->getMockBuilder('BB\DurianBundle\Share\Validator')
            ->disableOriginalConstructor()
            ->setMethods(array('getEntityManager'))
            ->getMock();

        $validator->validateLimit($share);
    }

    /**
     * 測試佔成(包含下層)下限不能低於0
     */
    public function testParentLowerCanNotBelowLimitMin()
    {
        $this->setExpectedException(
            'RangeException',
            'ParentLower can not be set below 0',
            150080012
        );

        $share = new ShareLimit($this->member, 1);
        $share->setParentLower(-1);

        $validator = $this
            ->getMockBuilder('BB\DurianBundle\Share\Validator')
            ->disableOriginalConstructor()
            ->setMethods(array('getEntityManager'))
            ->getMock();

        $validator->validateLimit($share);
    }

    /**
     * 測試佔成Lower數值不能大於Upper數值
     */
    public function testLowerCanNotExceedUpper()
    {
        $this->setExpectedException(
            'RangeException',
            'Lower can not exceed upper',
            150080013
        );

        $share = new ShareLimit($this->member, 1);
        $share->setUpper(0);
        $share->setLower(10);

        $validator = $this
            ->getMockBuilder('BB\DurianBundle\Share\Validator')
            ->disableOriginalConstructor()
            ->setMethods(array('getEntityManager'))
            ->getMock();

        $validator->validateLimit($share);
    }

    /**
     * 測試ParentLower數值不能大於ParentUpper數值
     */
    public function testParentLowerCanNotExceedParentUpper()
    {
        $this->setExpectedException(
            'RangeException',
            'ParentLower can not exceed parentUpper',
            150080014
        );

        $share = new ShareLimit($this->member, 1);
        $share->setParentUpper(0);
        $share->setParentLower(10);

        $validator = $this
            ->getMockBuilder('BB\DurianBundle\Share\Validator')
            ->disableOriginalConstructor()
            ->setMethods(array('getEntityManager'))
            ->getMock();

        $validator->validateLimit($share);
    }

    /**
     * 測試條件一：
     * 佔成(含下層)下限 不能超過 任一下層設定的上層自身佔成上限＋佔成(含下層)下限
     */
    public function testLowerCanNotLargerThanMin1()
    {
        $this->setExpectedException(
            'RangeException',
            'Lower can not exceed any child ParentUpper + Lower (min1)',
            150080015
        );

        $share = new ShareLimit($this->member, 1);
        $share->setLower(10);
        $share->setMin1(0);

        $validator = $this
            ->getMockBuilder('BB\DurianBundle\Share\Validator')
            ->disableOriginalConstructor()
            ->setMethods(array('getEntityManager'))
            ->getMock();

        $validator->validateLimit($share);
    }

    /**
     * 測試條件二：
     * 佔成(含下層)上限 不能低於 任一下層設定的上層自身佔成上限
     */
    public function testUpperCanNotLessThanMax1()
    {
        $this->setExpectedException(
            'RangeException',
            'Upper can not below any child ParentUpper (max1)',
            150080016
        );

        $share = new ShareLimit($this->member, 1);
        $share->setMax1(10);
        $share->setUpper(0);

        $validator = $this
            ->getMockBuilder('BB\DurianBundle\Share\Validator')
            ->disableOriginalConstructor()
            ->setMethods(array('getEntityManager'))
            ->getMock();

        $validator->validateLimit($share);
    }

    /**
     * 測試條件三：
     * 佔成(含下層)上限 不能低於 任一下層設定的上層自身佔成下限＋佔成(含下層)上限
     */
    public function testUpperCanNotLessThanMax2()
    {
        $this->setExpectedException(
            'RangeException',
            'Upper can not below any child ParentLower + Upper (max2)',
            150080017
        );

        $share = new ShareLimit($this->member, 1);
        $share->setMax2(10);
        $share->setUpper(0);

        $validator = $this
            ->getMockBuilder('BB\DurianBundle\Share\Validator')
            ->disableOriginalConstructor()
            ->setMethods(array('getEntityManager'))
            ->getMock();

        $validator->validateLimit($share);
    }

    /**
     * 測試條件四：
     * 上層自身佔成上限＋佔成(含下層)下限 不能小於 上層設定佔成(含下層)下限
     */
    public function testMin1CanNotLessThanParentLower()
    {
        $this->setExpectedException(
            'RangeException',
            'Any child ParentUpper + Lower (min1) can not below parentBelowLower',
            150080018
        );

        $parentShare = new ShareLimit($this->company, 1);
        $parentShare->setLower(50);

        $share = new ShareLimit($this->member, 1);
        $share->setParentUpper(10);
        $share->setLower(20);

        $validator = $this
            ->getMockBuilder('BB\DurianBundle\Share\Validator')
            ->disableOriginalConstructor()
            ->setMethods(array('getEntityManager'))
            ->getMock();

        $validator->validateLimit($share);
    }

    /**
     * 測試條件五：
     * 上層自身佔成上限 不能大於 上層佔成(含下層)上限
     */
    public function testMax1CantNotLargerThanParentUpper()
    {
        $this->setExpectedException(
            'RangeException',
            'Any child ParentUpper (max1) can not exceed parentBelowUpper',
            150080019
        );

        $parentShare = new ShareLimit($this->company, 1);
        $parentShare->setUpper(50);

        $share = new ShareLimit($this->member, 1);
        $share->setParentUpper(60);

        $validator = $this
            ->getMockBuilder('BB\DurianBundle\Share\Validator')
            ->disableOriginalConstructor()
            ->setMethods(array('getEntityManager'))
            ->getMock();

        $validator->validateLimit($share);
    }

    /**
     * 測試條件六：
     * 上層自身佔成下限＋佔成(含下層)上限 不能大於 上層設定佔成(含下層)上限
     */
    public function testMax2CantNotLargerThanParentUpper()
    {
        $this->setExpectedException(
            'RangeException',
            'Any child ParentLower + Upper (max2) can not exceed parentBelowUpper',
            150080020
        );

        $parentShare = new ShareLimit($this->company, 1);
        $parentShare->setUpper(50);

        $share = new ShareLimit($this->member, 1);
        $share->setParentUpper(40);
        $share->setParentLower(40);
        $share->setUpper(20);

        $validator = $this
            ->getMockBuilder('BB\DurianBundle\Share\Validator')
            ->disableOriginalConstructor()
            ->setMethods(array('getEntityManager'))
            ->getMock();

        $validator->validateLimit($share);
    }
}

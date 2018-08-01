<?php

namespace BB\DurianBundle\Tests\Share;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\User;
use BB\DurianBundle\Entity\ShareLimit;
use BB\DurianBundle\Entity\ShareLimitNext;
use BB\DurianBundle\Share\OptionGenerator;

class OptionGeneratorTest extends DurianTestCase
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
        $this->company = new User();

        $this->member = new User();
        $this->member->setParent($this->company);
    }

    /**
     * 測試 getUpperOption()
     */
    public function testGetUpperOption()
    {
        // #1
        $parentShare = new ShareLimit($this->company, 1);
        $parentShare->setParentUpper(20);
        $parentShare->setParentLower(20);
        $parentShare->setUpper(30);
        $parentShare->setLower(30);

        $share = new ShareLimit($this->member, 1);
        $share->setParentUpper(30);
        $share->setParentLower(30);
        $share->setUpper(0);
        $share->setLower(0);

        $generator = new OptionGenerator();
        $option = $generator->getUpperOption($share);

        $this->assertEquals(array(30, 25, 20, 15, 10, 5, 0), $option);

        // #2 預改
        $parentShareNext = new ShareLimitNext($this->company, 1);
        $parentShareNext->setParentUpper(20);
        $parentShareNext->setParentLower(20);
        $parentShareNext->setUpper(30);
        $parentShareNext->setLower(30);

        $shareNext = new ShareLimitNext($this->member, 1);
        $shareNext->setParentUpper(30);
        $shareNext->setParentLower(30);
        $shareNext->setUpper(0);
        $shareNext->setLower(0);

        $option = $generator->getUpperOption($shareNext);

        $this->assertEquals(array(30, 25, 20, 15, 10, 5, 0), $option);
    }

    /**
     * 測試 getLowerOption()
     */
    public function testGetLowerOption()
    {
        // #1
        $parentShare = new ShareLimit($this->company, 1);
        $parentShare->setParentUpper(20);
        $parentShare->setParentLower(20);
        $parentShare->setUpper(30);
        $parentShare->setLower(30);

        $share = new ShareLimit($this->member, 1);
        $share->setParentUpper(30);
        $share->setParentLower(30);
        $share->setUpper(0);
        $share->setLower(0);

        $generator = new OptionGenerator();
        $option = $generator->getLowerOption($share);

        $this->assertEquals(array(30, 25, 20, 15, 10, 5, 0), $option);

        // #2 預改
        $parentShareNext = new ShareLimitNext($this->company, 1);
        $parentShareNext->setParentUpper(20);
        $parentShareNext->setParentLower(20);
        $parentShareNext->setUpper(30);
        $parentShareNext->setLower(30);

        $shareNext = new ShareLimitNext($this->member, 1);
        $shareNext->setParentUpper(30);
        $shareNext->setParentLower(30);
        $shareNext->setUpper(0);
        $shareNext->setLower(0);

        $option = $generator->getLowerOption($shareNext);

        $this->assertEquals(array(30, 25, 20, 15, 10, 5, 0), $option);
    }

    /**
     * 測試 getParentUpperOption()
     */
    public function testGetParentUpperOption()
    {
        // #1
        $parentShare = new ShareLimit($this->company, 1);
        $parentShare->setParentUpper(20);
        $parentShare->setParentLower(20);
        $parentShare->setUpper(95);
        $parentShare->setLower(30);

        $share = new ShareLimit($this->member, 1);
        $share->setParentUpper(30);
        $share->setParentLower(30);
        $share->setUpper(0);
        $share->setLower(0);

        $generator = new OptionGenerator();
        $option = $generator->getParentUpperOption($share);

        $this->assertEquals(32, count($option));
        $this->assertEquals(95, $option[0]);
        $this->assertEquals(94, $option[1]);
        $this->assertEquals(89, $option[6]);
        $this->assertEquals(88, $option[7]);
        $this->assertEquals(81, $option[14]);
        $this->assertEquals(75, $option[16]);
        $this->assertEquals(5, $option[30]);
        $this->assertEquals(0, $option[31]);

        // #2 預改
        $parentShareNext = new ShareLimitNext($this->company, 1);
        $parentShareNext->setParentUpper(20);
        $parentShareNext->setParentLower(20);
        $parentShareNext->setUpper(95);
        $parentShareNext->setLower(30);

        $shareNext = new ShareLimitNext($this->member, 1);
        $shareNext->setParentUpper(30);
        $shareNext->setParentLower(30);
        $shareNext->setUpper(0);
        $shareNext->setLower(0);

        $option = $generator->getParentUpperOption($shareNext);

        $this->assertEquals(32, count($option));
        $this->assertEquals(95, $option[0]);
        $this->assertEquals(94, $option[1]);
        $this->assertEquals(89, $option[6]);
        $this->assertEquals(88, $option[7]);
        $this->assertEquals(81, $option[14]);
        $this->assertEquals(75, $option[16]);
        $this->assertEquals(5, $option[30]);
        $this->assertEquals(0, $option[31]);
    }

    /**
     * 測試 getParentLowerOption()
     */
    public function testGetParentLowerOption()
    {
        // #1
        $parentShare = new ShareLimit($this->company, 1);
        $parentShare->setParentUpper(20);
        $parentShare->setParentLower(20);
        $parentShare->setUpper(98);
        $parentShare->setLower(85);

        $share = new ShareLimit($this->member, 1);
        $share->setParentUpper(30);
        $share->setParentLower(30);
        $share->setUpper(0);
        $share->setLower(0);

        $generator = new OptionGenerator();
        $option = $generator->getParentLowerOption($share);

        $this->assertEquals(98, $option[0]);
        $this->assertEquals(97, $option[1]);
        $this->assertEquals(96, $option[2]);
        $this->assertEquals(80, $option[18]);
        $this->assertEquals(75, $option[19]);
        $this->assertEquals(45, $option[25]);
        $this->assertEquals(0, $option[34]);

        // #2 預改
        $parentShareNext = new ShareLimitNext($this->company, 1);
        $parentShareNext->setParentUpper(20);
        $parentShareNext->setParentLower(20);
        $parentShareNext->setUpper(98);
        $parentShareNext->setLower(85);

        $shareNext = new ShareLimitNext($this->member, 1);
        $shareNext->setParentUpper(30);
        $shareNext->setParentLower(30);
        $shareNext->setUpper(0);
        $shareNext->setLower(0);

        $option = $generator->getParentLowerOption($shareNext);

        $this->assertEquals(98, $option[0]);
        $this->assertEquals(97, $option[1]);
        $this->assertEquals(96, $option[2]);
        $this->assertEquals(80, $option[18]);
        $this->assertEquals(75, $option[19]);
        $this->assertEquals(45, $option[25]);
        $this->assertEquals(0, $option[34]);
    }
}

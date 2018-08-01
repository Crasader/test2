<?php
namespace BB\DurianBundle\Tests\Domain;

use BB\DurianBundle\Tests\Functional\WebTestCase;

class ValidatorTest extends WebTestCase
{

    private $validator;

    public function setUp()
    {
        parent::setUp();

        $classnames = ['BB\DurianBundle\Tests\DataFixtures\ORM\LoadDomainConfigData'];

        $this->loadFixtures($classnames, 'share');

        $this->validator = $this->getContainer()->get('durian.domain_validator');
    }

    /**
     * 測試name長度過長會例外
     */
    public function testNameLengthTooLong()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid name length given',
            150360015
        );

        $this->validator->validateName('0123456789 0123456789 0123456789');
    }

    /**
     * 測試name長度過短會例外
     */
    public function testNameLengthTooShort()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid name length given',
            150360015
        );

        $this->validator->validateName('');
    }

    /**
     * 測試name不允許特殊符號
     */
    public function testNameHasSpecialChar()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid name character given',
            150360016
        );

        $this->validator->validateName('><"');
    }

    /**
     * 測試name不能重複
     */
    public function testNameAlreadyExists()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Name already exist',
            150360017
        );

        $this->validator->validateName('domain2');
    }

    /**
     * 測試loginCode長度過長會例外
     */
    public function testloginCodeLengthTooLong()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid login code',
            150360020
        );

        $this->validator->validateLoginCode('PPAP');
    }

    /**
     * 測試loginCode長度過短會例外
     */
    public function testloginCodeLengthTooShort()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid login code',
            150360020
        );

        $this->validator->validateloginCode('');
    }

    /**
     * 測試loginCode不符合規則
     */
    public function testloginCodeNotMatchRule()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid login code character given',
            150360021
        );

        $this->validator->validateLoginCode('Ow0');
    }
}

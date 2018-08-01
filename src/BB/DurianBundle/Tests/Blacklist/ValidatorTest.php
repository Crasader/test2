<?php
namespace BB\DurianBundle\Tests\Blacklist;

use BB\DurianBundle\Tests\Functional\WebTestCase;

class ValidatorTest extends WebTestCase
{
    /**
     * @var \BB\DurianBundle\Blacklist\Validator
     */
    private $validator;

    public function setUp()
    {
        parent::setUp();

        $classnames = ['BB\DurianBundle\Tests\DataFixtures\ORM\LoadBlacklistData'];

        $this->loadFixtures($classnames, 'share');

        $this->validator = $this->getContainer()->get('durian.blacklist_validator');
    }

    /**
     * 測試驗證黑名單未帶參數直接回傳
     */
    public function testValidateBlacklistWithoutCriteriaAndIp()
    {
        $output = $this->validator->validate([], 1);

        $this->assertNull($output);
    }

    /**
     * 測試驗證銀行帳號
     */
    public function testValidateAccountBlacklist()
    {
        $this->setExpectedException(
            'RuntimeException',
            'This account has been blocked',
            150650015
        );

        $criteria['account'] = 'blackbank123';
        $this->validator->validate($criteria, 1);
    }

    /**
     * 測試驗證身分證字號
     */
    public function testValidateIdentityCardBlacklist()
    {
        $this->setExpectedException(
            'RuntimeException',
            'This identity_card has been blocked',
            150650016
        );

        $criteria['identity_card'] = '55665566';
        $this->validator->validate($criteria, 1);
    }

    /**
     * 測試驗證真實姓名
     */
    public function testValidateNameRealBlacklist()
    {
        $this->setExpectedException(
            'RuntimeException',
            'This name_real has been blocked',
            150650017
        );

        $criteria['name_real'] = '控端指定廳人工新增黑名單-5';
        $this->validator->validate($criteria, 2);
    }

    /**
     * 測試驗證真實姓名，帶非UTF8
     */
    public function testValidateNameRealBlacklistWithNotUtf8Encode()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'String must use utf-8 encoding',
            150610002
        );

        $criteria['name_real'] = mb_convert_encoding('龜龍鱉', 'GB2312', 'UTF-8');
        $this->validator->validate($criteria, 1);
    }

    /**
     * 測試驗證真實姓名，會過濾特殊字元
     */
    public function testValidateNameRealBlacklistContainsSpecialCharacter()
    {
        $this->setExpectedException(
            'RuntimeException',
            'This name_real has been blocked',
            150650017
        );

        $criteria['name_real'] = '控端指定廳人工新增黑名單-5';
        $this->validator->validate($criteria, 2);
    }

    /**
     * 測試驗證電話
     */
    public function testValidateTelephoneBlacklist()
    {
        $this->setExpectedException(
            'RuntimeException',
            'This telephone has been blocked',
            150650018
        );

        $criteria['telephone'] = '0911123456';
        $this->validator->validate($criteria, 1);
    }

    /**
     * 測試驗證信箱
     */
    public function testValidateEmailBlacklist()
    {
        $this->setExpectedException(
            'RuntimeException',
            'This email has been blocked',
            150650019
        );

        $criteria['email'] = 'blackemail@tmail.com';
        $this->validator->validate($criteria, 1);
    }

    /**
     * 測試驗證IP
     */
    public function testValidateIpBlacklist()
    {
        $this->setExpectedException(
            'RuntimeException',
            'This ip has been blocked',
            150650020
        );

        $criteria['ip'] = '115.195.41.247';
        $this->validator->validate($criteria, 1);
    }
}

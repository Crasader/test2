<?php

namespace BB\DurianBundle\Tests\Service;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Validator;

class ValidatorTest extends DurianTestCase
{
    /**
     * 測試驗證編碼是UTF8
     */
    public function testValidateEncodeIsUTF8()
    {
        $validator = new Validator;

        $this->assertNull($validator->validateEncode("\x01"));
    }

    /**
     * 測試驗證編碼非UTF8
     */
    public function testValidateEncodeNotUTF8()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'String must use utf-8 encoding',
            150610002
        );

        $validator = new Validator;

        $validator->validateEncode("\xFF");
    }

    /**
     * 測試驗證編碼帶有非法字元
     */
    public function testValidateEncodeWithIllegalCharacter()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Illegal character',
            150610007
        );

        $validator = new Validator;

        $validator->validateEncode("🔫");
    }

    /**
     * 測試驗證數字在小數點4位以內
     */
    public function testValidateDecimalNotExceedNumberOfDecimalPlaces()
    {
        $validator = new Validator;

        $this->assertNull($validator->validateDecimal(12.0011, 4));
    }

    /**
     * 測試驗證數字不在小數點4位以內
     */
    public function testValidateDecimalExceedsNumberOfDecimalPlaces()
    {
        $this->setExpectedException('RangeException', 'The decimal digit of amount exceeds limitation', 150610003);

        $validator = new Validator;

        $validator->validateDecimal(12.00111, 4);
    }

    /**
     * 測試驗證數字但格式非法
     */
    public function testValidateDecimalButInvalid()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid decimal',
            150610006
        );

        $validator = new Validator;

        $validator->validateDecimal('12.001z', 4);
    }

    /**
     * 測試檢查ref_id型態是否為int，且值在0~9223372036854775806之間
     */
    public function testValidateRefId()
    {
        $validator = new Validator;

        $this->assertFalse($validator->validateRefId(15));
        $this->assertTrue($validator->validateRefId(-5));
        $this->assertTrue($validator->validateRefId(9999999999999999999999));
        $this->assertTrue($validator->validateRefId('abc'));
    }

    /**
     * 測試檢查opcode型態是否為int，且值在1~999999之間
     */
    public function testValidateOpcode()
    {
        $validator = new Validator;

        $this->assertTrue($validator->validateOpcode(15));
        $this->assertFalse($validator->validateOpcode(-5));
        $this->assertFalse($validator->validateOpcode(1000000));
        $this->assertFalse($validator->validateOpcode('abc'));
    }

    /**
     * 測試檢查型態是否為int,$disallowNegative帶true時，則驗證是否為正整數
     */
    public function testIsInt()
    {
        $validator = new Validator;

        $this->assertTrue($validator->isInt(15));
        $this->assertFalse($validator->isInt(0.1));
        $this->assertFalse($validator->isInt(-5, true));
        $this->assertFalse($validator->isInt('abc'));
    }

    /**
     * 測試檢查型態是否為float,$disallowNegative帶true時，則驗證是否為正浮點數
     */
    public function testIsFloat()
    {
        $validator = new Validator;

        $this->assertTrue($validator->isFloat(0.1));
        $this->assertTrue($validator->isFloat(15));
        $this->assertFalse($validator->isFloat(-0.5, true));
        $this->assertFalse($validator->isFloat('abc'));
        $this->assertTrue($validator->isFloat('123'));
        $this->assertFalse($validator->isFloat(' 123'));
        $this->assertFalse($validator->isFloat('123 '));
    }

    /**
     * 檢查ip格式
     */
    public function testValidateIp()
    {
        $validator = new Validator;

        // 測試驗證合法ip
        $ip = '123.1.2.3';
        $this->assertTrue($validator->validateIp($ip));

        // 測試驗證不完整ip
        $ip = '123';
        $this->assertFalse($validator->validateIp($ip));

        // 測試驗證不完整ip
        $ip = '0';
        $this->assertFalse($validator->validateIp($ip));

        // 測試驗證格式不符ip
        $ip = '123.123.123.777';
        $this->assertFalse($validator->validateIp($ip));
    }

    /**
     * 檢查時間格式
     */
    public function testValidateDate()
    {
        $validator = new Validator;

        // 測試date格式正確且為存在日期之字串
        $date = '2014-01-01T12:00:00+0800';
        $this->assertTrue($validator->validateDate($date));

        // 測試date為空字串
        $date = '';
        $this->assertFalse($validator->validateDate($date));

        // 測試date為一般字串
        $date = 'test';
        $this->assertFalse($validator->validateDate($date));

        // 測試date為不存在日期
        $date = '2014-02-29T12:00:00+0800';
        $this->assertFalse($validator->validateDate($date));

        // 測試date為不完整日期時間
        $date = '1324';
        $this->assertFalse($validator->validateDate($date));

    }

    /**
     * 驗證電話格式
     */
    public function testValidateInvalidTelephone()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid telephone',
            150610001
        );

        $validator = new Validator;

        $telephone = 'a123456';
        $validator->validateTelephone($telephone);
    }

    /**
     * 檢查時間區間是否正確且成對帶入
     */
    public function testValidateDateRange()
    {
        $validator = new Validator;

        // 測試時間區間正確帶入
        $start = '2014-01-01T12:00:00+0800';
        $end = '2014-01-05T12:00:00+0800';
        $this->assertTrue($validator->validateDateRange($start, $end));

        // 測試帶入空字串
        $start = '';
        $this->assertFalse($validator->validateDateRange($start, $end));

        // 測試帶入空白字串
        $start = ' ';
        $this->assertFalse($validator->validateDateRange($start, $end));

        // 測試時間區間格式錯誤
        $start = '2014-01-01T12:00:00+0800';
        $end = '2014-02-90T12:00:00+0800';
        $this->assertFalse($validator->validateDateRange($start, $end));
    }

    /**
     * 測試驗證不合法的開始筆數
     */
    public function testValidateByInvalidFirstResult()
    {
        $validator = new Validator;

        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid first_result',
            150610004
        );
        $firstResult = 'test';
        $maxResults = 5;

        $validator->validatePagination($firstResult, $maxResults);
    }

    /**
     * 測試驗證不合法的顯示筆數
     */
    public function testValidateByInvalidMaxResults()
    {
        $validator = new Validator;

        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid max_results',
            150610005
        );
        $firstResult = 0;
        $maxResults = 'test';

        $validator->validatePagination($firstResult, $maxResults);
    }
}

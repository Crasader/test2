<?php
namespace BB\DurianBundle\Tests\UserDetail;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\UserDetail\Validator;

class ValidatorTest extends WebTestCase
{
    /**
     * 測試驗證暱稱長度
     */
    public function testValidateNicknameLength()
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid nickname length given', 150090026);

        $validator = new Validator;
        $validator->setContainer($this->getContainer());
        $validator->validateNicknameLength('我是用來測試暱稱長度是否過長的字串我是用來測試暱稱長度是否過長的字串'
            . '我是用來測試暱稱長度是否過長的字串');
    }

    /**
     * 測試驗證真實姓名長度
     */
    public function testValidateNameRealLength()
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid name_real length given', 150090027);

        $validator = new Validator;
        $validator->setContainer($this->getContainer());
        $validator->validateNameRealLength('我是用來測試真實姓名長度是否過長的字串，我是用來測試真實姓名長度是否過長的字串！'
            . '我是用來測試真實姓名長度是否過長的字串，我是用來測試真實姓名長度是否過長的字串！'
            . '我是用來測試真實姓名長度是否過長的字串，我是用來測試真實姓名長度是否過長的字串！');
    }

    /**
     * 測試驗證中文姓名長度
     */
    public function testValidateNameChineseLength()
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid name_chinese length given', 150090028);

        $validator = new Validator;
        $validator->setContainer($this->getContainer());
        $validator->validateNameChineseLength('我是用來測試中文姓名長度是否過長的字串'
            . '我是用來測試中文姓名長度是否過長的字串我是用來測試中文姓名長度是否過長的字串'
            . '我是用來測試中文姓名長度是否過長的字串我是用來測試中文姓名長度是否過長的字串');
    }

    /**
     * 測試驗證英文姓名長度
     */
    public function testValidateNameEnglishLength()
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid name_english length given', 150090029);

        $validator = new Validator;
        $validator->setContainer($this->getContainer());
        $validator->validateNameEnglishLength('name_english,name_english,name_english,name_english,name_english');
    }

    /**
     * 測試驗證國籍長度
     */
    public function testValidateCountryLength()
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid country length given', 150090030);

        $validator = new Validator;
        $validator->setContainer($this->getContainer());
        $validator->validateCountryLength('那美克星達爾悟空聯邦共和國那美克星達爾悟空聯邦共和國那美克星達爾悟空聯邦共和國');
    }

    /**
     * 測試驗證護照長度
     */
    public function testValidatePassportLength()
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid passport length given', 150090031);

        $validator = new Validator;
        $validator->setContainer($this->getContainer());
        $validator->validatePassportLength('RX258971558412567412555411223645632');
    }

    /**
     * 測試驗證身分證字號長度
     */
    public function testValidateIdentityCardLength()
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid identity_card length given', 150090032);

        $validator = new Validator;
        $validator->setContainer($this->getContainer());
        $validator->validateIdentityCardLength('RX258971558412567412555411223645632');
    }

    /**
     * 測試驗證駕照號碼長度
     */
    public function testValidateDriverLicenseLength()
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid driver_license length given', 150090033);

        $validator = new Validator;
        $validator->setContainer($this->getContainer());
        $validator->validateDriverLicenseLength('DavanciAndGGCLADYGAGATAYLORSWwiftifyouwanttodododofogoapps@yahoo.com');
    }

    /**
     * 測試驗證保險號碼長度
     */
    public function testValidateInsuranceCardLength()
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid insurance_card length given', 150090034);

        $validator = new Validator;
        $validator->setContainer($this->getContainer());
        $validator->validateInsuranceCardLength('RX258971558412567412555411223645632');
    }

    /**
     * 測試驗證健保卡字號長度
     */
    public function testValidateHealthCardLength()
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid health_card length given', 150090035);

        $validator = new Validator;
        $validator->setContainer($this->getContainer());
        $validator->validateHealthCardLength('RX258971558412567412555411223645632');
    }

    /**
     * 測試驗證電話長度
     */
    public function testValidateTelephoneLength()
    {
       $this->setExpectedException('InvalidArgumentException', 'Invalid telephone length given', 150090036);

        $validator = new Validator;
        $validator->setContainer($this->getContainer());
        $validator->validateTelephoneLength('19558971558412567412555411223645632');
    }

    /**
     * 測試驗證QQ號碼長度
     */
    public function testValidateQQNumLength()
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid qq_num length given', 150090037);

        $validator = new Validator;
        $validator->setContainer($this->getContainer());
        $validator->validateQQNumLength('19558971558412567412555411223645632');
    }

    /**
     * 測試驗證密碼長度
     */
    public function testValidatePasswordLength()
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid password length given', 150090038);

        $validator = new Validator;
        $validator->setContainer($this->getContainer());
        $validator->validatePasswordLength('testpasswordisinvaliddqwesmlfsefresfeesefkqwke');
    }

    /**
     * 測試驗證備註長度
     */
    public function testValidateNoteLength()
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid note length given', 150090039);

        $validator = new Validator;
        $validator->setContainer($this->getContainer());
        $validator->validateNoteLength('在此測試備註長度不符資料庫規範時，會發生例外。在此測試備註長度不符資料庫規範時，會發生例外。'
            . '在此測試備註長度不符資料庫規範時，會發生例外。在此測試備註長度不符資料庫規範時，會發生例外。'
            . '在此測試備註長度不符資料庫規範時，會發生例外。在此測試備註長度不符資料庫規範時，會發生例外。'
            . '在此測試備註長度不符資料庫規範時，會發生例外。在此測試備註長度不符資料庫規範時，會發生例外。');
    }
}

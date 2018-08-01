<?php
namespace BB\DurianBundle\Tests\User;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Entity\User;

class ValidatorTest extends WebTestCase
{

    private $validator;

    public function setUp()
    {
        parent::setUp();

        $classnames = array(
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserData',
        );

        $this->loadFixtures($classnames);

        $this->validator = $this->getContainer()->get('durian.user_validator');
    }

    /**
     * 測試username長度過長會例外
     */
    public function testUsernameLengthTooLong()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid username length given',
            150010012
        );

        $user = new User();
        $user->setUsername('testlongusername12345')
             ->setPassword('124123')
             ->setAlias('');

        $this->validator->validate($user);
    }

    /**
     * 測試username長度過短會例外
     */
    public function testUsernameLengthTooShort()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid username length given',
            150010012
        );

        $user = new User();
        $user->setUsername('')
             ->setAlias('v')
             ->setPassword('123');

        $this->validator->validate($user);
    }

    /**
     * 測試username不允許特殊符號
     */
    public function testUsernameHasSpecialChar()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid username character given',
            150010013
        );

        $user = new User();
        $user->setUsername('invalid._-')
             ->setAlias('v')
             ->setPassword('124');

        $this->validator->validate($user);
    }

    /**
     * 測試username不允許英文大寫
     */
    public function testUsernameHasCapitalLetter()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid username character given',
            150010013
        );

        $user = new User();
        $user->setUsername('INVALIDNAME')
             ->setAlias('v')
             ->setPassword('124');

        $this->validator->validate($user);
    }

    /**
     * 測試username不能重複
     */
    public function testUsernameAlreadyExists()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Username already exist',
            150010014
        );

        $this->validator->validateUniqueUsername('ztester', 2);
    }

    /**
     * 重複的usename直接update到資料庫會跳Doctrine\DBAL\DBALException
     */
    public function testUsernameAlreadyExistsException()
    {
        try {
            $em = $this->getContainer()->get('doctrine.orm.entity_manager');

            $user = $em->find('BBDurianBundle:User', 8);
            $user->setUsername('ztester');

            $em->flush();
        } catch (\Exception $e) {
            $message = $e->getMessage();

            $this->assertContains('SQLSTATE[23000]', $message);
            $this->assertContains('Integrity constraint violation', $message);
            $this->assertContains('username', $message);
            $this->assertContains('domain', $message);
            $this->assertContains('unique', $message, '', true);
        }
    }

    /**
     * username不能為null
     */
    public function testUsernameCanNotBeNull()
    {
        try {
            $em = $this->getContainer()->get('doctrine.orm.entity_manager');

            $name = null;
            $user = new User();
            $user->setId(11);
            $user->setUsername($name);

            $em->persist($user);
            $em->flush();
        } catch (\Exception $e) {
            $message = $e->getMessage();

            $this->assertContains('SQLSTATE[23000]', $message);
            $this->assertContains('Integrity constraint violation', $message);
            $this->assertContains('username', $message);
            $this->assertContains('NULL', $message);
        }
    }

    /**
     * 測試userPassword長度限制
     */
    public function testValidatePasswordExceedMaxLength()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid password length given',
            150010015
        );

        $user = new User();
        $user->setUsername('simon')
             ->setAlias('v')
             ->setPassword('124d77e8774g7f');

        $this->validator->validate($user);
    }

    /**
     * 測試userPassword符號限制
     */
    public function testValidatePasswordSymbolWhenNewUser()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid password character given',
            150010016
        );

        $user = new User();
        $user->setUsername('simon')
             ->setAlias('v')
             ->setPassword('124#$%^&*');

        $this->validator->validate($user);
    }

    /**
     * password不能為null
     */
    public function testPasswordCanNotBeNull()
    {
        try {
            $em = $this->getContainer()->get('doctrine.orm.entity_manager');

            $pass = null;
            $user = new User();
            $user->setId(11)
                ->setUsername('simon')
                ->setAlias('v')
                ->setPassword($pass)
                ->setDomain(11);

            $em->persist($user);
            $em->flush();
        } catch (\Exception $e) {
            $message = $e->getMessage();

            $this->assertContains('SQLSTATE[23000]', $message);
            $this->assertContains('Integrity constraint violation', $message);
            $this->assertContains('password', $message);
            $this->assertContains('NULL', $message);
        }
    }

    /**
     * 測試alias長度過長會例外
     */
    public function testAliasLengthTooLong()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid alias length given',
            150010017
        );

        $user = new User();
        $user->setUsername('simon')
             ->setPassword('124123')
             ->setAlias('TestingForOutOfAliasLengthLimit');

        $this->validator->validate($user);
    }

    /**
     * 測試alias長度過短會例外
     */
    public function testAliasLengthTooShort()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid alias length given',
            150010017
        );

        $user = new User();
        $user->setUsername('simon')
             ->setPassword('124123')
             ->setAlias('');

        $this->validator->validate($user);
    }

    /**
     * alias不能為null
     */
    public function testAliasCanNotBeNull()
    {
        try {
            $em = $this->getContainer()->get('doctrine.orm.entity_manager');

            $alias = null;
            $user = new User();
            $user->setId(11)
                ->setUsername('simon')
                ->setAlias($alias)
                ->setDomain(11);

            $em->persist($user);
            $em->flush();
        } catch (\Exception $e) {
            $message = $e->getMessage();

            $this->assertContains('SQLSTATE[23000]', $message);
            $this->assertContains('Integrity constraint violation', $message);
            $this->assertContains('alias', $message);
            $this->assertContains('NULL', $message);
        }
    }

    /**
     * 測試email格式錯誤會例外
     */
    public function testEmailWithWorngFormat()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid email given',
            150010127
        );

        $email = 'qwer@';

        $this->validator->validateEmail($email);
    }

    /**
     * 測試email長度過長會例外
     */
    public function testEmailLengthTooLong()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid email length given',
            150010146
        );

        $email = 'invalidinvalidinvalidinvalidinvalidinvalidemaiinvalidinvalidinvalidinvalid' .
            'invalidinvalidemaiinvalidinvalidinvalidinvalidinvalidinvalidemaiinvalidinvalidinvalidinvalid' .
            'invalidinvalidemailinvalidinvalidemailinvalidinvalidinvalidinvalidinvalidinvalidinvalidinvalid' .
            'invalidinvalidinvalidinvalidinvalidemailinvalidinvalidinvalidinvalidinvalidemail@gmail.com';

        $this->validator->validateEmail($email);
    }
}

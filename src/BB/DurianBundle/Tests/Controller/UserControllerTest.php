<?php

namespace BB\DurianBundle\Tests\Controller;

use Symfony\Component\HttpFoundation\Request;
use BB\DurianBundle\Controller\UserController;

class UserControllerTest extends ControllerTest
{
    /**
     * 測試產生使用者id，未帶入role
     * (即將移除,因case 209442,原採用GET,將改為POST)
     */
    public function testGenerateIdWithoutRole()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No role specified',
            150010057
        );

        $parameters = [];

        $query = new Request($parameters);
        $controller = new UserController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->generateIdAction($query);
    }

    /**
     * 測試產生使用者id，未帶入domain
     * (即將移除,因case 209442,原採用GET,將改為POST)
     */
    public function testGenerateIdWithoutDomain()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No domain specified',
            150010100
        );

        $parameters = ['role' => 1];

        $query = new Request($parameters);
        $controller = new UserController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->generateIdAction($query);
    }

    /**
     * 測試產生使用者id，未帶入ip
     * (即將移除,因case 209442,原採用GET,將改為POST)
     */
    public function testGenerateIdWithoutIp()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No client_ip specified',
            150010092
        );

        $parameters = [
            'role' => 1,
            'domain' => 2
        ];

        $query = new Request($parameters);
        $controller = new UserController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->generateIdAction($query);
    }

    /**
     * 測試產生非廳主的使用者id，未帶入parent_id
     * (即將移除,因case 209442,原採用GET,將改為POST)
     */
    public function testGenerateIdWithoutParentId()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No parent_id specified',
            150010036
        );

        $parameters = [
            'role'      => '1',
            'domain'    => 2,
            'client_ip' => '127.0.0.1'
        ];

        $query = new Request($parameters);
        $controller = new UserController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->generateIdAction($query);
    }

    /**
     * 測試產生廳主id，帶入parent_id
     * (即將移除,因case 209442,原採用GET,將改為POST)
     */
    public function testGenerateDomainIdWithParentId()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Domain shall not have parent',
            150010058
        );

        $parameters = [
            'parent_id' => '3',
            'role' => '7',
            'domain' => 2,
            'client_ip' => '127.0.0.1'
        ];

        $query = new Request($parameters);
        $controller = new UserController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->generateIdAction($query);
    }

    /**
     * 測試產生廳主子帳號id，未帶入parent_id
     * (即將移除,因case 209442,原採用GET,將改為POST)
     */
    public function testGenerateDomainSubUserIdWithoutParentId()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No parent_id specified',
            150010036
        );

        $parameters = [
            'role' => '7',
            'sub' => '1',
            'domain' => 2,
            'client_ip' => '127.0.0.1'
        ];

        $query = new Request($parameters);
        $controller = new UserController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->generateIdAction($query);
    }

    /**
     * 測試產生使用者id，未帶入role
     */
    public function testGenerateUserIdWithoutRole()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No role specified',
            150010163
        );

        $request = new Request();
        $controller = new UserController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->generateUserIdAction($request);
    }

    /**
     * 測試產生使用者id，未帶入domain
     */
    public function testGenerateUserIdWithoutDomain()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No domain specified',
            150010164
        );

        $parameters = ['role' => 1];

        $request = new Request([], $parameters);
        $controller = new UserController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->generateUserIdAction($request);
    }

    /**
     * 測試產生使用者id，未帶入ip
     */
    public function testGenerateUserIdWithoutIp()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No client_ip specified',
            150010165
        );

        $parameters = [
            'role' => 1,
            'domain' => 2
        ];

        $request = new Request([], $parameters);
        $controller = new UserController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->generateUserIdAction($request);
    }

    /**
     * 測試產生非廳主的使用者id，未帶入parent_id
     */
    public function testGenerateUserIdWithoutParentId()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No parent_id specified',
            150010166
        );

        $parameters = [
            'role'      => '1',
            'domain'    => 2,
            'client_ip' => '127.0.0.1'
        ];

        $request = new Request([], $parameters);
        $controller = new UserController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->generateUserIdAction($request);
    }

    /**
     * 測試產生廳主id，帶入parent_id
     */
    public function testGenerateUserIdDomainIdWithParentId()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Domain shall not have parent',
            150010167
        );

        $parameters = [
            'parent_id' => '3',
            'role' => '7',
            'domain' => 2,
            'client_ip' => '127.0.0.1'
        ];

        $request = new Request([], $parameters);
        $controller = new UserController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->generateUserIdAction($request);
    }

    /**
     * 測試產生廳主子帳號id，未帶入parent_id
     */
    public function testGenerateUserIdDomainSubUserIdWithoutParentId()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No parent_id specified',
            150010166
        );

        $parameters = [
            'role' => '7',
            'sub' => '1',
            'domain' => 2,
            'client_ip' => '127.0.0.1'
        ];

        $request = new Request([], $parameters);
        $controller = new UserController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->generateUserIdAction($request);
    }

    /**
     * 測試新增使用者時alias輸入非UTF8
     */
    public function testNewUserAliasNotUtf8()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'String must use utf-8 encoding',
            150610002
        );

        $parameters = [
            'parent_id' => '7',
            'username'  => 'chosen1',
            'password'  => 'chosen1',
            'alias'     => mb_convert_encoding('e龜龍鱉', 'GB2312', 'UTF-8'),
            'currency'  => 'TWD'
        ];

        $request = new Request([], $parameters);
        $controller = new UserController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request);
    }

    /**
     * 測試新增使用者時alias輸入特殊字元
     */
    public function testNewUserAliasWithSpecialChar()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid alias character given',
            150010018
        );

        $parameters = [
            'parent_id' => '7',
            'username'  => 'chosen1',
            'password'  => 'chosen1',
            'alias'     => '><"=',
            'currency'  => 'TWD'
        ];

        $request = new Request([], $parameters);
        $controller = new UserController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request);
    }

    /**
     * 測試新增廳主時角色代入錯誤
     */
    public function testNewDomainWithWrongRole()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No role specified',
            150010057
        );

        $parameters = [
            'username' => 'domainator2',
            'password' => 'domainator',
            'alias' => 'domainator2',
            'currency' => 'CNY',
            'login_code' => 'd2',
            'cash' => ['currency' => 'CNY'],
            'sharelimit' => [
                1 => [
                    'upper' => 100,
                    'lower' => 101,
                    'parent_upper' => 100,
                    'parent_lower' => 0
                ]
            ],
        ];

        $request = new Request([], $parameters);
        $controller = new UserController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request);
    }

    /**
     * 測試新增廳主時代碼過短
     */
    public function testDomainSubButLoginCodeIsTooShort()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid login code',
            150360020
        );

        $parameters = [
            'user_id' => 888765,
            'role' => 7,
            'login_code' => 'b',
            'username' => 'invaliddomain',
            'password' => 'newpassword',
            'alias' => '無效的代碼'
        ];

        $request = new Request([], $parameters);
        $controller = new UserController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request);
    }

    /**
     * 測試新增廳主時代碼過長
     */
    public function testDomainSubButLoginCodeIsTooLong()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid login code',
            150360020
        );

        $parameters = [
            'user_id' => 888765,
            'role' => 7,
            'login_code' => 'd2ab',
            'username' => 'invaliddomain',
            'password' => 'newpassword',
            'alias' => '無效的代碼'
        ];

        $request = new Request([], $parameters);
        $controller = new UserController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request);
    }

    /**
     * 測試新增廳主時代碼不符規則
     */
    public function testCreateDomainButLoginCodeNotMatchRule()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid login code character given',
            150360021
        );

        $parameters = [
            'user_id' => 888765,
            'role' => 7,
            'login_code' => 'QAQ',
            'username' => 'invaliddomain',
            'password' => 'newpassword',
            'alias' => '無效的代碼'
        ];

        $request = new Request([], $parameters);
        $controller = new UserController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request);
    }

    /**
     * 測試新增使用者(帳號長度小於contrllor限制)
     */
    public function testNewUserWithTooShortUsername()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid username length given',
            150010012
        );

        $parameters = [
            'parent_id' => 7,
            'username'  => 'jpm',
            'password'  => 'chosen1',
            'alias'     => 'chosen1'
        ];

        $request = new Request([], $parameters);
        $controller = new UserController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request);
    }

    /**
     * 測試新增使用者時Currency不合法
     */
    public function testNewUserWithIllegalCurrency()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Illegal currency',
            150010101
        );

        $parameters = [
            'parent_id' => 7,
            'username'  => 'jpmm',
            'password'  => 'chosen1',
            'alias'     => 'chosen1',
            'currency'  => 'T5T'
        ];

        $request = new Request([], $parameters);
        $controller = new UserController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request);
    }

    /**
     * 測試新增使用者時Currency為空白
     */
    public function testNewUserWithIllegalEmptyCurrency()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Illegal currency',
            150010101
        );

        $parameters = [
            'parent_id' => 7,
            'username'  => 'jpmm',
            'password'  => 'chosen1',
            'alias'     => 'chosen1',
            'currency'  => ' '
        ];

        $request = new Request([], $parameters);
        $controller = new UserController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request);
    }

    /**
     * 測試新增使用者(帳號為空白)
     */
    public function testNewUserWithBlankUsername()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid username length given',
            150010012
        );

        $parameters = [
            'parent_id' => 7,
            'username'  => '',
            'password'  => 'chosen1',
            'alias'     => 'chosen1'
        ];

        $request = new Request([], $parameters);
        $controller = new UserController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request);
    }

    /**
     * 測試新增使用者(不傳帳號)
     */
    public function testNewUserWithoutUsername()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid username length given',
            150010012
        );

        $parameters = [
            'parent_id' => 7,
            'password'  => 'chosen1',
            'alias'     => 'chosen1'
        ];

        $request = new Request([], $parameters);
        $controller = new UserController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request);
    }

    /**
     * 測試新增使用者(密碼長度小於contrllor限制)
     */
    public function testNewUserWithTooShortPassword()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid password length given',
            150010015
        );

        $parameters = [
            'parent_id' => 7,
            'username'  => 'test',
            'password'  => '15484',
            'alias'     => 't'
        ];

        $request = new Request([], $parameters);
        $controller = new UserController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request);
    }

    /**
     * 測試新增使用者(密碼含大寫)
     */
    public function testNewUserWithUpperPassword()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid password character given',
            150010016
        );

        $parameters = [
            'parent_id' => 7,
            'username'  => 'test',
            'password'  => '1AF484',
            'alias'     => 't'
        ];

        $request = new Request([], $parameters);
        $controller = new UserController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request);
    }

    /**
     * 測試新增使用者(密碼為空白)
     */
    public function testNewUserWithBlankPassword()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid password length given',
            150010015
        );

        $parameters = [
            'parent_id' => 7,
            'username'  => 'chosen1',
            'password'  => '',
            'alias'     => 'chosen1'
        ];

        $request = new Request([], $parameters);
        $controller = new UserController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request);
    }

    /**
     * 測試新增使用者(暱稱為空白)
     */
    public function testNewUserWithBlankAlias()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid alias length given',
            150010017
        );

        $parameters = [
            'parent_id' => 7,
            'username'  => 'chosen1',
            'password'  => 'kd45f8',
            'alias'     => ''
        ];

        $request = new Request([], $parameters);
        $controller = new UserController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request);
    }

    /**
     * 測試新增使用者(暱稱過長)
     */
    public function testNewUserWithOverLengthAlias()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid alias length given',
            150010017
        );

        $parameters = [
            'parent_id' => 7,
            'username'  => 'chosen1',
            'password'  => 'kd45f8',
            'alias'     => '1234567890123456789012345678912'
        ];

        $request = new Request([], $parameters);
        $controller = new UserController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request);
    }

    /**
     * 測試新增非廳主使用者，未帶parent_id
     */
    public function testCreateUserWithoutParentId()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No parent_id specified',
            150010036
        );

        $parameters = [
            'username' => 'testerson',
            'password' => 'tester1on',
            'alias'    => 'testerSon',
            'role'     => 1
        ];

        $request = new Request([], $parameters);
        $controller = new UserController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request);
    }

    /**
     * 測試新增子帳號但沒有帶parent_id
     */
    public function testNewSubWithoutParentId()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No parent_id specified',
            150010036
        );

        $parameters = [
            'username' => 'testerson',
            'password' => 'tester1on',
            'alias'    => 'testerSon',
            'role'     => 7,
            'sub'      => 1
        ];

        $request = new Request([], $parameters);
        $controller = new UserController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request);
    }

    /**
     * 測試新增使用者指定的ID非數字
     */
    public function testNewUserWithUserIdIsNotNumeric()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid user_id',
            150010055
        );

        $parameters = [
            'username' => 'test',
            'password' => 'test123',
            'alias' => 'test',
            'user_id' => '2007d',
            'role' => 7,
            'login_code' => 'tt'
        ];

        $request = new Request([], $parameters);
        $controller = new UserController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request);
    }


    /**
     * 測試新增使用者未指定role
     */
    public function testNewUserWithoutRole()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No role specified',
            150010057
        );

        $parameters = [
            'username' => 'test',
            'password' => 'test123',
            'alias'    => 'test',
            'user_id'  => '123'
        ];

        $request = new Request([], $parameters);
        $controller = new UserController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request);
    }

    /**
     * 測試新增使用者role帶入空字串
     */
    public function testNewUserWithoutEmptyRole()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No role specified',
            150010057
        );

        $parameters = [
            'username' => 'test',
            'password' => 'test123',
            'alias'    => 'test',
            'user_id'  => '123',
            'role'     => ''
        ];

        $request = new Request([], $parameters);
        $controller = new UserController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request);
    }

    /**
     * 測試新增使用者，user_detail[name_real] 帶有特殊字元
     */
    public function testCreateUserUserDetailNameRealWithSpecialCharacter()
    {
        $userDetail = ['name_real' => '\\0測試'];

        $params = [
            'user_id' => '1',
            'username' => 'abcd',
            'name' => 'abcd',
            'alias' => 'abcd',
            'disabled_password' => true,
            'verify_blacklist' => false,
            'role' => 7,
            'sub' => false,
            'login_code' => 'ab',
            'client_ip' => '1.1.1.1',
            'user_detail' => $userDetail
        ];

        // ActivateSLNext
        $activateSLNext = $this->getMockBuilder('BB\DurianBundle\Share\ActivateSLNext')
            ->disableOriginalConstructor()
            ->getMock();
        $activateSLNext->method('isUpdating')
            ->willReturn(null);
        $activateSLNext->method('hasBeenUpdated')
            ->willReturn(true);

        // Connection
        $dbConnection = $this->getMockBuilder('Doctrine\DBAL\Connection')
            ->disableOriginalConstructor()
            ->getMock();
        $dbConnection->method('isTransactionActive')
            ->willReturn(null);

        // ClassMetadata
        $classMetadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();
        $classMetadata->method('getFieldMapping')
            ->willReturn(['length' => 100]);

        // EntityRepository
        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $entityRepo->method('findOneBy')
            ->willReturn(null);

        // EntityManager
        $entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $entityManager->method('getRepository')
            ->willReturn($entityRepo);
        $entityManager->method('getConnection')
            ->willReturn($dbConnection);
        $entityManager->method('getClassMetadata')
            ->willReturn($classMetadata);
        $entityManager->method('persist')
            ->willReturn(1);
        $entityManager->method('flush')
            ->willReturn(1);

        $request = new Request([], $params);
        $controller = new UserController();
        $container = static::$kernel->getContainer();
        $container->set('durian.activate_sl_next', $activateSLNext);
        $container->set('doctrine.orm.default_entity_manager', $entityManager);
        $container->set('doctrine.orm.share_entity_manager', $entityManager);
        $container->set('request', $request);
        $controller->setContainer($container);

        $output = $controller->createAction($request);
        $output = json_decode($output->getContent(), true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('150090042', $output['code']);
        $this->assertEquals('Invalid name_real', $output['msg']);
    }

   /**
    * 測試取得多使用者，但帶入非陣列
    */
    public function testGetMultipleUserNotArray()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid users',
            150010069
        );

        $parameters = ['users' => '3'];

        $query = new Request($parameters);
        $controller = new UserController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->getUsersAction($query);
    }

    /**
     * 測試編輯使用者alias輸入非UTF8
     */
    public function testSetUserAliasNotUtf8()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'String must use utf-8 encoding',
            150610002
        );

        $parameters = ['alias' => mb_convert_encoding('e龜龍鱉', 'GB2312', 'UTF-8')];

        $request = new Request([], $parameters);
        $controller = new UserController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->setUserAction($request, 1);
    }

    /**
     * 測試編輯使用者alias輸入特殊字元
     */
    public function testSetUserAliasWithSpecialChar()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid alias character given',
            150010018
        );

        $parameters = ['alias' => "><='"];

        $request = new Request([], $parameters);
        $controller = new UserController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->setUserAction($request, 1);
    }

    /**
     * 測試設定使用者屬性時帶入錯誤的currency
     */
    public function testSetUserAliasWithWrongCurrency()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Illegal currency',
            150010101
        );

        $parameters = ['currency' => 'T7D'];

        $request = new Request([], $parameters);
        $controller = new UserController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->setUserAction($request, 1);
    }

    /**
     * 測試設定使用者屬性時帶入空白的currency
     */
    public function testSetUserAliasWithEmptyCurrency()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Illegal currency',
            150010101
        );

        $parameters = ['currency' => ' '];

        $request = new Request([], $parameters);
        $controller = new UserController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->setUserAction($request, 1);
    }

    /**
     * 測試list api不帶parentId的查詢
     */
    public function testListWithoutParentId()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No parent_id specified',
            150010036
        );

        $parameters = [];

        $query = new Request($parameters);
        $controller = new UserController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->listAction($query);
    }

    /**
     * 測試取得下層帳號，但search_field跟search_value變數量不一致
     */
    public function testListWithSearchFieldAndSearchValueNotMatch()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Search field and value did not match',
            150010083
        );

        $parameters = [
            'parent_id' => 3,
            'depth' => 1,
            'search_field' => [
                'username',
                'alias'
            ],
            'search_value' => ['wtester']
        ];

        $query = new Request($parameters);
        $controller = new UserController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->listAction($query);
    }

    /**
     * 測試檢查使用者資訊唯一，沒傳domain
     */
    public function testCheckUserUniqueWithoutDomain()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No domain specified',
            150010100
        );

        // 沒傳domain
        $parameters = ['fields' => ['username' => 'alibaba']];

        $query = new Request($parameters);
        $controller = new UserController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->userCheckUniqueAction($query);
    }

    /**
     * 測試檢查使用者資訊唯一，沒傳fields
     */
    public function testCheckUserUniqueWithoutFields()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No fields specified',
            150010038
        );

        // 沒傳fields
        $parameters = ['domain' => '2'];

        $query = new Request($parameters);
        $controller = new UserController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->userCheckUniqueAction($query);
    }

    /**
     * 測試檢查使用者資訊唯一，傳入不合法fields
     */
    public function testCheckUserUniqueWithInvalidFields()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No fields specified',
            150010038
        );

        // 不支援的field
        $parameters = [
            'domain' => '101',
            'fields' => ['birthday' => '2000-10-10']
        ];

        $query = new Request($parameters);
        $controller = new UserController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->userCheckUniqueAction($query);
    }

    /**
     * 測試取指定廳時間點後修改資料的會員,但沒帶起始時間點
     */
    public function testGetModifiedUserByDomainWithoutBeginTime()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No begin_at specified',
            150010065
        );

        $query = new Request();
        $controller = new UserController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->getModifiedUserByDomainAction($query, 2);
    }

    /**
     * 測試取指定廳時間點後刪除的會員,但沒帶起始時間點
     */
    public function testGetRemovedUserByDomainWithoutBeginTime()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No begin_at specified',
            150010065
        );

        $query = new Request();
        $controller = new UserController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->getRemovedUserByDomainAction($query, 2);
    }

    /**
     * 測試取指定廳時間區間內新增會員的詳細相關資訊,但沒帶起始時間點
     */
    public function testGetMemberDetaiWithoutStartTime()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No start_at specified',
            150010067
        );

        // 沒帶start_at
        $parameters = ['end_at' => '2015-03-04T12:13:55+0800'];

        $query = new Request($parameters);
        $controller = new UserController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->getMemberDetailAction($query, 2);
    }

    /**
     * 測試取指定廳時間區間內新增會員的詳細相關資訊,但沒帶結束時間點
     */
    public function testGetMemberDetaiWithoutEndTime()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No end_at specified',
            150010068
        );

        // 沒帶end_at
        $parameters = ['start_at' => '2015-03-04T12:13:55+0800'];

        $query = new Request($parameters);
        $controller = new UserController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->getMemberDetailAction($query, 2);
    }

    /**
     * 取指定時間點後刪除的會員,但沒帶時間點
     */
    public function testGetRemovedUserByTimeWithoutModifiedAt()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No removed_at specified',
            150010129
        );

        $query = new Request();
        $controller = new UserController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->getRemovedUserByTimeAction($query);
    }

    /**
     * 取指定時間點後刪除的會員,但帶不合法的時間
     */
    public function testGetRemovedUserByTimeWithInvalidModifiedAt()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid removed_at given',
            150010130
        );

        $parameters = ['removed_at' => 'abc'];

        $query = new Request($parameters);
        $controller = new UserController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->getRemovedUserByTimeAction($query);
    }

    /**
     * 根據使用者id，取得使用者出入款統計資料，但user_id不合法
     */
    public function testGetStatButInvalidUserId()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid user_id',
            150010055
        );

        $parameters = [
            'user_id' => '6'
        ];

        $query = new Request($parameters);
        $controller = new UserController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->getStatAction($query);
    }

    /**
     * 根據使用者id，取得使用者出入款統計資料，但未指定user_id
     */
    public function testGetStatButNoUserIdSpecified()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No user_id specified',
            150010137
        );

        $parameters = [
            'user_id' => []
        ];

        $query = new Request($parameters);
        $controller = new UserController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->getStatAction($query);
    }

    /**
     * 測試取得多個被刪除使用者資訊，帶入非陣列
     */
    public function testGetRemovedUserButIdsNotArray()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid users',
            150010144
        );

        $parameters = ['users' => 123];

        $query = new Request($parameters);
        $controller = new UserController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->getRemovedUsersAction($query);
    }

    /**
     * 測試取得多個被刪除使用者資訊，帶入空陣列
     */
    public function testGetRemovedUserButEmptyArray()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid users',
            150010144
        );

        $parameters = ['users' => []];

        $query = new Request($parameters);
        $controller = new UserController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->getRemovedUsersAction($query);
    }

    /**
     * 測試取得使用者名稱，users不合法
     */
    public function testGetUsernameWithInvalidUsers()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid users',
            150010149
        );

        $parameters = ['users' => 123];

        $query = new Request($parameters);
        $controller = new UserController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->getUsernameAction($query);
    }

    /**
     * 測試指定廳由帳號取得體系資料，未帶入domain
     */
    public function testGetHierarchyByDomainWithoutDomain()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No domain specified',
            150010147
        );

        $parameters = ['username' => 'test'];

        $query = new Request($parameters);
        $controller = new UserController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->getHierarchyByDomainAction($query);
    }
}

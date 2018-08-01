<?php

namespace BB\DurianBundle\Tests\Controller;

use Symfony\Component\HttpFoundation\Request;
use \BB\DurianBundle\Controller\CustomizeController;

class CustomizeControllerTest extends ControllerTest
{
    /**
     * 測試取得指定廳內會員的詳細資訊，未帶入domain
     */
    public function testGetUserDetailByDomainWithoutDomain()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No domain specified',
            150460002
        );

        $now = new \DateTime('now');

        $params = [
            'start_at' => $now->format('Y-m-d H:i:s'),
            'end_at' => $now->format('Y-m-d H:i:s')
        ];

        $request = new Request($params);
        $controller = new CustomizeController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->getUserDetailByDomainAction($request);
    }

    /**
     * 測試取得指定廳內會員的詳細資訊，帶入不合法domain
     */
    public function testGetUserDetailByDomainWithInvalidDomain()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid domain',
            150460003
        );

        $now = new \DateTime('now');

        $params = [
            'domain' => -1,
            'start_at' => $now->format('Y-m-d H:i:s'),
            'end_at' => $now->format('Y-m-d H:i:s')
        ];

        $request = new Request($params);
        $controller = new CustomizeController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->getUserDetailByDomainAction($request);
    }

    /**
     * 測試取得指定廳內會員的詳細資訊，未帶入start_at, end_at或usernames
     */
    public function testGetUserDetailByDomainWithoutTimeOrUsernames()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No start_at or end_at or usernames specified',
            150460004
        );

        $params = [
            'domain' => 1,
        ];

        $request = new Request($params);
        $controller = new CustomizeController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->getUserDetailByDomainAction($request);
    }

    /**
     * 測試複製使用者未帶舊使用者id
     */
    public function testCopyUserWithoutOldUserId()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No old_user_id specified',
            150460005
        );

        $request = new Request();
        $controller = new CustomizeController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->copyUserAction($request);
    }

    /**
     * 測試複製使用者未帶新使用者id
     */
    public function testCopyUserWithoutNewUserId()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No new_user_id specified',
            150460006
        );

        $params = ['old_user_id' => 1];

        $request = new Request([], $params);
        $controller = new CustomizeController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->copyUserAction($request);
    }

    /**
     * 測試複製使用者未帶新上層使用者id
     */
    public function testCopyUserWithoutNewParentId()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No new_parent_id specified',
            150460007
        );

        $params = [
            'old_user_id' => 1,
            'new_user_id' => 2
        ];

        $request = new Request([], $params);
        $controller = new CustomizeController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->copyUserAction($request);
    }

    /**
     * 測試複製使用者未帶使用者帳號
     */
    public function testCopyUserWithoutUsername()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No username specified',
            150460008
        );

        $params = [
            'old_user_id' => 1,
            'new_user_id' => 2,
            'new_parent_id' => 3
        ];

        $request = new Request([], $params);
        $controller = new CustomizeController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->copyUserAction($request);
    }

    /**
     * 測試複製使用者未帶來源廳主id
     */
    public function testCopyUserWithoutSourceDomain()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No source_domain specified',
            150460009
        );

        $params = [
            'old_user_id' => 1,
            'new_user_id' => 2,
            'new_parent_id' => 3,
            'username' => 'test123'
        ];

        $request = new Request([], $params);
        $controller = new CustomizeController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->copyUserAction($request);
    }

    /**
     * 測試複製使用者未帶目標廳主id
     */
    public function testCopyUserWithoutTragetDomain()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No target_domain specified',
            150460010
        );

        $params = [
            'old_user_id' => 1,
            'new_user_id' => 2,
            'new_parent_id' => 3,
            'username' => 'test123',
            'source_domain' => 4
        ];

        $request = new Request([], $params);
        $controller = new CustomizeController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->copyUserAction($request);
    }

    /**
     * 測試複製使用者未帶role
     */
    public function testCopyUserWithoutRole()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No role specified',
            150460011
        );

        $params = [
            'old_user_id' => 1,
            'new_user_id' => 2,
            'new_parent_id' => 3,
            'username' => 'test123',
            'source_domain' => 4,
            'target_domain' => 5
        ];

        $request = new Request([], $params);
        $controller = new CustomizeController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->copyUserAction($request);
    }

    /**
     * 測試複製使用者帶不合法的舊使用者id
     */
    public function testCopyUserWithInvalidOldUserId()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid oldUserId',
            150460012
        );

        $params = [
            'old_user_id' => '1a',
            'new_user_id' => 2,
            'new_parent_id' => 3,
            'username' => 'test123',
            'source_domain' => 4,
            'target_domain' => 5,
            'role' => 1
        ];

        $request = new Request([], $params);
        $controller = new CustomizeController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->copyUserAction($request);
    }

    /**
     * 測試複製使用者帶不合法的新使用者id
     */
    public function testCopyUserWithInvalidNewUserId()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid newUserId',
            150460013
        );

        $params = [
            'old_user_id' => 1,
            'new_user_id' => '2a',
            'new_parent_id' => 3,
            'username' => 'test123',
            'source_domain' => 4,
            'target_domain' => 5,
            'role' => 1
        ];

        $request = new Request([], $params);
        $controller = new CustomizeController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->copyUserAction($request);
    }

    /**
     * 測試複製使用者帶不合法的新上層使用者id
     */
    public function testCopyUserWithInvalidNewParentUserId()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid newParentId',
            150460014
        );

        $params = [
            'old_user_id' => 1,
            'new_user_id' => 2,
            'new_parent_id' => '3a',
            'username' => 'test123',
            'source_domain' => 4,
            'target_domain' => 5,
            'role' => 1
        ];

        $request = new Request([], $params);
        $controller = new CustomizeController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->copyUserAction($request);
    }

    /**
     * 測試複製使用者帶不合法的來源廳主id
     */
    public function testCopyUserWithInvalidSourceDomain()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid sourceDomain',
            150460015
        );

        $params = [
            'old_user_id' => 1,
            'new_user_id' => 2,
            'new_parent_id' => 3,
            'username' => 'test123',
            'source_domain' => '4a',
            'target_domain' => 5,
            'role' => 1
        ];

        $request = new Request([], $params);
        $controller = new CustomizeController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->copyUserAction($request);
    }

    /**
     * 測試複製使用者帶不合法的目標廳主id
     */
    public function testCopyUserWithInvalidTragetDomain()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid targetDomain',
            150460016
        );

        $params = [
            'old_user_id' => 1,
            'new_user_id' => 2,
            'new_parent_id' => 3,
            'username' => 'test123',
            'source_domain' => 4,
            'target_domain' => 'a5',
            'role' => 1
        ];

        $request = new Request([], $params);
        $controller = new CustomizeController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->copyUserAction($request);
    }

    /**
     * 測試複製使用者帶不合法的使用者帳號
     */
    public function testCopyUserWithInvalidUsername()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid username',
            150010013
        );

        $params = [
            'old_user_id' => 1,
            'new_user_id' => 2,
            'new_parent_id' => 3,
            'username' => 'test123A',
            'source_domain' => 4,
            'target_domain' => 5,
            'role' => 1
        ];

        $request = new Request([], $params);
        $controller = new CustomizeController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->copyUserAction($request);
    }

    /**
     * 測試複製使用者未帶分層id
     */
    public function testCopyUserWithoutPresetLevel()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No preset_level specified',
            150460017
        );

        $params = [
            'old_user_id' => 1,
            'new_user_id' => 2,
            'new_parent_id' => 3,
            'username' => 'test123',
            'source_domain' => 4,
            'target_domain' => 5,
            'role' => 1
        ];

        $request = new Request([], $params);
        $controller = new CustomizeController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->copyUserAction($request);
    }

    /**
     * 測試回傳廳指定時間後未登入總會員數，未指定廳
     */
    public function testGetInactivatedUserByDomainWithoutDomain()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No domain specified',
            150460018
        );

        $container = static::$kernel->getContainer();

        $request = new Request();
        $controller = new CustomizeController();
        $controller->setContainer($container);
        $controller->getInactivatedUserByDomainAction($request);
    }

    /**
     * 測試回傳廳指定時間後未登入總會員數，未指定時間
     */
    public function testGetInactivatedUserByDomainWithoutTime()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid date',
            150460019
        );

        $query = ['domain' => 2];
        $request = new Request($query);
        $controller = new CustomizeController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->getInactivatedUserByDomainAction($request);
    }
}

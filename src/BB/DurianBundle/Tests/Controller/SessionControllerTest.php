<?php

namespace BB\DurianBundle\Tests\Controller;

use Symfony\Component\HttpFoundation\Request;
use BB\DurianBundle\Controller\SessionController;

class SessionControllerTest extends ControllerTest
{
    /**
     * 測試用上層使用者刪除session，depth帶非數字
     */
    public function testDeleteByParentWithNotNumericDepth()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid depth',
            150330007
        );

        $param = ['depth' => 'test'];

        $request = new Request([], $param);
        $controller = new SessionController();
        $controller->setContainer(static::$kernel->getContainer());
        $controller->deleteByParentAction($request);
    }

    /**
     * 測試用上層使用者刪除session，depth負數
     */
    public function testDeleteByParentWithNegativeDepth()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid depth',
            150330007
        );

        $param = ['depth' => -1];

        $request = new Request([], $param);
        $controller = new SessionController();
        $controller->setContainer(static::$kernel->getContainer());
        $controller->deleteByParentAction($request);
    }

    /**
     * 測試用上層使用者刪除session，depth與role同時帶
     */
    public function testDeleteByParentWithDepthAndRole()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Depth and role cannot be chosen at same time',
            150330008
        );

        $param = [
            'depth' => 1,
            'role' => 1
        ];

        $request = new Request([], $param);
        $controller = new SessionController();
        $controller->setContainer(static::$kernel->getContainer());
        $controller->deleteByParentAction($request);
    }

    /**
     * 測試用上層使用者刪除session，role 帶非數字
     */
    public function testDeleteByParentWithNotNumericRole()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid role',
            150330009
        );

        $param = ['role' => 'test'];

        $request = new Request([], $param);
        $controller = new SessionController();
        $controller->setContainer(static::$kernel->getContainer());
        $controller->deleteByParentAction($request);
    }

    /**
     * 測試用上層使用者刪除session，role 帶負數
     */
    public function testDeleteByParentWithNegativeRole()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid role',
            150330009
        );

        $param = ['role' => -1];

        $request = new Request([], $param);
        $controller = new SessionController();
        $controller->setContainer(static::$kernel->getContainer());
        $controller->deleteByParentAction($request);
    }

    /**
     * 測試用session id建立 Session，但未帶user_id參數
     */
    public function testCreateBySessionIdWithoutUserId()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid user_id',
            150330010
        );

        $request = new Request([]);
        $controller = new SessionController();
        $controller->setContainer(static::$kernel->getContainer());
        $controller->createBySessionIdAction($request, '620318792ab2389366f7d9c6e0218d1c902564ac');
    }

    /**
     * 測試依據使用者帳號回傳線上人數列表，但未帶username參數
     */
    public function testOnlineListByUsernameWithoutUsername()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No username specified',
            150330013
        );

        $request = new Request([]);
        $controller = new SessionController();
        $controller->setContainer(static::$kernel->getContainer());
        $controller->getOnlineListByUsernameAction($request);
    }
}

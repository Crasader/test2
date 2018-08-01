<?php

namespace BB\DurianBundle\Tests\Controller;

use Symfony\Component\HttpFoundation\Request;
use BB\DurianBundle\Controller\RemovePlanUserController;

class RemovePlanUserControllerTest extends ControllerTest
{
    /**
     * 測試更新刪除使用者狀態，沒有帶狀態
     */
    public function testUpdatePlanUserStatusWithoutStatus()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No removePlanUser status specified',
            150770001
        );

        $request = new Request();
        $controller = new RemovePlanUserController();
        $controller->setContainer(static::$kernel->getContainer());
        $controller->updatePlanUserStatusAction($request, 499);
    }
}

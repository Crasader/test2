<?php

namespace BB\DurianBundle\Tests\Controller;

use BB\DurianBundle\Controller\WalletController;
use Symfony\Component\HttpFoundation\Request;

class WalletControllerTest extends ControllerTest
{
    /**
     * 測試取得使用者存提款紀錄，但未帶userId
     */
    public function testGetDepositWithdrawWithoutUserId()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No user_id specified',
            150070035
        );

        $query = new Request();

        $controller = new WalletController();
        $controller->setContainer(static::$kernel->getContainer());
        $controller->getDepositWithdrawAction($query);
    }
}

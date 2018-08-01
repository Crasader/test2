<?php

namespace BB\DurianBundle\Tests\Controller;

use Symfony\Component\HttpFoundation\Request;
use BB\DurianBundle\Controller\FreeTransferWalletController;

class FreeTransferWalletControllerTest extends ControllerTest
{
    /**
     * 測試設定廳開放錢包狀態，錢包狀態格式錯誤
     */
    public function testSetDomainWalletStatusWithErrorWalletStatus()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid domain wallet status',
            150960003
        );

        $container = static::$kernel->getContainer();

        $request = new Request();
        $controller = new FreeTransferWalletController();
        $controller->setContainer($container);
        $controller->setDomainWalletAction($request, 2);
    }

    /**
     * 測試設定使用者最後登入遊戲，未帶遊戲平台編號
     */
    public function testSetUserLastGameCodeWithoutGameCode()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No game code specified',
            150960009
        );

        $container = static::$kernel->getContainer();

        $request = new Request();
        $controller = new FreeTransferWalletController();
        $controller->setContainer($container);
        $controller->setUserLastGameCode($request, 2);
    }
}

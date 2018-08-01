<?php

namespace BB\DurianBundle\Tests\Controller;

use Symfony\Component\HttpFoundation\Request;
use BB\DurianBundle\Controller\ChatRoomController;

class ChatRoomControllerTest extends ControllerTest
{
    /**
     * 測試設定禁言時間但未帶時間參數
     */
    public function testSetBanAtWithoutAt()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No ban_at specified',
            150750003
        );

        $container = static::$kernel->getContainer();
        $request = new Request();
        $controller = new ChatRoomController();
        $controller->setContainer($container);
        $controller->setBanAtAction($request, 8);
    }

    /**
     * 測試回傳使用者聊天室已禁言列表時代入非法參數
     */
    public function testGetBanListWithInvalidDomain()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid domain',
            150750005
        );

        $parameters = ['domain' => 'HellloHowAreYou'];

        $container = static::$kernel->getContainer();
        $request = new Request($parameters);
        $controller = new ChatRoomController();
        $controller->setContainer($container);
        $controller->getBanListAction($request);
    }
}

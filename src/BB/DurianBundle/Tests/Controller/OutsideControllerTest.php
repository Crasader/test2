<?php

namespace BB\DurianBundle\Tests\Controller;

use BB\DurianBundle\Controller\OutsideController;
use Symfony\Component\HttpFoundation\Request;

class OutsideControllerTest extends ControllerTest
{
    /**
     * 測試取得使用者明細，但opcode不合法
     */
    public function testGetEntriesButOpcodeInvalid()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid opcode',
            150820004
        );

        $parameters = ['opcode' => 'HellloHowAreYou'];

        $container = static::$kernel->getContainer();
        $request = new Request($parameters);
        $controller = new OutsideController();
        $controller->setContainer($container);
        $controller->getEntriesAction($request, 1);
    }

    /**
     * 測試透過red_id取得使用者明細，但沒帶ref_id
     */
    public function testGetEntriesByRefIdWithoutRefId()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No ref_id specified',
            150820005
        );

        $container = static::$kernel->getContainer();
        $request = new Request();
        $controller = new OutsideController();
        $controller->setContainer($container);
        $controller->getEntriesByRefIdAction($request);
    }

    /**
     * 測試取得使用者明細，但ref_id不合法
     */
    public function testGetEntriesButRefIdInvalid()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid ref_id',
            150820006
        );

        $parameters = ['ref_id' => 'HellloHowAreYou'];

        $container = static::$kernel->getContainer();
        $request = new Request($parameters);
        $controller = new OutsideController();
        $controller->setContainer($container);
        $controller->getEntriesByRefIdAction($request);
    }

    /**
     * 測試取得明細加總，但opcode不合法
     */
    public function testGetTotalAmountButOpcodeInvalid()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid opcode',
            150820008
        );

        $parameters = ['opcode' => 'HellloHowAreYou'];

        $container = static::$kernel->getContainer();
        $request = new Request($parameters);
        $controller = new OutsideController();
        $controller->setContainer($container);
        $controller->getTotalAmountAction($request, 1);
    }
}

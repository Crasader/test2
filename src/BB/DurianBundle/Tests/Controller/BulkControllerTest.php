<?php

namespace BB\DurianBundle\Tests\Controller;

use Symfony\Component\HttpFoundation\Request;
use BB\DurianBundle\Controller\BulkController;

class BulkControllerTest extends ControllerTest
{
    /**
     * 測試根據廳與使用者帳號，回傳使用者id，未帶入domain
     */
    public function testFetchUserIdsByUsernameWithoutDomain()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No domain specified',
            150440001
        );

        $request = new Request();
        $controller = new BulkController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->fetchUserIdsByUsernameAction($request);
    }
}

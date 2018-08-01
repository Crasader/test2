<?php

namespace BB\DurianBundle\Tests\Controller;

use Symfony\Component\HttpFoundation\Request;
use BB\DurianBundle\Controller\UserDetailController;

class UserDetailControllerTest extends ControllerTest
{
    /**
     * 測試檢查使用者詳細設定資訊唯一，傳入錯誤的參數，沒傳domain
     */
    public function testCheckUserDetailUniqueWithErrorParameterNoDomain()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No parent_id specified',
            150090010
        );

        $query = ['fields' => ['username' => 'alibaba']];

        $request = new Request($query);
        $controller = new UserDetailController();
        $controller->setContainer(static::$kernel->getContainer());
        $controller->userDetailCheckUniqueAction($request);
    }

    /**
     * 測試檢查使用者詳細設定資訊唯一，傳入錯誤的參數，沒傳fields
     */
    public function testCheckUserDetailUniqueWithErrorParameterNoFields()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No fields specified',
            150090009
        );

        $query = ['domain' => '101'];

        $request = new Request($query);
        $controller = new UserDetailController();
        $controller->setContainer(static::$kernel->getContainer());
        $controller->userDetailCheckUniqueAction($request);
    }

    /**
     * 測試檢查使用者詳細設定資訊唯一，傳入錯誤的參數，不支援的field
     */
    public function testCheckUserDetailUniqueWithErrorParameterInvalidField()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No fields specified',
            150090009
        );

        $query = [
            'domain' => '101',
            'fields' => ['birthday' => '2000-10-10']
        ];

        $request = new Request($query);
        $controller = new UserDetailController();
        $controller->setContainer(static::$kernel->getContainer());
        $controller->userDetailCheckUniqueAction($request);
    }

    /**
     * 測試新增推廣資料未帶廳
     */
    public function testCreatePromotionWithoutDomain()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No domain specified',
            150090020
        );

        $request = new Request();
        $controller = new UserDetailController();
        $controller->setContainer(static::$kernel->getContainer());
        $controller->createPromotionAction($request, 10);
    }

    /**
     * 測試修改推廣資料未帶廳
     */
    public function testEditPromotionWithoutDomain()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No domain specified',
            150090020
        );

        $request = new Request();
        $controller = new UserDetailController();
        $controller->setContainer(static::$kernel->getContainer());
        $controller->editPromotionAction($request, 8);
    }

    /**
     * 測試回傳推廣資料未帶廳
     */
    public function testGetPromotionWithoutDomain()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No domain specified',
            150090020
        );

        $request = new Request();
        $controller = new UserDetailController();
        $controller->setContainer(static::$kernel->getContainer());
        $controller->getPromotionAction($request, 8);
    }

    /**
     * 測試刪除推廣資料未帶廳
     */
    public function testDeletePromotionWithoutDomain()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No domain specified',
            150090020
        );

        $request = new Request();
        $controller = new UserDetailController();
        $controller->setContainer(static::$kernel->getContainer());
        $controller->deletePromotionAction($request, 8);
    }

    /**
     * 測試取得指定廳使用者詳細資料列表未帶上層編號
     */
    public function testListByDomainWithoutParentId()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No parent_id specified',
            150090040
        );

        $request = new Request();
        $controller = new UserDetailController();
        $controller->setContainer(static::$kernel->getContainer());
        $controller->listByDomainAction($request, 8);
    }
}

<?php

namespace BB\DurianBundle\Tests\Controller;

use BB\DurianBundle\Controller\PaymentChargeController;
use Symfony\Component\HttpFoundation\Request;

class PaymentChargeControllerTest extends ControllerTest
{
    /**
     * 測試新增線上支付設定時name輸入非UTF8
     */
    public function testNewPaymentChargeNameNotUtf8()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'String must use utf-8 encoding',
            150610002
        );

        $query = ['name' => mb_convert_encoding('e龜龍鱉', 'GB2312', 'UTF-8')];

        $request = new Request([], $query);
        $controller = new PaymentChargeController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createPaymentChargeAction($request, 10);
    }

    /**
     * 測試新增線上支付設定時name輸入特殊字元
     */
    public function testNewPaymentChargeNameWithSpecialChar()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Illegal character',
            150610007
        );

        $query = ['name' => '😁mandy'];

        $request = new Request([], $query);
        $controller = new PaymentChargeController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createPaymentChargeAction($request, 10);
    }

    /**
     * 測試修改線上支付設定名稱時name輸入非UTF8
     */
    public function testSetPaymentChargeNameNotUtf8()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'String must use utf-8 encoding',
            150610002
        );

        $query = ['name' => mb_convert_encoding('e龜龍鱉', 'GB2312', 'UTF-8')];

        $request = new Request([], $query);
        $controller = new PaymentChargeController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->setPaymentChargeNameAction($request, 10);
    }

    /**
     * 測試修改線上支付設定名稱時name輸入特殊字元
     */
    public function testSetPaymentChargeNameWithSpecialChar()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Illegal character',
            150610007
        );

        $query = ['name' => '😁mandy'];

        $request = new Request([], $query);
        $controller = new PaymentChargeController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->setPaymentChargeNameAction($request, 10);
    }
}

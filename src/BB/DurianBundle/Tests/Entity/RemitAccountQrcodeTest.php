<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\RemitAccountQrcode;

/**
 * 測試 RemitAccountQrcode
 */
class RemitAccountQrcodeTest extends DurianTestCase
{
    /**
     * 測試基本
     */
    public function testBasic()
    {
        $id = 99;

        $remitAccount = $this->getMockBuilder('BB\DurianBundle\Entity\remitAccount')
            ->disableOriginalConstructor()
            ->setMethods(['getId'])
            ->getMock();

        $remitAccount->expects($this->any())
            ->method('getId')
            ->will($this->returnValue($id));

        $remitAccountQrcode = new RemitAccountQrcode($remitAccount, 'test');
        $this->assertEquals($id, $remitAccountQrcode->getRemitAccountId());
        $this->assertEquals('test', $remitAccountQrcode->getQrcode());

        $remitAccountQrcode->setQrcode('testtest');
        $this->assertEquals('testtest', $remitAccountQrcode->getQrcode());
    }
}

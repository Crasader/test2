<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\AbnormalDepositNotifyEmail;

class AbnormalDepositNotifyEmailTest extends DurianTestCase
{
    /**
     * 測試基本功能
     */
    public function testBasic()
    {
        $notifyEmail = new AbnormalDepositNotifyEmail('adc@gmail.com');

        $this->assertNull($notifyEmail->getId());
        $this->assertEquals('adc@gmail.com', $notifyEmail->getEmail());

        $notifyEmail->setEmail('qaz@gmail.com');
        $this->assertEquals('qaz@gmail.com', $notifyEmail->getEmail());

        $array = $notifyEmail->toArray();

        $this->assertNull($array['id']);
        $this->assertEquals('qaz@gmail.com', $array['email']);
    }
}

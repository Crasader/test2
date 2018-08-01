<?php

namespace BB\DurianBundle\Tests\Repository;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Entity\Bank;

class BankRepositoryTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadBankData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserAncestorData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserDetailData'
        ];

        $this->loadFixtures($classnames);
    }

    /**
     * 測試取得同站同層重複的Bank
     */
    public function testgetByDomain()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository('BBDurianBundle:Bank');

        $user = $em->find('BBDurianBundle:User', 8);
        $bank = $em->find('BBDurianBundle:Bank', 3);

        $result = $repo->getByDomain($user->getDomain(), $bank->getAccount());

        $this->assertEquals(3, $result[0]->getId());
        $this->assertEquals($user, $result[0]->getUser());
        $this->assertEquals(4, $result[0]->getAccount());

        //測試帶depth
        $depth = count($user->getAllParents());
        $result = $repo->getByDomain($user->getDomain(), $bank->getAccount(), $depth);

        $this->assertEquals(3, $result[0]->getId());
        $this->assertEquals($user, $result[0]->getUser());
        $this->assertEquals(4, $result[0]->getAccount());

        //測試帶userId
        $depth = count($user->getAllParents());
        $result = $repo->getByDomain($user->getDomain(), $bank->getAccount(), null, 8);

        $this->assertEmpty($result);
    }


    /**
     * 測試依條件回傳bank欄位
     */
    public function testgetBankArrayBy()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository('BBDurianBundle:Bank');

        $user = $em->find('BBDurianBundle:User', 8);

        //測試帶fields
        $fields = ['id', 'account'];
        $result = $repo->getBankArrayBy($user, $fields);

        $this->assertEquals(4, $result[0]['id']);
        $this->assertEquals(3141586254359, $result[0]['account']);
        $this->assertEquals(3, $result[1]['id']);
        $this->assertEquals(4, $result[1]['account']);

        //測試帶criteria
        $criteria = ['id' => 1];
        $result = $repo->getBankArrayBy($user, $fields, $criteria);

        $this->assertEquals(1, count($result));
        $this->assertEquals(1, $result[0]['id']);
        $this->assertEquals(6221386170003601228, $result[0]['account']);
    }

    /**
     * 測試依userId回傳所有相符的銀行資訊
     */
    public function testgetBankArrayByUserIds()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository('BBDurianBundle:Bank');

        //新增銀行資料，不同使用者
        $user = $em->find('BBDurianBundle:User', 2);
        $bank = new Bank($user);
        $bank->setCode(5);
        $bank->setAccount('10');
        $em->persist($bank);

        $user = $em->find('BBDurianBundle:User', 3);
        $bank = new Bank($user);
        $bank->setCode(5);
        $bank->setAccount('10');
        $em->persist($bank);

        $em->flush();

        //測試帶多個userId
        $result = $repo->getBankArrayByUserIds([2, 3]);

        $this->assertEquals(2, $result[2][0]['user_id']);
        $this->assertEquals(10, $result[2][0]['account']);
        $this->assertEquals(3, $result[3][0]['user_id']);
        $this->assertEquals(10, $result[3][0]['account']);

        //測試沒有帶userId
        $result = $repo->getBankArrayByUserIds(null);
        $this->assertEmpty($result);
    }
}

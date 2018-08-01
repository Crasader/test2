<?php

namespace BB\DurianBundle\Tests\Functional;

class RemitAccountLevelFunctionalTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->loadFixtures([
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadBankInfoData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadRemitAccountData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadRemitAccountLevelData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadLevelData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadRemitAccountQrcodeData',
        ]);

        $this->loadFixtures([], 'share');
    }

    /**
     * 測試取得銀行卡順序
     */
    public function testGetOrder()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/level/2/remit_account_level', ['currency' => 'CNY']);

        $output = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals('1', $output['ret'][0]['id']);
        $this->assertEquals('2', $output['ret'][0]['domain']);
        $this->assertEquals('1', $output['ret'][0]['bank_info_id']);
        $this->assertEquals('1234567890', $output['ret'][0]['account']);
        $this->assertEquals('1', $output['ret'][0]['account_type']);
        $this->assertFalse($output['ret'][0]['auto_confirm']);
        $this->assertEquals('CNY', $output['ret'][0]['currency']);
        $this->assertEquals('Control Tips', $output['ret'][0]['control_tips']);
        $this->assertEquals('Recipient', $output['ret'][0]['recipient']);
        $this->assertEquals('Message', $output['ret'][0]['message']);
        $this->assertTrue($output['ret'][0]['enable']);
        $this->assertFalse($output['ret'][0]['deleted']);
        $this->assertEquals('1', $output['ret'][0]['order_id']);
        $this->assertEquals('1', $output['ret'][0]['version']);
    }

    /**
     * 測試設定銀行卡順序
     */
    public function testSetOrder()
    {
        $client = $this->createClient();

        $requestRemitAccounts = [
            [
                'id' => 1,
                'order_id' => 2,
                'version' => 1,
            ],
            [
                'id' => 3,
                'order_id' => 6,
                'version' => 1,
            ],
            [
                'id' => 6,
                'order_id' => 3,
                'version' => 1,
            ],
            [
                'id' => 7,
                'order_id' => 5,
                'version' => 1,
            ],
            [
                'id' => 8,
                'order_id' => 4,
                'version' => 1,
            ],
        ];

        $client->request('PUT', '/api/level/2/remit_account/order', [
            'remit_accounts' => $requestRemitAccounts,
        ]);

        $output = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals('ok', $output['result']);

        $ralCriteria = [
            'remitAccountId' => [],
            'levelId' => 2,
            'orderId' => [],
            'version' => [],
        ];

        $logOperationCriteria = [
            'majorKey' => [],
            'tableName' => 'remit_account_level',
            'message' => [],
        ];

        foreach ($requestRemitAccounts as $requestRemitAccount) {
            $id = $requestRemitAccount['id'];
            $orderId = $requestRemitAccount['order_id'];

            $ralCriteria['remitAccountId'][] = $id;
            $ralCriteria['orderId'][] = $orderId;
            $ralCriteria['version'][] = $requestRemitAccount['version'] + 1;

            $logOperationCriteria['majorKey'][] = "@id:$id";
            $logOperationCriteria['message'][] = "@order_id:1=>$orderId";
        }

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $remitAccountLevels = $em->getRepository('BBDurianBundle:RemitAccountLevel')->findBy($ralCriteria);

        $this->assertCount(5, $remitAccountLevels);

        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $logOperations = $emShare->getRepository('BBDurianBundle:LogOperation')->findBy($logOperationCriteria);

        $this->assertCount(5, $logOperations);
    }
}

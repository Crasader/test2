<?php

namespace BB\DurianBundle\Tests\Card;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Card\Operator;
use BB\DurianBundle\Entity\User;
use BB\DurianBundle\Entity\Card;

class OperatorTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $redis = $this->getContainer()->get('snc_redis.sequence');

        $redis->set('card_seq', 1000);
    }

    /**
     * 測試父層租卡停用時取得可用租卡
     */
    public function testCheckWithParentDisableCard()
    {
        $user1 = new User();
        $user1->setRent(true);
        $card1 = new Card($user1);
        $card1->disable();

        $user2 = new User();
        $user2->setRent(true);
        $user2->setParent($user1);

        $cardOperator = new Operator();
        $this->assertNull($cardOperator->check($user2));
    }

    /**
     * 測試取得父層可用租卡
     */
    public function testGetParentEnableCard()
    {
        $user1 = new User();
        $card1 = new Card($user1);
        $card1->enable();

        $user2 = new User();
        $user2->setParent($user1);

        $cardOperator = new Operator();

        $this->assertNull($cardOperator->getParentEnableCard($user1));
        $this->assertEquals($card1, $cardOperator->getParentEnableCard($user2));
    }

    /**
     * 測試確認租卡但所有租卡皆停用
     */
    public function testCheckButNotAnyCardEnabled()
    {
        $user1 = new User();
        $card1 = new Card($user1);

        $user2 = new User();
        $user2->setParent($user1);
        $card2 = new Card($user2);

        $user3 = new User();
        $user3->setParent($user2);
        $card3 = new Card($user3);

        $cardOperator = new Operator();

        $this->assertEquals($cardOperator->check($user3), null);
    }

    /**
     * 測試交易操作，但輸入金額不為整數
     */
    public function testOpButAmountIsNotInteger()
    {
        $this->setExpectedException('InvalidArgumentException', 'Card amount must be integer', 150030003);

        $user = new User();
        $user->setId(1);
        $card = new Card($user);
        $card->enable();

        $cardOperator = new Operator();
        $mockContainer = $this->getMockContainer();
        $cardOperator->setContainer($mockContainer);

        $options = [
            'operator' => 'IRONMAN',
            'opcode' => 9901
        ];

        $cardOperator->op($card, 10.123, $options);
    }

    /**
     * 測試租卡扣點操作，但扣點後redis餘額為負數，在opcode允許為負的情況
     */
    public function testOpAndBalanceAllowBeNegative()
    {
        $user = new User();
        $user->setId(1);
        $card = new Card($user);
        $card->enable();

        $cardOperator = new Operator();
        $mockContainer = $this->getMockContainer();
        $cardOperator->setContainer($mockContainer);

        $options = [
            'operator' => 'jjj',
            'opcode' => 1033
        ];
        $result = $cardOperator->op($card, 100, $options);
        $this->assertEquals($result['entry']['amount'], 100);
        $this->assertEquals($result['card']['balance'], 100);

        $result = $cardOperator->op($card, -200, $options);
        $this->assertEquals($result['entry']['amount'], -200);
        $this->assertEquals($result['card']['balance'], -100);
    }

    /**
     * 測試租卡扣點操作，但扣點後redis餘額為負數，且opcode不允許餘額為負
     */
    public function testOpButBalanceNotAllowBeNegative()
    {
        $this->setExpectedException('RuntimeException', 'Not enough card balance', 150030011);
        $user = new User();
        $user->setId(1);
        $card = new Card($user);
        $card->enable();

        $cardOperator = new Operator();
        $mockContainer = $this->getMockContainer();
        $cardOperator->setContainer($mockContainer);

        $options = [
            'operator' => 'jjj',
            'opcode' => 1002
        ];
        $result = $cardOperator->op($card, 100, $options);
        $this->assertEquals($result['entry']['amount'], 100);
        $this->assertEquals($result['card']['balance'], 100);

        $result = $cardOperator->op($card, -200, $options);
    }

    /**
     * 測試租卡扣點操作，但扣點後redis餘額為負數，且opcode不允許餘額為負，但amount為正
     */
    public function testOpBalanceNotAllowBeNegativeButAmountIsPositive()
    {
        $user = new User();
        $user->setId(1);
        $card = new Card($user);
        $card->enable();

        $cardOperator = new Operator();
        $mockContainer = $this->getMockContainer();
        $cardOperator->setContainer($mockContainer);

        $options = [
            'operator' => 'jjj',
            'opcode' => 1033
        ];
        $result = $cardOperator->op($card, -100, $options);
        $this->assertEquals($result['entry']['amount'], -100);
        $this->assertEquals($result['card']['balance'], -100);

        $options = [
            'operator' => 'jjj',
            'opcode' => 1002
        ];
        $result = $cardOperator->op($card, 50, $options);
        $this->assertEquals($result['entry']['amount'], 50);
        $this->assertEquals($result['card']['balance'], -50);
    }

    /**
     * 測試租卡存點操作，但存點後redis餘額超過整數最大值
     */
    public function testOpAddAmountButRedisBalanceIsOverflow()
    {
        $this->setExpectedException('RangeException', 'Balance exceeds allowed MAX integer', 150030018);

        $user = new User();
        $user->setId(1);
        $card = new Card($user);
        $card->enable();

        $cardOperator = new Operator();
        $mockContainer = $this->getMockContainer();
        $cardOperator->setContainer($mockContainer);

        $options = [
            'operator' => 'IRONMAN',
            'opcode' => 9901
        ];

        $cardOperator->op($card, 1000000001, $options);
    }

    /**
     * 測試租卡存點操作，但租卡餘額超過最大值
     */
    public function testOpButCardBalanceIsOverflow()
    {
        $this->setExpectedException('RangeException', 'Balance exceeds allowed MAX integer', 150030018);

        $user = new User();
        $user->setId(1);
        $card = new Card($user);
        $card->setBalance(1000000001);
        $card->enable();

        $cardOperator = new Operator();
        $mockContainer = $this->getMockContainer();
        $cardOperator->setContainer($mockContainer);

        $options = [
            'operator' => 'IRONMAN',
            'opcode' => 9901
        ];

        $cardOperator->op($card, 1, $options);
    }

    /**
     * 測試當租卡停用時交易
     */
    public function testOpButCardDisabled()
    {
        $this->setExpectedException('RuntimeException', 'This card is disabled', 150030006);

        $user1 = new User();
        $card1 = new Card($user1);

        $user2 = new User();
        $user2->setParent($user1);
        $card2 = new Card($user2);

        $user3 = new User();
        $user3->setParent($user2);
        $card3 = new Card($user3);

        $card = $user2->getCard();

        $cardOperator = new Operator();

        $options = [
            'operator' => 'IRONMAN',
            'opcode' => 20001 // 20001 BETTING
        ];
        $cardOperator->op($card, 1000, $options);
    }

    /**
     * 測試重複開啟關閉時上層的enable_num是否正確
     */
    public function testEnableNumberWithMultiEnableAndDisable()
    {
        $service = $this->mockOperator();

        $user1 = new User();
        $card1 = new Card($user1);

        $user2 = new User();
        $user2->setParent($user1);
        $card2 = new Card($user2);

        $user3 = new User();
        $user3->setParent($user2);
        $card3 = new Card($user3);

        $user33 = new User();
        $user33->setParent($user2);
        $card33 = new Card($user33);

        $service->enable($card3);
        $service->enable($card3);
        $service->enable($card3);
        $service->enable($card33);

        $this->assertTrue($card3->isEnabled());
        $this->assertEquals(2, $card2->getEnableNum());
        $this->assertEquals(2, $card1->getEnableNum());


        $service->disable($card3);
        $service->disable($card3);
        $this->assertEquals(1, $card2->getEnableNum());
        $this->assertEquals(1, $card1->getEnableNum());
    }

    /**
     * 測試有可用租卡並回傳
     */
    public function testCheckCardIsEnable()
    {
        $user = new User();
        $user->setRent(true);
        $card = new Card($user);
        $card->enable();

        $cardOperator = $this->mockOperator();

        $this->assertEquals($card, $cardOperator->check($user));
    }

    /**
     * 測試若無可用租卡，則往上層找並回傳
     */
    public function testCheckEnabledCardFromParent()
    {
        $user = new User();
        $card = new Card($user);
        $card->enable();

        $user2 = new User();
        $user2->setParent($user);
        $user2->setRent(true);

        $cardOperator = $this->mockOperator();

        $this->assertEquals($card, $cardOperator->check($user2));
    }

    /**
     * 測試可用租卡，雖為租卡體系但所有租卡皆停用
     */
    public function testCheckButAllCardDisabled()
    {
        $user1 = new User();
        $user1->setRent(true);
        $card1 = new Card($user1);

        $user2 = new User();
        $user2->setParent($user1);
        $card2 = new Card($user2);

        $cardOperator = $this->mockOperator();

        $this->assertNull($cardOperator->check($user2));
    }

    /**
     * 測試將整條上層的"租卡數量"加一
     */
    public function testAddParentsEnableNum()
    {
        $user1 = new User();

        $user2 = new User();
        $user2->setParent($user1);

        $user3 = new User();
        $user3->setParent($user2);
        $card = new Card($user3);

        $cardOperator = $this->mockOperator();
        $cardOperator->addParentsEnableNum($card);

        $this->assertEquals(1, $user1->getCard()->getEnableNum());
        $this->assertEquals(1, $user2->getCard()->getEnableNum());
    }

    /**
     * 測試將整條上層的"租卡數量"減一
     */
    public function testSubParentsEnableNum()
    {
        $user1 = new User();

        $user2 = new User();
        $card2 = new Card($user2);

        $user3 = new User();
        $user3->setParent($user2);
        $card3 = new Card($user3);

        $cardOperator = $this->mockOperator();
        $cardOperator->addParentsEnableNum($card3);
        $cardOperator->subParentsEnableNum($card3);

        $this->assertEquals(0, $user2->getCard()->getEnableNum());

        // 測試上層若無租卡，則新增租卡
        $user2->setParent($user1);
        $cardOperator->subParentsEnableNum($card3);
        $this->assertNotEmpty($user1->getCard());
    }

    /**
     * 測試租卡啟用
     */
    public function testEnable()
    {
        $user1 = new User();

        $user2 = new User();
        $user2->setParent($user1);
        $card2 = new Card($user2);

        $cardOperator = $this->mockOperator();
        $cardOperator->enable($card2);

        $this->assertTrue($card2->isEnabled());
        $this->assertEquals(1, $user1->getCard()->getEnableNum());

        // 該租卡若已被啟用，則回傳card
        $this->assertEquals($card2, $cardOperator->enable($card2));
    }

    /**
     * 測試租卡啟用，發生例外上層已有開啟租卡
     */
    public function testEnableButCardISAlreadyEnabledFromParents()
    {
        $this->setExpectedException('RuntimeException', 'Only one card in the hierarchy would be enabled', 150030001);

        $user1 = new User();
        $card1 = new Card($user1);
        $card1->enable();

        $user2 = new User();
        $user2->setParent($user1);
        $card2 = new Card($user2);

        $cardOperator = $this->mockOperator();
        $cardOperator->enable($card2);
    }

    /**
     * 測試租卡停用
     */
    public function testDisable()
    {
        $user1 = new User();

        $user2 = new User();
        $user2->setParent($user1);
        $card2 = new Card($user2);

        $user3 = new User();
        $user3->setParent($user1);
        $card3 = new Card($user3);

        $cardOperator = $this->mockOperator();
        $cardOperator->enable($card3);

        // 租卡本身就未啟用，不會扣除上層租卡數量
        $cardOperator->disable($card2);
        $this->assertEquals(1, $user1->getCard()->getEnableNum());

        // 租卡已啟用，停用時扣除上層租卡數量
        $cardOperator->disable($card3);
        $this->assertEquals(0, $user1->getCard()->getEnableNum());
        $this->assertFalse($card3->isEnabled());
    }

    /**
     * 測試寫入租卡明細
     */
    public function testInsertCardEntryByRedis()
    {
        $entries = [
            [
                'id' => 1001,
                'card_id' => 5,
                'user_id' => 6,
                'opcode' => 1001,
                'amount' => -1,
                'balance' => 199,
                'card_version' => 1
            ],
            [
                'id' => 1002,
                'card_id' => 6,
                'user_id' => 7,
                'opcode' => 1002,
                'amount' => 1,
                'balance' => 201,
                'card_version' => 1
            ]
        ];

        $mockContainer = $this->getMockContainer();
        $cardOperator = new Operator();
        $cardOperator->setContainer($mockContainer);
        $output = $cardOperator->insertCardEntryByRedis($entries);

        $this->assertEquals(1001, $output[0]['id']);
        $this->assertEquals(5, $output[0]['card_id']);
        $this->assertEquals(6, $output[0]['user_id']);
        $this->assertEquals(1001, $output[0]['opcode']);
        $this->assertEquals(-1, $output[0]['amount']);
        $this->assertEquals(199, $output[0]['balance']);

        $this->assertEquals(1002, $output[1]['id']);
        $this->assertEquals(6, $output[1]['card_id']);
        $this->assertEquals(7, $output[1]['user_id']);
        $this->assertEquals(1002, $output[1]['opcode']);
        $this->assertEquals(1, $output[1]['amount']);
        $this->assertEquals(201, $output[1]['balance']);
    }

    private function mockOperator()
    {
        $service = $this->getMockBuilder('BB\DurianBundle\Card\Operator')
                ->disableOriginalConstructor()
                ->setMethods(array('getEntityManager'))
                ->getMock();

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
                ->disableOriginalConstructor()
                ->getMock();

        $service->expects($this->any())
                ->method('getEntityManager')
                ->will($this->returnValue($em));

        return $service;
    }

    /**
     * 取得 MockContainer
     *
     * @return Container
     */
    private function getMockContainer()
    {
        $redis = $this->getContainer()->get('snc_redis.default');

        $redisWallet = $this->getContainer()->get('snc_redis.wallet1');

        $op = $this->getContainer()->get('durian.op');

        $idGenerator = $this->getContainer()->get('durian.card_entry_id_generator');

        $mockContainer = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->setMethods(['get'])
            ->getMock();
        $getMap = [
            ['snc_redis.default', 1, $redis],
            ['snc_redis.wallet1', 1, $redisWallet],
            ['durian.op', 1, $op],
            ['durian.card_entry_id_generator', 1, $idGenerator]
        ];
        $mockContainer->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap($getMap));

        return $mockContainer;
    }
}

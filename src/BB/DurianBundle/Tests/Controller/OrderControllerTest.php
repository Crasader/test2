<?php

namespace BB\DurianBundle\Tests\Controller;

use Symfony\Component\HttpFoundation\Request;
use BB\DurianBundle\Controller\OrderController;

class OrderControllerTest extends ControllerTest
{
    /**
     * 測試多重下注，沒有帶order
     */
    public function testMultiOrderWithoutOrder()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No orders specified',
            150140005
        );

        $container = static::$kernel->getContainer();
        $request = new Request();
        $controller = new OrderController();
        $controller->setContainer($container);

        $controller->multiOrderAction($request);
    }

    /**
     * 測試批次下注，但沒有傳pay_way
     */
    public function testMultiOrderBunchWithoutPayway()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Plz chose a pay way',
            150140001
        );

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->setMethods(['find', 'clear'])
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('find')
            ->will($this->returnValue('BBDurianBundle:User'));

        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);

        $request = new Request();
        $controller = new OrderController();
        $controller->setContainer($container);

        $controller->multiOrderBunchAction($request, 1);
    }

    /**
     * 測試現金批次下注，輸入無效amount的情況
     */
    public function testCashMultiOrderBunchWithInvalidAmount()
    {
        $this->setExpectedException(
            'RangeException',
            'The decimal digit of amount exceeds limitation',
            150610003
        );

        $odParams = [
            [
                'am' => -1.00005698,
                'ref' => 1225799685,
                'memo' => 'test bunch order data 1'
            ]
        ];

        $params = [
            'pay_way' => 'cash',
            'opcode' => 1001,
            'od_count' => count($odParams),
            'od' => $odParams
        ];

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->setMethods(['find', 'clear'])
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('find')
            ->will($this->returnValue('BBDurianBundle:User'));

        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);

        $request = new Request([], $params);
        $controller = new OrderController();
        $controller->setContainer($container);

        $controller->multiOrderBunchAction($request, 1);
    }

    /**
     * 測試現金批次下注未帶注單參數
     */
    public function testCashMultiOrderBunchWithoutOd()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid order',
            150140034
        );

        $params = [
            'pay_way'  => 'cash',
            'opcode'   => 1001,
            'od_count' => 0
        ];

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->setMethods(['find', 'clear'])
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('find')
            ->will($this->returnValue('BBDurianBundle:User'));

        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);

        $request = new Request([], $params);
        $controller = new OrderController();
        $controller->setContainer($container);

        $controller->multiOrderBunchAction($request, 1);
    }

    /**
     * 測試信用額度批次下注，未帶opcode
     */
    public function testCreditMultiOrderBunchWithoutOpcode()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No opcode specified',
            150140019
        );

        $params = ['pay_way' => 'credit'];

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->setMethods(['find', 'clear'])
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('find')
            ->will($this->returnValue('BBDurianBundle:User'));

        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);

        $request = new Request([], $params);
        $controller = new OrderController();
        $controller->setContainer($container);

        $controller->multiOrderBunchAction($request, 1);
    }

    /**
     * 測試信用額度批次下注，帶入不合法的opcode
     */
    public function testCreditMultiOrderBunchWithInvalidOpcode()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid opcode',
            150140015
        );

        $params = [
            'pay_way' => 'credit',
            'opcode' => 'test'
        ];

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->setMethods(['find', 'clear'])
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('find')
            ->will($this->returnValue('BBDurianBundle:User'));

        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);

        $request = new Request([], $params);
        $controller = new OrderController();
        $controller->setContainer($container);

        $controller->multiOrderBunchAction($request, 1);
    }

    /**
     * 測試信用額度批次下注，帶入opcode = 1003
     */
    public function testCreditMultiOrderBunchWithTransferOpcode()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid opcode',
            150140015
        );

        $params = [
            'pay_way' => 'credit',
            'opcode' => 1003
        ];

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->setMethods(['find', 'clear'])
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('find')
            ->will($this->returnValue('BBDurianBundle:User'));

        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);

        $request = new Request([], $params);
        $controller = new OrderController();
        $controller->setContainer($container);

        $controller->multiOrderBunchAction($request, 1);
    }

    /**
     * 測試信用額度批次下注，帶入opcode = 1042
     */
    public function testCreditMultiOrderBunchWithTransferApiInOpcode()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid opcode',
            150140015
        );

        $params = [
            'pay_way' => 'credit',
            'opcode' => 1042
        ];

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->setMethods(['find', 'clear'])
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('find')
            ->will($this->returnValue('BBDurianBundle:User'));

        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);

        $request = new Request([], $params);
        $controller = new OrderController();
        $controller->setContainer($container);

        $controller->multiOrderBunchAction($request, 1);
    }

    /**
     * 測試信用額度批次下注，帶入opcode = 1043
     */
    public function testCreditMultiOrderBunchWithTransferApiOutOpcode()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid opcode',
            150140015
        );

        $params = [
            'pay_way' => 'credit',
            'opcode' => 1043
        ];

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->setMethods(['find', 'clear'])
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('find')
            ->will($this->returnValue('BBDurianBundle:User'));

        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);

        $request = new Request([], $params);
        $controller = new OrderController();
        $controller->setContainer($container);

        $controller->multiOrderBunchAction($request, 1);
    }

    /**
     * 測試信用額度批次下注，未帶信用額度群組代碼
     */
    public function testCreditMultiOrderBunchWithoutGroupNum()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Payway is credit, but none group num specified',
            150140030
        );

        $params = [
            'pay_way' => 'credit',
            'opcode' => 1001
        ];

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->setMethods(['find', 'clear'])
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('find')
            ->will($this->returnValue('BBDurianBundle:User'));

        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);

        $request = new Request([], $params);
        $controller = new OrderController();
        $controller->setContainer($container);

        $controller->multiOrderBunchAction($request, 1);
    }

    /**
     * 測試信用額度批次下注，時間為空值
     */
    public function testCreditMultiOrderBunchWithNullAt()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Must send timestamp',
            150140023
        );

        $params = [
            'pay_way' => 'credit',
            'opcode' => 1001,
            'credit_group_num' => 1,
            'at' => null
        ];

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->setMethods(['find', 'clear'])
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('find')
            ->will($this->returnValue('BBDurianBundle:User'));

        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);

        $request = new Request([], $params);
        $controller = new OrderController();
        $controller->setContainer($container);

        $controller->multiOrderBunchAction($request, 1);
    }

    /**
     * 測試信用額度批次下注，帶入不合法信用額度群組代碼
     */
    public function testCreditMultiOrderBunchWithInvalidGroupNum()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid group number',
            150140013
        );

        $now = new \DateTime('now');
        $params = [
            'pay_way' => 'credit',
            'opcode' => 1001,
            'credit_group_num' => '測試',
            'at' => $now->format(\DateTime::ISO8601)
        ];

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->setMethods(['find', 'clear'])
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('find')
            ->will($this->returnValue('BBDurianBundle:User'));

        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);

        $request = new Request([], $params);
        $controller = new OrderController();
        $controller->setContainer($container);

        $controller->multiOrderBunchAction($request, 1);
    }

    /**
     * 測試信用額度批次下注，下注筆數與接收到的筆數不符合
     */
    public function testCreditMultiOrderBunchWithWrongOdCount()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Order count error',
            150140007
        );

        $now = new \DateTime('now');
        $odParams = [
            [
                'am' => -200,
                'card' => -1,
                'ref' => 15569985,
                'memo' => 'test order data 1'
            ],
            [
                'am' => -200,
                'card' => -1,
                'ref' => 15569986,
                'memo' => 'test order data 2'
            ]
        ];

        $params = [
            'pay_way' => 'credit',
            'opcode' => 1001,
            'credit_group_num' => 1,
            'od_count' => 1,
            'at' => $now->format(\DateTime::ISO8601),
            'od' => $odParams
        ];

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->setMethods(['find', 'clear'])
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('find')
            ->will($this->returnValue('BBDurianBundle:User'));

        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);

        $request = new Request([], $params);
        $controller = new OrderController();
        $controller->setContainer($container);

        $controller->multiOrderBunchAction($request, 1);
    }

    /**
     * 測試信用額度批次下注，帶入金額不為數字
     */
    public function testCreditMultiOrderBunchWithNotNumericAmount()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Amount must be numeric',
            150140010
        );

        $now = new \DateTime('now');
        $odParams = [
            [
                'am' => '金額',
                'card' => -1,
                'ref' => 15569985,
                'memo' => 'test order data 1'
            ]
        ];

        $params = [
            'pay_way' => 'credit',
            'opcode' => 1001,
            'credit_group_num' => 1,
            'od_count' => count($odParams),
            'at' => $now->format(\DateTime::ISO8601),
            'od' => $odParams
        ];

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->setMethods(['find', 'clear'])
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('find')
            ->will($this->returnValue('BBDurianBundle:User'));

        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);

        $request = new Request([], $params);
        $controller = new OrderController();
        $controller->setContainer($container);

        $controller->multiOrderBunchAction($request, 1);
    }

    /**
     * 測試信用額度批次下注，傳入空陣列
     */
    public function testCreditMultiOrderBunchWithEmptyArray()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No orders specified',
            150140005
        );

        $now = new \DateTime('now');

        $params = [
            'pay_way' => 'credit',
            'opcode' => 1001,
            'credit_group_num' => 1,
            'od_count' => 0,
            'at' => $now->format(\DateTime::ISO8601),
            'od' => []
        ];

        $user = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();
        $user->expects($this->any())
            ->method('getCurrency')
            ->willReturn(156);
        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->setMethods(['find', 'clear'])
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('find')
            ->will($this->returnValue($user));

        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);

        $request = new Request([], $params);
        $controller = new OrderController();
        $controller->setContainer($container);

        $controller->multiOrderBunchAction($request, 1);
    }

    /**
     * 測試信用額度批次下注，帶入非整數的cardAmount
     */
    public function testCreditMultiOrderBunchWithNotIntegerCardAmount()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Card amount must be an integer',
            150140011
        );

        $now = new \DateTime('now');
        $odParams = [
            [
                'am' => -200,
                'card' => -1.5555,
                'ref' => 15569985,
                'memo' => 'test order data 1'
            ]
        ];

        $params = [
            'pay_way' => 'credit',
            'opcode' => 1001,
            'credit_group_num' => 1,
            'od_count' => count($odParams),
            'at' => $now->format(\DateTime::ISO8601),
            'od' => $odParams
        ];

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->setMethods(['find', 'clear'])
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('find')
            ->will($this->returnValue('BBDurianBundle:User'));

        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);

        $request = new Request([], $params);
        $controller = new OrderController();
        $controller->setContainer($container);

        $controller->multiOrderBunchAction($request, 1);
    }

    /**
     * 測試信用額度批次下注，ref_id帶字串
     */
    public function testCreditMultiOrderBunchWithStringRefId()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid ref_id',
            150140016
        );

        $now = new \DateTime('now');
        $odParams = [
            [
                'am' => -200,
                'ref' => '我是字串'
            ],
            [
                'am' => -200,
                'ref' => 1
            ],
            [
                'am' => -200,
                'ref' => 0
            ]
        ];

        $params = [
            'pay_way' => 'credit',
            'opcode' => 1001,
            'credit_group_num' => 1,
            'od_count' => count($odParams),
            'at' => $now->format(\DateTime::ISO8601),
            'od' => $odParams
        ];

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->setMethods(['find', 'clear'])
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('find')
            ->will($this->returnValue('BBDurianBundle:User'));

        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);

        $request = new Request([], $params);
        $controller = new OrderController();
        $controller->setContainer($container);

        $controller->multiOrderBunchAction($request, 1);
    }

    /**
     * 測試信用額度批次下注，ref_id帶負數
     */
    public function testCreditMultiOrderBunchWithNegativeRefId()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid ref_id',
            150140016
        );

        $now = new \DateTime('now');
        $odParams = [
            [
                'am' => -200,
                'ref' => -1
            ],
            [
                'am' => -200,
                'ref' => 1
            ],
            [
                'am' => -200,
                'ref' => 0
            ]
        ];

        $params = [
            'pay_way' => 'credit',
            'opcode' => 1001,
            'credit_group_num' => 1,
            'od_count' => count($odParams),
            'at' => $now->format(\DateTime::ISO8601),
            'od' => $odParams
        ];

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->setMethods(['find', 'clear'])
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('find')
            ->will($this->returnValue('BBDurianBundle:User'));

        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);

        $request = new Request([], $params);
        $controller = new OrderController();
        $controller->setContainer($container);

        $controller->multiOrderBunchAction($request, 1);
    }

    /**
     * 測試信用額度批次下注，ref_id超過範圍
     */
    public function testCreditMultiOrderBunchWithRefIdOutOfRange()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid ref_id',
            150140016
        );

        $now = new \DateTime('now');
        $odParams = [
            [
                'am' => -200,
                'ref' => 9223372036854775807
            ],
            [
                'am' => -200,
                'ref' => 1
            ],
            [
                'am' => -200,
                'ref' => 0
            ]
        ];

        $params = [
            'pay_way' => 'credit',
            'opcode' => 1001,
            'credit_group_num' => 1,
            'od_count' => count($odParams),
            'at' => $now->format(\DateTime::ISO8601),
            'od' => $odParams
        ];

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->setMethods(['find', 'clear'])
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('find')
            ->will($this->returnValue('BBDurianBundle:User'));

        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);

        $request = new Request([], $params);
        $controller = new OrderController();
        $controller->setContainer($container);

        $controller->multiOrderBunchAction($request, 1);
    }

    /**
     * 測試信用額度批次下注,memo輸入非UTF8
     */
    public function testCreditMultiOrderBunchButMemoNotUTF8()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'String must use utf-8 encoding',
            150610002
        );

        $now = new \DateTime('now');
        $odParams = [
            [
                'am' => -200,
                'memo' => mb_convert_encoding('大吉大利', 'GB2312', 'UTF-8')
            ],
            [
                'am' => -200,
                'memo' => 'test'
            ],
            [
                'am' => -200,
                'memo' => 'test'
            ]
        ];

        $params = [
            'pay_way' => 'credit',
            'opcode' => 1001,
            'credit_group_num' => 1,
            'od_count' => count($odParams),
            'at' => $now->format(\DateTime::ISO8601),
            'operator' => 'thorblack',
            'od' => $odParams
        ];

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->setMethods(['find', 'clear'])
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('find')
            ->will($this->returnValue('BBDurianBundle:User'));

        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);

        $request = new Request([], $params);
        $controller = new OrderController();
        $controller->setContainer($container);

        $controller->multiOrderBunchAction($request, 1);
    }

    /**
     * 測試以現金下注時金額為0
     */
    public function testCashDoOrderWithZeroAmount()
    {
        $params = [
            'pay_way' => 'cash',
            'opcode' => 1001,
            'amount' => 0,
            'auto_commit' => 1,
            'ref_id' => 123
        ];

        $mockUser = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->setMethods(['getCash'])
            ->disableOriginalConstructor()
            ->getMock();
        $mockUser->expects($this->any())
            ->method('getCash')
            ->willReturn('BBDurianBundle:Cash');

        $mockEm = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->setMethods(['find', 'clear'])
            ->disableOriginalConstructor()
            ->getMock();
        $mockEm->expects($this->any())
            ->method('find')
            ->willReturn($mockUser);

        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $mockEm);

        $request = new Request([], $params);
        $controller = new OrderController();
        $controller->setContainer($container);

        // 因API會將例外catch住只在回傳訊息裡輸出, 故直接驗證
        $jsonResponse = $controller->orderAction($request, 1);

        $res = json_decode($jsonResponse->getContent(), true);

        $this->assertEquals('error', $res['result']);
        $this->assertEquals('Amount can not be zero', $res['msg']);
        $this->assertEquals(150140031, $res['code']);
    }

    /**
     * 測試以現金多重下注時金額為0
     */
    public function testCashMultiOrderWithZeroAmount()
    {
        $params['orders'][] = [
            'user_id' => 7,
            'pay_way' => 'cash',
            'amount' => 0,
            'opcode' => 1001
        ];

        $mockUser = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->setMethods(['getCash'])
            ->disableOriginalConstructor()
            ->getMock();
        $mockUser->expects($this->any())
            ->method('getCash')
            ->willReturn('BBDurianBundle:Cash');

        $mockEm = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->setMethods(['find', 'clear'])
            ->disableOriginalConstructor()
            ->getMock();
        $mockEm->expects($this->any())
            ->method('find')
            ->willReturn($mockUser);

        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $mockEm);

        $request = new Request([], $params);
        $controller = new OrderController();
        $controller->setContainer($container);

        // 因API會將例外catch住只在回傳訊息裡輸出, 故直接驗證
        $jsonResponse = $controller->multiOrderAction($request, 1);

        $res = json_decode($jsonResponse->getContent(), true);

        $this->assertEquals('error', $res[0]['result']);
        $this->assertEquals('Amount can not be zero', $res[0]['msg']);
        $this->assertEquals(150140031, $res[0]['code']);
    }

    /**
     * 測試現金批次下注時其中一筆金額為0
     */
    public function testCashMultiOrderBunchWithZeroAmount()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Amount can not be zero',
            150140031
        );

        $odParams = [
            [
                'am' => 25,
                'ref' => 1225799685,
                'memo' => 'test bunch order data 1'
            ],
            [
                'am' => 0,
                'ref' => 1225799685,
                'memo' => 'test bunch order data 2'
            ],
            [
                'am' => 10,
                'ref' => 1225799685,
                'memo' => 'test bunch order data 3'
            ]
        ];

        $params = [
            'pay_way' => 'cash',
            'opcode' => 1001,
            'od_count' => 3,
            'od' => $odParams
        ];

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->setMethods(['find', 'clear'])
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('find')
            ->willReturn('BBDurianBundle:User');

        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);

        $request = new Request([], $params);
        $controller = new OrderController();
        $controller->setContainer($container);

        $controller->multiOrderBunchAction($request, 1);
    }
}

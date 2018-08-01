<?php
namespace BB\DurianBundle\Tests\Controller;

use Symfony\Component\HttpFoundation\Request;
use BB\DurianBundle\Controller\BitcoinWalletController;

class BitcoinWalletControllerTest extends ControllerTest
{
    /**
     * 測試新增比特幣錢包未帶入錢包帳號
     */
    public function testCreateWithoutWalletCode()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No wallet_code specified',
            150890001
        );

        $parameters = [
            'password' => 'test',
            'api_code' => '87654321-4321-4321-4321-210987654321',
        ];

        $request = new Request([], $parameters);
        $controller = new BitcoinWalletController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request, 2);
    }

    /**
     * 測試新增比特幣錢包未帶入錢包密碼
     */
    public function testCreateWithoutPassword()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No password specified',
            150890002
        );

        $parameters = [
            'wallet_code' => '12345678-1234-1234-1234-123456789012',
            'api_code' => '87654321-4321-4321-4321-210987654321',
        ];

        $request = new Request([], $parameters);
        $controller = new BitcoinWalletController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request, 2);
    }

    /**
     * 測試新增比特幣錢包未帶入api碼
     */
    public function testCreateWithoutApiCode()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No api_code specified',
            150890003
        );

        $parameters = [
            'wallet_code' => '12345678-1234-1234-1234-123456789012',
            'password' => 'test',
        ];

        $request = new Request([], $parameters);
        $controller = new BitcoinWalletController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request, 2);
    }

    /**
     * 測試新增比特幣錢包帶入非UTF8
     */
    public function testCreateApiCodeNotUtf8()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'String must use utf-8 encoding',
            150610002
        );

        $parameters = [
            'wallet_code' => '12345678-1234-1234-1234-123456789012',
            'password' => 'test',
            'second_password' => 'ttest',
            'api_code' => mb_convert_encoding('e龜龍鱉', 'GB2312', 'UTF-8'),
            'xpub' => 'xpub...',
        ];

        $request = new Request([], $parameters);
        $controller = new BitcoinWalletController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request, 2);
    }

    /**
     * 測試新增比特幣錢包帶入非整數比特幣出款手續費率
     */
    public function testCreateFeePerByteNotInt()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'FeePerByte must be an integer',
            150890005
        );

        $parameters = [
            'wallet_code' => '12345678-1234-1234-1234-123456789012',
            'password' => 'test',
            'second_password' => 'ttest',
            'api_code' => '87654321-4321-4321-4321-210987654321',
            'xpub' => 'xpub...',
            'fee_per_byte' => '2.5',
        ];

        $request = new Request([], $parameters);
        $controller = new BitcoinWalletController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request, 2);
    }

    /**
     * 測試新增比特幣錢包帶入錯誤密碼
     */
    public function testCreateWithWrongPassword()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Main wallet password incorrect',
            150180202
        );

        $parameters = [
            'wallet_code' => '12345678-1234-1234-1234-123456789012',
            'password' => 'errPassword',
            'second_password' => 'ttest',
            'api_code' => '87654321-4321-4321-4321-210987654321',
            'xpub' => 'xpub...',
        ];

        $mockBlockChain = $this->getMockBuilder('BB\DurianBundle\Payment\BlockChain')
            ->disableOriginalConstructor()
            ->getMock();
        $mockBlockChain->expects($this->any())
            ->method('validateBitcoinWallet')
            ->willThrowException(new \RuntimeException('Main wallet password incorrect', 150180202));

        $request = new Request([], $parameters);
        $controller = new BitcoinWalletController();
        $container = static::$kernel->getContainer();
        $container->set('durian.block_chain', $mockBlockChain);
        $controller->setContainer($container);

        $controller->createAction($request, 2);
    }

    /**
     * 測試新增比特幣錢包,出現flush錯誤,執行RollBack CashTrans
     */
    public function testCreateButRollBack()
    {
        $this->setExpectedException(
            'Exception',
            'SQLSTATE[28000] [1045]',
            0
        );

        $parameters = [
            'wallet_code' => '12345678-1234-1234-1234-123456789012',
            'password' => 'test',
            'second_password' => 'ttest',
            'api_code' => '87654321-4321-4321-4321-210987654321',
            'xpub' => 'xpub...',
        ];

        $mockBlockChain = $this->getMockBuilder('BB\DurianBundle\Payment\BlockChain')
            ->disableOriginalConstructor()
            ->getMock();

        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods([
                'beginTransaction',
                'persist',
                'flush',
                'rollback',
                'clear'
            ])
            ->getMock();
        $mockEm->expects($this->any())
            ->method('flush')
            ->willThrowException(new \Exception('SQLSTATE[28000] [1045]'));

        $request = new Request([], $parameters);
        $controller = new BitcoinWalletController();
        $container = static::$kernel->getContainer();
        $container->set('durian.block_chain', $mockBlockChain);
        $container->set('doctrine.orm.default_entity_manager', $mockEm);
        $controller->setContainer($container);

        $controller->createAction($request, 2);
    }

    /**
     * 測試取得不存在的比特幣錢包
     */
    public function testGetWalletWithBitcoinWalletNotExist()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No such bitcoin wallet',
            150890004
        );

        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEm->expects($this->any())
            ->method('find')
            ->willReturn(null);

        $controller = new BitcoinWalletController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $mockEm);
        $controller->setContainer($container);

        $controller->getWalletAction(1);
    }

    /**
     * 測試修改不存在的比特幣錢包
     */
    public function testEditWalletWithBitcoinWalletNotExist()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No such bitcoin wallet',
            150890004
        );

        $parameters = [
            'xpub' => 'xpub...',
        ];

        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEm->expects($this->any())
            ->method('find')
            ->willReturn(null);

        $request = new Request([], $parameters);
        $controller = new BitcoinWalletController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $mockEm);
        $controller->setContainer($container);

        $controller->editWalletAction($request, 1);
    }

    /**
     * 測試修改比特幣錢包帶入非UTF8
     */
    public function testEditWalletApiCodeNotUtf8()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'String must use utf-8 encoding',
            150610002
        );

        $parameters = [
            'wallet_code' => '12345678-1234-1234-1234-123456789012',
            'password' => 'test',
            'second_password' => 'ttest',
            'api_code' => mb_convert_encoding('e龜龍鱉', 'GB2312', 'UTF-8'),
            'xpub' => 'xpub...',
        ];

        $mockBitcoinWallet = $this->getMockBuilder('BB\DurianBundle\Entity\BitcoinWallet')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods([
                'find',
                'flush',
                'clear',
            ])
            ->getMock();
        $mockEm->expects($this->any())
            ->method('find')
            ->willReturn($mockBitcoinWallet);

        $request = new Request([], $parameters);
        $controller = new BitcoinWalletController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $mockEm);
        $controller->setContainer($container);

        $controller->editWalletAction($request, 1);
    }

    /**
     * 測試修改比特幣錢包帶入錯誤密碼
     */
    public function testEditWalletWithWrongPassword()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Main wallet password incorrect',
            150180202
        );

        $parameters = [
            'wallet_code' => '12345678-1234-1234-1234-123456789012',
            'password' => 'errPassword',
            'second_password' => 'ttest',
            'api_code' => '87654321-4321-4321-4321-210987654321',
            'xpub' => 'xpub...',
        ];

        $mockBlockChain = $this->getMockBuilder('BB\DurianBundle\Payment\BlockChain')
            ->disableOriginalConstructor()
            ->getMock();
        $mockBlockChain->expects($this->any())
            ->method('validateBitcoinWallet')
            ->willThrowException(new \RuntimeException('Main wallet password incorrect', 150180202));

        $mockLogOp = $this->getMockBuilder('BB\DurianBundle\Entity\LogOperation')
            ->disableOriginalConstructor()
            ->setMethods(['addMessage'])
            ->getMock();
        $mockOperationLogger = $this->getMockBuilder('\BB\DurianBundle\Logger\Operation')
            ->disableOriginalConstructor()
            ->setMethods(['create', 'save'])
            ->getMock();
        $mockOperationLogger->expects($this->any())
            ->method('create')
            ->willReturn($mockLogOp);

        $mockBitcoinWallet = $this->getMockBuilder('BB\DurianBundle\Entity\BitcoinWallet')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods([
                'beginTransaction',
                'find',
                'flush',
                'rollback',
                'clear',
            ])
            ->getMock();
        $mockEm->expects($this->any())
            ->method('find')
            ->willReturn($mockBitcoinWallet);

        $request = new Request([], $parameters);
        $controller = new BitcoinWalletController();
        $container = static::$kernel->getContainer();
        $container->set('durian.block_chain', $mockBlockChain);
        $container->set('durian.operation_logger', $mockOperationLogger);
        $container->set('doctrine.orm.default_entity_manager', $mockEm);
        $controller->setContainer($container);

        $controller->editWalletAction($request, 1);
    }

    /**
     * 測試修改比特幣錢包帶入過長api碼
     */
    public function testEditWalletWithErrorApiCode()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Database is busy',
            150780001
        );

        $parameters = [
            'wallet_code' => '87654321-4321-4321-4321-210987654321',
            'password' => 'ttest',
            'second_password' => 'test',
            'api_code' => '0123456789012345678901234567890123456789012345678901234567890123456789',
            'xpub' => 'xpub.....',
        ];

        $mockBlockChain = $this->getMockBuilder('BB\DurianBundle\Payment\BlockChain')
            ->disableOriginalConstructor()
            ->getMock();

        $mockLogOp = $this->getMockBuilder('BB\DurianBundle\Entity\LogOperation')
            ->disableOriginalConstructor()
            ->setMethods(['addMessage'])
            ->getMock();
        $mockOperationLogger = $this->getMockBuilder('\BB\DurianBundle\Logger\Operation')
            ->disableOriginalConstructor()
            ->setMethods(['create', 'save'])
            ->getMock();
        $mockOperationLogger->expects($this->any())
            ->method('create')
            ->willReturn($mockLogOp);

        $mockBitcoinWallet = $this->getMockBuilder('BB\DurianBundle\Entity\BitcoinWallet')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods([
                'beginTransaction',
                'find',
                'flush',
                'rollback',
                'clear',
            ])
            ->getMock();
        $mockEm->expects($this->any())
            ->method('find')
            ->willReturn($mockBitcoinWallet);
        $mockEm->expects($this->any())
            ->method('flush')
            ->willThrowException(new \RuntimeException('Database is busy', 150780001));

        $request = new Request([], $parameters);
        $controller = new BitcoinWalletController();
        $container = static::$kernel->getContainer();
        $container->set('durian.block_chain', $mockBlockChain);
        $container->set('durian.operation_logger', $mockOperationLogger);
        $container->set('doctrine.orm.default_entity_manager', $mockEm);
        $controller->setContainer($container);

        $controller->editWalletAction($request, 1);
    }

    /**
     * 測試修改比特幣錢包帶入非整數比特幣出款手續費率
     */
    public function testEditWalletWithNotIntFeePerByte()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'FeePerByte must be an integer',
            150890005
        );

        $parameters = [
            'wallet_code' => '87654321-4321-4321-4321-210987654321',
            'password' => 'ttest',
            'second_password' => 'test',
            'api_code' => '87654321-4321-4321-4321-210987654321',
            'xpub' => 'xpub.....',
            'fee_per_byte' => '2.5',
        ];

        $mockBlockChain = $this->getMockBuilder('BB\DurianBundle\Payment\BlockChain')
            ->disableOriginalConstructor()
            ->getMock();

        $mockLogOp = $this->getMockBuilder('BB\DurianBundle\Entity\LogOperation')
            ->disableOriginalConstructor()
            ->setMethods(['addMessage'])
            ->getMock();
        $mockOperationLogger = $this->getMockBuilder('\BB\DurianBundle\Logger\Operation')
            ->disableOriginalConstructor()
            ->setMethods(['create', 'save'])
            ->getMock();
        $mockOperationLogger->expects($this->any())
            ->method('create')
            ->willReturn($mockLogOp);

        $mockBitcoinWallet = $this->getMockBuilder('BB\DurianBundle\Entity\BitcoinWallet')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods([
                'beginTransaction',
                'find',
                'flush',
                'rollback',
                'clear',
            ])
            ->getMock();
        $mockEm->expects($this->any())
            ->method('find')
            ->willReturn($mockBitcoinWallet);

        $request = new Request([], $parameters);
        $controller = new BitcoinWalletController();
        $container = static::$kernel->getContainer();
        $container->set('durian.block_chain', $mockBlockChain);
        $container->set('durian.operation_logger', $mockOperationLogger);
        $container->set('doctrine.orm.default_entity_manager', $mockEm);
        $controller->setContainer($container);

        $controller->editWalletAction($request, 1);
    }
}

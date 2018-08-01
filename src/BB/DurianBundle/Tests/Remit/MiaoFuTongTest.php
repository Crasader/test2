<?php

namespace BB\DurianBundle\Tests\Remit;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Remit\MiaoFuTong;

class MiaoFuTongTest extends DurianTestCase
{
    /**
     * Container 的 mock
     *
     * @var Symfony\Component\DependencyInjection\Container
     */
    private $container;

    /**
     * Doctrine 的 mock
     *
     * @var Doctrine\Bundle\DoctrineBundle\Registry
     */
    private $doctrine;

    /**
     * Auto Remit Checker 的 mock
     *
     * @var BB\DurianBundle\Remit\AutoRemitChecker
     */
    private $autoRemitChecker;

    /**
     * em 的 mock
     *
     * @var Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * RemitAccount 的 mock
     *
     * @var BB\DurianBundle\Entity\RemitAccount
     */
    private $remitAccount;

    /**
     * AutoRemit 的 mock
     *
     * @var BB\DurianBundle\Entity\AutoRemit
     */
    private $autoRemit;

    /**
     * BankInfo 的 mock
     *
     * @var BB\DurianBundle\Entity\BankInfo
     */
    private $bankInfo;

    public function setUp()
    {
        parent::setUp();

        $this->em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['find'])
            ->getMock();

        $this->doctrine = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->setMethods(['getmanager'])
            ->getMock();

        $this->doctrine->expects($this->any())
            ->method('getManager')
            ->willReturn($this->em);

        $this->autoRemitChecker = $this->getMockBuilder('BB\DurianBundle\Remit\AutoRemitChecker')
            ->disableOriginalConstructor()
            ->setMethods(['getPermission'])
            ->getMock();

        $this->container = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->setMethods(['get'])
            ->getMock();

        $this->container->expects($this->any())
            ->method('get')
            ->willReturn($this->doctrine);

        $this->remitAccount = $this->getMockBuilder('BB\DurianBundle\Entity\RemitAccount')
            ->disableOriginalConstructor()
            ->getMock();

        $this->autoRemit = $this->getMockBuilder('BB\DurianBundle\Entity\AutoRemit')
            ->disableOriginalConstructor()
            ->getMock();

        $this->bankInfo = $this->getMockBuilder('BB\DurianBundle\Entity\BankInfo')
            ->disableOriginalConstructor()
            ->getMock();

        $this->domainAutoRemit = $this->getMockBuilder('BB\DurianBundle\Entity\DomainAutoRemit')
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * 測試啟用自動認款帳號時，沒有設定自動認款帳號
     */
    public function testCheckAutoRemitAccountButNoAutoRemitAccountFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No auto RemitAccount found',
            150870014
        );

        $miaoFuTong = new MiaoFuTong();
        $miaoFuTong->setContainer($this->container);
        $miaoFuTong->checkAutoRemitAccount();
    }

    /**
     * 測試啟用自動認款帳號時，但銀行不支援
     */
    public function testCheckAutoRemitAccountButBankInfoIsNotSupportidByAutoRemitBankInfo()
    {
        $this->setExpectedException(
            'RuntimeException',
            'BankInfo is not supported by AutoRemitBankInfo',
            150870015
        );

        $this->bankInfo->expects($this->any())
            ->method('getId')
            ->willReturn(2);

        $this->autoRemit->expects($this->any())
            ->method('getBankInfo')
            ->willReturn([$this->bankInfo]);

        $this->em->expects($this->any())
            ->method('find')
            ->willReturn($this->autoRemit);

        $this->remitAccount->expects($this->any())
            ->method('getBankInfoId')
            ->willReturn(1);

        $miaoFuTong = new MiaoFuTong();
        $miaoFuTong->setContainer($this->container);
        $miaoFuTong->setRemitAccount($this->remitAccount);
        $miaoFuTong->checkAutoRemitAccount();
    }

    /**
     * 測試啟用自動認款帳號時，但找不到廳的設定
     */
    public function testCheckAutoRemitAccountButDomainAutoRemitNotFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Domain is not supported by AutoConfirm',
            150870016
        );

        $this->container = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->setMethods(['get'])
            ->getMock();

        $this->container->expects($this->exactly(2))
            ->method('get')
            ->willReturnOnConsecutiveCalls($this->doctrine, $this->autoRemitChecker);

        $this->autoRemitChecker->expects($this->any())
            ->method('getPermission')
            ->willReturn(null);

        $user = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();

        $this->bankInfo->expects($this->any())
            ->method('getId')
            ->willReturn(1);

        $this->autoRemit->expects($this->any())
            ->method('getBankInfo')
            ->willReturn([$this->bankInfo]);

        $this->em->expects($this->exactly(2))
            ->method('find')
            ->will($this->onConsecutiveCalls($this->autoRemit, $user));

        $this->remitAccount->expects($this->any())
            ->method('getBankInfoId')
            ->willReturn(1);

        $this->remitAccount->expects($this->any())
            ->method('getDomain')
            ->willReturn(1);

        $this->remitAccount->expects($this->any())
            ->method('getAutoRemitId')
            ->willReturn(1);

        $miaoFuTong = new MiaoFuTong();
        $miaoFuTong->setContainer($this->container);
        $miaoFuTong->setRemitAccount($this->remitAccount);
        $miaoFuTong->checkAutoRemitAccount();
    }

    /**
     * 測試啟用自動認款帳號時，但廳不支援
     */
    public function testCheckAutoRemitAccountButDomainIsNotSupportedByAutoConfirm()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Domain is not supported by AutoConfirm',
            150870016
        );

        $this->container = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->setMethods(['get'])
            ->getMock();

        $this->container->expects($this->exactly(2))
            ->method('get')
            ->willReturnOnConsecutiveCalls($this->doctrine, $this->autoRemitChecker);

        $this->autoRemitChecker->expects($this->any())
            ->method('getPermission')
            ->willReturn($this->domainAutoRemit);

        $user = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();

        $this->bankInfo->expects($this->any())
            ->method('getId')
            ->willReturn(1);

        $this->autoRemit->expects($this->any())
            ->method('getBankInfo')
            ->willReturn([$this->bankInfo]);

        $this->em->expects($this->exactly(2))
            ->method('find')
            ->will($this->onConsecutiveCalls($this->autoRemit, $user));

        $this->remitAccount->expects($this->any())
            ->method('getBankInfoId')
            ->willReturn(1);

        $this->remitAccount->expects($this->any())
            ->method('getDomain')
            ->willReturn(1);

        $this->remitAccount->expects($this->any())
            ->method('getAutoRemitId')
            ->willReturn(1);

        $this->domainAutoRemit->expects($this->any())
            ->method('getEnable')
            ->willReturn(false);

        $miaoFuTong = new MiaoFuTong();
        $miaoFuTong->setContainer($this->container);
        $miaoFuTong->setRemitAccount($this->remitAccount);
        $miaoFuTong->checkAutoRemitAccount();
    }

    public function tearDown()
    {
        parent::tearDown();
    }
}

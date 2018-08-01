<?php

namespace BB\DurianBundle\Tests\Remit;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Remit\BBv2;
use Buzz\Message\Response;

class BBv2Test extends WebTestCase
{
    /**
     * Container 的 mock
     *
     * @var Symfony\Component\DependencyInjection\Container
     */
    private $container;

    /**
     * Curl 的 mock
     *
     * @var \Buzz\Client\Curl
     */
    private $client;

    /**
     * Doctrine 的 mock
     *
     * @var Doctrine\Bundle\DoctrineBundle\Registry
     */
    private $doctrine;

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

    /**
     * DomainAutoRemit 的 mock
     *
     * @var BB\DurianBundle\Entity\DomainAutoRemit
     */
    private $domainAutoRemit;

    /**
     * Auto Remit Checker 的 mock
     *
     * @var BB\DurianBundle\Remit\AutoRemitChecker
     */
    private $autoRemitChecker;

    /**
     * User 的 mock
     *
     * @var BB\DurianBundle\Entity\User
     */
    private $user;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var BB\DurianBundle\Entity\DomainConfig
     */
    private $domainConfig;

    public function setUp()
    {
        parent::setUp();

        $this->logger = $this->getContainer()->get('durian.remit_auto_confirm_logger');

        $this->autoRemitChecker = $this->getMockBuilder('BB\DurianBundle\Remit\AutoRemitChecker')
            ->disableOriginalConstructor()
            ->setMethods(['getPermission'])
            ->getMock();

        $this->em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['find'])
            ->getMock();

        $this->doctrine = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->setMethods(['getManager'])
            ->getMock();

        $this->doctrine->expects($this->any())
            ->method('getManager')
            ->willReturn($this->em);

        $this->container = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->setMethods(['get', 'getParameter'])
            ->getMock();

        $this->container->expects($this->any())
            ->method('get')
            ->willReturnOnConsecutiveCalls($this->doctrine, $this->autoRemitChecker, $this->doctrine, $this->logger);

        $this->container->expects($this->any())
            ->method('getParameter')
            ->willReturn('payment.http.35.189.173.249');

        $this->client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();

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

        $this->user = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();

        $this->domainConfig = $this->getMockBuilder('BB\DurianBundle\Entity\DomainConfig')
            ->disableOriginalConstructor()
            ->getMock();

        $logDir = $this->getContainer()->getParameter('kernel.logs_dir') . DIRECTORY_SEPARATOR . 'test';
        $this->logPath = $logDir . DIRECTORY_SEPARATOR . 'remit_auto_confirm.log';
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

        $bbv2 = new BBv2();
        $bbv2->setContainer($this->container);
        $bbv2->checkAutoRemitAccount();
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

        $bbv2 = new BBv2();
        $bbv2->setContainer($this->container);
        $bbv2->setRemitAccount($this->remitAccount);
        $bbv2->checkAutoRemitAccount();
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

        $this->autoRemitChecker->expects($this->any())
            ->method('getPermission')
            ->willReturn(null);

        $this->bankInfo->expects($this->any())
            ->method('getId')
            ->willReturn(3);

        $this->autoRemit->expects($this->any())
            ->method('getBankInfo')
            ->willReturn([$this->bankInfo]);

        $this->em->expects($this->exactly(2))
            ->method('find')
            ->willReturnOnConsecutiveCalls($this->autoRemit, $this->user);

        $this->remitAccount->expects($this->any())
            ->method('getBankInfoId')
            ->willReturn(3);

        $this->remitAccount->expects($this->any())
            ->method('getDomain')
            ->willReturn(1);

        $this->remitAccount->expects($this->any())
            ->method('getAutoRemitId')
            ->willReturn(4);

        $bbv2 = new BBv2();
        $bbv2->setContainer($this->container);
        $bbv2->setClient($this->client);
        $bbv2->setRemitAccount($this->remitAccount);
        $bbv2->checkAutoRemitAccount();
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

        $this->domainAutoRemit->expects($this->any())
            ->method('getEnable')
            ->willReturn(false);

        $this->autoRemitChecker->expects($this->any())
            ->method('getPermission')
            ->willReturn($this->domainAutoRemit);

        $this->bankInfo->expects($this->any())
            ->method('getId')
            ->willReturn(3);

        $this->autoRemit->expects($this->any())
            ->method('getBankInfo')
            ->willReturn([$this->bankInfo]);

        $this->em->expects($this->exactly(2))
            ->method('find')
            ->willReturn($this->autoRemit, $this->user);

        $this->remitAccount->expects($this->any())
            ->method('getBankInfoId')
            ->willReturn(3);

        $this->remitAccount->expects($this->any())
            ->method('getDomain')
            ->willReturn(1);

        $this->remitAccount->expects($this->any())
            ->method('getAutoRemitId')
            ->willReturn(4);

        $bbv2 = new BBv2();
        $bbv2->setContainer($this->container);
        $bbv2->setRemitAccount($this->remitAccount);
        $bbv2->checkAutoRemitAccount();
    }

    /**
     * 測試檢查銀行卡是否可用時，自動認款平台無返回指定參數
     */
    public function testCheckAutoRemitAccountNoReturnParameterSpecified()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No auto confirm return parameter specified',
            150870018
        );

        $this->domainAutoRemit->expects($this->any())
            ->method('getEnable')
            ->willReturn(true);

        $this->autoRemitChecker->expects($this->any())
            ->method('getPermission')
            ->willReturn($this->domainAutoRemit);

        $this->bankInfo->expects($this->any())
            ->method('getId')
            ->willReturn(3);

        $this->autoRemit->expects($this->any())
            ->method('getBankInfo')
            ->willReturn([$this->bankInfo]);

        $this->em->expects($this->exactly(3))
            ->method('find')
            ->willReturn($this->autoRemit, $this->user, $this->domainConfig);

        $this->remitAccount->expects($this->any())
            ->method('getBankInfoId')
            ->willReturn(3);

        $this->remitAccount->expects($this->any())
            ->method('getDomain')
            ->willReturn(1);

        $this->remitAccount->expects($this->any())
            ->method('getAutoRemitId')
            ->willReturn(4);

        $response = new Response();
        $response->setContent('{}');
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $bbv2 = new BBv2();
        $bbv2->setContainer($this->container);
        $bbv2->setClient($this->client);
        $bbv2->setResponse($response);
        $bbv2->setRemitAccount($this->remitAccount);
        $bbv2->checkAutoRemitAccount();
    }

    /**
     * 測試自動認款平台不支援此銀行卡
     */
    public function testCheckAutoRemitAccountIsNotSupportedByAutoRemit()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Please confirm auto_remit_account in the platform.',
            150870027
        );

        $this->domainAutoRemit->expects($this->any())
            ->method('getEnable')
            ->willReturn(true);

        $this->autoRemitChecker->expects($this->any())
            ->method('getPermission')
            ->willReturn($this->domainAutoRemit);

        $this->bankInfo->expects($this->any())
            ->method('getId')
            ->willReturn(3);

        $this->autoRemit->expects($this->any())
            ->method('getBankInfo')
            ->willReturn([$this->bankInfo]);

        $this->em->expects($this->exactly(3))
            ->method('find')
            ->willReturn($this->autoRemit, $this->user, $this->domainConfig);

        $this->remitAccount->expects($this->any())
            ->method('getBankInfoId')
            ->willReturn(3);

        $this->remitAccount->expects($this->any())
            ->method('getDomain')
            ->willReturn(1);

        $this->remitAccount->expects($this->any())
            ->method('getAutoRemitId')
            ->willReturn(4);

        $this->domainAutoRemit->expects($this->any())
            ->method('getEnable')
            ->willReturn(true);

        $response = new Response();
        $response->setContent('{"bank_code":"ABOC","bank_account_number":"741236985","order_placeable":false}');
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $bbv2 = new BBv2();
        $bbv2->setContainer($this->container);
        $bbv2->setClient($this->client);
        $bbv2->setResponse($response);
        $bbv2->setRemitAccount($this->remitAccount);
        $bbv2->checkAutoRemitAccount();
    }

    /**
     * 測試提交訂單但找不到廳的設定
     */
    public function testSubmitAutoRemitEntryButDomainAutoRemitNotFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Domain is not supported by AutoConfirm',
            150870017
        );

        $this->domainAutoRemit->expects($this->any())
            ->method('getEnable')
            ->willReturn(false);

        $this->autoRemitChecker->expects($this->any())
            ->method('getPermission')
            ->willReturn($this->domainAutoRemit);

        $this->em->expects($this->exactly(2))
            ->method('find')
            ->willReturn($this->autoRemit, $this->user);

        $this->remitAccount->expects($this->any())
            ->method('getBankInfoId')
            ->willReturn(3);

        $this->remitAccount->expects($this->any())
            ->method('getDomain')
            ->willReturn(1);

        $this->remitAccount->expects($this->any())
            ->method('getAutoRemitId')
            ->willReturn(4);

        $bbv2 = new BBv2();
        $bbv2->setContainer($this->container);
        $bbv2->setRemitAccount($this->remitAccount);

        $payData = [
            'pay_card_number' => '987654321',
            'pay_username' => '王者榮耀',
            'amount' => '12.11',
            'username' => 'test1234',
        ];
        $bbv2->submitAutoRemitEntry('12345', $payData);
    }

    /**
     * 測試提交訂單但廳不支援
     */
    public function testSubmitAutoRemitEntryButDomainIsNotSupportedByAutoConfirm()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Domain is not supported by AutoConfirm',
            150870017
        );

        $this->autoRemitChecker->expects($this->any())
            ->method('getPermission')
            ->willReturn(null);

        $this->em->expects($this->exactly(2))
            ->method('find')
            ->willReturn($this->autoRemit, $this->user);

        $this->remitAccount->expects($this->any())
            ->method('getBankInfoId')
            ->willReturn(3);

        $this->remitAccount->expects($this->any())
            ->method('getDomain')
            ->willReturn(1);

        $this->remitAccount->expects($this->any())
            ->method('getAutoRemitId')
            ->willReturn(4);

        $this->domainAutoRemit->expects($this->any())
            ->method('getEnable')
            ->willReturn(false);

        $bbv2 = new BBv2();
        $bbv2->setContainer($this->container);
        $bbv2->setRemitAccount($this->remitAccount);

        $payData = [
            'pay_card_number' => '987654321',
            'pay_username' => '王者榮耀',
            'amount' => '12.11',
            'username' => 'test1234',
        ];
        $bbv2->submitAutoRemitEntry('12345', $payData);
    }

    /**
     * 測試提交訂單後，自動認款平台無返回訂單號
     */
    public function testSubmitAutoRemitEntryNotReturnHashId()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No auto confirm return parameter specified',
            150870018
        );

        $this->domainAutoRemit->expects($this->any())
            ->method('getEnable')
            ->willReturn(true);

        $this->autoRemitChecker->expects($this->any())
            ->method('getPermission')
            ->willReturn($this->domainAutoRemit);

        $this->em->expects($this->exactly(3))
            ->method('find')
            ->willReturn($this->autoRemit, $this->user, $this->domainConfig);

        $this->remitAccount->expects($this->any())
            ->method('getBankInfoId')
            ->willReturn(3);

        $this->remitAccount->expects($this->any())
            ->method('getDomain')
            ->willReturn(1);

        $this->remitAccount->expects($this->any())
            ->method('getAutoRemitId')
            ->willReturn(4);

        $response = new Response();
        $response->setContent('{}');
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $bbv2 = new BBv2();
        $bbv2->setContainer($this->container);
        $bbv2->setClient($this->client);
        $bbv2->setResponse($response);
        $bbv2->setRemitAccount($this->remitAccount);

        $payData = [
            'pay_card_number' => '987654321',
            'pay_username' => '王者榮耀',
            'amount' => '12.11',
            'username' => 'test1234',
        ];
        $bbv2->submitAutoRemitEntry('12345', $payData);
    }

    /**
     * 測試提交訂單
     */
    public function testSubmitAutoRemitEntry()
    {
        $this->domainAutoRemit->expects($this->any())
            ->method('getEnable')
            ->willReturn(true);

        $this->autoRemitChecker->expects($this->any())
            ->method('getPermission')
            ->willReturn($this->domainAutoRemit);

        $this->em->expects($this->exactly(3))
            ->method('find')
            ->willReturn($this->autoRemit, $this->user, $this->domainConfig);

        $this->remitAccount->expects($this->any())
            ->method('getBankInfoId')
            ->willReturn(3);

        $this->remitAccount->expects($this->any())
            ->method('getDomain')
            ->willReturn(1);

        $this->remitAccount->expects($this->any())
            ->method('getAutoRemitId')
            ->willReturn(4);

        $response = new Response();
        $response->setContent('{"hash_id":"ea1l5s3to"}');
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $bbv2 = new BBv2();
        $bbv2->setContainer($this->container);
        $bbv2->setClient($this->client);
        $bbv2->setResponse($response);
        $bbv2->setRemitAccount($this->remitAccount);

        $payData = [
            'pay_card_number' => '987654321',
            'pay_username' => '王者榮耀',
            'amount' => '12.11',
            'username' => 'test1234',
        ];
        $hashId = $bbv2->submitAutoRemitEntry('12345', $payData);

        $this->assertEquals('ea1l5s3to', $hashId);
    }

    /**
     * 測試取消訂單但廳不支援
     */
    public function testCancelAutoRemitEntryButDomainIsNotSupportedByAutoConfirm()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Domain is not supported by AutoConfirm',
            150870019
        );

        $this->domainAutoRemit->expects($this->any())
            ->method('getEnable')
            ->willReturn(false);

        $this->autoRemitChecker->expects($this->any())
            ->method('getPermission')
            ->willReturn($this->domainAutoRemit);

        $this->em->expects($this->exactly(2))
            ->method('find')
            ->willReturn($this->autoRemit, $this->user);

        $this->bankInfo->expects($this->any())
            ->method('getId')
            ->willReturn(3);

        $this->autoRemit->expects($this->any())
            ->method('getBankInfo')
            ->willReturn([$this->bankInfo]);

        $this->remitAccount->expects($this->any())
            ->method('getBankInfoId')
            ->willReturn(3);

        $this->remitAccount->expects($this->any())
            ->method('getDomain')
            ->willReturn(1);

        $this->remitAccount->expects($this->any())
            ->method('getAutoRemitId')
            ->willReturn(4);

        $remitEntry = $this->getMockBuilder('BB\DurianBundle\Entity\RemitEntry')
            ->disableOriginalConstructor()
            ->getMock();

        $bbv2 = new BBv2();
        $bbv2->setContainer($this->container);
        $bbv2->setRemitAccount($this->remitAccount);
        $bbv2->cancelAutoRemitEntry($remitEntry);
    }

    /**
     * 測試取消訂單但找不到支付平台訂單號
     */
    public function testCancelAutoRemitEntryButRemitAutoConfirmNotFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No RemitAutoConfirm found',
            150870020
        );

        $this->domainAutoRemit->expects($this->any())
            ->method('getEnable')
            ->willReturn(true);

        $this->autoRemitChecker->expects($this->any())
            ->method('getPermission')
            ->willReturn($this->domainAutoRemit);

        $this->em->expects($this->exactly(3))
            ->method('find')
            ->willReturn($this->autoRemit, $this->user, null);

        $this->bankInfo->expects($this->any())
            ->method('getId')
            ->willReturn(3);

        $this->autoRemit->expects($this->any())
            ->method('getBankInfo')
            ->willReturn([$this->bankInfo]);

        $this->remitAccount->expects($this->any())
            ->method('getBankInfoId')
            ->willReturn(3);

        $this->remitAccount->expects($this->any())
            ->method('getDomain')
            ->willReturn(1);

        $this->remitAccount->expects($this->any())
            ->method('getAutoRemitId')
            ->willReturn(4);

        $remitEntry = $this->getMockBuilder('BB\DurianBundle\Entity\RemitEntry')
            ->disableOriginalConstructor()
            ->getMock();

        $bbv2 = new BBv2();
        $bbv2->setContainer($this->container);
        $bbv2->setRemitAccount($this->remitAccount);
        $bbv2->cancelAutoRemitEntry($remitEntry);
    }

    /**
     * 測試取消訂單未返回指定參數
     */
    public function testCancelAutoRemitEntryButNoReturnParameterSpecified()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No auto confirm return parameter specified',
            150870018
        );

        $remitAutoConfirm = $this->getMockBuilder('BB\DurianBundle\Entity\RemitAutoConfirm')
            ->disableOriginalConstructor()
            ->getMock();

        $this->domainAutoRemit->expects($this->any())
            ->method('getEnable')
            ->willReturn(true);

        $this->autoRemitChecker->expects($this->any())
            ->method('getPermission')
            ->willReturn($this->domainAutoRemit);

        $this->em->expects($this->exactly(4))
            ->method('find')
            ->willReturn($this->autoRemit, $this->user, $remitAutoConfirm, $this->domainConfig);

        $remitAutoConfirm->expects($this->any())
            ->method('getAutoConfirmId')
            ->willReturn('12345');

        $this->bankInfo->expects($this->any())
            ->method('getId')
            ->willReturn(3);

        $this->autoRemit->expects($this->any())
            ->method('getBankInfo')
            ->willReturn([$this->bankInfo]);

        $this->remitAccount->expects($this->any())
            ->method('getBankInfoId')
            ->willReturn(3);

        $this->remitAccount->expects($this->any())
            ->method('getDomain')
            ->willReturn(1);

        $this->remitAccount->expects($this->any())
            ->method('getAutoRemitId')
            ->willReturn(4);

        $this->domainAutoRemit->expects($this->any())
            ->method('getEnable')
            ->willReturn(true);

        $remitEntry = $this->getMockBuilder('BB\DurianBundle\Entity\RemitEntry')
            ->disableOriginalConstructor()
            ->getMock();

        $response = new Response();
        $response->setContent('{"hash_id":"e5b431"}');
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $bbv2 = new BBv2();
        $bbv2->setContainer($this->container);
        $bbv2->setClient($this->client);
        $bbv2->setResponse($response);
        $bbv2->setRemitAccount($this->remitAccount);
        $bbv2->cancelAutoRemitEntry($remitEntry);
    }

    /**
     * 測試取消訂單失敗
     */
    public function testCancelAutoRemitEntryFailure()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Auto Confirm failed',
            150870025
        );

        $remitAutoConfirm = $this->getMockBuilder('BB\DurianBundle\Entity\RemitAutoConfirm')
            ->disableOriginalConstructor()
            ->getMock();

        $this->domainAutoRemit->expects($this->any())
            ->method('getEnable')
            ->willReturn(true);

        $this->autoRemitChecker->expects($this->any())
            ->method('getPermission')
            ->willReturn($this->domainAutoRemit);

        $this->em->expects($this->exactly(4))
            ->method('find')
            ->willReturn($this->autoRemit, $this->user, $remitAutoConfirm, $this->domainConfig);

        $remitAutoConfirm->expects($this->any())
            ->method('getAutoConfirmId')
            ->willReturn('12345');

        $this->bankInfo->expects($this->any())
            ->method('getId')
            ->willReturn(3);

        $this->autoRemit->expects($this->any())
            ->method('getBankInfo')
            ->willReturn([$this->bankInfo]);

        $this->remitAccount->expects($this->any())
            ->method('getBankInfoId')
            ->willReturn(3);

        $this->remitAccount->expects($this->any())
            ->method('getDomain')
            ->willReturn(1);

        $this->remitAccount->expects($this->any())
            ->method('getAutoRemitId')
            ->willReturn(4);

        $this->domainAutoRemit->expects($this->any())
            ->method('getEnable')
            ->willReturn(true);

        $remitEntry = $this->getMockBuilder('BB\DurianBundle\Entity\RemitEntry')
            ->disableOriginalConstructor()
            ->getMock();

        $response = new Response();
        $response->setContent('{"hash_id":"e5b431","status":"created"}');
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $bbv2 = new BBv2();
        $bbv2->setContainer($this->container);
        $bbv2->setClient($this->client);
        $bbv2->setResponse($response);
        $bbv2->setRemitAccount($this->remitAccount);
        $bbv2->cancelAutoRemitEntry($remitEntry);
    }

    /**
     * 測試取消訂單
     */
    public function testCancelAutoRemitEntry()
    {
        $remitAutoConfirm = $this->getMockBuilder('BB\DurianBundle\Entity\RemitAutoConfirm')
            ->disableOriginalConstructor()
            ->getMock();

        $this->domainAutoRemit->expects($this->any())
            ->method('getEnable')
            ->willReturn(true);

        $this->autoRemitChecker->expects($this->any())
            ->method('getPermission')
            ->willReturn($this->domainAutoRemit);

        $this->em->expects($this->exactly(4))
            ->method('find')
            ->willReturn($this->autoRemit, $this->user, $remitAutoConfirm, $this->domainConfig);

        $remitAutoConfirm->expects($this->any())
            ->method('getAutoConfirmId')
            ->willReturn('12345');

        $this->bankInfo->expects($this->any())
            ->method('getId')
            ->willReturn(3);

        $this->autoRemit->expects($this->any())
            ->method('getBankInfo')
            ->willReturn([$this->bankInfo]);

        $this->remitAccount->expects($this->any())
            ->method('getBankInfoId')
            ->willReturn(3);

        $this->remitAccount->expects($this->any())
            ->method('getDomain')
            ->willReturn(1);

        $this->remitAccount->expects($this->any())
            ->method('getAutoRemitId')
            ->willReturn(4);

        $this->domainAutoRemit->expects($this->any())
            ->method('getEnable')
            ->willReturn(true);

        $remitEntry = $this->getMockBuilder('BB\DurianBundle\Entity\RemitEntry')
            ->disableOriginalConstructor()
            ->getMock();

        $response = new Response();
        $response->setContent('{"hash_id":"e5b431","status":"revoked"}');
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $bbv2 = new BBv2();
        $bbv2->setContainer($this->container);
        $bbv2->setClient($this->client);
        $bbv2->setResponse($response);
        $bbv2->setRemitAccount($this->remitAccount);
        $bbv2->cancelAutoRemitEntry($remitEntry);
    }

    /**
     * 測試自動認款平台連線失敗
     */
    public function testAutoConfirmConnectionFailure()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Auto Confirm connection failure',
            150870021
        );

        $this->domainAutoRemit->expects($this->any())
            ->method('getEnable')
            ->willReturn(true);

        $this->autoRemitChecker->expects($this->any())
            ->method('getPermission')
            ->willReturn($this->domainAutoRemit);

        $this->em->expects($this->exactly(3))
            ->method('find')
            ->willReturn($this->autoRemit, $this->user, $this->domainConfig);

        $this->bankInfo->expects($this->any())
            ->method('getId')
            ->willReturn(3);

        $this->autoRemit->expects($this->any())
            ->method('getBankInfo')
            ->willReturn([$this->bankInfo]);

        $this->remitAccount->expects($this->any())
            ->method('getBankInfoId')
            ->willReturn(3);

        $this->remitAccount->expects($this->any())
            ->method('getDomain')
            ->willReturn(1);

        $this->remitAccount->expects($this->any())
            ->method('getAutoRemitId')
            ->willReturn(4);

        $this->domainAutoRemit->expects($this->any())
            ->method('getEnable')
            ->willReturn(true);

        $exception = new \Exception('Auto Confirm connection failure', 150870021);
        $this->client->expects($this->any())
            ->method('send')
            ->willThrowException($exception);

        $bbv2 = new BBv2();
        $bbv2->setContainer($this->container);
        $bbv2->setClient($this->client);
        $bbv2->setRemitAccount($this->remitAccount);
        $bbv2->checkAutoRemitAccount();
    }

    /**
     * 測試自動認款平台連線失敗返回404 Not Found
     */
    public function testAutoConfirmConnectionFailureNotFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'AutoRemitAccount not exist',
            150870023
        );

        $this->domainAutoRemit->expects($this->any())
            ->method('getEnable')
            ->willReturn(true);

        $this->autoRemitChecker->expects($this->any())
            ->method('getPermission')
            ->willReturn($this->domainAutoRemit);

        $this->em->expects($this->exactly(3))
            ->method('find')
            ->willReturn($this->autoRemit, $this->user, $this->domainConfig);

        $this->bankInfo->expects($this->any())
            ->method('getId')
            ->willReturn(3);

        $this->autoRemit->expects($this->any())
            ->method('getBankInfo')
            ->willReturn([$this->bankInfo]);

        $this->remitAccount->expects($this->any())
            ->method('getBankInfoId')
            ->willReturn(3);

        $this->remitAccount->expects($this->any())
            ->method('getDomain')
            ->willReturn(1);

        $this->remitAccount->expects($this->any())
            ->method('getAutoRemitId')
            ->willReturn(4);

        $this->domainAutoRemit->expects($this->any())
            ->method('getEnable')
            ->willReturn(true);

        $response = new Response();
        $response->addHeader('HTTP/1.1 404 Not Found');
        $response->addHeader('Content-Type: application/json');

        $bbv2 = new BBv2();
        $bbv2->setContainer($this->container);
        $bbv2->setClient($this->client);
        $bbv2->setResponse($response);
        $bbv2->setRemitAccount($this->remitAccount);
        $bbv2->checkAutoRemitAccount();
    }

    /**
     * 測試自動認款平台失敗有返回錯誤訊息
     */
    public function testAutoConfirmFailureHasErrorMessage()
    {
        $this->setExpectedException(
            'RuntimeException',
            'AutoRemitAccount not exist',
            150870023
        );

        $this->domainAutoRemit->expects($this->any())
            ->method('getEnable')
            ->willReturn(true);

        $this->autoRemitChecker->expects($this->any())
            ->method('getPermission')
            ->willReturn($this->domainAutoRemit);

        $this->bankInfo->expects($this->any())
            ->method('getId')
            ->willReturn(3);

        $this->autoRemit->expects($this->any())
            ->method('getBankInfo')
            ->willReturn([$this->bankInfo]);

        $this->em->expects($this->exactly(3))
            ->method('find')
            ->willReturn($this->autoRemit, $this->user, $this->domainConfig);

        $this->remitAccount->expects($this->any())
            ->method('getBankInfoId')
            ->willReturn(3);

        $this->remitAccount->expects($this->any())
            ->method('getDomain')
            ->willReturn(1);

        $this->remitAccount->expects($this->any())
            ->method('getAutoRemitId')
            ->willReturn(4);

        $this->domainAutoRemit->expects($this->any())
            ->method('getEnable')
            ->willReturn(true);

        $response = new Response();
        $response->setContent('{"error_code":1002,"error_message":"不存在的資料"}');
        $response->addHeader('HTTP/1.1 404 Not Found');
        $response->addHeader('Content-Type: application/json');

        $bbv2 = new BBv2();
        $bbv2->setContainer($this->container);
        $bbv2->setClient($this->client);
        $bbv2->setResponse($response);
        $bbv2->setRemitAccount($this->remitAccount);
        $bbv2->checkAutoRemitAccount();
    }

    /**
     * 測試自動認款平台返回失敗
     */
    public function testAutoConfirmFailure()
    {
        $this->setExpectedException(
            'RuntimeException',
            'AutoRemitAccount not exist',
            150870023
        );

        $this->domainAutoRemit->expects($this->any())
            ->method('getEnable')
            ->willReturn(true);

        $this->autoRemitChecker->expects($this->any())
            ->method('getPermission')
            ->willReturn($this->domainAutoRemit);

        $this->bankInfo->expects($this->any())
            ->method('getId')
            ->willReturn(3);

        $this->autoRemit->expects($this->any())
            ->method('getBankInfo')
            ->willReturn([$this->bankInfo]);

        $this->em->expects($this->exactly(3))
            ->method('find')
            ->willReturn($this->autoRemit, $this->user, $this->domainConfig);

        $this->remitAccount->expects($this->any())
            ->method('getBankInfoId')
            ->willReturn(3);

        $this->remitAccount->expects($this->any())
            ->method('getDomain')
            ->willReturn(1);

        $this->remitAccount->expects($this->any())
            ->method('getAutoRemitId')
            ->willReturn(4);

        $this->domainAutoRemit->expects($this->any())
            ->method('getEnable')
            ->willReturn(true);

        $response = new Response();
        $response->setContent('{"error_code":1002}');
        $response->addHeader('HTTP/1.1 404 Not Found');
        $response->addHeader('Content-Type: application/json');

        $bbv2 = new BBv2();
        $bbv2->setContainer($this->container);
        $bbv2->setClient($this->client);
        $bbv2->setResponse($response);
        $bbv2->setRemitAccount($this->remitAccount);
        $bbv2->checkAutoRemitAccount();
    }

    public function tearDown()
    {
        if (file_exists($this->logPath)) {
            unlink($this->logPath);
        }

        parent::tearDown();
    }
}

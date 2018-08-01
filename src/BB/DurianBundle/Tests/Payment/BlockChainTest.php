<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\BlockChain;
use BB\DurianBundle\Entity\BitcoinWallet;
use BB\DurianBundle\Exception\PaymentConnectionException;
use Buzz\Message\Response;

class BlockChainTest extends DurianTestCase
{
    /**
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    private $container;

    /**
     * @var \Buzz\Client\Curl
     */
    private $client;

    /**
     * @var string
     */
    private $logPath;

    public function setUp()
    {
        parent::setUp();

        $kernel = new \AppKernel('test', true);
        $kernel->boot();

        $logDir = $kernel->getContainer()->getParameter('kernel.logs_dir') . DIRECTORY_SEPARATOR . 'test';
        $this->logPath = $logDir . DIRECTORY_SEPARATOR . 'payment.log';

        $logger = $kernel->getContainer()->get('durian.payment_logger');
        $validator = $kernel->getContainer()->get('durian.validator');

        $this->container = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->setMethods(['get'])
            ->getMock();

        $getMap = [
            ['durian.payment_logger', 1, $logger],
            ['durian.validator', 1, $validator],
        ];

        $this->container->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap($getMap));

        $this->container->setParameter('payment_ip', '192.168.181.129');

        $this->client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();
    }

    /**
     * 測試取得比特幣匯率帶入不支援的幣別
     */
    public function testGetExchangeWithErrCurrency()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Illegal currency',
            150180205
        );

        $currency = 'MYR';

        $blockChain = new BlockChain();
        $blockChain->setContainer($this->container);
        $blockChain->getExchange($currency);
    }

    /**
     * 測試取得比特幣匯率帶入不支援的幣別
     */
    public function testGetExchangeWithServerErrCurrency()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Parameter <currency> with unsupported symbol',
            150180202
        );

        $response = new Response();
        $response->setContent("Parameter <currency> with unsupported symbol");
        $response->addHeader('HTTP/1.1 500 Internal Server Error');
        $response->addHeader('Content-Type: text/plain;charset=UTF-8');

        $currency = 'CNY';

        $blockChain = new BlockChain();
        $blockChain->setContainer($this->container);
        $blockChain->setClient($this->client);
        $blockChain->setResponse($response);
        $blockChain->getExchange($currency);
    }

    /**
     * 測試取得比特幣匯率
     */
    public function testGetExchange()
    {
        $response = new Response();
        $response->setContent("0.00014564");
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: text/plain;charset=UTF-8');

        $currency = 'USD';

        $blockChain = new BlockChain();
        $blockChain->setContainer($this->container);
        $blockChain->setClient($this->client);
        $blockChain->setResponse($response);
        $exchange = $blockChain->getExchange($currency);

        $this->assertEquals(0.00014564, $exchange);
    }

    /**
     * 驗證比特幣資料帶入錯誤錢包帳號，server未啟動
     */
    public function testValidateBitcoinWalletWithoutStartingServer()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Parse data error',
            150180204
        );

        $content = '<html><head><title>502 Bad Gateway</title></head><body bgcolor="white">';
        $content .= '<center><h1>502 Bad Gateway</h1></center><hr><center>nginx/1.12.1</center></body></html>';
        $response = new Response();
        $response->setContent($content);
        $response->addHeader('HTTP/1.1 502');
        $response->addHeader('Content-Type: text/html; charset=UTF-8');

        $blockChain = new BlockChain();
        $blockChain->setContainer($this->container);
        $blockChain->setClient($this->client);
        $blockChain->setResponse($response);
        $blockChain->validateBitcoinWallet("testWalletId", 'password');
    }

    /**
     * 驗證比特幣資料帶入錯誤錢包帳號
     */
    public function testValidateBitcoinWalletWithErrWalletCode()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Unexpected error, please try again',
            150180202
        );

        $response = new Response();
        $response->setContent('{"error":"Unexpected error, please try again"}');
        $response->addHeader('HTTP/1.1 500 Internal Server Error');
        $response->addHeader('Content-Type: application/json; charset=utf-8');

        $blockChain = new BlockChain();
        $blockChain->setContainer($this->container);
        $blockChain->setClient($this->client);
        $blockChain->setResponse($response);
        $blockChain->validateBitcoinWallet("errWalletId", 'password');
    }

    /**
     * 驗證比特幣資料帶入錯誤錢包密碼
     */
    public function testValidateBitcoinWalletWithErrPassword()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Main wallet password incorrect',
            150180202
        );

        $response = new Response();
        $response->setContent('{"error":"Main wallet password incorrect"}');
        $response->addHeader('HTTP/1.1 500 Internal Server Error');
        $response->addHeader('Content-Type: application/json; charset=utf-8');

        $blockChain = new BlockChain();
        $blockChain->setContainer($this->container);
        $blockChain->setClient($this->client);
        $blockChain->setResponse($response);
        $blockChain->validateBitcoinWallet("testWalletId", 'errPassword');
    }

    /**
     * 驗證比特幣資料帶入錯誤錢包密碼，blockChain返回空
     */
    public function testValidateBitcoinWalletWithEmptyResult()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Parse data error',
            150180204
        );

        $response = new Response();
        $response->setContent('{}');
        $response->addHeader('HTTP/1.1 500 Internal Server Error');
        $response->addHeader('Content-Type: application/json; charset=utf-8');

        $blockChain = new BlockChain();
        $blockChain->setContainer($this->container);
        $blockChain->setClient($this->client);
        $blockChain->setResponse($response);
        $blockChain->validateBitcoinWallet("testWalletId", 'password');
    }

    /**
     * 驗證比特幣正確資料
     */
    public function testValidateBitcoinWallet()
    {
        $response = new Response();
        $response->setContent('{"balance":286863}');
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json; charset=utf-8');

        $blockChain = new BlockChain();
        $blockChain->setContainer($this->container);
        $blockChain->setClient($this->client);
        $blockChain->setResponse($response);
        $blockChain->validateBitcoinWallet("testWalletId", 'password');

        // 檢查log內密碼類參數是否遮罩
        $logResults = explode(PHP_EOL, file_get_contents($this->logPath));

        $requestLog = 'REQUEST: password=******';
        $this->assertContains($requestLog, $logResults[0]);
    }

    /**
     * 測試新建入款帳戶與位址，server未啟動
     */
    public function testCreateAccountAddressWithoutStartingServer()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Parse data error',
            150180204
        );

        $content = '<html><head><title>502 Bad Gateway</title></head><body bgcolor="white">';
        $content .= '<center><h1>502 Bad Gateway</h1></center><hr><center>nginx/1.12.1</center></body></html>';
        $response = new Response();
        $response->setContent($content);
        $response->addHeader('HTTP/1.1 502');
        $response->addHeader('Content-Type: text/html; charset=UTF-8');

        $blockChain = new BlockChain();
        $blockChain->setContainer($this->container);
        $blockChain->setClient($this->client);
        $blockChain->setResponse($response);

        $bitcoinWallet = new BitcoinWallet(1, 'wallet_id', 'passwd', 'apiCode');
        $username = 'rd5Test';
        $blockChain->createAccountAddress($bitcoinWallet, $username);
    }

    /**
     * 測試新建入款帳戶與位址，blockChain有設定第二密碼卻未帶入
     */
    public function testCreateAccountAddressWithoutSecondPassword()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Second password incorrect',
            150180202
        );

        $response = new Response();
        $response->setContent('{"error":"Second password incorrect"}');
        $response->addHeader('HTTP/1.1 500 Internal Server Error');
        $response->addHeader('Content-Type: application/json; charset=utf-8');

        $blockChain = new BlockChain();
        $blockChain->setContainer($this->container);
        $blockChain->setClient($this->client);
        $blockChain->setResponse($response);

        $bitcoinWallet = new BitcoinWallet(1, 'wallet_id', 'passwd', 'apiCode');
        $username = 'rd5Test';
        $blockChain->createAccountAddress($bitcoinWallet, $username);
    }

    /**
     * 測試新建入款帳戶與位址，blockChain返回空
     */
    public function testCreateAccountAddressWithEmptyResult()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Parse data error',
            150180204
        );

        $response = new Response();
        $response->setContent('{}');
        $response->addHeader('HTTP/1.1 500 Internal Server Error');
        $response->addHeader('Content-Type: application/json; charset=utf-8');

        $blockChain = new BlockChain();
        $blockChain->setContainer($this->container);
        $blockChain->setClient($this->client);
        $blockChain->setResponse($response);

        $bitcoinWallet = new BitcoinWallet(1, 'wallet_id', 'passwd', 'apiCode');
        $username = 'rd5Test';
        $blockChain->createAccountAddress($bitcoinWallet, $username);
    }

    /**
     * 測試新建入款帳戶與位址，取位址時server連線失敗
     */
    public function testCreateAccountAddressWithServerError()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Parse data error',
            150180204
        );

        $mockResponse = $this->getMockBuilder('Buzz\Message\Response')
            ->disableOriginalConstructor()
            ->setMethods(['getContent', 'getHeader'])
            ->getMock();

        $xpub = 'xpub6CVBKvozRjcxAKe2ePA3NjAZ4wSPg9S7i2bvWECYwpvUUvgXL3CsSr1PzfGR5dfgQRTMGzdNQbsi8y';
        $xpub .= '7RSMDaLCQppqBeo7dERyR751rKyuX';
        $xpriv = 'xprv9yVpvRH6bN4ewqZZYMd31bDpWubuGgiGLogKhqnwPVPVc8MNnVtcu3gv9QnuMqf1XL6cx6c9xC863';
        $xpriv .= 'JdVjYdA4gvqKjMXqUDxkbefGWAbMtb';
        $receiveAccount = 'xpub6DjjgnkdFobYd3cJAQfqRPuommkzUm7gJAapdhdZ4KZfgnNefepY14gdFGn7SwiYzTXYh2y';
        $receiveAccount .= 'VfazXb5vGyK8ARZS1BqAQo4xqecbV7D5P5VL';
        $changeAccount = 'xpub6DjjgnkdFobYeUCFzPM6HiCdKoy9cFrtZwtoSeLiSPt5M7nBtod72bftFcKvbmRhUZp82h';
        $changeAccount .= 'QhhyfbS7vurqW4FbfjpSfbxLFagVWcdk2pVqJ';
        $contentArray = [
            'label' => 'rd5Test',
            'archived' => false,
            'xpriv' => $xpriv,
            'xpub' => $xpub,
            'address_labels' => [],
            'cache' => [
                'receiveAccount' => $receiveAccount,
                'changeAccount' => $changeAccount,
            ],
        ];
        $mockResponse->expects($this->at(0))
            ->method('getContent')
            ->willReturn(json_encode($contentArray));

        $mockResponse->expects($this->at(1))
            ->method('getHeader')
            ->willReturn('Content-Type: application/json; charset=utf-8');

        $addressContent = '<html><head><title>502 Bad Gateway</title></head><body bgcolor="white">';
        $addressContent .= '<center><h1>502 Bad Gateway</h1></center><hr><center>nginx/1.12.1</center></body></html>';
        $mockResponse->expects($this->at(2))
            ->method('getContent')
            ->willReturn($addressContent);

        $mockResponse->expects($this->at(3))
            ->method('getHeader')
            ->willReturn('Content-Type: text/html; charset=UTF-8');

        $blockChain = new BlockChain();
        $blockChain->setContainer($this->container);
        $blockChain->setClient($this->client);
        $blockChain->setResponse($mockResponse);

        $bitcoinWallet = new BitcoinWallet(1, 'wallet_id', 'passwd', 'apiCode');
        $username = 'rd5Test';
        $blockChain->createAccountAddress($bitcoinWallet, $username);
    }

    /**
     * 測試新建入款帳戶與位址，取位址時帶入錯誤密碼
     */
    public function testCreateAccountAddressWithErrPassword()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Main wallet password incorrect',
            150180202
        );

        $mockResponse = $this->getMockBuilder('Buzz\Message\Response')
            ->disableOriginalConstructor()
            ->setMethods(['getContent', 'getHeader'])
            ->getMock();

        $xpub = 'xpub6CVBKvozRjcxAKe2ePA3NjAZ4wSPg9S7i2bvWECYwpvUUvgXL3CsSr1PzfGR5dfgQRTMGzdNQbsi8y';
        $xpub .= '7RSMDaLCQppqBeo7dERyR751rKyuX';
        $xpriv = 'xprv9yVpvRH6bN4ewqZZYMd31bDpWubuGgiGLogKhqnwPVPVc8MNnVtcu3gv9QnuMqf1XL6cx6c9xC863';
        $xpriv .= 'JdVjYdA4gvqKjMXqUDxkbefGWAbMtb';
        $receiveAccount = 'xpub6DjjgnkdFobYd3cJAQfqRPuommkzUm7gJAapdhdZ4KZfgnNefepY14gdFGn7SwiYzTXYh2y';
        $receiveAccount .= 'VfazXb5vGyK8ARZS1BqAQo4xqecbV7D5P5VL';
        $changeAccount = 'xpub6DjjgnkdFobYeUCFzPM6HiCdKoy9cFrtZwtoSeLiSPt5M7nBtod72bftFcKvbmRhUZp82h';
        $changeAccount .= 'QhhyfbS7vurqW4FbfjpSfbxLFagVWcdk2pVqJ';
        $contentArray = [
            'label' => 'rd5Test',
            'archived' => false,
            'xpriv' => $xpriv,
            'xpub' => $xpub,
            'address_labels' => [],
            'cache' => [
                'receiveAccount' => $receiveAccount,
                'changeAccount' => $changeAccount,
            ],
        ];
        $mockResponse->expects($this->at(0))
            ->method('getContent')
            ->willReturn(json_encode($contentArray));

        $mockResponse->expects($this->at(1))
            ->method('getHeader')
            ->willReturn('Content-Type: application/json; charset=utf-8');

        $mockResponse->expects($this->at(2))
            ->method('getContent')
            ->willReturn('{"error":"Main wallet password incorrect"}');

        $mockResponse->expects($this->at(3))
            ->method('getHeader')
            ->willReturn('Content-Type: application/json; charset=utf-8');

        $blockChain = new BlockChain();
        $blockChain->setContainer($this->container);
        $blockChain->setClient($this->client);
        $blockChain->setResponse($mockResponse);

        $bitcoinWallet = new BitcoinWallet(1, 'wallet_id', 'passwd', 'apiCode');
        $username = 'rd5Test';
        $blockChain->createAccountAddress($bitcoinWallet, $username);
    }

    /**
     * 測試新建入款帳戶與位址，取位址時帶入錯誤帳戶
     */
    public function testCreateAccountAddressWithErrAccount()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Parse data error',
            150180204
        );

        $mockResponse = $this->getMockBuilder('Buzz\Message\Response')
            ->disableOriginalConstructor()
            ->setMethods(['getContent', 'getHeader'])
            ->getMock();

        $xpub = 'xpub6CVBKvozRjcxAKe2ePA3NjAZ4wSPg9S7i2bvWECYwpvUUvgXL3CsSr1PzfGR5dfgQRTMGzdNQbsi8y';
        $xpub .= '7RSMDaLCQppqBeo7dERyR751rKyuX';
        $xpriv = 'xprv9yVpvRH6bN4ewqZZYMd31bDpWubuGgiGLogKhqnwPVPVc8MNnVtcu3gv9QnuMqf1XL6cx6c9xC863';
        $xpriv .= 'JdVjYdA4gvqKjMXqUDxkbefGWAbMtb';
        $receiveAccount = 'xpub6DjjgnkdFobYd3cJAQfqRPuommkzUm7gJAapdhdZ4KZfgnNefepY14gdFGn7SwiYzTXYh2y';
        $receiveAccount .= 'VfazXb5vGyK8ARZS1BqAQo4xqecbV7D5P5VL';
        $changeAccount = 'xpub6DjjgnkdFobYeUCFzPM6HiCdKoy9cFrtZwtoSeLiSPt5M7nBtod72bftFcKvbmRhUZp82h';
        $changeAccount .= 'QhhyfbS7vurqW4FbfjpSfbxLFagVWcdk2pVqJ';
        $contentArray = [
            'label' => 'rd5Test',
            'archived' => false,
            'xpriv' => $xpriv,
            'xpub' => $xpub,
            'address_labels' => [],
            'cache' => [
                'receiveAccount' => $receiveAccount,
                'changeAccount' => $changeAccount,
            ],
        ];
        $mockResponse->expects($this->at(0))
            ->method('getContent')
            ->willReturn(json_encode($contentArray));

        $mockResponse->expects($this->at(1))
            ->method('getHeader')
            ->willReturn('Content-Type: application/json; charset=utf-8');

        $mockResponse->expects($this->at(2))
            ->method('getContent')
            ->willReturn('{}');

        $mockResponse->expects($this->at(3))
            ->method('getHeader')
            ->willReturn('Content-Type: application/json; charset=utf-8');

        $blockChain = new BlockChain();
        $blockChain->setContainer($this->container);
        $blockChain->setClient($this->client);
        $blockChain->setResponse($mockResponse);

        $bitcoinWallet = new BitcoinWallet(1, 'wallet_id', 'passwd', 'apiCode');
        $username = 'rd5Test';
        $blockChain->createAccountAddress($bitcoinWallet, $username);
    }

    /**
     * 測試新建入款帳戶與位址
     */
    public function testCreateAccountAddress()
    {
        $mockResponse = $this->getMockBuilder('Buzz\Message\Response')
            ->disableOriginalConstructor()
            ->setMethods(['getContent', 'getHeader'])
            ->getMock();

        $xpub = 'xpub6CVBKvozRjcxAKe2ePA3NjAZ4wSPg9S7i2bvWECYwpvUUvgXL3CsSr1PzfGR5dfgQRTMGzdNQbsi8y';
        $xpub .= '7RSMDaLCQppqBeo7dERyR751rKyuX';
        $xpriv = 'xprv9yVpvRH6bN4ewqZZYMd31bDpWubuGgiGLogKhqnwPVPVc8MNnVtcu3gv9QnuMqf1XL6cx6c9xC863';
        $xpriv .= 'JdVjYdA4gvqKjMXqUDxkbefGWAbMtb';
        $receiveAccount = 'xpub6DjjgnkdFobYd3cJAQfqRPuommkzUm7gJAapdhdZ4KZfgnNefepY14gdFGn7SwiYzTXYh2y';
        $receiveAccount .= 'VfazXb5vGyK8ARZS1BqAQo4xqecbV7D5P5VL';
        $changeAccount = 'xpub6DjjgnkdFobYeUCFzPM6HiCdKoy9cFrtZwtoSeLiSPt5M7nBtod72bftFcKvbmRhUZp82h';
        $changeAccount .= 'QhhyfbS7vurqW4FbfjpSfbxLFagVWcdk2pVqJ';
        $contentArray = [
            'label' => 'rd5Test',
            'archived' => false,
            'xpriv' => $xpriv,
            'xpub' => $xpub,
            'address_labels' => [],
            'cache' => [
                'receiveAccount' => $receiveAccount,
                'changeAccount' => $changeAccount,
            ],
        ];
        $mockResponse->expects($this->at(0))
            ->method('getContent')
            ->willReturn(json_encode($contentArray));

        $mockResponse->expects($this->at(1))
            ->method('getHeader')
            ->willReturn('Content-Type: application/json; charset=utf-8');

        $mockResponse->expects($this->at(2))
            ->method('getContent')
            ->willReturn('{"address":"184LUvsHAZD71jyaamZRbdPF3KxnQgZUPH"}');

        $mockResponse->expects($this->at(3))
            ->method('getHeader')
            ->willReturn('Content-Type: application/json; charset=utf-8');

        $blockChain = new BlockChain();
        $blockChain->setContainer($this->container);
        $blockChain->setClient($this->client);
        $blockChain->setResponse($mockResponse);

        $bitcoinWallet = new BitcoinWallet(1, 'wallet_id', 'passwd', 'apiCode');
        $bitcoinWallet->setSecondPassword('secondPassword');
        $username = 'rd5Test';
        $result = $blockChain->createAccountAddress($bitcoinWallet, $username);
        $this->assertArrayHasKey('account', $result);
        $this->assertArrayHasKey('address', $result);
        $this->assertEquals($xpub, $result['account']);
        $this->assertEquals('184LUvsHAZD71jyaamZRbdPF3KxnQgZUPH', $result['address']);

        // 檢查log內密碼類參數是否遮罩
        $logResults = explode(PHP_EOL, file_get_contents($this->logPath));

        $createAccountRequest = 'REQUEST: label=rd5Test&password=******&api_code=******&second_password=******';
        $createAccountResponseArray = [
            'label' => 'rd5Test',
            'archived' => false,
            'xpriv' => '******',
            'xpub' => '******',
            'address_labels' => [],
            'cache' => '******',
        ];
        $this->assertContains($createAccountRequest, $logResults[0]);
        $this->assertContains('RESPONSE: ' . json_encode($createAccountResponseArray), $logResults[0]);

        $receiveAddressUri = '/merchant/wallet_id/accounts/******/receiveAddress';
        $receiveAddressRequest = 'REQUEST: password=******&api_code=******';
        $this->assertContains($receiveAddressUri, $logResults[1]);
        $this->assertContains($receiveAddressRequest, $logResults[1]);
    }

    /**
     * 測試轉帳帶入錯誤出款帳戶
     */
    public function testMakePaymentWithErrXpub()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Invalid bitcoin payment xPub',
            150180203
        );

        $response = new Response();
        $response->setContent('');
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json; charset=UTF-8');

        $blockChain = new BlockChain();
        $blockChain->setContainer($this->container);
        $blockChain->setClient($this->client);
        $blockChain->setResponse($response);

        $bitcoinWallet = new BitcoinWallet(1, 'wallet_id', 'passwd', 'apiCode');
        $blockChain->makePayment($bitcoinWallet, 'errXpub...', 'reveiceAddress', 0.00010000);
    }

    /**
     * 測試轉帳帶入錯誤出款帳戶
     */
    public function testMakePaymentWithTooSmall()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment Gateway connection failure',
            180088
        );

        $mockResponse = $this->getMockBuilder('Buzz\Message\Response')
            ->disableOriginalConstructor()
            ->setMethods(['getContent', 'getHeader'])
            ->getMock();

        $exception = new PaymentConnectionException('Payment Gateway connection failure', 180088, 0);
        $mockResponse->expects($this->at(0))
            ->method('getContent')
            ->will($this->throwException($exception));

        $blockChain = new BlockChain();
        $blockChain->setContainer($this->container);
        $blockChain->setClient($this->client);
        $blockChain->setResponse($mockResponse);

        $bitcoinWallet = new BitcoinWallet(1, 'wallet_id', 'passwd', 'apiCode');
        $blockChain->makePayment($bitcoinWallet, 'errXpub...', 'reveiceAddress', 0.00010000);
    }

    /**
     * 測試轉帳，server未啟動
     */
    public function testMakePaymentWithoutStartingServer()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Parse data error',
            150180204
        );

        $content = '<html><head><title>502 Bad Gateway</title></head><body bgcolor="white">';
        $content .= '<center><h1>502 Bad Gateway</h1></center><hr><center>nginx/1.12.1</center></body></html>';
        $response = new Response();
        $response->setContent($content);
        $response->addHeader('HTTP/1.1 502');
        $response->addHeader('Content-Type: text/html; charset=UTF-8');

        $blockChain = new BlockChain();
        $blockChain->setContainer($this->container);
        $blockChain->setClient($this->client);
        $blockChain->setResponse($response);

        $bitcoinWallet = new BitcoinWallet(1, 'wallet_id', 'passwd', 'apiCode');
        $blockChain->makePayment($bitcoinWallet, 'xpub...', 'reveiceAddress', 0.00010000);
    }

    /**
     * 測試轉帳帶入錯誤錢包密碼
     */
    public function testMakePaymentWithErrPassword()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Main wallet password incorrect',
            150180202
        );

        $response = new Response();
        $response->setContent('{"error":"Main wallet password incorrect"}');
        $response->addHeader('HTTP/1.1 500 Internal Server Error');
        $response->addHeader('Content-Type: application/json; charset=UTF-8');

        $blockChain = new BlockChain();
        $blockChain->setContainer($this->container);
        $blockChain->setClient($this->client);
        $blockChain->setResponse($response);

        $bitcoinWallet = new BitcoinWallet(1, 'wallet_id', 'errPasswd', 'apiCode');
        $blockChain->makePayment($bitcoinWallet, 'errXpub...', 'reveiceAddress', 0.00010000);
    }

    /**
     * 測試轉帳，blockChain返回空
     */
    public function testMakePaymentWithEmptyResult()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Parse data error',
            150180204
        );

        $response = new Response();
        $response->setContent('{}');
        $response->addHeader('HTTP/1.1 500 Internal Server Error');
        $response->addHeader('Content-Type: application/json; charset=UTF-8');

        $blockChain = new BlockChain();
        $blockChain->setContainer($this->container);
        $blockChain->setClient($this->client);
        $blockChain->setResponse($response);

        $bitcoinWallet = new BitcoinWallet(1, 'wallet_id', 'passwd', 'apiCode');
        $blockChain->makePayment($bitcoinWallet, 'errXpub...', 'reveiceAddress', 0.00010000);
    }

    /**
     * 測試轉帳，支付時連線失敗
     */
    public function testMakePaymentWithServerError()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Parse data error',
            150180204
        );

        $mockResponse = $this->getMockBuilder('Buzz\Message\Response')
            ->disableOriginalConstructor()
            ->setMethods(['getContent', 'getHeader'])
            ->getMock();

        $indexContent = '{"balance":0,"label":"rd5Test","index":109,"archived":false,"extendedPublicKey":"xpub..."}';
        $mockResponse->expects($this->at(0))
            ->method('getContent')
            ->willReturn($indexContent);

        $mockResponse->expects($this->at(1))
            ->method('getHeader')
            ->willReturn('Content-Type: application/json; charset=utf-8');

        $addressContent = '<html><head><title>502 Bad Gateway</title></head><body bgcolor="white">';
        $addressContent .= '<center><h1>502 Bad Gateway</h1></center><hr><center>nginx/1.12.1</center></body></html>';
        $mockResponse->expects($this->at(2))
            ->method('getContent')
            ->willReturn($addressContent);

        $mockResponse->expects($this->at(3))
            ->method('getHeader')
            ->willReturn('Content-Type: text/html; charset=UTF-8');

        $blockChain = new BlockChain();
        $blockChain->setContainer($this->container);
        $blockChain->setClient($this->client);
        $blockChain->setResponse($mockResponse);

        $bitcoinWallet = new BitcoinWallet(1, 'wallet_id', 'passwd', 'apiCode');
        $blockChain->makePayment($bitcoinWallet, 'xpub...', 'reveiceAddress', 0.00010000);
    }

    /**
     * 測試轉帳，blockChain有設定第二密碼卻未帶入
     */
    public function testMakePaymentWithoutSecondPassword()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Second password incorrect',
            150180202
        );

        $mockResponse = $this->getMockBuilder('Buzz\Message\Response')
            ->disableOriginalConstructor()
            ->setMethods(['getContent', 'getHeader'])
            ->getMock();

        $indexContent = '{"balance":0,"label":"rd5Test","index":109,"archived":false,"extendedPublicKey":"xpub..."}';
        $mockResponse->expects($this->at(0))
            ->method('getContent')
            ->willReturn($indexContent);

        $mockResponse->expects($this->at(1))
            ->method('getHeader')
            ->willReturn('Content-Type: application/json; charset=utf-8');

        $mockResponse->expects($this->at(2))
            ->method('getContent')
            ->willReturn('{"error":"Second password incorrect"}');

        $mockResponse->expects($this->at(3))
            ->method('getHeader')
            ->willReturn('Content-Type: application/json; charset=utf-8');

        $blockChain = new BlockChain();
        $blockChain->setContainer($this->container);
        $blockChain->setClient($this->client);
        $blockChain->setResponse($mockResponse);

        $bitcoinWallet = new BitcoinWallet(1, 'wallet_id', 'passwd', 'apiCode');
        $blockChain->makePayment($bitcoinWallet, 'xpub...', 'reveiceAddress', 0.00010000);
    }

    /**
     * 測試轉帳，出款時blockChain返回空
     */
    public function testMakePaymentPayWithEmptyResult()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Parse data error',
            150180204
        );

        $mockResponse = $this->getMockBuilder('Buzz\Message\Response')
            ->disableOriginalConstructor()
            ->setMethods(['getContent', 'getHeader'])
            ->getMock();

        $indexContent = '{"balance":0,"label":"rd5Test","index":109,"archived":false,"extendedPublicKey":"xpub..."}';
        $mockResponse->expects($this->at(0))
            ->method('getContent')
            ->willReturn($indexContent);

        $mockResponse->expects($this->at(1))
            ->method('getHeader')
            ->willReturn('Content-Type: application/json; charset=utf-8');

        $mockResponse->expects($this->at(2))
            ->method('getContent')
            ->willReturn('{}');

        $mockResponse->expects($this->at(3))
            ->method('getHeader')
            ->willReturn('Content-Type: application/json; charset=utf-8');

        $blockChain = new BlockChain();
        $blockChain->setContainer($this->container);
        $blockChain->setClient($this->client);
        $blockChain->setResponse($mockResponse);

        $bitcoinWallet = new BitcoinWallet(1, 'wallet_id', 'passwd', 'apiCode');
        $blockChain->makePayment($bitcoinWallet, 'xpub...', 'reveiceAddress', 0.00010000);
    }

    /**
     * 測試轉帳
     */
    public function testMakePayment()
    {
        $mockResponse = $this->getMockBuilder('Buzz\Message\Response')
            ->disableOriginalConstructor()
            ->setMethods(['getContent', 'getHeader'])
            ->getMock();

        $indexContent = '{"balance":0,"label":"rd5Test","index":109,"archived":false,"extendedPublicKey":"xpubtest"}';
        $mockResponse->expects($this->at(0))
            ->method('getContent')
            ->willReturn($indexContent);

        $mockResponse->expects($this->at(1))
            ->method('getHeader')
            ->willReturn('Content-Type: application/json; charset=utf-8');

        $warningMessage = 'It is recommended to specify a custom fee using the fee_per_byte parameter, ';
        $warningMessage .= 'transactions using the default 10000 satoshi fee may not confirm';
        $payContentArray = [
            'to' => ['reveiceAddress'],
            'amounts' => [10000],
            'from' => ['xpubtest'],
            'fee' => 10000,
            'txid' => 'f882475d8adbfa9324c3d20e6757b85baa2669418b6457e947d73d7fff5be434',
            'tx_hash' => 'f882475d8adbfa9324c3d20e6757b85baa2669418b6457e947d73d7fff5be434',
            'message' => 'Payment Sent',
            'success' => true,
            'warning' => $warningMessage,
        ];
        $mockResponse->expects($this->at(2))
            ->method('getContent')
            ->willReturn(json_encode($payContentArray));

        $mockResponse->expects($this->at(3))
            ->method('getHeader')
            ->willReturn('Content-Type: application/json; charset=utf-8');

        $blockChain = new BlockChain();
        $blockChain->setContainer($this->container);
        $blockChain->setClient($this->client);
        $blockChain->setResponse($mockResponse);

        $bitcoinWallet = new BitcoinWallet(1, 'wallet_id', 'passwd', 'apiCode');
        $bitcoinWallet->setSecondPassword('secondPassword');
        $txid = $blockChain->makePayment($bitcoinWallet, 'xpubtest', 'reveiceAddress', 0.00010000);
        $this->assertEquals('f882475d8adbfa9324c3d20e6757b85baa2669418b6457e947d73d7fff5be434', $txid);

        // 檢查log內密碼類參數是否遮罩
        $logResults = explode(PHP_EOL, file_get_contents($this->logPath));

        $indexUri = ' /merchant/wallet_id/accounts/******';
        $indexRequest = 'REQUEST: password=******&api_code=******';
        $indexResponse = '{"balance":0,"label":"rd5Test","index":109,"archived":false,"extendedPublicKey":"******"}';
        $this->assertContains($indexUri, $logResults[0]);
        $this->assertContains($indexRequest, $logResults[0]);
        $this->assertContains('RESPONSE: ' . $indexResponse, $logResults[0]);

        $paymentRequest = 'REQUEST: to=reveiceAddress&amount=10000&password=******&from=109&api_code=******';
        $paymentResponseArray = [
            'to' => ['reveiceAddress'],
            'amounts' => [10000],
            'from' => '******',
            'fee' => 10000,
            'txid' => 'f882475d8adbfa9324c3d20e6757b85baa2669418b6457e947d73d7fff5be434',
            'tx_hash' => 'f882475d8adbfa9324c3d20e6757b85baa2669418b6457e947d73d7fff5be434',
            'message' => 'Payment Sent',
            'success' => true,
            'warning' => $warningMessage,
        ];
        $this->assertContains($paymentRequest, $logResults[1]);
        $this->assertContains('RESPONSE: ' . json_encode($paymentResponseArray), $logResults[1]);
    }

    /**
     * 測試使用比特幣出款手續費率轉帳
     */
    public function testMakePaymentWithFeePerByte()
    {
        $mockResponse = $this->getMockBuilder('Buzz\Message\Response')
            ->disableOriginalConstructor()
            ->setMethods(['getContent', 'getHeader'])
            ->getMock();

        $indexContent = '{"balance":0,"label":"rd5Test","index":109,"archived":false,"extendedPublicKey":"xpubtest"}';
        $mockResponse->expects($this->at(0))
            ->method('getContent')
            ->willReturn($indexContent);

        $mockResponse->expects($this->at(1))
            ->method('getHeader')
            ->willReturn('Content-Type: application/json; charset=utf-8');

        $payContentArray = [
            'to' => ['reveiceAddress'],
            'amounts' => [45994],
            'from' => ['xpubtest'],
            'fee' => 40906,
            'txid' => '2cd559816f5be8f6867b0ba20ebe169a70c025d28298d61a3e04a3c13bba3fe3',
            'tx_hash' => '2cd559816f5be8f6867b0ba20ebe169a70c025d28298d61a3e04a3c13bba3fe3',
            'message' => 'Payment Sent',
            'success' => true,
        ];
        $mockResponse->expects($this->at(2))
            ->method('getContent')
            ->willReturn(json_encode($payContentArray));

        $mockResponse->expects($this->at(3))
            ->method('getHeader')
            ->willReturn('Content-Type: application/json; charset=utf-8');

        $blockChain = new BlockChain();
        $blockChain->setContainer($this->container);
        $blockChain->setClient($this->client);
        $blockChain->setResponse($mockResponse);

        $bitcoinWallet = new BitcoinWallet(1, 'wallet_id', 'passwd', 'apiCode');
        $bitcoinWallet->setSecondPassword('secondPassword');
        $bitcoinWallet->setFeePerByte(181);
        $txid = $blockChain->makePayment($bitcoinWallet, 'xpubtest', 'reveiceAddress', 0.00010000);
        $this->assertEquals('2cd559816f5be8f6867b0ba20ebe169a70c025d28298d61a3e04a3c13bba3fe3', $txid);

        // 檢查log內密碼類參數是否遮罩
        $logResults = explode(PHP_EOL, file_get_contents($this->logPath));

        $indexUri = ' /merchant/wallet_id/accounts/******';
        $indexRequest = 'REQUEST: password=******&api_code=******';
        $indexResponse = '{"balance":0,"label":"rd5Test","index":109,"archived":false,"extendedPublicKey":"******"}';
        $this->assertContains($indexUri, $logResults[0]);
        $this->assertContains($indexRequest, $logResults[0]);
        $this->assertContains('RESPONSE: ' . $indexResponse, $logResults[0]);

        $paymentRequest = 'REQUEST: to=reveiceAddress&amount=10000&password=******&from=109&api_code=******&';
        $paymentRequest .= 'second_password=******&fee_per_byte=181';
        $paymentResponseArray = [
            'to' => ['reveiceAddress'],
            'amounts' => [45994],
            'from' => '******',
            'fee' => 40906,
            'txid' => '2cd559816f5be8f6867b0ba20ebe169a70c025d28298d61a3e04a3c13bba3fe3',
            'tx_hash' => '2cd559816f5be8f6867b0ba20ebe169a70c025d28298d61a3e04a3c13bba3fe3',
            'message' => 'Payment Sent',
            'success' => true,
        ];
        $this->assertContains($paymentRequest, $logResults[1]);
        $this->assertContains('RESPONSE: ' . json_encode($paymentResponseArray), $logResults[1]);
    }

    public function tearDown()
    {
        if (file_exists($this->logPath)) {
            unlink($this->logPath);
        }

        parent::tearDown();
    }
}

<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\DinDin;
use Buzz\Message\Response;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;

class DinDinTest extends DurianTestCase
{
    /**
     * 此部分用於需要取得Container的時候
     *
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    private $container;

    /**
     * @var \Buzz\Client\Curl
     */
    private $client;

    /**
     * 此部分用於需要取得Container的時候
     */
    public function setUp()
    {
        parent::setUp();

        $this->container = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->setMethods(['get'])
            ->getMock();

        $mockLogger = $this->getMockBuilder('BB\DurianBundle\Logger\Payment')
            ->disableOriginalConstructor()
            ->setMethods(['record'])
            ->getMock();

        $mockLogger->expects($this->any())
            ->method('record')
            ->willReturn(null);

        $getMap = [
            ['durian.payment_logger', 1, $mockLogger]
        ];

        $this->container->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap($getMap));

        $this->client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();
    }

    /**
     * 測試出款沒有帶入privateKey
     */
    public function testWithdrawWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $dindin = new DinDin();
        $dindin->withdrawPayment();
    }

    /**
     * 測試出款未指定出款參數
     */
    public function testWithdrawNoWithdrawParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No withdraw parameter specified',
            150180196
        );

        $sourceData = ['account' => ''];

        $dindin = new DinDin();
        $dindin->setPrivateKey('jy9CV6uguTE=');

        $dindin->setOptions($sourceData);
        $dindin->withdrawPayment();
    }

    /**
     * 測試出款缺少商家附加設定值
     */
    public function testWithdrawWithoutMerchantExtra()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No merchant extra value specified',
            180143
        );

        $sourceData = [
            'account' => '6215590605000521773',
            'nameReal' => '吴坚',
            'bank_info_id' => '1',
            'amount' => '0.01',
            'orderId' => '112332',
            'withdraw_host' => 'payment.http.withdraw.com',
            'merchant_extra' => [],
        ];

        $dindin = new DinDin();
        $dindin->setPrivateKey('jy9CV6uguTE=');
        $dindin->setOptions($sourceData);
        $dindin->withdrawPayment();
    }

    /**
     * 測試出款但餘額查詢回傳錯誤
     */
    public function testWithdrawGetBalanceButError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '-1',
            180124
        );

        $sourceData = [
            'account' => '6215590605000521773',
            'nameReal' => '吴坚',
            'bank_info_id' => '1',
            'amount' => '100',
            'orderId' => '112332',
            'withdraw_host' => 'payment.http.withdraw.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'merchant_extra' => [
                'key2' => 'GzfSzLgmLiU=',
                'ID' => 'Mercury',
                'CardNum' => '9999173',
            ],
        ];

        $result = '<?xml version="1.0" encoding="utf-8"?><string xmlns="http://payout.sdapay.net/">-1</string>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/xml;charset=UTF-8');

        $dindin = new DinDin();
        $dindin->setContainer($this->container);
        $dindin->setClient($this->client);
        $dindin->setResponse($response);
        $dindin->setPrivateKey('jy9CV6uguTE=');
        $dindin->setOptions($sourceData);
        $dindin->withdrawPayment();
    }

    /**
     * 測試出款但餘額不足
     */
    public function testWithdrawButInsufficientBalance()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Insufficient balance',
            150180197
        );

        $sourceData = [
            'account' => '6215590605000521773',
            'nameReal' => '吴坚',
            'bank_info_id' => '1',
            'amount' => '100',
            'orderId' => '112332',
            'withdraw_host' => 'payment.http.withdraw.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'merchant_extra' => [
                'key2' => 'GzfSzLgmLiU=',
                'ID' => 'Mercury',
                'CardNum' => '9999173',
            ],
        ];

        $result = '<?xml version="1.0" encoding="utf-8"?><string xmlns="http://payout.sdapay.net/">5.0200</string>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/xml;charset=UTF-8');

        $dindin = new DinDin();
        $dindin->setContainer($this->container);
        $dindin->setClient($this->client);
        $dindin->setResponse($response);
        $dindin->setPrivateKey('jy9CV6uguTE=');
        $dindin->setOptions($sourceData);
        $dindin->withdrawPayment();
    }

    /**
     * 測試出款返回異常
     */
    public function testWithdrawFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '-15',
            180124
        );

        $sourceData = [
            'account' => '6215590605000521773',
            'nameReal' => '吴坚',
            'bank_info_id' => '1',
            'amount' => '0.01',
            'orderId' => '112332',
            'withdraw_host' => 'payment.http.withdraw.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'merchant_extra' => [
                'key2' => 'GzfSzLgmLiU=',
                'ID' => 'Mercury',
                'CardNum' => '9999173',
            ],
        ];

        $balanceResult = '<?xml version="1.0" encoding="utf-8"?>' .
            '<string xmlns="http://payout.sdapay.net/">5.0200</string>';
        $result = '<?xml version="1.0" encoding="utf-8"?><int xmlns="http://payout.sdapay.net/">-15</int>';

        $response = $this->getMockBuilder('Buzz\Message\Response')
            ->disableOriginalConstructor()
            ->setMethods(['getContent', 'getStatusCode'])
            ->getMock();
        $response->expects($this->any())
            ->method('getStatusCode')
            ->willReturn(200);
        $response->expects($this->at(0))
            ->method('getContent')
            ->willReturn($balanceResult);
        $response->expects($this->at(2))
            ->method('getContent')
            ->willReturn($result);

        $dindin = new DinDin();
        $dindin->setContainer($this->container);
        $dindin->setClient($this->client);
        $dindin->setResponse($response);
        $dindin->setPrivateKey('jy9CV6uguTE=');
        $dindin->setOptions($sourceData);
        $dindin->withdrawPayment();
    }

    /**
     * 測試出款返回成功
     */
    public function testWithdrawSuccess()
    {
        $sourceData = [
            'account' => '6215590605000521773',
            'nameReal' => '吴坚',
            'bank_info_id' => '1',
            'amount' => '0.01',
            'orderId' => '112332',
            'withdraw_host' => 'payment.http.withdraw.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'merchant_extra' => [
                'key2' => 'GzfSzLgmLiU=',
                'ID' => 'Mercury',
                'CardNum' => '9999173',
            ],
        ];

        $balanceResult = '<?xml version="1.0" encoding="utf-8"?>' .
            '<string xmlns="http://payout.sdapay.net/">5.0200</string>';
        $result = '<?xml version="1.0" encoding="utf-8"?><int xmlns="http://payout.sdapay.net/">15927668</int>';

        $response = $this->getMockBuilder('Buzz\Message\Response')
            ->disableOriginalConstructor()
            ->setMethods(['getContent', 'getStatusCode'])
            ->getMock();
        $response->expects($this->any())
            ->method('getStatusCode')
            ->willReturn(200);
        $response->expects($this->at(0))
            ->method('getContent')
            ->willReturn($balanceResult);
        $response->expects($this->at(2))
            ->method('getContent')
            ->willReturn($result);

        $mockCwe = $this->getMockBuilder('BB\DurianBundle\Entity\CashWithdrawEntry')
            ->disableOriginalConstructor()
            ->setMethods(['setRefId'])
            ->getMock();
        $mockCwe->expects($this->any())
            ->method('setRefId')
            ->willReturn($mockCwe);

        $mockRepo = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['findOneBy'])
            ->getMock();
        $mockRepo->expects($this->any())
            ->method('findOneBy')
            ->willReturn($mockCwe);

        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['getRepository', 'flush'])
            ->getMock();
        $mockEm->expects($this->at(0))
            ->method('getRepository')
            ->willReturn($mockRepo);

        $mockDoctrine = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->setMethods(['getManager'])
            ->getMock();
        $mockDoctrine->expects($this->any())
            ->method('getManager')
            ->willReturn($mockEm);

        $mockContainer = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->setMethods(['get'])
            ->getMock();

        $mockLogger = $this->getMockBuilder('BB\DurianBundle\Logger\Payment')
            ->disableOriginalConstructor()
            ->setMethods(['record'])
            ->getMock();

        $getMap = [
            ['durian.payment_logger', 1, $mockLogger],
            ['doctrine', 1, $mockDoctrine],
        ];

        $mockContainer->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap($getMap));

        $dindin = new DinDin();
        $dindin->setContainer($mockContainer);
        $dindin->setClient($this->client);
        $dindin->setResponse($response);
        $dindin->setPrivateKey('jy9CV6uguTE=');
        $dindin->setOptions($sourceData);
        $dindin->withdrawPayment();
    }

    /**
     * 測試出款查詢沒有帶入privateKey
     */
    public function testWithdrawTrackingWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $dindin = new DinDin();
        $dindin->withdrawTracking();
    }

    /**
     * 測試出款查詢未指定出款查詢參數
     */
    public function testWithdrawTrackingNoWithdrawTrackingParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No withdraw tracking parameter specified',
            150180199
        );

        $sourceData = ['ref_id' => ''];

        $dindin = new DinDin();
        $dindin->setPrivateKey('jy9CV6uguTE=');
        $dindin->setOptions($sourceData);
        $dindin->withdrawTracking();
    }

    /**
     * 測試出款查詢缺少商家附加設定值
     */
    public function testWithdrawTrackingWithoutMerchantExtra()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No merchant extra value specified',
            180143
        );

        $sourceData = [
            'ref_id' => '15929911',
            'merchant_extra' => [],
        ];

        $dindin = new DinDin();
        $dindin->setPrivateKey('jy9CV6uguTE=');
        $dindin->setOptions($sourceData);
        $dindin->withdrawTracking();
    }

    /**
     * 測試出款查詢但查詢失敗
     */
    public function testWithdrawTrackingButWithdrawTrackingFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Withdraw tracking failed',
            150180198
        );

        $sourceData = [
            'ref_id' => '15929911',
            'merchant_extra' => [
                'key2' => 'GzfSzLgmLiU=',
                'ID' => 'Mercury',
            ],
            'withdraw_host' => 'payment.http.withdraw.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $data = [
            'Id' => '0',
            'RolloutAccount' => '',
            'IntoAccount' => '',
            'IntoName' => '',
            'IntoAmount' => '0',
            'RecordsState' => '0',
            'Tip' => '',
            'ElectronicOdd' => '',
            'SerialNumber' => '',
            'PageCode' => '',
            'BusinessmanId' => '0',
            'Ip' => '',
            'SendORNOT' => '0',
            'SendTime' => '0',
            'CardNumberGroup' => '0',
            'beforeMoney' => '0',
            'afterMoney' => '0',
            'BankCardAlias' => '',
            'AccountSerialNumber' => '0',
            'HandlingFee' => '0',
            'GroupId' => '0',
            'TransferredBank' => '',
            'IntoProvince' => '',
            'IntoCity' => '',
            'BusinessmanName' => '',
            'BankCode' => '',
            'ElectronicOddFileUrl' => '',
            'PageCodeUrl' => '',
            'Notes' => '',
            'Batch' => '',
        ];

        $privateKey = 'jy9CV6uguTE=';

        $xml = $this->arrayToXml($data, [], 'TransferInformation');
        $md5Hash = md5(time());
        $key = base64_decode($privateKey);
        $iv = base64_decode($sourceData['merchant_extra']['key2']);
        $encodeStr = openssl_encrypt($xml, 'des-cbc', $key, 0, $iv);
        $encodeStr .= $md5Hash;

        $result = '<string xmlns="http://payout.sdapay.net/">' . $encodeStr . '</string>';

        $response = new Response();
        $response->setContent(($result));
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/xml;charset=UTF-8');

        $dindin = new DinDin();
        $dindin->setPrivateKey($privateKey);
        $dindin->setOptions($sourceData);
        $dindin->setContainer($this->container);
        $dindin->setClient($this->client);
        $dindin->setResponse($response);
        $dindin->withdrawTracking();
    }

    /**
     * 測試出款查詢但返回參數未指定
     */
    public function testWithdrawTrackingNoWithdrawTrackingReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No withdraw tracking return parameter specified',
            150180200
        );

        $sourceData = [
            'ref_id' => '15929911',
            'merchant_extra' => [
                'key2' => 'GzfSzLgmLiU=',
                'ID' => 'Mercury',
            ],
            'withdraw_host' => 'payment.http.withdraw.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $data = ['Id' => '15928118'];

        $privateKey = 'jy9CV6uguTE=';

        $xml = $this->arrayToXml($data, [], 'TransferInformation');
        $md5Hash = md5(time());
        $key = base64_decode($privateKey);
        $iv = base64_decode($sourceData['merchant_extra']['key2']);
        $encodeStr = openssl_encrypt($xml, 'des-cbc', $key, 0, $iv);
        $encodeStr .= $md5Hash;

        $result = '<string xmlns="http://payout.sdapay.net/">' . $encodeStr . '</string>';
        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/xml;charset=UTF-8');

        $dindin = new DinDin();
        $dindin->setContainer($this->container);
        $dindin->setClient($this->client);
        $dindin->setResponse($response);
        $dindin->setPrivateKey('jy9CV6uguTE=');
        $dindin->setOptions($sourceData);
        $dindin->withdrawTracking();
    }

    /**
     * 測試出款查詢但查詢錯誤
     */
    public function testWithdrawTrackingButWithdrawTrackingError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            '账号错误',
            150180201
        );

        $sourceData = [
            'ref_id' => '15929911',
            'merchant_extra' => [
                'key2' => 'GzfSzLgmLiU=',
                'ID' => 'Mercury',
            ],
            'withdraw_host' => 'payment.http.withdraw.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $data = [
            'Id' => '15928118',
            'RolloutAccount' => '9999173',
            'IntoAccount' => '1',
            'IntoName' => '兔',
            'IntoBank1' => '中国工商银行',
            'IntoBank2' => '',
            'TransferNote' => 'VCMBC17300000010',
            'IntoAmount' => '0.0100',
            'RecordsState' => '3',
            'Tip' => '账号错误',
            'ApplicationTime' => '2017/07/28 11:32:06',
            'ProcessingTime' => '2017/07/28 11:32:26',
            'Completetime' => '2017/07/28 11:33:27',
            'ElectronicOdd' => '',
            'SerialNumber' => '112333',
            'PageCode' => '',
            'BusinessmanId' => '205',
            'Ip' => '111.235.135.54',
            'SendORNOT' => '0',
            'SendTime' => '0',
            'CardNumberGroup' => '0',
            'beforeMoney' => '4.950',
            'afterMoney' => '4.940',
            'banknumber' => '19290282_20170728113226382',
            'bankcardalias' => 'VCMBC173',
            'AccountSerialNumber' => '10',
            'HandlingFee' => '0.0000',
            'GroupId' => '0',
            'TransferredBank' => 'ALL',
            'IntoProvince' => '',
            'IntoCity' => '',
            'BusinessmanName' => 'W88803173',
            'BankCode' => 'ICBC',
            'ElectronicOddFileUrl' => '',
            'PageCodeUrl' => '',
            'Notes' => '4011',
            'Batch' => '',
        ];

        $privateKey = 'jy9CV6uguTE=';

        $xml = $this->arrayToXml($data, [], 'TransferInformation');
        $md5Hash = md5(time());
        $key = base64_decode($privateKey);
        $iv = base64_decode($sourceData['merchant_extra']['key2']);
        $encodeStr = openssl_encrypt($xml, 'des-cbc', $key, 0, $iv);
        $encodeStr .= $md5Hash;

        $result = '<string xmlns="http://payout.sdapay.net/">' . $encodeStr . '</string>';
        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/xml;charset=UTF-8');

        $dindin = new DinDin();
        $dindin->setContainer($this->container);
        $dindin->setClient($this->client);
        $dindin->setResponse($response);
        $dindin->setPrivateKey('jy9CV6uguTE=');
        $dindin->setOptions($sourceData);
        $dindin->withdrawTracking();
    }

    /**
     * 測試出款查詢但訂單錯誤
     */
    public function testWithdrawTrackingButOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $sourceData = [
            'ref_id' => '15929911',
            'orderId' => '201608040000004412',
            'auto_withdraw_amount' => '100',
            'merchant_extra' => [
                'key2' => 'GzfSzLgmLiU=',
                'ID' => 'Mercury',
            ],
            'withdraw_host' => 'payment.http.withdraw.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $data = [
            'Id' => '15929911',
            'RolloutAccount' => '6226228000199028',
            'IntoAccount' => '6215590605000521773',
            'IntoName' => '吴坚',
            'IntoBank1' => '中国工商银行',
            'TransferNote' => 'CMBC902800000002',
            'IntoAmount' => '0.0100',
            'RecordsState' => '2',
            'Tip' => 'success',
            'ApplicationTime' => '2017/07/28 17:36:31',
            'ProcessingTime' => '2017/07/28 17:36:35',
            'CompleteTime' => '2017/07/28 17:39:01',
            'PushTime' => '2017/07/28 17:39:08',
            'ElectronicOdd' => 'ElectronicOdd',
            'SerialNumber' => '112339',
            'PageCode' => 'PageCode',
            'BusinessmanId' => '205',
            'Ip' => '111.235.135.54',
            'SendORNOT' => '2',
            'SendTime' => '1',
            'CardNumberGroup' => '0',
            'beforeMoney' => '2.000',
            'afterMoney' => '1.990',
            'bankNumber' => '31314201707286725475453313000000',
            'BankCardAlias' => 'CMBC9028',
            'AccountSerialNumber' => '2',
            'HandlingFee' => '0.0000',
            'GroupId' => '0',
            'TransferredBank' => 'CMBC',
            'IntoProvince' => '',
            'IntoCity' => '',
            'BusinessmanName' => '',
            'BankCode' => 'BankCode',
            'ElectronicOddFileUrl' => '',
            'PageCodeUrl' => '',
            'Notes' => 'Notes',
            'Batch' => '',
        ];

        $privateKey = 'jy9CV6uguTE=';

        $xml = $this->arrayToXml($data, [], 'TransferInformation');
        $md5Hash = md5(time());
        $key = base64_decode($privateKey);
        $iv = base64_decode($sourceData['merchant_extra']['key2']);
        $encodeStr = openssl_encrypt($xml, 'des-cbc', $key, 0, $iv);
        $encodeStr .= $md5Hash;

        $result = '<string xmlns="http://payout.sdapay.net/">' . $encodeStr . '</string>';
        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/xml;charset=UTF-8');

        $dindin = new DinDin();
        $dindin->setContainer($this->container);
        $dindin->setClient($this->client);
        $dindin->setResponse($response);
        $dindin->setPrivateKey('jy9CV6uguTE=');
        $dindin->setOptions($sourceData);
        $dindin->withdrawTracking();
    }

    /**
     * 測試出款查詢但金額錯誤
     */
    public function testWithdrawTrackingButOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $sourceData = [
            'ref_id' => '15929911',
            'orderId' => '112339',
            'auto_withdraw_amount' => '100',
            'merchant_extra' => [
                'key2' => 'GzfSzLgmLiU=',
                'ID' => 'Mercury',
            ],
            'withdraw_host' => 'payment.http.withdraw.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $data = [
            'Id' => '15929911',
            'RolloutAccount' => '6226228000199028',
            'IntoAccount' => '6215590605000521773',
            'IntoName' => '吴坚',
            'IntoBank1' => '中国工商银行',
            'TransferNote' => 'CMBC902800000002',
            'IntoAmount' => '0.0100',
            'RecordsState' => '2',
            'Tip' => 'success',
            'ApplicationTime' => '2017/07/28 17:36:31',
            'ProcessingTime' => '2017/07/28 17:36:35',
            'CompleteTime' => '2017/07/28 17:39:01',
            'PushTime' => '2017/07/28 17:39:08',
            'ElectronicOdd' => 'ElectronicOdd',
            'SerialNumber' => '112339',
            'PageCode' => 'PageCode',
            'BusinessmanId' => '205',
            'Ip' => '111.235.135.54',
            'SendORNOT' => '2',
            'SendTime' => '1',
            'CardNumberGroup' => '0',
            'beforeMoney' => '2.000',
            'afterMoney' => '1.990',
            'bankNumber' => '31314201707286725475453313000000',
            'BankCardAlias' => 'CMBC9028',
            'AccountSerialNumber' => '2',
            'HandlingFee' => '0.0000',
            'GroupId' => '0',
            'TransferredBank' => 'CMBC',
            'IntoProvince' => '',
            'IntoCity' => '',
            'BusinessmanName' => '',
            'BankCode' => 'BankCode',
            'ElectronicOddFileUrl' => '',
            'PageCodeUrl' => '',
            'Notes' => 'Notes',
            'Batch' => '',
        ];

        $privateKey = 'jy9CV6uguTE=';

        $xml = $this->arrayToXml($data, [], 'TransferInformation');
        $md5Hash = md5(time());
        $key = base64_decode($privateKey);
        $iv = base64_decode($sourceData['merchant_extra']['key2']);
        $encodeStr = openssl_encrypt($xml, 'des-cbc', $key, 0, $iv);
        $encodeStr .= $md5Hash;

        $result = '<string xmlns="http://payout.sdapay.net/">' . $encodeStr . '</string>';
        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/xml;charset=UTF-8');

        $dindin = new DinDin();
        $dindin->setContainer($this->container);
        $dindin->setClient($this->client);
        $dindin->setResponse($response);
        $dindin->setPrivateKey('jy9CV6uguTE=');
        $dindin->setOptions($sourceData);
        $dindin->withdrawTracking();
    }

    /**
     * 測試出款查詢成功
     */
    public function testWithdrawTrackingSuccess()
    {
        $sourceData = [
            'ref_id' => '15929911',
            'orderId' => '112339',
            'auto_withdraw_amount' => '0.01',
            'merchant_extra' => [
                'key2' => 'GzfSzLgmLiU=',
                'ID' => 'Mercury',
            ],
            'withdraw_host' => 'payment.http.withdraw.com',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $data = [
            'Id' => '15929911',
            'RolloutAccount' => '6226228000199028',
            'IntoAccount' => '6215590605000521773',
            'IntoName' => '吴坚',
            'IntoBank1' => '中国工商银行',
            'TransferNote' => 'CMBC902800000002',
            'IntoAmount' => '0.0100',
            'RecordsState' => '2',
            'Tip' => 'success',
            'ApplicationTime' => '2017/07/28 17:36:31',
            'ProcessingTime' => '2017/07/28 17:36:35',
            'CompleteTime' => '2017/07/28 17:39:01',
            'PushTime' => '2017/07/28 17:39:08',
            'ElectronicOdd' => 'ElectronicOdd',
            'SerialNumber' => '112339',
            'PageCode' => 'PageCode',
            'BusinessmanId' => '205',
            'Ip' => '111.235.135.54',
            'SendORNOT' => '2',
            'SendTime' => '1',
            'CardNumberGroup' => '0',
            'beforeMoney' => '2.000',
            'afterMoney' => '1.990',
            'bankNumber' => '31314201707286725475453313000000',
            'BankCardAlias' => 'CMBC9028',
            'AccountSerialNumber' => '2',
            'HandlingFee' => '0.0000',
            'GroupId' => '0',
            'TransferredBank' => 'CMBC',
            'IntoProvince' => '',
            'IntoCity' => '',
            'BusinessmanName' => '',
            'BankCode' => 'BankCode',
            'ElectronicOddFileUrl' => '',
            'PageCodeUrl' => '',
            'Notes' => 'Notes',
            'Batch' => '',
        ];

        $privateKey = 'jy9CV6uguTE=';

        $xml = $this->arrayToXml($data, [], 'TransferInformation');
        $md5Hash = md5(time());
        $key = base64_decode($privateKey);
        $iv = base64_decode($sourceData['merchant_extra']['key2']);
        $encodeStr = openssl_encrypt($xml, 'des-cbc', $key, 0, $iv);
        $encodeStr .= $md5Hash;

        $result = '<string xmlns="http://payout.sdapay.net/">' . $encodeStr . '</string>';
        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:text/xml;charset=UTF-8');

        $dindin = new DinDin();
        $dindin->setContainer($this->container);
        $dindin->setClient($this->client);
        $dindin->setResponse($response);
        $dindin->setPrivateKey('jy9CV6uguTE=');
        $dindin->setOptions($sourceData);
        $dindin->withdrawTracking();
    }

    /**
     * 將array格式轉成xml
     *
     * @param array $data
     * @param array $context
     * @param string $rootNodeName
     * @return string
     */
    protected function arrayToXml($data, $context, $rootNodeName = 'response')
    {
        $encoders = [new XmlEncoder($rootNodeName)];
        $normalizers = [new GetSetMethodNormalizer()];
        $serializer = new Serializer($normalizers, $encoders);

        $xml = $serializer->encode($data, 'xml', $context);

        return str_replace("\n", '', $xml);
    }
}

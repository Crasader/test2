<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\Zhifu;
use Buzz\Message\Response;

class ZhifuTest extends DurianTestCase
{
    /**
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    private $container;

    /**
     * @var \Buzz\Client\Curl
     */
    private $client;

    public function setUp()
    {
        parent::setUp();

        $mockLogger = $this->getMockBuilder('BB\DurianBundle\Logger\Payment')
            ->disableOriginalConstructor()
            ->setMethods(['record'])
            ->getMock();

        $mockLogger->expects($this->any())
            ->method('record')
            ->willReturn(null);

        $this->container = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->setMethods(['get'])
            ->getMock();

        $this->container->expects($this->any())
            ->method('get')
            ->willReturn($mockLogger);

        $this->client = $this->getMockBuilder('Buzz\Client\Curl')->getMock();
    }

    /**
     * 測試支付時缺少私鑰
     */
    public function testPayWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $zhifu = new Zhifu();
        $zhifu->getVerifyData();
    }

    /**
     * 測試支付時未指定支付參數
     */
    public function testPayWithNoPayParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $zhifu = new Zhifu();
        $zhifu->setPrivateKey('test');
        $zhifu->getVerifyData();
    }

    /**
     * 測試支付設定帶入不支援的銀行
     */
    public function testPaySourceNotSupportBank()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $options = [
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'orderCreateDate' => '20150316',
            'amount' => '100',
            'notify_url' => 'http://154.58.78.54/',
            'paymentVendorId' => '99',
            'username' => 'php1test',
        ];

        $zhifu = new Zhifu();
        $zhifu->setPrivateKey('test');
        $zhifu->setOptions($options);
        $zhifu->getVerifyData();
    }

    /**
     * 測試支付時沒有帶入verify_url的情況
     */
    public function testPayWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $options = [
            'number' => 'acctest',
            'orderId' => '201503220000000123',
            'orderCreateDate' => '2015-03-22 21:25:29',
            'amount' => '100',
            'notify_url' => 'http://pay.xxx.xxx/app/return.php',
            'paymentVendorId' => '1',
            'username' => 'php1test',
            'verify_url' => '',
        ];

        $zhifu = new Zhifu();
        $zhifu->setPrivateKey('test');
        $zhifu->setOptions($options);
        $zhifu->getVerifyData();
    }

    /**
     * 測試支付銀行為網銀
     */
    public function testPayWithBank()
    {
        $options = [
            'number' => 'acctest',
            'orderId' => '201503220000000123',
            'orderCreateDate' => '2015-03-22 21:25:29',
            'amount' => '100',
            'notify_url' => 'http://pay.xxx.xxx/app/return.php',
            'paymentVendorId' => '1',
            'username' => 'php1test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.www.56zhifu.com',
        ];

        $zhifu = new Zhifu();
        $zhifu->setPrivateKey('test');
        $zhifu->setOptions($options);
        $requestData = $zhifu->getVerifyData();

        $this->assertEquals('acctest', $requestData['merchantId']);
        $this->assertEquals('CP00000015', $requestData['prodCode']);
        $this->assertEquals('201503220000000123', $requestData['orderId']);
        $this->assertEquals('10000', $requestData['orderAmount']);
        $this->assertEquals('20150322212529', $requestData['orderDate']);
        $this->assertEquals('0', $requestData['prdOrdType']);
        $this->assertEquals('http://pay.xxx.xxx/app/return.php', $requestData['retUrl']);
        $this->assertEquals('http://pay.xxx.xxx/app/return.php', $requestData['returnUrl']);
        $this->assertEquals('7068703174657374', $requestData['prdName']);
        $this->assertEmpty($requestData['prdDesc']);
        $this->assertEquals('MD5', $requestData['signType']);
        $this->assertEquals('ff3d54f4ee90735e809681f0e3b10aed', $requestData['signature']);
    }

    /**
     * 測試支付銀行為二維時未返回retCode
     */
    public function testPayWithQRCodeNoReturnRetCode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $zhifu = new Zhifu();
        $zhifu->setContainer($this->container);
        $zhifu->setClient($this->client);

        $result = '<?xml version="1.0" encoding="UTF-8"?><settle retMsg="无此验签方式！"></settle>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $zhifu->setResponse($response);

        $options = [
            'number' => 'acctest',
            'orderId' => '201503220000000123',
            'orderCreateDate' => '2015-03-22 21:25:29',
            'amount' => '100',
            'notify_url' => 'http://pay.xxx.xxx/app/return.php',
            'paymentVendorId' => '1090',
            'username' => 'php1test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.www.56zhifu.com',
        ];

        $zhifu->setPrivateKey('test');
        $zhifu->setOptions($options);
        $zhifu->getVerifyData();
    }

    /**
     * 測試支付銀行為二維時對外返回結果錯誤
     */
    public function testPayWithQRCodeReturnError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '无此验签方式！',
            180130
        );

        $zhifu = new Zhifu();
        $zhifu->setContainer($this->container);
        $zhifu->setClient($this->client);

        $result = '<?xml version="1.0" encoding="UTF-8"?><settle retCode="0002" retMsg="无此验签方式！"></settle>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $zhifu->setResponse($response);

        $options = [
            'number' => 'acctest',
            'orderId' => '201503220000000123',
            'orderCreateDate' => '2015-03-22 21:25:29',
            'amount' => '100',
            'notify_url' => 'http://pay.xxx.xxx/app/return.php',
            'paymentVendorId' => '1090',
            'username' => 'php1test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.www.56zhifu.com',
        ];

        $zhifu->setPrivateKey('test');
        $zhifu->setOptions($options);
        $zhifu->getVerifyData();
    }

    /**
     * 測試支付銀行為二維時未返回qrcode
     */
    public function testPayWithQRCodeNoReturnQrcode()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $zhifu = new Zhifu();
        $zhifu->setContainer($this->container);
        $zhifu->setClient($this->client);

        $result = '<?xml version="1.0" encoding="UTF-8"?><settle retCode="0001" retMsg="交易成功"></settle>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $zhifu->setResponse($response);

        $options = [
            'number' => 'acctest',
            'orderId' => '201503220000000123',
            'orderCreateDate' => '2015-03-22 21:25:29',
            'amount' => '100',
            'notify_url' => 'http://pay.xxx.xxx/app/return.php',
            'paymentVendorId' => '1090',
            'username' => 'php1test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.www.56zhifu.com',
        ];

        $zhifu->setPrivateKey('test');
        $zhifu->setOptions($options);
        $zhifu->getVerifyData();
    }

    /**
     * 測試支付銀行為二維
     */
    public function testPayWithQRCode()
    {
        $zhifu = new Zhifu();
        $zhifu->setContainer($this->container);
        $zhifu->setClient($this->client);

        $result = '<?xml version="1.0" encoding="UTF-8"?><settle retCode="0001" retMsg="交易成功">' .
            '<qrURL>http://59.41.60.158:8099/mobile/payment/images/P3sTH4bOK5Gc.png</qrURL></settle>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $zhifu->setResponse($response);

        $options = [
            'number' => 'acctest',
            'orderId' => '201503220000000123',
            'orderCreateDate' => '2015-03-22 21:25:29',
            'amount' => '100',
            'notify_url' => 'http://pay.xxx.xxx/app/return.php',
            'paymentVendorId' => '1090',
            'username' => 'php1test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.www.56zhifu.com',
        ];

        $zhifu->setPrivateKey('test');
        $zhifu->setOptions($options);
        $requestData = $zhifu->getVerifyData();

        $this->assertEquals('http://59.41.60.158:8099/mobile/payment/images/P3sTH4bOK5Gc.png', $requestData['act_url']);
    }

    /**
     * 測試支付銀行為手機支付時未返回RSPCOD
     */
    public function testPayWithWapNoReturnRSPCOD()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $zhifu = new Zhifu();
        $zhifu->setContainer($this->container);
        $zhifu->setClient($this->client);

        $result = '{"MERID":"00000000000645","PRDNAME":"7068703174657374","ACTDAT":"20161014",' .
            '"SIGNATURE":"225533730a548f170c5121f9e65a3d35","TELLERID":"00000000000000000000",' .
            '"TRANDESC":"商户接入--商品订单建立--WAP网站商户直连","RSPMSG":"商户未签约该产品！","CONFIGNAME":"BeforeCfg",' .
            '"COMPANY_BRIEF":"商物通测试商户","ORDERDATE":"","PRDORDTYPE":"0",' .
            '"NOTIFYURL":"http://two123.comxa.com/pay/return.php","SENDID":"12","CUST_STATUS":"0",' .
            '"SENDURL":"http://59.41.60.158:8914/TradeAppServer/user/sms.jf","PRDORDNO":"201610140000006660",' .
            '"TXN_ORG_NO":"000001","TERMCODE":"111.235.135.54","AUT_STS":"1","IP":"127.0.0.1","ORDAMT":"100",' .
            '"PRODCODE":"CP00000003","PRDDESC":"","NOWDATE":"20161014","NOWTIME":"113546","TRANCODE":"820001",' .
            '"ORDERTIME":"20161014113542","PAYTYPE":"01","MERCHANTID":"00000000000645","G_NUMPERPAG":"5",' .
            '"LOSEDATE":"5","RETURL":"http://two123.comxa.com/pay/return.php","SIGNTYPE":"MD5"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $zhifu->setResponse($response);

        $options = [
            'number' => 'acctest',
            'orderId' => '201503220000000123',
            'orderCreateDate' => '2015-03-22 21:25:29',
            'amount' => '100',
            'notify_url' => 'http://pay.xxx.xxx/app/return.php',
            'paymentVendorId' => '1088',
            'username' => 'php1test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.www.56zhifu.com',
        ];

        $zhifu->setPrivateKey('test');
        $zhifu->setOptions($options);
        $zhifu->getVerifyData();
    }

    /**
     * 測試支付銀行為手機支付時對外返回結果錯誤
     */
    public function testPayWithWapReturnError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '商户未签约该产品！',
            180130
        );

        $zhifu = new Zhifu();
        $zhifu->setContainer($this->container);
        $zhifu->setClient($this->client);

        $result = '{"MERID":"00000000000645","PRDNAME":"7068703174657374","ACTDAT":"20161014",' .
            '"SIGNATURE":"225533730a548f170c5121f9e65a3d35","TELLERID":"00000000000000000000",' .
            '"TRANDESC":"商户接入--商品订单建立--WAP网站商户直连","RSPMSG":"商户未签约该产品！","CONFIGNAME":"BeforeCfg",' .
            '"COMPANY_BRIEF":"商物通测试商户","ORDERDATE":"","PRDORDTYPE":"0",' .
            '"NOTIFYURL":"http://two123.comxa.com/pay/return.php","SENDID":"12","CUST_STATUS":"0",' .
            '"SENDURL":"http://59.41.60.158:8914/TradeAppServer/user/sms.jf","PRDORDNO":"201610140000006660",' .
            '"TXN_ORG_NO":"000001","TERMCODE":"111.235.135.54","AUT_STS":"1","IP":"127.0.0.1","ORDAMT":"100",' .
            '"PRODCODE":"CP00000003","PRDDESC":"","NOWDATE":"20161014","NOWTIME":"113546","TRANCODE":"820001",' .
            '"ORDERTIME":"20161014113542","PAYTYPE":"01","MERCHANTID":"00000000000645","G_NUMPERPAG":"5",' .
            '"LOSEDATE":"5","RETURL":"http://two123.comxa.com/pay/return.php","RSPCOD":"08039","SIGNTYPE":"MD5"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $zhifu->setResponse($response);

        $options = [
            'number' => 'acctest',
            'orderId' => '201503220000000123',
            'orderCreateDate' => '2015-03-22 21:25:29',
            'amount' => '100',
            'notify_url' => 'http://pay.xxx.xxx/app/return.php',
            'paymentVendorId' => '1088',
            'username' => 'php1test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.www.56zhifu.com',
        ];

        $zhifu->setPrivateKey('test');
        $zhifu->setOptions($options);
        $zhifu->getVerifyData();
    }

    /**
     * 測試支付銀行為手機支付時未返回UPOPWAPPAYURL
     */
    public function testPayWithWapNoReturnUPOPWAPPAYURL()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Get pay parameters failed',
            180128
        );

        $zhifu = new Zhifu();
        $zhifu->setContainer($this->container);
        $zhifu->setClient($this->client);

        $result = '{"MERID":"777290058132938","KEYVAL":"00000031","CHANNELTYPE":"08",' .
            '"ACCESSURL":"http://59.41.60.154:8090/user/html/upopwaphtml/",' .
            '"INMSG":"prdOrdNo=201610140000006662&merchantId=00000000000698&prodCode=CP00000003' .
            '&prdOrdType=0&ordAmt=100&orderTime=20161014113912&signType=MD5","BANKCOD":"01010000",' .
            '"TXNTIME":"20161014113902","ACCESSTYPE":"0","ENCODING":"UTF-8","VERSION":"5.0.0","TXAMT":"100",' .
            '"TELLERID":"00000000000000000000","RSPMSG":"交易成功",' .
            '"PAGEURL":"/home/payment/apache-tomcat-7.0.64/webapps/user/html/upopwaphtml/",' .
            '"SIGNMETHOD":"01","ORDSTATUS":"00","CONFIGNAME":"phonePayConfig","ORDERDATE":"",' .
            '"OUTSTR":"f60f3e8e24a2d5519f79c82363561439","MERNO":"00000000000698","TXN_ORG_NO":"000001",' .
            '"PRDORDNO":"201610140000006662","BANKCODE":"01010000","IP":"192.168.0.92","PRODCODE":"CP00000003",' .
            '"ORDAMT":"100","NOWTIME":"113902","PRDDESC":"","TRANCODE":"820001","PAYTYPE":"01","G_NUMPERPAG":"5",' .
            '"RETURL":"http://two123.comxa.com/pay/return.php","RSPCOD":"00000","ISPROXY":"0","TXNAMT":"100",' .
            '"TXCCY":"CNY","BIZTYPE":"000201","TXNCOMAMT":"0","TODAY":"20161014","PRDNAME":"7068703174657374",' .
            '"INSTR":"prdOrdNo=201610140000006662&merchantId=00000000000698&prodCode=CP00000003&prdOrdType=0' .
            '&ordAmt=100&orderTime=20161014113912&signType=MD5NSOTI80P273IVE69LSR203PK",' .
            '"KEY":"NSOTI80P273IVE69LSR203PK","ACTDAT":"20161014113902","ORDCCY":"CNY","ORDSOURCE":"0",' .
            '"CERTID":"40220995861346480087409489142384722381","TXNTYPE":"01","ORDERID":"P016101400000031",' .
            '"BACKURL":"http://59.41.60.154:8090/user/565102.tran","CUST_ID":"",' .
            '"SIGNATURE":"WsFyuBRbiNYlE92bt0/UbN/uKqWeZ  b4ps3HH5YZugjuGACjGId3Y2bkfT o85EkA2zeXEzWOuegrJSzC' .
            'dSuBSctLjpJJluIeZXqoUJaYGK7d/dLJgkJU gBdApqRPDUdVtRI3pROz2zKzcYw3seQt6vgayIuyHTF8YfFp9eFg=",' .
            '"TRANDESC":"商户接入--商品订单建立--WAP网站商户直连","PAYORDNO":"P016101400000031","PRDORDTYPE":"0",' .
            '"COMPANY_BRIEF":"广州商物通测试商户","SENDID":"12","NOTIFYURL":"http://two123.comxa.com/pay/return.php",' .
            '"LINKREQIP":"192.168.29.2","REQUESTFRONTURL":"https://101.231.204.80:5000/gateway/api/appTransReq.do",' .
            '"CUST_STATUS":"0","SENDURL":"http://59.41.60.158:8914/TradeAppServer/user/sms.jf",' .
            '"FRONTURL":"http://59.41.60.154:8090/RMobPay/888889.tran","TERMCODE":"192.168.29.2",'.
            '"SIGNMSG":"","AUT_STS":"1","PROD_CODE":"CP00000003","TXNSUBTYPE":"01","NOWDATE":"20161014",' .
            '"ORDERTIME":"20161014113912","LOSEDATE":"5","MERCHANTID":"00000000000698","SIGNTYPE":"MD5",' .
            '"MSGTYP":"N","EXPR_DATE":"20161014","CURRENCYCODE":"156"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $zhifu->setResponse($response);

        $options = [
            'number' => 'acctest',
            'orderId' => '201503220000000123',
            'orderCreateDate' => '2015-03-22 21:25:29',
            'amount' => '100',
            'notify_url' => 'http://pay.xxx.xxx/app/return.php',
            'paymentVendorId' => '1088',
            'username' => 'php1test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.www.56zhifu.com',
        ];

        $zhifu->setPrivateKey('test');
        $zhifu->setOptions($options);
        $zhifu->getVerifyData();
    }

    /**
     * 測試支付銀行為手機支付
     */
    public function testPayWithWap()
    {
        $zhifu = new Zhifu();
        $zhifu->setContainer($this->container);
        $zhifu->setClient($this->client);

        $result = '{"MERID":"777290058132938","KEYVAL":"00000031","CHANNELTYPE":"08",' .
            '"ACCESSURL":"http://59.41.60.154:8090/user/html/upopwaphtml/",' .
            '"INMSG":"prdOrdNo=201610140000006662&merchantId=00000000000698&prodCode=CP00000003' .
            '&prdOrdType=0&ordAmt=100&orderTime=20161014113912&signType=MD5","BANKCOD":"01010000",' .
            '"TXNTIME":"20161014113902","ACCESSTYPE":"0","ENCODING":"UTF-8","VERSION":"5.0.0","TXAMT":"100",' .
            '"TELLERID":"00000000000000000000","RSPMSG":"交易成功",' .
            '"UPOPWAPPAYURL":"http://59.41.60.154:8090/user/html/upopwaphtml/P016101400000031.html",' .
            '"PAGEURL":"/home/payment/apache-tomcat-7.0.64/webapps/user/html/upopwaphtml/",' .
            '"SIGNMETHOD":"01","ORDSTATUS":"00","CONFIGNAME":"phonePayConfig","ORDERDATE":"",' .
            '"OUTSTR":"f60f3e8e24a2d5519f79c82363561439","MERNO":"00000000000698","TXN_ORG_NO":"000001",' .
            '"PRDORDNO":"201610140000006662","BANKCODE":"01010000","IP":"192.168.0.92","PRODCODE":"CP00000003",' .
            '"ORDAMT":"100","NOWTIME":"113902","PRDDESC":"","TRANCODE":"820001","PAYTYPE":"01","G_NUMPERPAG":"5",' .
            '"RETURL":"http://two123.comxa.com/pay/return.php","RSPCOD":"00000","ISPROXY":"0","TXNAMT":"100",' .
            '"TXCCY":"CNY","BIZTYPE":"000201","TXNCOMAMT":"0","TODAY":"20161014","PRDNAME":"7068703174657374",' .
            '"INSTR":"prdOrdNo=201610140000006662&merchantId=00000000000698&prodCode=CP00000003&prdOrdType=0' .
            '&ordAmt=100&orderTime=20161014113912&signType=MD5NSOTI80P273IVE69LSR203PK",' .
            '"KEY":"NSOTI80P273IVE69LSR203PK","ACTDAT":"20161014113902","ORDCCY":"CNY","ORDSOURCE":"0",' .
            '"CERTID":"40220995861346480087409489142384722381","TXNTYPE":"01","ORDERID":"P016101400000031",' .
            '"BACKURL":"http://59.41.60.154:8090/user/565102.tran","CUST_ID":"",' .
            '"SIGNATURE":"WsFyuBRbiNYlE92bt0/UbN/uKqWeZ  b4ps3HH5YZugjuGACjGId3Y2bkfT o85EkA2zeXEzWOuegrJSzC' .
            'dSuBSctLjpJJluIeZXqoUJaYGK7d/dLJgkJU gBdApqRPDUdVtRI3pROz2zKzcYw3seQt6vgayIuyHTF8YfFp9eFg=",' .
            '"TRANDESC":"商户接入--商品订单建立--WAP网站商户直连","PAYORDNO":"P016101400000031","PRDORDTYPE":"0",' .
            '"COMPANY_BRIEF":"广州商物通测试商户","SENDID":"12","NOTIFYURL":"http://two123.comxa.com/pay/return.php",' .
            '"LINKREQIP":"192.168.29.2","REQUESTFRONTURL":"https://101.231.204.80:5000/gateway/api/appTransReq.do",' .
            '"CUST_STATUS":"0","SENDURL":"http://59.41.60.158:8914/TradeAppServer/user/sms.jf",' .
            '"FRONTURL":"http://59.41.60.154:8090/RMobPay/888889.tran","TERMCODE":"192.168.29.2",'.
            '"SIGNMSG":"","AUT_STS":"1","PROD_CODE":"CP00000003","TXNSUBTYPE":"01","NOWDATE":"20161014",' .
            '"ORDERTIME":"20161014113912","LOSEDATE":"5","MERCHANTID":"00000000000698","SIGNTYPE":"MD5",' .
            '"MSGTYP":"N","EXPR_DATE":"20161014","CURRENCYCODE":"156"}';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type:application/json;charset=UTF-8');

        $zhifu->setResponse($response);

        $options = [
            'number' => 'acctest',
            'orderId' => '201503220000000123',
            'orderCreateDate' => '2015-03-22 21:25:29',
            'amount' => '100',
            'notify_url' => 'http://pay.xxx.xxx/app/return.php',
            'paymentVendorId' => '1088',
            'username' => 'php1test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.www.56zhifu.com',
        ];

        $zhifu->setPrivateKey('test');
        $zhifu->setOptions($options);
        $requestData = $zhifu->getVerifyData();

        $this->assertEquals(
            'http://59.41.60.154:8090/user/html/upopwaphtml/P016101400000031.html',
            $requestData['act_url']
        );
    }

    /**
     * 測試返回時缺少私鑰
     */
    public function testReturnWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $zhifu = new Zhifu();
        $zhifu->verifyOrderPayment([]);
    }

    /**
     * 測試返回時未指定返回參數
     */
    public function testReturnWithNoReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $zhifu = new Zhifu();
        $zhifu->setPrivateKey('test');
        $zhifu->verifyOrderPayment([]);
    }

    /**
     * 測試返回時缺少加密簽名
     */
    public function testReturnWithoutSignMsg()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $options = [
            'versionId' => '5.0.0',
            'merchantId' => '00000000003974',
            'orderId' => '201610120000006623',
            'settleDate' => '20161012142817',
            'completeDate' => '20161012142817',
            'status' => '1',
            'notifyTyp' => '0',
            'payOrdNo' => 'P016101200000540',
            'orderAmt' => '100',
            'notifyUrl' => 'http://two123.comxa.com/pay/return.php',
            'signType' => 'MD5',
        ];

        $zhifu = new Zhifu();
        $zhifu->setPrivateKey('test');
        $zhifu->setOptions($options);
        $zhifu->verifyOrderPayment([]);
    }

    /**
     * 測試返回時簽名驗證錯誤
     */
    public function testReturnSignatureVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $options = [
            'versionId' => '5.0.0',
            'merchantId' => '00000000003974',
            'orderId' => '201610120000006623',
            'settleDate' => '20161012142817',
            'completeDate' => '20161012142817',
            'status' => '1',
            'notifyTyp' => '0',
            'payOrdNo' => 'P016101200000540',
            'orderAmt' => '100',
            'notifyUrl' => 'http://two123.comxa.com/pay/return.php',
            'signType' => 'MD5',
            'signature' => '1812f956dce54467e02d2f693cab6c49',
        ];

        $zhifu = new Zhifu();
        $zhifu->setPrivateKey('test');
        $zhifu->setOptions($options);
        $zhifu->verifyOrderPayment([]);
    }

    /**
     * 測試返回時支付失敗
     */
    public function testReturnPaymentFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $options = [
            'versionId' => '5.0.0',
            'merchantId' => '00000000003974',
            'orderId' => '201610120000006623',
            'settleDate' => '20161012142817',
            'completeDate' => '20161012142817',
            'status' => '0',
            'notifyTyp' => '0',
            'payOrdNo' => 'P016101200000540',
            'orderAmt' => '100',
            'notifyUrl' => 'http://two123.comxa.com/pay/return.php',
            'signType' => 'MD5',
            'signature' => 'f0997c649312a0f97e2462a832c1e15d',
        ];

        $zhifu = new Zhifu();
        $zhifu->setPrivateKey('test');
        $zhifu->setOptions($options);
        $zhifu->verifyOrderPayment([]);
    }

    /**
     * 測試返回結果為訂單號錯誤
     */
    public function testReturnOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $options = [
            'versionId' => '5.0.0',
            'merchantId' => '00000000003974',
            'orderId' => '201610120000006623',
            'settleDate' => '20161012142817',
            'completeDate' => '20161012142817',
            'status' => '1',
            'notifyTyp' => '0',
            'payOrdNo' => 'P016101200000540',
            'orderAmt' => '100',
            'notifyUrl' => 'http://two123.comxa.com/pay/return.php',
            'signType' => 'MD5',
            'signature' => 'de64c99d29c970324b93e631ceb7c677',
        ];

        $entry = ['id' => '201608040000005975'];

        $zhifu = new Zhifu();
        $zhifu->setPrivateKey('test');
        $zhifu->setOptions($options);
        $zhifu->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果為訂單金額錯誤
     */
    public function testReturnOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $options = [
            'versionId' => '5.0.0',
            'merchantId' => '00000000003974',
            'orderId' => '201610120000006623',
            'settleDate' => '20161012142817',
            'completeDate' => '20161012142817',
            'status' => '1',
            'notifyTyp' => '0',
            'payOrdNo' => 'P016101200000540',
            'orderAmt' => '100',
            'notifyUrl' => 'http://two123.comxa.com/pay/return.php',
            'signType' => 'MD5',
            'signature' => 'de64c99d29c970324b93e631ceb7c677',
        ];

        $entry = [
            'id' => '201610120000006623',
            'amount' => '0.02',
        ];

        $zhifu = new Zhifu();
        $zhifu->setPrivateKey('test');
        $zhifu->setOptions($options);
        $zhifu->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturnOrder()
    {
        $options = [
            'versionId' => '5.0.0',
            'merchantId' => '00000000003974',
            'orderId' => '201610120000006623',
            'settleDate' => '20161012142817',
            'completeDate' => '20161012142817',
            'status' => '1',
            'notifyTyp' => '0',
            'payOrdNo' => 'P016101200000540',
            'orderAmt' => '100',
            'notifyUrl' => 'http://two123.comxa.com/pay/return.php',
            'signType' => 'MD5',
            'signature' => 'de64c99d29c970324b93e631ceb7c677',
        ];

        $entry = [
            'id' => '201610120000006623',
            'amount' => '1',
        ];

        $zhifu = new Zhifu();
        $zhifu->setPrivateKey('test');
        $zhifu->setOptions($options);
        $zhifu->verifyOrderPayment($entry);

        $this->assertEquals('success', $zhifu->getMsg());
    }

    /**
     * 測試訂單查詢缺少私鑰
     */
    public function testTrackingWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $zhifu = new Zhifu();
        $zhifu->paymentTracking();
    }

    /**
     * 測試訂單查詢未指定查詢參數
     */
    public function testTrackingWithNoTrackingParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking parameter specified',
            180138
        );

        $zhifu = new Zhifu();
        $zhifu->setPrivateKey('test');
        $zhifu->paymentTracking();
    }

    /**
     * 測試訂單查詢沒有帶入verify_url的情況
     */
    public function testTrackingWithoutVerifyUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No verify_url specified',
            180140
        );

        $options = [
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'verify_url' => '',
        ];

        $zhifu = new Zhifu();
        $zhifu->setPrivateKey('test');
        $zhifu->setOptions($options);
        $zhifu->paymentTracking();
    }

    /**
     * 測試訂單查詢返回沒有必要參數
     */
    public function testTrackingNoReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $options = [
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.www.56zhifu.com',
        ];

        $result = '<?xml version="1.0" encoding="UTF-8"?>' .
            '<orderPkg retMsg="成功"><order orderId="201610140000006663" amount="100" ' .
            'orderDate="20161014" completeDate="20161014122109" status="11" statusDes="未支付订单"/></orderPkg>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $zhifu = new Zhifu();
        $zhifu->setContainer($this->container);
        $zhifu->setClient($this->client);
        $zhifu->setResponse($response);
        $zhifu->setPrivateKey('test');
        $zhifu->setOptions($options);
        $zhifu->paymentTracking();
    }

    /**
     * 測試訂單查詢訂單其他錯誤
     */
    public function testTrackingReturnPaymentTrackingError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '商户未配置MD5key！',
            180123
        );

        $options = [
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.www.56zhifu.com',
        ];

        $result = '<?xml version="1.0" encoding="UTF-8"?><orderPkg retCode="0002" retMsg="商户未配置MD5key！"/>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $zhifu = new Zhifu();
        $zhifu->setContainer($this->container);
        $zhifu->setClient($this->client);
        $zhifu->setResponse($response);
        $zhifu->setPrivateKey('test');
        $zhifu->setOptions($options);
        $zhifu->paymentTracking();
    }

    /**
     * 測試訂單查詢返回沒有其他必要參數
     */
    public function testTrackingNoReturnOtherParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $options = [
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.www.56zhifu.com',
        ];

        $result = '<?xml version="1.0" encoding="UTF-8"?><orderPkg retCode="0001" retMsg="成功"/>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $zhifu = new Zhifu();
        $zhifu->setContainer($this->container);
        $zhifu->setClient($this->client);
        $zhifu->setResponse($response);
        $zhifu->setPrivateKey('test');
        $zhifu->setOptions($options);
        $zhifu->paymentTracking();
    }

    /**
     * 測試訂單查詢結果訂單狀態不為1則代表支付失敗
     */
    public function testTrackingReturnOrderPaymentfailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $options = [
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.www.56zhifu.com',
        ];

        $result = '<?xml version="1.0" encoding="UTF-8"?>' .
            '<orderPkg retCode="0001" retMsg="成功">' .
            '<order orderId="201610140000006663" amount="100" orderDate="20161014" completeDate="20161014122109"' .
            ' status="0" statusDes="未支付订单"/></orderPkg>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $zhifu = new Zhifu();
        $zhifu->setContainer($this->container);
        $zhifu->setClient($this->client);
        $zhifu->setResponse($response);
        $zhifu->setPrivateKey('test');
        $zhifu->setOptions($options);
        $zhifu->paymentTracking();
    }

    /**
     * 測試訂單查詢結果單號不正確的情況
     */
    public function testTrackingOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $options = [
            'number' => '20130809',
            'orderId' => '201503160000002219',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.www.56zhifu.com',
        ];

        $result = '<?xml version="1.0" encoding="UTF-8"?>' .
            '<orderPkg retCode="0001" retMsg="成功"><order orderId="201610120000006623" amount="100"' .
            ' orderDate="20161012" completeDate="20161012142817" status="1" statusDes="支付成功"/></orderPkg>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $zhifu = new Zhifu();
        $zhifu->setContainer($this->container);
        $zhifu->setClient($this->client);
        $zhifu->setResponse($response);
        $zhifu->setPrivateKey('test');
        $zhifu->setOptions($options);
        $zhifu->paymentTracking();
    }

    /**
     * 測試訂單查詢結果金額不正確的情況
     */
    public function testTrackingOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $options = [
            'number' => '20130809',
            'orderId' => '201610120000006623',
            'amount' => '0.1',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.www.56zhifu.com',
        ];

        $result = '<?xml version="1.0" encoding="UTF-8"?>' .
            '<orderPkg retCode="0001" retMsg="成功"><order orderId="201610120000006623" amount="100"' .
            ' orderDate="20161012" completeDate="20161012142817" status="1" statusDes="支付成功"/></orderPkg>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $zhifu = new Zhifu();
        $zhifu->setContainer($this->container);
        $zhifu->setClient($this->client);
        $zhifu->setResponse($response);
        $zhifu->setPrivateKey('test');
        $zhifu->setOptions($options);
        $zhifu->paymentTracking();
    }

    /**
     * 測試訂單查詢成功
     */
    public function testTrackingSuccess()
    {
        $options = [
            'number' => '20130809',
            'orderId' => '201610120000006623',
            'amount' => '1',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => 'payment.https.www.56zhifu.com',
        ];

        $result = '<?xml version="1.0" encoding="UTF-8"?>' .
            '<orderPkg retCode="0001" retMsg="成功"><order orderId="201610120000006623" amount="100"' .
            ' orderDate="20161012" completeDate="20161012142817" status="1" statusDes="支付成功"/></orderPkg>';

        $response = new Response();
        $response->setContent($result);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $zhifu = new Zhifu();
        $zhifu->setContainer($this->container);
        $zhifu->setClient($this->client);
        $zhifu->setResponse($response);
        $zhifu->setPrivateKey('test');
        $zhifu->setOptions($options);
        $zhifu->paymentTracking();
    }
}

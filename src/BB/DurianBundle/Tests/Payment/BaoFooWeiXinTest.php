<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\BaoFooWeiXin;
use Buzz\Message\Response;

class BaoFooWeiXinTest extends DurianTestCase
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
     * 測試支付時未指定支付參數
     */
    public function testPayWithNoPayParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $baoFooWeiXin = new BaoFooWeiXin();
        $baoFooWeiXin->setPrivateKey('test');
        $baoFooWeiXin->getVerifyData();
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

        $options = [
            'number' => '123456',
            'orderId' => '201608040000004412',
            'orderCreateDate' => '2016-08-04 12:25:29',
            'amount' => '100',
            'notify_url' => 'http://pay.test/pay/',
            'username' => 'testUser',
            'merchant_extra' => ['terminal_id' => '123456'],
            'rsa_private_key' => '',
        ];

        $baoFooWeiXin = new BaoFooWeiXin();
        $baoFooWeiXin->setOptions($options);
        $baoFooWeiXin->getVerifyData();
    }

    /**
     * 測試支付時取得商家私鑰為空字串
     */
    public function testPayGetMerchantKeyFileContentNull()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Rsa private key is empty',
            180092
        );

        $options = [
            'number' => '123456',
            'orderId' => '201608040000004412',
            'orderCreateDate' => '2016-08-04 12:25:29',
            'amount' => '100',
            'notify_url' => 'http://pay.test/pay/',
            'username' => 'testUser',
            'merchant_extra' => ['terminal_id' => '123456'],
            'rsa_private_key' => '',
        ];

        $baoFooWeiXin = new BaoFooWeiXin();
        $baoFooWeiXin->setPrivateKey('test');
        $baoFooWeiXin->setOptions($options);
        $baoFooWeiXin->getVerifyData();
    }

    /**
     * 測試支付時取得商家私鑰失敗
     */
    public function testPayGetMerchantKeyFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Get rsa private key failure',
            180093
        );

        $options = [
            'number' => '123456',
            'orderId' => '201608040000004412',
            'orderCreateDate' => '2016-08-04 12:25:29',
            'amount' => '100',
            'notify_url' => 'http://pay.test/pay/',
            'username' => 'testUser',
            'merchant_extra' => ['terminal_id' => '123456'],
            'rsa_private_key' => base64_encode('test'),
        ];

        $baoFooWeiXin = new BaoFooWeiXin();
        $baoFooWeiXin->setPrivateKey('test');
        $baoFooWeiXin->setOptions($options);
        $baoFooWeiXin->getVerifyData();
    }

    /**
     * 測試支付時生成加密簽名錯誤
     */
    public function testPayGenerateSignatureFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Generate signature failure',
            180144
        );

        $config = [
            'private_key_bits' => 512,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];

        $cert = '';
        $pkcsPrivate = '';
        $newKey = openssl_pkey_new($config);
        $privatekey = openssl_pkey_get_private($newKey);
        $csrKey = openssl_csr_new([], $newKey);
        $csrSign = openssl_csr_sign($csrKey, null, $newKey, 365);
        openssl_x509_export($csrSign, $cert);
        openssl_pkcs12_export($cert, $pkcsPrivate, $privatekey, 'test');

        $options = [
            'number' => '123456',
            'orderId' => '201608040000004412',
            'orderCreateDate' => '2016-08-04 12:25:29',
            'amount' => '100',
            'notify_url' => 'http://pay.test/pay/return.php',
            'username' => 'testUser',
            'merchant_extra' => ['terminal_id' => '123456'],
            'rsa_private_key' => base64_encode($pkcsPrivate),
        ];

        $baoFooWeiXin = new BaoFooWeiXin();
        $baoFooWeiXin->setPrivateKey('test');
        $baoFooWeiXin->setOptions($options);
        $requestData = $baoFooWeiXin->getVerifyData();

        $notifyUrl = sprintf(
            '%s?trans_id=%s',
            $options['notify_url'],
            $options['orderId']
        );

        $this->assertEquals($options['number'], $requestData['member_id']);
        $this->assertEquals($options['orderId'], $requestData['trans_id']);
        $this->assertEquals('20160804122529', $requestData['trade_date']);
        $this->assertEquals('10000', $requestData['txn_amt']);
        $this->assertEquals($notifyUrl, $requestData['page_url']);
        $this->assertEquals($notifyUrl, $requestData['return_url']);
        $this->assertEquals($options['username'], $requestData['commodity_name']);
    }

    /**
     * 測試加密
     */
    public function testPay()
    {
        $cert = '';
        $pkcsPrivate = '';
        $newKey = openssl_pkey_new();
        $privatekey = openssl_pkey_get_private($newKey);
        $csrKey = openssl_csr_new([], $newKey);
        $csrSign = openssl_csr_sign($csrKey, null, $newKey, 365);
        openssl_x509_export($csrSign, $cert);
        openssl_pkcs12_export($cert, $pkcsPrivate, $privatekey, 'test');

        $options = [
            'number' => '123456',
            'orderId' => '201608040000004412',
            'orderCreateDate' => '2016-08-04 12:25:29',
            'amount' => '100',
            'notify_url' => 'http://pay.test/pay/return.php',
            'username' => 'testUser',
            'merchant_extra' => ['terminal_id' => '123456'],
            'rsa_private_key' => base64_encode($pkcsPrivate),
        ];

        $baoFooWeiXin = new BaoFooWeiXin();
        $baoFooWeiXin->setPrivateKey('test');
        $baoFooWeiXin->setOptions($options);
        $requestData = $baoFooWeiXin->getVerifyData();

        $notifyUrl = sprintf(
            '%s?trans_id=%s',
            $options['notify_url'],
            $options['orderId']
        );

        $this->assertEquals($options['number'], $requestData['member_id']);
        $this->assertEquals($options['orderId'], $requestData['trans_id']);
        $this->assertEquals('20160804122529', $requestData['trade_date']);
        $this->assertEquals('10000', $requestData['txn_amt']);
        $this->assertEquals($notifyUrl, $requestData['page_url']);
        $this->assertEquals($notifyUrl, $requestData['return_url']);
        $this->assertEquals($options['username'], $requestData['commodity_name']);
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

        $baoFooWeiXin = new BaoFooWeiXin();
        $baoFooWeiXin->setPrivateKey('test');
        $baoFooWeiXin->verifyOrderPayment([]);
    }

    /**
     * 測試返回時取得RSA公鑰為空
     */
    public function testReturnGetRsaPublicKeyEmpty()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Rsa public key is empty',
            180095
        );

        $config = [
            'private_key_bits' => 1024,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];

        $cert = '';
        $pkcsPrivate = '';
        $newKey = openssl_pkey_new($config);
        $privatekey = openssl_pkey_get_private($newKey);
        $csrKey = openssl_csr_new([], $newKey);
        $csrSign = openssl_csr_sign($csrKey, null, $newKey, 365);
        openssl_x509_export($csrSign, $cert);
        openssl_pkcs12_export($cert, $pkcsPrivate, $privatekey, 'test');

        $parseData = [
            'additional_info' => '',
            'data_type' => 'json',
            'member_id' => '122234',
            'req_reserved' => '',
            'resp_code' => '0000',
            'resp_msg' => '交易成功',
            'succ_amt' => '1',
            'terminal_id' => '30411',
            'trade_date' => '20160804122617',
            'trans_id' => '201608040000004407',
            'txn_sub_type' => '01',
            'txn_type' => '10199',
            'version' => '4.0.0.0',
        ];

        $encodeStr = str_replace("\\/", "/", json_encode($parseData, true));
        $content = base64_encode($encodeStr);
        $totalLen = strlen($content);

        $data = '';
        $encrypted = '';
        $pos = 0;

        while ($pos < $totalLen){
            openssl_private_encrypt(substr($content, $pos, 117), $data, $privatekey);
            $encrypted .= bin2hex($data);
            $pos += 117;
        }

        $options = [
            'data_content' => $encrypted,
            'rsa_public_key' => '',
        ];

        $baoFooWeiXin = new BaoFooWeiXin();
        $baoFooWeiXin->setPrivateKey('test');
        $baoFooWeiXin->setOptions($options);
        $baoFooWeiXin->verifyOrderPayment([]);
    }

    /**
     * 測試返回時取得RSA公鑰失敗
     */
    public function testReturnGetRsaPublicKeyError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Get rsa public key failure',
            180096
        );

        $config = [
            'private_key_bits' => 1024,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];

        $cert = '';
        $pkcsPrivate = '';
        $newKey = openssl_pkey_new($config);
        $privatekey = openssl_pkey_get_private($newKey);
        $csrKey = openssl_csr_new([], $newKey);
        $csrSign = openssl_csr_sign($csrKey, null, $newKey, 365);
        openssl_x509_export($csrSign, $cert);
        openssl_pkcs12_export($cert, $pkcsPrivate, $privatekey, 'test');

        $parseData = [
            'additional_info' => '',
            'data_type' => 'json',
            'member_id' => '122234',
            'req_reserved' => '',
            'resp_code' => '0000',
            'resp_msg' => '交易成功',
            'succ_amt' => '1',
            'terminal_id' => '30411',
            'trade_date' => '20160804122617',
            'trans_id' => '201608040000004407',
            'txn_sub_type' => '01',
            'txn_type' => '10199',
            'version' => '4.0.0.0',
        ];

        $encodeStr = str_replace("\\/", "/", json_encode($parseData, true));
        $content = base64_encode($encodeStr);
        $totalLen = strlen($content);

        $data = '';
        $encrypted = '';
        $pos = 0;

        while ($pos < $totalLen){
            openssl_private_encrypt(substr($content, $pos, 117), $data, $privatekey);
            $encrypted .= bin2hex($data);
            $pos += 117;
        }

        $options = [
            'data_content' => $encrypted,
            'rsa_public_key' => 'test',
        ];

        $baoFooWeiXin = new BaoFooWeiXin();
        $baoFooWeiXin->setPrivateKey('test');
        $baoFooWeiXin->setOptions($options);
        $baoFooWeiXin->verifyOrderPayment([]);
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

        $config = [
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];

        $cert = '';
        $pkcsPrivate = '';
        $newKey = openssl_pkey_new($config);
        $privatekey = openssl_pkey_get_private($newKey);
        $csrKey = openssl_csr_new([], $newKey);
        $csrSign = openssl_csr_sign($csrKey, null, $newKey, 365);
        openssl_x509_export($csrSign, $cert);
        openssl_pkcs12_export($cert, $pkcsPrivate, $privatekey, 'test');

        $parseData = [
            'additional_info' => '',
            'data_type' => 'json',
            'member_id' => '122234',
            'req_reserved' => '',
            'resp_code' => '0001',
            'resp_msg' => '交易失败。详情请咨询宝付',
            'succ_amt' => '1',
            'terminal_id' => '30411',
            'trade_date' => '20160804122617',
            'trans_id' => '201608040000004407',
            'txn_sub_type' => '01',
            'txn_type' => '10199',
            'version' => '4.0.0.0',
        ];

        $encodeStr = str_replace("\\/", "/", json_encode($parseData, true));
        $content = base64_encode($encodeStr);
        $totalLen = strlen($content);

        $data = '';
        $encrypted = '';
        $pos = 0;

        while ($pos < $totalLen){
            openssl_private_encrypt(substr($content, $pos, 117), $data, $privatekey);
            $encrypted .= bin2hex($data);
            $pos += 117;
        }

        $options = [
            'data_content' => $encrypted,
            'rsa_public_key' => base64_encode($cert),
        ];

        $baoFooWeiXin = new BaoFooWeiXin();
        $baoFooWeiXin->setPrivateKey('test');
        $baoFooWeiXin->setOptions($options);
        $baoFooWeiXin->verifyOrderPayment([]);
    }

    /**
     * 測試返回時缺少訂單號
     */
    public function testReturnWithoutTransId()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $config = [
            'private_key_bits' => 1024,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];

        $cert = '';
        $pkcsPrivate = '';
        $newKey = openssl_pkey_new($config);
        $privatekey = openssl_pkey_get_private($newKey);
        $csrKey = openssl_csr_new([], $newKey);
        $csrSign = openssl_csr_sign($csrKey, null, $newKey, 365);
        openssl_x509_export($csrSign, $cert);
        openssl_pkcs12_export($cert, $pkcsPrivate, $privatekey, 'test');

        $parseData = [
            'additional_info' => '',
            'data_type' => 'json',
            'member_id' => '122234',
            'req_reserved' => '',
            'resp_code' => '0001',
            'resp_msg' => '交易失败。详情请咨询宝付',
            'succ_amt' => '1',
            'terminal_id' => '30411',
            'trade_date' => '20160804122617',
            'txn_sub_type' => '01',
            'txn_type' => '10199',
            'version' => '4.0.0.0',
        ];

        $encodeStr = str_replace("\\/", "/", json_encode($parseData, true));
        $content = base64_encode($encodeStr);
        $totalLen = strlen($content);

        $data = '';
        $encrypted = '';
        $pos = 0;

        while ($pos < $totalLen){
            openssl_private_encrypt(substr($content, $pos, 117), $data, $privatekey);
            $encrypted .= bin2hex($data);
            $pos += 117;
        }

        $options = [
            'data_content' => $encrypted,
            'rsa_public_key' => base64_encode($cert),
        ];

        $baoFooWeiXin = new BaoFooWeiXin();
        $baoFooWeiXin->setPrivateKey('test');
        $baoFooWeiXin->setOptions($options);
        $baoFooWeiXin->verifyOrderPayment([]);
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

        $config = [
            'private_key_bits' => 1024,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];

        $cert = '';
        $pkcsPrivate = '';
        $newKey = openssl_pkey_new($config);
        $privatekey = openssl_pkey_get_private($newKey);
        $csrKey = openssl_csr_new([], $newKey);
        $csrSign = openssl_csr_sign($csrKey, null, $newKey, 365);
        openssl_x509_export($csrSign, $cert);
        openssl_pkcs12_export($cert, $pkcsPrivate, $privatekey, 'test');

        $parseData = [
            'additional_info' => '',
            'data_type' => 'json',
            'member_id' => '122234',
            'req_reserved' => '',
            'resp_code' => '0001',
            'resp_msg' => '交易失败。详情请咨询宝付',
            'succ_amt' => '1',
            'terminal_id' => '30411',
            'trade_date' => '20160804122617',
            'trans_id' => '201608040000004407',
            'txn_sub_type' => '01',
            'txn_type' => '10199',
            'version' => '4.0.0.0',
        ];

        $encodeStr = str_replace("\\/", "/", json_encode($parseData, true));
        $content = base64_encode($encodeStr);
        $totalLen = strlen($content);

        $data = '';
        $encrypted = '';
        $pos = 0;

        while ($pos < $totalLen){
            openssl_private_encrypt(substr($content, $pos, 117), $data, $privatekey);
            $encrypted .= bin2hex($data);
            $pos += 117;
        }

        $options = [
            'data_content' => $encrypted,
            'rsa_public_key' => base64_encode($cert),
        ];

        $baoFooWeiXin = new BaoFooWeiXin();
        $baoFooWeiXin->setPrivateKey('test');
        $baoFooWeiXin->setOptions($options);
        $baoFooWeiXin->verifyOrderPayment([]);
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

        $config = [
            'private_key_bits' => 1024,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];

        $cert = '';
        $pkcsPrivate = '';
        $newKey = openssl_pkey_new($config);
        $privatekey = openssl_pkey_get_private($newKey);
        $csrKey = openssl_csr_new([], $newKey);
        $csrSign = openssl_csr_sign($csrKey, null, $newKey, 365);
        openssl_x509_export($csrSign, $cert);
        openssl_pkcs12_export($cert, $pkcsPrivate, $privatekey, 'test');

        $parseData = [
            'additional_info' => '',
            'data_type' => 'json',
            'member_id' => '122234',
            'req_reserved' => '',
            'resp_code' => '0000',
            'resp_msg' => '交易成功',
            'succ_amt' => '1',
            'terminal_id' => '30411',
            'trade_date' => '20160804122617',
            'trans_id' => '201608040000004407',
            'txn_sub_type' => '01',
            'txn_type' => '10199',
            'version' => '4.0.0.0',
        ];

        $encodeStr = str_replace("\\/", "/", json_encode($parseData, true));
        $content = base64_encode($encodeStr);
        $totalLen = strlen($content);

        $data = '';
        $encrypted = '';
        $pos = 0;

        while ($pos < $totalLen){
            openssl_private_encrypt(substr($content, $pos, 117), $data, $privatekey);
            $encrypted .= bin2hex($data);
            $pos += 117;
        }

        $options = [
            'data_content' => $encrypted,
            'rsa_public_key' => base64_encode($cert),
        ];

        $entry = ['id' => '201608040000004406'];

        $baoFooWeiXin = new BaoFooWeiXin();
        $baoFooWeiXin->setPrivateKey('test');
        $baoFooWeiXin->setOptions($options);
        $baoFooWeiXin->verifyOrderPayment($entry);
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

        $config = [
            'private_key_bits' => 1024,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];

        $cert = '';
        $pkcsPrivate = '';
        $newKey = openssl_pkey_new($config);
        $privatekey = openssl_pkey_get_private($newKey);
        $csrKey = openssl_csr_new([], $newKey);
        $csrSign = openssl_csr_sign($csrKey, null, $newKey, 365);
        openssl_x509_export($csrSign, $cert);
        openssl_pkcs12_export($cert, $pkcsPrivate, $privatekey, 'test');

        $parseData = [
            'additional_info' => '',
            'data_type' => 'json',
            'member_id' => '122234',
            'req_reserved' => '',
            'resp_code' => '0000',
            'resp_msg' => '交易成功',
            'succ_amt' => '1',
            'terminal_id' => '30411',
            'trade_date' => '20160804122617',
            'trans_id' => '201608040000004407',
            'txn_sub_type' => '01',
            'txn_type' => '10199',
            'version' => '4.0.0.0',
        ];

        $encodeStr = str_replace("\\/", "/", json_encode($parseData, true));
        $content = base64_encode($encodeStr);
        $totalLen = strlen($content);

        $data = '';
        $encrypted = '';
        $pos = 0;

        while ($pos < $totalLen){
            openssl_private_encrypt(substr($content, $pos, 117), $data, $privatekey);
            $encrypted .= bin2hex($data);
            $pos += 117;
        }

        $options = [
            'data_content' => $encrypted,
            'rsa_public_key' => base64_encode($cert),
        ];

        $entry = [
            'id' => '201608040000004407',
            'amount' => '1.00',
        ];

        $baoFooWeiXin = new BaoFooWeiXin();
        $baoFooWeiXin->setPrivateKey('test');
        $baoFooWeiXin->setOptions($options);
        $baoFooWeiXin->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturnOrder()
    {
        $config = [
            'private_key_bits' => 1024,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];

        $cert = '';
        $pkcsPrivate = '';
        $newKey = openssl_pkey_new($config);
        $privatekey = openssl_pkey_get_private($newKey);
        $csrKey = openssl_csr_new([], $newKey);
        $csrSign = openssl_csr_sign($csrKey, null, $newKey, 365);
        openssl_x509_export($csrSign, $cert);
        openssl_pkcs12_export($cert, $pkcsPrivate, $privatekey, 'test');

        $parseData = [
            'additional_info' => '',
            'data_type' => 'json',
            'member_id' => '122234',
            'req_reserved' => '',
            'resp_code' => '0000',
            'resp_msg' => '交易成功',
            'succ_amt' => '1',
            'terminal_id' => '30411',
            'trade_date' => '20160804122617',
            'trans_id' => '201608040000004407',
            'txn_sub_type' => '01',
            'txn_type' => '10199',
            'version' => '4.0.0.0',
        ];

        $encodeStr = str_replace("\\/", "/", json_encode($parseData, true));
        $content = base64_encode($encodeStr);
        $totalLen = strlen($content);

        $data = '';
        $encrypted = '';
        $pos = 0;

        while ($pos < $totalLen){
            openssl_private_encrypt(substr($content, $pos, 117), $data, $privatekey);
            $encrypted .= bin2hex($data);
            $pos += 117;
        }

        $options = [
            'data_content' => $encrypted,
            'rsa_public_key' => base64_encode($cert),
        ];

        $entry = [
            'id' => '201608040000004407',
            'amount' => '0.01',
        ];

        $baoFooWeiXin = new BaoFooWeiXin();
        $baoFooWeiXin->setPrivateKey('test');
        $baoFooWeiXin->setOptions($options);
        $baoFooWeiXin->verifyOrderPayment($entry);

        $this->assertEquals('OK', $baoFooWeiXin->getMsg());
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

        $baoFooWeiXin = new BaoFooWeiXin();
        $baoFooWeiXin->setPrivateKey('test');
        $baoFooWeiXin->paymentTracking();
    }

    /**
     * 測試訂單查詢缺少商家額外的參數設定terminal_id
     */
    public function testTrackingWithoutMerchantExtraTerminalId()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No merchant extra value specified',
            180143
        );

        $options = [
            'number' => '123456',
            'orderId' => '201608040000004412',
            'orderCreateDate' => '2016-08-04 12:25:29',
            'merchant_extra' => [],
        ];

        $baoFooWeiXin = new BaoFooWeiXin();
        $baoFooWeiXin->setPrivateKey('test');
        $baoFooWeiXin->setOptions($options);
        $baoFooWeiXin->paymentTracking();
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

        $options = [
            'number' => '123456',
            'orderId' => '201608040000004412',
            'orderCreateDate' => '2016-08-04 12:25:29',
            'merchant_extra' => ['terminal_id' => '123456'],
            'rsa_private_key' => '',
        ];

        $baoFooWeiXin = new BaoFooWeiXin();
        $baoFooWeiXin->setOptions($options);
        $baoFooWeiXin->paymentTracking();
    }

    /**
     * 測試訂單查詢取得商家私鑰為空字串
     */
    public function testTrackingGetMerchantKeyFileContentNull()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Rsa private key is empty',
            180092
        );

        $options = [
            'number' => '123456',
            'orderId' => '201608040000004412',
            'orderCreateDate' => '2016-08-04 12:25:29',
            'merchant_extra' => ['terminal_id' => '123456'],
            'rsa_private_key' => '',
        ];

        $baoFooWeiXin = new BaoFooWeiXin();
        $baoFooWeiXin->setPrivateKey('test');
        $baoFooWeiXin->setOptions($options);
        $baoFooWeiXin->paymentTracking();
    }

    /**
     * 測試訂單查詢取得商家私鑰失敗
     */
    public function testTrackingGetMerchantKeyFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Get rsa private key failure',
            180093
        );

        $options = [
            'number' => '123456',
            'orderId' => '201608040000004412',
            'orderCreateDate' => '2016-08-04 12:25:29',
            'merchant_extra' => ['terminal_id' => '123456'],
            'rsa_private_key' => 'test',
        ];

        $baoFooWeiXin = new BaoFooWeiXin();
        $baoFooWeiXin->setPrivateKey('test');
        $baoFooWeiXin->setOptions($options);
        $baoFooWeiXin->paymentTracking();
    }

    /**
     * 測試訂單查詢時生成加密簽名錯誤
     */
    public function testTrackingSignatureFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Generate signature failure',
            180144
        );

        $config = [
            'private_key_bits' => 512,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];

        $cert = '';
        $pkcsPrivate = '';
        $newKey = openssl_pkey_new($config);
        $privatekey = openssl_pkey_get_private($newKey);
        $csrKey = openssl_csr_new([], $newKey);
        $csrSign = openssl_csr_sign($csrKey, null, $newKey, 365);
        openssl_x509_export($csrSign, $cert);
        openssl_pkcs12_export($cert, $pkcsPrivate, $privatekey, 'test');

        $options = [
            'number' => '123456',
            'orderId' => '201608040000004412',
            'orderCreateDate' => '2016-08-04 12:25:29',
            'merchant_extra' => ['terminal_id' => '123456'],
            'rsa_private_key' => base64_encode($pkcsPrivate),
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '1.1.1.1',
        ];

        $baoFooWeiXin = new BaoFooWeiXin();
        $baoFooWeiXin->setPrivateKey('test');
        $baoFooWeiXin->setOptions($options);
        $baoFooWeiXin->paymentTracking();
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

        $cert = '';
        $pkcsPrivate = '';
        $newKey = openssl_pkey_new();
        $privatekey = openssl_pkey_get_private($newKey);
        $csrKey = openssl_csr_new([], $newKey);
        $csrSign = openssl_csr_sign($csrKey, null, $newKey, 365);
        openssl_x509_export($csrSign, $cert);
        openssl_pkcs12_export($cert, $pkcsPrivate, $privatekey, 'test');

        $options = [
            'number' => '123456',
            'orderId' => '201608040000004412',
            'orderCreateDate' => '2016-08-04 12:25:29',
            'merchant_extra' => ['terminal_id' => '123456'],
            'rsa_private_key' => base64_encode($pkcsPrivate),
            'verify_url' => '',
        ];

        $baoFooWeiXin = new BaoFooWeiXin();
        $baoFooWeiXin->setPrivateKey('test');
        $baoFooWeiXin->setOptions($options);
        $baoFooWeiXin->paymentTracking();
    }

    /**
     * 測試訂單查詢結果驗證取得RSA公鑰為空
     */
    public function testTrackingReturnGetRsaPublicKeyEmpty()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Rsa public key is empty',
            180095
        );

        $config = [
            'private_key_bits' => 1024,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];

        $cert = '';
        $pkcsPrivate = '';
        $newKey = openssl_pkey_new($config);
        $privatekey = openssl_pkey_get_private($newKey);
        $csrKey = openssl_csr_new([], $newKey);
        $csrSign = openssl_csr_sign($csrKey, null, $newKey, 365);
        openssl_x509_export($csrSign, $cert);
        openssl_pkcs12_export($cert, $pkcsPrivate, $privatekey, 'test');

        $parseData = [
            'additional_info' => '',
            'data_type' => 'json',
            'member_id' => '122234',
            'req_reserved' => '',
            'resp_msg' => '交易成功',
            'succ_amt' => '1',
            'terminal_id' => '30411',
            'trade_date' => '20160804122617',
            'trans_id' => '201608040000004407',
            'txn_sub_type' => '01',
            'txn_type' => '10199',
            'version' => '4.0.0.0',
        ];

        $encodeStr = str_replace("\\/", "/", json_encode($parseData, true));
        $content = base64_encode($encodeStr);
        $totalLen = strlen($content);

        $data = '';
        $encrypted = '';
        $pos = 0;

        while ($pos < $totalLen){
            openssl_private_encrypt(substr($content, $pos, 117), $data, $privatekey);
            $encrypted .= bin2hex($data);
            $pos += 117;
        }

        $options = [
            'number' => '123456',
            'orderId' => '201608040000004412',
            'orderCreateDate' => '2016-08-04 12:25:29',
            'merchant_extra' => ['terminal_id' => '123456'],
            'rsa_private_key' => base64_encode($pkcsPrivate),
            'verify_url' => '1.1.1.1',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'rsa_public_key' =>  '',
        ];

        $response = new Response();
        $response->setContent($encrypted);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $baoFooWeiXin = new BaoFooWeiXin();
        $baoFooWeiXin->setContainer($this->container);
        $baoFooWeiXin->setClient($this->client);
        $baoFooWeiXin->setResponse($response);
        $baoFooWeiXin->setPrivateKey('test');
        $baoFooWeiXin->setOptions($options);
        $baoFooWeiXin->paymentTracking();
    }

    /**
     * 測試訂單查詢結果解密驗證錯誤
     */
    public function testTrackingReturnDecryptValidationError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $config = [
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];

        $cert = '';
        $pkcsPrivate = '';
        $newKey = openssl_pkey_new($config);
        $privatekey = openssl_pkey_get_private($newKey);
        $csrKey = openssl_csr_new([], $newKey);
        $csrSign = openssl_csr_sign($csrKey, null, $newKey, 365);
        openssl_x509_export($csrSign, $cert);
        openssl_pkcs12_export($cert, $pkcsPrivate, $privatekey, 'test');

        $parseData = [
            'additional_info' => '',
            'data_type' => 'json',
            'member_id' => '122234',
            'req_reserved' => '',
            'resp_msg' => '交易成功',
            'succ_amt' => '1',
            'terminal_id' => '30411',
            'trade_date' => '20160804122617',
            'trans_id' => '201608040000004407',
            'txn_sub_type' => '01',
            'txn_type' => '10199',
            'version' => '4.0.0.0',
        ];

        $encodeStr = str_replace("\\/", "/", json_encode($parseData, true));
        $content = base64_encode($encodeStr);
        $totalLen = strlen($content);

        $data = '';
        $encrypted = '';
        $pos = 0;

        while ($pos < $totalLen){
            openssl_private_encrypt(substr($content, $pos, 117), $data, $privatekey);
            $encrypted .= bin2hex($data);
            $pos += 117;
        }

        $response = new Response();
        $response->setContent($encrypted);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $options = [
            'number' => '123456',
            'orderId' => '201608040000004412',
            'orderCreateDate' => '2016-08-04 12:25:29',
            'merchant_extra' => ['terminal_id' => '123456'],
            'rsa_private_key' => base64_encode($pkcsPrivate),
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '1.1.1.1',
            'rsa_public_key' => base64_encode($cert),
        ];

        $baoFooWeiXin = new BaoFooWeiXin();
        $baoFooWeiXin->setContainer($this->container);
        $baoFooWeiXin->setClient($this->client);
        $baoFooWeiXin->setResponse($response);
        $baoFooWeiXin->setPrivateKey('test');
        $baoFooWeiXin->setOptions($options);
        $baoFooWeiXin->paymentTracking();
    }

    /**
     * 測試訂單查詢結果驗證沒有respCode的情況
     */
    public function testTrackingReturnWithNoTrackingReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $config = [
            'private_key_bits' => 1024,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];

        $cert = '';
        $pkcsPrivate = '';
        $newKey = openssl_pkey_new($config);
        $privatekey = openssl_pkey_get_private($newKey);
        $csrKey = openssl_csr_new([], $newKey);
        $csrSign = openssl_csr_sign($csrKey, null, $newKey, 365);
        openssl_x509_export($csrSign, $cert);
        openssl_pkcs12_export($cert, $pkcsPrivate, $privatekey, 'test');

        $parseData = [
            'additional_info' => '',
            'data_type' => 'json',
            'member_id' => '122234',
            'req_reserved' => '',
            'resp_msg' => '交易成功',
            'succ_amt' => '1',
            'terminal_id' => '30411',
            'trade_date' => '20160804122617',
            'trans_id' => '201608040000004407',
            'txn_sub_type' => '01',
            'txn_type' => '10199',
            'version' => '4.0.0.0',
        ];

        $encodeStr = str_replace("\\/", "/", json_encode($parseData, true));
        $content = base64_encode($encodeStr);
        $totalLen = strlen($content);

        $data = '';
        $encrypted = '';
        $pos = 0;

        while ($pos < $totalLen){
            openssl_private_encrypt(substr($content, $pos, 117), $data, $privatekey);
            $encrypted .= bin2hex($data);
            $pos += 117;
        }

        $response = new Response();
        $response->setContent($encrypted);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $options = [
            'number' => '123456',
            'orderId' => '201608040000004412',
            'orderCreateDate' => '2016-08-04 12:25:29',
            'merchant_extra' => ['terminal_id' => '123456'],
            'rsa_private_key' => base64_encode($pkcsPrivate),
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '1.1.1.1',
            'rsa_public_key' => base64_encode($cert),
        ];

        $baoFooWeiXin = new BaoFooWeiXin();
        $baoFooWeiXin->setContainer($this->container);
        $baoFooWeiXin->setClient($this->client);
        $baoFooWeiXin->setResponse($response);
        $baoFooWeiXin->setPrivateKey('test');
        $baoFooWeiXin->setOptions($options);
        $baoFooWeiXin->paymentTracking();
    }

    /**
     * 測試訂單查詢訂單不存在
     */
    public function testTrackingReturnPaymentTrackingOrderDoesNotExist()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Order does not exist',
            180060
        );

        $config = [
            'private_key_bits' => 1024,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];

        $cert = '';
        $pkcsPrivate = '';
        $newKey = openssl_pkey_new($config);
        $privatekey = openssl_pkey_get_private($newKey);
        $csrKey = openssl_csr_new([], $newKey);
        $csrSign = openssl_csr_sign($csrKey, null, $newKey, 365);
        openssl_x509_export($csrSign, $cert);
        openssl_pkcs12_export($cert, $pkcsPrivate, $privatekey, 'test');

        $parseData = [
            'additional_info' => '',
            'data_type' => 'json',
            'member_id' => '122234',
            'req_reserved' => '',
            'resp_code' => '0310',
            'resp_msg' => '该笔订单【%s】不存在',
            'terminal_id' => '30411',
            'trade_date' => '20160804122617',
            'trans_id' => '201608040000004407',
            'txn_sub_type' => '03',
            'txn_type' => '20199',
            'version' => '4.0.0.0',
        ];

        $encodeStr = str_replace("\\/", "/", json_encode($parseData, true));
        $content = base64_encode($encodeStr);
        $totalLen = strlen($content);

        $data = '';
        $encrypted = '';
        $pos = 0;

        while ($pos < $totalLen){
            openssl_private_encrypt(substr($content, $pos, 117), $data, $privatekey);
            $encrypted .= bin2hex($data);
            $pos += 117;
        }

        $response = new Response();
        $response->setContent($encrypted);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $options = [
            'number' => '123456',
            'orderId' => '201608040000004412',
            'orderCreateDate' => '2016-08-04 12:25:29',
            'merchant_extra' => ['terminal_id' => '123456'],
            'rsa_private_key' => base64_encode($pkcsPrivate),
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '1.1.1.1',
            'rsa_public_key' => base64_encode($cert),
        ];

        $baoFooWeiXin = new BaoFooWeiXin();
        $baoFooWeiXin->setContainer($this->container);
        $baoFooWeiXin->setClient($this->client);
        $baoFooWeiXin->setResponse($response);
        $baoFooWeiXin->setPrivateKey('test');
        $baoFooWeiXin->setOptions($options);
        $baoFooWeiXin->paymentTracking();
    }

    /**
     * 測試訂單查詢訂單未支付
     */
    public function testTrackingReturnPaymentTrackingUnpaidOrder()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Unpaid order',
            180062
        );

        $config = [
            'private_key_bits' => 1024,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];

        $cert = '';
        $pkcsPrivate = '';
        $newKey = openssl_pkey_new($config);
        $privatekey = openssl_pkey_get_private($newKey);
        $csrKey = openssl_csr_new([], $newKey);
        $csrSign = openssl_csr_sign($csrKey, null, $newKey, 365);
        openssl_x509_export($csrSign, $cert);
        openssl_pkcs12_export($cert, $pkcsPrivate, $privatekey, 'test');

        $parseData = [
            'additional_info' => '',
            'data_type' => 'json',
            'member_id' => '122234',
            'req_reserved' => '',
            'resp_code' => '0312',
            'resp_msg' => '此笔订单未支付成功',
            'terminal_id' => '30411',
            'trade_date' => '20160804122617',
            'trans_id' => '201608040000004407',
            'txn_sub_type' => '03',
            'txn_type' => '20199',
            'version' => '4.0.0.0',
        ];

        $encodeStr = str_replace("\\/", "/", json_encode($parseData, true));
        $content = base64_encode($encodeStr);
        $totalLen = strlen($content);

        $data = '';
        $encrypted = '';
        $pos = 0;

        while ($pos < $totalLen){
            openssl_private_encrypt(substr($content, $pos, 117), $data, $privatekey);
            $encrypted .= bin2hex($data);
            $pos += 117;
        }

        $response = new Response();
        $response->setContent($encrypted);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $options = [
            'number' => '123456',
            'orderId' => '201608040000004412',
            'orderCreateDate' => '2016-08-04 12:25:29',
            'merchant_extra' => ['terminal_id' => '123456'],
            'rsa_private_key' => base64_encode($pkcsPrivate),
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '1.1.1.1',
            'rsa_public_key' => base64_encode($cert),
        ];

        $baoFooWeiXin = new BaoFooWeiXin();
        $baoFooWeiXin->setContainer($this->container);
        $baoFooWeiXin->setClient($this->client);
        $baoFooWeiXin->setResponse($response);
        $baoFooWeiXin->setPrivateKey('test');
        $baoFooWeiXin->setOptions($options);
        $baoFooWeiXin->paymentTracking();
    }

    /**
     * 測試訂單查詢訂單其他錯誤
     */
    public function testTrackingReturnPaymentTrackingError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            '风控限额拦截',
            180123
        );

        $config = [
            'private_key_bits' => 1024,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];

        $cert = '';
        $pkcsPrivate = '';
        $newKey = openssl_pkey_new($config);
        $privatekey = openssl_pkey_get_private($newKey);
        $csrKey = openssl_csr_new([], $newKey);
        $csrSign = openssl_csr_sign($csrKey, null, $newKey, 365);
        openssl_x509_export($csrSign, $cert);
        openssl_pkcs12_export($cert, $pkcsPrivate, $privatekey, 'test');

        $parseData = [
            'additional_info' => '',
            'data_type' => 'json',
            'member_id' => '122234',
            'req_reserved' => '',
            'resp_code' => '0319',
            'resp_msg' => '风控限额拦截',
            'terminal_id' => '30411',
            'trade_date' => '20160804122617',
            'trans_id' => '201608040000004407',
            'txn_sub_type' => '03',
            'txn_type' => '20199',
            'version' => '4.0.0.0',
        ];

        $encodeStr = str_replace("\\/", "/", json_encode($parseData, true));
        $content = base64_encode($encodeStr);
        $totalLen = strlen($content);

        $data = '';
        $encrypted = '';
        $pos = 0;

        while ($pos < $totalLen){
            openssl_private_encrypt(substr($content, $pos, 117), $data, $privatekey);
            $encrypted .= bin2hex($data);
            $pos += 117;
        }

        $response = new Response();
        $response->setContent($encrypted);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $options = [
            'number' => '123456',
            'orderId' => '201608040000004412',
            'orderCreateDate' => '2016-08-04 12:25:29',
            'merchant_extra' => ['terminal_id' => '123456'],
            'rsa_private_key' => base64_encode($pkcsPrivate),
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '1.1.1.1',
            'rsa_public_key' => base64_encode($cert),
        ];

        $baoFooWeiXin = new BaoFooWeiXin();
        $baoFooWeiXin->setContainer($this->container);
        $baoFooWeiXin->setClient($this->client);
        $baoFooWeiXin->setResponse($response);
        $baoFooWeiXin->setPrivateKey('test');
        $baoFooWeiXin->setOptions($options);
        $baoFooWeiXin->paymentTracking();
    }

    /**
     * 測試訂單查詢支付失敗
     */
    public function testTrackingReturnPaymentTrackingFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $config = [
            'private_key_bits' => 1024,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];

        $cert = '';
        $pkcsPrivate = '';
        $newKey = openssl_pkey_new($config);
        $privatekey = openssl_pkey_get_private($newKey);
        $csrKey = openssl_csr_new([], $newKey);
        $csrSign = openssl_csr_sign($csrKey, null, $newKey, 365);
        openssl_x509_export($csrSign, $cert);
        openssl_pkcs12_export($cert, $pkcsPrivate, $privatekey, 'test');

        $parseData = [
            'additional_info' => '',
            'data_type' => 'json',
            'member_id' => '122234',
            'req_reserved' => '',
            'resp_code' => '0314',
            'terminal_id' => '30411',
            'trade_date' => '20160804122617',
            'trans_id' => '201608040000004407',
            'txn_sub_type' => '03',
            'txn_type' => '20199',
            'version' => '4.0.0.0',
        ];

        $encodeStr = str_replace("\\/", "/", json_encode($parseData, true));
        $content = base64_encode($encodeStr);
        $totalLen = strlen($content);

        $data = '';
        $encrypted = '';
        $pos = 0;

        while ($pos < $totalLen){
            openssl_private_encrypt(substr($content, $pos, 117), $data, $privatekey);
            $encrypted .= bin2hex($data);
            $pos += 117;
        }

        $response = new Response();
        $response->setContent($encrypted);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $options = [
            'number' => '123456',
            'orderId' => '201608040000004412',
            'orderCreateDate' => '2016-08-04 12:25:29',
            'merchant_extra' => ['terminal_id' => '123456'],
            'rsa_private_key' => base64_encode($pkcsPrivate),
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '1.1.1.1',
            'rsa_public_key' => base64_encode($cert),
        ];

        $baoFooWeiXin = new BaoFooWeiXin();
        $baoFooWeiXin->setContainer($this->container);
        $baoFooWeiXin->setClient($this->client);
        $baoFooWeiXin->setResponse($response);
        $baoFooWeiXin->setPrivateKey('test');
        $baoFooWeiXin->setOptions($options);
        $baoFooWeiXin->paymentTracking();
    }

    /**
     * 測試訂單查詢未返回金額
     */
    public function testTrackingReturnWithoutSuccAmt()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No tracking return parameter specified',
            180139
        );

        $config = [
            'private_key_bits' => 1024,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];

        $cert = '';
        $pkcsPrivate = '';
        $newKey = openssl_pkey_new($config);
        $privatekey = openssl_pkey_get_private($newKey);
        $csrKey = openssl_csr_new([], $newKey);
        $csrSign = openssl_csr_sign($csrKey, null, $newKey, 365);
        openssl_x509_export($csrSign, $cert);
        openssl_pkcs12_export($cert, $pkcsPrivate, $privatekey, 'test');

        $parseData = [
            'additional_info' => '',
            'data_type' => 'json',
            'member_id' => '122234',
            'req_reserved' => '',
            'resp_code' => '0000',
            'resp_msg' => '支付成功',
            'terminal_id' => '30411',
            'trade_date' => '20160804122617',
            'trans_id' => '201608040000004407',
            'txn_sub_type' => '03',
            'txn_type' => '20199',
            'version' => '4.0.0.0',
        ];

        $encodeStr = str_replace("\\/", "/", json_encode($parseData, true));
        $content = base64_encode($encodeStr);
        $totalLen = strlen($content);

        $data = '';
        $encrypted = '';
        $pos = 0;

        while ($pos < $totalLen){
            openssl_private_encrypt(substr($content, $pos, 117), $data, $privatekey);
            $encrypted .= bin2hex($data);
            $pos += 117;
        }

        $response = new Response();
        $response->setContent($encrypted);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $options = [
            'number' => '123456',
            'orderId' => '201608040000004412',
            'orderCreateDate' => '2016-08-04 12:25:29',
            'merchant_extra' => ['terminal_id' => '123456'],
            'rsa_private_key' => base64_encode($pkcsPrivate),
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '1.1.1.1',
            'rsa_public_key' => base64_encode($cert),
            'amount' => '0.02',
        ];

        $baoFooWeiXin = new BaoFooWeiXin();
        $baoFooWeiXin->setContainer($this->container);
        $baoFooWeiXin->setClient($this->client);
        $baoFooWeiXin->setResponse($response);
        $baoFooWeiXin->setPrivateKey('test');
        $baoFooWeiXin->setOptions($options);
        $baoFooWeiXin->paymentTracking();
    }

    /**
     * 測試訂單查詢返回金額錯誤
     */
    public function testTrackingReturnOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $config = [
            'private_key_bits' => 1024,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];

        $cert = '';
        $pkcsPrivate = '';
        $newKey = openssl_pkey_new($config);
        $privatekey = openssl_pkey_get_private($newKey);
        $csrKey = openssl_csr_new([], $newKey);
        $csrSign = openssl_csr_sign($csrKey, null, $newKey, 365);
        openssl_x509_export($csrSign, $cert);
        openssl_pkcs12_export($cert, $pkcsPrivate, $privatekey, 'test');

        $parseData = [
            'additional_info' => '',
            'data_type' => 'json',
            'member_id' => '122234',
            'req_reserved' => '',
            'resp_code' => '0000',
            'resp_msg' => '支付成功',
            'succ_amt' => '1',
            'terminal_id' => '30411',
            'trade_date' => '20160804122617',
            'trans_id' => '201608040000004407',
            'txn_sub_type' => '03',
            'txn_type' => '20199',
            'version' => '4.0.0.0',
        ];

        $encodeStr = str_replace("\\/", "/", json_encode($parseData, true));
        $content = base64_encode($encodeStr);
        $totalLen = strlen($content);

        $data = '';
        $encrypted = '';
        $pos = 0;

        while ($pos < $totalLen){
            openssl_private_encrypt(substr($content, $pos, 117), $data, $privatekey);
            $encrypted .= bin2hex($data);
            $pos += 117;
        }

        $response = new Response();
        $response->setContent($encrypted);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $options = [
            'number' => '123456',
            'orderId' => '201608040000004412',
            'orderCreateDate' => '2016-08-04 12:25:29',
            'merchant_extra' => ['terminal_id' => '123456'],
            'rsa_private_key' => base64_encode($pkcsPrivate),
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '1.1.1.1',
            'rsa_public_key' => base64_encode($cert),
            'amount' => '0.02',
        ];

        $baoFooWeiXin = new BaoFooWeiXin();
        $baoFooWeiXin->setContainer($this->container);
        $baoFooWeiXin->setClient($this->client);
        $baoFooWeiXin->setResponse($response);
        $baoFooWeiXin->setPrivateKey('test');
        $baoFooWeiXin->setOptions($options);
        $baoFooWeiXin->paymentTracking();
    }

    /**
     * 測試訂單查詢成功
     */
    public function testTrackingSuccess()
    {
        $config = [
            'private_key_bits' => 1024,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];

        $cert = '';
        $pkcsPrivate = '';
        $newKey = openssl_pkey_new($config);
        $privatekey = openssl_pkey_get_private($newKey);
        $csrKey = openssl_csr_new([], $newKey);
        $csrSign = openssl_csr_sign($csrKey, null, $newKey, 365);
        openssl_x509_export($csrSign, $cert);
        openssl_pkcs12_export($cert, $pkcsPrivate, $privatekey, 'test');

        $parseData = [
            'additional_info' => '',
            'data_type' => 'json',
            'member_id' => '122234',
            'req_reserved' => '',
            'resp_code' => '0000',
            'resp_msg' => '支付成功',
            'succ_amt' => '1',
            'terminal_id' => '30411',
            'trade_date' => '20160804122617',
            'trans_id' => '201608040000004407',
            'txn_sub_type' => '03',
            'txn_type' => '20199',
            'version' => '4.0.0.0',
        ];

        $encodeStr = str_replace("\\/", "/", json_encode($parseData, true));
        $content = base64_encode($encodeStr);
        $totalLen = strlen($content);

        $data = '';
        $encrypted = '';
        $pos = 0;

        while ($pos < $totalLen){
            openssl_private_encrypt(substr($content, $pos, 117), $data, $privatekey);
            $encrypted .= bin2hex($data);
            $pos += 117;
        }

        $response = new Response();
        $response->setContent($encrypted);
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('content-type:text/html;');

        $options = [
            'number' => '123456',
            'orderId' => '201608040000004412',
            'orderCreateDate' => '2016-08-04 12:25:29',
            'merchant_extra' => ['terminal_id' => '123456'],
            'rsa_private_key' => base64_encode($pkcsPrivate),
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'verify_url' => '1.1.1.1',
            'rsa_public_key' => base64_encode($cert),
            'amount' => '0.01',
        ];

        $baoFooWeiXin = new BaoFooWeiXin();
        $baoFooWeiXin->setContainer($this->container);
        $baoFooWeiXin->setClient($this->client);
        $baoFooWeiXin->setResponse($response);
        $baoFooWeiXin->setPrivateKey('test');
        $baoFooWeiXin->setOptions($options);
        $baoFooWeiXin->paymentTracking();
    }
}

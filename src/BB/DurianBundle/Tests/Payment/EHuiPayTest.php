<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Payment\EHuiPay;
use BB\DurianBundle\Tests\Functional\WebTestCase;
use Buzz\Message\Response;

class EHuiPayTest extends WebTestCase
{
    /**
     * 私鑰
     *
     * @var string
     */
    private $privateKey;

    /**
     * 公鑰
     *
     * @var string
     */
    private $publicKey;

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

        $res = openssl_pkey_new();

        $privateKey = '';
        openssl_pkey_export($res, $privateKey);
        $this->privateKey = base64_encode($privateKey);

        $publicKey = openssl_pkey_get_details($res);

        $this->publicKey = base64_encode($publicKey['key']);

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

        $sourceData = ['number' => ''];

        $eHuiPay = new EHuiPay();
        $eHuiPay->setOptions($sourceData);
        $eHuiPay->getVerifyData();
    }

    /**
     * 測試支付時帶入不支援銀行
     */
    public function testPayUnsupportedPaymentVendor()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $options = [
            'number' => '180223555071',
            'notify_url' => 'http://pay.in-action.tw/',
            'orderId' => '201803080000010482',
            'amount' => '10.00',
            'paymentVendorId' => '9999',
            'orderCreateDate' => '2018-03-07 12:26:41',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'ip' => '192.168.110.1',
            'merchant_extra' => ['AppId' => '2448564ebb7a4f838ecea89000896b4d'],
        ];

        $eHuiPay = new EHuiPay();
        $eHuiPay->setOptions($options);
        $eHuiPay->getVerifyData();
    }

    /**
     * 測試加密簽名參數失敗
     */
    public function testGetEncodeGenerateSignatureFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Generate signature failure',
            180144
        );

        $config = [
            'private_key_bits' => 384,
            'private_key_type' => OPENSSL_KEYTYPE_DH,
        ];
        $res = openssl_pkey_new($config);

        $privateKey = '';
        openssl_pkey_export($res, $privateKey);
        $privateKey = base64_encode($privateKey);

        $options = [
            'number' => '180223555071',
            'notify_url' => 'http://pay.in-action.tw/',
            'orderId' => '201803080000010482',
            'amount' => '10.00',
            'paymentVendorId' => '1103',
            'orderCreateDate' => '2018-03-07 12:26:41',
            'rsa_private_key' => $privateKey,
            'ip' => '192.168.110.1',
            'merchant_extra' => ['AppId' => '2448564ebb7a4f838ecea89000896b4d'],
        ];

        $eHuiPay = new EHuiPay();
        $eHuiPay->setOptions($options);
        $eHuiPay->getVerifyData();
    }

    /**
     * 測試沒有BankCode支付
     */
    public function testWithoutBankCodePay()
    {
        $options = [
            'number' => '180223555071',
            'notify_url' => 'http://pay.in-action.tw/',
            'orderId' => '201803080000010482',
            'amount' => '10.00',
            'paymentVendorId' => '1103',
            'orderCreateDate' => '2018-03-07 12:26:41',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'ip' => '192.168.110.1',
            'merchant_extra' => ['AppId' => '2448564ebb7a4f838ecea89000896b4d'],
        ];

        $eHuiPay = new EHuiPay();
        $eHuiPay->setOptions($options);
        $verifyData = $eHuiPay->getVerifyData();

        $this->assertEquals('1.0', $verifyData['Version']);
        $this->assertEquals('180223555071', $verifyData['MchId']);
        $this->assertEquals('201803080000010482', $verifyData['MchOrderNo']);
        $this->assertEquals('60', $verifyData['PayType']);
        $this->assertEquals('', $verifyData['BankCode']);
        $this->assertEquals('10.00', $verifyData['Amount']);
        $this->assertEquals('20180307122641', $verifyData['OrderTime']);
        $this->assertEquals('192.168.110.1', $verifyData['ClientIp']);
        $this->assertEquals('http://pay.in-action.tw/', $verifyData['NotifyUrl']);

        foreach ($verifyData as $key => $value) {
            if (($key != 'BankCode') && ($key != 'sign')) {
                $encodeData[$key] = $value;
            }
        }

        $encodeStr = implode('|', $encodeData);

        $encodeStr .= '|' . '2448564ebb7a4f838ecea89000896b4d';

        openssl_sign($encodeStr, $veirfySign, $eHuiPay->getRsaPrivateKey(), OPENSSL_ALGO_SHA256);
        $this->assertEquals(base64_encode($veirfySign), $verifyData['sign']);
    }

    /**
     * 測試有BankCode支付
     */
    public function testWithBankCodePay()
    {
        $options = [
            'number' => '180223555071',
            'notify_url' => 'http://pay.in-action.tw/',
            'orderId' => '201803080000010482',
            'amount' => '10.00',
            'paymentVendorId' => '1107',
            'orderCreateDate' => '2018-03-07 12:26:41',
            'rsa_private_key' => $this->privateKey,
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
            'ip' => '192.168.110.1',
            'merchant_extra' => ['AppId' => '2448564ebb7a4f838ecea89000896b4d'],
        ];

        $eHuiPay = new EHuiPay();
        $eHuiPay->setOptions($options);
        $verifyData = $eHuiPay->getVerifyData();

        $this->assertEquals('1.0', $verifyData['Version']);
        $this->assertEquals('180223555071', $verifyData['MchId']);
        $this->assertEquals('201803080000010482', $verifyData['MchOrderNo']);
        $this->assertEquals('50', $verifyData['PayType']);
        $this->assertEquals('JDPAY', $verifyData['BankCode']);
        $this->assertEquals('10.00', $verifyData['Amount']);
        $this->assertEquals('20180307122641', $verifyData['OrderTime']);
        $this->assertEquals('192.168.110.1', $verifyData['ClientIp']);
        $this->assertEquals('http://pay.in-action.tw/', $verifyData['NotifyUrl']);

        foreach ($verifyData as $key => $value) {
            if (($key != 'BankCode') && ($key != 'sign')) {
                $encodeData[$key] = $value;
            }
        }

        $encodeStr = implode('|', $encodeData);

        $encodeStr .= '|' . '2448564ebb7a4f838ecea89000896b4d';

        openssl_sign($encodeStr, $veirfySign, $eHuiPay->getRsaPrivateKey(), OPENSSL_ALGO_SHA256);
        $this->assertEquals(base64_encode($veirfySign), $verifyData['sign']);
    }

    /**
     * 測試支付異步返回返回未指定返回參數
     */
    public function testReturnWithNoReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $eHuiPay = new EHuiPay();
        $eHuiPay->verifyOrderPayment([]);
    }

    /**
     * 測試支付異步返回通知缺少sign
     */
    public function testReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $returnData = [
            'Version' => '1.0',
            'MchId' => '180223555071',
            'MchOrderNo' => '201803080000010476',
            'OrderId' => '1803081803081927324990144',
            'PayAmount' => '2.01',
            'PayResult' => 'SUCCESS',
            'PayMessage' => '银联扫码-支付成功',
            'rsa_public_key' => $this->publicKey,
            'merchant_extra' => ['AppId' => '2448564ebb7a4f838ecea89000896b4d'],
        ];

        $entry = [
            'id' => '201803080000010476',
            'amount' => '2.01',
        ];

        $eHuiPay = new EHuiPay();
        $eHuiPay->setOptions($returnData);
        $eHuiPay->verifyOrderPayment($entry);
    }

    /**
     * 測試支付異步返回通知驗簽時公鑰為空
     */
    public function testReturnGetRsaPublicKeyEmpty()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Rsa public key is empty',
            180095
        );

        $returnData = [
            'Version' => '1.0',
            'MchId' => '180223555071',
            'MchOrderNo' => '201803080000010476',
            'OrderId' => '1803081803081927324990144',
            'PayAmount' => '2.01',
            'PayResult' => 'SUCCESS',
            'PayMessage' => '银联扫码-支付成功',
            'rsa_public_key' => '',
            'merchant_extra' => ['AppId' => '2448564ebb7a4f838ecea89000896b4d'],
            'Sign' => 'test',
        ];

        $entry = [
            'id' => '201803080000010476',
            'amount' => '2.01',
        ];

        $eHuiPay = new EHuiPay();
        $eHuiPay->setOptions($returnData);
        $eHuiPay->verifyOrderPayment($entry);
    }

    /**
     * 測試支付異步返回通知驗簽時取得公鑰失敗
     */
    public function testReturnGetRsaPublicKeyFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Get rsa public key failure',
            180096
        );

        $returnData = [
            'Version' => '1.0',
            'MchId' => '180223555071',
            'MchOrderNo' => '201803080000010476',
            'OrderId' => '1803081803081927324990144',
            'PayAmount' => '2.01',
            'PayResult' => 'SUCCESS',
            'PayMessage' => '银联扫码-支付成功',
            'rsa_public_key' => '123456',
            'merchant_extra' => ['AppId' => '2448564ebb7a4f838ecea89000896b4d'],
            'Sign' => 'test',
        ];

        $entry = [
            'id' => '201803080000010476',
            'amount' => '2.01',
        ];


        $eHuiPay = new EHuiPay();
        $eHuiPay->setOptions($returnData);
        $eHuiPay->verifyOrderPayment($entry);
    }


    /**
     * 測試支付異步返回通知sign錯誤
     */
    public function testReturnSignError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $returnData = [
            'Version' => '1.0',
            'MchId' => '180223555071',
            'MchOrderNo' => '201803080000010476',
            'OrderId' => '1803081803081927324990144',
            'PayAmount' => '2.01',
            'PayResult' => 'SUCCESS',
            'PayMessage' => '银联扫码-支付成功',
            'rsa_public_key' => $this->publicKey,
            'merchant_extra' => ['AppId' => '2448564ebb7a4f838ecea89000896b4d'],
            'Sign' => 'test',
        ];

        $entry = [
            'id' => '201803080000010476',
            'amount' => '2.01',
        ];

        $eHuiPay = new EHuiPay();
        $eHuiPay->setOptions($returnData);
        $eHuiPay->verifyOrderPayment($entry);
    }

    /**
     * 測試支付異步返回支付失敗
     */
    public function testReturnFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $encodeData = [
            'Version' => '1.0',
            'MchId' => '180223555071',
            'MchOrderNo' => '201803080000010476',
            'OrderId' => '1803081803081927324990144',
            'PayAmount' => '2.01',
            'PayResult' => 'FAIL',
            'PayMessage' => '银联扫码-支付成功',
        ];

        $encodeStr = implode('|', $encodeData);

        $encodeStr .= '|' . '2448564ebb7a4f838ecea89000896b4d';

        $sign = '';
        openssl_sign($encodeStr, $sign, base64_decode($this->privateKey), OPENSSL_ALGO_SHA256);

        $returnData = [
            'Version' => '1.0',
            'MchId' => '180223555071',
            'MchOrderNo' => '201803080000010476',
            'OrderId' => '1803081803081927324990144',
            'PayAmount' => '2.01',
            'PayResult' => 'FAIL',
            'PayMessage' => '银联扫码-支付成功',
            'rsa_public_key' => $this->publicKey,
            'merchant_extra' => ['AppId' => '2448564ebb7a4f838ecea89000896b4d'],
            'Sign' => base64_encode($sign),
        ];

        $entry = [
            'id' => '201803080000010476',
            'amount' => '2.01',
        ];

        $eHuiPay = new EHuiPay();
        $eHuiPay->setOptions($returnData);
        $eHuiPay->verifyOrderPayment($entry);
    }

    /**
     * 測試支付異步返回通知訂單號錯誤
     */
    public function testReturnOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $encodeData = [
            'Version' => '1.0',
            'MchId' => '180223555071',
            'MchOrderNo' => '201803080000010476',
            'OrderId' => '1803081803081927324990144',
            'PayAmount' => '2.01',
            'PayResult' => 'SUCCESS',
            'PayMessage' => '银联扫码-支付成功',
        ];

        $encodeStr = implode('|', $encodeData);

        $encodeStr .= '|' . '2448564ebb7a4f838ecea89000896b4d';

        $sign = '';
        openssl_sign($encodeStr, $sign, base64_decode($this->privateKey), OPENSSL_ALGO_SHA256);

        $returnData = [
            'Version' => '1.0',
            'MchId' => '180223555071',
            'MchOrderNo' => '201803080000010476',
            'OrderId' => '1803081803081927324990144',
            'PayAmount' => '2.01',
            'PayResult' => 'SUCCESS',
            'PayMessage' => '银联扫码-支付成功',
            'rsa_public_key' => $this->publicKey,
            'merchant_extra' => ['AppId' => '2448564ebb7a4f838ecea89000896b4d'],
            'Sign' => base64_encode($sign),
        ];

        $entry = [
            'id' => '201803080000010400',
            'amount' => '2.01',
        ];

        $eHuiPay = new EHuiPay();
        $eHuiPay->setOptions($returnData);
        $eHuiPay->verifyOrderPayment($entry);
    }

    /**
     * 測試支付異步返回通知金額錯誤
     */
    public function testReturnOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $encodeData = [
            'Version' => '1.0',
            'MchId' => '180223555071',
            'MchOrderNo' => '201803080000010476',
            'OrderId' => '1803081803081927324990144',
            'PayAmount' => '2.01',
            'PayResult' => 'SUCCESS',
            'PayMessage' => '银联扫码-支付成功',
        ];

        $encodeStr = implode('|', $encodeData);

        $encodeStr .= '|' . '2448564ebb7a4f838ecea89000896b4d';

        $sign = '';
        openssl_sign($encodeStr, $sign, base64_decode($this->privateKey), OPENSSL_ALGO_SHA256);

        $returnData = [
            'Version' => '1.0',
            'MchId' => '180223555071',
            'MchOrderNo' => '201803080000010476',
            'OrderId' => '1803081803081927324990144',
            'PayAmount' => '2.01',
            'PayResult' => 'SUCCESS',
            'PayMessage' => '银联扫码-支付成功',
            'rsa_public_key' => $this->publicKey,
            'merchant_extra' => ['AppId' => '2448564ebb7a4f838ecea89000896b4d'],
            'Sign' => base64_encode($sign),
        ];

        $entry = [
            'id' => '201803080000010476',
            'amount' => '2.0',
        ];

        $eHuiPay = new EHuiPay();
        $eHuiPay->setOptions($returnData);
        $eHuiPay->verifyOrderPayment($entry);
    }

    /**
     * 測試支付異步返回成功
     */
    public function testReturnSuccess()
    {
        $encodeData = [
            'Version' => '1.0',
            'MchId' => '180223555071',
            'MchOrderNo' => '201803080000010476',
            'OrderId' => '1803081803081927324990144',
            'PayAmount' => '2.01',
            'PayResult' => 'SUCCESS',
            'PayMessage' => '银联扫码-支付成功',
        ];

        $encodeStr = implode('|', $encodeData);

        $encodeStr .= '|' . '2448564ebb7a4f838ecea89000896b4d';

        $sign = '';
        openssl_sign($encodeStr, $sign, base64_decode($this->privateKey), OPENSSL_ALGO_SHA256);

        $returnData = [
            'Version' => '1.0',
            'MchId' => '180223555071',
            'MchOrderNo' => '201803080000010476',
            'OrderId' => '1803081803081927324990144',
            'PayAmount' => '2.01',
            'PayResult' => 'SUCCESS',
            'PayMessage' => '银联扫码-支付成功',
            'rsa_public_key' => $this->publicKey,
            'merchant_extra' => ['AppId' => '2448564ebb7a4f838ecea89000896b4d'],
            'Sign' => base64_encode($sign),
        ];

        $entry = [
            'id' => '201803080000010476',
            'amount' => '2.01',
        ];

        $eHuiPay = new EHuiPay();
        $eHuiPay->setOptions($returnData);
        $eHuiPay->verifyOrderPayment($entry);

        $this->assertEquals('ok', $eHuiPay->getMsg());
    }
}

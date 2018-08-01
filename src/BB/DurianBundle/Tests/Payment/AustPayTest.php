<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\AustPay;
use Buzz\Message\Response;

class AustPayTest extends DurianTestCase
{
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

        $austPay = new AustPay();
        $austPay->getVerifyData();
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

        $austPay = new AustPay();
        $austPay->setPrivateKey('test');
        $austPay->setOptions($sourceData);
        $austPay->getVerifyData();
    }

    /**
     * 測試支付時代入支付平台不支援的銀行
     */
    public function testPayUnsupportedPaymentVendor()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $sourceData = [
            'number' => '135325',
            'paymentVendorId' => '7',
            'amount' => '1.00',
            'orderId' => '201705090000002599',
            'orderCreateDate' => '2017-03-08 15:45:55',
            'username' => 'php1test',
            'ip' => '123.123.123.123',
            'notify_url' => 'http://two123.comxa.com/',
            'verify_url' => 'payment.http.test',
            'verify_ip' => ['172.26.54.42', '172.26.54.41'],
        ];

        $austPay = new AustPay();
        $austPay->setPrivateKey('test');
        $austPay->setOptions($sourceData);
        $austPay->getVerifyData();
    }

    /**
     * 測試網銀支付
     */
    public function testBankPay()
    {
        $sourceData = [
            'number' => 'MIDTES1',
            'paymentVendorId' => '1',
            'amount' => '0.01',
            'orderId' => '201709210000007070',
            'notify_url' => 'http://3.1415926387/',
            'merchant_extra' => ['siteid' => '55688'],
        ];

        $austPay = new AustPay();
        $austPay->setPrivateKey('test');
        $austPay->setOptions($sourceData);
        $verifyData = $austPay->getVerifyData();

        $this->assertEquals('MIDTES1', $verifyData['merchantid']);
        $this->assertEquals('201709210000007070', $verifyData['order_id']);
        $this->assertEquals('0.01', $verifyData['Amount']);
        $this->assertEquals('http://3.1415926387/', $verifyData['return_url']);
        $this->assertEquals('http://3.1415926387/', $verifyData['notify_url']);
        $this->assertEquals('9', $verifyData['bankcode']);
        $this->assertEquals('3', $verifyData['type']);
        $this->assertEquals('b9da52c8c149690ee079eb2a86064ddd', $verifyData['security_code']);
    }

    /**
     * 測試微信支付
     */
    public function testWeiXinPay()
    {
        $sourceData = [
            'number' => 'MIDTES1',
            'paymentVendorId' => '1090',
            'amount' => '0.01',
            'orderId' => '201709210000007070',
            'notify_url' => 'http://3.1415926387/',
            'merchant_extra' => ['siteid' => '55688'],
        ];

        $austPay = new AustPay();
        $austPay->setPrivateKey('test');
        $austPay->setOptions($sourceData);
        $verifyData = $austPay->getVerifyData();

        $this->assertEquals('MIDTES1', $verifyData['merchantid']);
        $this->assertEquals('201709210000007070', $verifyData['order_id']);
        $this->assertEquals('0.01', $verifyData['Amount']);
        $this->assertEquals('http://3.1415926387/', $verifyData['return_url']);
        $this->assertEquals('http://3.1415926387/', $verifyData['notify_url']);
        $this->assertEquals('', $verifyData['bankcode']);
        $this->assertEquals('2', $verifyData['type']);
        $this->assertEquals('b9da52c8c149690ee079eb2a86064ddd', $verifyData['security_code']);
    }

    /**
     * 測試支付寶支付
     */
    public function testAliPay()
    {
        $sourceData = [
            'number' => 'MIDTES1',
            'paymentVendorId' => '1092',
            'amount' => '0.01',
            'orderId' => '201709210000007070',
            'notify_url' => 'http://3.1415926387/',
            'merchant_extra' => ['siteid' => '55688'],
        ];

        $austPay = new AustPay();
        $austPay->setPrivateKey('test');
        $austPay->setOptions($sourceData);
        $verifyData = $austPay->getVerifyData();

        $this->assertEquals('MIDTES1', $verifyData['merchantid']);
        $this->assertEquals('201709210000007070', $verifyData['order_id']);
        $this->assertEquals('0.01', $verifyData['Amount']);
        $this->assertEquals('http://3.1415926387/', $verifyData['return_url']);
        $this->assertEquals('http://3.1415926387/', $verifyData['notify_url']);
        $this->assertEquals('', $verifyData['bankcode']);
        $this->assertEquals('1', $verifyData['type']);
        $this->assertEquals('b9da52c8c149690ee079eb2a86064ddd', $verifyData['security_code']);
    }

    /**
     * 測試QQ支付
     */
    public function testQQPay()
    {
        $sourceData = [
            'number' => 'MIDTES1',
            'paymentVendorId' => '1103',
            'amount' => '0.01',
            'orderId' => '201709210000007070',
            'notify_url' => 'http://3.1415926387/',
            'merchant_extra' => ['siteid' => '55688'],
        ];

        $austPay = new AustPay();
        $austPay->setPrivateKey('test');
        $austPay->setOptions($sourceData);
        $verifyData = $austPay->getVerifyData();

        $this->assertEquals('MIDTES1', $verifyData['merchantid']);
        $this->assertEquals('201709210000007070', $verifyData['order_id']);
        $this->assertEquals('0.01', $verifyData['Amount']);
        $this->assertEquals('http://3.1415926387/', $verifyData['return_url']);
        $this->assertEquals('http://3.1415926387/', $verifyData['notify_url']);
        $this->assertEquals('', $verifyData['bankcode']);
        $this->assertEquals('4', $verifyData['type']);
        $this->assertEquals('b9da52c8c149690ee079eb2a86064ddd', $verifyData['security_code']);
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

        $austPay = new AustPay();
        $austPay->verifyOrderPayment([]);
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

        $austPay = new AustPay();
        $austPay->setPrivateKey('test');
        $austPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時支付平台缺少回傳add_string
     */
    public function testReturnWithoutAddString()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $sourceData = [
            'merchantid' => 'MIDTES1',
            'siteid' => '2000269',
            'successcode' => 'ok',
            'oid' => '201709210000007070',
            'system_id' => '20170921-MIDTES1-152114_894_20983690',
            'order_amount' => '0.01',
            'add_string2' => 'b222c3e875c589f96bae1f21087016ce8faa596242c6e722426d2b9c4b980e49',
            'version' => '2.0',
        ];

        $austPay = new AustPay();
        $austPay->setPrivateKey('test');
        $austPay->setOptions($sourceData);
        $austPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時sign簽名驗證錯誤
     */
    public function testReturnSignatureVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $sourceData = [
            'merchantid' => 'MIDTES1',
            'siteid' => '2000269',
            'successcode' => 'ok',
            'oid' => '201709210000007070',
            'system_id' => '20170921-MIDTES1-152114_894_20983690',
            'order_amount' => '0.01',
            'add_string' => '3b15a48b02babcf9e372bbed4b1ffc62',
            'add_string2' => 'b222c3e875c589f96bae1f21087016ce8faa596242c6e722426d2b9c4b980e49',
            'version' => '2.0',
        ];

        $austPay = new AustPay();
        $austPay->setPrivateKey('test');
        $austPay->setOptions($sourceData);
        $austPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回結果為支付失敗
     */
    public function testReturnPaymentFailure()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Payment failure',
            180035
        );

        $sourceData = [
            'merchantid' => 'MIDTES1',
            'siteid' => '2000269',
            'successcode' => 'fail',
            'oid' => '201709210000007070',
            'system_id' => '20170921-MIDTES1-152114_894_20983690',
            'order_amount' => '0.01',
            'add_string' => '31a142ff5e249b4be7fd759e3237f484',
            'add_string2' => 'b222c3e875c589f96bae1f21087016ce8faa596242c6e722426d2b9c4b980e49',
            'version' => '2.0',
        ];

        $austPay = new AustPay();
        $austPay->setPrivateKey('test');
        $austPay->setOptions($sourceData);
        $austPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回結果為訂單編號錯誤
     */
    public function testReturnOrderIdFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $sourceData = [
            'merchantid' => 'MIDTES1',
            'siteid' => '2000269',
            'successcode' => 'ok',
            'oid' => '201709210000007070',
            'system_id' => '20170921-MIDTES1-152114_894_20983690',
            'order_amount' => '0.01',
            'add_string' => 'eedb04a6b534c8a5496c0a33ee81d7d5',
            'add_string2' => 'b222c3e875c589f96bae1f21087016ce8faa596242c6e722426d2b9c4b980e49',
            'version' => '2.0',
        ];

        $entry = ['id' => '201709210000007777'];

        $austPay = new AustPay();
        $austPay->setPrivateKey('test');
        $austPay->setOptions($sourceData);
        $austPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果為訂單金額錯誤
     */
    public function testReturnAmountFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $sourceData = [
            'merchantid' => 'MIDTES1',
            'siteid' => '2000269',
            'successcode' => 'ok',
            'oid' => '201709210000007070',
            'system_id' => '20170921-MIDTES1-152114_894_20983690',
            'order_amount' => '0.01',
            'add_string' => 'eedb04a6b534c8a5496c0a33ee81d7d5',
            'add_string2' => 'b222c3e875c589f96bae1f21087016ce8faa596242c6e722426d2b9c4b980e49',
            'version' => '2.0',
        ];

        $entry = [
            'id' => '201709210000007070',
            'amount' => '1.00',
        ];

        $austPay = new AustPay();
        $austPay->setPrivateKey('test');
        $austPay->setOptions($sourceData);
        $austPay->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testPaySuccess()
    {
        $sourceData = [
            'merchantid' => 'MIDTES1',
            'siteid' => '2000269',
            'successcode' => 'ok',
            'oid' => '201709210000007070',
            'system_id' => '20170921-MIDTES1-152114_894_20983690',
            'order_amount' => '0.01',
            'add_string' => 'eedb04a6b534c8a5496c0a33ee81d7d5',
            'add_string2' => 'b222c3e875c589f96bae1f21087016ce8faa596242c6e722426d2b9c4b980e49',
            'version' => '2.0',
        ];

        $entry = [
            'id' => '201709210000007070',
            'amount' => '0.01',
        ];

        $austPay = new AustPay();
        $austPay->setPrivateKey('test');
        $austPay->setOptions($sourceData);
        $austPay->verifyOrderPayment($entry);

        $this->assertEquals('<result>yes</result>', $austPay->getMsg());
    }
}

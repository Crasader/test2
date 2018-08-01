<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\YiHeXin;

class YiHeXinTest extends DurianTestCase
{
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

        $yiHeXin = new YiHeXin();
        $yiHeXin->setPrivateKey('test');
        $yiHeXin->setOptions($sourceData);
        $yiHeXin->getVerifyData();
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
            'number' => '2381',
            'paymentVendorId' => '20',
            'amount' => '2.00',
            'orderId' => '2017121100000023819',
            'notify_url' => 'http://two123.comxa.com/',
        ];

        $yiHeXin = new YiHeXin();
        $yiHeXin->setPrivateKey('test');
        $yiHeXin->setOptions($sourceData);
        $yiHeXin->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $sourceData = [
            'number' => '2381',
            'paymentVendorId' => '1',
            'amount' => '2.00',
            'orderId' => '2017121100000023819',
            'notify_url' => 'http://two123.comxa.com/',
        ];

        $yiHeXin = new YiHeXin();
        $yiHeXin->setPrivateKey('test');
        $yiHeXin->setOptions($sourceData);
        $encodeData = $yiHeXin->getVerifyData();

        $this->assertEquals($sourceData['number'], $encodeData['userid']);
        $this->assertEquals($sourceData['orderId'], $encodeData['orderid']);
        $this->assertSame('2.00', $encodeData['money']);
        $this->assertEquals($sourceData['notify_url'], $encodeData['url']);
        $this->assertEquals('', $encodeData['aurl']);
        $this->assertEquals('1002', $encodeData['bankid']);
        $this->assertEquals('091ece55fa61106b2ac7ce847dad5d40', $encodeData['sign']);
        $this->assertEquals('19b4e83070ff2159fc251cc2add66039', $encodeData['sign2']);
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

        $yiHeXin = new YiHeXin();
        $yiHeXin->verifyOrderPayment([]);
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

        $yiHeXin = new YiHeXin();
        $yiHeXin->setPrivateKey('test');
        $yiHeXin->verifyOrderPayment([]);
    }

    /**
     * 測試返回時支付平台缺少回傳sign
     */
    public function testReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $sourceData = [
            'returncode' => '1',
            'userid' => '2381',
            'orderid' => '2017121100000023819',
            'money' => '2',
        ];

        $yiHeXin = new YiHeXin();
        $yiHeXin->setPrivateKey('test');
        $yiHeXin->setOptions($sourceData);
        $yiHeXin->verifyOrderPayment([]);
    }

    /**
     * 測試返回時支付平台缺少回傳sign2
     */
    public function testReturnWithoutSign2()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $sourceData = [
            'returncode' => '1',
            'userid' => '2381',
            'orderid' => '2017121100000023819',
            'money' => '2',
            'sign' => 'ffead463c000b6a4c695166142845747',
        ];

        $yiHeXin = new YiHeXin();
        $yiHeXin->setPrivateKey('test');
        $yiHeXin->setOptions($sourceData);
        $yiHeXin->verifyOrderPayment([]);
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
            'returncode' => '1',
            'userid' => '2381',
            'orderid' => '2017121100000023819',
            'money' => '2',
            'sign' => '0dbd1f331672800100fb0092418614ff',
            'sign2' => '0dbd1f331672800100fb0092418614ff',
        ];

        $yiHeXin = new YiHeXin();
        $yiHeXin->setPrivateKey('test');
        $yiHeXin->setOptions($sourceData);
        $yiHeXin->verifyOrderPayment([]);
    }

    /**
     * 測試返回時sign2簽名驗證錯誤
     */
    public function testReturnSignature2VerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $sourceData = [
            'returncode' => '1',
            'userid' => '2381',
            'orderid' => '2017121100000023819',
            'money' => '2',
            'sign' => '9df366c45c20b7ec40a1e9bf83075877',
            'sign2' => '0dbd1f331672800100fb0092418614ff',
        ];

        $yiHeXin = new YiHeXin();
        $yiHeXin->setPrivateKey('test');
        $yiHeXin->setOptions($sourceData);
        $yiHeXin->verifyOrderPayment([]);
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
            'returncode' => '0',
            'userid' => '2381',
            'orderid' => '2017121100000023819',
            'money' => '2',
            'sign' => 'd84ef23c2d884a3fcc5bc3df955f07bc',
            'sign2' => '53ca49f793ae3b0ef0653542db5427c4',
        ];

        $yiHeXin = new YiHeXin();
        $yiHeXin->setPrivateKey('test');
        $yiHeXin->setOptions($sourceData);
        $yiHeXin->verifyOrderPayment([]);
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
            'returncode' => '1',
            'userid' => '2381',
            'orderid' => '2017121100000023819',
            'money' => '2',
            'sign' => '9df366c45c20b7ec40a1e9bf83075877',
            'sign2' => '9543f041474285a43762f11216d39119',
        ];

        $entry = ['id' => '201611150000000241'];

        $yiHeXin = new YiHeXin();
        $yiHeXin->setPrivateKey('test');
        $yiHeXin->setOptions($sourceData);
        $yiHeXin->verifyOrderPayment($entry);
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
            'returncode' => '1',
            'userid' => '2381',
            'orderid' => '2017121100000023819',
            'money' => '2',
            'sign' => '9df366c45c20b7ec40a1e9bf83075877',
            'sign2' => '9543f041474285a43762f11216d39119',
        ];

        $entry = [
            'id' => '2017121100000023819',
            'amount' => '0.01',
        ];

        $yiHeXin = new YiHeXin();
        $yiHeXin->setPrivateKey('test');
        $yiHeXin->setOptions($sourceData);
        $yiHeXin->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testPaySuccess()
    {
        $sourceData = [
            'returncode' => '1',
            'userid' => '2381',
            'orderid' => '2017121100000023819',
            'money' => '2',
            'sign' => '9df366c45c20b7ec40a1e9bf83075877',
            'sign2' => '9543f041474285a43762f11216d39119',
        ];

        $entry = [
            'id' => '2017121100000023819',
            'amount' => '2',
        ];

        $yiHeXin = new YiHeXin();
        $yiHeXin->setPrivateKey('test');
        $yiHeXin->setOptions($sourceData);
        $yiHeXin->verifyOrderPayment($entry);

        $this->assertEquals('ok', $yiHeXin->getMsg());
    }
}

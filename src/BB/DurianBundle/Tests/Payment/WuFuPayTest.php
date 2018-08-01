<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\WuFuPay;

class WuFuPayTest extends DurianTestCase
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

        $wuFuPay = new WuFuPay();
        $wuFuPay->getVerifyData();
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

        $wuFuPay = new WuFuPay();
        $wuFuPay->setPrivateKey('test');
        $wuFuPay->getVerifyData();
    }

    /**
     * 測試支付時代入不支援的銀行
     */
    public function testPayWithUnsupportedPaymentVendor()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $options = [
            'paymentVendorId' => '9999',
            'number' => 'WF69015',
            'orderId' => '201712140000003105',
            'username' => 'php1test',
            'amount' => '100',
            'notify_url' => 'http://pay.my/pay/return.php',
        ];

        $wuFuPay = new WuFuPay();
        $wuFuPay->setPrivateKey('test');
        $wuFuPay->setOptions($options);
        $wuFuPay->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $options = [
            'paymentVendorId' => '1',
            'number' => 'WF69015',
            'orderId' => '201712140000003105',
            'username' => 'php1test',
            'amount' => '100',
            'notify_url' => 'http://pay.my/pay/return.php',
        ];

        $wuFuPay = new WuFuPay();
        $wuFuPay->setPrivateKey('test');
        $wuFuPay->setOptions($options);
        $encodeData = $wuFuPay->getVerifyData();

        $this->assertEquals('gatewayPay', $encodeData['svcName']);
        $this->assertEquals('WF69015', $encodeData['merId']);
        $this->assertEquals('201712140000003105', $encodeData['merchOrderId']);
        $this->assertEquals('1000042', $encodeData['tranType']);
        $this->assertEquals('php1test', $encodeData['pName']);
        $this->assertEquals('10000', $encodeData['amt']);
        $this->assertEquals('http://pay.my/pay/return.php', $encodeData['notifyUrl']);
        $this->assertEquals('http://pay.my/pay/return.php', $encodeData['retUrl']);
        $this->assertEquals('1', $encodeData['showCashier']);
        $this->assertEquals('', $encodeData['merData']);
        $this->assertEquals('6061EB6471C4FBB58743ED6B5816F938', $encodeData['md5value']);
    }

    /**
     * 測試手機支付
     */
    public function testPhonePay()
    {
        $options = [
            'paymentVendorId' => '1098',
            'number' => 'WF69015',
            'orderId' => '201712140000003105',
            'username' => 'php1test',
            'amount' => '100',
            'notify_url' => 'http://pay.my/pay/return.php',
        ];

        $wuFuPay = new WuFuPay();
        $wuFuPay->setPrivateKey('test');
        $wuFuPay->setOptions($options);
        $encodeData = $wuFuPay->getVerifyData();

        $this->assertEquals('UniThirdPay', $encodeData['svcName']);
        $this->assertEquals('WF69015', $encodeData['merId']);
        $this->assertEquals('201712140000003105', $encodeData['merchOrderId']);
        $this->assertEquals('ALIPAY_H5', $encodeData['tranType']);
        $this->assertEquals('php1test', $encodeData['pName']);
        $this->assertEquals('10000', $encodeData['amt']);
        $this->assertEquals('http://pay.my/pay/return.php', $encodeData['notifyUrl']);
        $this->assertEquals('http://pay.my/pay/return.php', $encodeData['retUrl']);
        $this->assertEquals('1', $encodeData['showCashier']);
        $this->assertEquals('', $encodeData['merData']);
        $this->assertEquals('7BB4029BE271D1127D7A682E19E0851B', $encodeData['md5value']);
    }

    /**
     * 測試銀聯在線支付
     */
    public function testPayWithUnionpay()
    {
        $options = [
            'paymentVendorId' => '278',
            'number' => 'WF69015',
            'orderId' => '201712140000003105',
            'username' => 'php1test',
            'amount' => '100',
            'notify_url' => 'http://pay.my/pay/return.php',
        ];

        $wuFuPay = new WuFuPay();
        $wuFuPay->setPrivateKey('test');
        $wuFuPay->setOptions($options);
        $encodeData = $wuFuPay->getVerifyData();

        $this->assertEquals('pcQuickPay', $encodeData['svcName']);
        $this->assertEquals('WF69015', $encodeData['merId']);
        $this->assertEquals('201712140000003105', $encodeData['merchOrderId']);
        $this->assertEquals('2000047', $encodeData['tranType']);
        $this->assertEquals('php1test', $encodeData['pName']);
        $this->assertEquals('10000', $encodeData['amt']);
        $this->assertEquals('http://pay.my/pay/return.php', $encodeData['notifyUrl']);
        $this->assertEquals('http://pay.my/pay/return.php', $encodeData['retUrl']);
        $this->assertEquals('1', $encodeData['showCashier']);
        $this->assertEquals('', $encodeData['merData']);
        $this->assertEquals('56125BF5CE17B8CF713A934E5E3320CC', $encodeData['md5value']);
    }

    /**
     * 測試銀聯在線手機支付
     */
    public function testPayWithPhoneUnionpay()
    {
        $options = [
            'paymentVendorId' => '1088',
            'number' => 'WF69015',
            'orderId' => '201712140000003105',
            'username' => 'php1test',
            'amount' => '100',
            'notify_url' => 'http://pay.my/pay/return.php',
        ];

        $wuFuPay = new WuFuPay();
        $wuFuPay->setPrivateKey('test');
        $wuFuPay->setOptions($options);
        $encodeData = $wuFuPay->getVerifyData();

        $this->assertEquals('wapQuickPay', $encodeData['svcName']);
        $this->assertEquals('WF69015', $encodeData['merId']);
        $this->assertEquals('201712140000003105', $encodeData['merchOrderId']);
        $this->assertEquals('2000048', $encodeData['tranType']);
        $this->assertEquals('php1test', $encodeData['pName']);
        $this->assertEquals('10000', $encodeData['amt']);
        $this->assertEquals('http://pay.my/pay/return.php', $encodeData['notifyUrl']);
        $this->assertEquals('http://pay.my/pay/return.php', $encodeData['retUrl']);
        $this->assertEquals('1', $encodeData['showCashier']);
        $this->assertEquals('', $encodeData['merData']);
        $this->assertEquals('11C24E54B9AC2D15CA21BEF8E44B8B01', $encodeData['md5value']);
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

        $wuFuPay = new WuFuPay();
        $wuFuPay->getVerifyData();
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

        $wuFuPay = new WuFuPay();
        $wuFuPay->setPrivateKey('test');
        $wuFuPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時支付平台缺少回傳md5value
     */
    public function testReturnWithoutMd5value()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $sourceData = [
            'tranTime' => '20171214 16:55:35',
            'orderStatusMsg' => '交易成功',
            'merchOrderId' => '201712140000003105',
            'orderId' => 'I201712140000328951',
            'amt' => '1',
            'status' => '0',
        ];

        $wuFuPay = new WuFuPay();
        $wuFuPay->setOptions($sourceData);
        $wuFuPay->setPrivateKey('test');
        $wuFuPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時md5value簽名驗證錯誤
     */
    public function testReturnSignatureVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $sourceData = [
            'tranTime' => '20171214 16:55:35',
            'orderStatusMsg' => '交易成功',
            'merchOrderId' => '201712140000003105',
            'orderId' => 'I201712140000328951',
            'amt' => '1',
            'status' => '0',
            'md5value' => '006398AD3D312133465AFAF6C933EBB1',
        ];

        $wuFuPay = new WuFuPay();
        $wuFuPay->setOptions($sourceData);
        $wuFuPay->setPrivateKey('test');
        $wuFuPay->verifyOrderPayment([]);
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
            'tranTime' => '20171214 16:55:35',
            'orderStatusMsg' => '交易失敗',
            'merchOrderId' => '201712140000003105',
            'orderId' => 'I201712140000328951',
            'amt' => '1',
            'status' => '1',
            'md5value' => 'C24CC7DDE4AD4E29D37E5EA14D58321F',
        ];

        $wuFuPay = new WuFuPay();
        $wuFuPay->setOptions($sourceData);
        $wuFuPay->setPrivateKey('test');
        $wuFuPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回結果為訂單編號錯誤
     */
    public function testReturnOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $sourceData = [
            'tranTime' => '20171214 16:55:35',
            'orderStatusMsg' => '交易成功',
            'merchOrderId' => '201712140000003105',
            'orderId' => 'I201712140000328951',
            'amt' => '1',
            'status' => '0',
            'md5value' => '2EA781CE3148AEE70BFB52101DCC987B',
        ];

        $entry = [
            'id' => '201712140000003104',
            'amount' => '0.01',
        ];

        $wuFuPay = new WuFuPay();
        $wuFuPay->setOptions($sourceData);
        $wuFuPay->setPrivateKey('test');
        $wuFuPay->verifyOrderPayment($entry);
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

        $sourceData = [
            'tranTime' => '20171214 16:55:35',
            'orderStatusMsg' => '交易成功',
            'merchOrderId' => '201712140000003105',
            'orderId' => 'I201712140000328951',
            'amt' => '1',
            'status' => '0',
            'md5value' => '2EA781CE3148AEE70BFB52101DCC987B',
        ];

        $entry = [
            'id' => '201712140000003105',
            'amount' => '1',
        ];

        $wuFuPay = new WuFuPay();
        $wuFuPay->setOptions($sourceData);
        $wuFuPay->setPrivateKey('test');
        $wuFuPay->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testPaySuccess()
    {
        $sourceData = [
            'tranTime' => '20171214 16:55:35',
            'orderStatusMsg' => '交易成功',
            'merchOrderId' => '201712140000003105',
            'orderId' => 'I201712140000328951',
            'amt' => '1',
            'status' => '0',
            'md5value' => '2EA781CE3148AEE70BFB52101DCC987B',
        ];

        $entry = [
            'id' => '201712140000003105',
            'amount' => '0.01',
        ];

        $wuFuPay = new WuFuPay();
        $wuFuPay->setOptions($sourceData);
        $wuFuPay->setPrivateKey('test');
        $wuFuPay->verifyOrderPayment($entry);

        $this->assertEquals('SUCCESS', $wuFuPay->getMsg());
    }
}

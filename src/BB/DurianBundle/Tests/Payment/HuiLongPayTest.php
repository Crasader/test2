<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\HuiLongPay;
use Buzz\Message\Response;

class HuiLongPayTest extends DurianTestCase
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

        $huiLongPay = new HuiLongPay();
        $huiLongPay->getVerifyData();
    }

    /**
     * 測試支付時沒指定支付參數
     */
    public function testPayWithNoPayParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $huiLongPay = new HuiLongPay();
        $huiLongPay->setPrivateKey('test');
        $huiLongPay->getVerifyData();
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
            'notify_url' => 'http://fufutest.000webhostapp.com/pay/',
            'paymentVendorId' => '9999',
            'number' => '1584',
            'orderId' => '201805220000013133',
            'amount' => '1',
        ];

        $huiLongPay = new HuiLongPay();
        $huiLongPay->setPrivateKey('test');
        $huiLongPay->setOptions($options);
        $huiLongPay->getVerifyData();
    }

    /**
     * 測試網銀
     */
    public function testPay()
    {
        $options = [
            'notify_url' => 'http://fufutest.000webhostapp.com/pay/',
            'paymentVendorId' => '1',
            'number' => '1584',
            'orderId' => '201805220000013133',
            'amount' => '1',
        ];

        $huiLongPay = new HuiLongPay();
        $huiLongPay->setPrivateKey('test');
        $huiLongPay->setOptions($options);
        $data = $huiLongPay->getVerifyData();

        $this->assertEquals('1584', $data['merNo']);
        $this->assertEquals('201805220000013133', $data['orderNo']);
        $this->assertEquals('1', $data['amount']);
        $this->assertEquals('http://fufutest.000webhostapp.com/pay/', $data['returnUrl']);
        $this->assertEquals('http://fufutest.000webhostapp.com/pay/', $data['notifyUrl']);
        $this->assertEquals('WY', $data['payType']);
        $this->assertEquals('0', $data['isDirect']);
        $this->assertEquals('ICBC', $data['bankSegment']);
        $this->assertEquals('288058f49d18a34d81972a3caa55613d', $data['sign']);
    }

    /**
     * 測試銀聯在線
     */
    public function testQuickPay()
    {
        $options = [
            'notify_url' => 'http://fufutest.000webhostapp.com/pay/',
            'paymentVendorId' => '278',
            'number' => '1584',
            'orderId' => '201805220000013133',
            'amount' => '1',
            'username' => 'php1test',
        ];

        $huiLongPay = new HuiLongPay();
        $huiLongPay->setPrivateKey('test');
        $huiLongPay->setOptions($options);
        $data = $huiLongPay->getVerifyData();

        $this->assertEquals('1584', $data['merNo']);
        $this->assertEquals('201805220000013133', $data['orderNo']);
        $this->assertEquals('1', $data['amount']);
        $this->assertEquals('http://fufutest.000webhostapp.com/pay/', $data['returnUrl']);
        $this->assertEquals('http://fufutest.000webhostapp.com/pay/', $data['notifyUrl']);
        $this->assertEquals('KUAIJIE', $data['payType']);
        $this->assertEquals('0', $data['isDirect']);
        $this->assertEquals('ca4c57449c6d074aa2bb7d0516e863a3', $data['sign']);
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

        $huiLongPay = new HuiLongPay();
        $huiLongPay->verifyOrderPayment([]);
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

        $huiLongPay = new HuiLongPay();
        $huiLongPay->setPrivateKey('test');
        $huiLongPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時缺少簽名
     */
    public function testReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $options = [
            'returnParam' => '',
            'payoverTime' => '2018-05-22 16:33:16',
            'orderNo' => '201805220000013133',
            'orderAmount' => '1.010000',
            'status' => '200',
            'payType' => 'WY',
            'orderStatus' => 'SUCCESS',
        ];

        $huiLongPay = new HuiLongPay();
        $huiLongPay->setPrivateKey('test');
        $huiLongPay->setOptions($options);
        $huiLongPay->verifyOrderPayment([]);
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
            'sign' => '123456798798',
            'returnParam' => '',
            'payoverTime' => '2018-05-22 16:33:16',
            'orderNo' => '201805220000013133',
            'orderAmount' => '1.010000',
            'status' => '200',
            'payType' => 'WY',
            'orderStatus' => 'SUCCESS',
        ];

        $huiLongPay = new HuiLongPay();
        $huiLongPay->setPrivateKey('test');
        $huiLongPay->setOptions($options);
        $huiLongPay->verifyOrderPayment([]);
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

        $options = [
            'sign' => '9a84a434333d46c26134805c05f5893a',
            'returnParam' => '',
            'payoverTime' => '2018-05-22 16:33:16',
            'orderNo' => '201805220000013133',
            'orderAmount' => '1.010000',
            'status' => '200',
            'payType' => 'WY',
            'orderStatus' => 'FAILED',
        ];

        $huiLongPay = new HuiLongPay();
        $huiLongPay->setPrivateKey('test');
        $huiLongPay->setOptions($options);
        $huiLongPay->verifyOrderPayment([]);
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
            'sign' => 'efb375b166a463c064cbb3ae24f4bb5e',
            'returnParam' => '',
            'payoverTime' => '2018-05-22 16:33:16',
            'orderNo' => '201805220000013133',
            'orderAmount' => '1.010000',
            'status' => '200',
            'payType' => 'WY',
            'orderStatus' => 'SUCCESS',
        ];

        $entry = ['id' => '201503220000000555'];

        $huiLongPay = new HuiLongPay();
        $huiLongPay->setPrivateKey('test');
        $huiLongPay->setOptions($options);
        $huiLongPay->verifyOrderPayment($entry);
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
            'sign' => 'efb375b166a463c064cbb3ae24f4bb5e',
            'returnParam' => '',
            'payoverTime' => '2018-05-22 16:33:16',
            'orderNo' => '201805220000013133',
            'orderAmount' => '1.010000',
            'status' => '200',
            'payType' => 'WY',
            'orderStatus' => 'SUCCESS',
        ];

        $entry = [
            'id' => '201805220000013133',
            'amount' => '15.00',
        ];

        $huiLongPay = new HuiLongPay();
        $huiLongPay->setPrivateKey('test');
        $huiLongPay->setOptions($options);
        $huiLongPay->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testReturnResultSuccess()
    {
        $options = [
            'sign' => 'efb375b166a463c064cbb3ae24f4bb5e',
            'returnParam' => '',
            'payoverTime' => '2018-05-22 16:33:16',
            'orderNo' => '201805220000013133',
            'orderAmount' => '1.010000',
            'status' => '200',
            'payType' => 'WY',
            'orderStatus' => 'SUCCESS',
        ];

        $entry = [
            'id' => '201805220000013133',
            'amount' => '1.010000',
        ];

        $huiLongPay = new HuiLongPay();
        $huiLongPay->setPrivateKey('test');
        $huiLongPay->setOptions($options);
        $huiLongPay->verifyOrderPayment($entry);

        $this->assertEquals('SUCCESS', $huiLongPay->getMsg());
    }
}

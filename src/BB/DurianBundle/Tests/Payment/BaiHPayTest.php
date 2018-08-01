<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\BaiHPay;
use Buzz\Message\Response;

class BaiHPayTest extends DurianTestCase
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

        $baiHPay = new BaiHPay();
        $baiHPay->getVerifyData();
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

        $baiHPay = new BaiHPay();
        $baiHPay->setPrivateKey('test');
        $baiHPay->getVerifyData();
    }

    /**
     * 測試支付加密時代入支付平台不支援的銀行
     */
    public function testPayUnsupportedPaymentVendor()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $options = [
            'number' => '1234',
            'paymentVendorId' => '9999',
            'amount' => '1.01',
            'orderId' => '201803080000008212',
            'notify_url' => 'http://pay.in-action.tw/',
        ];

        $baiHPay = new BaiHPay();
        $baiHPay->setPrivateKey('test');
        $baiHPay->setOptions($options);
        $baiHPay->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $options = [
            'number' => '1234',
            'paymentVendorId' => '1',
            'amount' => '1.01',
            'orderId' => '201803080000008212',
            'notify_url' => 'http://pay.in-action.tw/',
        ];

        $baiHPay = new BaiHPay();
        $baiHPay->setPrivateKey('test');
        $baiHPay->setOptions($options);
        $data = $baiHPay->getVerifyData();

        $this->assertEquals('1234', $data['parter']);
        $this->assertEquals('967', $data['type']);
        $this->assertEquals('1.01', $data['value']);
        $this->assertEquals('201803080000008212', $data['orderid']);
        $this->assertEquals('http://pay.in-action.tw/', $data['callbackurl']);
        $this->assertEquals('', $data['hrefbackurl']);
        $this->assertEquals('', $data['payerIp']);
        $this->assertEquals('', $data['attach']);
        $this->assertEquals('aefd1ba1b36e932dc0fa95ef3719ba28', $data['sign']);
        $this->assertEquals('GET', $baiHPay->getPayMethod());
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

        $baiHPay = new BaiHPay();
        $baiHPay->verifyOrderPayment([]);
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

        $baiHPay = new BaiHPay();
        $baiHPay->setPrivateKey('test');
        $baiHPay->verifyOrderPayment([]);
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
            'orderid' => '201803080000008212',
            'opstate' => '0',
            'ovalue' => '1.00',
            'systime' => '2018/03/08 15:53:18',
            'sysorderid' => '1803081552414270093',
            'completiontime' => '2018/03/08 15:53:18',
            'attach' => '',
            'msg' => '',
        ];

        $baiHPay = new BaiHPay();
        $baiHPay->setPrivateKey('test');
        $baiHPay->setOptions($options);
        $baiHPay->verifyOrderPayment([]);
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
            'orderid' => '201803080000008212',
            'opstate' => '0',
            'ovalue' => '1.00',
            'systime' => '2018/03/08 15:53:18',
            'sysorderid' => '1803081552414270093',
            'completiontime' => '2018/03/08 15:53:18',
            'attach' => '',
            'msg' => '',
            'sign' => '0f9f907d8c237ac262765daa9e26b53b',
        ];

        $baiHPay = new BaiHPay();
        $baiHPay->setPrivateKey('test');
        $baiHPay->setOptions($options);
        $baiHPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時取得無效的支付參數
     */
    public function testReturnInvalidPayParameters()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Invalid pay parameters',
            180129
        );

        $options = [
            'orderid' => '201803080000008212',
            'opstate' => '-1',
            'ovalue' => '1.00',
            'systime' => '2018/03/08 15:53:18',
            'sysorderid' => '1803081552414270093',
            'completiontime' => '2018/03/08 15:53:18',
            'attach' => '',
            'msg' => '',
            'sign' => 'cdaceadaa67d91e56cfa66a4b43c9b12',
        ];

        $baiHPay = new BaiHPay();
        $baiHPay->setPrivateKey('test');
        $baiHPay->setOptions($options);
        $baiHPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時返回請求簽名錯誤
     */
    public function testReturnMerchantSignError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'PaymentGateway error, Merchant sign error',
            180127
        );

        $options = [
            'orderid' => '201803080000008212',
            'opstate' => '-2',
            'ovalue' => '1.00',
            'systime' => '2018/03/08 15:53:18',
            'sysorderid' => '1803081552414270093',
            'completiontime' => '2018/03/08 15:53:18',
            'attach' => '',
            'msg' => '',
            'sign' => '278652a851542419bc626b6ec1dc2009',
        ];

        $baiHPay = new BaiHPay();
        $baiHPay->setPrivateKey('test');
        $baiHPay->setOptions($options);
        $baiHPay->verifyOrderPayment([]);
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
            'orderid' => '201803080000008212',
            'opstate' => '-3',
            'ovalue' => '1.00',
            'systime' => '2018/03/08 15:53:18',
            'sysorderid' => '1803081552414270093',
            'completiontime' => '2018/03/08 15:53:18',
            'attach' => '',
            'msg' => '',
            'sign' => 'f54af412c2a70900e5b475e575005f4e',
        ];

        $baiHPay = new BaiHPay();
        $baiHPay->setPrivateKey('test');
        $baiHPay->setOptions($options);
        $baiHPay->verifyOrderPayment([]);
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
            'orderid' => '201803080000008212',
            'opstate' => '0',
            'ovalue' => '1.00',
            'systime' => '2018/03/08 15:53:18',
            'sysorderid' => '1803081552414270093',
            'completiontime' => '2018/03/08 15:53:18',
            'attach' => '',
            'msg' => '',
            'sign' => '4720a31afc9151c54c9d731a1e1a928b',
        ];

        $entry = ['id' => '201503220000000555'];

        $baiHPay = new BaiHPay();
        $baiHPay->setPrivateKey('test');
        $baiHPay->setOptions($options);
        $baiHPay->verifyOrderPayment($entry);
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
            'orderid' => '201803080000008212',
            'opstate' => '0',
            'ovalue' => '1.00',
            'systime' => '2018/03/08 15:53:18',
            'sysorderid' => '1803081552414270093',
            'completiontime' => '2018/03/08 15:53:18',
            'attach' => '',
            'msg' => '',
            'sign' => '4720a31afc9151c54c9d731a1e1a928b',
        ];

        $entry = [
            'id' => '201803080000008212',
            'amount' => '15.00',
        ];

        $baiHPay = new BaiHPay();
        $baiHPay->setPrivateKey('test');
        $baiHPay->setOptions($options);
        $baiHPay->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testReturnOrder()
    {
        $options = [
            'orderid' => '201803080000008212',
            'opstate' => '0',
            'ovalue' => '1.00',
            'systime' => '2018/03/08 15:53:18',
            'sysorderid' => '1803081552414270093',
            'completiontime' => '2018/03/08 15:53:18',
            'attach' => '',
            'msg' => '',
            'sign' => '4720a31afc9151c54c9d731a1e1a928b',
        ];

        $entry = [
            'id' => '201803080000008212',
            'amount' => '1',
        ];

        $baiHPay = new BaiHPay();
        $baiHPay->setPrivateKey('test');
        $baiHPay->setOptions($options);
        $baiHPay->verifyOrderPayment($entry);

        $this->assertEquals('opstate=0', $baiHPay->getMsg());
    }
}

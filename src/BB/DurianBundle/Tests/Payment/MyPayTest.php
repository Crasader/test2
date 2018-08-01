<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\MyPay;

class MyPayTest extends DurianTestCase
{
    /**
     * 提交給支付平台時需要的參數
     *
     * @var array
     */
    private $sourceData;

    /**
     * 支付成功時通知的參數
     *
     * @var array
     */
    private $returnResult;

    public function setUp()
    {
        parent::setUp();

        $this->sourceData = [
            'number' => 'IID003',
            'paymentVendorId' => '1104',
            'orderId' => '201807060000012457',
            'orderCreateDate' => '2018-07-09 10:27:01',
            'amount' => '10',
            'ip' => '192.168.101.1',
            'notify_url' => 'http://return.php',
            'merchant_extra' => [
                'orgId' => 'IID',
                'postUrl' => 'http://testapipay.mypayla.com/apiOrder/sendOrder.zv',
            ],
        ];

        $this->returnResult = [
            'amount' => '10.00',
            'clientIp' => '127.0.0.1',
            'extra' => '',
            'merId' => 'IID003',
            'merchantNo' => '201807060000012485',
            'orderNo' => 'M201807069940000030',
            'realAmount' => '10.00',
            'sign' => 'cf8ae2f4c0ddb77309779498dc99a778',
            'tradeDate' => '20180706174523',
        ];
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

        $myPay = new MyPay();
        $myPay->getVerifyData();
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

        $myPay = new MyPay();
        $myPay->setPrivateKey('test');
        $myPay->getVerifyData();
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

        $this->sourceData['paymentVendorId'] = '9999';

        $myPay = new MyPay();
        $myPay->setPrivateKey('test');
        $myPay->setOptions($this->sourceData);
        $myPay->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $myPay = new MyPay();
        $myPay->setPrivateKey('test');
        $myPay->setOptions($this->sourceData);
        $verifyData = $myPay->getVerifyData();

        $this->assertEquals('http://testapipay.mypayla.com/apiOrder/sendOrder.zv', $verifyData['post_url']);
        $this->assertEquals('V1.0', $verifyData['params']['version']);
        $this->assertEquals('IID003', $verifyData['params']['merId']);
        $this->assertEquals('IID', $verifyData['params']['orgId']);
        $this->assertEquals('9', $verifyData['params']['payType']);
        $this->assertEquals('201807060000012457', $verifyData['params']['merchantNo']);
        $this->assertEquals('wap', $verifyData['params']['terminalClient']);
        $this->assertEquals('20180709102701', $verifyData['params']['tradeDate']);
        $this->assertEquals('10.00', $verifyData['params']['amount']);
        $this->assertEquals('192.168.101.1', $verifyData['params']['clientIp']);
        $this->assertEquals('http://return.php', $verifyData['params']['notifyUrl']);
        $this->assertEquals('E3C2AE7940974127B869EE66C24C1367', $verifyData['params']['sign']);
        $this->assertEquals('MD5', $verifyData['params']['signType']);
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

        $myPay = new MyPay();
        $myPay->verifyOrderPayment([]);
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

        $myPay = new MyPay();
        $myPay->setPrivateKey('test');
        $myPay->verifyOrderPayment([]);
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

        unset($this->returnResult['sign']);

        $myPay = new MyPay();
        $myPay->setPrivateKey('test');
        $myPay->setOptions($this->returnResult);
        $myPay->verifyOrderPayment([]);
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

        $this->returnResult['sign'] = 'error';

        $myPay = new MyPay();
        $myPay->setPrivateKey('test');
        $myPay->setOptions($this->returnResult);
        $myPay->verifyOrderPayment([]);
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

        $entry = ['id' => '201503220000000555'];

        $myPay = new MyPay();
        $myPay->setPrivateKey('test');
        $myPay->setOptions($this->returnResult);
        $myPay->verifyOrderPayment($entry);
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

        $entry = [
            'id' => '201807060000012485',
            'amount' => '15.00',
        ];

        $myPay = new MyPay();
        $myPay->setPrivateKey('test');
        $myPay->setOptions($this->returnResult);
        $myPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturnSuccess()
    {
        $entry = [
            'id' => '201807060000012485',
            'amount' => '10.00',
        ];

        $myPay = new MyPay();
        $myPay->setPrivateKey('test');
        $myPay->setOptions($this->returnResult);
        $myPay->verifyOrderPayment($entry);

        $this->assertEquals('MyPay', $myPay->getMsg());
    }
}

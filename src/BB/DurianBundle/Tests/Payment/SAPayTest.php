<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\SAPay;

class SAPayTest extends DurianTestCase
{
    /**
     * 支付時的參數
     *
     * @var array
     */
    private $sourceData;

    /**
     * 返回時的參數
     *
     * @var array
     */
    private $returnResult;

    public function setUp()
    {
        parent::setUp();

        $this->sourceData = [
            'number' => '10001443',
            'amount' => '1',
            'orderId' => '201806160000011709',
            'paymentVendorId' => '1098',
            'orderCreateDate' => '2018-06-15 20:20:20',
            'notify_url' => 'http://sandsomeGuy.isme',
            'ip' => '192.168.101.111',
        ];

        $this->returnResult = [
            'returnParam' => '',
            'order_amount' => '1',
            'order_time' => '2018-06-16 19:57:06',
            'pay_type' => 'ALIH5',
            'status' => '200',
            'mac' => '423f7eb29883b98df5e1cb3605df73bf',
            'order_status' => 'SUCCESS',
            'order_no' => '201806160000011709',
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

        $sAPay = new SAPay();
        $sAPay->getVerifyData();
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

        $sAPay = new SAPay();
        $sAPay->setPrivateKey('test');
        $sAPay->getVerifyData();
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

        $this->sourceData['paymentVendorId'] = '66666';

        $sAPay = new SAPay();
        $sAPay->setPrivateKey('test');
        $sAPay->setOptions($this->sourceData);
        $sAPay->getVerifyData();
    }

    /**
     * 測試手機支付
     */
    public function testPhonePay()
    {
        $sAPay = new SAPay();
        $sAPay->setPrivateKey('test');
        $sAPay->setOptions($this->sourceData);
        $verifyData = $sAPay->getVerifyData();

        $this->assertEquals('10001443', $verifyData['key']);
        $this->assertEquals('1.00', $verifyData['order_amount']);
        $this->assertEquals('10001443', $verifyData['account_id']);
        $this->assertEquals('201806160000011709', $verifyData['order_no']);
        $this->assertEquals('ALIH5', $verifyData['pay_type']);
        $this->assertEquals('20180615202020', $verifyData['order_time']);
        $this->assertEquals('http://sandsomeGuy.isme', $verifyData['return_url']);
        $this->assertEquals('http://sandsomeGuy.isme', $verifyData['callback_url']);
        $this->assertEquals('192.168.101.111', $verifyData['request_ip']);
        $this->assertEquals('BEE4A6A5F89974D25C2764DB6E94B1F0', $verifyData['mac']);
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

        $sAPay = new SAPay();
        $sAPay->verifyOrderPayment([]);
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

        $sAPay = new SAPay();
        $sAPay->setPrivateKey('test');
        $sAPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時缺少簽名
     */
    public function testReturnWithoutMac()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        unset($this->returnResult['mac']);

        $sAPay = new SAPay();
        $sAPay->setPrivateKey('test');
        $sAPay->setOptions($this->returnResult);
        $sAPay->verifyOrderPayment([]);
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

        $this->returnResult['mac'] = 'This sign will verify fail';

        $sAPay = new SAPay();
        $sAPay->setPrivateKey('test');
        $sAPay->setOptions($this->returnResult);
        $sAPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時訂單未支付
     */
    public function testReturnUnpaidOrder()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Unpaid order',
            180062
        );

        $this->returnResult['order_status'] = 'WAITING_PAYMENT';
        $this->returnResult['status'] = '0';
        $this->returnResult['mac'] = '213800296e5a5c92de83cd2f9a550139';

        $sAPay = new SAPay();
        $sAPay->setPrivateKey('test');
        $sAPay->setOptions($this->returnResult);
        $sAPay->verifyOrderPayment([]);
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

        $this->returnResult['order_status'] = 'FAILED';
        $this->returnResult['status'] = '-1';
        $this->returnResult['mac'] = 'a29309f548678ad772681aac8924cec4';

        $sAPay = new SAPay();
        $sAPay->setPrivateKey('test');
        $sAPay->setOptions($this->returnResult);
        $sAPay->verifyOrderPayment([]);
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

        $entry = ['id' => '201503220000000000'];

        $sAPay = new SAPay();
        $sAPay->setPrivateKey('test');
        $sAPay->setOptions($this->returnResult);
        $sAPay->verifyOrderPayment($entry);
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
            'id' => '201806160000011709',
            'amount' => '15.00',
        ];

        $sAPay = new SAPay();
        $sAPay->setPrivateKey('test');
        $sAPay->setOptions($this->returnResult);
        $sAPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturnSuccess()
    {
        $entry = [
            'id' => '201806160000011709',
            'amount' => '1.00',
        ];

        $sAPay = new SAPay();
        $sAPay->setPrivateKey('test');
        $sAPay->setOptions($this->returnResult);
        $sAPay->verifyOrderPayment($entry);

        $this->assertEquals('SUCCESS', $sAPay->getMsg());
    }
}

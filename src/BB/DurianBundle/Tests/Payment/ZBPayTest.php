<?php
namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\ZBPay;

class ZBPayTest extends DurianTestCase
{
    /**
     * 測試支付加密時沒有帶入privateKey的情況
     */
    public function testPayEncodeWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $zBPay = new ZBPay();
        $zBPay->getVerifyData();
    }

    /**
     * 測試加密基本參數設定未指定支付參數
     */
    public function testPayWithNoPayParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $zBPay = new ZBPay();

        $zBPay->setPrivateKey('1234');
        $zBPay->getVerifyData();
    }

    /**
     * 測試支付加密時代入支付平台不支援的銀行
     */
    public function testPayEncodeUnsupportedPaymentVendor()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $sourceData = [
            'number' => '123456789',
            'paymentVendorId' => '999',
            'amount' => '0.01',
            'orderId' => '201709140000000875',
            'notify_url' => 'http://payment/return.php',
            'orderCreateDate' => '2017-09-14 10:16:02',
        ];

        $zBPay = new ZBPay();
        $zBPay->setPrivateKey('1234');
        $zBPay->setOptions($sourceData);
        $zBPay->getVerifyData();
    }

    /**
     * 測試加密
     */
    public function testGetEncodeData()
    {
        $sourceData = [
            'number' => '123456789',
            'paymentVendorId' => '1',
            'amount' => '0.01',
            'orderId' => '201709140000000875',
            'notify_url' => 'http://payment/return.php',
            'orderCreateDate' => '2017-09-14 10:16:02',
        ];

        $zBPay = new ZBPay();
        $zBPay->setPrivateKey('1234');
        $zBPay->setOptions($sourceData);
        $encodeData = $zBPay->getVerifyData();

        $this->assertEquals($sourceData['number'], $encodeData['customer']);
        $this->assertEquals($sourceData['orderId'], $encodeData['orderid']);
        $this->assertEquals(967, $encodeData['banktype']);
        $this->assertEquals($sourceData['amount'], $encodeData['amount']);
        $this->assertEquals(strtotime($sourceData['orderCreateDate']), $encodeData['request_time']);
    }

    /**
     * 測試解密基本參數設定沒有帶入key的情況
     */
    public function testSetDecodeSourceNoKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $zBPay = new ZBPay();

        $zBPay->verifyOrderPayment([]);
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

        $zBPay = new ZBPay();
        $zBPay->setPrivateKey('1234');
        $zBPay->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證時支付平台缺少回傳sign參數
     */
    public function testReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $sourceData = [
            'orderid' => '201709140000000875',
            'result' => '0',
            'amount' => '2.01',
            'zborderid' => 'Z1709141015345411647',
            'completetime' => '2017/09/14 10:16:02',
            'notifytime' => '2017/09/14 10:16:02',
            'attach' => '',
            'msg' => '',
        ];

        $zBPay = new ZBPay();
        $zBPay->setPrivateKey('1234');
        $zBPay->setOptions($sourceData);
        $zBPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時簽名錯誤
     */
    public function testReturnSignatureVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $sourceData = [
            'orderid' => '201709140000000875',
            'result' => '0',
            'amount' => '1.01',
            'zborderid' => 'Z1709141015345411647',
            'completetime' => '2017/09/14 10:16:02',
            'notifytime' => '2017/09/14 10:16:02',
            'attach' => '',
            'msg' => '',
            'sign' => 'd6a7ac62b6a9d21ccf4b4e8c06214c53',
        ];

        $zBPay = new ZBPay();
        $zBPay->setPrivateKey('12345');
        $zBPay->setOptions($sourceData);
        $zBPay->verifyOrderPayment([]);
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

        $sourceData = [
            'orderid' => '201709140000000875',
            'result' => '1',
            'amount' => '1.01',
            'zborderid' => 'Z1709141015345411647',
            'completetime' => '2017/09/14 10:16:02',
            'notifytime' => '2017/09/14 10:16:02',
            'attach' => '',
            'msg' => '',
            'sign' => '6a8c1e5060741211778e2b5d7ebb0f6b',
        ];

        $zBPay = new ZBPay();
        $zBPay->setPrivateKey('1234');
        $zBPay->setOptions($sourceData);
        $zBPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回訂單號錯誤
     */
    public function testOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $sourceData = [
            'orderid' => '201709140000000875',
            'result' => '0',
            'amount' => '1.01',
            'zborderid' => 'Z1709141015345411647',
            'completetime' => '2017/09/14 10:16:02',
            'notifytime' => '2017/09/14 10:16:02',
            'attach' => '',
            'msg' => '',
            'sign' => 'd6a7ac62b6a9d21ccf4b4e8c06214c53',
        ];

        $entry = [
            'id' => '201709140000000876',
            'amount' => '1.01',
        ];

        $zBPay = new ZBPay();
        $zBPay->setPrivateKey('1234');
        $zBPay->setOptions($sourceData);
        $zBPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回金額錯誤
     */
    public function testOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $sourceData = [
            'orderid' => '201709140000000875',
            'result' => '0',
            'amount' => '1.01',
            'zborderid' => 'Z1709141015345411647',
            'completetime' => '2017/09/14 10:16:02',
            'notifytime' => '2017/09/14 10:16:02',
            'attach' => '',
            'msg' => '',
            'sign' => 'd6a7ac62b6a9d21ccf4b4e8c06214c53',
        ];

        $entry = [
            'id' => '201709140000000875',
            'amount' => '2.01',
        ];

        $zBPay = new ZBPay();
        $zBPay->setPrivateKey('1234');
        $zBPay->setOptions($sourceData);
        $zBPay->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testPaySuccess()
    {
        $sourceData = [
            'orderid' => '201709140000000875',
            'result' => '0',
            'amount' => '1.01',
            'zborderid' => 'Z1709141015345411647',
            'completetime' => '2017/09/14 10:16:02',
            'notifytime' => '2017/09/14 10:16:02',
            'attach' => '',
            'msg' => '',
            'sign' => 'd6a7ac62b6a9d21ccf4b4e8c06214c53',
        ];

        $entry = [
            'id' => '201709140000000875',
            'amount' => '1.01',
        ];

        $zBPay = new ZBPay();
        $zBPay->setPrivateKey('1234');
        $zBPay->setOptions($sourceData);
        $zBPay->verifyOrderPayment($entry);

        $this->assertEquals('opstate=0', $zBPay->getMsg());
    }
}
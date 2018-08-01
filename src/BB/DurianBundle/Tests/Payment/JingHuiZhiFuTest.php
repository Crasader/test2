<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Payment\JingHuiZhiFu;
use BB\DurianBundle\Tests\DurianTestCase;
use Buzz\Message\Response;

class JingHuiZhiFuTest extends DurianTestCase
{
    /**
     * 支付時的內部參數
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
            'number' => 'bjhzymsm10004',
            'orderId' => '201805170000012191',
            'paymentVendorId' => '1098',
            'amount' => 1,
            'orderCreateDate' => '2018-05-17 18:06:24',
            'notify_url' => 'http://pay.php',
            'ip' => '192.168.1.1',
        ];

        $this->returnResult = [
            'sign' => '495ce6f11084d263d60b664a4526ba08',
            'payResult' => 'SUCCESS',
            'payAmt' => '10.00',
            'remark' => '',
            'jnetOrderId' => '20180517000010135585',
            'agentId' => 'bjhzymsm10004',
            'payMessage' => '支付宝支付成功',
            'agentOrderId' => '201805170000012191',
            'version' => '1.0',
        ];
    }

    /**
     * 測試支付時缺少私鑰
     */
    public function testPayWithPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $jJingHuiZhiFu = new JingHuiZhiFu();
        $jJingHuiZhiFu->getVerifyData();
    }

    /**
     *  測試支付時沒有指定支付參數
     */
    public function testPayWithNoPayParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $jJingHuiZhiFu = new JingHuiZhiFu();
        $jJingHuiZhiFu->setPrivateKey('test');
        $jJingHuiZhiFu->getVerifyData();
    }

    /**
     *  測試支付時帶入不支援銀行
     */
    public function testPayWithoutSupportedBank()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $this->sourceData['paymentVendorId'] = '9999';

        $jJingHuiZhiFu = new JingHuiZhiFu();
        $jJingHuiZhiFu->setPrivateKey('test');
        $jJingHuiZhiFu->setOptions($this->sourceData);
        $jJingHuiZhiFu->getVerifyData();
    }

    /**
     *  測試支付成功
     */
    public function testPaySuccess()
    {
        $jJingHuiZhiFu = new JingHuiZhiFu();
        $jJingHuiZhiFu->setPrivateKey('test');
        $jJingHuiZhiFu->setOptions($this->sourceData);
        $requestData = $jJingHuiZhiFu->getVerifyData();

        $this->assertEquals('1.0', $requestData['version']);
        $this->assertEquals('bjhzymsm10004', $requestData['agentId']);
        $this->assertEquals('201805170000012191', $requestData['agentOrderId']);
        $this->assertEquals('P0004', $requestData['payType']);
        $this->assertEquals('', $requestData['bankCode']);
        $this->assertEquals('1.00', $requestData['payAmt']);
        $this->assertEquals('20180517180624', $requestData['orderTime']);
        $this->assertEquals('http://pay.php', $requestData['notifyUrl']);
        $this->assertEquals('192.168.1.1', $requestData['payIp']);
        $this->assertEquals('', $requestData['noticePage']);
        $this->assertEquals('', $requestData['remark']);
        $this->assertEquals('4b7b3ff3b3d0174efbe6c80d27131485', $requestData['sign']);
    }

    /**
     *  測試返回時缺少私鑰
     */
    public function testReturnWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $jJingHuiZhiFu = new JingHuiZhiFu();
        $jJingHuiZhiFu->verifyOrderPayment([]);
    }

    /**
     *測試返回時未指定返回參數
     */
    public function testReturnWithReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $jJingHuiZhiFu = new JingHuiZhiFu();
        $jJingHuiZhiFu->setPrivateKey('test');
        $jJingHuiZhiFu->verifyOrderPayment([]);
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

        $jJingHuiZhiFu = new JingHuiZhiFu();
        $jJingHuiZhiFu->setPrivateKey('test');
        $jJingHuiZhiFu->setOptions($this->returnResult);
        $jJingHuiZhiFu->verifyOrderPayment([]);
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

        $jJingHuiZhiFu = new JingHuiZhiFu();
        $jJingHuiZhiFu->setPrivateKey('test');
        $jJingHuiZhiFu->setOptions($this->returnResult);
        $jJingHuiZhiFu->verifyOrderPayment([]);
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

        $this->returnResult['payResult'] = 'FAIL';
        $this->returnResult['sign'] = '483cc3a9e7f45d1dcf25e96aa7ff47e8';

        $jJingHuiZhiFu = new JingHuiZhiFu();
        $jJingHuiZhiFu->setPrivateKey('test');
        $jJingHuiZhiFu->setOptions($this->returnResult);
        $jJingHuiZhiFu->verifyOrderPayment([]);
    }

    /**
     * 測試返回結果訂單號錯誤
     */
    public function testReturnOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $entry = ['id' => '201707030000000105'];

        $jJingHuiZhiFu = new JingHuiZhiFu();
        $jJingHuiZhiFu->setPrivateKey('test');
        $jJingHuiZhiFu->setOptions($this->returnResult);
        $jJingHuiZhiFu->verifyOrderPayment($entry);
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
            'id' => '201805170000012191',
            'amount' => '2.0000',
        ];

        $jJingHuiZhiFu = new JingHuiZhiFu();
        $jJingHuiZhiFu->setPrivateKey('test');
        $jJingHuiZhiFu->setOptions($this->returnResult);
        $jJingHuiZhiFu->verifyOrderPayment($entry);
    }

    /**
     * 測試支付認證成功
     */
    public function testVerifyOrderPayment()
    {
        $entry = [
            'id' => '201805170000012191',
            'amount' => '10',
        ];

        $jJingHuiZhiFu = new JingHuiZhiFu();
        $jJingHuiZhiFu->setPrivateKey('test');
        $jJingHuiZhiFu->setOptions($this->returnResult);
        $jJingHuiZhiFu->verifyOrderPayment($entry);

        $this->assertEquals('OK', $jJingHuiZhiFu->getMsg());
    }
}


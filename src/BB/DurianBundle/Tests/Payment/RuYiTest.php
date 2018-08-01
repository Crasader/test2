<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\RuYi;

class RuYiTest extends DurianTestCase
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
            'number' => '990020241',
            'orderId' => '201805180000011595',
            'amount' => '2',
            'paymentVendorId' => '1111',
            'notify_url' => 'http://retunn.php',
        ];

        $this->returnResult = [
            'merId' => '990020241',
            'merOrdId' => '201805180000011595',
            'merOrdAmt' => '2.00',
            'sysOrdId' => 'J180543876902',
            'tradeStatus' => 'success002',
            'remark' => '201805180000011595',
            'signType' => 'MD5',
            'signMsg' => '359a9a2c401ac12b5fbb95c955df93e0',
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

        $ruYi = new RuYi();
        $ruYi->getVerifyData();
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

        $ruYi = new RuYi();
        $ruYi->setPrivateKey('test');
        $ruYi->getVerifyData();
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

        $ruYi = new RuYi();
        $ruYi->setPrivateKey('test');
        $ruYi->setOptions($this->sourceData);
        $ruYi->getVerifyData();
    }

    /**
     * 測試網銀支付
     */
    public function testOnlinePay()
    {
        $this->sourceData['paymentVendorId'] = '1';

        $ruYi = new RuYi();
        $ruYi->setPrivateKey('test');
        $ruYi->setOptions($this->sourceData);
        $requestData = $ruYi->getVerifyData();

        $this->assertEquals('990020241', $requestData['merId']);
        $this->assertEquals('201805180000011595', $requestData['merOrdId']);
        $this->assertEquals('2.00', $requestData['merOrdAmt']);
        $this->assertEquals('10', $requestData['payType']);
        $this->assertEquals('ICBC', $requestData['bankCode']);
        $this->assertEquals('http://retunn.php', $requestData['returnUrl']);
        $this->assertEquals('http://retunn.php', $requestData['notifyUrl']);
        $this->assertEquals('MD5', $requestData['signType']);
        $this->assertEquals('aefabbf0f80fb7977503f5fbbdec8bb6', $requestData['signMsg']);
    }

    /**
     * 測試非網銀支付
     */
    public function testNotOnlinePay()
    {
        $ruYi = new RuYi();
        $ruYi->setPrivateKey('test');
        $ruYi->setOptions($this->sourceData);
        $requestData = $ruYi->getVerifyData();

        $this->assertEquals('990020241', $requestData['merId']);
        $this->assertEquals('201805180000011595', $requestData['merOrdId']);
        $this->assertEquals('2.00', $requestData['merOrdAmt']);
        $this->assertEquals('11', $requestData['payType']);
        $this->assertEquals('UNIONQR', $requestData['bankCode']);
        $this->assertEquals('http://retunn.php', $requestData['returnUrl']);
        $this->assertEquals('http://retunn.php', $requestData['notifyUrl']);
        $this->assertEquals('MD5', $requestData['signType']);
        $this->assertEquals('66b8e44c73c7bad74ed516ba2ee35a9a', $requestData['signMsg']);
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

        $ruYi = new RuYi();
        $ruYi->verifyOrderPayment([]);
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

        $ruYi = new RuYi();
        $ruYi->setPrivateKey('test');
        $ruYi->verifyOrderPayment([]);
    }

    /**
     * 測試返回時缺少加密簽名
     */
    public function testReturnWithoutSignMsg()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        unset($this->returnResult['signMsg']);

        $ruYi = new RuYi();
        $ruYi->setPrivateKey('test');
        $ruYi->setOptions($this->returnResult);
        $ruYi->verifyOrderPayment([]);
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

        $this->returnResult['signMsg'] = 'error';

        $ruYi = new RuYi();
        $ruYi->setPrivateKey('test');
        $ruYi->setOptions($this->returnResult);
        $ruYi->verifyOrderPayment([]);
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

        $this->returnResult['tradeStatus'] = 'fail';
        $this->returnResult['signMsg'] = 'e45caf3e6915e1ce59482d1913e009d0';

        $ruYi = new RuYi();
        $ruYi->setPrivateKey('test');
        $ruYi->setOptions($this->returnResult);
        $ruYi->verifyOrderPayment([]);
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

        $entry = ['id' => '201705220000000321'];

        $ruYi = new RuYi();
        $ruYi->setPrivateKey('test');
        $ruYi->setOptions($this->returnResult);
        $ruYi->verifyOrderPayment($entry);
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
            'id' => '201805180000011595',
            'amount' => '11.00',
        ];

        $ruYi = new RuYi();
        $ruYi->setPrivateKey('test');
        $ruYi->setOptions($this->returnResult);
        $ruYi->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturn()
    {
        $entry = [
            'id' => '201805180000011595',
            'amount' => '2.00',
        ];

        $ruYi = new RuYi();
        $ruYi->setPrivateKey('test');
        $ruYi->setOptions($this->returnResult);
        $ruYi->verifyOrderPayment($entry);

        $this->assertEquals('stopnotify', $ruYi->getMsg());
    }
}

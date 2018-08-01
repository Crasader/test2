<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\KuaiShua;
use Buzz\Message\Response;

class KuaiShuaTest extends DurianTestCase
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
            'paymentVendorId' => '278',
            'number' => '936951118820000',
            'orderId' => '201805240000012300',
            'orderCreateDate' => '2018-05-24 15:04:21',
            'amount' => '10',
            'notify_url' => 'http://return.php',
        ];

        $this->returnResult = [
            'versionId' => '001',
            'businessType' => '1100',
            'insCode' => '',
            'merId' => '936951118820000',
            'transDate' => '20180524',
            'transAmount' => '10.00',
            'transCurrency' => '156',
            'transChanlName' => 'UNIONPAY',
            'openBankName' => '',
            'orderId' => '201805240000012300',
            'payStatus' => '00',
            'payMsg' => '%BD%BB%D2%D7%B3%C9%B9%A6',
            'pageNotifyUrl' => 'http://candj.huhu.tw/pay/return.php',
            'backNotifyUrl' => 'http://candj.huhu.tw/pay/return.php',
            'orderDesc' => '201805240000012300',
            'dev' => '',
            'signData' => 'FC0334894BA318F7A58C2141EF2CEC89',
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

        $kuaiShua = new KuaiShua();
        $kuaiShua->getVerifyData();
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

        $kuaiShua = new KuaiShua();
        $kuaiShua->setPrivateKey('test');
        $kuaiShua->getVerifyData();
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

        $this->sourceData['paymentVendorId'] = '9999';

        $kuaiShua = new KuaiShua();
        $kuaiShua->setPrivateKey('test');
        $kuaiShua->setOptions($this->sourceData);
        $kuaiShua->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $kuaiShua = new KuaiShua();
        $kuaiShua->setPrivateKey('test');
        $kuaiShua->setOptions($this->sourceData);
        $requestData = $kuaiShua->getVerifyData();

        $this->assertEquals('001', $requestData['versionId']);
        $this->assertEquals('1100', $requestData['businessType']);
        $this->assertEquals('', $requestData['insCode']);
        $this->assertEquals('936951118820000', $requestData['merId']);
        $this->assertEquals('201805240000012300', $requestData['orderId']);
        $this->assertEquals('20180524150421', $requestData['transDate']);
        $this->assertEquals('10.00', $requestData['transAmount']);
        $this->assertEquals('UNIONPAY', $requestData['transChanlName']);
        $this->assertEquals('', $requestData['openBankName']);
        $this->assertEquals('http://return.php', $requestData['pageNotifyUrl']);
        $this->assertEquals('http://return.php', $requestData['backNotifyUrl']);
        $this->assertEquals('', $requestData['dev']);
        $this->assertEquals('D31DC877D2290FE457EBD4F2CA42DCA8', $requestData['signData']);
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

        $kuaiShua = new KuaiShua();
        $kuaiShua->verifyOrderPayment([]);
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

        $kuaiShua = new KuaiShua();
        $kuaiShua->setPrivateKey('test');
        $kuaiShua->verifyOrderPayment([]);
    }

    /**
     * 測試返回時缺少簽名
     */
    public function testReturnWithoutSignData()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        unset($this->returnResult['signData']);

        $kuaiShua = new KuaiShua();
        $kuaiShua->setPrivateKey('test');
        $kuaiShua->setOptions($this->returnResult);
        $kuaiShua->verifyOrderPayment([]);
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

        $this->returnResult['signData'] = 'ERROR';

        $kuaiShua = new KuaiShua();
        $kuaiShua->setPrivateKey('test');
        $kuaiShua->setOptions($this->returnResult);
        $kuaiShua->verifyOrderPayment([]);
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

        $this->returnResult['payStatus'] = '02';
        $this->returnResult['signData'] = '69EAB233AE3E725EC6D18C6280761B7F';

        $kuaiShua = new KuaiShua();
        $kuaiShua->setPrivateKey('test');
        $kuaiShua->setOptions($this->returnResult);
        $kuaiShua->verifyOrderPayment([]);
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

        $entry = [
            'id' => '201503220000000555',
        ];

        $kuaiShua = new KuaiShua();
        $kuaiShua->setPrivateKey('test');
        $kuaiShua->setOptions($this->returnResult);
        $kuaiShua->verifyOrderPayment($entry);
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
            'id' => '201805240000012300',
            'amount' => '15.00',
        ];

        $kuaiShua = new KuaiShua();
        $kuaiShua->setPrivateKey('test');
        $kuaiShua->setOptions($this->returnResult);
        $kuaiShua->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testReturnOrder()
    {
        $entry = [
            'id' => '201805240000012300',
            'amount' => '10',
        ];

        $kuaiShua = new KuaiShua();
        $kuaiShua->setPrivateKey('test');
        $kuaiShua->setOptions($this->returnResult);
        $kuaiShua->verifyOrderPayment($entry);

        $this->assertEquals('OK', $kuaiShua->getMsg());
    }
}

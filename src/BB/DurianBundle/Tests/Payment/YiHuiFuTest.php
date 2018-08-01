<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\YiHuiFu;

class YiHuiFuTest extends DurianTestCase
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
    private $returnReslt;

    public function setUp()
    {
        $this->sourceData = [
            'number' => '10913',
            'orderId' => '201806280000012216',
            'amount' => '10',
            'notify_url' => 'http://return.php',
            'paymentVendorId' => '1103',
            'ip' => '192.168.1.1',
            'verify_url' => 'payment.http.YiHuiFu.com',
            'verify_ip' => ['172.26.54.41', '172.26.54.42'],
        ];

        $this->returnResult = [
            'status' => '1',
            'trade_no' => '2018062816394068258',
            'out_trade_no' => '201806280000012216',
            'total_amount' => '10.000000',
            'trade_type' => 'qqrcode',
            'pay_time' => '',
            'sign' => 'e8f7383a1ec81c02a3bba0ce6d296a3c',
            'sign_type' => 'MD5',
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

        $yiHuiFu = new YiHuiFu();
        $yiHuiFu->getVerifyData();
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

        $yiHuiFu = new YiHuiFu();
        $yiHuiFu->setPrivateKey('test');
        $yiHuiFu->setOptions([]);
        $yiHuiFu->getVerifyData();
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

        $this->sourceData['paymentVendorId'] = '66666';

        $yiHuiFu = new YiHuiFu();
        $yiHuiFu->setPrivateKey('test');
        $yiHuiFu->setOptions($this->sourceData);
        $yiHuiFu->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $yiHuiFu = new YiHuiFu();
        $yiHuiFu->setPrivateKey('test');
        $yiHuiFu->setOptions($this->sourceData);
        $data = $yiHuiFu->getVerifyData();

        $json = '{"out_trade_no":"201806280000012216","order_name":"201806280000012216","total_amount":"10.00",' .
            '"spbill_create_ip":"192.168.1.1","notify_url":"http:\/\/return.php","return_url":"http:\/\/return.php"}';

        $this->assertEquals('10913', $data['app_id']);
        $this->assertEquals('qqrcode', $data['method']);
        $this->assertEquals('fa369699ef114a61ecdf11558db8b138', $data['sign']);
        $this->assertEquals('MD5', $data['sign_type']);
        $this->assertEquals('1.0', $data['version']);
        $this->assertEquals($json, $data['content']);
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

        $yiHuiFu = new YiHuiFu();
        $yiHuiFu->verifyOrderPayment([]);
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

        $yiHuiFu = new YiHuiFu();
        $yiHuiFu->setPrivateKey('test');
        $yiHuiFu->setOptions([]);
        $yiHuiFu->verifyOrderPayment([]);
    }

    /**
     * 測試返回時支付平台缺少回傳sign(加密簽名)
     */
    public function testReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        unset($this->returnResult['sign']);

        $yiHuiFu = new YiHuiFu();
        $yiHuiFu->setPrivateKey('test');
        $yiHuiFu->setOptions($this->returnResult);
        $yiHuiFu->verifyOrderPayment([]);
    }

    /**
     * 測試返回時加密簽名驗證錯誤
     */
    public function testReturnSignatureVerificationFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $this->returnResult['sign'] = 'error';

        $yiHuiFu = new YiHuiFu();
        $yiHuiFu->setPrivateKey('test');
        $yiHuiFu->setOptions($this->returnResult);
        $yiHuiFu->verifyOrderPayment([]);
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

        $this->returnResult['status'] = '-1';
        $this->returnResult['sign'] = '4a64d43aff04435e234250381c7b8248';

        $yiHuiFu = new YiHuiFu();
        $yiHuiFu->setPrivateKey('test');
        $yiHuiFu->setOptions($this->returnResult);
        $yiHuiFu->verifyOrderPayment([]);
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

        $entry = ['id' => '201704100000002210'];

        $yiHuiFu = new YiHuiFu();
        $yiHuiFu->setPrivateKey('test');
        $yiHuiFu->setOptions($this->returnResult);
        $yiHuiFu->verifyOrderPayment($entry);
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
            'id' => '201806280000012216',
            'amount' => '1000',
        ];

        $yiHuiFu = new YiHuiFu();
        $yiHuiFu->setPrivateKey('test');
        $yiHuiFu->setOptions($this->returnResult);
        $yiHuiFu->verifyOrderPayment($entry);
    }

    /**
     * 測試返回成功
     */
    public function testReturnSuccess()
    {
        $entry = [
            'id' => '201806280000012216',
            'amount' => '10.00',
        ];

        $yiHuiFu = new YiHuiFu();
        $yiHuiFu->setPrivateKey('test');
        $yiHuiFu->setOptions($this->returnResult);
        $yiHuiFu->verifyOrderPayment($entry);

        $this->assertEquals('success', $yiHuiFu->getMsg());
    }
}

<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\Juypay;

class JuypayTest extends DurianTestCase
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

        $juypay = new Juypay();
        $juypay->getVerifyData();
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

        $juypay = new Juypay();
        $juypay->setPrivateKey('test');
        $juypay->getVerifyData();
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
            'number' => '16972',
            'notify_url' => 'http://two123.comxa.com/',
            'paymentVendorId' => '999',
            'orderId' => '201609290000004496',
            'amount' => '0.10',
        ];

        $juypay = new Juypay();
        $juypay->setPrivateKey('test');
        $juypay->setOptions($options);
        $juypay->getVerifyData();
    }

    /**
     * 測試支付沒有帶入postUrl的情況
     */
    public function testPayWithoutPostUrl()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $sourceData = [
            'number' => '6550',
            'paymentVendorId' => '1',
            'amount' => '0.01',
            'orderId' => '201609220000004434',
            'notify_url' => 'http://two123.comxa.com/',
            'postUrl' => '',
        ];

        $juypay = new Juypay();
        $juypay->setPrivateKey('1234');
        $juypay->setOptions($sourceData);
        $juypay->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $options = [
            'notify_url' => 'http://two123.comxa.com/',
            'paymentVendorId' => '1',
            'orderId' => '201608160000003698',
            'amount' => '1.00',
            'number' => '16972',
            'postUrl' => 'http://pay.juypay.com/PayBank.aspx',
        ];

        $juypay = new Juypay();
        $juypay->setPrivateKey('test');
        $juypay->setOptions($options);
        $encodeData = $juypay->getVerifyData();

        $this->assertEquals($options['number'], $encodeData['partner']);
        $this->assertEquals('ICBC', $encodeData['banktype']);
        $this->assertEquals($options['amount'], $encodeData['paymoney']);
        $this->assertEquals($options['orderId'], $encodeData['ordernumber']);
        $this->assertEquals($options['notify_url'], $encodeData['callbackurl']);
        $this->assertEquals('', $encodeData['hrefbackurl']);
        $this->assertEquals('', $encodeData['attach']);
        $this->assertEquals('1', $encodeData['isshow']);
        $this->assertEquals('58a2184df96f782d23e0bf7eb0ced45d', $encodeData['sign']);

        // 檢查要提交的網址是否正確
        $data = [];
        $data['partner'] = $encodeData['partner'];
        $data['banktype'] = $encodeData['banktype'];
        $data['paymoney'] = $encodeData['paymoney'];
        $data['ordernumber'] = $encodeData['ordernumber'];
        $data['callbackurl'] = $encodeData['callbackurl'];
        $data['hrefbackurl'] = $encodeData['hrefbackurl'];
        $data['attach'] = $encodeData['attach'];
        $data['isshow'] = $encodeData['isshow'];
        $data['sign'] = $encodeData['sign'];

        $this->assertEquals($options['postUrl'] . '?' . http_build_query($data), $encodeData['act_url']);
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

        $juypay = new Juypay();
        $juypay->verifyOrderPayment([]);
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

        $juypay = new Juypay();
        $juypay->setPrivateKey('test');
        $juypay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時缺少sign
     */
    public function testReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $options = [
            'partner' => '16972',
            'ordernumber' => '201609290000004496',
            'orderstatus' => '1',
            'paymoney' => '0.1000',
            'sysnumber' => 'J1697216092913397188210000',
            'attach' => '',
        ];

        $juypay = new Juypay();
        $juypay->setPrivateKey('test');
        $juypay->setOptions($options);
        $juypay->verifyOrderPayment([]);
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
            'partner' => '16972',
            'ordernumber' => '201609290000004496',
            'orderstatus' => '1',
            'paymoney' => '0.1000',
            'sysnumber' => 'J1697216092913397188210000',
            'attach' => '',
            'sign' => '6aed90cc1da327f188b9387bf5443123',
        ];

        $juypay = new Juypay();
        $juypay->setPrivateKey('test');
        $juypay->setOptions($options);
        $juypay->verifyOrderPayment([]);
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

        $options = [
            'partner' => '16972',
            'ordernumber' => '201609290000004496',
            'orderstatus' => '-1',
            'paymoney' => '0.1000',
            'sysnumber' => 'J1697216092913397188210000',
            'attach' => '',
            'sign' => 'a9e11ed74f3ff210a06dd7915900d765',
        ];

        $juypay = new Juypay();
        $juypay->setPrivateKey('test');
        $juypay->setOptions($options);
        $juypay->verifyOrderPayment([]);
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
            'partner' => '16972',
            'ordernumber' => '201609290000004496',
            'orderstatus' => '1',
            'paymoney' => '0.1000',
            'sysnumber' => 'J1697216092913397188210000',
            'attach' => '',
            'sign' => 'd23b7583831fdf22dd11ad9d745dcdc4',
        ];

        $entry = ['id' => '201509140000002475'];

        $juypay = new Juypay();
        $juypay->setPrivateKey('test');
        $juypay->setOptions($options);
        $juypay->verifyOrderPayment($entry);
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
            'partner' => '16972',
            'ordernumber' => '201609290000004496',
            'orderstatus' => '1',
            'paymoney' => '0.1000',
            'sysnumber' => 'J1697216092913397188210000',
            'attach' => '',
            'sign' => 'd23b7583831fdf22dd11ad9d745dcdc4',
        ];

        $entry = [
            'id' => '201609290000004496',
            'amount' => '15.00',
        ];

        $juypay = new Juypay();
        $juypay->setPrivateKey('test');
        $juypay->setOptions($options);
        $juypay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturnOrder()
    {
        $options = [
            'partner' => '16972',
            'ordernumber' => '201609290000004496',
            'orderstatus' => '1',
            'paymoney' => '0.1000',
            'sysnumber' => 'J1697216092913397188210000',
            'attach' => '',
            'sign' => 'd23b7583831fdf22dd11ad9d745dcdc4',
        ];

        $entry = [
            'id' => '201609290000004496',
            'amount' => '0.1',
        ];

        $juypay = new Juypay();
        $juypay->setPrivateKey('test');
        $juypay->setOptions($options);
        $juypay->verifyOrderPayment($entry);

        $this->assertEquals('ok', $juypay->getMsg());
    }
}

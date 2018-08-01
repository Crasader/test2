<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\YunAnPay;

class YunAnPayTest extends DurianTestCase
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

        $yunAnPay = new YunAnPay();
        $yunAnPay->getVerifyData();
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

        $yunAnPay = new YunAnPay();
        $yunAnPay->setPrivateKey('test');
        $yunAnPay->getVerifyData();
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

        $sourceData = [
            'number' => '27641',
            'notify_url' => 'http://two123.comxa.com/',
            'paymentVendorId' => '999',
            'orderId' => '201609290000004496',
            'amount' => '0.01',
            'username' => '',
        ];

        $yunAnPay = new YunAnPay();
        $yunAnPay->setPrivateKey('test');
        $yunAnPay->setOptions($sourceData);
        $yunAnPay->getVerifyData();
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
            'number' => '27641',
            'paymentVendorId' => '1',
            'amount' => '0.01',
            'orderId' => '201609220000004434',
            'notify_url' => 'http://two123.comxa.com/',
            'username' => '',
            'postUrl' => '',
        ];

        $yunAnPay = new YunAnPay();
        $yunAnPay->setPrivateKey('1234');
        $yunAnPay->setOptions($sourceData);
        $yunAnPay->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $sourceData = [
            'number' => '27641',
            'paymentVendorId' => '1',
            'amount' => '1.00',
            'orderId' => '201608160000003698',
            'notify_url' => 'http://two123.comxa.com/',
            'postUrl' => 'http://api.52hpay.com:8888/PayGateWay.aspx',
            'username' => '',
        ];

        $yunAnPay = new YunAnPay();
        $yunAnPay->setPrivateKey('test');
        $yunAnPay->setOptions($sourceData);
        $encodeData = $yunAnPay->getVerifyData();

        $this->assertEquals($sourceData['number'], $encodeData['partner']);
        $this->assertEquals('ICBC', $encodeData['banktype']);
        $this->assertEquals($sourceData['amount'], $encodeData['paymoney']);
        $this->assertEquals($sourceData['orderId'], $encodeData['ordernumber']);
        $this->assertEquals($sourceData['notify_url'], $encodeData['callbackurl']);
        $this->assertEquals('', $encodeData['hrefbackurl']);
        $this->assertEquals('', $encodeData['attach']);
        $this->assertEquals('4d40067d6b314600dda3fb8cf395b399', $encodeData['sign']);

        // 檢查要提交的網址是否正確
        $data = [];
        $data['partner'] = $encodeData['partner'];
        $data['banktype'] = $encodeData['banktype'];
        $data['paymoney'] = $encodeData['paymoney'];
        $data['ordernumber'] = $encodeData['ordernumber'];
        $data['callbackurl'] = $encodeData['callbackurl'];
        $data['hrefbackurl'] = $encodeData['hrefbackurl'];
        $data['attach'] = $encodeData['attach'];
        $data['sign'] = $encodeData['sign'];

        $this->assertEquals($sourceData['postUrl'] . '?' . http_build_query($data), $encodeData['act_url']);
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

        $yunAnPay = new YunAnPay();
        $yunAnPay->setPrivateKey('test');
        $yunAnPay->verifyOrderPayment([]);
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

        $sourceData = [
            'partner' => '27641',
            'ordernumber' => '201609290000004496',
            'orderstatus' => '1',
            'paymoney' => '0.10',
            'sysnumber' => 'J1697216092913397188210000',
            'attach' => '',
        ];

        $yunAnPay = new YunAnPay();
        $yunAnPay->setPrivateKey('test');
        $yunAnPay->setOptions($sourceData);
        $yunAnPay->verifyOrderPayment([]);
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

        $sourceData = [
            'partner' => '27641',
            'ordernumber' => '201609290000004496',
            'orderstatus' => '1',
            'paymoney' => '0.10',
            'sysnumber' => 'J1697216092913397188210000',
            'attach' => '',
            'sign' => '6aed90cc1da387bf5443123',
        ];

        $yunAnPay = new YunAnPay();
        $yunAnPay->setPrivateKey('test');
        $yunAnPay->setOptions($sourceData);
        $yunAnPay->verifyOrderPayment([]);
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
            'partner' => '27641',
            'ordernumber' => '201609290000004496',
            'orderstatus' => '999',
            'paymoney' => '0.10',
            'sysnumber' => 'J1697216092913397188210000',
            'attach' => '',
            'sign' => 'f380c2e42b542e246444421526fdeca4',
        ];

        $yunAnPay = new YunAnPay();
        $yunAnPay->setPrivateKey('test');
        $yunAnPay->setOptions($sourceData);
        $yunAnPay->verifyOrderPayment([]);
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

        $sourceData = [
            'partner' => '27641',
            'ordernumber' => '201609290000004496',
            'orderstatus' => '1',
            'paymoney' => '0.10',
            'sysnumber' => 'J1697216092913397188210000',
            'attach' => '',
            'sign' => 'f957ccedf2ff06d65016cec863408ae3',
        ];

        $entry = [
            'id' => '201609290000004496',
            'amount' => '15.00',
        ];

        $yunAnPay = new YunAnPay();
        $yunAnPay->setPrivateKey('test');
        $yunAnPay->setOptions($sourceData);
        $yunAnPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果為訂單單號錯誤
     */
    public function testReturnOrderIdFailed()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $sourceData = [
            'partner' => '27641',
            'ordernumber' => '201609290000004496',
            'orderstatus' => '1',
            'paymoney' => '0.10',
            'sysnumber' => 'J1697216092913397188210000',
            'attach' => '',
            'sign' => 'f957ccedf2ff06d65016cec863408ae3',
        ];

        $entry = ['id' => '201609290000004499'];

        $yunAnPay = new YunAnPay();
        $yunAnPay->setPrivateKey('test');
        $yunAnPay->setOptions($sourceData);
        $yunAnPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturnOrder()
    {
        $sourceData = [
            'partner' => '27641',
            'ordernumber' => '201609290000004496',
            'orderstatus' => '1',
            'paymoney' => '0.10',
            'sysnumber' => 'J1697216092913397188210000',
            'attach' => '',
            'sign' => 'f957ccedf2ff06d65016cec863408ae3',
        ];

        $entry = [
            'id' => '201609290000004496',
            'amount' => '0.1',
        ];

        $yunAnPay = new YunAnPay();
        $yunAnPay->setPrivateKey('test');
        $yunAnPay->setOptions($sourceData);
        $yunAnPay->verifyOrderPayment($entry);

        $this->assertEquals('ok', $yunAnPay->getMsg());
    }
}

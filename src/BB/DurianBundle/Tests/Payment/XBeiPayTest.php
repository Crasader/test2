<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\XBeiPay;

class XBeiPayTest extends DurianTestCase
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

        $XBeiPay = new XBeiPay();
        $XBeiPay->getVerifyData();
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

        $options = ['number' => ''];

        $XBeiPay = new XBeiPay();
        $XBeiPay->setPrivateKey('abcdefg');
        $XBeiPay->setOptions($options);
        $XBeiPay->getVerifyData();
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
            'number' => '100000178',
            'orderId' => '20140326000000123',
            'amount' => '1234',
            'notify_url' => 'http://www.baofoo.com/demo/UserNotIFy.aspx',
            'orderCreateDate' => '2014-03-26 12:09:53',
            'ip' => '1.2.3.4',
            'paymentVendorId' => '7',
            'username' => 'hello123',
            'domain' => '6',
            'merchantId' => '12345',
        ];

        $XBeiPay = new XBeiPay();
        $XBeiPay->setPrivateKey('abcdefg');
        $XBeiPay->setOptions($options);
        $XBeiPay->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $options = [
            'number' => '100000178',
            'orderId' => '20140326000000123',
            'amount' => '1234',
            'notify_url' => 'http://www.baofoo.com/demo/UserNotIFy.aspx',
            'orderCreateDate' => '2014-03-26 12:09:53',
            'ip' => '1.2.3.4',
            'paymentVendorId' => '1',
            'username' => 'hello123',
            'domain' => '6',
            'merchantId' => '12345',
        ];

        $XBeiPay = new XBeiPay();
        $XBeiPay->setPrivateKey('abcdefg');
        $XBeiPay->setOptions($options);
        $requestData = $XBeiPay->getVerifyData();

        $notifyUrl = sprintf(
            '%s?pay_system=%s&hallid=%s',
            $options['notify_url'],
            $options['merchantId'],
            $options['domain']
        );

        $this->assertEquals('V1.0', $requestData['Version']);
        $this->assertEquals('100000178', $requestData['MerchantCode']);
        $this->assertEquals('20140326000000123', $requestData['OrderId']);
        $this->assertEquals('1234', $requestData['Amount']);
        $this->assertEquals($notifyUrl, $requestData['AsyNotifyUrl']);
        $this->assertEquals($notifyUrl, $requestData['SynNotifyUrl']);
        $this->assertEquals('20140326120953', $requestData['OrderDate']);
        $this->assertEquals('1.2.3.4', $requestData['TradeIp']);
        $this->assertEquals('100012', $requestData['PayCode']);
        $this->assertEquals('hello123', $requestData['GoodsName']);
        $this->assertEquals('297C82C4A35A4565BB3D3F0A7A8CE5D1', $requestData['SignValue']);
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

        $XBeiPay = new XBeiPay();
        $XBeiPay->verifyOrderPayment([]);
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

        $XBeiPay = new XBeiPay();
        $XBeiPay->setPrivateKey('abcdefg');
        $XBeiPay->verifyOrderPayment([]);
    }

    /**
     * 測試返回時缺少簽名
     */
    public function testReturnWithoutMd5Sign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $options = [
            'Version' => 'V1.0',
            'MerchantCode' => 'E01846',
            'OrderId' => '201604110000004935',
            'OrderDate' => '20160411115802',
            'TradeIp' => '1.2.3.4',
            'SerialNo' => '635959275253347774',
            'Amount' => '0.01',
            'PayCode' => '20140328100612',
            'State' => '8888',
            'FinishTime' => '20160411115549',
        ];

        $XBeiPay = new XBeiPay();
        $XBeiPay->setPrivateKey('abcdefg');
        $XBeiPay->setOptions($options);
        $XBeiPay->verifyOrderPayment([]);
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
            'Version' => 'V1.0',
            'MerchantCode' => 'E01846',
            'OrderId' => '201604110000004935',
            'OrderDate' => '20160411115802',
            'TradeIp' => '1.2.3.4',
            'SerialNo' => '635959275253347774',
            'Amount' => '0.01',
            'PayCode' => '20140328100612',
            'State' => '8888',
            'FinishTime' => '20160411115549',
            'SignValue' => '123',
        ];

        $XBeiPay = new XBeiPay();
        $XBeiPay->setPrivateKey('abcdefg');
        $XBeiPay->setOptions($options);
        $XBeiPay->verifyOrderPayment([]);
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
            'Version' => 'V1.0',
            'MerchantCode' => 'E01846',
            'OrderId' => '201604110000004935',
            'OrderDate' => '20160411115802',
            'TradeIp' => '1.2.3.4',
            'SerialNo' => '635959275253347774',
            'Amount' => '0.01',
            'PayCode' => '20140328100612',
            'State' => 'MR01',
            'FinishTime' => '20160411115549',
            'SignValue' => '5B5829753733ABF4DABB82E12D61E8ED',
        ];

        $XBeiPay = new XBeiPay();
        $XBeiPay->setPrivateKey('abcdefg');
        $XBeiPay->setOptions($options);
        $XBeiPay->verifyOrderPayment([]);
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
            'Version' => 'V1.0',
            'MerchantCode' => 'E01846',
            'OrderId' => '201604110000004935',
            'OrderDate' => '20160411115802',
            'TradeIp' => '1.2.3.4',
            'SerialNo' => '635959275253347774',
            'Amount' => '0.01',
            'PayCode' => '20140328100612',
            'State' => '8888',
            'FinishTime' => '20160411115549',
            'SignValue' => '314A1DD44271C0D583E90E5948F0F692',
        ];

        $entry = ['id' => '20140327000001456'];

        $XBeiPay = new XBeiPay();
        $XBeiPay->setPrivateKey('abcdefg');
        $XBeiPay->setOptions($options);
        $XBeiPay->verifyOrderPayment($entry);
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
            'Version' => 'V1.0',
            'MerchantCode' => 'E01846',
            'OrderId' => '201604110000004935',
            'OrderDate' => '20160411115802',
            'TradeIp' => '1.2.3.4',
            'SerialNo' => '635959275253347774',
            'Amount' => '0.01',
            'PayCode' => '20140328100612',
            'State' => '8888',
            'FinishTime' => '20160411115549',
            'SignValue' => '314A1DD44271C0D583E90E5948F0F692',
        ];

        $entry = [
            'id' => '201604110000004935',
            'amount' => '12340',
        ];

        $XBeiPay = new XBeiPay();
        $XBeiPay->setPrivateKey('abcdefg');
        $XBeiPay->setOptions($options);
        $XBeiPay->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturnOrder()
    {
        $options = [
            'Version' => 'V1.0',
            'MerchantCode' => 'E01846',
            'OrderId' => '201604110000004935',
            'OrderDate' => '20160411115802',
            'TradeIp' => '1.2.3.4',
            'SerialNo' => '635959275253347774',
            'Amount' => '0.01',
            'PayCode' => '20140328100612',
            'State' => '8888',
            'FinishTime' => '20160411115549',
            'SignValue' => '314A1DD44271C0D583E90E5948F0F692',
        ];

        $entry = [
            'id' => '201604110000004935',
            'amount' => '0.01'
        ];

        $XBeiPay = new XBeiPay();
        $XBeiPay->setPrivateKey('abcdefg');
        $XBeiPay->setOptions($options);
        $XBeiPay->verifyOrderPayment($entry);

        $this->assertEquals('OK', $XBeiPay->getMsg());
    }
}

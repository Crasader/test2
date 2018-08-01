<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\DinPay;

class DinPayTest extends DurianTestCase
{
    /**
     * 測試加密基本參數設定沒有帶入privateKey的情況
     */
    public function testSetEncodeSourceNoPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $dinPay = new DinPay();
        $dinPay->getVerifyData();
    }

    /**
     * 測試加密基本參數設定未指定支付參數
     */
    public function testSetEncodeSourceNoPayParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $dinPay = new DinPay();
        $dinPay->setPrivateKey('kaixinjiuhao_1234567890');

        $sourceData = ['number' => ''];

        $dinPay->setOptions($sourceData);
        $dinPay->getVerifyData();
    }

    /**
     * 測試加密基本參數設定沒有帶入paymentVendorId的情況
     */
    public function testSetEncodeSourceNoPaymentVendorId()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $dinPay = new DinPay();
        $dinPay->setPrivateKey('kaixinjiuhao_1234567890');

        $sourceData = [
            'number' => '2000872',
            'orderId' => '201405060001',
            'amount' => '0.01',
            'notify_url' => 'http://59.126.84.197:3030/return.php?pay_system=21347&hallid=243',
            'username' => 'php1test',
            'orderCreateDate' => '2014-05-09 13:58:00',
            'paymentVendorId' => '',
            'merchantId' => '12345',
            'domain' => '6',
        ];

        $dinPay->setOptions($sourceData);
        $dinPay->getVerifyData();
    }

    /**
     * 測試加密基本參數設定帶入不支援的銀行
     */
    public function testSetEncodeSourceUnsupportedPaymentVendor()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $dinPay = new DinPay();
        $dinPay->setPrivateKey('kaixinjiuhao_1234567890');

        $sourceData = [
            'number' => '2000872',
            'orderId' => '201405060001',
            'amount' => '0.01',
            'notify_url' => 'http://59.126.84.197:3030/return.php?pay_system=21347&hallid=243',
            'username' => 'php1test',
            'orderCreateDate' => '2014-05-09 13:58:00',
            'paymentVendorId' => '999',
            'merchantId' => '12345',
            'domain' => '6',
        ];

        $dinPay->setOptions($sourceData);
        $dinPay->getVerifyData();
    }

    /**
     * 測試加密
     */
    public function testGetEncodeData()
    {
        $dinPay = new DinPay();
        $dinPay->setPrivateKey('kaixinjiuhao_1234567890');

        $sourceData = [
            'number' => '2000872',
            'orderId' => '201405060001',
            'amount' => '0.01',
            'notify_url' => 'http://59.126.84.197:3030/return.php',
            'username' => 'php1test',
            'orderCreateDate' => '2014-05-09 13:58:00',
            'paymentVendorId' => '1',
            'merchantId' => '12345',
            'domain' => '6',
        ];

        $dinPay->setOptions($sourceData);
        $encodeData = $dinPay->getVerifyData();

        $OrderMessage = '323030303837327C3230313430353036303030317C302E30317C317' .
            'C687474703A2F2F35392E3132362E38342E3139373A333033302F72657475726E2E7068' .
            '703F7061795F73797374656D3D31323334352668616C6C69643D367C317C7C7C7C7C7' .
            'C7C7C7C7C7C70687031746573747C307C323031342D30352D30392031333A35383A3030';

        $this->assertEquals($OrderMessage, $encodeData['OrderMessage']);
        $this->assertEquals('A24508D6AFA8AF82E6A2EC3AB68832E9', $encodeData['digest']);
        $this->assertEquals('2000872', $encodeData['M_ID']);
    }

    /**
     * 測試解密基本參數設定沒有帶入key的情況
     */
    public function testSetDecodeSourceNoKey()
    {
        $this->setExpectedException('BB\DurianBundle\Exception\PaymentException', 'No privateKey specified');

        $dinPay = new DinPay();

        $dinPay->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證缺少訂單信息(OrderMessage)
     */
    public function testVerifyWithoutOrderMessage()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $dinPay = new DinPay();
        $dinPay->setPrivateKey('kaixinjiuhao_1234567890');

        $sourceData = ['Digest' => 'C74F4BB8E007FEF161C144EBA0914DFE'];

        $dinPay->setOptions($sourceData);
        $dinPay->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證缺少加密簽名(Digest)
     */
    public function testVerifyWithoutDigest()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $dinPay = new DinPay();
        $dinPay->setPrivateKey('kaixinjiuhao_1234567890');

        $orderMessage = '323030303837327C3230313430353036303030327C2E30317C317'.
            'C317C6E756C6C7C6E756C6C7C6E756C6C7C6E756C6C7C6E756C6C7C6E756C6C7C'.
            '6E756C6C7C6E756C6C7C6E756C6C7C6E756C6C7C70687031746573747C3230313'.
            '42D30352D30392031333A35383A30307C32';

        $sourceData = ['OrderMessage' => $orderMessage];

        $dinPay->setOptions($sourceData);
        $dinPay->verifyOrderPayment([]);
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

        $dinPay = new DinPay();
        $dinPay->setPrivateKey('kaixinjiuhao_1234567890');

        $orderMessage = '323030303837327C3230313430353036303030327C2E30317C317'.
            'C317C6E756C6C7C6E756C6C7C6E756C6C7C6E756C6C7C6E756C6C7C6E756C6C7C'.
            '6E756C6C7C6E756C6C7C6E756C6C7C6E756C6C7C70687031746573747C3230313'.
            '42D30352D30392031333A35383A30307C32';

        $sourceData = [
            'OrderMessage' => $orderMessage,
            'Digest'       => '161C144EBA0914DFEC74F4BB8E007FEF'
        ];

        $dinPay->setOptions($sourceData);
        $dinPay->verifyOrderPayment([]);
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

        $dinPay = new DinPay();
        $dinPay->setPrivateKey('kaixinjiuhao_1234567890');

        $orderMessage = '323030303837327C3230313430353036303030327C2E30317C317'.
            'C317C6E756C6C7C6E756C6C7C6E756C6C7C6E756C6C7C6E756C6C7C6E756C6C7C'.
            '6E756C6C7C6E756C6C7C6E756C6C7C6E756C6C7C70687031746573747C3230313'.
            '42D30352D30392031333A35383A30307C31';

        $sourceData = [
            'OrderMessage' => $orderMessage,
            'Digest'       => '312A7D8165ECE52B5824289F9B4310CA'
        ];

        $dinPay->setOptions($sourceData);
        $dinPay->verifyOrderPayment([]);
    }

    /**
     * 測試線上支付單號不正確的情況
     */
    public function testOrderIdError()
    {
        $this->setExpectedException('BB\DurianBundle\Exception\PaymentException', 'Order Id error', 180061);

        $dinPay = new DinPay();
        $dinPay->setPrivateKey('kaixinjiuhao_1234567890');

        $orderMessage = '323030303837327C3230313430353036303030327C2E30317C317'.
            'C317C6E756C6C7C6E756C6C7C6E756C6C7C6E756C6C7C6E756C6C7C6E756C6C7C'.
            '6E756C6C7C6E756C6C7C6E756C6C7C6E756C6C7C70687031746573747C3230313'.
            '42D30352D30392031333A35383A30307C32';

        $sourceData = [
            'OrderMessage' => $orderMessage,
            'Digest'       => 'C74F4BB8E007FEF161C144EBA0914DFE'
        ];

        $entry = ['id' => '201405060001'];

        $dinPay->setOptions($sourceData);
        $dinPay->verifyOrderPayment($entry);
    }

    /**
     * 測試線上支付金額不正確的情況
     */
    public function testOrderAmountError()
    {
        $this->setExpectedException('BB\DurianBundle\Exception\PaymentException', 'Order Amount error', 180058);

        $dinPay = new DinPay();
        $dinPay->setPrivateKey('kaixinjiuhao_1234567890');

        $orderMessage = '323030303837327C3230313430353036303030327C2E30317C317'.
            'C317C6E756C6C7C6E756C6C7C6E756C6C7C6E756C6C7C6E756C6C7C6E756C6C7C'.
            '6E756C6C7C6E756C6C7C6E756C6C7C6E756C6C7C70687031746573747C3230313'.
            '42D30352D30392031333A35383A30307C32';

        $sourceData = [
            'OrderMessage' => $orderMessage,
            'Digest'       => 'C74F4BB8E007FEF161C144EBA0914DFE'
        ];

        $entry = [
            'id' => '201405060002',
            'amount' => '1.0000'
        ];

        $dinPay->setOptions($sourceData);
        $dinPay->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testPaySuccess()
    {
        $dinPay = new DinPay();
        $dinPay->setPrivateKey('kaixinjiuhao_1234567890');

        $orderMessage = '323030303837327C3230313430353036303030327C2E30317C317'.
            'C317C6E756C6C7C6E756C6C7C6E756C6C7C6E756C6C7C6E756C6C7C6E756C6C7C'.
            '6E756C6C7C6E756C6C7C6E756C6C7C6E756C6C7C70687031746573747C3230313'.
            '42D30352D30392031333A35383A30307C32';

        $sourceData = [
            'OrderMessage' => $orderMessage,
            'Digest'       => 'C74F4BB8E007FEF161C144EBA0914DFE'
        ];

        $entry = [
            'id' => '201405060002',
            'amount' => '0.0100'
        ];

        $dinPay->setOptions($sourceData);
        $dinPay->verifyOrderPayment($entry);

        $this->assertEquals('success', $dinPay->getMsg());
    }
}

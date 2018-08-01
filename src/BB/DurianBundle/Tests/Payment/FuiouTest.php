<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\Fuiou;

class FuiouTest extends DurianTestCase
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

        $fuiou = new Fuiou();
        $fuiou->getVerifyData();
    }

    /**
     * 測試加密基本參數設定 未指定支付參數
     */
    public function testSetEncodeSourceNoPayParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $fuiou = new Fuiou();
        $fuiou->setPrivateKey('1234');

        $sourceData = ['number' => ''];

        $fuiou->setOptions($sourceData);
        $fuiou->getVerifyData();
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

        $fuiou = new Fuiou();
        $fuiou->setPrivateKey('1234');

        $sourceData = [
            'number' => '0002900F0006944',
            'orderId' => '11032302065863805732',
            'amount' => '2000',
            'notify_url' => 'http://192.168.9.7:8080/paytest/result.jsp',
            'paymentVendorId' => '999',
            'username' => 'Sony W5500',
            'merchantId' => '12345',
            'domain' => '6',
        ];

        $fuiou->setOptions($sourceData);
        $fuiou->getVerifyData();
    }

    /**
     * 測試加密
     */
    public function testGetEncodeData()
    {
        $sourceData = [
            'number' => '0002900F0006944',
            'orderId' => '11032302065863805732',
            'amount' => '2000',
            'notify_url' => 'http://esball.org/app/member/pay_online2/pay_result.php',
            'paymentVendorId' => '1',
            'username' => 'Sony W5500',
            'merchantId' => '12345',
            'domain' => '6',
        ];

        $fuiou = new Fuiou();
        $fuiou->setPrivateKey('1234');
        $fuiou->setOptions($sourceData);
        $encodeData = $fuiou->getVerifyData();

        $notifyUrl = sprintf(
            '%s?pay_system=%s&hallid=%s',
            $sourceData['notify_url'],
            $sourceData['merchantId'],
            $sourceData['domain']
        );

        $this->assertEquals($sourceData['number'], $encodeData['mchnt_cd']);
        $this->assertEquals($sourceData['orderId'], $encodeData['order_id']);
        $this->assertEquals($sourceData['amount'] * 100, $encodeData['order_amt']);
        $this->assertEquals($notifyUrl, $encodeData['page_notify_url']);
        $this->assertEquals($notifyUrl, $encodeData['back_notify_url']);
        $this->assertEquals('0801020000', $encodeData['iss_ins_cd']);
        $this->assertEquals($sourceData['username'], $encodeData['goods_name']);
        $this->assertEquals('b1ff523c37dc6df06ce9f74f49f6ff2e', $encodeData['md5']);
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

        $fuiou = new Fuiou();

        $fuiou->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證時缺少回傳參數(測試缺少:order_id)
     */
    public function testVerifyWithNoReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $fuiou = new Fuiou();
        $fuiou->setPrivateKey('6qbqzfd8zc2ng5topytcbfzjjt4a7g1x');

        $sourceData = [
            'mchnt_cd'        => '0005840F0062251',
            'order_date'      => '20121106',
            'order_amt'       => '1',
            'order_st'        => '11',
            'order_pay_code'  => '0000',
            'order_pay_error' => '',
            'resv1'           => '',
            'fy_ssn'          => '000005319028',
            'md5'             => 'e2e04d46f7c02ef5766bd1573f8c717f'
        ];

        $fuiou->setOptions($sourceData);
        $fuiou->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證時缺少回傳參數(測試缺少:SignMD5info)
     */
    public function testVerifyWithoutSignMD5info()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $fuiou = new Fuiou();
        $fuiou->setPrivateKey('6qbqzfd8zc2ng5topytcbfzjjt4a7g1x');

        $sourceData = [
            'mchnt_cd'        => '0005840F0062251',
            'order_id'        => '201211061512110707',
            'order_date'      => '20121106',
            'order_amt'       => '1',
            'order_st'        => '11',
            'order_pay_code'  => '0000',
            'order_pay_error' => '',
            'resv1'           => '',
            'fy_ssn'          => '000005319028'
        ];

        $fuiou->setOptions($sourceData);
        $fuiou->verifyOrderPayment([]);
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

        $fuiou = new Fuiou();
        $fuiou->setPrivateKey('6qbqzfd8zc2ng5topytcbfzjjt4a7g1x');

        $sourceData = [
            'mchnt_cd'        => '0005840F0062251',
            'order_id'        => '201211061512110707',
            'order_date'      => '20121106',
            'order_amt'       => '1',
            'order_st'        => '11',
            'order_pay_code'  => '0000',
            'order_pay_error' => '',
            'resv1'           => '',
            'fy_ssn'          => '000005319028',
            'md5'             => 'x'
        ];

        $fuiou->setOptions($sourceData);
        $fuiou->verifyOrderPayment([]);
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

        $fuiou = new Fuiou();
        $fuiou->setPrivateKey('6qbqzfd8zc2ng5topytcbfzjjt4a7g1x');

        $SourceData = [
            'mchnt_cd'        => '0005840F0062251',
            'order_id'        => '201211061512110707',
            'order_date'      => '20121106',
            'order_amt'       => '1',
            'order_st'        => '1',
            'order_pay_code'  => '0000',
            'order_pay_error' => '',
            'resv1'           => '',
            'fy_ssn'          => '000005319028',
            'md5'             => 'e2e04d46f7c02ef5766bd1573f8c717f'
        ];

        $fuiou->setOptions($SourceData);
        $fuiou->verifyOrderPayment([]);
    }

    /**
     * 測試線上支付單號不正確的情況
     */
    public function testOrderIdError()
    {
        $this->setExpectedException('BB\DurianBundle\Exception\PaymentException', 'Order Id error', 180061);

        $fuiou = new Fuiou();
        $fuiou->setPrivateKey('6qbqzfd8zc2ng5topytcbfzjjt4a7g1x');

        $sourceData = [
            'mchnt_cd'        => '0005840F0062251',
            'order_id'        => '201211061512110707',
            'order_date'      => '20121106',
            'order_amt'       => '1',
            'order_st'        => '11',
            'order_pay_code'  => '0000',
            'order_pay_error' => '',
            'resv1'           => '',
            'fy_ssn'          => '000005319028',
            'md5'             => 'c0f49dcd6b26d3d7966f3bdaadccd8e0'
        ];

        $entry = ['id' => '19990720'];

        $fuiou->setOptions($sourceData);
        $fuiou->verifyOrderPayment($entry);
    }

    /**
     * 測試線上支付金額不正確的情況
     */
    public function testOrderAmountError()
    {
        $this->setExpectedException('BB\DurianBundle\Exception\PaymentException', 'Order Amount error', 180058);

        $fuiou = new Fuiou();
        $fuiou->setPrivateKey('6qbqzfd8zc2ng5topytcbfzjjt4a7g1x');

        $sourceData = [
            'mchnt_cd'        => '0005840F0062251',
            'order_id'        => '201211061512110707',
            'order_date'      => '20121106',
            'order_amt'       => '1',
            'order_st'        => '11',
            'order_pay_code'  => '0000',
            'order_pay_error' => '',
            'resv1'           => '',
            'fy_ssn'          => '000005319028',
            'md5'             => 'c0f49dcd6b26d3d7966f3bdaadccd8e0'
        ];

        $entry = [
            'id' => '201211061512110707',
            'amount' => '1'
        ];

        $fuiou->setOptions($sourceData);
        $fuiou->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testPaySuccess()
    {
        $fuiou = new Fuiou();
        $fuiou->setPrivateKey('6qbqzfd8zc2ng5topytcbfzjjt4a7g1x');

        $sourceData = [
            'mchnt_cd'        => '0005840F0062251',
            'order_id'        => '201211061512110707',
            'order_date'      => '20121106',
            'order_amt'       => '1',
            'order_st'        => '11',
            'order_pay_code'  => '0000',
            'order_pay_error' => '',
            'resv1'           => '',
            'fy_ssn'          => '000005319028',
            'md5'             => 'c0f49dcd6b26d3d7966f3bdaadccd8e0'
        ];

        $entry = [
            'id' => '201211061512110707',
            'amount' => '0.01'
        ];

        $fuiou->setOptions($sourceData);
        $fuiou->verifyOrderPayment($entry);

        $this->assertEquals('[Succeed]', $fuiou->getMsg());
    }
}

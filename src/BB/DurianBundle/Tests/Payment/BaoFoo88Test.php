<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\BaoFoo88;

class BaoFoo88Test extends DurianTestCase
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

        $baoFoo88 = new BaoFoo88();
        $baoFoo88->getVerifyData();
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

        $baoFoo88 = new BaoFoo88();
        $baoFoo88->setPrivateKey('d330e490553f4f6ea1604856938b43de');

        $sourceData = ['number' => ''];

        $baoFoo88->setOptions($sourceData);
        $baoFoo88->getVerifyData();
    }

    /**
     * 測試加密基本參數設定帶入不支援的銀行
     */
    public function testSetEncodeSourceNotSupportBank()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor is not supported by PaymentGateway',
            180066
        );

        $baoFoo88 = new BaoFoo88();
        $baoFoo88->setPrivateKey('d330e490553f4f6ea1604856938b43de');

        $sourceData = [
            'number' => '3171112543353101',
            'orderId' => '201411141317192331',
            'amount' => '1',
            'notify_url' => 'http://pay.rasfasl.com/pay/pay_response.php',
            'paymentVendorId' => '999',
            'orderCreateDate' => '20141127000020',
        ];

        $baoFoo88->setOptions($sourceData);
        $baoFoo88->getVerifyData();
    }

    /**
     * 測試加密
     */
    public function testGetEncodeData()
    {
        $baoFoo88 = new BaoFoo88();
        $baoFoo88->setPrivateKey('d330e490553f4f6ea1604856938b43de');

        $sourceData = [
            'number' => '3171112543353101',
            'orderId' => '201411141317192331',
            'amount' => '1',
            'notify_url' => 'http://pay.rasfasl.com/pay/pay_response.php',
            'paymentVendorId' => '1',
            'merchantId' => '55555',
            'domain' => '6',
            'orderCreateDate' => '20141114000020',
        ];

        $baoFoo88->setOptions($sourceData);
        $encodeData = $baoFoo88->getVerifyData();

        $this->assertEquals('3171112543353101', $encodeData['Merid']);
        $this->assertEquals('201411141317192331', $encodeData['Billno']);
        $this->assertSame('1.00', $encodeData['Amount']);
        $this->assertEquals('http://pay.rasfasl.com/pay/pay_response.php', $encodeData['Merchanturl']);
        $this->assertEquals('ICBC-NET-B2C', $encodeData['Bankcode']);
        $this->assertEquals('ac16ddc72aa7213e9490c43f8b7d06aa', $encodeData['hmac']);
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

        $baoFoo88 = new BaoFoo88();

        $baoFoo88->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證支付平台缺少回傳參數
     */
    public function testVerifyNoReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $baoFoo88 = new BaoFoo88();
        $baoFoo88->setPrivateKey('d330e490553f4f6ea1604856938b43de');

        $baoFoo88->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證支付平台沒有回傳hmac(加密簽名)
     */
    public function testVerifyWithoutHmac()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $baoFoo88 = new BaoFoo88();
        $baoFoo88->setPrivateKey('d330e490553f4f6ea1604856938b43de');

        $sourceData = [
            'rMercode'   => '3171112543353101',
            'rOrder'     => '201411210000205981',
            'rAmt'       => '0.01',
            'rAttach'    => '',
            'rPamp'      => '',
            'rSucc'      => 'Y',
            'rDate'      => '20141121',
            'rBankorder' => '26112810881411',
            'rBtype'     => '0'
        ];

        $baoFoo88->setOptions($sourceData);
        $baoFoo88->verifyOrderPayment([]);
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

        $baoFoo88 = new BaoFoo88();
        $baoFoo88->setPrivateKey('d330e490553f4f6ea1604856938b43de');

        $sourceData = [
            'rMercode'   => '3171112543353101',
            'rOrder'     => '201411210000205981',
            'rAmt'       => '0.01',
            'rAttach'    => '',
            'rPamp'      => '',
            'rSucc'      => 'Y',
            'rDate'      => '20141121',
            'rBankorder' => '26112810881411',
            'rBtype'     => '0',
            'hcmack'     => '6ac9eb5c9d968dba1b7926a2b5cf9fab'
        ];

        $baoFoo88->setOptions($sourceData);
        $baoFoo88->verifyOrderPayment([]);
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

        $baoFoo88 = new BaoFoo88();
        $baoFoo88->setPrivateKey('d330e490553f4f6ea1604856938b43de');

        $sourceData = [
            'rMercode'   => '3171112543353101',
            'rOrder'     => '201411210000205981',
            'rAmt'       => '0.01',
            'rAttach'    => '',
            'rPamp'      => '',
            'rSucc'      => 'N',
            'rDate'      => '20141121',
            'rBankorder' => '26112810881411',
            'rBtype'     => '0',
            'hcmack'     => 'ea4f839924e7798fcd2a69dc1c77e930'
        ];

        $baoFoo88->setOptions($sourceData);
        $baoFoo88->verifyOrderPayment([]);
    }

    /**
     * 測試線上支付單號不正確的情況
     */
    public function testOrderIdError()
    {
        $this->setExpectedException('BB\DurianBundle\Exception\PaymentException', 'Order Id error', 180061);

        $baoFoo88 = new BaoFoo88();
        $baoFoo88->setPrivateKey('d330e490553f4f6ea1604856938b43de');

        $sourceData = [
            'rMercode'   => '3171112543353101',
            'rOrder'     => '201411210000205981',
            'rAmt'       => '0.01',
            'rAttach'    => '',
            'rPamp'      => '',
            'rSucc'      => 'Y',
            'rDate'      => '20141121',
            'rBankorder' => '26112810881411',
            'rBtype'     => '0',
            'hcmack'     => '2b9fdd4981920dfa66b9f88c1e9bd5f9'
        ];

        $entry = ['id' => '201405020016748610'];

        $baoFoo88->setOptions($sourceData);
        $baoFoo88->verifyOrderPayment($entry);
    }

    /**
     * 測試線上支付金額不正確的情況
     */
    public function testOrderAmountError()
    {
        $this->setExpectedException('BB\DurianBundle\Exception\PaymentException', 'Order Amount error', 180058);

        $baoFoo88 = new BaoFoo88();
        $baoFoo88->setPrivateKey('d330e490553f4f6ea1604856938b43de');

        $sourceData = [
            'rMercode'   => '3171112543353101',
            'rOrder'     => '201411210000205981',
            'rAmt'       => '0.01',
            'rAttach'    => '',
            'rPamp'      => '',
            'rSucc'      => 'Y',
            'rDate'      => '20141121',
            'rBankorder' => '26112810881411',
            'rBtype'     => '0',
            'hcmack'     => '2b9fdd4981920dfa66b9f88c1e9bd5f9'
        ];

        $entry = [
            'id' => '201411210000205981',
            'amount' => '9900.0000'
        ];

        $baoFoo88->setOptions($sourceData);
        $baoFoo88->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testPaySuccess()
    {
        $baoFoo88 = new BaoFoo88();
        $baoFoo88->setPrivateKey('d330e490553f4f6ea1604856938b43de');

        $sourceData = [
            'rMercode'   => '3171112543353101',
            'rOrder'     => '201411210000205981',
            'rAmt'       => '0.01',
            'rAttach'    => '',
            'rPamp'      => '',
            'rSucc'      => 'Y',
            'rDate'      => '20141121',
            'rBankorder' => '26112810881411',
            'rBtype'     => '0',
            'hcmack'     => '2b9fdd4981920dfa66b9f88c1e9bd5f9'
        ];

        $entry = [
            'id' => '201411210000205981',
            'amount' => '0.01'
        ];

        $baoFoo88->setOptions($sourceData);
        $baoFoo88->verifyOrderPayment($entry);

        $this->assertEquals('success', $baoFoo88->getMsg());
    }
}

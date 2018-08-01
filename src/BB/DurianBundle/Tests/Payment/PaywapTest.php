<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\Paywap;

class PaywapTest extends DurianTestCase
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

        $paywap = new Paywap();
        $paywap->getVerifyData();
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

        $paywap = new Paywap();
        $paywap->setPrivateKey('test');
        $paywap->getVerifyData();
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
            'number' => '5010202772',
            'orderId' => '201610180000004708',
            'amount' => '0.01',
            'notify_url' => 'http://two123.comxa.com/',
            'orderCreateDate' => '2016-08-04 12:25:29',
            'paymentVendorId' => '999',
            'username' => 'php1test',
            'ip' => '127.0.0.1',
        ];

        $paywap = new Paywap();
        $paywap->setPrivateKey('test');
        $paywap->setOptions($options);
        $paywap->getVerifyData();
    }

    /**
     * 測試支付
     */
    public function testPay()
    {
        $options = [
            'number' => '5010202772',
            'orderId' => '201610180000004708',
            'amount' => '0.01',
            'notify_url' => 'http://two123.comxa.com/',
            'orderCreateDate' => '2016-08-04 12:25:29',
            'paymentVendorId' => '1',
            'username' => 'php1test',
            'ip' => '127.0.0.1',
        ];

        $paywap = new Paywap();
        $paywap->setPrivateKey('test');
        $paywap->setOptions($options);
        $data = $paywap->getVerifyData();

        $this->assertEquals($options['number'], $data['p1_usercode']);
        $this->assertEquals($options['orderId'], $data['p2_order']);
        $this->assertEquals($options['amount'], $data['p3_money']);
        $this->assertEquals($options['notify_url'], $data['p4_returnurl']);
        $this->assertEquals($options['notify_url'], $data['p5_notifyurl']);
        $this->assertEquals('20160804122529', $data['p6_ordertime']);
        $this->assertEquals('1', $data['p9_paymethod']);
        $this->assertEquals('ICBC', $data['p10_paychannelnum']);
        $this->assertEquals($options['username'], $data['p14_customname']);
        $this->assertEquals('127_0_0_1', $data['p17_customip']);
        $this->assertEquals('1B15BC2482498E89837CF33D34C9239F', $data['p7_sign']);
    }

    /**
     * 測試支付，帶入微信二維
     */
    public function testPayWithWx()
    {
        $options = [
            'number' => '5010202772',
            'orderId' => '201610180000004708',
            'amount' => '0.01',
            'notify_url' => 'http://two123.comxa.com/',
            'orderCreateDate' => '2016-08-04 12:25:29',
            'paymentVendorId' => '1090',
            'username' => 'php1test',
            'ip' => '127.0.0.1',
        ];

        $paywap = new Paywap();
        $paywap->setPrivateKey('test');
        $paywap->setOptions($options);
        $data = $paywap->getVerifyData();

        $this->assertEquals($options['number'], $data['p1_usercode']);
        $this->assertEquals($options['orderId'], $data['p2_order']);
        $this->assertEquals($options['amount'], $data['p3_money']);
        $this->assertEquals($options['notify_url'], $data['p4_returnurl']);
        $this->assertEquals($options['notify_url'], $data['p5_notifyurl']);
        $this->assertEquals('20160804122529', $data['p6_ordertime']);
        $this->assertEquals('', $data['p10_paychannelnum']);
        $this->assertEquals('3', $data['p9_paymethod']);
        $this->assertEquals($options['username'], $data['p14_customname']);
        $this->assertEquals('127_0_0_1', $data['p17_customip']);
        $this->assertEquals('1B15BC2482498E89837CF33D34C9239F', $data['p7_sign']);
    }

    /**
     * 測試支付，帶入支付寶二維
     */
    public function testPayWithZfb()
    {
        $options = [
            'number' => '5010202772',
            'orderId' => '201610180000004708',
            'amount' => '0.01',
            'notify_url' => 'http://two123.comxa.com/',
            'orderCreateDate' => '2016-08-04 12:25:29',
            'paymentVendorId' => '1092',
            'username' => 'php1test',
            'ip' => '127.0.0.1',
        ];

        $paywap = new Paywap();
        $paywap->setPrivateKey('test');
        $paywap->setOptions($options);
        $data = $paywap->getVerifyData();

        $this->assertEquals($options['number'], $data['p1_usercode']);
        $this->assertEquals($options['orderId'], $data['p2_order']);
        $this->assertEquals($options['amount'], $data['p3_money']);
        $this->assertEquals($options['notify_url'], $data['p4_returnurl']);
        $this->assertEquals($options['notify_url'], $data['p5_notifyurl']);
        $this->assertEquals('20160804122529', $data['p6_ordertime']);
        $this->assertEquals('', $data['p10_paychannelnum']);
        $this->assertEquals('4', $data['p9_paymethod']);
        $this->assertEquals($options['username'], $data['p14_customname']);
        $this->assertEquals('127_0_0_1', $data['p17_customip']);
        $this->assertEquals('1B15BC2482498E89837CF33D34C9239F', $data['p7_sign']);
    }

    /**
     * 測試支付，帶入聯通儲值卡
     */
    public function testPayWithUnicomCard()
    {
        $options = [
            'number' => '5010202772',
            'orderId' => '201610180000004708',
            'amount' => '0.01',
            'notify_url' => 'http://two123.comxa.com/',
            'orderCreateDate' => '2016-08-04 12:25:29',
            'paymentVendorId' => '1001',
            'username' => 'php1test',
            'ip' => '127.0.0.1',
        ];

        $paywap = new Paywap();
        $paywap->setPrivateKey('test');
        $paywap->setOptions($options);
        $data = $paywap->getVerifyData();

        $this->assertEquals($options['number'], $data['p1_usercode']);
        $this->assertEquals($options['orderId'], $data['p2_order']);
        $this->assertEquals($options['amount'], $data['p3_money']);
        $this->assertEquals($options['notify_url'], $data['p4_returnurl']);
        $this->assertEquals($options['notify_url'], $data['p5_notifyurl']);
        $this->assertEquals('20160804122529', $data['p6_ordertime']);
        $this->assertEquals('UNICOM', $data['p10_paychannelnum']);
        $this->assertEquals('5', $data['p9_paymethod']);
        $this->assertEquals($options['username'], $data['p14_customname']);
        $this->assertEquals('127_0_0_1', $data['p17_customip']);
        $this->assertEquals('1B15BC2482498E89837CF33D34C9239F', $data['p7_sign']);
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

        $paywap = new Paywap();
        $paywap->verifyOrderPayment([]);
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

        $paywap = new Paywap();
        $paywap->setPrivateKey('test');
        $paywap->verifyOrderPayment([]);
    }

    /**
     * 測試返回時缺少簽名數據(p10_sign)
     */
    public function testReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $options = [
            'p1_usercode' => '5010202772',
            'p2_order' => '201610180000004708',
            'p3_money' => '0.10',
            'p4_status' => '1',
            'p5_payorder' => '50161018162337722223',
            'p6_paymethod' => '1',
            'p7_paychannelnum' => 'ICBC',
            'p8_charset' => 'UTF-8',
            'p9_signtype' => 'MD5',
        ];

        $paywap = new Paywap();
        $paywap->setPrivateKey('test');
        $paywap->setOptions($options);
        $paywap->verifyOrderPayment([]);
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
            'p1_usercode' => '5010202772',
            'p2_order' => '201610180000004708',
            'p3_money' => '0.10',
            'p4_status' => '1',
            'p5_payorder' => '50161018162337722223',
            'p6_paymethod' => '1',
            'p7_paychannelnum' => 'ICBC',
            'p8_charset' => 'UTF-8',
            'p9_signtype' => 'MD5',
            'p10_sign' => '123456789',
        ];


        $paywap = new Paywap();
        $paywap->setPrivateKey('test');
        $paywap->setOptions($options);
        $paywap->verifyOrderPayment([]);
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
            'p1_usercode' => '5010202772',
            'p2_order' => '201610180000004708',
            'p3_money' => '0.10',
            'p4_status' => '3',
            'p5_payorder' => '50161018162337722223',
            'p6_paymethod' => '1',
            'p7_paychannelnum' => 'ICBC',
            'p8_charset' => 'UTF-8',
            'p9_signtype' => 'MD5',
            'p10_sign' => '32CABBD6F2A763A25A237FEAAF30634F',
        ];

        $paywap = new Paywap();
        $paywap->setPrivateKey('test');
        $paywap->setOptions($options);
        $paywap->verifyOrderPayment([]);
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
            'p1_usercode' => '5010202772',
            'p2_order' => '201610180000004708',
            'p3_money' => '0.10',
            'p4_status' => '1',
            'p5_payorder' => '50161018162337722223',
            'p6_paymethod' => '1',
            'p7_paychannelnum' => 'ICBC',
            'p8_charset' => 'UTF-8',
            'p9_signtype' => 'MD5',
            'p10_sign' => '5B62687C64435686DFD5706824CAEEA1',
        ];

        $entry = ['id' => '201509140000002475'];

        $paywap = new Paywap();
        $paywap->setPrivateKey('test');
        $paywap->setOptions($options);
        $paywap->verifyOrderPayment($entry);
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
            'p1_usercode' => '5010202772',
            'p2_order' => '201610180000004708',
            'p3_money' => '0.10',
            'p4_status' => '1',
            'p5_payorder' => '50161018162337722223',
            'p6_paymethod' => '1',
            'p7_paychannelnum' => 'ICBC',
            'p8_charset' => 'UTF-8',
            'p9_signtype' => 'MD5',
            'p10_sign' => '5B62687C64435686DFD5706824CAEEA1',
        ];

        $entry = [
            'id' => '201610180000004708',
            'amount' => '15.00',
        ];

        $paywap = new Paywap();
        $paywap->setPrivateKey('test');
        $paywap->setOptions($options);
        $paywap->verifyOrderPayment($entry);
    }

    /**
     * 測試返回時缺少銀行代碼(p7_paychannelnum)
     */
    public function testReturnWithoutPayChannelNum()
    {
        $options = [
            'p1_usercode' => '5010202772',
            'p2_order' => '201610180000004708',
            'p3_money' => '0.10',
            'p4_status' => '1',
            'p5_payorder' => '50161018162337722223',
            'p6_paymethod' => '1',
            'p8_charset' => 'UTF-8',
            'p9_signtype' => 'MD5',
            'p10_sign' => '2D1C8924FD771AF97B33FF24647F5964',
        ];

        $entry = [
            'id' => '201610180000004708',
            'amount' => '0.10',
        ];

        $paywap = new Paywap();
        $paywap->setPrivateKey('ad9f7aac858148c197997b3f7e80db44');
        $paywap->setOptions($options);
        $paywap->verifyOrderPayment($entry);

        $this->assertEquals('success', $paywap->getMsg());
    }

    /**
     * 測試返回結果
     */
    public function testReturnOrder()
    {
        $options = [
            'p1_usercode' => '5010202772',
            'p2_order' => '201610180000004708',
            'p3_money' => '0.10',
            'p4_status' => '1',
            'p5_payorder' => '50161018162337722223',
            'p6_paymethod' => '1',
            'p7_paychannelnum' => 'ICBC',
            'p8_charset' => 'UTF-8',
            'p9_signtype' => 'MD5',
            'p10_sign' => '8FB5197A3ED4747D7CF2BC7EF0ED798A',
        ];

        $entry = [
            'id' => '201610180000004708',
            'amount' => '0.10',
        ];

        $paywap = new Paywap();
        $paywap->setPrivateKey('ad9f7aac858148c197997b3f7e80db44');
        $paywap->setOptions($options);
        $paywap->verifyOrderPayment($entry);

        $this->assertEquals('success', $paywap->getMsg());
    }
}

<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\Rijietong;

class RijietongTest extends DurianTestCase
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

        $rijietong = new Rijietong();
        $rijietong->getVerifyData();
    }

    /**
     * 測試加密基本參數設定未指定支付參數
     */
    public function testSetEncodeSourceNoNumber()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $rijietong = new Rijietong();
        $rijietong->setPrivateKey('Xke1a0TqZE2Q8ku943LgC425247y3zuu09vLh78S4U0142rp60h3s6egNY3j');

        $sourceData = ['number' => ''];

        $rijietong->setOptions($sourceData);
        $rijietong->getVerifyData();
    }

    /**
     * 測試加密基本參數設定沒有帶入merchantId的情況
     */
    public function testSetEncodeSourceNoMerchantId()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $rijietong = new Rijietong();
        $rijietong->setPrivateKey('Xke1a0TqZE2Q8ku943LgC425247y3zuu09vLh78S4U0142rp60h3s6egNY3j');

        $sourceData = [
            'number' => '10012150139',
            'orderId' => '201405120018316114',
            'amount' => '1',
            'notify_url' => 'http://pay.rasfasl.com/pay/pay_response.php?pay_system=48542&hallid=206',
            'paymentVendorId' => '1',
            'merchantId' => '',
            'paymentGatewayId' => '63'
        ];

        $rijietong->setOptions($sourceData);
        $rijietong->getVerifyData();
    }

    /**
     * 測試加密時廳為空字串的情況
     */
    public function testEncodeWithDomainEmpty()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $rijietong = new Rijietong();
        $rijietong->setPrivateKey('Xke1a0TqZE2Q8ku943LgC425247y3zuu09vLh78S4U0142rp60h3s6egNY3j');

        $sourceData = [
            'number' => '10012150139',
            'orderId' => '201405120018316114',
            'amount' => '1',
            'notify_url' => 'http://pay.rasfasl.com/pay/pay_response.php',
            'paymentVendorId' => '1',
            'merchantId' => '1',
            'domain' => '',
            'paymentGatewayId' => '63'
        ];

        $rijietong->setOptions($sourceData);
        $rijietong->getVerifyData();
    }

    /**
     * 測試加密時代入支付平台不支援的銀行
     */
    public function testSetEncodeSourceUnsupportedPaymentVendor()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'PaymentVendor not support by PaymentGateway',
            180066
        );

        $rijietong = new Rijietong();
        $rijietong->setPrivateKey('Xke1a0TqZE2Q8ku943LgC425247y3zuu09vLh78S4U0142rp60h3s6egNY3j');

        $sourceData = [
            'number' => '10012150139',
            'orderId' => '201405120018316114',
            'amount' => '1',
            'notify_url' => 'http://pay.rasfasl.com/pay/pay_response.php?pay_system=48542&hallid=206',
            'paymentVendorId' => '18',
            'merchantId' => '1',
            'domain' => '1',
            'paymentGatewayId' => '63'
        ];

        $rijietong->setOptions($sourceData);
        $rijietong->getVerifyData();
    }

    /**
     * 測試加密時PrivateKey長度超過64
     */
    public function testGetEncodeDataWithPrivateKeyLength()
    {
        $rijietong = new Rijietong();
        $rijietong->setPrivateKey('Xke1a0TqZE2Q8ku943LgC425247y3zuu09vLh78S4U0142rp60h3s6egNY3j12345');

        $sourceData = [
            'number' => '10012150139',
            'orderId' => '201405120018316114',
            'amount' => '1',
            'notify_url' => 'http://pay.rasfasl.com/pay/pay_response.php?pay_system=48542&hallid=206',
            'paymentVendorId' => '1',
            'merchantId' => '1',
            'domain' => '1',
            'paymentGatewayId' => '63'
        ];

        $rijietong->setOptions($sourceData);
        $rijietong->getVerifyData();
    }

    /**
     * 測試加密
     */
    public function testGetEncodeData()
    {
        $sourceData = [
            'number' => '10012150139',
            'orderId' => '201405120018316114',
            'amount' => '1',
            'notify_url' => 'http://esball.org/app/member/pay_online2/pay_result.php',
            'paymentVendorId' => '1',
            'merchantId' => '1',
            'domain' => '1',
            'paymentGatewayId' => '63'
        ];

        $rijietong = new Rijietong();
        $rijietong->setPrivateKey('Xke1a0TqZE2Q8ku943LgC425247y3zuu09vLh78S4U0142rp60h3s6egNY3j');
        $rijietong->setOptions($sourceData);
        $encodeData = $rijietong->getVerifyData();

        $notifyUrl = sprintf(
            '%s?payment_id=%s',
            $sourceData['notify_url'],
            $sourceData['paymentGatewayId']
        );

        $this->assertEquals($sourceData['number'], $encodeData['p1_MerId']);
        $this->assertSame('1.00', $encodeData['p3_Amt']);
        $this->assertEquals($sourceData['orderId'], $encodeData['p2_Order']);
        $this->assertEquals($notifyUrl, $encodeData['p8_Url']);
        $this->assertEquals('ICBC-NET-B2C', $encodeData['pd_FrpId']);
        $this->assertEquals($sourceData['merchantId'].'_'.$sourceData['domain'], $encodeData['pa_MP']);
        $this->assertEquals('71ca8e435d2c1f87b29b5afc36ac0e5c', $encodeData['hmac']);
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

        $rijietong = new Rijietong();

        $rijietong->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證支付平台未指定返回參數
     */
    public function testVerifyWithNoReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $rijietong = new Rijietong();
        $rijietong->setPrivateKey('Xke1a0TqZE2Q8ku943LgC425247y3zuu09vLh78S4U0142rp60h3s6egNY3j');

        $sourceData = [
            'p1_MerId' => '10012150139',
            'r0_Cmd' => 'Buy',
            'r1_Code' => '1',
            'r2_TrxId' => '914292209794231I',
            'r4_Cur' => 'RMB',
            'r5_Pid' => '-',
            'r6_Order' => '201405120018316114',
            'r7_Uid' => '',
            'r8_MP' => 'php1test',
            'r9_BType' => '2',
            'hmac' => '1c2b4504c80cf5cfc76c1ba05531e50a'
        ];

        $rijietong->setOptions($sourceData);
        $rijietong->verifyOrderPayment([]);
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

        $rijietong = new Rijietong();
        $rijietong->setPrivateKey('Xke1a0TqZE2Q8ku943LgC425247y3zuu09vLh78S4U0142rp60h3s6egNY3j');

        $sourceData = [
            'p1_MerId' => '10012150139',
            'r0_Cmd' => 'Buy',
            'r1_Code' => '1',
            'r2_TrxId' => '914292209794231I',
            'r3_Amt' => '1.0',
            'r4_Cur' => 'RMB',
            'r5_Pid' => '-',
            'r6_Order' => '201405120018316114',
            'r7_Uid' => '',
            'r8_MP' => 'php1test',
            'r9_BType' => '2'
        ];

        $rijietong->setOptions($sourceData);
        $rijietong->verifyOrderPayment([]);
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

        $rijietong = new Rijietong();
        $rijietong->setPrivateKey('Xke1a0TqZE2Q8ku943LgC425247y3zuu09vLh78S4U0142rp60h3s6egNY3j');

        $sourceData = [
            'p1_MerId' => '10012150139',
            'r0_Cmd' => 'Buy',
            'r1_Code' => '1',
            'r2_TrxId' => '914292209794231I',
            'r3_Amt' => '1.0',
            'r4_Cur' => 'RMB',
            'r5_Pid' => '-',
            'r6_Order' => '201405120018316114',
            'r7_Uid' => '',
            'r8_MP' => 'php1test',
            'r9_BType' => '2',
            'hmac' => 'x'
        ];

        $rijietong->setOptions($sourceData);
        $rijietong->verifyOrderPayment([]);
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

        $rijietong = new Rijietong();
        $rijietong->setPrivateKey('Xke1a0TqZE2Q8ku943LgC425247y3zuu09vLh78S4U0142rp60h3s6egNY3j');

        $sourceData = [
            'p1_MerId' => '10012150139',
            'r0_Cmd' => 'Buy',
            'r1_Code' => '0',
            'r2_TrxId' => '914292209794231I',
            'r3_Amt' => '1.0',
            'r4_Cur' => 'RMB',
            'r5_Pid' => '-',
            'r6_Order' => '201405120018316114',
            'r7_Uid' => '',
            'r8_MP' => 'php1test',
            'r9_BType' => '2',
            'hmac' => '9317771b1468a9856f242229ab2001d7'
        ];

        $rijietong->setOptions($sourceData);
        $rijietong->verifyOrderPayment([]);
    }

    /**
     * 測試線上支付單號不正確的情況
     */
    public function testOrderIdError()
    {
        $this->setExpectedException('BB\DurianBundle\Exception\PaymentException', 'Order Id error', 180061);

        $rijietong = new Rijietong();
        $rijietong->setPrivateKey('Xke1a0TqZE2Q8ku943LgC425247y3zuu09vLh78S4U0142rp60h3s6egNY3j');

        $sourceData = [
            'p1_MerId' => '10012150139',
            'r0_Cmd' => 'Buy',
            'r1_Code' => '1',
            'r2_TrxId' => '914292209794231I',
            'r3_Amt' => '1.0',
            'r4_Cur' => 'RMB',
            'r5_Pid' => '-',
            'r6_Order' => '201405120018316114',
            'r7_Uid' => '',
            'r8_MP' => 'php1test',
            'r9_BType' => '2',
            'hmac' => '1c2b4504c80cf5cfc76c1ba05531e50a'
        ];

        $entry = ['id' => '201405020016748610'];

        $rijietong->setOptions($sourceData);
        $rijietong->verifyOrderPayment($entry);
    }

    /**
     * 測試線上支付金額不正確的情況
     */
    public function testOrderAmountError()
    {
        $this->setExpectedException('BB\DurianBundle\Exception\PaymentException', 'Order Amount error', 180058);

        $rijietong = new Rijietong();
        $rijietong->setPrivateKey('Xke1a0TqZE2Q8ku943LgC425247y3zuu09vLh78S4U0142rp60h3s6egNY3j');

        $sourceData = [
            'p1_MerId' => '10012150139',
            'r0_Cmd' => 'Buy',
            'r1_Code' => '1',
            'r2_TrxId' => '914292209794231I',
            'r3_Amt' => '1.0',
            'r4_Cur' => 'RMB',
            'r5_Pid' => '-',
            'r6_Order' => '201405120018316114',
            'r7_Uid' => '',
            'r8_MP' => 'php1test',
            'r9_BType' => '2',
            'hmac' => '1c2b4504c80cf5cfc76c1ba05531e50a'
        ];

        $entry = [
            'id' => '201405120018316114',
            'amount' => '9900.0000'
        ];

        $rijietong->setOptions($sourceData);
        $rijietong->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testPaySuccess()
    {
        $rijietong = new Rijietong();
        $rijietong->setPrivateKey('Xke1a0TqZE2Q8ku943LgC425247y3zuu09vLh78S4U0142rp60h3s6egNY3j');

        $sourceData = [
            'p1_MerId' => '10012150139',
            'r0_Cmd' => 'Buy',
            'r1_Code' => '1',
            'r2_TrxId' => '914292209794231I',
            'r3_Amt' => '1.0',
            'r4_Cur' => 'RMB',
            'r5_Pid' => '-',
            'r6_Order' => '201405120018316114',
            'r7_Uid' => '',
            'r8_MP' => 'php1test',
            'r9_BType' => '2',
            'hmac' => '1c2b4504c80cf5cfc76c1ba05531e50a'
        ];

        $entry = [
            'id' => '201405120018316114',
            'amount' => '1.0000'
        ];

        $rijietong->setOptions($sourceData);
        $rijietong->verifyOrderPayment($entry);

        $this->assertEquals('success', $rijietong->getMsg());
    }
}

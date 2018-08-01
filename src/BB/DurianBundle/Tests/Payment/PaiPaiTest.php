<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\PaiPai;

class PaiPaiTest extends DurianTestCase
{
    /**
     * 測試支付加密時沒有帶入privateKey的情況
     */
    public function testPayWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $paiPai = new PaiPai();
        $paiPai->getVerifyData();
    }

    /**
     * 測試支付加密時未指定支付參數
     */
    public function testPayWithNoPayParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No pay parameter specified',
            180145
        );

        $sourceData = ['number' => ''];

        $paiPai = new PaiPai();
        $paiPai->setPrivateKey('1234');
        $paiPai->setOptions($sourceData);
        $paiPai->getVerifyData();
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

        $sourceData = [
            'number' => '1234',
            'paymentVendorId' => '999',
            'amount' => '0.01',
            'orderId' => '201801260000008688',
            'notify_url' => 'http://two123.comuv.com',
            'orderCreateDate' => '2018-01-26 12:45:00',
        ];

        $paiPai = new PaiPai();
        $paiPai->setPrivateKey('1234');
        $paiPai->setOptions($sourceData);
        $paiPai->getVerifyData();
    }

    /**
     * 測試支付加密
     */
    public function testPay()
    {
        $sourceData = [
            'number' => '1234',
            'paymentVendorId' => '1',
            'amount' => '0.01',
            'orderId' => '201801260000008688',
            'notify_url' => 'http://two123.comuv.com',
            'orderCreateDate' => '2018-01-26 12:45:00',
        ];

        $paiPai = new PaiPai();
        $paiPai->setPrivateKey('1234');
        $paiPai->setOptions($sourceData);
        $encodeData = $paiPai->getVerifyData();

        $this->assertEquals('vb1.0', $encodeData['pay_version']);
        $this->assertEquals($sourceData['number'], $encodeData['pay_memberid']);
        $this->assertEquals($sourceData['orderId'], $encodeData['pay_orderid']);
        $this->assertEquals('20180126124500', $encodeData['pay_applydate']);
        $this->assertEquals('967', $encodeData['pay_bankcode']);
        $this->assertEquals($sourceData['notify_url'], $encodeData['pay_notifyurl']);
        $this->assertEquals($sourceData['amount'], $encodeData['pay_amount']);
        $this->assertEquals('168ae64775453c4f9797262a01fec4f0', $encodeData['pay_md5sign']);
    }

    /**
     * 測試返回時基本參數設定沒有帶入key的情況
     */
    public function testReturnWithoutPrivateKey()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No privateKey specified',
            180142
        );

        $paiPai = new PaiPai();
        $paiPai->verifyOrderPayment([]);
    }

    /**
     * 測試返回時支付平台未指定返回參數
     */
    public function testReturnWithNoReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $sourceData = [
            'mId' => '50009',
            'orderNumber' => '201801260000008688',
        ];

        $paiPai = new PaiPai();
        $paiPai->setPrivateKey('1234');
        $paiPai->setOptions($sourceData);
        $paiPai->verifyOrderPayment([]);
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

        $sourceData = [
            'orderid' => '201801310000008779',
            'opstate' => '0',
            'ovalue' => '1.00',
            'sysorderid' => 'pos1801311028088404',
            'systime' => '2018/01/31 10:30:05',
            'attach' => '',
            'msg' => '',
        ];

        $paiPai = new PaiPai();
        $paiPai->setPrivateKey('1234');
        $paiPai->setOptions($sourceData);
        $paiPai->verifyOrderPayment([]);
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
            'orderid' => '201801310000008779',
            'opstate' => '0',
            'ovalue' => '1.00',
            'sysorderid' => 'pos1801311028088404',
            'systime' => '2018/01/31 10:30:05',
            'attach' => '',
            'msg' => '',
            'sign' => '6e1d8920b2be02a505466e4f083f20ba',
        ];

        $paiPai = new PaiPai();
        $paiPai->setPrivateKey('1234');
        $paiPai->setOptions($sourceData);
        $paiPai->verifyOrderPayment([]);
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
            'orderid' => '201801310000008779',
            'opstate' => '5',
            'ovalue' => '1.00',
            'sysorderid' => 'pos1801311028088404',
            'systime' => '2018/01/31 10:30:05',
            'attach' => '',
            'msg' => '',
            'sign' => '1c41d226385b2f8a1deeb6108b253e08',
        ];

        $paiPai = new PaiPai();
        $paiPai->setPrivateKey('1234');
        $paiPai->setOptions($sourceData);
        $paiPai->verifyOrderPayment([]);
    }

    /**
     * 測試返回支付單號不正確的情況
     */
    public function testReturnOrderIdError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Id error',
            180061
        );

        $sourceData = [
            'orderid' => '201801310000008779',
            'opstate' => '0',
            'ovalue' => '1.00',
            'sysorderid' => 'pos1801311028088404',
            'systime' => '2018/01/31 10:30:05',
            'attach' => '',
            'msg' => '',
            'sign' => '9104253e4b1a2cddb8149074c9b28850',
        ];

        $entry = ['id' => '201606220000002806'];

        $paiPai = new PaiPai();
        $paiPai->setPrivateKey('1234');
        $paiPai->setOptions($sourceData);
        $paiPai->verifyOrderPayment($entry);
    }

    /**
     * 測試返回支付金額不正確的情況
     */
    public function testReturnOrderAmountError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'Order Amount error',
            180058
        );

        $sourceData = [
            'orderid' => '201801310000008779',
            'opstate' => '0',
            'ovalue' => '1.00',
            'sysorderid' => 'pos1801311028088404',
            'systime' => '2018/01/31 10:30:05',
            'attach' => '',
            'msg' => '',
            'sign' => '9104253e4b1a2cddb8149074c9b28850',
        ];

        $entry = [
            'id' => '201801310000008779',
            'amount' => '1.10',
        ];

        $paiPai = new PaiPai();
        $paiPai->setPrivateKey('1234');
        $paiPai->setOptions($sourceData);
        $paiPai->verifyOrderPayment($entry);
    }

    /**
     * 測試返回驗證成功
     */
    public function testReturnSuccess()
    {
        $sourceData = [
            'orderid' => '201801310000008779',
            'opstate' => '0',
            'ovalue' => '1.00',
            'sysorderid' => 'pos1801311028088404',
            'systime' => '2018/01/31 10:30:05',
            'attach' => '',
            'msg' => '',
            'sign' => '9104253e4b1a2cddb8149074c9b28850',
        ];

        $entry = [
            'id' => '201801310000008779',
            'amount' => '1.00',
        ];

        $paiPai = new PaiPai();
        $paiPai->setPrivateKey('1234');
        $paiPai->setOptions($sourceData);
        $paiPai->verifyOrderPayment($entry);

        $this->assertEquals('SUCCESS', $paiPai->getMsg());
    }
}

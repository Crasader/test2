<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\Betwinpay;

class BetwinpayTest extends DurianTestCase
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

        $betwinpay = new Betwinpay();
        $betwinpay->getVerifyData();
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

        $betwinpay = new Betwinpay();
        $betwinpay->setPrivateKey('1234');

        $sourceData = ['number' => ''];

        $betwinpay->setOptions($sourceData);
        $betwinpay->getVerifyData();
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

        $betwinpay = new Betwinpay();
        $betwinpay->setPrivateKey('1234');

        $sourceData = [
            'number' => '2000',
            'notify_url' => 'http://www.yousite.com/result.asp',
            'orderId' => '20140423000001234',
            'amount' => '10',
            'orderCreateDate' => '2014-04-24 18:18:59',
            'paymentVendorId' => '999',
            'merchantId' => '12345',
            'domain' => '6',
        ];

        $betwinpay->setOptions($sourceData);
        $betwinpay->getVerifyData();
    }

    /**
     * 測試加密
     */
    public function testGetEncodeData()
    {
        $sourceData = [
            'number' => '2000',
            'notify_url' => 'http://esball.org/app/member/pay_online2/pay_result.php',
            'orderId' => '20140423000001234',
            'amount' => '10',
            'orderCreateDate' => '2014-04-24 18:18:59',
            'paymentVendorId' => '1',
            'merchantId' => '12345',
            'domain' => '6',
        ];

        $betwinpay = new Betwinpay();
        $betwinpay->setPrivateKey('1234');
        $betwinpay->setOptions($sourceData);
        $encodeData = $betwinpay->getVerifyData();

        $notifyUrl = sprintf(
            '%s?pay_system=%s&hallid=%s',
            $sourceData['notify_url'],
            $sourceData['merchantId'],
            $sourceData['domain']
        );

        $this->assertEquals($sourceData['number'], $encodeData['agentId']);
        $this->assertEquals($sourceData['orderId'], $encodeData['orderNo']);
        $this->assertEquals($sourceData['amount'] * 100, $encodeData['orderAmount']);
        $this->assertEquals('20140424181859', $encodeData['orderDatetime']);
        $this->assertEquals($notifyUrl, $encodeData['receiveUrl']);
        $this->assertEquals('', $encodeData['pickupUrl']);
        $this->assertEquals('icbc', $encodeData['issuerId']);
        $this->assertEquals('91b23e347a39a147c197496b25d12dd7', $encodeData['signMsg']);
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

        $betwinpay = new Betwinpay();

        $betwinpay->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證支付平台沒有回傳參數
     */
    public function testSetDecodeSourceNoReturnParameterSpecified()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $betwinpay = new Betwinpay();
        $betwinpay->setPrivateKey('1234');

        $sourceData = [
            'agentId'        => '2000',
            'payType'        => '1',
            'issuerId'       => 'icbc',
            'paymentOrderId' => '10002123154612',
            'orderNo'        => '20140423000001234',
            'orderDatetime'  => '20140424181859',
            'orderAmount'    => '10000',
            'payDatetime'    => '20140424181859',
            'payAmount'      => '10000',
            'ext1'           => '',
            'ext2'           => '',
            'errorCode'      => '',
            'returnDatetime' => '20140424190012',
            'signMsg'        => 'df10effd564ac4e8578138fc55b3cea9'
        ];

        $betwinpay->setOptions($sourceData);
        $betwinpay->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證支付平台沒有回傳signMsg(加密簽名)
     */
    public function testSetDecodeSourceWithoutSignMsg()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $betwinpay = new Betwinpay();
        $betwinpay->setPrivateKey('1234');

        $sourceData = [
            'agentId'        => '2000',
            'payType'        => '1',
            'issuerId'       => 'icbc',
            'paymentOrderId' => '10002123154612',
            'orderNo'        => '20140423000001234',
            'orderDatetime'  => '20140424181859',
            'orderAmount'    => '10000',
            'payDatetime'    => '20140424181859',
            'payAmount'      => '10000',
            'ext1'           => '',
            'ext2'           => '',
            'payResult'      => '1',
            'errorCode'      => '',
            'returnDatetime' => '20140424190012'
        ];

        $betwinpay->setOptions($sourceData);
        $betwinpay->verifyOrderPayment([]);
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

        $betwinpay = new Betwinpay();
        $betwinpay->setPrivateKey('1234');

        $sourceData = [
            'agentId'        => '2000',
            'payType'        => '1',
            'issuerId'       => 'icbc',
            'paymentOrderId' => '10002123154612',
            'orderNo'        => '20140423000001234',
            'orderDatetime'  => '20140424181859',
            'orderAmount'    => '10000',
            'payDatetime'    => '20140424181859',
            'payAmount'      => '10000',
            'ext1'           => '',
            'ext2'           => '',
            'payResult'      => '1',
            'errorCode'      => '',
            'returnDatetime' => '20140424190012',
            'signMsg'        => 'df10effd564ac4e8578138fc55b3cea9'
        ];

        $betwinpay->setOptions($sourceData);
        $betwinpay->verifyOrderPayment([]);
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

        $betwinpay = new Betwinpay();
        $betwinpay->setPrivateKey('1234');

        $sourceData = [
            'agentId'        => '2000',
            'payType'        => '1',
            'issuerId'       => 'icbc',
            'paymentOrderId' => '10002123154612',
            'orderNo'        => '20140423000001234',
            'orderDatetime'  => '20140424181859',
            'orderAmount'    => '10000',
            'payDatetime'    => '20140424181859',
            'payAmount'      => '10000',
            'ext1'           => '',
            'ext2'           => '',
            'payResult'      => '0',
            'errorCode'      => '',
            'returnDatetime' => '20140424190012',
            'signMsg'        => 'f960d4c81ae53ad75e341e8b071a62fe'
        ];

        $betwinpay->setOptions($sourceData);
        $betwinpay->verifyOrderPayment([]);
    }

    /**
     * 測試線上支付單號不正確的情況
     */
    public function testOrderIdError()
    {
        $this->setExpectedException('BB\DurianBundle\Exception\PaymentException', 'Order Id error', 180061);

        $betwinpay = new Betwinpay();
        $betwinpay->setPrivateKey('1234');

        $sourceData = [
            'agentId'        => '2000',
            'payType'        => '1',
            'issuerId'       => 'icbc',
            'paymentOrderId' => '10002123154612',
            'orderNo'        => '20140423000001234',
            'orderDatetime'  => '20140424181859',
            'orderAmount'    => '10000',
            'payDatetime'    => '20140424181859',
            'payAmount'      => '10000',
            'ext1'           => '',
            'ext2'           => '',
            'payResult'      => '1',
            'errorCode'      => '',
            'returnDatetime' => '20140424190012',
            'signMsg'        => '4550a4fa39f4bb1fed756c1f4db74ea1'
        ];

        $entry = ['id' => '19990720'];

        $betwinpay->setOptions($sourceData);
        $betwinpay->verifyOrderPayment($entry);
    }

    /**
     * 測試線上支付金額不正確的情況
     */
    public function testOrderAmountError()
    {
        $this->setExpectedException('BB\DurianBundle\Exception\PaymentException', 'Order Amount error', 180058);

        $betwinpay = new Betwinpay();
        $betwinpay->setPrivateKey('1234');

        $sourceData = [
            'agentId'        => '2000',
            'payType'        => '1',
            'issuerId'       => 'icbc',
            'paymentOrderId' => '10002123154612',
            'orderNo'        => '20140423000001234',
            'orderDatetime'  => '20140424181859',
            'orderAmount'    => '10000',
            'payDatetime'    => '20140424181859',
            'payAmount'      => '10000',
            'ext1'           => '',
            'ext2'           => '',
            'payResult'      => '1',
            'errorCode'      => '',
            'returnDatetime' => '20140424190012',
            'signMsg'        => '4550a4fa39f4bb1fed756c1f4db74ea1'
        ];

        $entry = [
            'id' => '20140423000001234',
            'amount' => '1000.0000'
        ];

        $betwinpay->setOptions($sourceData);
        $betwinpay->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testPaySuccess()
    {
        $betwinpay = new Betwinpay();
        $betwinpay->setPrivateKey('1234');

        $sourceData = [
            'agentId'        => '2000',
            'payType'        => '1',
            'issuerId'       => 'icbc',
            'paymentOrderId' => '10002123154612',
            'orderNo'        => '20140423000001234',
            'orderDatetime'  => '20140424181859',
            'orderAmount'    => '10000',
            'payDatetime'    => '20140424181859',
            'payAmount'      => '10000',
            'ext1'           => '',
            'ext2'           => '',
            'payResult'      => '1',
            'errorCode'      => '',
            'returnDatetime' => '20140424190012',
            'signMsg'        => '4550a4fa39f4bb1fed756c1f4db74ea1'
        ];

        $entry = [
            'id' => '20140423000001234',
            'amount' => '100.0000'
        ];

        $betwinpay->setOptions($sourceData);
        $betwinpay->verifyOrderPayment($entry);

        $this->assertEquals('success', $betwinpay->getMsg());
    }
}

<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\XinHuiHe;

class XinHuiHeTest extends DurianTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->sourceData = [
            'number' => 'b160b69a6e04460c8787f08c81c7969a',
            'amount' => '1',
            'orderId' => '201806140000011648',
            'paymentVendorId' => '1111',
            'orderCreateDate' => '2018-06-14 19:10:11',
            'ip' => '192.168.101.1',
            'notify_url' => 'http://handsomeGuy.php',
            'postUrl' => 'https://gateway.huihezhifu.com',
        ];

        $this->returnResult = [
            'orderPrice' => '1.00',
            'orderTime' => '20180614191202',
            'outTradeNo' => '201806140000011648',
            'payKey' => 'b160b69a6e04460c8787f08c81c7969a',
            'productName' => '201806140000011648',
            'productType' => '60000104',
            'successTime' => '20180614191355',
            'tradeStatus' => 'SUCCESS',
            'trxNo' => 'P77772018061410134538',
            'sign' => '71ed09e1e617f05ee644071424162ef6',
        ];
    }

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

        $xinHuiHe = new XinHuiHe();
        $xinHuiHe->getVerifyData();
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

        $xinHuiHe = new XinHuiHe();
        $xinHuiHe->setPrivateKey('test');
        $xinHuiHe->getVerifyData();
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

        $this->sourceData['paymentVendorId'] = '66666';

        $xinHuiHe = new XinHuiHe();
        $xinHuiHe->setPrivateKey('test');
        $xinHuiHe->setOptions($this->sourceData);
        $xinHuiHe->getVerifyData();
    }

    /**
     * 測試二維支付
     */
    public function testQrcodePay()
    {
        $xinHuiHe = new XinHuiHe();
        $xinHuiHe->setPrivateKey('test');
        $xinHuiHe->setOptions($this->sourceData);
        $verifyData = $xinHuiHe->getVerifyData();

        $this->assertEquals('https://gateway.huihezhifu.com/gateWayApi/pay', $verifyData['post_url']);
        $this->assertEquals('b160b69a6e04460c8787f08c81c7969a', $verifyData['params']['payKey']);
        $this->assertEquals('1.00', $verifyData['params']['orderPrice']);
        $this->assertEquals('201806140000011648', $verifyData['params']['outTradeNo']);
        $this->assertEquals('60000104', $verifyData['params']['productType']);
        $this->assertEquals('20180614191011', $verifyData['params']['orderTime']);
        $this->assertEquals('201806140000011648', $verifyData['params']['productName']);
        $this->assertEquals('192.168.101.1', $verifyData['params']['orderIp']);
        $this->assertEquals('http://handsomeGuy.php', $verifyData['params']['returnUrl']);
        $this->assertEquals('http://handsomeGuy.php', $verifyData['params']['notifyUrl']);
        $this->assertEquals('9FA5C7F7B96B8EE87ED751F2DC2AFDFE', $verifyData['params']['sign']);
        $this->assertEquals('', $verifyData['params']['remark']);
    }

    /**
     * 測試網銀支付
     */
    public function testPay()
    {
        $this->sourceData['paymentVendorId'] = '1102';

        $xinHuiHe = new XinHuiHe();
        $xinHuiHe->setPrivateKey('test');
        $xinHuiHe->setOptions($this->sourceData);
        $verifyData = $xinHuiHe->getVerifyData();

        $this->assertEquals('https://gateway.huihezhifu.com/netPayApi/pay', $verifyData['post_url']);
        $this->assertEquals('b160b69a6e04460c8787f08c81c7969a', $verifyData['params']['payKey']);
        $this->assertEquals('1.00', $verifyData['params']['orderPrice']);
        $this->assertEquals('201806140000011648', $verifyData['params']['outTradeNo']);
        $this->assertEquals('50000103', $verifyData['params']['productType']);
        $this->assertEquals('20180614191011', $verifyData['params']['orderTime']);
        $this->assertEquals('201806140000011648', $verifyData['params']['productName']);
        $this->assertEquals('192.168.101.1', $verifyData['params']['orderIp']);
        $this->assertEquals('http://handsomeGuy.php', $verifyData['params']['returnUrl']);
        $this->assertEquals('http://handsomeGuy.php', $verifyData['params']['notifyUrl']);
        $this->assertEquals('D6971747813964AD1BB16D410326DE43', $verifyData['params']['sign']);
        $this->assertEquals('', $verifyData['params']['remark']);
    }

    /**
     * 測試手機支付
     */
    public function testPhonePay()
    {
        $this->sourceData['paymentVendorId'] = '1098';

        $xinHuiHe = new XinHuiHe();
        $xinHuiHe->setPrivateKey('test');
        $xinHuiHe->setOptions($this->sourceData);
        $verifyData = $xinHuiHe->getVerifyData();

        $this->assertEquals('https://gateway.huihezhifu.com/gateWayApi/pay', $verifyData['post_url']);
        $this->assertEquals('b160b69a6e04460c8787f08c81c7969a', $verifyData['params']['payKey']);
        $this->assertEquals('1.00', $verifyData['params']['orderPrice']);
        $this->assertEquals('201806140000011648', $verifyData['params']['outTradeNo']);
        $this->assertEquals('20000305', $verifyData['params']['productType']);
        $this->assertEquals('20180614191011', $verifyData['params']['orderTime']);
        $this->assertEquals('201806140000011648', $verifyData['params']['productName']);
        $this->assertEquals('192.168.101.1', $verifyData['params']['orderIp']);
        $this->assertEquals('http://handsomeGuy.php', $verifyData['params']['returnUrl']);
        $this->assertEquals('http://handsomeGuy.php', $verifyData['params']['notifyUrl']);
        $this->assertEquals('FDD85F67FDE9868362BD7A3ADB8CC4D1', $verifyData['params']['sign']);
        $this->assertEquals('', $verifyData['params']['remark']);
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

        $xinHuiHe = new XinHuiHe();
        $xinHuiHe->verifyOrderPayment([]);
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

        $xinHuiHe = new XinHuiHe();
        $xinHuiHe->setPrivateKey('test');
        $xinHuiHe->verifyOrderPayment([]);
    }

    /**
     * 測試返回時缺少簽名
     */
    public function testReturnWithoutSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        unset($this->returnResult['sign']);

        $xinHuiHe = new XinHuiHe();
        $xinHuiHe->setPrivateKey('test');
        $xinHuiHe->setOptions($this->returnResult);
        $xinHuiHe->verifyOrderPayment([]);
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

        $this->returnResult['sign'] = 'error';

        $xinHuiHe = new XinHuiHe();
        $xinHuiHe->setPrivateKey('test');
        $xinHuiHe->setOptions($this->returnResult);
        $xinHuiHe->verifyOrderPayment([]);
    }

    /**
     * 測試返回時訂單未支付
     */
    public function testReturnUnpaidOrder()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Unpaid order',
            180062
        );

        $this->returnResult['tradeStatus'] = 'WAITING_PAYMENT';
        $this->returnResult['sign'] = '1612abd302961eb929ff42dbf1bf4467';

        $xinHuiHe = new XinHuiHe();
        $xinHuiHe->setPrivateKey('test');
        $xinHuiHe->setOptions($this->returnResult);
        $xinHuiHe->verifyOrderPayment([]);
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

        $this->returnResult['tradeStatus'] = 'FAILED';
        $this->returnResult['sign'] = 'd44c01abce840e993017a5f777c39b3a';

        $xinHuiHe = new XinHuiHe();
        $xinHuiHe->setPrivateKey('test');
        $xinHuiHe->setOptions($this->returnResult);
        $xinHuiHe->verifyOrderPayment([]);
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

        $entry = ['id' => '201503220000000555'];

        $xinHuiHe = new XinHuiHe();
        $xinHuiHe->setPrivateKey('test');
        $xinHuiHe->setOptions($this->returnResult);
        $xinHuiHe->verifyOrderPayment($entry);
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

        $entry = [
            'id' => '201806140000011648',
            'amount' => '15.00',
        ];

        $xinHuiHe = new XinHuiHe();
        $xinHuiHe->setPrivateKey('test');
        $xinHuiHe->setOptions($this->returnResult);
        $xinHuiHe->verifyOrderPayment($entry);
    }

    /**
     * 測試返回結果
     */
    public function testReturnSuccess()
    {
        $entry = [
            'id' => '201806140000011648',
            'amount' => '1.00',
        ];

        $xinHuiHe = new XinHuiHe();
        $xinHuiHe->setPrivateKey('test');
        $xinHuiHe->setOptions($this->returnResult);
        $xinHuiHe->verifyOrderPayment($entry);

        $this->assertEquals('SUCCESS', $xinHuiHe->getMsg());
    }
}

<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Payment\BaoFoo99;

class BaoFoo99Test extends DurianTestCase
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

        $baoFoo99 = new BaoFoo99();
        $baoFoo99->getVerifyData();
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

        $baoFoo99 = new BaoFoo99();
        $baoFoo99->setPrivateKey('fr0zUb5PPej9XHwPgko6rF8WYGVhnp2Xmw5Vt7P287bExA2f');

        $sourceData = [
            'number' => 'PAY000155',
            'paymentVendorId' => '3',
            'orderId' => '',
            'notify_url' => 'http://pay.1199-eb.net/pay/RequestReturn.php',
            'merchantId' => '12345',
            'domain' => '6',
        ];

        $baoFoo99->setOptions($sourceData);
        $baoFoo99->getVerifyData();
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

        $baoFoo99 = new BaoFoo99();
        $baoFoo99->setPrivateKey('fr0zUb5PPej9XHwPgko6rF8WYGVhnp2Xmw5Vt7P287bExA2f');

        $sourceData = [
            'number' => 'PAY000155',
            'paymentVendorId' => '999',
            'orderId' => '201404050012749333',
            'orderCreateDate' => '2014-04-05 00:06:15',
            'amount' => '600',
            'notify_url' => 'http://pay.1199-eb.net/pay/RequestReturn.php',
            'username' => 'a10214',
            'merchantId' => '12345',
            'domain' => '6',
        ];

        $baoFoo99->setOptions($sourceData);
        $baoFoo99->getVerifyData();
    }

    /**
     * 測試加密
     */
    public function testGetEncodeData()
    {
        $sourceData = [
            'number' => 'PAY000155',
            'paymentVendorId' => '3', //'3' => '330005'
            'orderId' => '201404050012749333',
            'orderCreateDate' => '2014-04-05 00:06:15',
            'amount' => '600',
            'notify_url' => 'http://esball.org/app/member/pay_online2/pay_result.php',
            'username' => 'a10214',
            'merchantId' => '12345',
            'domain' => '6',
        ];

        $baoFoo99 = new BaoFoo99();
        $baoFoo99->setPrivateKey('fr0zUb5PPej9XHwPgko6rF8WYGVhnp2Xmw5Vt7P287bExA2f');
        $baoFoo99->setOptions($sourceData);
        $encodeData = $baoFoo99->getVerifyData();

        $notifyUrl = sprintf(
            '%s?pay_system=%s&hallid=%s',
            $sourceData['notify_url'],
            $sourceData['merchantId'],
            $sourceData['domain']
        );

        //商戶數據包
        $merchantData[] = $sourceData['orderId'];
        $merchantData[] = '20140405'; //format('Ymd')
        $merchantData[] = $sourceData['amount'] * 100;
        $merchantData[] = '100';
        $merchantData[] = '01';
        $merchantData[] = '01';
        $merchantData[] = $notifyUrl;
        $merchantData[] = '';
        $merchantData[] = '';
        $merchantData[] = 'GB';
        $merchantData[] = base64_encode('');
        $merchantData[] = base64_encode($sourceData['username']);

        $this->assertEquals($sourceData['number'], $encodeData['MerID']);
        $this->assertEquals('330005', $encodeData['BankID']);
        $this->assertEquals(implode('|', $merchantData), base64_decode($encodeData['MerReqData']));
        $this->assertEquals('f4d24e2b90750411cbe1cb73a5fac95c', $encodeData['MerSign']);
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

        $baoFoo99 = new BaoFoo99();

        $baoFoo99->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證時沒有支付平台缺少回傳參數
     */
    public function testVerifyWithoutAPIVersion()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $baoFoo99 = new BaoFoo99();
        $baoFoo99->setPrivateKey('fr0zUb5PPej9XHwPgko6rF8WYGVhnp2Xmw5Vt7P287bExA2f');

        $MerReqData = [
            '0101040709274256',
            '201404070013042974',
            '20140406',
            '1159910002114863',
            '20140407025033',
            '300000',
            '100',
            'GB',
            'Y',
            base64_encode('wuge8818')
        ];

        $MerReq = implode('|', $MerReqData);

        $sourceData = [
            'MerID'      => 'PAY000155',
            'MerReqData' => base64_encode($MerReq),
            'MerSign'    => 'aa28b578e06ab45f4f8k4uc2e61ec44d'
        ];

        $baoFoo99->setOptions($sourceData);
        $baoFoo99->verifyOrderPayment([]);
    }

    /**
     * 測試解密驗證時沒有支付平台缺少回傳參數(測試MerSign:加密簽名)
     */
    public function testVerifyWithoutMerSign()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentException',
            'No return parameter specified',
            180137
        );

        $baoFoo99 = new BaoFoo99();
        $baoFoo99->setPrivateKey('fr0zUb5PPej9XHwPgko6rF8WYGVhnp2Xmw5Vt7P287bExA2f');

        $MerReqData = [
            '0101040709274256',
            '201404070013042974',
            '20140406',
            '1159910002114863',
            '20140407025033',
            '300000',
            '100',
            'GB',
            'Y',
            base64_encode('wuge8818')
        ];

        $MerReq = implode('|', $MerReqData);

        $sourceData = [
            'MerID'      => 'PAY000155',
            'APIVersion' => '01',
            'MerReqData' => base64_encode($MerReq)
        ];

        $baoFoo99->setOptions($sourceData);
        $baoFoo99->verifyOrderPayment([]);
    }

    /**
     * 測試返回時簽名驗證數量錯誤
     */
    public function testReturnSignatureVerificationError()
    {
        $this->setExpectedException(
            'BB\DurianBundle\Exception\PaymentConnectionException',
            'Signature verification failed',
            180034
        );

        $baoFoo99 = new BaoFoo99();
        $baoFoo99->setPrivateKey('fr0zUb5PPej9XHwPgko6rF8WYGVhnp2Xmw5Vt7P287bExA2f');

        $MerReqData = [
            '0101040709274256',
            '201404070013042974',
            '20140406',
            '1159910002114863',
            '20140407025033',
            '300000',
            '100',
            'GB',
            base64_encode('wuge8818')
        ];

        $MerReq = implode('|', $MerReqData);

        $sourceData = [
            'MerID'      => 'PAY000155',
            'APIVersion' => '01',
            'MerReqData' => base64_encode($MerReq)
        ];

        $baoFoo99->setOptions($sourceData);
        $baoFoo99->verifyOrderPayment([]);
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

        $baoFoo99 = new BaoFoo99();

        $key = 'fr0zUb5PPej9XHwPgko6rF8WYGVhnp2Xmw5Vt7P287bExA2fpPDmJQz0I31nfx'.
            'B9BmxAm168uMYKEU9xoQRxCqovWemQfCwkV2dlnSTNteRXSEMOTdZp6oXvYcDhoG3H';

        $baoFoo99->setPrivateKey($key);

        $merReqData = 'MDEwMTA0MDcwOTI3NDI1NnwyMDE0MDQwNzAwMTMwNDI5NzR8MjAxNDA'.
            '0MDZ8MTE1OTkxMDAwMjExNDg2M3wyMDE0MDQwNzAyNTAzM3wzMDAwMDB8MTAwfEdC'.
            'fE58ZDNWblpUZzRNVGc9';

        $sourceData = [
            'MerID'      => 'PAY000155',
            'APIVersion' => '01',
            'MerReqData' => $merReqData,
            'MerSign'    => 'aa28b578e06ab45f4f8k4uc2e61ec44d'
        ];

        $baoFoo99->setOptions($sourceData);
        $baoFoo99->verifyOrderPayment([]);
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

        $baoFoo99 = new BaoFoo99();

        $key = 'fr0zUb5PPej9XHwPgko6rF8WYGVhnp2Xmw5Vt7P287bExA2fpPDmJQz0I31nfx'.
            'B9BmxAm168uMYKEU9xoQRxCqovWemQfCwkV2dlnSTNteRXSEMOTdZp6oXvYcDhoG3H';

        $baoFoo99->setPrivateKey($key);

        $merReqData = 'MDEwMTA0MDcwOTI3NDI1NnwyMDE0MDQwNzAwMTMwNDI5NzR8MjAxNDA'.
            '0MDZ8MTE1OTkxMDAwMjExNDg2M3wyMDE0MDQwNzAyNTAzM3wzMDAwMDB8MTAwfEdC'.
            'fE58ZDNWblpUZzRNVGc9';

        $sourceData = [
            'MerID'      => 'PAY000155',
            'APIVersion' => '01',
            'MerReqData' => $merReqData,
            'MerSign'    => '5aedca41161acb0a074dea3dcfd50828'
        ];

        $baoFoo99->setOptions($sourceData);
        $baoFoo99->verifyOrderPayment([]);
    }

    /**
     * 測試線上支付單號不正確的情況
     */
    public function testOrderIdError()
    {
        $this->setExpectedException('BB\DurianBundle\Exception\PaymentException', 'Order Id error', 180061);

        $baoFoo99 = new BaoFoo99();

        $key = 'fr0zUb5PPej9XHwPgko6rF8WYGVhnp2Xmw5Vt7P287bExA2fpPDmJQz0I31nfx'.
            'B9BmxAm168uMYKEU9xoQRxCqovWemQfCwkV2dlnSTNteRXSEMOTdZp6oXvYcDhoG3H';

        $baoFoo99->setPrivateKey($key);

        $merReqData = 'MDEwMTA0MDcwOTI3NDI1NnwyMDE0MDQwNzAwMTMwNDI5NzR8MjAxNDA'.
            '0MDZ8MTE1OTkxMDAwMjExNDg2M3wyMDE0MDQwNzAyNTAzM3wzMDAwMDB8MTAwfEdC'.
            'fFl8ZDNWblpUZzRNVGc9';

        $sourceData = [
            'MerID'      => 'PAY000155',
            'APIVersion' => '01',
            'MerReqData' => $merReqData,
            'MerSign'    => 'aa28b578e06ab45fb9ec2fc2e61ec44d'
        ];

        $entry = ['id' => '201404070013042111'];

        $baoFoo99->setOptions($sourceData);
        $baoFoo99->verifyOrderPayment($entry);
    }

    /**
     * 測試線上支付金額不正確的情況
     */
    public function testOrderAmountError()
    {
        $this->setExpectedException('BB\DurianBundle\Exception\PaymentException', 'Order Amount error', 180058);

        $baoFoo99 = new BaoFoo99();

        $key = 'fr0zUb5PPej9XHwPgko6rF8WYGVhnp2Xmw5Vt7P287bExA2fpPDmJQz0I31nfx'.
            'B9BmxAm168uMYKEU9xoQRxCqovWemQfCwkV2dlnSTNteRXSEMOTdZp6oXvYcDhoG3H';

        $baoFoo99->setPrivateKey($key);

        $merReqData = 'MDEwMTA0MDcwOTI3NDI1NnwyMDE0MDQwNzAwMTMwNDI5NzR8MjAxNDA'.
            '0MDZ8MTE1OTkxMDAwMjExNDg2M3wyMDE0MDQwNzAyNTAzM3wzMDAwMDB8MTAwfEdC'.
            'fFl8ZDNWblpUZzRNVGc9';

        $sourceData = [
            'MerID'      => 'PAY000155',
            'APIVersion' => '01',
            'MerReqData' => $merReqData,
            'MerSign'    => 'aa28b578e06ab45fb9ec2fc2e61ec44d'
        ];

        $entry = [
            'id' => '201404070013042974',
            'amount' => '115.00'
        ];

        $baoFoo99->setOptions($sourceData);
        $baoFoo99->verifyOrderPayment($entry);
    }

    /**
     * 測試支付驗證成功
     */
    public function testPaySuccess()
    {
        $baoFoo99 = new BaoFoo99();

        $key = 'fr0zUb5PPej9XHwPgko6rF8WYGVhnp2Xmw5Vt7P287bExA2fpPDmJQz0I31nfx'.
            'B9BmxAm168uMYKEU9xoQRxCqovWemQfCwkV2dlnSTNteRXSEMOTdZp6oXvYcDhoG3H';

        $baoFoo99->setPrivateKey($key);

        $merReqData = 'MDEwMTA0MDcwOTI3NDI1NnwyMDE0MDQwNzAwMTMwNDI5NzR8MjAxNDA'.
            '0MDZ8MTE1OTkxMDAwMjExNDg2M3wyMDE0MDQwNzAyNTAzM3wzMDAwMDB8MTAwfEdC'.
            'fFl8ZDNWblpUZzRNVGc9';

        $sourceData = [
            'MerID'      => 'PAY000155',
            'APIVersion' => '01',
            'MerReqData' => $merReqData,
            'MerSign'    => 'aa28b578e06ab45fb9ec2fc2e61ec44d'
        ];

        $entry = [
            'id' => '201404070013042974',
            'amount' => '3000.00'
        ];

        $baoFoo99->setOptions($sourceData);
        $baoFoo99->verifyOrderPayment($entry);

        $this->assertEquals('[Succeed]', $baoFoo99->getMsg());
    }
}

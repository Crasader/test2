<?php

namespace BB\DurianBundle\Tests\Payment;

use BB\DurianBundle\Tests\DurianTestCase;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;

class PaymentBaseTest extends DurianTestCase
{
    /**
     * 測試將array格式轉成xml
     */
    public function testArrayToXml()
    {
        $data = [
            'apple' => '100',
            'bird' => '50',
            'cat' => '80',
            'dog' => '10',
            'elephant' => '150'
        ];

        // 設定version和encoding
        $context = [
            'xml_version' => '1.0',
            'xml_encoding' => 'utf-8'
        ];

        $encoders = [new XmlEncoder()];
        $normalizers = [new GetSetMethodNormalizer()];
        $serializer = new Serializer($normalizers, $encoders);
        $dataXml = $serializer->encode($data, 'xml', $context);

        $paymentBase = $this->getMockForAbstractClass('BB\DurianBundle\Payment\PaymentBase');
        $reflector = new \ReflectionClass('BB\DurianBundle\Payment\PaymentBase');
        $method = $reflector->getMethod('arrayToXml');
        $method->setAccessible(true);

        $xml = $method->invokeArgs($paymentBase, [$data, $context]);

        // 產生出來的xml格式會有換行字元，所以比對會錯誤
        $this->assertFalse($xml == $dataXml);

        // 去除換行字元後，比對結果就會相同
        $newXml = str_replace("\n", '', $dataXml);
        $this->assertTrue($xml == $newXml);
    }

    /**
     * 測試不為XML格式
     */
    public function testIsNotXml()
    {
        $data = '<Ips><GateWayRsp><head><ReferenceID></ReferenceID><RspCode>000000</RspCode><RspMsg>' .
            '<![CDATA[success]]></RspMsg><ReqDate>20151206214259</ReqDate><RspDate>20151206214345</RspDate>' .
            '<Signature>236d0313b3c1a1fa029c7a75a05a6c07</Signature></head><body><MerBillNo>201512060150435695' .
            '</MerBillNo><CurrencyType>156</CurrencyType><Amount>50</Amount><Date>20151206</Date><Status>Y</Status>' .
            '<Msg><![CDATA[success]]></Msg><IpsBillNo>BO2015120621425954361</IpsBillNo><IpsTradeNo>' .
            '2015120609125981121</IpsTradeNo><RetEncodeType>17</RetEncodeType><BankBillNo>713085765</BankBillNo>' .
            '<ResultType>0</ResultType><IpsBillTime>20151206214345</IpsBillTime><script';

        $paymentBase = $this->getMockForAbstractClass('BB\DurianBundle\Payment\PaymentBase');
        $reflector = new \ReflectionClass('BB\DurianBundle\Payment\PaymentBase');
        $method = $reflector->getMethod('isXml');
        $method->setAccessible(true);

        $xml = $method->invokeArgs($paymentBase, [$data]);

        $this->assertFalse($xml);
    }
}

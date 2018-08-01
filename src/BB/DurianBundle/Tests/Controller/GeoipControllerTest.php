<?php

namespace BB\DurianBundle\Tests\Controller;

use Symfony\Component\HttpFoundation\Request;
use BB\DurianBundle\Controller\GeoipController;

class GeoipControllerTest extends ControllerTest
{
    /**
     * 測試設定Geoip國家翻譯帶入格式不合的英文名稱
     */
    public function testSetCountryNameWithInvalidEnglishName()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'String must use utf-8 encoding',
            150610002
        );

        $params = [
            'en_name' => mb_convert_encoding('e龜龍鱉', 'GB2312', 'UTF-8'),
            'zh_tw_name' => 'e龜龍鱉',
            'zh_cn_name' => 'e龜龍鱉'
        ];

        $request = new Request([], $params);
        $controller = new GeoipController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->setCountryNameAction($request, 2);
    }

    /**
     * 測試設定Geoip國家翻譯帶入格式不合的繁體中文名稱
     */
    public function testSetCountryNameWithInvalidTraditionalChineseName()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'String must use utf-8 encoding',
            150610002
        );

        $params = [
            'en_name' => 'e龜龍鱉',
            'zh_tw_name' => mb_convert_encoding('e龜龍鱉', 'GB2312', 'UTF-8'),
            'zh_cn_name' => 'e龜龍鱉'
        ];

        $request = new Request([], $params);
        $controller = new GeoipController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->setCountryNameAction($request, 2);
    }

    /**
     * 測試設定Geoip國家翻譯帶入格式不合的簡體中文名稱
     */
    public function testSetCountryNameWithInvalidSimplifiedChineseName()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'String must use utf-8 encoding',
            150610002
        );

        $params = [
            'en_name' => 'e龜龍鱉',
            'zh_tw_name' => 'e龜龍鱉',
            'zh_cn_name' => mb_convert_encoding('e龜龍鱉', 'GB2312', 'UTF-8')
        ];

        $request = new Request([], $params);
        $controller = new GeoipController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->setCountryNameAction($request, 2);
    }

    /**
     * 測試設定區域翻譯檔帶入格式不合的英文名稱
     */
    public function testSetRegionNameWithInvalidEnglishName()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'String must use utf-8 encoding',
            150610002
        );

        $params = [
            'en_name' => mb_convert_encoding('e龜龍鱉', 'GB2312', 'UTF-8'),
            'zh_tw_name' => 'e龜龍鱉',
            'zh_cn_name' => 'e龜龍鱉'
        ];

        $request = new Request([], $params);
        $controller = new GeoipController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->setRegionNameAction($request, 2);
    }

    /**
     * 測試設定區域翻譯檔帶入格式不合的繁體中文名稱
     */
    public function testSetRegionNameWithInvalidTraditionalChineseName()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'String must use utf-8 encoding',
            150610002
        );

        $params = [
            'en_name' => 'e龜龍鱉',
            'zh_tw_name' => mb_convert_encoding('e龜龍鱉', 'GB2312', 'UTF-8'),
            'zh_cn_name' => 'e龜龍鱉'
        ];

        $request = new Request([], $params);
        $controller = new GeoipController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->setRegionNameAction($request, 2);
    }

    /**
     * 測試設定區域翻譯檔帶入格式不合的簡體中文名稱
     */
    public function testSetRegionNameWithInvalidSimplifiedChineseName()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'String must use utf-8 encoding',
            150610002
        );

        $params = [
            'en_name' => 'e龜龍鱉',
            'zh_tw_name' => 'e龜龍鱉',
            'zh_cn_name' => mb_convert_encoding('e龜龍鱉', 'GB2312', 'UTF-8')
        ];

        $request = new Request([], $params);
        $controller = new GeoipController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->setRegionNameAction($request, 2);
    }

    /**
     * 測試設定城市翻譯檔帶入格式不合的英文名稱
     */
    public function testSetCityNameWithInvalidEnglishName()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'String must use utf-8 encoding',
            150610002
        );

        $params = [
            'en_name' => mb_convert_encoding('e龜龍鱉', 'GB2312', 'UTF-8'),
            'zh_tw_name' => 'e龜龍鱉',
            'zh_cn_name' => 'e龜龍鱉'
        ];

        $request = new Request([], $params);
        $controller = new GeoipController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->setCityNameAction($request, 2);
    }

    /**
     * 測試設定城市翻譯檔帶入格式不合的繁體中文名稱
     */
    public function testSetCityNameWithInvalidTraditionalChineseName()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'String must use utf-8 encoding',
            150610002
        );

        $params = [
            'en_name' => 'e龜龍鱉',
            'zh_tw_name' => mb_convert_encoding('e龜龍鱉', 'GB2312', 'UTF-8'),
            'zh_cn_name' => 'e龜龍鱉'
        ];

        $request = new Request([], $params);
        $controller = new GeoipController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->setCityNameAction($request, 2);
    }

    /**
     * 測試設定城市翻譯檔帶入格式不合的簡體中文名稱
     */
    public function testSetCityNameWithInvalidSimplifiedChineseName()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'String must use utf-8 encoding',
            150610002
        );

        $params = [
            'en_name' => 'e龜龍鱉',
            'zh_tw_name' => 'e龜龍鱉',
            'zh_cn_name' => mb_convert_encoding('e龜龍鱉', 'GB2312', 'UTF-8')
        ];

        $request = new Request([], $params);
        $controller = new GeoipController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->setCityNameAction($request, 2);
    }
}

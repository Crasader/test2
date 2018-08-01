<?php

namespace BB\DurianBundle\Tests\Parser;

use BB\DurianBundle\Tests\Functional\WebTestCase;

class ParserTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = array(
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadBankData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserAncestorData',
        );

        $this->loadFixtures($classnames);
    }

    /**
     * 測試字典檔案的格式是否正確，測試方法為在字典檔最後一行加入一語句，並以
     * 兩語系翻譯該句子，若中間有其他的異常，則會在測試途中噴錯。
     */
    public function testParser()
    {
        $this->getContainer()->get('translator')->setLocale('zh_TW');
        $msg = $this->getContainer()->get('translator')->trans('Check over, everything is fine');

        $this->assertEquals('檢查結束，一切正常', $msg);

        $this->getContainer()->get('translator')->setLocale('zh_CN');
        $msg = $this->getContainer()->get('translator')->trans('Check over, everything is fine');

        $this->assertEquals('检查结束，一切正常', $msg);
    }
}

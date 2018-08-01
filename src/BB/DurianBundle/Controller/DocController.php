<?php

namespace BB\DurianBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

class DocController extends Controller
{
    /**
     * 帶入文件的路徑和檔名顯示文件
     *
     * 例如item帶入以下左方參數可得結果：
     * currency                      列出幣別代碼
     * opcode/allow_balance_negative 列出餘額可以為負的opcode
     * opcode/allow_amount_zero      列出金額可為0的opcode
     * opcode/disable_not_allow      列出停用不可使用的opcode
     *
     * @Route("/doc/{item}",
     *     name = "doc",
     *     defaults = {"item" = ""},
     *     requirements = {"item" = ".+"})
     *
     * @param string $item 路徑和檔名
     * @return Renders
     */
    public function docAction($item)
    {
        $rootDir = $this->get('kernel')->getRootDir();

        $file = $item . '.md';
        $dir = $rootDir . '/../src/BB/DurianBundle/Resources/doc/';

        $doc = $dir . $file;

        $title = str_replace('_', ' ', $item);
        if (!file_exists($doc)) {
            return $this->render(
                'BBDurianBundle:Default/Doc:mappingList.html.twig',
                ['title' => ucwords($title), 'txt' => $item]
            );
        }

        $txt = file_get_contents($doc);
        $txt = $this->get('markdown.parser')->transform($txt);

        return $this->render(
            'BBDurianBundle:Default/Doc:doc.html.twig',
            array('txt' => $txt)
        );
    }
}

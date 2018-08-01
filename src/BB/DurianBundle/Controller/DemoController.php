<?php

namespace BB\DurianBundle\Controller;

use Doctrine\Common\Util\Inflector;
use Symfony\Component\Finder\Finder;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

class DemoController extends Controller
{
    /**
     * Demo for APIs
     *
     * @Route("/demo/", name="demo_default")
     *
     * @Route("/demo/{group}/{item}",
     *        name = "demo",
     *        defaults = {"group" = "", "item" = "portal"})
     *
     * @param Request $request
     * @param $group 分類類別
     * @param $item 頁面變數
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function demoAction(Request $request, $group = '', $item = 'portal')
    {
        $demoDir = $this->container->getParameter('kernel.root_dir');
        $demoDir .= '/../src/BB/DurianBundle/Resources/views/Default/Demo';

        $finder = new Finder();
        $finder->files()->in($demoDir)->sortByName();

        $api = [];

        foreach ($finder as $file) {
            $path = $file->getRelativePath();

            // 直屬在demo目錄下的檔案不處理
            if (!$path) {
                continue;
            }

            $tPath = Inflector::tableize($path);

            $fileName = $file->getFileName();

            // 去掉副檔名 .html.twig
            $name = substr($fileName, 0, -10);
            $tName = Inflector::tableize($name);

            // 單字間加空白
            $label = preg_replace('/([A-Z])/', ' $1', $name);
            $label = ucwords($label);

            $api[$tPath]['label'] = ucwords($path);
            $api[$tPath]['sub'][$tName]['label'] = $label;
            $api[$tPath]['sub'][$tName]['name'] = '';
            $api[$tPath]['sub'][$tName]['method'] = '';
            $api[$tPath]['sub'][$tName]['route'] = '';
            $api[$tPath]['sub'][$tName]['description'] = '';

            $content = $file->getContents();
            if (preg_match('/<h[123]>(.*)<\/h[123]>/', $content, $match)) {
                $api[$tPath]['sub'][$tName]['name'] = $match[1];
            }

            if (preg_match('/<code>(.*)<\/code>/', $content, $match)) {
                $router = explode(' ', $match[1]);
                $api[$tPath]['sub'][$tName]['method'] = $router[0];
                $api[$tPath]['sub'][$tName]['route'] = $router[1];
            }

            if (preg_match('/<p class="lead">(.*)<\/p>/', $content, $match)) {
                $api[$tPath]['sub'][$tName]['description'] = $match[1];
            }
        }

        // active設定true，反白使用中連結
        if ($group != '') {
            $api[$group]['active'] = true;
        }

        if ($item != 'portal') {
            $api[$group]['sub'][$item]['active'] = true;
        }

        $group = Inflector::classify($group);
        $item  = Inflector::camelize($item);

        $clientIp = $request->getClientIp();
        $pathInfo = $request->getPathInfo();

        $view = "BBDurianBundle:Default/Demo/$group:$item.html.twig";
        $parameters = [
            'api' => $api,
            'clientIp' => $clientIp,
            'pathInfo' => $pathInfo
        ];

        return $this->render($view, $parameters);
    }
}

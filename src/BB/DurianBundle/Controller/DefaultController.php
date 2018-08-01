<?php

namespace BB\DurianBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

class DefaultController extends Controller
{
    /**
     * Durian index page
     *
     * @Route("/", name = "home")
     *
     * @return Response
     */
    public function indexAction()
    {
        $response = $this->forward('BBDurianBundle:Monitor:index');

        return $response;
    }

    /**
     * 讀durian.sha1檔案以取得版號。若無此檔案回傳空字串
     *
     * @Route("/version")
     */
    public function versionAction()
    {
        $appDir = $this->get('kernel')->getRootDir();
        $file   = $appDir . '/../durian.sha1';

        $version = '';

        if (file_exists($file)) {
            $version = file_get_contents($file);
        }

        return new Response($version);
    }
}

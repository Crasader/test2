<?php

namespace BB\DurianBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;

class BulkController extends Controller
{
    /**
     * 根據廳與使用者帳號，回傳使用者id
     *
     * @Route("/bulk/fetch_user_ids_by_username",
     *        name = "api_fetch_user_ids_by_username",
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function fetchUserIdsByUsernameAction(Request $request)
    {
        $query = $request->query;
        $domain = $query->get('domain');
        $allUsername = array_map('trim', $query->get('username', []));

        if (!$domain) {
            throw new \InvalidArgumentException('No domain specified', 150440001);
        }

        $em = $this->getEntityManager();
        $userRepo = $em->getRepository('BBDurianBundle:User');

        $output['ret'] = [];

        $users = $userRepo->getUserIdsByUsername($domain, array_unique($allUsername));

        $output['result'] = 'ok';
        $output['ret'] = $users;

        return new JsonResponse($output);
    }

    /**
     * 回傳Doctrine EntityManager
     *
     * @param string $name Entity manager name
     * @return \Doctrine\ORM\EntityManager
     */
    private function getEntityManager($name = 'default')
    {
        return $this->getDoctrine()->getManager($name);
    }
}

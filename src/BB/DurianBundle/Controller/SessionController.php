<?php

namespace BB\DurianBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;

class SessionController extends Controller
{
    /**
     * 建立 Session
     *
     * @Route("/user/{userId}/session",
     *      name = "api_session_create",
     *      requirements = {"userId" = "\d+", "_format" = "json"},
     *      defaults = {"_format" = "json"})
     * @Method({"POST"})
     *
     * @param integer $userId 使用者編號
     * @return JsonResponse
     */
    public function createAction($userId)
    {
        $user = $this->getDoctrine()->getManager()->find('BBDurianBundle:User', $userId);

        if (!$user) {
            throw new \RuntimeException('No such user', 150330004);
        }

        $sessionBroker = $this->get('durian.session_broker');

        if ($sessionBroker->existsByUserId($userId)) {
            throw new \RuntimeException('Session already exists', 150330002);
        }

        $sessionId = $sessionBroker->create($user);
        $session = $sessionBroker->getBySessionId($sessionId);

        $out = [
            'result' => 'ok',
            'ret' => $session
        ];

        return new JsonResponse($out);
    }

    /**
     * 用session id建立Session
     *
     * @Route("/session/{sessionId}",
     *      name = "api_session_create_by_session_id",
     *      requirements = {"sessionId" = "\w+", "_format" = "json"},
     *      defaults = {"_format" = "json"})
     * @Method({"POST"})
     *
     * @param Request $request
     * @param string $sessionId
     * @return JsonResponse
     */
    public function createBySessionIdAction(Request $request, $sessionId)
    {
        $em = $this->getDoctrine()->getManager();
        $request = $request->request;
        $userId = $request->get('user_id', null);

        if (!$userId) {
            throw new \InvalidArgumentException('Invalid user_id', 150330010);
        }

        $user = $em->find('BBDurianBundle:User', $userId);

        if (!$user) {
            throw new \RuntimeException('No such user', 150330011);
        }

        $sessionBroker = $this->get('durian.session_broker');

        if ($sessionBroker->existsBySessionId($sessionId)) {
            throw new \RuntimeException('Session already exists', 150330012);
        }

        $sessionId = $sessionBroker->createBySessionId($user, $sessionId);
        $session = $sessionBroker->getBySessionId($sessionId);

        $out = [
            'result' => 'ok',
            'ret' => $session
        ];

        return new JsonResponse($out);
    }

    /**
     * 取得 Session 資料
     * 注意：此 API 僅測試用，實際上會透過 Nginx 導向 node.js 取得 Session
     *
     * @Route("/session/{sessionId}",
     *      name = "api_session_get",
     *      requirements = {"sessionId" = "\w+", "_format" = "json"},
     *      defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param string $sessionId
     * @return JsonResponse
     */
    public function getAction($sessionId)
    {
        $sessionBroker = $this->get('durian.session_broker');
        $session = $sessionBroker->getBySessionId($sessionId);
        $maintainInfo = $sessionBroker->getMaintainInfo();

        $session['is_maintaining'] = $maintainInfo;
        $session['whitelist'] = [];

        if ($session['is_maintaining']) {
            $session['whitelist'] = $sessionBroker->getWhitelist();
        }

        $out = [
            'result' => 'ok',
            'ret' => $session
        ];

        return new JsonResponse($out);
    }

    /**
     * 根據使用者編號，取得 Session 資料
     * 注意：此 API 僅測試用，實際上會透過 Nginx 導向 node.js 取得 Session
     *
     * @Route("/user/{userId}/session",
     *      name = "api_session_get_by_user_id",
     *      requirements = {"userId" = "\d+", "_format" = "json"},
     *      defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param string $userId
     * @return JsonResponse
     */
    public function getByUserAction($userId)
    {
        $sessionBroker = $this->get('durian.session_broker');
        $session = $sessionBroker->getByUserId($userId);
        $maintainInfo = $sessionBroker->getMaintainInfo();

        $session['is_maintaining'] = $maintainInfo;
        $session['whitelist'] = [];

        if ($session['is_maintaining']) {
            $session['whitelist'] = $sessionBroker->getWhitelist();
        }

        $out = [
            'result' => 'ok',
            'ret' => $session
        ];

        return new JsonResponse($out);
    }

    /**
     * 刪除 Session 資料
     *
     * @Route("/session/{sessionId}",
     *      name = "api_session_delete",
     *      requirements = {"sessionId" = "\w+", "_format" = "json"},
     *      defaults = {"_format" = "json"})
     * @Method({"DELETE"})
     *
     * @param string $sessionId
     * @return JsonResponse
     */
    public function deleteAction($sessionId)
    {
        $sessionBroker = $this->get('durian.session_broker');
        $sessionBroker->remove($sessionId);

        $out = ['result' => 'ok'];

        return new JsonResponse($out);
    }

    /**
     * 依據使用者編號，刪除 Session 資料
     *
     * @Route("/user/{userId}/session",
     *      name = "api_session_delete_by_user_id",
     *      requirements = {"userId" = "\d+", "_format" = "json"},
     *      defaults = {"_format" = "json"})
     * @Method({"DELETE"})
     *
     * @param integer $userId
     * @return JsonResponse
     */
    public function deleteByUserAction($userId)
    {
        $sessionBroker = $this->get('durian.session_broker');
        $sessionBroker->removeByUserId($userId);

        $out = ['result' => 'ok'];

        return new JsonResponse($out);
    }

    /**
     * 依據上層使用者，刪除 Session 資料
     *
     * @Route("/session",
     *      name = "api_session_delete_by_parent",
     *      requirements = {"_format" = "json"},
     *      defaults = {"_format" = "json"})
     * @Method({"DELETE"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function deleteByParentAction(Request $request)
    {
        $sessionBroker = $this->get('durian.session_broker');
        $em = $this->getDoctrine()->getManager();
        $request = $request->request;

        $parentId = $request->get('parent_id', null);
        $depth = $request->get('depth', null);
        $role = $request->get('role', null);

        // 有帶parentId 需判斷是否有此使用者
        if ($parentId) {
            $parent = $em->find('BBDurianBundle:User', $parentId);

            if (!$parent) {
                throw new \RuntimeException('No parent found', 150330006);
            }
        }

        if (isset($depth) && isset($role)) {
            throw new \InvalidArgumentException('Depth and role cannot be chosen at same time', 150330008);
        }

        $validator = $this->get('durian.validator');

        if (isset($depth) && !$validator->isInt($depth, true)) {
            throw new \InvalidArgumentException('Invalid depth', 150330007);
        }

        if (isset($role) && !$validator->isInt($role, true)) {
            throw new \InvalidArgumentException('Invalid role', 150330009);
        }

        $sessionBroker->pushToRemoveList($parentId, $depth, $role);

        $out = ['result' => 'ok'];

        return new JsonResponse($out);
    }

    /**
     * 回傳線上人數列表
     *
     * @Route("/online/list",
     *      name = "api_get_online_list",
     *      requirements = {"_format" = "json"},
     *      defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @return JsonResponse
     */
    public function getOnlineListAction(Request $request)
    {
        $query = $request->query;
        $domain = $query->get('domain', 0);
        $inTime = $query->get('in_time', 5);
        $maxResults = $query->get('max_results', 20);

        $sessionBroker = $this->get('durian.session_broker');
        $members = $sessionBroker->getOnlineList($domain, $inTime, $maxResults);

        $result = [];
        foreach ($members as $member) {
            $user = explode(':', $member);
            $result[] = [
                'domain' => $user[0],
                'user_id' => $user[1],
                'username' => $user[2]
            ];
        }

        $out = [
            'result' => 'ok',
            'ret' => $result
        ];

        return new JsonResponse($out);
    }

    /**
     * 依據使用者帳號回傳線上人數列表
     *
     * @Route("/online/list_by_username",
     *      name = "api_get_online_list_by_username",
     *      requirements = {"_format" = "json"},
     *      defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @return JsonResponse
     */
    public function getOnlineListByUsernameAction(Request $request)
    {
        $query = $request->query;
        $domain = $query->get('domain', 0);
        $username = $query->get('username');

        if (!$username) {
            throw new \InvalidArgumentException('No username specified', 150330013);
        }

        $sessionBroker = $this->get('durian.session_broker');
        $list = $sessionBroker->getOnlineListByUsername($domain, $username);

        $result = [];
        foreach ($list as $members) {
            foreach ($members as $member) {
                $user = explode(':', $member[0]);
                $result[] = [
                    'domain' => $user[0],
                    'user_id' => $user[1],
                    'username' => $user[2],
                    'last_online' => (new \DateTime($member[1]))->format(\DateTime::ISO8601)
                ];
            }
        }

        $out = [
            'result' => 'ok',
            'ret' => $result
        ];

        return new JsonResponse($out);
    }

    /**
     * 回傳線上人數
     *
     * @Route("/online/total",
     *      name = "api_get_total_online",
     *      requirements = {"_format" = "json"},
     *      defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @return JsonResponse
     */
    public function getTotalOnlineAction(Request $request)
    {
        $query = $request->query;
        $domain = $query->get('domain', 0);
        $inTime = $query->get('in_time', 5);

        $sessionBroker = $this->get('durian.session_broker');
        $total = $sessionBroker->getTotalOnline($domain, $inTime);

        $out = [
            'result' => 'ok',
            'ret' => $total
        ];

        return new JsonResponse($out);
    }

    /**
     * 建立一次性Session (One-Time Session)
     *
     * @Route("/session/{sessionId}/ots",
     *      name = "api_session_create_ots",
     *      requirements = {"sessionId" = "\w+", "_format" = "json"},
     *      defaults = {"_format" = "json"})
     * @Method({"POST"})
     *
     * @param Request $request
     * @param string $sessionId
     * @return JsonResponse
     */
    public function createOneTimeSessionAction(Request $request, $sessionId)
    {
        $broker = $this->get('durian.session_broker');
        $broker->getBySessionId($sessionId);

        $otsId = $broker->createOneTimeSession($sessionId);

        return new JsonResponse([
            'result' => 'ok',
            'ret' => $otsId
        ]);
    }

    /**
     * 透過一次性Session取得Session id
     *
     * @Route("/ots/{otsId}",
     *      name = "api_session_get_ots",
     *      requirements = {"otsId" = "\w+", "_format" = "json"},
     *      defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @param string $otsId One-Time Session id
     * @return JsonResponse
     */
    public function getOneTimeSessionAction(Request $request, $otsId)
    {
        $broker = $this->get('durian.session_broker');
        $sid = $broker->getOneTimeSession($otsId);

        if (!$sid) {
            throw new \RuntimeException('No one-time session found', 150330016);
        }

        return new JsonResponse([
            'result' => 'ok',
            'ret' => $sid
        ]);
    }

    /**
     * 依據sessionId新增Session的研發資訊
     *
     * @Route("/session/{sessionId}/rd_info",
     *     name = "api_session_set_rd_info",
     *     requirements = {"sessionId" = "\w+", "_format" = "json"},
     *     defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @param string $sessionId
     * @return JsonResponse
     */
    public function setSessionRdInfoAction(Request $request, $sessionId)
    {
        $broker = $this->get('durian.session_broker');
        $request = $request->request;

        if (!$request->keys()) {
            throw new \InvalidArgumentException('No rd_info specified', 150330017);
        }

        $rdInfo = $request->get('rd_info', null);

        $ret = $broker->setSessionRdInfo($sessionId, $rdInfo);

        return new JsonResponse([
            'result' => 'ok',
            'ret' => $ret
        ]);
    }
}

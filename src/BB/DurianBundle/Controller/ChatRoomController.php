<?php

namespace BB\DurianBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use BB\DurianBundle\Entity\ChatRoom;

class ChatRoomController extends Controller
{
    /**
     * 回傳使用者聊天室資訊
     *
     * @Route("/user/{userId}/chat_room",
     *        name = "api_chat_room_get_user",
     *        requirements = {"userId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param integer $userId 使用者ID
     * @return JsonResponse
     *
     * @author Ruby 2016.02.25
     */
    public function getAction($userId)
    {
        $emShare = $this->getEntityManager('share');

        $user = $this->findUser($userId);
        $chatRoom = $emShare->find('BBDurianBundle:ChatRoom', $userId);

        $output = [];
        $output['result'] = 'ok';
        $output['ret'] = [
            'user_id' => (int) $userId,
            'readable' => true,
            'writable' => true,
            'ban_at' => null
        ];

        if ($user->isTest()) {
            $output['ret']['writable'] = false;
        }

        if ($chatRoom) {
            $output['ret'] = $chatRoom->toArray();
        }

        return new JsonResponse($output);
    }

    /**
     * 回傳使用者聊天室已禁言列表
     *
     * @Route("/chat_room/ban_list",
     *        name = "api_chat_room_get_ban_list",
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     *
     * @author Billy 2016.06.30
     */
    public function getBanListAction(Request $request)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $validator = $this->get('durian.validator');

        $query = $request->query;

        $criteria = [];
        $criteria['now'] = new \DateTime('now');

        if ($query->has('domain')) {
            $criteria['domain'] = $query->get('domain');

            if(!$validator->isInt($criteria['domain'])) {
                throw new \InvalidArgumentException('Invalid domain', 150750005);
            }
        }

        $crRepo = $emShare->getRepository('BBDurianBundle:ChatRoom');
        $crLists = $crRepo->getBanList($criteria);

        $lists = [];

        if ($crLists) {
            $crUsers = [];

            foreach ($crLists as $chatRoom) {
                $crUsers[] = $chatRoom->getUserId();
            }

            $criteria['userIds'] = $crUsers;
            $userRepo = $em->getRepository('BBDurianBundle:User');
            $users = $userRepo->getUntestUser($criteria);
            $userIds = array_column($users, 'id');

            foreach ($crLists as $chatRoom) {
                $userId = $chatRoom->getUserId();

                if (in_array($userId, $userIds)) {
                    $lists[] = $chatRoom->toArray();
                }
            }
        }

        $ret['result'] = 'ok';
        $ret['ret'] = $lists;
        $ret['pagination'] = ['total' => count($lists)];

        return new JsonResponse($ret);
    }

    /**
     * 修改使用者聊天室資訊
     *
     * @Route("/user/{userId}/chat_room",
     *        name = "api_chat_room_edit_user",
     *        requirements = {"userId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @param integer $userId 使用者ID
     * @return JsonResponse
     *
     * @author Ruby 2016.02.25
     */
    public function editAction(Request $request, $userId)
    {
        $emShare = $this->getEntityManager('share');
        $operationLogger = $this->get('durian.operation_logger');
        $request = $request->request;

        $user = $this->findUser($userId);
        $chatRoom = $emShare->find('BBDurianBundle:ChatRoom', $userId);

        if (!$chatRoom) {
            $chatRoom = new ChatRoom($user);

            $log = $operationLogger->create('chat_room', ['user_id' => $userId]);
            $log->addMessage('readable', 'true');
            $log->addMessage('writable', var_export($chatRoom->isWritable(), true));
            $log->addMessage('ban_at', 'null');
            $operationLogger->save($log);

            $log = $operationLogger->create('chat_room', ['user_id' => $userId]);
            $this->editChatRoom($request, $chatRoom, $log);

            //與預設值不相同時才要新增
            if ($log->getMessage()) {
                $emShare->persist($chatRoom);
                $operationLogger->save($log);

                try {
                    $emShare->flush();
                } catch (\Exception $e) {
                    $emShare->clear();

                    //同分秒新增聊天室資訊時要丟例外
                    if ($e->getPrevious()->getCode() == 23000) {
                        throw new \RuntimeException('Database is busy', 150750002);
                    }

                    throw $e;
                }
            }

            $emShare->clear();
        } else {
            $log = $operationLogger->create('chat_room', ['user_id' => $userId]);
            $this->editChatRoom($request, $chatRoom, $log);

            if ($log->getMessage()) {
                $operationLogger->save($log);
                $emShare->flush();
            }
        }

        $output['result'] = 'ok';
        $output['ret'] = $chatRoom->toArray();

        return new JsonResponse($output);
    }

    /**
     * 設定使用者聊天室禁言時間
     *
     * @Route("/user/{userId}/chat_room/ban_at",
     *        name = "api_chat_room_set_ban_at",
     *        requirements = {"userId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @param integer $userId 使用者ID
     * @return JsonResponse
     *
     * @author Ruby 2016.02.25
     */
    public function setBanAtAction(Request $request, $userId)
    {
        $emShare = $this->getEntityManager('share');
        $operationLogger = $this->get('durian.operation_logger');
        $request = $request->request;
        $banAt = $request->get('ban_at');

        if (!$banAt) {
            throw new \InvalidArgumentException('No ban_at specified', 150750003);
        }

        $banAt = new \DateTime($banAt);
        $now = new \DateTime('now');

        $user = $this->findUser($userId);
        $chatRoom = $emShare->find('BBDurianBundle:ChatRoom', $userId);

        if (!$chatRoom) {
            $chatRoom = new ChatRoom($user);
            $emShare->persist($chatRoom);

            $log = $operationLogger->create('chat_room', ['user_id' => $userId]);
            $log->addMessage('readable', 'true');
            $log->addMessage('writable', var_export($chatRoom->isWritable(), true));
            $log->addMessage('ban_at', 'null');
            $operationLogger->save($log);

            try {
                $emShare->flush();
            } catch (\Exception $e) {
                $emShare->clear();

                //同分秒新增聊天室資訊時要丟例外
                if ($e->getPrevious()->getCode() == 23000) {
                    throw new \RuntimeException('Database is busy', 150750004);
                }

                throw $e;
            }
        }

        $chatRoom->setBanAt($banAt);
        $log = $operationLogger->create('chat_room', ['user_id' => $userId]);
        $log->addMessage('ban_at', $banAt->format('Y-m-d H:i:s'));
        $operationLogger->save($log);
        $emShare->flush();

        $output['result'] = 'ok';
        $output['ret'] = $chatRoom->toArray();

        return new JsonResponse($output);
    }

    /**
     * 回傳Doctrine EntityManager
     *
     * @param string $name EntityManager名稱
     * @return \Doctrine\ORM\EntityManager
     */
    private function getEntityManager($name = 'default')
    {
        return $this->getDoctrine()->getManager($name);
    }

    /**
     * 取得使用者
     *
     * @param integer $userId 使用者ID
     * @return User
     */
    private function findUser($userId)
    {
        $user = $this->getEntityManager()->find('BBDurianBundle:User', $userId);

        if (!$user) {
            throw new \RuntimeException('No such user', 150750001);
        }

        return $user;
    }

    /**
     * 修改聊天室權限設定
     *
     * @param Request      $request
     * @param ChatRoom     $chatRoom 聊天室
     * @param LogOperation $log      操作紀錄
     */
    private function editChatRoom($request, $chatRoom, $log)
    {
        if ($request->has('readable')) {
            $readable = (boolean) $request->get('readable');
            if ($chatRoom->isReadable() != $readable) {
                $log->addMessage(
                    'readable',
                    var_export($chatRoom->isReadable(), true),
                    var_export($readable, true)
                );
                $chatRoom->setReadable($readable);
            }
        }

        if ($request->has('writable')) {
            $writable = (boolean) $request->get('writable');
            if ($chatRoom->isWritable() != $writable) {
                $log->addMessage(
                    'writable',
                    var_export($chatRoom->isWritable(), true),
                    var_export($writable, true)
                );
                $chatRoom->setWritable($writable);
            }
        }
    }
}

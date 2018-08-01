<?php

namespace BB\DurianBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use BB\DurianBundle\Entity\User;
use BB\DurianBundle\Entity\UserDetail;
use BB\DurianBundle\Entity\Petition;
use Doctrine\ORM\OptimisticLockException;
use Symfony\Component\HttpFoundation\Request;

class PetitionController extends Controller
{
    /**
     * 新增一筆提交單
     *
     * @Route("/petition",
     *        name = "api_petition_create",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"POST"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function createAction(Request $request)
    {
        $emShare = $this->getEntityManager('share');
        $validator = $this->get('durian.validator');
        $blacklistValidator = $this->get('durian.blacklist_validator');
        $operationLogger = $this->get('durian.operation_logger');
        $parameterHandler = $this->container->get('durian.parameter_handler');
        $request = $request->request;

        $userId = $request->get('user_id');
        $value = trim($request->get('value'));
        $operator = trim($request->get('operator'));
        $verifyBlacklist = (bool) $request->get('verify_blacklist', 1);
        $specialCharacter = ['\0', '\t', '\n', '\r', '\x0B'];

        if (!$userId) {
            throw new \InvalidArgumentException('No user_id specified', 150310007);
        }

        $user = $this->findUser($userId);
        $detail = $this->getUserDetail($userId);
        $domain = $user->getDomain();
        $role = $user->getRole();

        if (!$value) {
            throw new \InvalidArgumentException('Value can not be null', 150310004);
        }

        if (!$operator) {
            throw new \InvalidArgumentException('Operator can not be null', 150310005);
        }

        // 驗證參數編碼是否為utf8
        $checkParameter = [$value, $operator];
        $validator->validateEncode($checkParameter);

        // 指定特殊字元會被移除，所以移除後若是與原字串不同，就代表字串帶有特殊字元
        if (str_replace($specialCharacter, '', $value) != $value) {
            throw new \InvalidArgumentException('Invalid value', 150310014);
        }

        if ($user->isTest()) {
            throw new \RuntimeException('Test user can not create petition', 150310012);
        }

        // 真實姓名需過濾特殊字元
        $value = $parameterHandler->filterSpecialChar($value);

        $metadata = $emShare->getClassMetadata('BBDurianBundle:Petition');
        $valueFieldData = $metadata->getFieldMapping('value');
        $maxValueLength = $valueFieldData['length'];

        if (mb_strlen($value, 'UTF-8') > $maxValueLength) {
            throw new \RuntimeException('Invalid value length given', 150310013);
        }

        // 驗證黑名單
        if ($verifyBlacklist && $role == 1) {
            $criteria['name_real'] = $value;
            $blacklistValidator->validate($criteria, $domain);
        }

        $log = $operationLogger->create('petition', ['user_id' => $userId]);
        $log->addMessage('value', $value);
        $log->addMessage('operator', $operator);
        $operationLogger->save($log);

        $originNameReal = $detail->getNameReal();

        if ($originNameReal == $value) {
            throw new \InvalidArgumentException('The value can not be the same as the original value', 150310006);
        }

        $petition = new Petition($userId, $domain, $role, $value, $originNameReal, $operator);

        $emShare->persist($petition);
        $emShare->flush();

        $output['result'] = 'ok';
        $output['ret'] = $petition->toArray();

        return new JsonResponse($output);
    }

    /**
     * 撤銷一筆提交單
     *
     * @Route("/petition/{petitionId}/cancel",
     *        name = "api_petition_cancel",
     *        requirements = {"petitionId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param integer $petitionId 提交單編號
     *
     * @return JsonResponse
     */
    public function cancelAction($petitionId)
    {
        $emShare = $this->getEntityManager('share');
        $operationLogger = $this->get('durian.operation_logger');

        $petition = $emShare->find('BBDurianBundle:Petition', $petitionId);

        if (!$petition) {
            throw new \RuntimeException('No petition found', 150310001);
        }

        if ($petition->isConfirm()) {
            throw new \RuntimeException('This petition has been confirmed', 150310002);
        }

        if ($petition->isCancel()) {
            throw new \RuntimeException('This petition has been cancelled', 150310003);
        }

        $petition->cancel();

        $activeAt = $petition->getActiveAt()->format('Y-m-d H:i:s');

        $log = $operationLogger->create('petition', ['petition_id' => $petitionId]);
        $log->addMessage('untreated', 'true', 'false');
        $log->addMessage('cancel', 'false', 'true');
        $log->addMessage('activeAt', $activeAt);
        $operationLogger->save($log);

        try {
            $emShare->flush();
        } catch(OptimisticLockException $e) {
            throw new \RuntimeException('Database is busy', 150310008);
        }

        $output['result'] = 'ok';
        $output['ret'] = $petition->toArray();

        return new JsonResponse($output);
    }

    /**
     * 確認通過提交單
     *
     * @Route("/petition/{petitionId}/confirm",
     *        name = "api_petition_confirm",
     *        requirements = {"petitionId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param integer $petitionId 提交單編號
     *
     * @return JsonResponse
     */
    public function confirmAction($petitionId)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $sensitiveLogger = $this->get('durian.sensitive_logger');
        $operationLogger = $this->get('durian.operation_logger');

        $petition = $emShare->find('BBDurianBundle:Petition', $petitionId);

        if (!$petition) {
            throw new \RuntimeException('No petition found', 150310001);
        }

        if ($petition->isConfirm()) {
            throw new \RuntimeException('This petition has been confirmed', 150310002);
        }

        if ($petition->isCancel()) {
            throw new \RuntimeException('This petition has been cancelled', 150310003);
        }

        $value = $petition->getValue();
        $userId = $petition->getUserId();
        $user = $this->findUser($userId);

        $sensitiveLogger->validateAllowedOperator($user);

        $detail = $this->getUserDetail($userId);
        $originNameReal = $detail->getNameReal();
        $detail->setNameReal($value);

        $log = $operationLogger->create('user_detail', ['user_id' => $userId]);
        $log->addMessage('name_real', $originNameReal, $value);
        $operationLogger->save($log);

        $now = new \DateTime();
        $lastModified = $user->getModifiedAt()->format('Y-m-d H:i:s');
        $user->setModifiedAt($now);
        $activeAt = $now->format('Y-m-d H:i:s');

        $log = $operationLogger->create('user', ['id' => $userId]);
        $log->addMessage('modifiedAt', $lastModified, $activeAt);
        $operationLogger->save($log);

        $petition->confirm();

        $activeAt = $petition->getActiveAt()->format('Y-m-d H:i:s');

        $log = $operationLogger->create('petition', ['petition_id' => $petitionId]);
        $log->addMessage('untreated', 'true', 'false');
        $log->addMessage('confirm', 'false', 'true');
        $log->addMessage('activeAt', $activeAt);
        $operationLogger->save($log);

        try {
            $em->flush();
            $emShare->flush();
        } catch(OptimisticLockException $e) {
            throw new \RuntimeException('Database is busy', 150310011);
        }

        $output['result'] = 'ok';
        $output['ret'] = $petition->toArray();
        $sensitiveLogger->writeSensitiveLog();

        return new JsonResponse($output);
    }

    /**
     * 列出提交單
     *
     * @Route("/petition/list",
     *        name = "api_petition_list",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getListAction(Request $request)
    {
        $query = $request->query;
        $emShare = $this->getEntityManager('share');
        $parameterHandler = $this->get('durian.parameter_handler');
        $validator = $this->get('durian.validator');

        $criteria = [
            'id' => $query->get('id'),
            'user_id' => $query->get('user_id'),
            'domain' => $query->get('domain'),
            'role' => $query->get('role'),
            'untreated' => $query->get('untreated'),
            'confirm' => $query->get('confirm'),
            'cancel' => $query->get('cancel')
        ];

        $sort = $query->get('sort');
        $order = $query->get('order');
        $firstResult = $query->get('first_result');
        $maxResults = $query->get('max_results');

        $validator->validatePagination($firstResult, $maxResults);

        $orderBy = $parameterHandler->orderBy($sort, $order);

        $repo = $emShare->getRepository('BBDurianBundle:Petition');

        $total = $repo->countListBy($criteria);

        $petitions = $repo->getListBy($criteria, $orderBy, $firstResult, $maxResults);

        $output['result'] = 'ok';
        $output['ret'] = [];

        foreach ($petitions as $petition) {
            $ret = $petition->toArray();
            $output['ret'][] = $ret;
        }

        $output['pagination']['first_result'] = $firstResult;
        $output['pagination']['max_results'] = $maxResults;
        $output['pagination']['total'] = $total;

        return new JsonResponse($output);
    }

    /**
     * 回傳Doctrine EntityManager
     *
     * @param $name Entity manager name
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
            throw new \RuntimeException('No such user', 150310009);
        }

        return $user;
    }

    /**
     * 取得使用者詳細資訊
     *
     * @param Integer $userId
     * @return UserDetail
     */
    private function getUserDetail($userId)
    {
        $em = $this->getEntityManager();
        $detail = $em->getRepository('BBDurianBundle:UserDetail')->findOneByUser($userId);

        return $detail;
    }
}

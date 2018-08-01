<?php

namespace BB\DurianBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use BB\DurianBundle\Entity\User;
use BB\DurianBundle\Entity\UserDetail;
use BB\DurianBundle\Entity\Promotion;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ParameterBag;

class UserDetailController extends Controller
{
    /**
     * 修改使用者詳細資料
     *
     * @Route("/user/{userId}/detail",
     *        name = "api_user_edit_detail",
     *        requirements = {"userId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @param integer $userId
     * @return JsonResponse
     */
    public function editUserDetailAction(Request $request, $userId)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $sensitiveLogger = $this->get('durian.sensitive_logger');
        $request = $request->request;

        $user = $this->findUser($userId);

        $detail = $this->getUserDetail($user);

        $sensitiveLogger->validateAllowedOperator($user);

        $this->editUserDetail($request, $detail);

        $em->flush();
        $emShare->flush();

        $output['result'] = 'ok';
        $output['ret'] = $detail->toArray();
        $mail = $em->find('BBDurianBundle:UserEmail', $output['ret']['user_id']);
        $output['ret']['email'] = $mail->getEmail();

        $sensitiveLogger->writeSensitiveLog();

        return new JsonResponse($output);
    }

    /**
     * 取得使用者詳細資料
     *
     * @Route("/user/{userId}/detail",
     *        name = "api_user_get_detail",
     *        requirements = {"userId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @param integer $userId
     * @return JsonResponse
     */
    public function getUserDetailByUserAction(Request $request, $userId)
    {
        $em = $this->getEntityManager();
        $sensitiveLogger = $this->get('durian.sensitive_logger');
        $query = $request->query;

        $user = $this->findUser($userId);

        $fields = $query->get('fields', []);
        $subRet = $query->get('sub_ret', false);

        $output['ret'] = $em->getRepository('BBDurianBundle:UserDetail')
            ->getSingleUserDetailBy($userId, $fields);

        $sensitiveLogger->validateAllowedOperator($user);

        if ($subRet) {
            $output['sub_ret']['user'] = $user->toArray();
        }

        $output['result'] = 'ok';

        $sensitiveLogger->writeSensitiveLog();

        return new JsonResponse($output);
    }

    /**
     * 取得使用者詳細資料列表
     *
     * @Route("/user_detail/list",
     *        name = "api_user_detail_list",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function listAction(Request $request)
    {
        $query = $request->query;
        $em = $this->getEntityManager();
        $parameterHandler = $this->get('durian.parameter_handler');
        $sensitiveLogger = $this->get('durian.sensitive_logger');
        $validator = $this->get('durian.validator');

        $userCriteria   = array();
        $detailCriteria = array();
        $userRets       = array();

        $parent = $query->get('parent_id');
        $depth  = $query->get('depth');
        $subRet = $query->get('sub_ret', false);

        $username = trim($query->get('username'));
        $alias    = $query->get('alias');
        $fields   = $query->get('fields', []);

        if ($query->has('username')) {
            $userCriteria['username'] = $username;
        }

        if ($query->has('alias')) {
            $userCriteria['alias'] = $alias;
        }

        $account = $query->get('account');

        $sort  = $query->get('sort');
        $order = $query->get('order');
        $firstResult = $query->get('first_result');
        $maxResults  = $query->get('max_results');

        $validator->validatePagination($firstResult, $maxResults);

        $orderBy = $parameterHandler->orderBy($sort, $order);

        $detailCriteria = $this->processDetailQuery($query);
        $sensitiveLogger->validateAllowedOperator($parent);

        $repo = $em->getRepository('BB\DurianBundle\Entity\UserDetail');

        $total = $repo->countByFuzzyParameter(
            $parent,
            $depth,
            $userCriteria,
            $detailCriteria,
            $account
        );

        $details = $repo->findByFuzzyParameter(
            $parent,
            $depth,
            $userCriteria,
            $detailCriteria,
            $account,
            $orderBy,
            $firstResult,
            $maxResults,
            $fields
        );

        $output['ret'] = array();
        foreach ($details as $key => $value) {
            $user = $this->findUser($details[$key]['user_id']);
            $details[$key]['bank'] = $this->getBankArrayByUser($user, $account);
            $output['ret'][] = $details[$key];

            if ($subRet) {
                $userRet = $user->toArray();

                if (!in_array($userRet, $userRets)) {
                    $userRets[] = $userRet;
                }
            }
        }

        if ($subRet) {
            $output['sub_ret']['user'] = $userRets;
        }

        $output['result'] = 'ok';
        $output['pagination']['first_result'] = $firstResult;
        $output['pagination']['max_results']  = $maxResults;
        $output['pagination']['total'] = $total;

        $sensitiveLogger->writeSensitiveLog();

        return new JsonResponse($output);
    }

    /**
     * 取得使用者詳細資料列表
     *
     * @Route("/v2/user_detail/list",
     *        name = "api_v2_user_detail_list",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     *
     * @author Cathy 2016.11.04
     */
    public function listV2Action(Request $request)
    {
        $query = $request->query;
        $em = $this->getEntityManager();
        $parameterHandler = $this->get('durian.parameter_handler');
        $validator = $this->get('durian.validator');

        $userCriteria   = [];
        $detailCriteria = [];
        $userRets       = [];

        $subRet = $query->get('sub_ret', false);
        $username = trim($query->get('username'));
        $alias = $query->get('alias');
        $fields = $query->get('fields', []);
        $account = $query->get('account');
        $sort = $query->get('sort');
        $order = $query->get('order');
        $firstResult = $query->get('first_result');
        $maxResults = $query->get('max_results');

        if ($query->has('username')) {
            $userCriteria['username'] = $username;
        }

        if ($query->has('alias')) {
            $userCriteria['alias'] = $alias;
        }

        $validator->validatePagination($firstResult, $maxResults);

        $orderBy = $parameterHandler->orderBy($sort, $order);

        $detailCriteria = $this->processDetailQuery($query);

        $repo = $em->getRepository('BBDurianBundle:UserDetail');

        $total = $repo->countByFuzzyParameter(
            null,
            null,
            $userCriteria,
            $detailCriteria,
            $account
        );

        $details = $repo->findByFuzzyParameter(
            null,
            null,
            $userCriteria,
            $detailCriteria,
            $account,
            $orderBy,
            $firstResult,
            $maxResults,
            $fields
        );

        $output['ret'] = [];
        foreach ($details as $key => $value) {
            $user = $this->findUser($details[$key]['user_id']);
            $details[$key]['bank'] = $this->getBankArrayByUser($user, $account);
            $output['ret'][] = $details[$key];

            if ($subRet) {
                $userRet = $user->toArray();

                if (!in_array($userRet, $userRets)) {
                    $userRets[] = $userRet;
                }
            }
        }

        if ($subRet) {
            $output['sub_ret']['user'] = $userRets;
        }

        $output['result'] = 'ok';
        $output['pagination']['first_result'] = $firstResult;
        $output['pagination']['max_results']  = $maxResults;
        $output['pagination']['total'] = $total;

        return new JsonResponse($output);
    }

    /**
     * 取得指定廳使用者詳細資料列表
     *
     * @Route("/user_detail/list_by_domain",
     *        name = "api_user_detail_list_by_domain",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     *
     * @author Cathy 2016.11.04
     */
    public function listByDomainAction(Request $request)
    {
        $query = $request->query;
        $em = $this->getEntityManager();
        $parameterHandler = $this->get('durian.parameter_handler');
        $sensitiveLogger = $this->get('durian.sensitive_logger');
        $validator = $this->get('durian.validator');

        $userCriteria   = [];
        $detailCriteria = [];
        $userRets       = [];

        $parent = $query->get('parent_id');
        $depth  = $query->get('depth');
        $subRet = $query->get('sub_ret', false);
        $username = trim($query->get('username'));
        $alias = $query->get('alias');
        $fields = $query->get('fields', []);
        $account = $query->get('account');
        $sort = $query->get('sort');
        $order = $query->get('order');
        $firstResult = $query->get('first_result');
        $maxResults = $query->get('max_results');

        if ($query->has('username')) {
            $userCriteria['username'] = $username;
        }

        if ($query->has('alias')) {
            $userCriteria['alias'] = $alias;
        }

        $validator->validatePagination($firstResult, $maxResults);

        if (empty($parent)) {
            throw new \InvalidArgumentException('No parent_id specified', 150090040);
        }

        $orderBy = $parameterHandler->orderBy($sort, $order);

        $detailCriteria = $this->processDetailQuery($query);

        $user = $em->find('BBDurianBundle:User', $parent);
        if (!$user) {
            throw new \RuntimeException('No parent found', 150090041);
        }

        $sensitiveLogger->validateAllowedOperator($parent);

        $repo = $em->getRepository('BBDurianBundle:UserDetail');

        $total = $repo->countByFuzzyParameter(
            $parent,
            $depth,
            $userCriteria,
            $detailCriteria,
            $account
        );

        $details = $repo->findByFuzzyParameter(
            $parent,
            $depth,
            $userCriteria,
            $detailCriteria,
            $account,
            $orderBy,
            $firstResult,
            $maxResults,
            $fields
        );

        $output['ret'] = [];
        foreach ($details as $key => $value) {
            $user = $this->findUser($details[$key]['user_id']);
            $details[$key]['bank'] = $this->getBankArrayByUser($user, $account);
            $output['ret'][] = $details[$key];

            if ($subRet) {
                $userRet = $user->toArray();

                if (!in_array($userRet, $userRets)) {
                    $userRets[] = $userRet;
                }
            }
        }

        if ($subRet) {
            $output['sub_ret']['user'] = $userRets;
        }

        $output['result'] = 'ok';
        $output['pagination']['first_result'] = $firstResult;
        $output['pagination']['max_results']  = $maxResults;
        $output['pagination']['total'] = $total;

        $sensitiveLogger->writeSensitiveLog();

        return new JsonResponse($output);
    }

    /**
     * 檢查指定的站別內資料是否重複
     *
     * @Route("/user_detail/check_unique",
     *        name = "api_user_detail_check_unique",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function userDetailCheckUniqueAction(Request $request)
    {
        $em = $this->getEntityManager();
        $sensitiveLogger = $this->get('durian.sensitive_logger');
        $parameterHandler = $this->get('durian.parameter_handler');
        $query = $request->query;

        // 取得傳入的參數
        $domain   = $query->get('domain'); //向下相容
        $parentId = $query->get('parent_id');
        $depth    = $query->get('depth');
        $fields   = $query->has('fields') ? $query->get('fields') : [];

        // initialize
        $unique = true;
        $supportField = array(
            'username',
            'email',
            'nickname',
            'name_real',
            'name_chinese',
            'name_english',
            'country',
            'passport',
            'identity_card',
            'driver_license',
            'insurance_card',
            'health_card',
            'telephone',
            'qq_num',
            'note',
            'wechat'
        );

        if (null == $domain && null == $parentId) {
            throw new \InvalidArgumentException('No parent_id specified', 150090010);
        }

        if ($domain) {
            $parentId = $domain;
        }

        $criteria = array();
        foreach ($fields as $field => $value) {
            // 過濾掉不支援的欄位
            if (in_array($field, $supportField)) {
                $field = \Doctrine\Common\Util\Inflector::camelize($field);
                $criteria[$field] = $value;
            }
        }

        if (empty($criteria)) {
            throw new \InvalidArgumentException('No fields specified', 150090009);
        }

        // 真實姓名需過濾特殊字元
        if (isset($criteria['nameReal'])) {
            $criteria['nameReal'] = $parameterHandler->filterSpecialChar($criteria['nameReal']);
        }

        $sensitiveLogger->validateAllowedOperator($parentId);
        $result = $em->getRepository('BB\DurianBundle\Entity\UserDetail')
                     ->findOneByDomain($parentId, $criteria, $depth);

        if (!empty($result)) {
            $unique = false;
        }

        $output['result'] = 'ok';
        $output['ret']['unique'] = $unique;

        $sensitiveLogger->writeSensitiveLog();

        return new JsonResponse($output);
    }

    /**
     * 新增推廣資料
     *
     * @Route("/user/{userId}/promotion",
     *        name = "api_create_promotion",
     *        requirements = {"userId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"POST"})
     *
     * @param Request $request
     * @param integer $userId 使用者編號
     * @return JsonResponse
     *
     * @author Ruby 2015.10.16
     */
    public function createPromotionAction(Request $request, $userId)
    {
        $operationLogger = $this->get('durian.operation_logger');
        $validator = $this->get('durian.validator');
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $request = $request->request;

        $domain = $request->get('domain', null);
        $url = trim($request->get('url', ''));
        $others = trim($request->get('others', ''));

        if (!$domain) {
            throw new \InvalidArgumentException('No domain specified', 150090020);
        }

        $user = $this->findUser($userId);

        if ($domain != $user->getDomain()) {
            throw new \RuntimeException('No such user', 150090013);
        }

        $promotion = $em->find('BBDurianBundle:Promotion', $userId);

        if ($promotion) {
            throw new \RuntimeException('Promotion for the user already exists', 150090021);
        }

        if ($url) {
            $validator->validateEncode($url);
        }

        if ($others) {
            $validator->validateEncode($others);
        }

        $promotion = new Promotion($user);
        $promotion->setUrl($url);
        $promotion->setOthers($others);
        $em->persist($promotion);

        $log = $operationLogger->create('promotion', ['user_id' => $userId]);
        $log->addMessage('url', $url);
        $log->addMessage('others', $others);
        $operationLogger->save($log);

        try {
            $em->flush();
            $emShare->flush();
        } catch (\Exception $e) {
            if ($e->getPrevious()->getCode() == 23000) {
                throw new \RuntimeException('Database is busy', 150090022);
            }

            throw $e;
        }

        $output['result'] = 'ok';
        $output['ret'] = $promotion->toArray();

        return new JsonResponse($output);
    }

    /**
     * 修改推廣資料
     *
     * @Route("/user/{userId}/promotion",
     *        name = "api_edit_promotion",
     *        requirements = {"userId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @param integer $userId 使用者編號
     * @return JsonResponse
     *
     * @author Ruby 2015.10.16
     */
    public function editPromotionAction(Request $request, $userId)
    {
        $operationLogger = $this->get('durian.operation_logger');
        $validator = $this->get('durian.validator');
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $request = $request->request;

        $domain = $request->get('domain', null);

        if (!$domain) {
            throw new \InvalidArgumentException('No domain specified', 150090020);
        }

        $user = $this->findUser($userId);

        if ($domain != $user->getDomain()) {
            throw new \RuntimeException('No such user', 150090013);
        }

        $promotion = $em->find('BBDurianBundle:Promotion', $userId);

        if (!$promotion) {
            throw new \RuntimeException('No promotion found', 150090023);
        }

        $log = $operationLogger->create('promotion', ['user_id' => $userId]);

        if ($request->has('url')) {
            $url = trim($request->get('url'));

            if ($url != $promotion->getUrl()) {
                $validator->validateEncode($url);
                $log->addMessage('url', $promotion->getUrl(), $url);
                $promotion->setUrl($url);
            }
        }

        if ($request->has('others')) {
            $others = trim($request->get('others'));

            if ($others != $promotion->getOthers()) {
                $validator->validateEncode($others);
                $log->addMessage('others', $promotion->getOthers(), $others);
                $promotion->setOthers($others);
            }
        }

        if ($log->getMessage()) {
            $operationLogger->save($log);
        }

        $em->flush();
        $emShare->flush();

        $output['result'] = 'ok';
        $output['ret'] = $promotion->toArray();

        return new JsonResponse($output);
    }

    /**
     * 回傳推廣資料
     *
     * @Route("/user/{userId}/promotion",
     *        name = "api_get_promotion",
     *        requirements = {"userId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @param integer $userId 使用者編號
     * @return JsonResponse
     *
     * @author Ruby 2015.10.16
     */
    public function getPromotionAction(Request $request, $userId)
    {
        $em = $this->getEntityManager();
        $query = $request->query;

        $domain = $query->get('domain', null);
        $fields = $query->get('fields', []);

        if (!$domain) {
            throw new \InvalidArgumentException('No domain specified', 150090020);
        }

        $user = $this->findUser($userId);

        if ($domain != $user->getDomain()) {
            throw new \RuntimeException('No such user', 150090013);
        }

        $promotion = $em->find('BBDurianBundle:Promotion', $userId);

        $output['result'] = 'ok';
        $output['ret'] = [];

        if ($promotion) {
            $output['ret'] = $this->getPromotionInfo($promotion, $fields);
        }

        return new JsonResponse($output);
    }

    /**
     * 刪除推廣資料
     *
     * @Route("/user/{userId}/promotion",
     *        name = "api_delete_promotion",
     *        requirements = {"userId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"DELETE"})
     *
     * @param Request $request
     * @param integer $userId 使用者編號
     * @return JsonResponse
     *
     * @author Ruby 2015.10.19
     */
    public function deletePromotionAction(Request $request, $userId)
    {
        $operationLogger = $this->get('durian.operation_logger');
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $request = $request->request;

        $domain = $request->get('domain', null);

        if (!$domain) {
            throw new \InvalidArgumentException('No domain specified', 150090020);
        }

        $user = $this->findUser($userId);

        if ($domain != $user->getDomain()) {
            throw new \RuntimeException('No such user', 150090013);
        }

        $promotion = $em->find('BBDurianBundle:Promotion', $userId);

        if (!$promotion) {
            throw new \RuntimeException('No promotion found', 150090023);
        }

        $log = $operationLogger->create('promotion', ['user_id' => $userId]);
        $log->addMessage('promotion', 'removed');
        $operationLogger->save($log);

        $em->remove($promotion);
        $em->flush();
        $emShare->flush();

        $output['result'] = 'ok';

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
        $user = $this->getEntityManager()
                ->find('BB\DurianBundle\Entity\User', $userId);

        if (!$user) {
            throw new \RuntimeException('No such user', 150090013);
        }

        return $user;
    }

    /**
     * Edit userDetail
     *
     * @param ParameterBag $request
     * @param UserDetail $detail
     */
    private function editUserDetail($request, $detail)
    {
        $em = $this->getEntityManager();
        $operationLogger = $this->get('durian.operation_logger');
        $validator = $this->get('durian.validator');
        $blacklistValidator = $this->get('durian.blacklist_validator');
        $parameterHandler = $this->get('durian.parameter_handler');
        $userDetailValidator = $this->get('durian.user_detail_validator');

        $verifyBlacklist = (bool) $request->get('verify_blacklist', 1);
        $parameter = [
            'email' => $request->get('email'),
            'name_real' => $request->get('name_real'),
            'identity_card' => $request->get('identity_card'),
            'telephone' => $request->get('telephone')
        ];

        $user = $detail->getUser();

        // 驗證黑名單
        if ($verifyBlacklist && $user->getRole() == 1) {
            $blacklistValidator->validate($parameter, $user->getDomain());
        }

        $log = $operationLogger->create('user_detail', ['user_id' => $user->getId()]);

        // 最多只可帶入一個有值證件欄位
        $credentialCount = 0;

        $credentials['passport'] = $detail->getPassport();
        $credentials['identity_card'] = $detail->getIdentityCard();
        $credentials['driver_license'] = $detail->getDriverLicense();
        $credentials['insurance_card'] = $detail->getInsuranceCard();
        $credentials['health_card'] = $detail->getHealthCard();

        foreach ($credentials as $field => $value) {
            if ($request->has($field)) {
                $credentials[$field] = $request->get($field);
            }

            if ($credentials[$field] !== '') {
                $credentialCount++;
            }
        }

        if ($credentialCount > 1) {
            throw new \RuntimeException('Cannot specify more than one credential fields', 150090011);
        }

        // 設定userDetail資料
        if ($request->has('email')) {
            $email = trim($request->get('email', ''));

            // 上線時 此部分需拔除 只能透過客服修改
            $this->editUserEmail($user, $email);
        }

        if ($request->has('nickname')) {
            $nickname = trim($request->get('nickname', ''));
            $validator->validateEncode($nickname);
            $nickname = $parameterHandler->filterSpecialChar($nickname);
            $userDetailValidator->validateNicknameLength($nickname);
            $originNickname = $detail->getNickname();

            if ($originNickname != $nickname) {
                $log->addMessage('nickname', $detail->getNickname(), $nickname);
            }

            $detail->setNickname($nickname);
        }

        if ($request->has('name_real')) {
            $nameReal = trim($request->get('name_real', ''));

            if ($user->isTest()) {
                throw new \RuntimeException('Test user can not edit name_real', 150090024);
            }

            $validator->validateEncode($nameReal);
            $userDetailValidator->validateNameRealLength($nameReal);
            $originNameReal = $detail->getNameReal();

            if ($originNameReal != $nameReal) {
                $this->validateNameReal($request, $detail);
                $log->addMessage('name_real', $detail->getNameReal(), $nameReal);
            }

            $nameReal = $parameterHandler->filterSpecialChar($nameReal);
            $detail->setNameReal($nameReal);
        }

        if ($request->has('name_chinese')) {
            $nameChinese = trim($request->get('name_chinese', ''));
            $validator->validateEncode($nameChinese);
            $nameChinese = $parameterHandler->filterSpecialChar($nameChinese);
            $userDetailValidator->validateNameChineseLength($nameChinese);

            if ($detail->getNameChinese() != $nameChinese) {
                $log->addMessage('name_chinese', $detail->getNameChinese(), $nameChinese);
            }

            $detail->setNameChinese($nameChinese);
        }

        if ($request->has('name_english')) {
            $nameEnglish = trim($request->get('name_english', ''));
            $validator->validateEncode($nameEnglish);
            $nameEnglish = $parameterHandler->filterSpecialChar($nameEnglish);
            $userDetailValidator->validateNameEnglishLength($nameEnglish);

            if ($detail->getNameEnglish() != $nameEnglish) {
                $log->addMessage('name_english', $detail->getNameEnglish(), $nameEnglish);
            }

            $detail->setNameEnglish($nameEnglish);
        }

        if ($request->has('country')) {
            $country = trim($request->get('country', ''));
            $validator->validateEncode($country);
            $country = $parameterHandler->filterSpecialChar($country);
            $userDetailValidator->validateCountryLength($country);

            if ($detail->getCountry() != $country) {
                $log->addMessage('country', $detail->getCountry(), $country);
            }
            $detail->setCountry($country);
        }

        if ($request->has('passport')) {
            $oldPassport = $detail->getPassport();
            $newPassport = trim($request->get('passport', ''));
            $validator->validateEncode($newPassport);
            $newPassport = $parameterHandler->filterSpecialChar($newPassport);
            $userDetailValidator->validatePassportLength($newPassport);

            if ($oldPassport !== $newPassport) {
                $log->addMessage('passport', $oldPassport, $newPassport);
                $detail->setPassport($newPassport);
            }
        }

        if ($request->has('identity_card')) {
            $oldIdentityCard = $detail->getIdentityCard();
            $newIdentityCard = trim($request->get('identity_card', ''));
            $validator->validateEncode($newIdentityCard);
            $newIdentityCard = $parameterHandler->filterSpecialChar($newIdentityCard);
            $userDetailValidator->validateIdentityCardLength($newIdentityCard);

            if ($oldIdentityCard !== $newIdentityCard) {
                $log->addMessage('identity_card', $oldIdentityCard, $newIdentityCard);
                $detail->setIdentityCard($newIdentityCard);
            }
        }

        if ($request->has('driver_license')) {
            $oldDriverLicense = $detail->getDriverLicense();
            $newDriverLicense = trim($request->get('driver_license', ''));
            $validator->validateEncode($newDriverLicense);
            $newDriverLicense = $parameterHandler->filterSpecialChar($newDriverLicense);
            $userDetailValidator->validateDriverLicenseLength($newDriverLicense);

            if ($oldDriverLicense !== $newDriverLicense) {
                $log->addMessage('driver_license', $oldDriverLicense, $newDriverLicense);
                $detail->setDriverLicense($newDriverLicense);
            }
        }

        if ($request->has('insurance_card')) {
            $oldInsuranceCard = $detail->getInsuranceCard();
            $newInsuranceCard = trim($request->get('insurance_card', ''));
            $validator->validateEncode($newInsuranceCard);
            $newInsuranceCard = $parameterHandler->filterSpecialChar($newInsuranceCard);
            $userDetailValidator->validateInsuranceCardLength($newInsuranceCard);

            if ($oldInsuranceCard !== $newInsuranceCard) {
                $log->addMessage('insurance_card', $oldInsuranceCard, $newInsuranceCard);
                $detail->setInsuranceCard($newInsuranceCard);
            }
        }

        if ($request->has('health_card')) {
            $oldHealthCard = $detail->getHealthCard();
            $newHealthCard = trim($request->get('health_card', ''));
            $validator->validateEncode($newHealthCard);
            $newHealthCard = $parameterHandler->filterSpecialChar($newHealthCard);
            $userDetailValidator->validateHealthCardLength($newHealthCard);

            if ($oldHealthCard !== $newHealthCard) {
                $log->addMessage('health_card', $oldHealthCard, $newHealthCard);
                $detail->setHealthCard($newHealthCard);
            }
        }

        if ($request->has('telephone')) {
            $telephone = trim($request->get('telephone', ''));
            $userDetailValidator->validateTelephoneLength($telephone);
            // 電話號碼允許格式 ex:+11111111111或11111111111
            $validator->validateTelephone($telephone);

            if ($detail->getTelephone() !== $telephone) {
                $log->addMessage('telephone', $detail->getTelephone(), $telephone);
            }
            $detail->setTelephone($telephone);
        }

        if ($request->has('qq_num')) {
            $qqNum = trim($request->get('qq_num', ''));
            $validator->validateEncode($qqNum);
            $qqNum = $parameterHandler->filterSpecialChar($qqNum);
            $userDetailValidator->validateQQNumLength($qqNum);

            if ($detail->getQQNum() !== $qqNum) {
                $log->addMessage('qq_num', $detail->getQQNum(), $qqNum);
            }
            $detail->setQQNum($qqNum);
        }

        if ($request->has('note')) {
            $note = trim($request->get('note', ''));
            $validator->validateEncode($note);
            $userDetailValidator->validateNoteLength($note);
            $note = $parameterHandler->filterSpecialChar($note);

            if ($detail->getNote() != $note) {
                $log->addMessage('note', $detail->getNote(), $note);
            }

            $detail->setNote($note);
        }

        if ($request->has('password')) {
            $password = $request->get('password', '');
            $validator->validateEncode($password);
            $userDetailValidator->validatePasswordLength($password);

            if ($detail->getPassword() != $password) {
                $log->addMessage('password', $detail->getPassword(), $password);
            }
            $detail->setPassword($request->get('password', ''));
        }

        if ($request->has('birthday')) {
            $birthday = trim($request->get('birthday'));
            if ($birthday) {
                if (!$validator->validateDate($birthday)) {
                    throw new \InvalidArgumentException('Invalid birthday given', 150090025);
                }

                $birthday = new \DateTime($birthday);
                $changeBirthday = $birthday->format('Y-m-d');
            } else {
                $birthday = null;
                $changeBirthday = 'null';
            }

            $originalBirthday = 'null';
            if (null !== $detail->getBirthday()) {
                $originalBirthday = $detail->getBirthday()->format('Y-m-d');
            }

            if ($originalBirthday != $changeBirthday) {
                $log->addMessage('birthday', $originalBirthday, $changeBirthday);
            }

            $detail->setBirthday($birthday);
        }

        if ($request->has('wechat')) {
            $wechat = trim($request->get('wechat', ''));
            $validator->validateEncode($wechat);
            $wechat = $parameterHandler->filterSpecialChar($wechat);
            $userDetailValidator->validateWechatLength($wechat);

            if ($detail->getWechat() !== $wechat) {
                $log->addMessage('wechat', $detail->getWechat(), $wechat);
            }

            $detail->setWechat($wechat);
        }

        if ($log->getMessage()) {
            $operationLogger->save($log);

            $now = new \DateTime();
            $lastModified = $user->getModifiedAt()->format('Y-m-d H:i:s');
            $user->setModifiedAt($now);
            $modifiedAt = $now->format('Y-m-d H:i:s');

            $log = $operationLogger->create('user', ['id' => $user->getId()]);
            $log->addMessage('modifiedAt', $lastModified, $modifiedAt);
            $operationLogger->save($log);
        }
    }

    /**
     * 使用者詳細資料query string處理
     *
     * @param ParameterBag $query
     * @return array
     */
    private function processDetailQuery($query)
    {
        $array = array();

        if ($query->has('nickname')) {
            $array['nickname'] = $query->get('nickname');
        }

        if ($query->has('name_real')) {
            $array['nameReal'] = $query->get('name_real');
        }

        if ($query->has('name_chinese')) {
            $array['nameChinese'] = $query->get('name_chinese');
        }

        if ($query->has('name_english')) {
            $array['nameEnglish'] = $query->get('name_english');
        }

        if ($query->has('country')) {
            $array['country'] = $query->get('country');
        }

        if ($query->has('passport')) {
            $array['passport'] = $query->get('passport');
        }

        if ($query->has('identity_card')) {
            $array['identityCard'] = $query->get('identity_card');
        }

        if ($query->has('driver_license')) {
            $array['driverLicense'] = $query->get('driver_license');
        }

        if ($query->has('insurance_card')) {
            $array['insuranceCard'] = $query->get('insurance_card');
        }

        if ($query->has('health_card')) {
            $array['healthCard'] = $query->get('health_card');
        }

        if ($query->has('telephone')) {
            $array['telephone'] = $query->get('telephone');
        }

        if ($query->has('qq_num')) {
            $array['qqNum'] = $query->get('qq_num');
        }

        if ($query->has('note')) {
            $array['note'] = $query->get('note');
        }

        if ($query->has('birthday')) {
            $array['birthday'] = $query->get('birthday');
        }

        if ($query->has('email')) {
            $array['email'] = $query->get('email');
        }

        if ($query->has('wechat')) {
            $array['wechat'] = $query->get('wechat');
        }

        return $array;
    }

    /**
     * 藉由使用者回傳銀行資訊
     * @param User $user
     * @param string $account
     * @return array
     */
    private function getBankArrayByUser(User $user, $account)
    {
        $criteria = array();
        $fields = array('id', 'account');

        if ($account) {
            $criteria = array('account' => $account);
        }

        $banks = $this->getEntityManager()
                      ->getRepository('BBDurianBundle:Bank')
                      ->getBankArrayBy($user, $fields, $criteria);

        if ($banks) {
            return $banks;
        } else {
            return null;
        }
    }

    /**
     * 取得使用者詳細資訊
     *
     * @param User $user
     * @return UserDetail
     */
    private function getUserDetail(User $user)
    {
        $em = $this->getEntityManager();
        $detail = $em->getRepository('BBDurianBundle:UserDetail')
                     ->findOneByUser($user->getId());

        return $detail;
    }

    /**
     * 驗證真實姓名是否符合規則
     * 規則:1.假設原本真實姓名是小明，
     *        那只能帶入小明-1 | 小明-2 到小明-99 | 小明-* 才屬於合法
     *
     *      2.如果原始真實姓名為空字串則可以跳過檢查
     *
     * @param ParameterBag $request
     * @param UserDetail $detail
     */
    private function validateNameReal($request, UserDetail $detail)
    {
        $nameReal = $request->get('name_real');
        $originNameReal = $detail->getNameReal();

        // 如果原本真實姓名是空字串則不檢查
        if (trim($originNameReal) == '') {
            return;
        }

        // 如果原始真實姓名結尾是-1 ~ -99 或 -* 則去除結尾兩碼
        $regex = '/-(\*|[1-9][0-9]?)$/';

        if (preg_match($regex, $originNameReal, $matches)) {
            $subStrOriNameleng = strlen($originNameReal) - strlen($matches[0]);
            $originNameReal = substr($originNameReal, 0, $subStrOriNameleng);

        }

        //原始真實姓名與帶入真實姓名相符則跳出
        if ($originNameReal == $nameReal) {
            return;
        }

        // 原始真實姓名 + -1 ~ -99 | -*
        $regex = "/^{$originNameReal}-(\*|[1-9][0-9]?)$/";

        // 帶入的真實姓名不符合規則跳例外
        if (!preg_match($regex, $nameReal)) {
            throw new \InvalidArgumentException('Invalid name_real', 150090006);
        }
    }

    /**
     * 修改UserEmail
     *
     * @param User   $user  使用者
     * @param string $email 信箱
     */
    private function editUserEmail($user, $email)
    {
        $userValidator = $this->get('durian.user_validator');

        if ($email != '') {
            $userValidator->validateEmail($email);
        }

        $em = $this->getEntityManager();
        $userEmail = $em->find('BBDurianBundle:UserEmail', $user);

        //新增修改使用者email的操作紀錄
        $operationLogger = $this->get('durian.operation_logger');
        $emailLog = $operationLogger->create('user_email', ['user_id' => $user->getId()]);

        if ($userEmail->getEmail() === $email) {
            return;
        }

        $now = new \DateTime('now');

        $emailLog->addMessage('email', $userEmail->getEmail(), $email);

        if ($userEmail->isConfirm()) {
            $emailLog->addMessage('confirm', 'true', 'false');
            $emailLog->addMessage('confirm_at', $userEmail->getConfirmAt()->format('Y-m-d H:i:s'), 'null');
        }

        $operationLogger->save($emailLog);

        $userEmail->setEmail($email)
            ->setConfirm(false)
            ->removeConfirmAt();
    }

    /**
     * 取得推廣資訊
     *
     * @param Promotion $promotion 推廣資料
     * @param array     $fields    指定查詢的資料
     * @return array
     */
    private function getPromotionInfo(Promotion $promotion, $fields = [])
    {
        $output = [];
        $all = false;
        $output['user_id'] = $promotion->getUserId();

        // 預設傳回全部欄位
        if (empty($fields)) {
            $all = true;
        }

        if ($all || in_array('url', $fields)){
            $output['url'] = $promotion->getUrl();
        }

        if ($all || in_array('others', $fields)){
            $output['others'] = $promotion->getOthers();
        }

        return $output;
    }
}

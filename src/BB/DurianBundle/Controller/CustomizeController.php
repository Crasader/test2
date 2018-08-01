<?php

namespace BB\DurianBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use BB\DurianBundle\Entity\UserPayway;
use Symfony\Component\HttpFoundation\Request;
use BB\DurianBundle\Entity\Cash;

/**
 * 跨聽查詢用API
 */
class CustomizeController extends Controller
{
    /**
     * 取得大股東列表
     *
     * @Route("/customize/supreme_shareholder/list",
     *        name = "api_supreme_shareholder_list",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     *
     * @author Linda 2015.03.26
     */
    public function getSupremeShareholderListAction(Request $request)
    {
        $em = $this->getEntityManager();
        $domainRepo = $this->getEntityManager('share')->getRepository('BBDurianBundle:DomainConfig');
        $userRepo = $em->getRepository('BBDurianBundle:User');
        $validator = $this->get('durian.validator');

        $query       = $request->query;
        $domain      = $query->get('domain');
        $test        = $query->get('test');
        $hiddenTest  = $query->get('hidden_test');
        $firstResult = $query->get('first_result');
        $maxResults  = $query->get('max_results');

        $validator->validatePagination($firstResult, $maxResults);

        $ssList = [];
        $total = 0;

        $domainConfig = $domainRepo->findDomain($domain);

        if ($domainConfig) {
            $parentIds = array_column($domainConfig, 'domain');

            $criteria = [
                'parent_ids'   => $parentIds,
                'test'         => $test,
                'hidden_test'  => $hiddenTest,
                'first_result' => $firstResult,
                'max_results'  => $maxResults
            ];

            $total = $userRepo->countNumOfSupremeShareholder($criteria);
            $ssList = $userRepo->findSupremeShareholder($criteria);
        }

        $output['ret'] = $ssList;
        $output['result'] = 'ok';
        $output['pagination']['first_result'] = $firstResult;
        $output['pagination']['max_results'] = $maxResults;
        $output['pagination']['total'] = $total;

        return new JsonResponse($output);
    }

    /**
     * 取得指定廳內會員的詳細資訊
     *
     * @Route("/customize/user_detail",
     *        name = "api_domain_user_detail",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getUserDetailByDomainAction (Request $request)
    {
        $em = $this->getEntityManager();
        $parameterHandler = $this->get('durian.parameter_handler');
        $validator = $this->get('durian.validator');
        $sensitiveLogger = $this->get('durian.sensitive_logger');
        $currencyOperator = $this->get('durian.currency');

        $query = $request->query;
        $startAt = $query->get('start_at');
        $endAt = $query->get('end_at');
        $domain = $query->get('domain');
        $usernames = $query->get('usernames', []);
        $deposit = $query->get('has_deposit');
        $withdraw = $query->get('has_withdraw');
        $sort = $query->get('sort', []);
        $order = $query->get('order', []);
        $firstResult = $query->get('first_result');
        $maxResults = $query->get('max_results');

        $validator->validatePagination($firstResult, $maxResults);

        if (!$domain) {
            throw new \InvalidArgumentException('No domain specified', 150460002);
        }

        if (!$validator->isInt($domain, true)) {
            throw new \InvalidArgumentException('Invalid domain', 150460003);
        }

        if (!$validator->validateDateRange($startAt, $endAt) && !$usernames) {
            throw new \InvalidArgumentException('No start_at or end_at or usernames specified', 150460004);
        }

        if (!is_array($usernames)) {
            $usernames = [$usernames];
        }

        $user = $em->find('BBDurianBundle:User', $domain);
        $sensitiveLogger->validateAllowedOperator($user);

        $userRepo = $em->getRepository('BBDurianBundle:User');

        $orderBy = $parameterHandler->orderBy($sort, $order);

        $criteria = [
            'domain' => $domain,
            'start_at' => $startAt,
            'end_at' => $endAt,
            'usernames' => $usernames,
            'deposit' => $deposit,
            'withdraw' => $withdraw
        ];

        $limit = [
            'first_result' => $firstResult,
            'max_results' => $maxResults
        ];

        $users = $userRepo->getUserByDomain($criteria, $orderBy, $limit);

        $ret = [];
        $total = 0;

        if ($users) {
            $userIds = [];
            foreach ($users as $user) {
                $userIds[] = $user['id'];
                $ret[$user['id']] = $user;
                $ret[$user['id']]['created_at'] = $user['created_at']->format(\DateTime::ISO8601);
                $ret[$user['id']]['cash_balance'] = null;
                $ret[$user['id']]['cash_currency'] = null;
                $ret[$user['id']]['cash_fake_balance'] = null;
                $ret[$user['id']]['cash_fake_currency'] = null;
                $ret[$user['id']]['email'] = '';
            }

            $userDetailRepo = $em->getRepository('BBDurianBundle:UserDetail');
            $userDetails = $userDetailRepo->getUserDetailByUserId($userIds);

            foreach ($userDetails as $userDetail) {
                $userId = $userDetail['user_id'];
                $ret[$userId]['name_real'] = $userDetail['name_real'];
                $ret[$userId]['country'] = $userDetail['country'];
                $ret[$userId]['telephone'] = $userDetail['telephone'];
                $ret[$userId]['qq_num'] = $userDetail['qq_num'];
                $ret[$userId]['birthday'] = $userDetail['birthday'];
                $ret[$userId]['wechat'] = $userDetail['wechat'];
            }

            $userEmailRepo = $em->getRepository('BBDurianBundle:UserEmail');
            $userEmails = $userEmailRepo->getUserEmailByUserId($userIds);

            foreach ($userEmails as $userEmail) {
                $userId = $userEmail['user_id'];
                $ret[$userId]['email'] = $userEmail['email'];
            }

            $cashRepo = $em->getRepository('BBDurianBundle:Cash');
            $cashs = $cashRepo->getBalanceByUserId($userIds);

            foreach ($cashs as $cash) {
                $userId = $cash['user_id'];
                $ret[$userId]['cash_balance'] = $cash['balance'];
                $ret[$userId]['cash_currency'] = $currencyOperator->getMappedCode($cash['currency']);
            }

            $cashFakeRepo = $em->getRepository('BBDurianBundle:CashFake');
            $cashFakes = $cashFakeRepo->getBalanceByUserId($userIds);

            foreach ($cashFakes as $cashFake) {
                $userId = $cashFake['user_id'];
                $ret[$userId]['cash_fake_balance'] = $cashFake['balance'];
                $ret[$userId]['cash_fake_currency'] = $currencyOperator->getMappedCode($cashFake['currency']);
            }

            $total = $userRepo->countUserByDomain($criteria);
        }

        $output['ret'] = array_values($ret);
        $output['result'] = 'ok';
        $output['pagination']['first_result'] = $firstResult;
        $output['pagination']['max_results'] = $maxResults;
        $output['pagination']['total'] = $total;

        $sensitiveLogger->writeSensitiveLog();

        return new JsonResponse($output);
    }

    /**
     * 複製使用者
     *
     * @Route("/customize/user/copy",
     *        name = "api_customize_user_copy",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"POST"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function copyUserAction(Request $request)
    {
        $em = $this->getEntityManager();
        $redis = $this->get('snc_redis.default');
        $redisMap = $this->get('snc_redis.map');
        $um = $this->get('durian.user_manager');
        $opService = $this->get('durian.op');
        $emShare = $this->getEntityManager('share');
        $request = $request->request;

        $operationLogger = $this->get('durian.operation_logger');
        $validator = $this->get('durian.validator');
        $oldUserId = $request->get('old_user_id');
        $newUserId = $request->get('new_user_id');
        $newParentId = $request->get('new_parent_id');
        $username = trim($request->get('username'));
        $sourceDomain = $request->get('source_domain');
        $targetDomain = $request->get('target_domain');
        $presetLevel = $request->get('preset_level');
        $role = $request->get('role');
        $date = new \DateTime('now');

        if (empty($oldUserId)) {
            throw new \InvalidArgumentException('No old_user_id specified', 150460005);
        }

        if (empty($newUserId)) {
            throw new \InvalidArgumentException('No new_user_id specified', 150460006);
        }

        if (empty($newParentId)) {
            throw new \InvalidArgumentException('No new_parent_id specified', 150460007);
        }

        if (empty($username)) {
            throw new \InvalidArgumentException('No username specified', 150460008);
        }

        if (empty($sourceDomain)) {
            throw new \InvalidArgumentException('No source_domain specified', 150460009);
        }

        if (empty($targetDomain)) {
            throw new \InvalidArgumentException('No target_domain specified', 150460010);
        }

        if (empty($role)) {
            throw new \InvalidArgumentException('No role specified', 150460011);
        }

        if (!$validator->isInt($oldUserId)) {
            throw new \InvalidArgumentException('Invalid oldUserId', 150460012);
        }

        if (!$validator->isInt($newUserId)) {
            throw new \InvalidArgumentException('Invalid newUserId', 150460013);
        }

        if (!$validator->isInt($newParentId)) {
            throw new \InvalidArgumentException('Invalid newParentId', 150460014);
        }

        if (!$validator->isInt($sourceDomain)) {
            throw new \InvalidArgumentException('Invalid sourceDomain', 150460015);
        }

        if (!$validator->isInt($targetDomain)) {
            throw new \InvalidArgumentException('Invalid targetDomain', 150460016);
        }

        $userValidator = $this->get('durian.user_validator');
        $userValidator->validateUsername($username);

        if ($role == 1 && !isset($presetLevel)) {
            throw new \InvalidArgumentException('No preset_level specified', 150460017);
        }

        $criteria = [
            'old_user_id' => $oldUserId,
            'new_user_id' => $newUserId,
            'new_parent_id' => $newParentId,
            'username' => $username,
            'source_domain' => $sourceDomain,
            'target_domain' => $targetDomain,
            'date' => $date->format('Y-m-d H:i:s')
        ];

        $userRepo = $em->getRepository('BBDurianBundle:User');
        $ancestorRepo = $em->getRepository('BBDurianBundle:UserAncestor');
        $detailRepo = $em->getRepository('BBDurianBundle:UserDetail');
        $emailRepo = $em->getRepository('BBDurianBundle:UserEmail');
        $passwordRepo = $em->getRepository('BBDurianBundle:UserPassword');
        $cashRepo = $em->getRepository('BBDurianBundle:Cash');
        $bankRepo = $em->getRepository('BBDurianBundle:Bank');

        $em->beginTransaction();
        $emShare->beginTransaction();
        try {
            $userRepo->copyUser($criteria, $role);
            $user = $em->find('BBDurianBundle:User', $newUserId);

            $depth = 1;
            foreach ($user->getAllParentsId() as $ancestor) {
                $ancestorRepo->copyUserAncestor($newUserId, $ancestor, $depth);
                $depth++;
            }

            $detailRepo->copyUserDetail($newUserId, $oldUserId);
            $emailRepo->copyUserEmail($newUserId, $oldUserId);
            $passwordRepo->copyUserPassword($newUserId, $oldUserId);

            //負數現金使用者複製體系時，餘額為0
            $oldCash = $cashRepo->findOneBy(['user' => $oldUserId]);

            if ($oldCash) {
                $oldBalance = $oldCash->getBalance();

                $cash = new Cash($user, $oldCash->getCurrency());
                $em->persist($cash);
                $em->flush();
            }

            if ($role != 1) {
                $shareLimtRepo = $em->getRepository('BBDurianBundle:ShareLimit');
                $shareLimtNextRepo = $em->getRepository('BBDurianBundle:ShareLimitNext');

                $shareLimtRepo->copyShareLimit($newUserId, $oldUserId);
                $shareLimtData = $shareLimtRepo->getCopyShareLimitId($newUserId);
                $shareLimtNextRepo->copyShareLimitNext($shareLimtData, $newUserId, $oldUserId);
            }

            if ($role == 1) {
                $userLevelRepo = $em->getRepository('BBDurianBundle:UserLevel');
                $userLevelRepo->copyUserLevel($newUserId, $oldUserId, $presetLevel);

                $data = [
                    'index' => $newParentId,
                    'value' => 1
                ];
                $redis->rpush('user_size_queue', json_encode($data));
            }

            if ($role == 5) {
                $userPaywayRepo = $em->getRepository('BBDurianBundle:UserPayway');
                $isMixed = $userPaywayRepo->checkMixedPayway($targetDomain);

                //因大股東只會有一種交易方式，為避免大股東默認為混和廳的交易方式，便替大股東加上交易方式。
                if ($isMixed) {
                    $payway = new UserPayway($user);
                    //目前會進行複寫的大股東只會有現金交易方式
                    $payway->enableCash();

                    $em->persist($payway);
                }

                //對應上層廳主 size + 1
                $data = [
                    'index' => $targetDomain,
                    'value' => 1
                ];
                $redis->rpush('user_size_queue', json_encode($data));
            }

            //更新user身上的last_bank資料與新增出來的銀行資料進行同步
            $bankRepo->copyUserBank($newUserId, $oldUserId);
            $lastBank = $user->getLastBank();
            if ($lastBank) {
                $oldBank = $em->find('BBDurianBundle:Bank', $lastBank);
                $account = $oldBank->getAccount();
                $newBank = $bankRepo->findOneBy(['user' => $newUserId, 'account' => $account]);
                $newBankId = $newBank->getId();
                $user->setLastBank($newBankId);
                $em->persist($user);
                $em->flush();
            }

            $log = $operationLogger->create('user', ['id' => $newUserId]);
            $log->addMessage('username', $user->getUsername());
            $log->addMessage('domain', $targetDomain);
            $log->addMessage('alisas', $user->getAlias());
            $log->addMessage('sub', var_export($user->isSub(), true));
            $log->addMessage('enable', var_export($user->isEnabled(), true));
            $log->addMessage('block', var_export($user->isBlock(), true));
            $log->addMessage('password', 'new');
            $log->addMessage('test', var_export($user->isTest(), true));
            $log->addMessage('currency', $user->getCurrency());
            $log->addMessage('rent', var_export($user->isRent(), true));
            $log->addMessage('password_reset', var_export($user->isPasswordReset(), true));
            $log->addMessage('role', $role);
            $operationLogger->save($log);
            $em->flush();
            $emShare->flush();

            // 加入redis對應表
            $domainKey = $um->getKey($newUserId, 'domain');
            $usernameKey = $um->getKey($newUserId, 'username');

            $map = [
                $domainKey => $user->getDomain(),
                $usernameKey => $username
            ];

            $redisMap->mset($map);

            $em->commit();
            $emShare->commit();

            $cash = $cashRepo->findOneBy(['user' => $newUserId]);

            //如果餘額大於0的話，須補初始明細
            if ($cash && $oldBalance > 0) {
                $options = [
                    'opcode' => 1023,
                    'memo'   => 'Copy-user 複寫體系'
                ];

                $opService->cashDirectOpByRedis($cash, $oldBalance, $options);
            }

            $output['result'] = 'ok';
            $output['ret'] = $user->toArray();

            return new JsonResponse($output);
        } catch (\Exception $e) {
            $em->rollback();
            $emShare->rollback();

            $redisMap->del($um->getKey($newUserId, 'domain'));
            $redisMap->del($um->getKey($newUserId, 'username'));

            throw $e;
        }
    }

    /**
     * 回傳指定時間後未登入總會員數
     *
     * @Route("/customize/domain/inactivated_user",
     *      name = "api_get_domain_inactivated_user",
     *      defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getInactivatedUserByDomainAction(Request $request)
    {
        $em = $this->getEntityManager();
        $repo = $em->getRepository('BBDurianBundle:User');

        $query = $request->query;
        $domain = $query->get('domain');
        $date = $query->get('date');

        $validator = $this->get('durian.validator');

        if (!$domain) {
            throw new \InvalidArgumentException('No domain specified', 150460018);
        }

        if (!$validator->validateDate($date)) {
            throw new \InvalidArgumentException('Invalid date', 150460019);
        }

        $config = $em->find('BBDurianBundle:DomainConfig', $domain);

        if (!$config) {
            throw new \RuntimeException('Not a domain', 150460020);
        }

        $total = $repo->countNotLogin($domain, $date);

        $out = [
            'result' => 'ok',
            'ret' => $total
        ];

        return new JsonResponse($out);
    }

    /**
     * 回傳Doctrine EntityManager
     *
     * @param string $name Entity manager name
     * @return \Doctrine\ORM\EntityManager
     *
     * @author david 2014.09.30
     */
    private function getEntityManager($name = 'default')
    {
        return $this->getDoctrine()->getManager($name);
    }
}

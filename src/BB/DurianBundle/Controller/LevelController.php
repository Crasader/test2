<?php

namespace BB\DurianBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use BB\DurianBundle\Entity\Level;
use BB\DurianBundle\Entity\LevelTransfer;
use BB\DurianBundle\Entity\LevelUrl;
use BB\DurianBundle\Entity\PresetLevel;
use BB\DurianBundle\Entity\LevelCurrency;

/**
 * 層級設定
 */
class LevelController extends Controller
{
    /**
     * 新增層級
     *
     * @Route("/level",
     *        name = "api_create_level",
     *        defaults = {"_format" = "json"})
     * @Method({"POST"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function createAction(Request $request)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $repo = $em->getRepository('BBDurianBundle:Level');
        $validator = $this->get('durian.validator');
        $opLogger = $this->get('durian.operation_logger');
        $currencyOperator = $this->get('durian.currency');
        $parameterHandler = $this->get('durian.parameter_handler');

        $domain = $request->get('domain');
        $alias = trim($request->get('alias'));
        $orderStrategy = $request->get('order_strategy');
        $createdAtStart = $request->get('created_at_start');
        $createdAtEnd = $request->get('created_at_end');
        $depositCount = $request->get('deposit_count');
        $depositTotal = $request->get('deposit_total');
        $depositMax = $request->get('deposit_max');
        $withdrawCount = $request->get('withdraw_count');
        $withdrawTotal = $request->get('withdraw_total');
        $memo = $request->get('memo');

        if (is_null($domain)) {
            throw new \InvalidArgumentException('No domain specified', 150620001);
        }

        if ($alias == '') {
            throw new \InvalidArgumentException('No alias specified', 150620002);
        }

        // 驗證編碼
        $validator->validateEncode($alias);
        $alias = $parameterHandler->filterSpecialChar($alias);

        if (!is_null($memo)) {
            $validator->validateEncode($memo);
        }

        if (!$validator->isInt($orderStrategy) || !in_array($orderStrategy, Level::$legalOrderStrategy)) {
            throw new \InvalidArgumentException('Invalid order_strategy', 150620003);
        }

        // 檢查時間是否為正確格式
        if (!$validator->validateDate($createdAtStart)) {
            throw new \InvalidArgumentException('Invalid created_at_start', 150620005);
        }

        if (!$validator->validateDate($createdAtEnd)) {
            throw new \InvalidArgumentException('Invalid created_at_end', 150620007);
        }

        // 檢查入款次數
        if (!$validator->isInt($depositCount, true)) {
            throw new \InvalidArgumentException('DepositCount must be an integer', 150620009);
        }

        // 檢查出款次數
        if (!$validator->isInt($withdrawCount, true)) {
            throw new \InvalidArgumentException('WithdrawCount must be an integer', 150620011);
        }

        // 檢查入款總額
        if (!$validator->isInt($depositTotal, true)) {
            throw new \InvalidArgumentException('DepositTotal must be an integer', 150620013);
        }

        if ($depositTotal > Level::MAX_AMOUNT) {
            throw new \InvalidArgumentException('The deposit_total is out of limitation', 150620056);
        }

        // 檢查最大入款額度
        if (!$validator->isInt($depositMax, true)) {
            throw new \InvalidArgumentException('DepositMax must be an integer', 150620015);
        }

        if ($depositMax > Level::MAX_AMOUNT) {
            throw new \InvalidArgumentException('The deposit_max is out of limitation', 150620057);
        }

        // 檢查出款總額
        if (!$validator->isInt($withdrawTotal, true)) {
            throw new \InvalidArgumentException('WithdrawTotal must be an integer', 150620017);
        }

        if ($withdrawTotal > Level::MAX_AMOUNT) {
            throw new \InvalidArgumentException('The withdraw_total is out of limitation', 150620058);
        }

        // 檢查廳是否存在
        $domainUser = $em->find('BBDurianBundle:User', $domain);

        if (!$domainUser) {
            throw new \RuntimeException('No domain found', 150620018);
        }

        // 非廳主不可以新增層級
        if ($domainUser->getRole() != 7) {
            throw new \RuntimeException('Not a domain', 150620024);
        }

        $em->beginTransaction();
        $emShare->beginTransaction();
        try {
            // 檢查同一廳是否重複alias
            $this->checkDuplicateAlias($domain, $alias);

            $orderId = $repo->getDefaultOrder($domain);

            $level = new Level($domain, $alias, $orderStrategy, $orderId);
            $level->setDepositCount($depositCount);
            $level->setDepositTotal($depositTotal);
            $level->setDepositMax($depositMax);
            $level->setWithdrawCount($withdrawCount);
            $level->setWithdrawTotal($withdrawTotal);
            $level->setCreatedAtStart(new \DateTime($createdAtStart));
            $level->setCreatedAtEnd(new \DateTime($createdAtEnd));

            if (!is_null($memo)) {
                $level->setMemo(trim($memo));
            }

            $em->persist($level);
            $em->flush();

            $levelId = $level->getId();
            $log = $opLogger->create('level', ['id' => $levelId]);
            $log->addMessage('domain', $domain);
            $log->addMessage('alias', $alias);
            $log->addMessage('order_strategy', $orderStrategy);
            $log->addMessage('order_id', $orderId);
            $log->addMessage('created_at_start', $createdAtStart);
            $log->addMessage('created_at_end', $createdAtEnd);
            $log->addMessage('deposit_count', $depositCount);
            $log->addMessage('deposit_total', $depositTotal);
            $log->addMessage('deposit_max', $depositMax);
            $log->addMessage('withdraw_count', $withdrawCount);
            $log->addMessage('withdraw_total', $withdrawTotal);
            $log->addMessage('memo', $level->getMemo());
            $opLogger->save($log);

            // 建立層級幣別相關資料
            $allCurrency = $currencyOperator->getAvailable();
            foreach (array_keys($allCurrency) as $currency) {
                $levelCurrency = new LevelCurrency($level, $currency);
                $em->persist($levelCurrency);
            }

            $em->flush();
            $emShare->flush();

            // 檢查層級是否超過允許的數量
            $total = $repo->countNumOf(['domain' => $domain]);

            if ($total > Level::MAX_CREATE_NUMBER_OF_DOMAIN) {
                throw new \RangeException('The number of level exceeds the max number', 150620055);
            }

            $em->commit();
            $emShare->commit();
        } catch (\Exception $e) {
            $em->rollback();
            $emShare->rollback();

            // 重複的紀錄
            if (!is_null($e->getPrevious()) && $e->getPrevious()->errorInfo[1] == 1062) {
                throw new \RuntimeException('Database is busy', 150620020);
            }

            throw $e;
        }

        $output = [
            'result' => 'ok',
            'ret' => $level->toArray()
        ];

        return new JsonResponse($output);
    }

    /**
     * 修改層級
     *
     * @Route("/level/{levelId}",
     *        name = "api_set_level",
     *        requirements = {"levelId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @param integer $levelId
     * @return JsonResponse
     */
    public function setAction(Request $request, $levelId)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $validator = $this->get('durian.validator');
        $opLogger = $this->get('durian.operation_logger');

        $alias = $request->get('alias');
        $orderStrategy = $request->get('order_strategy');
        $createdAtStart = $request->get('created_at_start');
        $createdAtEnd = $request->get('created_at_end');
        $depositCount = $request->get('deposit_count');
        $depositTotal = $request->get('deposit_total');
        $depositMax = $request->get('deposit_max');
        $withdrawCount = $request->get('withdraw_count');
        $withdrawTotal = $request->get('withdraw_total');
        $memo = $request->get('memo');

        if (!is_null($alias)) {
            if (trim($alias) == '') {
                throw new \InvalidArgumentException('No alias specified', 150620002);
            }

            // 驗證層級別名的編碼
            $validator->validateEncode($alias);
        }

        // 驗證備註的編碼
        if (!is_null($memo)) {
            $validator->validateEncode($memo);
        }

        // 檢查排序方式
        if (!is_null($orderStrategy)) {
            if (!$validator->isInt($orderStrategy) || !in_array($orderStrategy, Level::$legalOrderStrategy)) {
                throw new \InvalidArgumentException('Invalid order_strategy', 150620003);
            }
        }

        // 檢查時間是否為正確格式
        if (!is_null($createdAtStart) && !$validator->validateDate($createdAtStart)) {
            throw new \InvalidArgumentException('Invalid created_at_start', 150620005);
        }

        if (!is_null($createdAtEnd) && !$validator->validateDate($createdAtEnd)) {
            throw new \InvalidArgumentException('Invalid created_at_end', 150620007);
        }

        // 次數要是整數
        if (!is_null($depositCount) && !$validator->isInt($depositCount, true)) {
            throw new \InvalidArgumentException('DepositCount must be an integer', 150620009);
        }

        if (!is_null($withdrawCount) && !$validator->isInt($withdrawCount, true)) {
            throw new \InvalidArgumentException('WithdrawCount must be an integer', 150620011);
        }

        // 驗證金額是否為整數及超過上限
        if (!is_null($depositTotal)) {
            if (!$validator->isInt($depositTotal, true)) {
                throw new \InvalidArgumentException('DepositTotal must be an integer', 150620013);
            }

            if ($depositTotal > Level::MAX_AMOUNT) {
                throw new \InvalidArgumentException('The deposit_total is out of limitation', 150620056);
            }
        }

        if (!is_null($depositMax)) {
            if (!$validator->isInt($depositMax, true)) {
                throw new \InvalidArgumentException('DepositMax must be an integer', 150620015);
            }

            if ($depositMax > Level::MAX_AMOUNT) {
                throw new \InvalidArgumentException('The deposit_max is out of limitation', 150620057);
            }
        }

        if (!is_null($withdrawTotal)) {
            if (!$validator->isInt($withdrawTotal, true)) {
                throw new \InvalidArgumentException('WithdrawTotal must be an integer', 150620017);
            }

            if ($withdrawTotal > Level::MAX_AMOUNT) {
                throw new \InvalidArgumentException('The withdraw_total is out of limitation', 150620058);
            }
        }

        $level = $em->find('BBDurianBundle:Level', $levelId);

        if (!$level) {
            throw new \RuntimeException('No Level found', 150620022);
        }

        $log = $opLogger->create('level', ['id' => $levelId]);

        $em->beginTransaction();
        $emShare->beginTransaction();
        try {
            // 設定層級別名
            if (!is_null($alias)) {
                $alias = trim($alias);

                if (htmlspecialchars($level->getAlias()) != $alias) {
                    // 檢查同一廳是否重複alias
                    $this->checkDuplicateAlias($level->getDomain(), $alias);

                    $log->addMessage('alias', htmlspecialchars($level->getAlias()), $alias);
                    $level->setAlias($alias);
                }
            }

            // 設定排序方式
            if (!is_null($orderStrategy)) {
                if ($level->getOrderStrategy() != $orderStrategy) {
                    $log->addMessage('order_strategy', $level->getOrderStrategy(), $orderStrategy);
                    $level->setOrderStrategy($orderStrategy);
                }
            }

            // 設定使用者建立時間的條件起始值
            if (!is_null($createdAtStart)) {
                $startTime = new \DateTime($createdAtStart);

                if ($level->getCreatedAtStart() != $startTime) {
                    $originCreatedAtStart = $level->getCreatedAtStart()->format(\DateTime::ISO8601);

                    $log->addMessage('created_at_start', $originCreatedAtStart, $createdAtStart);
                    $level->setCreatedAtStart($startTime);
                }
            }

            // 設定使用者建立時間的條件結束值
            if (!is_null($createdAtEnd)) {
                $endTime = new \DateTime($createdAtEnd);

                if ($level->getCreatedAtEnd() != $endTime) {
                    $originCreatedAtEnd = $level->getCreatedAtEnd()->format(\DateTime::ISO8601);

                    $log->addMessage('created_at_end', $originCreatedAtEnd, $createdAtEnd);
                    $level->setCreatedAtEnd($endTime);
                }
            }

            // 設定入款次數
            if (!is_null($depositCount)) {
                if ($level->getDepositCount() != $depositCount) {
                    $log->addMessage('deposit_count', $level->getDepositCount(), $depositCount);
                    $level->setDepositCount($depositCount);
                }
            }

            // 設定入款總額
            if (!is_null($depositTotal)) {
                if ($level->getDepositTotal() != $depositTotal) {
                    $log->addMessage('deposit_total', $level->getDepositTotal(), $depositTotal);
                    $level->setDepositTotal($depositTotal);
                }
            }

            // 設定最大入款額度
            if (!is_null($depositMax)) {
                if ($level->getDepositMax() != $depositMax) {
                    $log->addMessage('deposit_max', $level->getDepositMax(), $depositMax);
                    $level->setDepositMax($depositMax);
                }
            }

            // 設定出款次數
            if (!is_null($withdrawCount)) {
                if ($level->getWithdrawCount() != $withdrawCount) {
                    $log->addMessage('withdraw_count', $level->getWithdrawCount(), $withdrawCount);
                    $level->setWithdrawCount($withdrawCount);
                }
            }

            // 設定出款總額
            if (!is_null($withdrawTotal)) {
                if ($level->getWithdrawTotal() != $withdrawTotal) {
                    $log->addMessage('withdraw_total', $level->getWithdrawTotal(), $withdrawTotal);
                    $level->setWithdrawTotal($withdrawTotal);
                }
            }

            // 設定備註
            if (!is_null($memo)) {
                $memo = trim($memo);

                if ($level->getMemo() != $memo) {
                    $log->addMessage('memo', $level->getMemo(), $memo);
                    $level->setMemo($memo);
                }
            }

            $opLogger->save($log);
            $em->flush();
            $em->commit();
            $emShare->flush();
            $emShare->commit();
        } catch (\Exception $e) {
            $em->rollback();
            $emShare->rollback();

            // 重複的紀錄
            if (!is_null($e->getPrevious()) && $e->getPrevious()->errorInfo[1] == 1062) {
                throw new \RuntimeException('Database is busy', 150620020);
            }

            throw $e;
        }

        $output = [
            'result' => 'ok',
            'ret' => $level->toArray()
        ];

        return new JsonResponse($output);
    }

    /**
     * 回傳單筆層級資料
     *
     * @Route("/level/{levelId}",
     *        name = "api_get_level",
     *        requirements = {"levelId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param integer $levelId
     * @return JsonResponse
     */
    public function getAction($levelId)
    {
        $em = $this->getEntityManager();
        $level = $em->find('BBDurianBundle:Level', $levelId);

        if (!$level) {
            throw new \RuntimeException('No Level found', 150620022);
        }

        $output = [
            'result' => 'ok',
            'ret' => $level->toArray()
        ];

        return new JsonResponse($output);
    }

    /**
     * 回傳層級列表
     *
     * @Route("/level/list",
     *        name = "api_level_list",
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $query
     * @return JsonResponse
     */
    public function listAction(Request $query)
    {
        $em = $this->getEntityManager();
        $repo = $em->getRepository('BBDurianBundle:Level');
        $parameterHandler = $this->get('durian.parameter_handler');
        $validator = $this->get('durian.validator');

        $domain = $query->get('domain');
        $alias = $query->get('alias');
        $subRet = $query->get('sub_ret', false);
        $sort = $query->get('sort');
        $order = $query->get('order');
        $firstResult = $query->get('first_result');
        $maxResults = $query->get('max_results');

        $validator->validatePagination($firstResult, $maxResults);

        $criteria = [];
        $orderBy = $parameterHandler->orderBy($sort, $order);

        if (!is_null($domain)) {
            $criteria['domain'] = $domain;
        }

        if (!is_null($alias)) {
            $criteria['alias'] = trim($alias);
        }

        $levels = $repo->findBy($criteria, $orderBy, $maxResults, $firstResult);
        $total = $repo->countNumOf($criteria);

        $ret = [];
        $levelIds = [];

        foreach ($levels as $level) {
            $ret[] = $level->toArray();
            $levelIds[] = $level->getId();
        }

        $output = [
            'result' => 'ok',
            'ret' => $ret
        ];

        // 回傳預設層級
        if ($subRet) {
            $presetLevels = $em->getRepository('BBDurianBundle:PresetLevel')
                ->findBy(['level' => $levelIds]);

            $presetLevelSet = [];
            foreach ($presetLevels as $presetLevel) {
                $presetLevelSet[] = $presetLevel->toArray();
            }

            $output['sub_ret']['preset_level'] = $presetLevelSet;
        }

        $output['pagination']['first_result'] = $firstResult;
        $output['pagination']['max_results'] = $maxResults;
        $output['pagination']['total'] = $total;

        return new JsonResponse($output);
    }

    /**
     * 刪除層級
     *
     * @Route("/level/{levelId}",
     *        name = "api_remove_level",
     *        requirements = {"levelId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"DELETE"})
     *
     * @param integer $levelId
     * @return JsonResponse
     */
    public function removeAction($levelId)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $opLogger = $this->get('durian.operation_logger');
        $repo = $em->getRepository('BBDurianBundle:Level');
        $ltRepo = $em->getRepository('BBDurianBundle:LevelTransfer');

        $level = $em->find('BBDurianBundle:Level', $levelId);

        if (!$level) {
            throw new \RuntimeException('No Level found', 150620022);
        }

        // 層級人數不為0時,不能刪除層級
        if ($level->getUserCount() != 0) {
            throw new \RuntimeException('Can not remove Level when Level has user', 150620023);
        }

        // 若被設定成使用者的預設層級，不能刪除層級
        $presetLevel = $em->getRepository('BBDurianBundle:PresetLevel')
            ->findOneBy(['level' => $levelId]);

        if ($presetLevel) {
            throw new \RuntimeException('Can not remove when Level is set by PresetLevel', 150620044);
        }

        // 若會員層級在轉移中，不能刪除層級
        $ltSource = $ltRepo->findOneBy(['source' => $levelId]);
        $ltTarget = $ltRepo->findOneBy(['target' => $levelId]);

        if ($ltSource || $ltTarget) {
            throw new \RuntimeException('User Level transferring', 150620030);
        }

        // 若有層級網址設定,不能刪除
        $levelUrl = $em->getRepository('BBDurianBundle:LevelUrl')
            ->findOneBy(['level' => $levelId]);

        if ($levelUrl) {
            throw new \RuntimeException(
                'Can not remove when Level is set by LevelUrl',
                150620059
            );
        }

        // 刪除層級相關資料
        $repo->removeLevel($level);

        $log = $opLogger->create('level', ['id' => $levelId]);
        $opLogger->save($log);

        $em->remove($level);
        $em->flush();
        $emShare->flush();

        $output = ['result' => 'ok'];

        return new JsonResponse($output);
    }

    /**
     * 回傳層級轉移列表
     *
     * @Route("/level/transfer/list",
     *        name = "api_level_transfer_list",
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $query
     * @return JsonResponse
     */
    public function transferListAction(Request $query)
    {
        $em = $this->getEntityManager();

        $domain = $query->get('domain');

        $criteria = [];

        if (!is_null($domain)) {
            $criteria['domain'] = $domain;
        }

        $levelTransfers = $em->getRepository('BBDurianBundle:LevelTransfer')
            ->findBy($criteria);

        $ret = [];
        foreach ($levelTransfers as $levelTransfer) {
            $ret[] = $levelTransfer->toArray();
        }

        $output = [
            'result' => 'ok',
            'ret' => $ret
        ];

        return new JsonResponse($output);
    }

    /**
     * 層級轉移
     *
     * @Route("/level/transfer",
     *        name = "api_level_transfer",
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function transferAction(Request $request)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $opLogger = $this->get('durian.operation_logger');
        $redis = $this->container->get('snc_redis.default_client');

        $sources = $request->get('source');
        $target = $request->get('target');

        if (!is_array($sources) || empty($sources)) {
            throw new \InvalidArgumentException('No source specified', 150620031);
        }
        $sourceSet = array_unique($sources);

        if (!$target) {
            throw new \InvalidArgumentException('No target specified', 150620025);
        }

        // 檢查來源與目標是否相同
        if (in_array($target, $sourceSet)) {
            throw new \InvalidArgumentException(
                'Source level can not be the same as target level',
                150620026
            );
        }

        $sourceLevels = $em->getRepository('BBDurianBundle:Level')->findBy(['id' => $sourceSet]);

        if (count($sourceLevels) != count($sourceSet)) {
            throw new \RuntimeException('No source level found', 150620027);
        }

        $targetLevel = $em->find('BBDurianBundle:Level', $target);

        if (!$targetLevel) {
            throw new \RuntimeException('No target level found', 150620028);
        }

        // 檢查來源和目標層級是否同一廳
        $domain = $targetLevel->getDomain();
        foreach ($sourceLevels as $sourceLevel) {
            if ($sourceLevel->getDomain() != $domain) {
                throw new \RuntimeException('Cannot transfer to different domain', 150620029);
            }
        }

        // 檢查是否同一廳已有轉移的資料
        $levelTransfer = $em->getRepository('BBDurianBundle:LevelTransfer')
            ->findBy(['domain' => $domain]);

        if ($levelTransfer) {
            throw new \RuntimeException('User Level transferring', 150620030);
        }

        $em->beginTransaction();
        $emShare->beginTransaction();
        try {
            // 儲存轉移的資訊
            foreach ($sourceSet as $source) {
                $levelTransfer = new LevelTransfer($domain, $source, $target);
                $em->persist($levelTransfer);

                $majorKey = [
                    'domain' => $domain,
                    'source' => $source
                ];
                $log = $opLogger->create('level_transfer', $majorKey);
                $log->addMessage('target', $levelTransfer->getTarget());
                $log->addMessage('createdAt', $levelTransfer->getCreatedAt()->format(\DateTime::ISO8601));
                $opLogger->save($log);
            }
            $em->flush();
            $em->commit();
            $emShare->flush();
            $emShare->commit();

            foreach ($sourceSet as $source) {
                $data = [
                    'domain' => $domain,
                    'source' => $source,
                    'target' => $target
                ];
                $redis->rpush('level_transfer_queue', json_encode($data));
            }
        } catch (\Exception $e) {
            $em->rollback();
            $emShare->rollback();

            // 重複的紀錄
            if (!is_null($e->getPrevious()) && $e->getPrevious()->errorInfo[1] == 1062) {
                throw new \RuntimeException('Database is busy', 150620020);
            }

            throw $e;
        }

        $output = ['result' => 'ok'];

        return new JsonResponse($output);
    }

    /**
     * 新增層級網址
     *
     * @Route("/level_url",
     *        name = "api_create_level_url",
     *        defaults = {"_format" = "json"})
     * @Method({"POST"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function createUrlAction(Request $request)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $opLogger = $this->get('durian.operation_logger');
        $validator = $this->get('durian.validator');
        $parameterHandler = $this->get('durian.parameter_handler');

        $levelId = $request->get('level_id');
        $url = trim($request->get('url'));
        $enable = (bool) $request->get('enable', 0); // 停啟用

        $validator->validateEncode($url);
        $url = $parameterHandler->filterSpecialChar($url);

        if (!$levelId) {
            throw new \InvalidArgumentException('No level_id specified', 150620038);
        }

        if ($url == '') {
            throw new \InvalidArgumentException('No url specified', 150620033);
        }

        $level = $em->find('BBDurianBundle:Level', $levelId);

        if (!$level) {
            throw new \RuntimeException('No Level found', 150620022);
        }

        try {
            $levelUrl = new LevelUrl($level, $url);
            $em->persist($levelUrl);

            // 檢查層級是否存在已啟用網址
            if ($enable) {
                $criteria = [
                    'level' => $levelId,
                    'enable' => true
                ];
                $check = $em->getRepository('BBDurianBundle:LevelUrl')->findOneBy($criteria);

                if ($check) {
                    throw new \RuntimeException('Enable LevelUrl Already Exist', 150620032);
                }
                $levelUrl->enable();
            }
            $em->flush();

            $log = $opLogger->create('level_url', ['id' => $levelUrl->getId()]);
            $log->addMessage('url', $url);
            $log->addMessage('enable', var_export($enable, true));
            $opLogger->save($log);

            $em->flush();
            $emShare->flush();
        } catch (\Exception $e) {
            // 重複的紀錄
            if (!is_null($e->getPrevious()) && $e->getPrevious()->errorInfo[1] == 1062) {
                throw new \RuntimeException('Database is busy', 150620020);
            }

            throw $e;
        }

        $output = [
            'result' => 'ok',
            'ret' => $levelUrl->toArray()
        ];

        return new JsonResponse($output);
    }

    /**
     * 回傳層級網址列表
     *
     * @Route("/level_url/list",
     *        name = "api_level_url_list",
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function urlListAction(Request $request)
    {
        $em = $this->getEntityManager();
        $validator = $this->get('durian.validator');
        $repo = $em->getRepository('BBDurianBundle:LevelUrl');

        $query = $request->query;
        $domain = $query->get('domain');
        $levelId = $query->get('level_id');
        $enable = $query->get('enable');
        $url = $query->get('url');
        $firstResult = $query->get('first_result');
        $maxResults = $query->get('max_results');

        $validator->validatePagination($firstResult, $maxResults);

        $criteria = [];

        if ($domain) {
            $criteria['domain'] = $domain;
        }

        if ($levelId) {
            $criteria['level'] = $levelId;
        }

        if (!is_null($enable)) {
            $criteria['enable'] = $enable;
        }

        if ($url) {
            $criteria['url'] = trim($url);
        }

        $ret = $repo->getLevelUrl($criteria, $firstResult, $maxResults);
        $total = $repo->countLevelUrl($criteria);

        $output = [
            'result' => 'ok',
            'ret' => $ret,
            'pagination' => [
                'first_result' => $firstResult,
                'max_results' => $maxResults,
                'total' => $total
            ]
        ];

        return new JsonResponse($output);
    }

    /**
     * 修改層級網址
     *
     * @Route("/level_url/{levelUrlId}",
     *        name = "api_set_level_url",
     *        requirements = {"levelUrlId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @param integer $levelUrlId
     * @return JsonResponse
     */
    public function setUrlAction(Request $request, $levelUrlId)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $opLogger = $this->get('durian.operation_logger');
        $validator = $this->get('durian.validator');
        $parameterHandler = $this->get('durian.parameter_handler');

        $url = trim($request->get('url'));
        $enable = (bool) $request->get('enable'); // 是否啟用

        $validator->validateEncode($url);
        $url = $parameterHandler->filterSpecialChar($url);

        $levelUrl = $em->find('BBDurianBundle:LevelUrl', $levelUrlId);

        if (!$levelUrl) {
            throw new \RuntimeException('No LevelUrl found', 150620035);
        }

        $log = $opLogger->create('level_url', ['id' => $levelUrlId]);
        $oldUrl = $levelUrl->getUrl();

        if ($url != '' && $url != $oldUrl) {
            $levelUrl->setUrl($url);

            $log->addMessage('url', $oldUrl, $url);
        }

        if ($enable != $levelUrl->isEnabled()) {
            if ($enable) {
                // 設定啟用網址時需檢查層級啟用網址是否存在
                $criteria = [
                    'level' => $levelUrl->getLevel(),
                    'enable' => true
                ];
                $check = $em->getRepository('BBDurianBundle:LevelUrl')->findOneBy($criteria);

                if ($check) {
                    throw new \RuntimeException('Enable LevelUrl Already Exist', 150620032);
                }
                $levelUrl->enable();
            } else {
                $levelUrl->disable();
            }
            $log->addMessage('enable', var_export(!$enable, true), var_export($enable, true));
        }
        $opLogger->save($log);
        $em->flush();
        $emShare->flush();

        $output = [
            'result' => 'ok',
            'ret' => $levelUrl->toArray()
        ];

        return new JsonResponse($output);
    }

    /**
     * 刪除層級網址
     *
     * @Route("/level_url/{levelUrlId}",
     *        name = "api_remove_level_url",
     *        requirements = {"levelUrlId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"DELETE"})
     *
     * @param integer $levelUrlId
     * @return JsonResponse
     */
    public function removeUrlAction($levelUrlId)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $opLogger = $this->get('durian.operation_logger');

        $levelUrl = $em->find('BBDurianBundle:LevelUrl', $levelUrlId);

        if (!$levelUrl) {
            throw new \RuntimeException('No LevelUrl found', 150620035);
        }

        $log = $opLogger->create('level_url', ['id' => $levelUrlId]);
        $opLogger->save($log);

        $em->remove($levelUrl);
        $em->flush();
        $emShare->flush();

        $output = ['result' => 'ok'];

        return new JsonResponse($output);
    }

    /**
     * 設定層級順序
     *
     * @Route("/level/order",
     *        name = "api_set_level_order",
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function setOrderAction(Request $request)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $opLogger = $this->get('durian.operation_logger');
        $repo = $em->getRepository('BBDurianBundle:Level');

        $levels = $request->get('levels');

        if (!is_array($levels)) {
            throw new \InvalidArgumentException('No levels specified', 150620037);
        }

        // 將帶入參數的key值重編
        $levels = array_values($levels);

        if (!isset($levels[0]['level_id'])) {
            throw new \InvalidArgumentException('No level_id specified', 150620038);
        }

        // 取得層級第一筆廳主做為比對使用
        $domainLevel = $em->find('BBDurianBundle:Level', $levels[0]['level_id']);

        if (!$domainLevel) {
            throw new \RuntimeException('No Level found', 150620022);
        }
        $domain = $domainLevel->getDomain();

        $em->beginTransaction();
        $emShare->beginTransaction();
        try {
            foreach ($levels as $levelArray) {
                if (!isset($levelArray['level_id'])) {
                    throw new \InvalidArgumentException('No level_id specified', 150620038);
                }
                $levelId = $levelArray['level_id'];

                if (!isset($levelArray['order_id'])) {
                    throw new \InvalidArgumentException('No order_id specified', 150620039);
                }
                $orderId = $levelArray['order_id'];

                if (!isset($levelArray['version'])) {
                    throw new \InvalidArgumentException('No version specified', 150620040);
                }
                $version = $levelArray['version'];

                $level = $em->find('BBDurianBundle:Level', $levelId);

                if (!$level) {
                    throw new \RuntimeException('No Level found', 150620022);
                }

                if ($domain != $level->getDomain()) {
                    throw new \RuntimeException('Level domain not match', 150620041);
                }

                if ($version != $level->getVersion()) {
                    throw new \RuntimeException('Level order has been changed', 150620042);
                }

                $originOid = $level->getOrderId();

                if ($orderId != $originOid) {
                    $level->setOrderId($orderId);

                    $log = $opLogger->create('level', ['id' => $levelId]);
                    $log->addMessage('order_id', $originOid, $orderId);
                    $opLogger->save($log);
                }
            }
            $em->flush();
            $emShare->flush();
            $duplicatedLevel = $repo->getDuplicatedOrder($domain);

            if (!empty($duplicatedLevel)) {
                throw new \RuntimeException('Duplicate Level orderId', 150620043);
            }

            $em->commit();
            $emShare->commit();

            $levels = $repo->findBy(['domain' => $domain]);

            $ret = [];

            foreach ($levels as $level) {
                $levelArray = $level->toArray();
                $ret[] = $levelArray;
            }

            $output['result'] = 'ok';
            $output['ret'] = $ret;

        } catch (\Exception $e) {
            $em->rollback();
            $emShare->rollback();

            throw $e;
        }

        return new JsonResponse($output);
    }

    /**
     * 回傳層級內使用者資料
     *
     * @Route("/level/{levelId}/users",
     *        name = "api_get_level_users",
     *        requirements = {"levelId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @param integer $levelId
     * @return JsonResponse
     */
    public function getUsersAction(Request $request, $levelId)
    {
        $validator = $this->get('durian.validator');

        $locked = $request->get('locked');
        $firstResult = $request->get('first_result');
        $maxResults = $request->get('max_results');
        $validator->validatePagination($firstResult, $maxResults);

        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');

        $level = $em->find('BBDurianBundle:Level', $levelId);

        if (!$level) {
            throw new \RuntimeException('No Level found', 150620022);
        }

        $domain = $em->find('BBDurianBundle:User', $level->getDomain());
        $config = $emShare->find('BBDurianBundle:DomainConfig', $level->getDomain());

        if (!$domain) {
            throw new \RuntimeException('No domain found', 150620018);
        }

        $criteria = ['level_id' => $levelId];

        if (!is_null($locked) && trim($locked) != '') {
            $criteria['locked'] = $locked;
        }

        $repo = $em->getRepository('BBDurianBundle:UserLevel');
        $users = $repo->getUsersByLevel($criteria, $firstResult, $maxResults);
        $total = $repo->countUsersByLevel($criteria);

        $output = [
            'result' => 'ok',
            'ret' => [
                'domain' => $config->getDomain(),
                'domain_name' => $config->getName(),
                'domain_alias' => $domain->getAlias(),
                'users' => $users
            ],
            'pagination' => [
                'first_result' => $firstResult,
                'max_results' => $maxResults,
                'total' => $total
            ]
        ];

        return new JsonResponse($output);
    }

    /**
     * 新增預設層級
     *
     * @Route("/user/{userId}/preset_level",
     *        name = "api_create_preset_level",
     *        requirements = {"userId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"POST"})
     *
     * @param Request $request
     * @param integer $userId
     * @return JsonResponse
     */
    public function createPresetAction(Request $request, $userId)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $opLogger = $this->get('durian.operation_logger');

        $levelId = $request->get('level_id');

        if (!$levelId) {
            throw new \InvalidArgumentException('No level_id specified', 150620038);
        }

        // 檢查使用者是否存在
        $user = $em->find('BBDurianBundle:User', $userId);

        if (!$user) {
            throw new \RuntimeException('No such user', 150620045);
        }

        // 檢查層級是否存在
        $level = $em->find('BBDurianBundle:Level', $levelId);

        if (!$level) {
            throw new \RuntimeException('No Level found', 150620022);
        }

        // 檢查層級的廳是否與使用者的相同
        if ($user->getDomain() != $level->getDomain()) {
            throw new \RuntimeException('Level domain not match', 150620041);
        }

        try {
            // 檢查使用者是否已設定預設層級
            $presetLevel = $em->find('BBDurianBundle:PresetLevel', $userId);

            if ($presetLevel) {
                throw new \RuntimeException('PresetLevel already exists', 150620047);
            }

            $presetLevel = new PresetLevel($user, $level);
            $em->persist($presetLevel);

            $log = $opLogger->create('preset_level', ['user_id' => $userId]);
            $log->addMessage('level_id', $levelId);
            $opLogger->save($log);

            $em->flush();
            $emShare->flush();
        } catch (\Exception $e) {
            // 重複使用者的紀錄
            if (!is_null($e->getPrevious()) && $e->getPrevious()->errorInfo[1] == 1062) {
                throw new \RuntimeException('Database is busy', 150620020);
            }

            throw $e;
        }

        $output = [
            'result' => 'ok',
            'ret' => $presetLevel->toArray()
        ];

        return new JsonResponse($output);
    }

    /**
     * 刪除預設層級
     *
     * @Route("/user/{userId}/preset_level",
     *        name = "api_remove_preset_level",
     *        requirements = {"userId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"DELETE"})
     *
     * @param integer $userId
     * @return JsonResponse
     */
    public function removePresetAction($userId)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $opLogger = $this->get('durian.operation_logger');

        $presetLevel = $em->find('BBDurianBundle:PresetLevel', $userId);

        if (!$presetLevel) {
            throw new \RuntimeException('No PresetLevel found', 150620046);
        }

        $log = $opLogger->create('preset_level', ['user_id' => $userId]);
        $opLogger->save($log);

        $em->remove($presetLevel);
        $em->flush();
        $emShare->flush();

        $output = ['result' => 'ok'];

        return new JsonResponse($output);
    }

    /**
     * 設定層級幣別相關設定
     *
     * @Route("/level/{levelId}/currency",
     *        name = "api_set_level_currency",
     *        requirements = {"levelId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @param integer $levelId
     * @return JsonResponse
     */
    public function setCurrencyAction(Request $request, $levelId)
    {
        $chelper = $this->get('durian.currency');
        $operationLogger = $this->get('durian.operation_logger');
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $repo = $em->getRepository('BBDurianBundle:LevelCurrency');

        $currency = $request->get('currency');
        $paymentChargeId = $request->get('payment_charge_id');

        if (is_null($currency)) {
            throw new \InvalidArgumentException('No currency specified', 150620048);
        }

        if ($currency == '') {
            throw new \InvalidArgumentException('No currency specified', 150620048);
        }

        if (!$chelper->isAvailable($currency)) {
            throw new \InvalidArgumentException('Illegal currency', 150620049);
        }
        $currencyNum = $chelper->getMappedNum($currency);

        if (is_null($paymentChargeId) || $paymentChargeId == '') {
            throw new \InvalidArgumentException('No PaymentCharge specified', 150620050);
        }

        // 抓取支付設定資料
        $paymentCharge = $em->find('BBDurianBundle:PaymentCharge', $paymentChargeId);

        if (!$paymentCharge) {
            throw new \RuntimeException('No PaymentCharge found', 150620051);
        }

        // 抓取層級幣別相關資料
        $criteria = [
            'levelId' => $levelId,
            'currency' => $currencyNum
        ];
        $levelCurrency = $repo->findOneBy($criteria);

        if (!$levelCurrency) {
            throw new \RuntimeException('No LevelCurrency found', 150620052);
        }

        if ($levelCurrency->getPaymentCharge() != $paymentCharge) {
            $oldPaymentChargeId = null;

            if ($levelCurrency->getPaymentCharge()) {
                $oldPaymentChargeId = $levelCurrency->getPaymentCharge()->getId();
            }
            $levelCurrency->setPaymentCharge($paymentCharge);

            // 寫入操作紀錄
            $majorKey = [
                'levelId' => $levelId,
                'currency' => $currencyNum
            ];
            $log = $operationLogger->create('level_currency', $majorKey);

            $log->addMessage('payment_charge_id', $oldPaymentChargeId, $paymentChargeId);
            $operationLogger->save($log);
            $em->flush();
            $emShare->flush();
        }

        $output = [
            'result' => 'ok',
            'ret' => $levelCurrency->toArray()
        ];

        return new JsonResponse($output);
    }

    /**
     * 取得層級幣別相關資料
     *
     * @Route("/level/{levelId}/currency",
     *        name = "api_get_level_currency",
     *        requirements = {"levelId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @param integer $levelId
     * @return JsonResponse
     */
    public function getCurrencyAction(Request $request, $levelId)
    {
        $chelper = $this->get('durian.currency');
        $em = $this->getEntityManager();
        $repo = $em->getRepository('BBDurianBundle:LevelCurrency');
        $currency = $request->get('currency');

        // 判斷currency有沒有帶入，沒有帶入則不檢查
        if (!is_null($currency) && $currency != '') {
            // 判斷幣別是否支援
            if (!$chelper->isAvailable($currency)) {
                throw new \InvalidArgumentException('Illegal currency', 150620049);
            }
            $currencyNum = $chelper->getMappedNum($currency);

            $params['currency'] = $currencyNum;
        }
        $params['levelId'] = $levelId;

        $ret = [];
        $levelCurrencies = $repo->findBy($params);

        foreach ($levelCurrencies as $levelCurrency) {
            $ret[] = $levelCurrency->toArray();
        }

        $output = [
            'result' => 'ok',
            'ret' => $ret
        ];

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

    /**
     * 檢查同一廳的層級是否有重複alias
     *
     * @param integer $domain 廳
     * @param integer $alias 層級別名
     */
    private function checkDuplicateAlias($domain, $alias)
    {
        $em = $this->getEntityManager();

        $criteria = [
            'domain' => $domain,
            'alias' => $alias
        ];

        $duplicateLevel = $em->getRepository('BBDurianBundle:Level')
            ->findOneBy($criteria);

        if ($duplicateLevel) {
             throw new \RuntimeException('Duplicate Level alias', 150620019);
        }
    }
}

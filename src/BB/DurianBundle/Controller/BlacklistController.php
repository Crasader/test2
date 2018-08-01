<?php

namespace BB\DurianBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use BB\DurianBundle\Entity\Blacklist;
use BB\DurianBundle\Entity\BlacklistOperationLog;
use BB\DurianBundle\Entity\RemovedBlacklist;
use Symfony\Component\HttpFoundation\Request;

class BlacklistController extends Controller
{
    /**
     * 新增黑名單
     *
     * @Route("/blacklist",
     *        name = "api_blacklist_create",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"POST"})
     *
     * @param Request $request
     * @return JsonResponse
     *
     * @author Ruby 2015.04.20
     */
    public function createAction(Request $request)
    {
        $emShare = $this->getEntityManager('share');
        $repo = $emShare->getRepository('BBDurianBundle:Blacklist');
        $validator = $this->get('durian.validator');
        $userValidator = $this->get('durian.user_validator');
        $operationLogger = $this->get('durian.operation_logger');
        $parameterHandler = $this->get('durian.parameter_handler');

        $request = $request->request;
        $domain = $request->get('domain', 0);
        $note = trim($request->get('note'));
        $operator = trim($request->get('operator'));
        $clientIp = $request->get('client_ip');
        $controlTerminal = (bool) $request->get('control_terminal', 0);

        $criteria = [
            'account' => trim($request->get('account', '')),
            'identity_card' => trim($request->get('identity_card', '')),
            'name_real' => trim($request->get('name_real', '')),
            'telephone' => trim($request->get('telephone', '')),
            'email' => trim($request->get('email', '')),
            'ip' => trim($request->get('ip', ''))
        ];

        //確認新增欄位不可超過一個
        $this->checkNumOfCreate($criteria);

        if (!$request->has('operator')) {
            throw new \InvalidArgumentException('No operator specified', 150650031);
        }

        //非控端操作時不可新增全廳黑名單
        if (!$controlTerminal && !$domain) {
            throw new \InvalidArgumentException('Creating whole domain blacklist is not allowed', 150650032);
        }

        $validator->validateEncode($operator);

        if ($domain) {
            if (!$validator->isInt($domain)) {
                throw new \InvalidArgumentException('Invalid domain', 150650001);
            }

            $domainConfig = $emShare->find('BBDurianBundle:DomainConfig', $domain);

            if (!$domainConfig) {
                throw new \RuntimeException('No such domain', 150650002);
            }
        }

        $blacklist = new Blacklist($domain);
        $blacklist->setControlTerminal($controlTerminal);

        if ($request->has('account')) {
            $validator->validateEncode($criteria['account']);
            $criteria['account'] = $parameterHandler->filterSpecialChar($criteria['account']);
            $this->validateAccount($criteria['account']);
            $oldBlacklist = $repo->findOneBy([
                'domain' => $domain,
                'account' => $criteria['account']
            ]);

            if ($oldBlacklist) {
                throw new \RuntimeException('Account already exists in blacklist', 150650004);
            }

            $blacklist->setAccount($criteria['account']);
        }

        if ($request->has('identity_card')) {
            $validator->validateEncode($criteria['identity_card']);
            $criteria['identity_card'] = $parameterHandler->filterSpecialChar($criteria['identity_card']);
            $oldBlacklist = $repo->findOneBy([
                'domain' => $domain,
                'identityCard' => $criteria['identity_card']
            ]);

            if ($oldBlacklist) {
                throw new \RuntimeException('Identity_card already exists in blacklist', 150650005);
            }

            $blacklist->setIdentityCard($criteria['identity_card']);
        }

        if ($request->has('name_real')) {
            $validator->validateEncode($criteria['name_real']);
            $criteria['name_real'] = $parameterHandler->filterSpecialChar($criteria['name_real']);
            $criteria['name_real'] = $this->checkNameReal($criteria['name_real']);
            $oldBlacklist = $repo->findOneBy([
                'domain' => $domain,
                'nameReal' => $criteria['name_real']
            ]);

            if ($oldBlacklist) {
                throw new \RuntimeException('Name_real already exists in blacklist', 150650006);
            }

            $blacklist->setNameReal($criteria['name_real']);
        }

        if ($request->has('telephone')) {
            $validator->validateTelephone($criteria['telephone']);
            $oldBlacklist = $repo->findOneBy([
                'domain' => $domain,
                'telephone' => $criteria['telephone']
            ]);

            if ($oldBlacklist) {
                throw new \RuntimeException('Telephone already exists in blacklist', 150650007);
            }

            $blacklist->setTelephone($criteria['telephone']);
        }

        if ($request->has('email')) {
            $userValidator->validateEmail($criteria['email']);
            $oldBlacklist = $repo->findOneBy([
                'domain' => $domain,
                'email' => $criteria['email']
            ]);

            if ($oldBlacklist) {
                throw new \RuntimeException('Email already exists in blacklist', 150650008);
            }

            $blacklist->setEmail($criteria['email']);
        }

        if ($request->has('ip')) {
            $this->validateIp($criteria['ip']);
            $oldBlacklist = $repo->findOneBy([
                'domain' => $domain,
                'ip' => ip2long($criteria['ip'])
            ]);

            if ($oldBlacklist) {
                throw new \RuntimeException('IP already exists in blacklist', 150650009);
            }

            $blacklist->setIp($criteria['ip']);
        }

        $emShare->persist($blacklist);

        $emShare->beginTransaction();
        try {
            $emShare->flush();

            // 寫入黑名單操作紀錄
            $blacklistLog = new BlacklistOperationLog($blacklist->getId());
            $blacklistLog->setCreatedOperator($operator);

            if ($clientIp) {
                $this->validateIp($clientIp);
                $blacklistLog->setCreatedClientIp($clientIp);
            }

            if ($request->has('note')) {
                $validator->validateEncode($note);
                $note = $parameterHandler->filterSpecialChar($note);
                $blacklistLog->setNote($note);
            }

            $emShare->persist($blacklistLog);
            $emShare->flush();

            $emShare->commit();
        } catch (\Exception $e) {
            $emShare->rollback();

            //DBALException內部BUG
            if (!is_null($e->getPrevious())) {
                if ($e->getPrevious()->getCode() == 23000 && $e->getPrevious()->errorInfo[1] == 1062) {
                    $pdoMsg = $e->getMessage();

                    /**
                     * 隱藏阻擋同分秒加入黑名單的狀況，
                     * 改以不同error code區別 Database is busy錯誤訊息狀況
                     */
                    if (strpos($pdoMsg, 'uni_blacklist_domain_ip')) {
                        throw new \RuntimeException('Database is busy', 150650025);
                    }
                }
            }

            throw $e;
        }

        $log = $operationLogger->create('blacklist', ['id' => $blacklist->getId()]);
        $log->addMessage('domain', var_export($blacklist->getDomain(), true));
        $log->addMessage('whole_domain', var_export($blacklist->isWholeDomain(), true));

        foreach ($criteria as $key => $value) {
            if ($value) {
                $log->addMessage($key, $value);
            }
        }

        $log->addMessage('created_at', $blacklist->getCreatedAt()->format('Y-m-d H:i:s'));
        $log->addMessage('modified_at', $blacklist->getModifiedAt()->format('Y-m-d H:i:s'));
        $operationLogger->save($log);
        $emShare->flush();

        $output['result'] = 'ok';
        $output['ret'] = $blacklist->toArray();
        $output['ret']['created_operator'] = $blacklistLog->getCreatedOperator();
        $output['ret']['note'] = $blacklistLog->getNote();

        return new JsonResponse($output);
    }

    /**
     * 修改黑名單備註
     *
     * @Route("/blacklist/{blacklistId}",
     *        name = "api_blacklist_edit",
     *        requirements = {"blacklistId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @param integer $blacklistId 黑名單id
     * @return JsonResponse
     *
     * @author Ruby 2015.04.22
     */
    public function editAction(Request $request, $blacklistId)
    {
        $emShare = $this->getEntityManager('share');
        $validator = $this->get('durian.validator');
        $operationLogger = $this->get('durian.operation_logger');
        $parameterHandler = $this->get('durian.parameter_handler');
        $controlTerminal = (bool) $request->get('control_terminal', 0);
        $request = $request->request;

        if (is_null($request->get('note'))) {
            throw new \InvalidArgumentException('No note specified', 150650010);
        }

        $note = trim($request->get('note'));
        $validator->validateEncode($note);
        $note = $parameterHandler->filterSpecialChar($note);

        $blacklist = $emShare->find('BBDurianBundle:Blacklist', $blacklistId);

        if (!$blacklist) {
            throw new \RuntimeException('No such blacklist', 150650011);
        }

        //非控端操作時不可異動控端黑名單
        if (!$controlTerminal && $blacklist->isControlTerminal()) {
            throw new \RuntimeException('Editing blacklist is not allowed', 150650033);
        }

        $repo = $emShare->getRepository('BBDurianBundle:BlacklistOperationLog');
        $opLog = $repo->findOneBy(['blacklistId' => $blacklistId]);

        if ($opLog->getNote() !== $note) {
            $at = new \DateTime('now');
            $log = $operationLogger->create('blacklist_operation_log', ['id' => $opLog->getId()]);
            $log->addMessage('note', $opLog->getNote(), $note);
            $operationLogger->save($log);

            $log = $operationLogger->create('blacklist', ['id' => $blacklistId]);
            $log->addMessage('modified_at', $blacklist->getModifiedAt()->format('Y-m-d H:i:s'), $at->format('Y-m-d H:i:s'));
            $operationLogger->save($log);

            $opLog->setNote($note);
            $blacklist->setModifiedAt($at);

            $emShare->flush();
        }

        $output['result'] = 'ok';
        $output['ret'] = $blacklist->toArray();
        $output['ret']['note'] = $note;

        return new JsonResponse($output);
    }

    /**
     * 回傳單一黑名單
     *
     * @Route("/blacklist/{blacklistId}",
     *        name = "api_get_blacklist_by_id",
     *        requirements = {"blacklistId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param integer $blacklistId 黑名單id
     * @return JsonResponse
     */
    public function getBlacklistByIdAction($blacklistId)
    {
        $emShare = $this->getEntityManager('share');
        $blacklist = $emShare->find('BBDurianBundle:Blacklist', $blacklistId);

        if (!$blacklist) {
            throw new \RuntimeException('No such blacklist', 150650035);
        }

        $repo = $emShare->getRepository('BBDurianBundle:BlacklistOperationLog');
        $blacklistLog = $repo->findOneBy(['blacklistId' => $blacklistId]);

        $output['result'] = 'ok';
        $output['ret'] = $blacklist->toArray();
        $output['ret']['created_operator'] = $blacklistLog->getCreatedOperator();
        $output['ret']['note'] = $blacklistLog->getNote();

        return new JsonResponse($output);
    }

    /**
     * 回傳黑名單
     *
     * @Route("/blacklist",
     *        name = "api_get_blacklist",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     *
     * @author Ruby 2015.04.28
     */
    public function getBlacklistAction(Request $request)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $repo = $emShare->getRepository('BBDurianBundle:Blacklist');
        $parameterHandler = $this->get('durian.parameter_handler');
        $validator = $this->get('durian.validator');

        $query = $request->query;
        $domain = $query->get('domain', []);
        $removed = (bool) $query->get('removed', 0);
        $account = trim($query->get('account'));
        $identityCard = trim($query->get('identity_card'));
        $nameReal = trim($query->get('name_real'));
        $telephone = trim($query->get('telephone'));
        $email = trim($query->get('email'));
        $ip = trim($query->get('ip'));
        $note = trim($query->get('note'));
        $startAt = $query->get('start_at');
        $endAt = $query->get('end_at');
        $sort = $query->get('sort');
        $order = $query->get('order');
        $firstResult = $query->get('first_result');
        $maxResults = $query->get('max_results');

        $orderBy = $parameterHandler->orderBy($sort, $order);
        $validator->validatePagination($firstResult, $maxResults);

        $criteria = [
            'removed' => $removed
        ];

        if ($startAt) {
            if (!$validator->validateDate($startAt)) {
                throw new \InvalidArgumentException('Invalid start_at', 150650022);
            }

            $criteria['start_at'] = $startAt;
        }

        if ($endAt) {
            if (!$validator->validateDate($endAt)) {
                throw new \InvalidArgumentException('Invalid end_at', 150650023);
            }

            $criteria['end_at'] = $endAt;
        }

        if ($domain) {
            $criteria['domain'] = $domain;
        }

        if ($query->has('account')) {
            $criteria['account'] = $account;
        }

        if ($query->has('identity_card')) {
            $criteria['identity_card'] = $identityCard;
        }

        if ($query->has('name_real')) {
            $nameReal = $this->checkNameReal($nameReal);
            $criteria['name_real'] = $nameReal;
        }

        if ($query->has('telephone')) {
            $criteria['telephone'] = $telephone;
        }

        if ($query->has('email')) {
            $criteria['email'] = $email;
        }

        if ($query->has('ip')) {
            $criteria['ip'] = $ip;
        }

        if ($query->has('note')) {
            $criteria['note'] = $note;
        }

        if ($query->has('whole_domain')) {
            $criteria['whole_domain'] = (bool) $query->get('whole_domain');
        }

        if ($query->has('system_lock')) {
            $criteria['system_lock'] = (bool) $query->get('system_lock');
        }

        if ($query->has('control_terminal')) {
            $criteria['control_terminal'] = (bool) $query->get('control_terminal');
        }

        $limit = [
            'first_result' => $firstResult,
            'max_results' => $maxResults
        ];

        $blacklists = $repo->getBlacklistByDomain($criteria, $limit, $orderBy);
        $total = $repo->getCountOfBlacklist($criteria);

        $output['result'] = 'ok';
        $output['ret'] = [];

        foreach ($blacklists as $index => $blacklist) {
            $output['ret'][$index] = $blacklist[0]->toArray();
            $output['ret'][$index]['created_operator'] = $blacklist['created_operator'];
            $output['ret'][$index]['removed_operator'] = $blacklist['removed_operator'];
            $output['ret'][$index]['note'] = $blacklist['note'];
        }

        $output['pagination']['total'] = $total;
        $output['pagination']['first_result'] = $firstResult;
        $output['pagination']['max_results'] = $maxResults;

        return new JsonResponse($output);
    }

    /**
     * 刪除黑名單
     *
     * @Route("/blacklist/{blacklistId}",
     *        name = "api_blacklist_remove",
     *        requirements = {"blacklistId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"DELETE"})
     *
     * @param Request $request
     * @param integer $blacklistId 黑名單ID
     * @return JsonResponse
     *
     * @author Ruby 2015.04.22
     */
    public function removeAction(Request $request, $blacklistId)
    {
        $emShare = $this->getEntityManager('share');
        $operationLogger = $this->get('durian.operation_logger');
        $validator = $this->get('durian.validator');
        $parameterHandler = $this->get('durian.parameter_handler');

        $request = $request->request;
        $operator = trim($request->get('operator'));
        $clientIp = $request->get('client_ip');
        $note = $request->get('note');
        $controlTerminal = (bool) $request->get('control_terminal', 0);

        if (!$request->has('operator')) {
            throw new \InvalidArgumentException('No operator specified', 150650031);
        }

        $validator->validateEncode($operator);

        $blacklist = $emShare->find('BBDurianBundle:Blacklist', $blacklistId);

        if (!$blacklist) {
            throw new \RuntimeException('No such blacklist', 150650011);
        }

        //非控端操作時不可異動控端黑名單
        if (!$controlTerminal && $blacklist->isControlTerminal()) {
            throw new \RuntimeException('Removing blacklist is not allowed', 150650034);
        }

        $rmBlacklist = $emShare->find('BBDurianBundle:RemovedBlacklist', $blacklistId);

        if ($rmBlacklist) {
            throw new \RuntimeException('Blacklist already removed', 150650012);
        }

        $rmBlacklist = new RemovedBlacklist($blacklist);
        $emShare->persist($rmBlacklist);

        $emShare->remove($blacklist);

        $log = $operationLogger->create('blacklist', ['id' => $blacklistId]);
        $operationLogger->save($log);

        // 寫入黑名單操作紀錄
        $blacklistLog = new BlacklistOperationLog($blacklistId);
        $blacklistLog->setRemovedOperator($operator);

        if ($clientIp) {
            $this->validateIp($clientIp);
            $blacklistLog->setRemovedClientIp($clientIp);
        }

        if ($request->has('note')) {
            $validator->validateEncode($note);
            $note = $parameterHandler->filterSpecialChar(trim($note));
            $blacklistLog->setNote($note);
        }

        $emShare->persist($blacklistLog);
        $emShare->flush();

        $output['result'] = 'ok';
        $output['ret'] = $rmBlacklist->toArray();
        $output['ret']['removed_operator'] = $operator;
        $output['ret']['note'] = $note;

        return new JsonResponse($output);
    }

    /**
     * 回傳黑名單操作紀錄
     *
     * @Route("/blacklist/operation_log",
     *        name = "api_get_blacklist_operation_log",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getBlacklistOperationLogAction(Request $request)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $repo = $emShare->getRepository('BBDurianBundle:BlacklistOperationLog');
        $parameterHandler = $this->get('durian.parameter_handler');
        $validator = $this->get('durian.validator');

        $query = $request->query;
        $domain = $query->get('domain', []);
        $account = trim($query->get('account'));
        $identityCard = trim($query->get('identity_card'));
        $nameReal = trim($query->get('name_real'));
        $telephone = trim($query->get('telephone'));
        $email = trim($query->get('email'));
        $ip = trim($query->get('ip'));
        $note = trim($query->get('note'));
        $startAt = $query->get('start_at');
        $endAt = $query->get('end_at');
        $sort = $query->get('sort', ['id']);
        $order = $query->get('order');
        $firstResult = $query->get('first_result');
        $maxResults = $query->get('max_results');

        $orderBy = $parameterHandler->orderBy($sort, $order);
        $validator->validatePagination($firstResult, $maxResults);

        $criteria = [];

        if ($startAt) {
            if (!$validator->validateDate($startAt)) {
                throw new \InvalidArgumentException('Invalid start_at', 150650029);
            }

            $criteria['start_at'] = $startAt;
        }

        if ($endAt) {
            if (!$validator->validateDate($endAt)) {
                throw new \InvalidArgumentException('Invalid end_at', 150650030);
            }

            $criteria['end_at'] = $endAt;
        }

        if ($domain) {
            $criteria['domain'] = $domain;
        }

        if ($query->has('account')) {
            $criteria['account'] = $account;
        }

        if ($query->has('identity_card')) {
            $criteria['identity_card'] = $identityCard;
        }

        if ($query->has('name_real')) {
            $nameReal = $this->checkNameReal($nameReal);
            $criteria['name_real'] = $nameReal;
        }

        if ($query->has('telephone')) {
            $criteria['telephone'] = $telephone;
        }

        if ($query->has('email')) {
            $criteria['email'] = $email;
        }

        if ($query->has('ip')) {
            $criteria['ip'] = $ip;
        }

        if ($query->has('note')) {
            $criteria['note'] = $note;
        }

        if ($query->has('whole_domain')) {
            $criteria['whole_domain'] = (bool) $query->get('whole_domain');
        }

        if ($query->has('system_lock')) {
            $criteria['system_lock'] = (bool) $query->get('system_lock');
        }

        if ($query->has('control_terminal')) {
            $criteria['control_terminal'] = (bool) $query->get('control_terminal');
        }

        $limit = [
            'first_result' => $firstResult,
            'max_results' => $maxResults
        ];

        $operationLogs = $repo->getOperationLogBy($criteria, $limit, $orderBy);
        $total = $repo->getCountOfOperationLog($criteria);

        $output['result'] = 'ok';
        $output['ret'] = [];

        foreach ($operationLogs as $operationLog) {
            $log = [];
            $log['id'] = $operationLog['id'];
            $log['blacklist_id'] = $operationLog['blacklist_id'];
            $log['created_operator'] = $operationLog['created_operator'];
            $log['created_client_ip'] = $operationLog['created_client_ip'];
            $log['removed_operator'] = $operationLog['removed_operator'];
            $log['removed_client_ip'] = $operationLog['removed_client_ip'];
            $log['at'] = (new \DateTime($operationLog['at']))->format(\DateTime::ISO8601);
            $log['note'] = $operationLog['note'];

            if ($operationLog['created_client_ip']) {
                $log['created_client_ip'] = long2ip($operationLog['created_client_ip']);
            }

            if ($operationLog['removed_client_ip']) {
                $log['removed_client_ip'] = long2ip($operationLog['removed_client_ip']);
            }

            $table = 'bl';

            if (!$operationLog['bl_id']) {
                $table = 'rbl';
            }

            $log['domain'] = $operationLog["{$table}_domain"];
            $log['whole_domain'] = $operationLog["{$table}_wholeDomain"];
            $log['account'] = $operationLog["{$table}_account"];
            $log['identity_card'] = $operationLog["{$table}_identityCard"];
            $log['name_real'] = $operationLog["{$table}_nameReal"];
            $log['telephone'] = $operationLog["{$table}_telephone"];
            $log['email'] = $operationLog["{$table}_email"];
            $log['ip'] = null;

            if ($operationLog["{$table}_ip"]) {
                $log['ip'] = long2ip($operationLog["{$table}_ip"]);
            }

            $log['system_lock'] = $operationLog["{$table}_systemLock"];
            $log['control_terminal'] = $operationLog["{$table}_controlTerminal"];

            $output['ret'][] = $log;
        }

        $output['pagination']['total'] = $total;
        $output['pagination']['first_result'] = $firstResult;
        $output['pagination']['max_results'] = $maxResults;

        return new JsonResponse($output);
    }

    /**
     * 回傳Doctrine EntityManager
     *
     * @param string $name EntityManager 名稱
     * @return \Doctrine\ORM\EntityManager
     */
    private function getEntityManager($name = 'default')
    {
        return $this->getDoctrine()->getManager($name);
    }

    /**
     * 檢查新增欄位是否超過一個
     *
     * @param array $criteria 參數
     */
    private function checkNumOfCreate($criteria)
    {
        $count = 0;

        if ($criteria['account'] || $criteria['account'] == '0') {
            $count++;
        }

        if ($criteria['identity_card'] || $criteria['identity_card'] == '0') {
            $count++;
        }

        if ($criteria['name_real'] || $criteria['name_real'] == '0') {
            $count++;
        }

        if ($criteria['telephone'] || $criteria['telephone'] == '0') {
            $count++;
        }

        if ($criteria['email'] || $criteria['email'] == '0') {
            $count++;
        }

        if ($criteria['ip'] || $criteria['ip'] == '0') {
            $count++;
        }

        if ($count == 0) {
            throw new \InvalidArgumentException('No blacklist fields specified', 150650013);
        }

        if ($count > 1) {
            throw new \InvalidArgumentException('Cannot specify more than one blacklist fields', 150650014);
        }
    }

    /**
     * 驗證銀行帳號格式
     *
     * @param string $account 銀行帳號
     */
    private function validateAccount($account)
    {
        if (!preg_match("/^([A-Za-z0-9-\s])*$/i", $account)) {
            throw new \InvalidArgumentException('Invalid account', 150650003);
        }
    }

    /**
     * 驗證IP格式
     *
     * @param string $ip IP
     */
    private function validateIp($ip)
    {
        $validator = $this->get('durian.validator');

        if (!$validator->validateIp($ip)) {
            throw new \InvalidArgumentException('Invalid IP', 150650024);
        }
    }

    /**
     * 檢查姓名後贅詞，有則去除
     *
     * @param string $nameReal 真實姓名
     * @return string
     */
    private function checkNameReal($nameReal)
    {
        $regex = '/-(\*|[1-9][0-9]?)$/';

        if (preg_match($regex, $nameReal, $matches)) {
            $subStrOriNameleng = strlen($nameReal) - strlen($matches[0]);
            $nameReal = substr($nameReal, 0, $subStrOriNameleng);
        }

        return $nameReal;
    }
}

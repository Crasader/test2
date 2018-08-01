<?php

namespace BB\DurianBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use BB\DurianBundle\Entity\MaintainStatus;
use BB\DurianBundle\Entity\MaintainWhitelist;
use Symfony\Component\HttpFoundation\Request;

class MaintainController extends Controller
{
    /**
     * 取不合法的測試帳號
     *
     * @Route("/maintain/get_illegal_tester",
     *        name = "api_get_illegal_tester",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getIllegalTestUserAction(Request $request)
    {
        $validator = $this->get('durian.validator');
        $query = $request->query;

        $firstResult = $query->get('first_result', 0);
        $maxResults = $query->get('max_results', 20);
        $parentId = $query->get('parent_id');

        $validator->validatePagination($firstResult, $maxResults);

        if (!$parentId) {
            throw new \InvalidArgumentException('No parent_id specified', 150100012);
        }

        $result = $this->getEntityManager()
                ->getRepository('BB\DurianBundle\Entity\User')
                ->getAllChildTestUserIdArray($parentId);

        if (sizeof($result) <= 0) {
            $output['result'] = 'ok';
            $output['message'] = 'There is no test user';

            return new JsonResponse($output);
        }

        $idSet = array();
        foreach ($result as $row) {
            $idSet[] = $row['id'];
        }

        $totalIllegal = $this->getEntityManager()
                ->getRepository('BB\DurianBundle\Entity\UserDetail')
                ->countTestUserIdWithErrorNameReal($idSet);

        if ($totalIllegal <= 0) {
            $output['result'] = 'ok';
            $output['message'] = 'No illegal tester found';

            return new JsonResponse($output);
        }

        $resultUserDetail = $this->getEntityManager()
                ->getRepository('BB\DurianBundle\Entity\UserDetail')
                ->getTestUserIdWithErrorNameReal($idSet, $firstResult, $maxResults);

        $output['result'] = 'ok';
        $output['ret'] = $resultUserDetail;
        $output['pagination']['first_result'] = $firstResult;
        $output['pagination']['max_results'] = $maxResults;
        $output['pagination']['total'] = $totalIllegal;

        return new JsonResponse($output);
    }

    /**
     * 設定遊戲維護資訊
     *
     * @Route("/maintain/game/{code}",
     *        name = "api_set_maintain_by_game",
     *        requirements = {"code" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @param integer $code 代碼
     * @return JsonResponse
     */
    public function setMaintainByGameAction(Request $request, $code)
    {
        $request = $request->request;
        $em = $this->getEntityManager();
        $validator = $this->get('durian.validator');

        $nowTime = new \Datetime('now');
        $beginAt = new \Datetime($request->get('begin_at'));
        $beginAt->setTimezone(new \DateTimeZone('Asia/Taipei'));
        $endAt = new \Datetime($request->get('end_at'));
        $endAt->setTimezone(new \DateTimeZone('Asia/Taipei'));
        $msg = trim($request->get('msg', ''));
        $operator = trim($request->get('operator', ''));
        $noticeInterval = $request->get('notice_interval', 0);
        $sendDomainMessage = $request->getBoolean('send_domain_message', 0);

        if (!$request->has('begin_at')) {
            throw new \InvalidArgumentException('No begin_at specified', 150100006);
        }

        if (!$request->has('end_at')) {
            throw new \InvalidArgumentException('No end_at specified', 150100007);
        }

        if ($beginAt->getTimestamp() - $endAt->getTimestamp() > 0) {
            throw new \InvalidArgumentException('Illgegal game maintain time', 150100008);
        }

        if ($noticeInterval) {
            if (!$validator->isInt($noticeInterval, true)) {
                throw new \InvalidArgumentException('Invalid notice_interval', 150100016);
            }

            $noticeAt = clone $beginAt;
            $noticeAt->sub(new \DateInterval('PT' . $noticeInterval . 'M'));

            if ($noticeAt->getTimestamp() - $nowTime->getTimestamp() < 0) {
                throw new \InvalidArgumentException('Illegal notice time', 150100017);
            }
        }

        // 驗證參數編碼是否為utf8
        $checkParameter = [$msg, $operator];
        $validator->validateEncode($checkParameter);

        $maintain = $em->find('BBDurianBundle:Maintain', $code);

        if (!$maintain) {
            throw new \RuntimeException('No game code exists', 150100005);
        }

        $maintain->setBeginAt($beginAt);
        $maintain->setEndAt($endAt);
        $maintain->setModifiedAt($nowTime);
        $maintain->setMsg($msg);
        $maintain->setOperator($operator);

        $maintainStatusRepo = $em->getRepository('BBDurianBundle:MaintainStatus');

        $targetGroup = ['1', '3', 'mobile', 'domain'];
        foreach ($targetGroup as $target) {
            //若未帶開關則預設不送廳主訊息
            if ($target == 'domain' && !$sendDomainMessage) {
                continue;
            }

            $criteria = array(
                'maintain' => $code,
                'target'   => $target
            );

            $maintainStatus = $maintainStatusRepo->findOneBy($criteria);
            if (!$maintainStatus) {
                $maintainStatus = new MaintainStatus($maintain, $target);
                $em->persist($maintainStatus);
            }

            if ($nowTime >= $beginAt && $nowTime <= $endAt) {
                /**
                 * 維護中不需要發送維護訊息給研三
                 * 發送開始維護訊息後status 會變成 SEND_MAINTAIN_END
                 * 在維護時間內更新維護時間不改變研三的status，維持 SEND_MAINTAIN_END
                 * SendMaintainMessageCommand 判斷研三status 為SEND_MAINTAIN_END
                 * 且維護時間還沒結束則不會再送維護訊息給研三
                 */
                if ($target == '3' && $maintainStatus->getStatus() == MaintainStatus::SEND_MAINTAIN_END) {
                    continue;
                }

                $maintainStatus->setStatus(MaintainStatus::SEND_MAINTAIN_START);
            } elseif ($nowTime > $endAt) {
                $maintainStatus->setStatus(MaintainStatus::SEND_MAINTAIN_END);
            } else {
                $maintainStatus->setStatus(MaintainStatus::SEND_MAINTAIN);
            }
            $maintainStatus->setUpdateAt($nowTime);
        }

        $criteria = [
            'maintain' => $code,
            'target' => 'domain',
            'status' => MaintainStatus::SEND_MAINTAIN_NOTICE
        ];

        $noticeStatus = $maintainStatusRepo->findOneBy($criteria);

        if ($noticeInterval) {
            if (!$noticeStatus) {
                $maintainStatus = new MaintainStatus($maintain, $target);
                $maintainStatus->setStatus(MaintainStatus::SEND_MAINTAIN_NOTICE);
                $maintainStatus->setUpdateAt($noticeAt);
                $em->persist($maintainStatus);
            } else {
                $noticeStatus->setUpdateAt($noticeAt);
            }
        }

        // 若未帶提醒參數但資料庫仍有提醒訊息需清空
        if (!$noticeInterval && $noticeStatus) {
            $em->remove($noticeStatus);
        }

        //為避免重覆操作仍送訊息，必須做判斷刪除狀態
        if (!$sendDomainMessage) {
            $criteria = [
                'maintain' => $code,
                'target' => 'domain'
            ];

            $domainStatus = $maintainStatusRepo->findBy($criteria);

            foreach ($domainStatus as $status) {
                $em->remove($status);
            }
        }

        $em->flush();
        $em->refresh($maintain);

        // 設定session 的維護資訊
        $sessionBroker = $this->get('durian.session_broker');
        $sessionBroker->setMaintainInfo($code, $beginAt, $endAt, $msg);

        $output['result'] = 'ok';
        $output['ret'] = $maintain->toArray();

        if ($noticeInterval) {
            $output['ret']['notice_interval'] = $noticeInterval;
        }

        $output['ret']['send_domain_message'] = false;

        if ($sendDomainMessage) {
            $output['ret']['send_domain_message'] = true;
        }

        return new JsonResponse($output);
    }

    /**
     * 取得遊戲維護狀態
     *
     * @Route("/maintain/game/{code}",
     *        name = "api_get_maintain_by_game",
     *        requirements = {"code" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @param integer $code 代碼
     * @return JsonResponse
     */
    public function getMaintainByGameAction(Request $request, $code)
    {
        $query = $request->query;
        $em = $this->getEntityManager();

        $isMaintaining = false;
        $inWhitelist = null;

        $now = new \DateTime();

        $ip = $query->get('client_ip');

        $maintain = $em->find('BBDurianBundle:Maintain', $code);

        if (!$maintain) {
            throw new \RuntimeException('No game code exists', 150100005);
        }

        $beginAt = $maintain->getBeginAt();
        $endAt = $maintain->getEndAt();

        if ($now >= $beginAt && $now <= $endAt) {
            $isMaintaining = true;

            $inWhitelist = false;
            $whitelist = $em->getRepository('BBDurianBundle:MaintainWhitelist')->findOneBy(['ip' => $ip]);
        }

        if (isset($whitelist)) {
           $inWhitelist = true;
        }

        $ret = $maintain->toArray();
        $ret['is_maintaining'] = $isMaintaining;
        $ret['in_whitelist'] = $inWhitelist;

        $statusRepo = $em->getRepository('BBDurianBundle:MaintainStatus');
        $maintainStatus = $statusRepo->findOneBy(['maintain' => $maintain, 'status' => MaintainStatus::SEND_MAINTAIN_NOTICE]);

        if ($maintainStatus) {
            $noticeAt = $maintainStatus->getUpdateAt();
            $noticeInterval = ($beginAt->getTimestamp() - $noticeAt->getTimestamp()) / 60;
            $ret['notice_interval'] = $noticeInterval;
        }

        $ret['send_domain_message'] = false;
        $domainStatus = $statusRepo->findOneBy(['maintain' => $code, 'target' => 'domain']);

        if ($domainStatus) {
            $ret['send_domain_message'] = true;
        }

        $output['result'] = 'ok';
        $output['ret'] = $ret;

        return new JsonResponse($output);
    }

    /**
     * 取得目前維護中遊戲
     *
     * @Route("/maintain/game_list",
     *        name = "api_get_maintain_game_list",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @return JsonResponse
     */
    public function getMaintainGameList()
    {
        $em = $this->getEntityManager();

        $now = new \DateTime();
        $maintains = $em->getRepository('BBDurianBundle:Maintain')->getIsMaintain($now);

        $output['result'] = 'ok';
        $output['ret'] = [];

        foreach ($maintains as $key => $maintain) {
            $output['ret'][] = $maintain['code'];
        }

        return new JsonResponse($output);
    }

    /**
     * 新增白名單
     *
     * @Route("/maintain/whitelist",
     *        name = "api_create_maintain_whitelist",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"POST"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function createWhitelistAction(Request $request)
    {
        $request = $request->request;
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $validator = $this->get('durian.validator');
        $operationLogger = $this->get('durian.operation_logger');

        $ip = $request->get('ip');

        if (!is_array($ip)) {
            $ip = [$ip];
        }

        $ipSet = array_unique($ip);
        sort($ipSet);

        foreach ($ipSet as $ip) {
            if (!$validator->validateIp($ip)) {
                throw new \InvalidArgumentException('Invalid IP', 150100013);
            }
        }

        $checkIp = $em->getRepository('BBDurianBundle:MaintainWhitelist')->findBy(['ip' => $ipSet]);

        if ($checkIp) {
            throw new \RuntimeException('Ip already exists', 150100014);
        }

        $whitelistArr = [];

        foreach ($ipSet as $ip) {
            $whitelist = new MaintainWhitelist($ip);
            $em->persist($whitelist);

            $whitelistArr[] = $whitelist->toArray();

            // 因發生寫同一筆而導致 major_key 欄位無法紀錄 ips 的情形，故拆開紀錄
            $log = $operationLogger->create('maintain_whitelist', ['ip' => $ip]);
            $log->addMessage('ip', $ip);
            $operationLogger->save($log);
        }

        $em->beginTransaction();
        $emShare->beginTransaction();
        try {
            $em->flush();
            $emShare->flush();
            $em->commit();
            $emShare->commit();
        } catch (\Exception $e) {
            $em->rollback();
            $emShare->rollback();
            throw $e;
        }

        // 將須送各組的資料推進 redis queue
        $this->sendNewWhitelist();

        // 新增session白名單ip
        $sessionBroker = $this->get('durian.session_broker');

        foreach ($ipSet as $ip) {
            $sessionBroker->addWhitelistIp($ip);
        }

        $result = [
            'result' => 'ok',
            'ret' => $whitelistArr
        ];

        return new JsonResponse($result);
    }

    /**
     * 刪除白名單
     *
     * @Route("/maintain/whitelist",
     *        name = "api_delete_maintain_whitelist",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"DELETE"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function deleteWhitelistAction(Request $request)
    {
        $request = $request->request;
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $validator = $this->get('durian.validator');
        $operationLogger = $this->get('durian.operation_logger');

        $ip = $request->get('ip');

        if (!is_array($ip)) {
            $ip = [$ip];
        }

        $ipSet = array_unique($ip);
        sort($ipSet);

        foreach ($ipSet as $ip) {
            if (!$validator->validateIp($ip)) {
                throw new \InvalidArgumentException('Invalid IP', 150100013);
            }
        }

        $whitelists = $em->getRepository('BBDurianBundle:MaintainWhitelist')->findBy(['ip' => $ipSet]);

        if (!$whitelists) {
            throw new \RuntimeException('No MaintainWhitelist found', 150100015);
        }

        foreach ($whitelists as $whitelist) {
            $em->remove($whitelist);

            // 因發生寫同一筆而導致 major_key 欄位無法紀錄 ips 的情形，故拆開紀錄
            $log = $operationLogger->create('maintain_whitelist', ['ip' => $whitelist->getIp()]);
            $log->addMessage('ip', $whitelist->getIp());
            $operationLogger->save($log);
        }

        $em->beginTransaction();
        $emShare->beginTransaction();
        try {
            $em->flush();
            $emShare->flush();
            $em->commit();
            $emShare->commit();
        } catch (\Exception $e) {
            $em->rollback();
            $emShare->rollback();
            throw $e;
        }

        // 將須送各組的資料推進 redis queue
        $this->sendNewWhitelist();

        // 刪除session白名單ip
        $sessionBroker = $this->get('durian.session_broker');

        foreach ($ipSet as $ip) {
            $sessionBroker->removeWhitelistIp($ip);
        }

        $result = ['result' => 'ok'];

        return new JsonResponse($result);
    }

    /**
     * 白名單列表
     *
     * @Route("/maintain/whitelist",
     *        name = "api_get_maintain_whitelist",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getWhitelistAction(Request $request)
    {
        $query = $request->query;
        $validator = $this->get('durian.validator');
        $em = $this->getEntityManager();

        $firstResult = $query->get('first_result');
        $maxResults = $query->get('max_results');

        $validator->validatePagination($firstResult, $maxResults);

        $repo = $em->getRepository('BBDurianBundle:MaintainWhitelist');
        $total = $repo->countNumOf();
        $whitelists = $repo->findby([], null, $maxResults, $firstResult);

        $ret = [];

        foreach($whitelists as $whitelist) {
            $ret[] = $whitelist->toArray();
        }

        $pagination = [
            'first_result' => $firstResult,
            'max_results' => $maxResults,
            'total' => $total
        ];

        $result = [
            'result' => 'ok',
            'ret' => $ret,
            'pagination' => $pagination
        ];

        return new JsonResponse($result);
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
     * 寄送新的ip白名單給研一與Mobile
     */
    private function sendNewWhitelist()
    {
        $rd1WhitelistWorker = $this->get('durian.rd1_whitelist_worker');
        $mobileWhitelistWorker = $this->get('durian.mobile_whitelist_worker');

        $repo = $this->getEntityManager()->getRepository('BBDurianBundle:MaintainWhitelist');
        $maintainWhitelist = $repo->findBy([], ['id' => 'ASC']);

        $whitelist = [];

        foreach ($maintainWhitelist as $list) {
            $whitelist[] = $list->getIp();
        }

        $params = [
            'content' => ['whitelist' => $whitelist],
        ];

        $rd1WhitelistWorker->push($params);

        $mobileParams = [
            'content' => ['ipList' => $whitelist],
        ];

        $mobileWhitelistWorker->push($mobileParams);
    }
}

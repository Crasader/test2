<?php

namespace BB\DurianBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use BB\DurianBundle\Entity\Cash;
use BB\DurianBundle\Entity\UserStat;
use BB\DurianBundle\Entity\UserRemitDiscount;

class ManualController extends Controller
{
    /**
     * 人工存入API
     *
     * @Route("/user/{userId}/manual",
     *        name = "api_manual",
     *        requirements = {"userId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @param integer $userId
     * @return JsonResponse
     */
    public function manualAction(Request $request, $userId)
    {
        $operationLogger = $this->get('durian.operation_logger');
        $validator = $this->get('durian.validator');
        $op = $this->get('durian.op');
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $redis = $this->get('snc_redis.default_client');

        $post = $request->request;
        $amount = trim($post->get('amount'));
        $memo = trim($post->get('memo', ''));
        $opcode = $post->get('opcode');

        $offer = $post->get('offer');
        $offerMemo = trim($post->get('offer_memo', ''));

        $remitOffer = $post->get('remit_offer');
        $remitOfferMemo = trim($post->get('remit_offer_memo', ''));

        $refId  = $post->get('ref_id', 0);
        $operator = trim($post->get('operator'));

        $validator->validateEncode([$memo, $offerMemo, $remitOfferMemo, $operator]);

        if (!$validator->validateOpcode($opcode)) {
            throw new \InvalidArgumentException('Invalid opcode', 490001);
        }

        $user = $em->find('BBDurianBundle:User', $userId);
        if (!$user) {
            throw new \RuntimeException('No such user', 490004);
        }

        $cash = $user->getCash();
        if (!$cash) {
            throw new \RuntimeException('No cash found', 490003);
        }

        if (!$validator->isFloat($amount)) {
            throw new \InvalidArgumentException('No amount specified', 490006);
        }
        $validator->validateDecimal($amount, Cash::NUMBER_OF_DECIMAL_PLACES);

        $maxBalance = Cash::MAX_BALANCE;
        if ($amount > $maxBalance || $amount < $maxBalance*-1) {
            throw new \RangeException('Oversize amount given which exceeds the MAX', 490007);
        }

        if ($post->has('ref_id') && $validator->validateRefId($refId)) {
            throw new \InvalidArgumentException('Invalid ref_id', 490002);
        }

        if ($offer) {
            $validator->validateDecimal($offer, Cash::NUMBER_OF_DECIMAL_PLACES);
        }

        if ($remitOffer) {
            $validator->validateDecimal($remitOffer, Cash::NUMBER_OF_DECIMAL_PLACES);
        }

        // 這邊是為了強制DB連master
        $em->beginTransaction();
        $emShare->beginTransaction();
        try {
            // 人工存入統計，只記錄1010
            if ($opcode == 1010) {
                $now = new \DateTime();
                $depositAt = clone $now;

                $now->setTimezone(new \DateTimeZone('Etc/GMT+4'))
                    ->setTime(0, 0, 0)
                    ->setTimeZone(new \DateTimeZone('Asia/Taipei'));

                $newAmount = $amount;
                if ($cash->getCurrency() != 156) {
                    $exRepo = $this->getEntityManager('share')->getRepository('BBDurianBundle:Exchange');
                    $exchange = $exRepo->findByCurrencyAt($cash->getCurrency(), $now);

                    if (!$exchange) {
                        throw new \RuntimeException('No such exchange', 490005);
                    }

                    $newAmount = $exchange->reconvertByBasic($amount);
                }

                // 紀錄使用者出入款統計資料
                $userStat = $em->find('BBDurianBundle:UserStat', $user->getId());
                $userStatLog = $operationLogger->create('user_stat', ['user_id' => $userId]);

                if (!$userStat) {
                    $userStat = new UserStat($user);
                    $em->persist($userStat);
                }

                $manualCount = $userStat->getManualCount();
                $manualTotal = $userStat->getManualTotal();

                $userStat->setManualCount($manualCount + 1);
                $userStatLog->addMessage('manual_count', $manualCount, $manualCount + 1);

                $userStat->setManualTotal($manualTotal + $newAmount);
                $userStatLog->addMessage('manual_total', $manualTotal, $manualTotal + $newAmount);

                if ($userStat->getManualMax() < $newAmount) {
                    $manualMax = $userStat->getManualMax();

                    $userStat->setManualMax($newAmount);
                    $userStatLog->addMessage('manual_max', $manualMax, $newAmount);
                }

                if (!$userStat->getFirstDepositAt()) {
                    $userStat->setFirstDepositAt($depositAt->format('YmdHis'));
                    $userStatLog->addMessage('first_deposit_at', $depositAt->format(\DateTime::ISO8601));

                    $userStat->setFirstDepositAmount($newAmount);
                    $userStatLog->addMessage('first_deposit_amount', $newAmount);
                }

                $oldModifiedAt = $userStat->getModifiedAt()->format(\DateTime::ISO8601);
                $userStat->setModifiedAt();
                $newModifiedAt = $userStat->getModifiedAt()->format(\DateTime::ISO8601);
                $userStatLog->addMessage('modified_at', $oldModifiedAt, $newModifiedAt);

                $operationLogger->save($userStatLog);
            }

            // 匯款優惠
            if ($opcode == 1010 && $remitOffer > 0) {
                // 加到每日優惠上限
                $cron = \Cron\CronExpression::factory('0 12 * * *');
                $periodAt = $cron->getPreviousRunDate(new \DateTime(), 0, true);

                $criteria = [
                    'userId' => $userId,
                    'periodAt' => $periodAt
                ];

                $repository = $em->getRepository('BBDurianBundle:UserRemitDiscount');
                $dailyDiscount = $repository->findOneBy($criteria);

                if (!$dailyDiscount) {
                    $dailyDiscount = new UserRemitDiscount($user, $criteria['periodAt']);
                    $em->persist($dailyDiscount);
                }
                $dailyDiscount->addDiscount($remitOffer);
            }

            $em->flush();
            $emShare->flush();

            $options = [
                'operator' => $operator,
                'opcode' => $opcode,
                'refId' => $refId,
                'memo' => $memo,
            ];

            $entry = $op->cashDirectOpByRedis($cash, $amount, $options);
            $output['ret']['cash'] = $entry['cash'];
            $output['ret']['entry'] = $entry['entry'];

            // 存款優惠
            if ($opcode == 1010 && $offer > 0) {
                $options['opcode'] = 1011;
                $options['memo'] = $offerMemo;

                $offerEntry = $op->cashDirectOpByRedis($cash, $offer, $options);
                $output['ret']['offer_entry'] = $offerEntry['entry'];
                $output['ret']['cash'] = $offerEntry['cash'];
            }

            // 匯款優惠
            if ($opcode == 1010 && $remitOffer > 0) {
                $options['opcode'] = 1012;
                $options['memo'] = $remitOfferMemo;

                $remitOfferEntry = $op->cashDirectOpByRedis($cash, $remitOffer, $options);
                $output['ret']['remit_offer_entry'] = $remitOfferEntry['entry'];
                $output['ret']['cash'] = $remitOfferEntry['cash'];
            }

            $em->commit();
            $emShare->commit();

            if ($opcode == 1010) {
                // 人工存入超過50萬人民幣, 需寄發異常入款提醒
                if ($newAmount >= 500000) {
                    $notify = [
                        'domain' => $user->getDomain(),
                        'confirm_at' => $now->format(\DateTime::ISO8601),
                        'user_name' => $user->getUsername(),
                        'opcode' => $opcode,
                        'operator' => $operator,
                        'amount' => $newAmount,
                    ];

                    $redis->rpush('abnormal_deposit_notify_queue', json_encode($notify));
                }

                // 需統計入款金額
                $statDeposit = [
                    'domain' => $user->getDomain(),
                    'confirm_at' => $now->format(\DateTime::ISO8601),
                    'amount' => $newAmount,
                ];
                $redis->rpush('stat_domain_deposit_queue', json_encode($statDeposit));
            }
        } catch (\Exception $e) {
            $em->rollback();
            $emShare->rollback();

            // 重複的紀錄
            if (!is_null($e->getPrevious()) && $e->getPrevious()->errorInfo[1] == 1062) {
                throw new \RuntimeException('Database is busy', 490008);
            }

            throw $e;
        }

        $output['result'] = 'ok';

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

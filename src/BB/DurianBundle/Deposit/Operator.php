<?php

namespace BB\DurianBundle\Deposit;

use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\ContainerAware;
use BB\DurianBundle\Entity\CardDepositEntry;
use BB\DurianBundle\Entity\MerchantCard;
use BB\DurianBundle\Entity\MerchantCardRecord;
use BB\DurianBundle\Entity\User;
use BB\DurianBundle\Entity\CashDepositEntry;

class Operator extends ContainerAware
{
    /**
     * 租卡確認入款
     *
     * @param CardDepositEntry $entry 入款明細
     * @param array $option 入款相關參數
     * @return array
     */
    public function cardDepositConfirm(CardDepositEntry $entry, array $option)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $statRepo = $em->getRepository('BBDurianBundle:MerchantCardStat');
        $cardOp = $this->container->get('durian.card_operator');
        $opLogger = $this->container->get('durian.operation_logger');

        if ($entry->isConfirm()) {
            throw new \RuntimeException('CardDepositEntry has been confirmed', 150720017);
        }

        $mcId = $entry->getMerchantCardId();
        $merchantCard = $em->find('BBDurianBundle:MerchantCard', $mcId);
        if (!$merchantCard) {
            throw new \RuntimeException('No MerchantCard found', 150720018);
        }

        $user = $em->find('BBDurianBundle:User', $entry->getUserId());
        if (!$user) {
            throw new \RuntimeException('No such user', 150720002);
        }

        $card = $user->getCard();
        if (!$card) {
            throw new \RuntimeException('No Card found', 150720007);
        }

        $atString = $entry->getAt()->format('Y-m-d H:i:s');
        $cron = \Cron\CronExpression::factory('0 0 * * *'); //每天午夜12點
        $runDate = $cron->getPreviousRunDate($atString, 0, true);

        $at = $runDate->format('YmdHis');
        $domain = $merchantCard->getDomain();
        $amount = $entry->getAmount();
        $entryId = $entry->getId();

        $em->beginTransaction();
        $emShare->beginTransaction();
        try {
            // 人工存入且有代入操作者Id, 需檢查金額
            if (array_key_exists('manual', $option) && !is_null($option['operator_id'])) {
                $confirmQuota = $option['deposit_confirm_quota'];

                // 檢查金額是否超過上限
                if ($amount > $confirmQuota) {
                    throw new \RangeException('Amount exceed DepositConfirmQuota of operator', 150720021);
                }
            }

            // 設定人工存入
            if (array_key_exists('manual', $option)) {
                $entry->setManual($option['manual']);
            }

            // 先改狀態並寫入，防止同分秒造成的問題
            $entry->confirm();
            $em->flush();

            // 商家統計
            $criteria = [
                'at' => $at,
                'domain' => $domain,
                'merchantCard' => $mcId
            ];
            $mcStat = $statRepo->findOneBy($criteria);

            if (!$mcStat) {
                $statId = $statRepo->insertMerchantCardStat($merchantCard, $at, 1, $amount);

                $oldCount = 0;
                $oldTotal = 0;
            } else {
                $statId = $mcStat->getId();
                $oldCount = $mcStat->getCount();
                $oldTotal = $mcStat->getTotal();

                $statRepo->updateMerchantCardStat($statId, 1, $amount);
                $em->detach($mcStat);
            }

            $merchantCardStat = $statRepo->find($statId);
            $newCount = $merchantCardStat->getCount();
            $newTotal = $merchantCardStat->getTotal();

            $majorKey = ['id' => $statId];
            $log = $opLogger->create('merchant_card_stat', $majorKey);
            $log->addMessage('merchant_card_id', $mcId);
            $log->addMessage('domain', $domain);
            $log->addMessage('at', $at);
            $log->addMessage('count', $oldCount, $newCount);
            $log->addMessage('total', $oldTotal, $newTotal);
            $opLogger->save($log);

            $this->checkBankLimit($merchantCard);

            $amountConv = $entry->getAmountConv();
            $feeConv = $entry->getFeeConv();

            $options = [
                'operator' => '',
                'opcode' => 9901,
                'ref_id' => $entryId,
                'force' => 1
            ];

            // 人工需紀錄操作者
            if (array_key_exists('manual', $option)) {
                $options['operator'] = $option['username'];
            }

            $opResult = $cardOp->op($card, $amountConv, $options);

            $entry->setEntryId($opResult['entry']['id']);
            $amountEntry = $opResult['entry'];

            if ($feeConv < 0) {
                $options = [
                    'operator' => '',
                    'opcode' => 9902,
                    'ref_id' => $entryId,
                    'force' => 1
                ];

                $feeResult = $cardOp->op($card, $feeConv, $options);
                $entry->setFeeEntryId($feeResult['entry']['id']);
            }

            $em->flush();
            $emShare->flush();

            $result = $entry->toArray();
            $result['amount_entry'] = $amountEntry;

            $em->commit();
            $emShare->commit();
        } catch (\Exception $exception) {
            $em->rollback();
            $emShare->rollback();

            throw $exception;
        }

        return $result;
    }

    /**
     * 處理租卡商號達到限制停用
     *
     * @param MerchantCard $merchantCard
     */
    public function checkBankLimit(MerchantCard $merchantCard)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $mcId = $merchantCard->getId();
        $domain = $merchantCard->getDomain();

        $criteria = [
            'merchantCard' => $mcId,
            'name' => 'bankLimit'
        ];
        $mcExtra = $em->getRepository('BBDurianBundle:MerchantCardExtra')->findOneBy($criteria);

        // 如沒有限額設定則跳出
        if (!$mcExtra) {
            return;
        }

        // 取當下的時間避免跨天停用的問題
        $cron = \Cron\CronExpression::factory('0 0 * * *'); //每天午夜12點
        $runDate = $cron->getPreviousRunDate('now', 0, true);
        $at = $runDate->format('YmdHis');

        $statCriteria = [
            'merchantCard' => $mcId,
            'domain' => $domain,
            'at' => $at
        ];
        $mcStat = $em->getRepository('BBDurianBundle:MerchantCardStat')->findOneBy($statCriteria);

        $total = 0;
        if ($mcStat) {
            $total = $mcStat->getTotal();
        }

        $domainConfig = $emShare->find('BBDurianBundle:DomainConfig', $domain);
        $domainAlias = $domainConfig->getName();
        $loginCode = $domainConfig->getLoginCode();
        $bankLimit = $mcExtra->getValue();
        $suspend = $merchantCard->isSuspended();
        $enable = $merchantCard->isEnabled();

        // 商號若為停用則不需暫停
        if ($bankLimit >= 0 && $total >= $bankLimit && !$suspend && $enable) {

            $msg = "廳主: $domainAlias@$loginCode, ";
            $msg .= "租卡商家編號: $mcId, ";
            $msg .= "已達到停用金額: $bankLimit, 已累積: $total, 停用該商號";

            $mcRecord = new MerchantCardRecord($domain, $msg);
            $em->persist($mcRecord);

            // 通知客服
            $now = new \DateTime('now');
            $italking = $this->container->get('durian.italking_operator');
            $queueMsg = "北京时间：" . $now->format('Y-m-d H:i:s') . " " . $msg;
            $italking->pushMessageToQueue('payment_alarm', $queueMsg);

            $opLogger = $this->container->get('durian.operation_logger');
            $log = $opLogger->create('merchant_card', ['id' => $mcId]);
            $log->addMessage('suspend', var_export($suspend, true), 'true');
            $opLogger->save($log);

            $merchantCard->suspend();
        }
    }

    /**
     * 取得線上付款設定
     *
     * @param User $user
     * @param integer $payway
     * @param integer $levelId
     * @return PaymentCharge
     */
    public function getPaymentCharge(User $user, $payway, $levelId)
    {
        $em = $this->getEntityManager();
        $currencyOp = $this->container->get('durian.currency');

        $currency = 156;
        $cash = $user->getCash();

        if ($cash) {
            $currency = $cash->getCurrency();
        }

        // 從level_currency取得線上付款設定
        $levelCurrency = $em->getRepository('BBDurianBundle:LevelCurrency')
            ->findOneBy(['levelId' => $levelId, 'currency' => $currency]);

        if (!$levelCurrency) {
            throw new \RuntimeException('No LevelCurrency found', 370055);
        }
        $paymentCharge = $levelCurrency->getPaymentCharge();

        // 如果回傳是null，改從payment_charge取得預設值
        if ($paymentCharge == null) {
            $criteria = [
                'payway' => $payway,
                'domain' => $user->getDomain(),
                'preset' => 1,
                'code' => $currencyOp->getMappedCode($currency)
            ];

            $paymentCharge = $em->getRepository('BBDurianBundle:PaymentCharge')
                ->findOneBy($criteria);

            if (!$paymentCharge) {
                throw new \RuntimeException('No PaymentCharge found', 370046);
            }
        }

        return $paymentCharge;
    }

    /**
     * 回傳 EntityManager 物件
     *
     * @param string $name EntityManager 名稱
     * @return EntityManager
     */
    private function getEntityManager($name = 'default')
    {
        return $this->container->get("doctrine.orm.{$name}_entity_manager");
    }
}

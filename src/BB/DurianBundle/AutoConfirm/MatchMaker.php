<?php

namespace BB\DurianBundle\AutoConfirm;

use BB\DurianBundle\Entity\AutoConfirmEntry;
use BB\DurianBundle\Entity\RemitEntry;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Symfony\Component\DependencyInjection\ContainerAware;

class MatchMaker extends ContainerAware
{
    /**
     * @var integer
     */
    private $remitEntryId;

    /**
     * @var integer
     */
    private $remitAccountId;

    /**
     * @var Registry
     */
    private $doctrine;

    /**
     * @param Registry $doctrine
     */
    public function setDoctrine($doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * 自動認款比對訂單
     *
     * 基本條件：
     *     1.資料金額與訂單金額相同
     *     2.資料時間與訂單提交時間在60分鐘內
     *     3.資料收款卡號與訂單收款卡號相同
     *
     * 複合條件一：
     *     1.附言與訂單號相同
     *
     * 複合條件二：
     *     1.資料匯款方戶名與訂單銀行卡戶名相同
     *
     * 複合條件三：
     *     1.資料匯款方帳號與訂單銀行卡帳號相同
     *
     * 複合條件四：
     *     1.支付寶特例
     *       a.取出交易姓名檢查是否為"支付宝（中国）网络技术有限公司客户备付金"
     *       b.(a)條件符合後，去掉附言內"支付宝转账"後用訂單姓名匹配
     *
     * @param AutoConfirmEntry $autoConfirmEntry
     * @param integer $remitAccountId
     * @return mixed
     */
    public function autoConfirmMatchRemitEntry(AutoConfirmEntry $autoConfirmEntry, $remitAccountId)
    {
        $this->remitAccountId = $remitAccountId;

        // 匹配複合條件:匯款資料姓名與訂單姓名相同
        if ($this->matchByName($autoConfirmEntry)) {
            return $this->remitEntryId;
        }

        // 匹配複合條件:附言與卡號相同
        if ($this->matchByTradeMemo($autoConfirmEntry)) {
            return $this->remitEntryId;
        }

        // 匹配複合條件:匯款資料帳號與訂單銀行卡帳號相同
        if ($this->matchByAccount($autoConfirmEntry)) {
            return $this->remitEntryId;
        }

        // 匹配複合條件:支付寶特例
        if ($this->matchAliPay($autoConfirmEntry)) {
            return $this->remitEntryId;
        }

        return false;
    }

    /**
     * 符合基本條件及複合條件一:附言與訂單號相同
     *
     * @param AutoConfirmEntry $autoConfirmEntry
     * @return boolean
     */
    private function matchByTradeMemo(AutoConfirmEntry $autoConfirmEntry)
    {
        $criteria = ['orderNumber' => $autoConfirmEntry->getTradeMemo()];

        return $this->matchRemitEntry($autoConfirmEntry, $criteria);
    }

    /**
     * 符合基本條件及複合條件二:交易姓名與訂單姓名相同
     *
     * @param AutoConfirmEntry $autoConfirmEntry
     * @return boolean
     */
    private function matchByName(AutoConfirmEntry $autoConfirmEntry)
    {
        // 如果為空字串，條件失效
        if ($autoConfirmEntry->getName() === '') {
            return false;
        }

        $criteria = ['nameReal' => $autoConfirmEntry->getName()];

        return $this->matchRemitEntry($autoConfirmEntry, $criteria);
    }

    /**
     * 符合基本條件及複合條件三:交易帳號與訂單帳號相同
     *
     * @param AutoConfirmEntry $autoConfirmEntry
     * @return boolean
     */
    private function matchByAccount(AutoConfirmEntry $autoConfirmEntry)
    {
        // 如果為空字串，條件失效
        if ($autoConfirmEntry->getAccount() === '') {
            return false;
        }

        $criteria = ['payerCard' => $autoConfirmEntry->getAccount()];

        return $this->matchRemitEntry($autoConfirmEntry, $criteria);
    }

    /**
     *  符合基本條件及複合條件四:支付寶特例
     *      a.取出交易姓名檢查是否為"支付宝（中国）网络技术有限公司客户备付金"
     *      b.(a)條件符合後，去掉附言內"支付宝转账"後用訂單姓名匹配
     *
     * @param AutoConfirmEntry $autoConfirmEntry
     * @return boolean
     */
    private function matchAliPay(AutoConfirmEntry $autoConfirmEntry)
    {
        if (mb_strpos($autoConfirmEntry->getName(), '支付宝（中国）网络技术有限公司客户备付金') === 0) {
            // 取出姓名
            $tradeName = trim(preg_replace('/支付宝转账$/', '', $autoConfirmEntry->getTradeMemo(), 1));

            $criteria = ['nameReal' => $tradeName];

            return $this->matchRemitEntry($autoConfirmEntry, $criteria);
        }

        return false;
    }

    /**
     * 回傳是否有符合條件的訂單
     *
     * @param AutoConfirmEntry $autoConfirmEntry
     * @param array $criteria
     * @return boolean
     */
    private function matchRemitEntry(AutoConfirmEntry $autoConfirmEntry, $criteria)
    {
        $em = $this->getEntityManager();
        $remitRepo = $em->getRepository('BBDurianBundle:RemitEntry');

        $entryCriteria = [
            'autoConfirm' => true,
            'remitAccountId' => $this->remitAccountId,
            'amount' => $autoConfirmEntry->getAmount(),
            'status' => RemitEntry::UNCONFIRM,
        ];
        $entryCriteria = array_merge($entryCriteria, $criteria);

        // 交易時間60分鐘前
        $intervalTime = new \DateInterval('PT1H');
        $createdStart = clone $autoConfirmEntry->getTradeAt();
        $createdStart = $createdStart->sub($intervalTime)->format('YmdHis');

        $createdEnd = $autoConfirmEntry->getTradeAt()->format('YmdHis');

        // 撈出訂單建立在交易時間~交易前60分鐘的訂單
        $rangeCriteria = [
            'createdStart' => $createdStart,
            'createdEnd' => $createdEnd,
        ];

        $remitEntry = $remitRepo->getEntriesBy($entryCriteria, $rangeCriteria, [], 0, 1);
        if (count($remitEntry)) {
            $remitEntry = $remitEntry[0];
            $this->remitEntryId = $remitEntry->getId();

            return true;
        }

        return false;
    }

    /**
     * 回傳Doctrine EntityManager
     *
     * @param string $name Entity manager name
     * @return \Doctrine\ORM\EntityManager
     */
    private function getEntityManager($name = 'default')
    {
        return $this->doctrine->getManager($name);
    }
}

<?php

namespace BB\DurianBundle\Cash;

use Doctrine\ORM\EntityManager;
use BB\DurianBundle\Entity\Cash;
use BB\DurianBundle\Entity\CashEntry;
use BB\DurianBundle\Entity\PaymentDepositWithdrawEntry;
use BB\DurianBundle\Entity\CashFake;
use BB\DurianBundle\Entity\CashFakeEntry;
use BB\DurianBundle\Entity\CashFakeTransferEntry;
use BB\DurianBundle\Entity\CashFakeEntryOperator;
use BB\DurianBundle\Entity\User;

class Helper
{
    /**
     * @var \Doctrine\Bundle\DoctrineBundle\Registry
     */
    private $doctrine;

    /**
     * @var \BB\DurianBundle\Cash\Entry\IdGenerator
     */
    private $cashEntryIdGenerator;

    /**
     * @var \BB\DurianBundle\CashFake\Entry\IdGenerator
     */
    private $cashFakeEntryIdGenerator;

    /**
     * @var \BB\DurianBundle\Service\OpService
     */
    private $opService;

    /**
     * @param \Doctrine\Bundle\DoctrineBundle\Registry $doctrine
     */
    public function setDoctrine($doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * @param \BB\DurianBundle\Cash\Entry\IdGenerator $idGenerator
     * @return Helper
     */
    public function setCashEntryIdGenerator($idGenerator)
    {
        $this->cashEntryIdGenerator = $idGenerator;

        return $this;
    }

    /**
     * @param \BB\DurianBundle\CashFake\Entry\IdGenerator $idGenerator
     * @return Helper
     */
    public function setCashFakeEntryIdGenerator($idGenerator)
    {
        $this->cashFakeEntryIdGenerator = $idGenerator;

        return $this;
    }

    /**
     * @param \BB\DurianBundle\Service\OpService $service
     */
    public function setOpService($opService)
    {
        $this->opService = $opService;
    }

    /**
     * 回傳Doctrine EntityManager
     *
     * @param string $name Entity manager name
     * @return EntityManager
     */
    protected function getEntityManager($name = 'default')
    {
        return $this->doctrine->getManager($name);
    }

    /**
     * 將陣列中明細的額度依提出(負數)及存入(正數)分別加總，最後再加起來成回合計
     *
     * @param Array $entries 明細物件的集合
     * @param Array $output  輸出結果
     * @return Array
     */
    public function getSubTotal($entries, $output)
    {
        $withdraw = 0;
        $deposite = 0;

        foreach ($entries as $entry) {
            $amount = $entry->getAmount();
            if ($amount < 0) {
                $withdraw += $amount;
            } elseif ($amount > 0) {
                $deposite += $amount;
            }
        }

        $output['sub_total']['withdraw'] = $withdraw;
        $output['sub_total']['deposite'] = $deposite;
        $output['sub_total']['total'] = $withdraw + $deposite;

        return $output;
    }

    /**
     * 新增一個現金交易
     *
     * @param Cash    $cash
     * @param integer $opcode 交易種類
     * @param float   $amount 金額。負數代表扣款
     * @param string  $memo   備註
     * @param integer $refId  參考編號
     * @return array CashEntry[]
     */
    public function addCashEntry(
        Cash $cash,
        $opcode,
        $amount,
        $memo = '',
        $refId = 0
    ) {
        $em = $this->getEntityManager();
        $emEntry = $this->getEntityManager('entry');

        $entry = new CashEntry($cash, $opcode, $amount, $memo);
        $entry->setId($this->cashEntryIdGenerator->generate());
        $entry->setRefId($refId);
        $emEntry->persist($entry);

        $pEntry = null;
        if ($opcode < 9890) {
            $pEntry = new PaymentDepositWithdrawEntry($entry, $cash->getUser()->getDomain());
            $em->persist($pEntry);
        }

        $cash->setBalance($entry->getBalance());

        // 判斷餘額設定negative欄位
        $negative = $entry->getBalance() < 0;
        $cash->setNegative($negative);

        $entries = [
            'entry'                          => $entry,
            'payment_deposit_withdraw_entry' => $pEntry
        ];

        return $entries;
    }

    /**
     * 新增一個快開額度交易
     *
     * @param CashFake $cashFake
     * @param integer  $opcode 交易種類
     * @param float    $amount 金額。負數代表扣款
     * @param string   $memo   備註
     * @param integer  $refId  參考編號
     * @param string   $operator 操作者
     * @return array CashFakeEntry[]
     */
    public function addCashFakeEntry(
        CashFake $cashFake,
        $opcode,
        $amount,
        $memo = '',
        $refId = 0,
        $operator = ''
    ) {
        $em = $this->getEntityManager();
        $emHis = $this->getEntityManager('his');

        $entry = new CashFakeEntry($cashFake, $opcode, $amount, $memo);
        $entry->setId($this->cashFakeEntryIdGenerator->generate());
        $entry->setRefId($refId);
        $entry->setCashFakeVersion($cashFake->getVersion() + 1);
        $em->persist($entry);
        $emHis->persist($entry);

        $tEntry = null;

        if ($opcode < 9890) {
            $domain = $cashFake->getUser()->getDomain();
            $tEntry = new CashFakeTransferEntry($entry, $domain);
            $em->persist($tEntry);
        }

        $entryOperator = null;
        if ($opcode == 1003) {
            $parent = $cashFake->getUser()->getParent();
            $whom = $parent->getUsername();
            $repo = $em->getRepository('BBDurianBundle:User');
            $level = $repo->getLevel($parent);
            $transferOut = ($amount < 0) ? 1 : 0;

            $entryOperator = new CashFakeEntryOperator($entry->getId(), $operator);
            $entryOperator->setWhom($whom);
            $entryOperator->setLevel($level);
            $entryOperator->setTransferOut($transferOut);
            $em->persist($entryOperator);
        }

        $cashFake->setBalance($entry->getBalance());
        $cashFake->setVersion($entry->getCashFakeVersion());

        $entries = [
            'entry' => $entry,
            'transfer_entry' => $tEntry,
            'entry_operator' => $entryOperator
        ];

        return $entries;
    }
}

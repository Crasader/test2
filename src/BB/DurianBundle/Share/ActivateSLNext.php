<?php

namespace BB\DurianBundle\Share;

use Symfony\Component\DependencyInjection\ContainerAware;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\ResultSetMapping;
use BB\DurianBundle\Entity\ShareUpdateRecord;
use BB\DurianBundle\Entity\ShareUpdateCron;

class ActivateSLNext extends ContainerAware
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * 取消記錄sql log
     *
     * @var bool
     */
    private $disableLog;

    public function __construct()
    {
        $this->disableLog = false;
    }

    /**
     * 回傳EntityManager
     *
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        if (!$this->em) {
            $doctrine = $this->container->get('doctrine');
            $this->em = $doctrine->getManager('default');
        }

        return $this->em;
    }

    /**
     * 判斷是否在跑佔成更新
     *
     * @param \DateTime $date
     * @param integer $groupNum
     * @return boolean
     */
    public function isUpdating(\DateTime $date, $groupNum = null)
    {
        /**
         * 判斷有沒有在佔成更新的方法是,
         * 比對share update cron規定的時間,
         * 從share update cron規定時間開始的一分鐘內都算是在佔成更新,
         * 一分鐘之後則看share update cron有沒有還沒跑完的紀錄,
         * 若有的話則算是在佔成更新
         */

        // 統一用Asia/Taipei時區來做判斷
        $date->setTimezone(new \DateTimeZone('Asia/Taipei'));

        $em = $this->getEntityManager();
        $repo = $em->getRepository('BBDurianBundle:ShareUpdateCron');

        // 在share update cron規定時間的一分鐘內都算是在佔成更新
        if (is_null($groupNum)) {
            $updateCrons = $repo->findAll();

            // share update crons當中, 若存在正在佔成更新的updateCrons, 就代表在佔成更新
            $criteria = array(
                'state' => ShareUpdateCron::RUNNING
            );
            $updateCron = $repo->findOneBy($criteria);
        } else {
            $updateCrons = $repo->findBy(array('groupNum' => $groupNum));

            // share update crons當中, 若存在正在佔成更新的updateCrons, 就代表在佔成更新
            if (count($updateCrons) != 0) {
                $criteria = array(
                    'state'    => ShareUpdateCron::RUNNING,
                    'groupNum' => $groupNum
                );
                $updateCron = $repo->findOneBy($criteria);
            }
        }

        if (count($updateCrons) == 0) {
            throw new \RuntimeException('No share update cron exists', 150080044);
        }

        if (!empty($updateCron)) {
            return true;
        }

        foreach ($updateCrons as $updateCron) {

            if ($updateCron->getPeriod() != '') { //如果有存更新排程
                $cron = \Cron\CronExpression::factory($updateCron->getPeriod());
                $at = clone $date;
                $at = $cron->getPreviousRunDate($at, 0, true);
                $diff = $date->getTimestamp() - $at->getTimestamp();

                //如果帶入的$date與佔成更新時間時間差在0~60間則為佔成更新中
                if ($diff <= 60 && $diff >= 0) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * 判斷是否已經跑過佔成更新
     *
     * @param \DateTime $curDate
     * @param array $groupNum
     * @return boolean
     */
    public function hasBeenUpdated($curDate, $groupNum = null)
    {
        $em = $this->getEntityManager();
        $repo = $em->getRepository('BBDurianBundle:ShareUpdateCron');

        if (is_null($groupNum)) {
            $updateCrons = $repo->findAll();
        } else {
            $updateCrons = $repo->findBy(array('groupNum' => $groupNum));
        }

        if (count($updateCrons) == 0) {
            throw new \RuntimeException('No share update cron exists', 150080044);
        }

        foreach ($updateCrons as $updateCron) {
            if ($this->notRunYet($updateCron, $curDate)) {
                return false;
            }
        }

        return true;
    }

    /**
     * 判斷是否在跑佔成更新
     *
     * @param \DateTime $date
     * @param ShareUpdateCron $updateCrons
     * @return boolean
     */
    public function checkUpdate(\DateTime $date, $updateCrons)
    {
        /**
         * 判斷有沒有在佔成更新的方法是,
         * 比對share update cron規定的時間,
         * 從share update cron規定時間開始的一分鐘內都算是在佔成更新,
         * 一分鐘之後則看share update cron有沒有還沒跑完的紀錄,
         * 若有的話則算是在佔成更新
         */

        // 統一用Asia/Taipei時區來做判斷
        $date->setTimezone(new \DateTimeZone('Asia/Taipei'));

        if (count($updateCrons) == 0) {
            throw new \RuntimeException('No share update cron exists', 150080044);
        }

        // share update crons當中, 若存在正在佔成更新的updateCrons, 就代表在佔成更新
        foreach ($updateCrons as $updateCron) {
            if ($updateCron->getState() == ShareUpdateCron::RUNNING) {
                return true;
            }
        }

        foreach ($updateCrons as $updateCron) {
            // 檢查是否在佔成更新中
            if ($updateCron->getPeriod() != '') { //如果有存更新排程
                $cron = \Cron\CronExpression::factory($updateCron->getPeriod());
                $at = clone $date;
                $at = $cron->getPreviousRunDate($at, 0, true);
                $diff = $date->getTimestamp() - $at->getTimestamp();

                //如果帶入的$date與佔成更新時間時間差在0~60間則為佔成更新中
                if ($diff <= 60 && $diff >= 0) {
                    return true;
                }
            }

            // 檢查是否已佔成更新完畢
            if ($this->notRunYet($updateCron, $date)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 傳回還需要執行的佔成更新排程
     *
     * @param \DateTime $currentDate
     * @return Array
     */
    public function getUpdateCronsNeedRun(\DateTime $currentDate)
    {
        $em = $this->getEntityManager();
        $repo = $em->getRepository('BBDurianBundle:ShareUpdateCron');

        $updateCronsNeedRun = array();
        foreach ($repo->findAll() as $updateCron) {
            if ($this->notRunYet($updateCron, $currentDate)) {
                $updateCronsNeedRun[] = $updateCron;
            }
        }

        return $updateCronsNeedRun;
    }

    /**
     * 執行更新佔成
     *
     * @param ShareUpdateCron $updateCron
     * @param \DateTime $date
     */
    public function update($updateCron, \DateTime $date)
    {
        $em = $this->getEntityManager();

        if (empty($updateCron)) {
            return;
        }

        $this->beginUpdate($updateCron, $date);
        $groupNum = $updateCron->getGroupNum();

        $queryObj = $em->createQuery(
            "SELECT sn.id FROM BB\DurianBundle\Entity\ShareLimitNext sn, ".
            "BB\DurianBundle\Entity\ShareLimit s ".
            "WHERE sn.groupNum = $groupNum ".
            "AND sn.id = s.id ".
            "AND (sn.upper != s.upper OR ".
            "sn.lower != s.lower OR ".
            "sn.parentUpper != s.parentUpper OR ".
            "sn.parentLower != s.parentLower OR ".
            "sn.min1 != s.min1 OR ".
            "sn.max1 != s.max1 OR ".
            "sn.max2 != s.max2)"
        );

        $slIdArray = $queryObj->getResult();
        $this->log($queryObj->getSQL());

        $this->updateSL($slIdArray);

        $this->finishUpdate($updateCron, $date);
    }

    /**
     * 更新佔成前的初始化
     *
     * @param ShareUpdateCron $updateCron
     * @param \DateTime $date
     * @return int
     */
    private function beginUpdate($updateCron, \DateTime $date)
    {
        $em = $this->getEntityManager();
        $date->setTimezone(new \DateTimeZone('Asia/Taipei'));

        // 變更狀態為佔成更新中
        $updateCron->reRun();

        // 新增一筆佔成更新記錄
        $record = new ShareUpdateRecord($updateCron, $date);
        $em->persist($record);
        $em->flush();
    }

    /**
     * 更新佔成結束後的清理動作
     *
     * @param ShareUpdateCron $updateCron
     * @param \DateTime $date
     */
    private function finishUpdate($updateCron, \DateTime $date)
    {
        $em = $this->getEntityManager();

        // 變更狀態為佔成更新完成, 並記錄佔成更新時間
        $updateCron->setUpdateAt($date);
        $updateCron->finish();

        // 新增一筆佔成更新記錄
        $record = new ShareUpdateRecord($updateCron, $date);
        $record->finish();
        $em->persist($record);
    }

    /**
     * 從預改佔成寫值到現行佔成
     *
     * @param array $slIdArray
     */
    private function updateSL($slIdArray)
    {
        if (empty($slIdArray)) {
            return;
        }

        $em = $this->getEntityManager();
        $rsm = new ResultSetMapping();

        $rsm->addScalarResult('user_id', 'userId');
        $rsm->addScalarResult('upper', 'upper');
        $rsm->addScalarResult('lower', 'lower');
        $rsm->addScalarResult('parent_upper', 'parentUpper');
        $rsm->addScalarResult('parent_lower', 'parentLower');
        $rsm->addScalarResult('min1', 'min1');
        $rsm->addScalarResult('max1', 'max1');
        $rsm->addScalarResult('max2', 'max2');

        foreach ($slIdArray as $item) {
            $id = $item['id'];
            $query = "select upper, lower, parent_upper, parent_lower, " .
                "min1, max1, max2 from share_limit_next where id = '$id'";
            $queryObj = $em->createNativeQuery($query, $rsm);

            $result = $queryObj->getResult();
            $this->log($queryObj->getSQL());

            $slNext['upper'] = $result[0]['upper'];
            $slNext['lower'] = $result[0]['lower'];
            $slNext['parentUpper'] = $result[0]['parentUpper'];
            $slNext['parentLower'] = $result[0]['parentLower'];
            $slNext['min1'] = $result[0]['min1'];
            $slNext['max1'] = $result[0]['max1'];
            $slNext['max2'] = $result[0]['max2'];

            $query = "UPDATE share_limit ".
                     "SET upper = '{$slNext['upper']}', ".
                     "lower = '{$slNext['lower']}', ".
                     "parent_upper = '{$slNext['parentUpper']}', ".
                     "parent_lower = '{$slNext['parentLower']}', ".
                     "min1 = '{$slNext['min1']}', ".
                     "max1 = '{$slNext['max1']}', ".
                     "max2 = '{$slNext['max2']}' ".
                     "WHERE id = {$id} ";

            $result = $em->getConnection()->executeUpdate($query);
            $this->log($query);
        }
    }

    /**
     * 判斷某佔成更新排程是否已經跑過
     *
     * @param ShareUpdateCron $updateCron
     * @param \DateTime $curDate
     * @return boolean true: 還沒跑過, false: 已經跑過
     */
    private function notRunYet($updateCron, \DateTime $curDate)
    {
        /**
         * ShareUpdateCron裡的每個項目稱為一個updateCron,
         * 例如groupNum = 1, period = '0 0 * * 1'的updateCron表示說
         * group 1 在每個禮拜一午夜12點要更新佔成.
         * 判斷需不需要跑佔成更新的方式為,
         * 以目前的時間減去上次佔成更新的時間, 如果差距大於一個定值的話,
         * 就需要跑佔成更新, 以上面的例子來說, 若差距大於一天,
         * 則需要跑佔成更新. 由於佔成更新時間有時候不會是整點, 例如網路速度慢導致
         * 12:00:02才跑, 因此佔成更新時間要先調整到整點再做比較
         */

        $prevUpdateCronDate = $this->getPrevUpdateCronTS($updateCron);

        // 開始判斷, 若隔太久代表還沒佔成更新
        if ($updateCron->getPeriod() != '') {
            $cron = \Cron\CronExpression::factory($updateCron->getPeriod());

            //抓上次更新時間 (假設 $prevUpdateCronDate等於更新排程時間則回傳更新排程時間 )
            $thisRunDate = $cron->getPreviousRunDate($prevUpdateCronDate, 0, true);

            $at = clone $thisRunDate;

            //下次更新時間
            $nextRunDate = $cron->getNextRunDate($at, 1, true);

            //如帶入時間大於等於下次更新時間代表很久沒有佔成更新
            if ($curDate >= $nextRunDate) {
                return true;
            }
        }

        return false;
    }

    /**
     * 設定並記錄log
     *
     * @param String $message
     */
    public function log($message)
    {
        if ($this->disableLog) {
            return;
        }

        if (null === $this->logger) {
            $this->logger = $this->container->get('durian.logger_manager')
                ->setUpLogger('activate_sl_next.log');
        }

        $this->logger->addInfo($message);
    }

    /**
     * 取消記錄 sql log
     */
    public function disableLog()
    {
        $this->disableLog = true;
    }

    /**
     * 傳回上次佔成更新記錄的時間戳記
     *
     * @param ShareUpdateCron $updateCron
     * @return \DateTime
     */
    private function getPrevUpdateCronTS($updateCron)
    {
        /**
         * 即使date_default_timezone_set 設成 Asia/Taipei,
         * ShareUpdateCron::getUpdateAt 依然傳回php.ini的時區,
         * 而不是Asia/Taipei, 所以才使用下列方式來傳回台灣時區
         * 的DateTime object
         */
        $format = $updateCron->getUpdateAt()
                         ->format('Y-m-d H:i:s');
        $prevDate = new \DateTime($format, new \DateTimeZone('Asia/Taipei'));

        return $prevDate;
    }
}

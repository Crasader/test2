<?php

namespace BB\DurianBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use BB\DurianBundle\Entity\UserState;
use BB\DurianBundle\Entity\UserStat;

/**
 * 統計速達入款會員資料
 */
class BuildSudaUserStatCommand extends ContainerAwareCommand
{
    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function configure()
    {
        $this
            ->setName('durian:build-suda-user-stat')
            ->setDescription('統計速達入款會員資料')
            ->setHelp(<<<EOT
統計會員速達入款次數及金額
app/console durian:build-suda-user-stat
EOT
             );
    }

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $startTime = microtime(true);

        // init
        $this->output = $output;
        $em = $this->getContainer()->get("doctrine.orm.default_entity_manager");
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $qb = $em->createQueryBuilder();
        $qb->select('dse.userId as user_id');
        $qb->addSelect('SUM(dse.amount) as amount');
        $qb->addSelect('COUNT(dse.id) as counts');
        $qb->addSelect('MAX(dse.amount) as max_amount');
        $qb->from('BBDurianBundle:DepositSudaEntry', 'dse');
        $qb->where('dse.confirm = 1');
        $qb->groupBy('dse.userId');

        $results = $qb->getQuery()->getArrayResult();

        foreach ($results as $result) {
            $userId = $result['user_id'];
            $user = $em->find('BBDurianBundle:User', $userId);

            // 找不到會員 搜尋已刪除會員
            if (!$user) {
                $user = $emShare->find('BBDurianBundle:RemovedUser', $userId);
            }

            // 會員都找不到 則跳過這筆統計資料
            if (!$user) {
                $this->output->writeln("User $userId Not Exist");

                continue;
            }

            // 紀錄會員出入款相關參數
            $userState = $em->find('BBDurianBundle:UserState', $userId);

            if (!$userState) {
                $userState = new UserState($user);
                $em->persist($userState);
            }
            $userState->setDeposited(true);

            // 紀錄會員出入款統計
            $userStat = $em->find('BBDurianBundle:UserStat', $userId);

            if (!$userStat) {
                $userStat = new UserStat($user);
                $em->persist($userStat);
            }
            $userStat->setSudaCount($result['counts']);
            $userStat->setSudaTotal($result['amount']);
            $userStat->setSudaMax($result['max_amount']);
        }
        $em->flush();

        $this->printPerformance($startTime);
    }

    /**
     * 印出效能相關訊息
     *
     * @param integer $startTime
     */
    private function printPerformance($startTime)
    {
        $endTime = microtime(true);
        $excutionTime = round($endTime - $startTime, 1);
        $timeString = $excutionTime . ' sec.';

        if ($excutionTime > 60) {
            $timeString = round($excutionTime / 60, 0) . ' mins.';
        }
        $this->output->writeln("\nExecute time: $timeString");

        $memory = memory_get_peak_usage() / 1024 / 1024;
        $usage = number_format($memory, 2);
        $this->output->writeln("Memory MAX use: $usage M");
    }
}

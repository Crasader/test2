<?php

namespace BB\DurianBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * 補removed_user_email資料表資料
 *
 * @author sin-hao 2015.07.14
 */
class BuildRemovedUserEmailCommand extends ContainerAwareCommand
{
    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function configure()
    {
        $this
            ->setName('durian:build-removed-user-email')
            ->setDescription('補removed_user_email資料表資料')
            ->addOption('begin-id', null, InputOption::VALUE_OPTIONAL, '從指定的user_id開始')
            ->setHelp(<<<EOT
補removed_user_email資料表資料
$ ./console durian:build-user-email

補removed_user_email資料表資料(指定開始user_id)
$ ./console durian:build-removed-user-email --begin-id=1000
EOT
            );
    }

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $conn = $this->getContainer()->get('doctrine.dbal.share_connection');
        $maxId = 0;
        $lastMaxId = 0;

        if ($input->getOption('begin-id')) {
            $maxId = $input->getOption('begin-id');
        }

        while(true) {
            $sql = 'select rud.user_id, rud.email, ru.created_at, ru.modified_at from `removed_user_detail` '.
                'as rud inner join `removed_user` as ru on rud.user_id = ru.user_id where rud.user_id > ? '.
                'order by rud.user_id limit 1000';
            $param = [$maxId];

            $userDetails = $conn->fetchAll($sql, $param);
            $conn->beginTransaction();
            try {
                foreach ($userDetails as $userDetail) {
                    $removedUserEmail = $em->find('BBDurianBundle:RemovedUserEmail', $userDetail['user_id']);
                    if ($removedUserEmail) {
                        continue;
                    }

                    $insertSql = 'INSERT INTO removed_user_email (user_id, email, confirm, confirm_at) '.
                        "VALUES ({$userDetail['user_id']}, '{$userDetail['email']}', 0, NULL)";

                    $conn->executeUpdate($insertSql);

                    if ($maxId < $userDetail['user_id']) {
                        $maxId = $userDetail['user_id'];
                    }
                }

                $conn->commit();

                // 再跑測試的時候，就不sleep了，避免測試碼執行時間過長
                if ($this->getContainer()->getParameter('kernel.environment') != 'test') {
                    usleep(500000);
                }
            } catch (\Exception $e) {
                $conn->rollBack();
                throw $e;
            }

            $output->writeln("/ MaxId: $maxId");
            // 最大ID沒變, 可以離開了
            if ($lastMaxId == $maxId) {
                break;
            }

            $lastMaxId = $maxId;
        }
    }
}
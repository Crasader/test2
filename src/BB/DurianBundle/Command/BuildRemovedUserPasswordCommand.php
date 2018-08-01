<?php

namespace BB\DurianBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * 補removed_user_password資料表資料
 *
 * @author Cullen 2015.11.19
 */
class BuildRemovedUserPasswordCommand extends ContainerAwareCommand
{
    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function configure()
    {
        $this
            ->setName('durian:build-removed-user-password')
            ->setDescription('補removed_user_password資料表資料')
            ->addOption('begin-id', null, InputOption::VALUE_OPTIONAL, '從指定的user_id開始')
            ->setHelp(<<<EOT
補removed_user_password資料表資料
$ ./console durian:build-removed-user-password

補removed_user_password資料表資料(指定開始user_id)
$ ./console durian:build-removed-user-password --begin-id=1000
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
            $sql = 'SELECT user_id, password, password_expire_at, password_reset, modified_at, err_num FROM removed_user '.
                'WHERE user_id > ? ORDER BY user_id limit 1000';
            $param = [$maxId];

            $removedUsers = $conn->fetchAll($sql, $param);

            $conn->beginTransaction();
            try {
                foreach ($removedUsers as $removedUser) {
                    $removedUserPassword = $em->find('BBDurianBundle:RemovedUserPassword', $removedUser['user_id']);

                    if ($removedUserPassword) {
                        continue;
                    }

                    $hash = password_hash($removedUser['password'], PASSWORD_BCRYPT);

                    $insertSql = "INSERT INTO removed_user_password (user_id, hash, expire_at, ".
                        "modified_at, reset, once_password, used, once_expire_at, err_num) ".
                        "VALUES ('{$removedUser['user_id']}', '$hash', '{$removedUser['password_expire_at']}', ".
                        "'{$removedUser['modified_at']}', '{$removedUser['password_reset']}', ".
                        "NULL, 0, NULL, '{$removedUser['err_num']}')";

                    $conn->executeUpdate($insertSql);

                    if ($maxId < $removedUser['user_id']) {
                        $maxId = $removedUser['user_id'];
                    }
                }

                $conn->commit();

                // 在跑測試的時候，就不sleep了，避免測試碼執行時間過長
                if ($this->getContainer()->getParameter('kernel.environment') != 'test') {
                    usleep(500000);
                }
            } catch (\Exception $e) {
                $conn->rollBack();
                throw $e;
            }

            // 最大ID沒變, 可以離開了
            if ($lastMaxId == $maxId) {
                break;
            }

            $output->writeln("/ MaxId: $maxId");
            $lastMaxId = $maxId;
        }
    }
}

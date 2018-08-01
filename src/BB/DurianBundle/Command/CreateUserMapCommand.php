<?php

namespace BB\DurianBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * 於 redis 建立 user_id vs domain 及 user_id vs username 對應表
 *
 * @author Sweet 2016.03.04
 */
class CreateUserMapCommand extends ContainerAwareCommand
{
    /**
     * @var InputInterface
     */
    private $input;

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
            ->setName('durian:create-user-map')
            ->setDescription('建立使用者對應表')
            ->addOption('domain', null, InputOption::VALUE_NONE, '建立廳對應表')
            ->addOption('username', null, InputOption::VALUE_NONE, '建立使用者名稱對應表')
            ->addOption('remove', null, InputOption::VALUE_NONE, '建立刪除使用者')
            ->setHelp(<<<EOT
** 需先跑 removed_user 再跑 user 避免相同id 被下 expire **

建立刪除使用者 user_id vs domain 及 user_id vs username 對應表
$ app/console durian:create-user-map --domain --username --remove

僅建立刪除使用者 user_id vs domain 對應表
$ app/console durian:create-user-map --domain --remove

僅建立刪除使用者 user_id vs username 對應表
$ app/console durian:create-user-map --username --remove

建立使用者 user_id vs domain 及 user_id vs username 對應表
$ app/console durian:create-user-map --domain --username

僅建立使用者 user_id vs domain 對應表
$ app/console durian:create-user-map --domain

僅建立使用者 user_id vs username 對應表
$ app/console durian:create-user-map --username
EOT
            );
    }

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $startTime = new \DateTime;

        $this->input = $input;
        $this->output = $output;

        if ($this->input->getOption('domain')) {
            $this->findTable('domain');
        }

        if ($this->input->getOption('username')) {
            $this->findTable('username');
        }

        $this->end($startTime);
    }

    /**
     * 判斷table
     *
     * @param string $mapTag 對應表標籤
     */
    private function findTable($mapTag)
    {
        if ($this->input->getOption('remove')) {
            return $this->createRemovedUserMap($mapTag);
        }

        $this->createUserMap($mapTag);
    }

    /**
     * 建立使用者對應表
     *
     * @param string $mapTag 對應表標籤
     */
    private function createUserMap($mapTag)
    {
        $conn = $this->getContainer()->get('doctrine.dbal.default_connection');
        $redis = $this->getContainer()->get('snc_redis.map');
        $um = $this->getContainer()->get('durian.user_manager');

        $sql = 'SELECT MAX(id) FROM user';
        $maxId = $conn->fetchColumn($sql);

        $id = 0;

        while($id < $maxId) {
            $sql = "SELECT id, $mapTag FROM user WHERE id > ? and id <= ?";
            $users = $conn->fetchAll($sql, [$id, $id += 10000]);

            if (!$users) {
                continue;
            }

            $batch = [];

            foreach ($users as $user) {
                $key = $um->getKey($user['id'], $mapTag);
                $batch[$key] = $user[$mapTag];
            }

            $redis->mset($batch);
            $this->output->writeln("currentId: $id");
        }

        $this->output->writeln('Create user-' . $mapTag . ' map success.');
    }

    /**
     * 建立刪除使用者對應表
     *
     * @param string $mapTag 對應表標籤
     */
    private function createRemovedUserMap($mapTag)
    {
        $conn = $this->getContainer()->get('doctrine.dbal.share_connection');
        $redis = $this->getContainer()->get('snc_redis.map');
        $um = $this->getContainer()->get('durian.user_manager');

        $sql = 'SELECT MAX(user_id) FROM removed_user';
        $maxId = $conn->fetchColumn($sql);

        $id = 0;

        while($id < $maxId) {
            $sql = "SELECT user_id, $mapTag FROM removed_user WHERE user_id > ? and user_id <= ?";
            $users = $conn->fetchAll($sql, [$id, $id += 10000]);

            if (!$users) {
                continue;
            }

            foreach ($users as $user) {
                $key = $um->getKey($user['user_id'], $mapTag);

                // 保留 90天
                $redis->set($key, $user[$mapTag]);
                $redis->expire($key, 7776000);
            }

            $this->output->writeln("currentId: $id");
        }

        $this->output->writeln('Create removed-user-' . $mapTag . ' map success.');
    }

    /**
     * 程式結束顯示處理時間、記憶體
     *
     * @param \DateTime $startTime 開始時間
     */
    private function end($startTime)
    {
        $endTime = new \DateTime;
        $costTime = $endTime->diff($startTime, true);
        $this->output->writeln('Execute time: ' . $costTime->format('%H:%I:%S'));

        $memory = memory_get_peak_usage() / 1024 / 1024;
        $usage = number_format($memory, 2);
        $this->output->writeln('Memory MAX use: ' . $usage . 'M');
    }
}

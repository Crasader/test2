<?php

namespace BB\DurianBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * 建立session的白名單與維護資訊
 *
 * @author Evan 2016.07.04
 */
class CreateSessionInfoCommand extends ContainerAwareCommand
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
            ->setName('durian:create-session-info')
            ->setDescription('設定session的維護與白名單資訊')
            ->addOption('maintain', null, InputOption::VALUE_NONE, '設定維護資訊')
            ->addOption('whitelist', null, InputOption::VALUE_NONE, '設定白名單資訊')
            ->setHelp(<<<EOT
從mysql的maintain資料表建立session的維護資訊
$ ./console durian:create-session-info --maintain

從mysql的maintain_whitelist資料表建立session的白名單資訊
$ ./console durian:create-session-info --whitelist
EOT
            );
    }

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input  = $input;
        $this->output = $output;

        if ($input->getOption('maintain')) {
            $this->createSesisonMaintain();
            $this->output->write("CreateSesstionMaintain Success.", true);
        }

        if ($input->getOption('whitelist')) {
            $this->createSesisonWhitelist();
            $this->output->write("CreateSesstionWhitelist Success.", true);
        }
    }

    /**
     * 建立session的維護資訊
     */
    private function createSesisonMaintain()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $redis = $this->getContainer()->get('snc_redis.cluster');

        $maintainKey = 'session_maintain';

        if ($redis->exists($maintainKey)) {
            $redis->del($maintainKey);
        }

        $repo = $em->getRepository('BBDurianBundle:Maintain');
        $allMaintain = $repo->getAllMaintain();

        foreach ($allMaintain as $field) {
            $data = [
                'begin_at' => $field['beginAt']->format('Y-m-d H:i:s'),
                'end_at' => $field['endAt']->format('Y-m-d H:i:s'),
                'msg' => $field['msg']
            ];

            $redis->hmset($maintainKey, $field['code'], json_encode($data));
        }
    }

    /**
     * 建立session的白名單資訊
     */
    private function createSesisonWhitelist()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $redis = $this->getContainer()->get('snc_redis.cluster');

        $whitelistKey = 'session_whitelist';

        if ($redis->exists($whitelistKey)) {
            $redis->del($whitelistKey);
        }

        $repo = $em->getRepository('BBDurianBundle:MaintainWhitelist');
        $whitelists = $repo->findAll();

        foreach ($whitelists as $whitelist) {
            $redis->sadd($whitelistKey, $whitelist->getIp());
        }
    }
}

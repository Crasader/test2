<?php

namespace BB\DurianBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use BB\DurianBundle\Entity\User;

/**
 * 轉移體系
 */
class ChangeParentCommand extends ContainerAwareCommand
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
     * @var \Doctrine\ORM\EntityManager
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

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function configure()
    {
        $this
            ->setName('durian:change:parent')
            ->setDescription('轉移體系')
            ->addOption('user-id', null, InputOption::VALUE_REQUIRED, 'userId', null)
            ->addOption('parent-id', null, InputOption::VALUE_REQUIRED, 'parentId', null)
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'limit', null)
            ->addOption('disable-log', null, InputOption::VALUE_NONE, 'Disable logging')
            ->setHelp(<<<EOT
轉移體系
app/console durian:change:parent --user-id=123 --parent-id=456
轉移體系,批次更新
app/console durian:change:parent --user-id=123 --parent-id=456 --limit=1000
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
        $redis = $this->getContainer()->get('snc_redis.default');

        $this->disableLog = $this->input->getOption('disable-log');
        $userId    = $this->input->getOption('user-id');
        $parentId  = $this->input->getOption('parent-id');
        $limit = $this->input->getOption('limit');

        $em = $this->getEntityManager();

        $this->log("ChangeParentCommand Start.");
        $this->log("UserId: $userId ParentId: $parentId");

        $startTime = microtime(true);

        $em->beginTransaction();

        try {
            $activateSLNext = $this->getContainer()->get('durian.activate_sl_next');
            $curDate = new \DateTime('now');

            if ($activateSLNext->isUpdating($curDate)) {
                throw new \RuntimeException('Cannot perform this during updating sharelimit', 150080042);
            }

            if (!$activateSLNext->hasBeenUpdated($curDate)) {
                throw new \RuntimeException(
                    'Cannot perform this due to updating sharelimit is not performed for too long time',
                    150080043
                );
            }

            $user = $this->findUser($userId);
            $targetParent = $this->findUser($parentId);

            $ancestorManager = $this->getContainer()->get('durian.ancestor_manager');
            $sizeQueue = $ancestorManager->changeParent($user, $targetParent, '', $limit);

            if (isset($sizeQueue['old_parent'])) {
                $data = [
                    'index' => $sizeQueue['old_parent'],
                    'value' => -1
                ];
                $redis->rpush('user_size_queue', json_encode($data));
            }

            if (isset($sizeQueue['new_parent'])) {
                $data = [
                    'index' => $sizeQueue['new_parent'],
                    'value' => 1
                ];
                $redis->rpush('user_size_queue', json_encode($data));
            }

            $em->flush();

            $this->getContainer()->get('durian.share_scheduled_for_update')->execute();

            $em->flush();
            $em->commit();
            $this->log("Success");
            $this->output->write("Success", true);
        } catch (\Exception $e) {
            $em->rollback();
            $code = $e->getCode();
            $msg = $e->getMessage();

            $log = "Error:$msg Code:$code";
            $this->log($log);
            $this->output->write($log, true);
        }

        $endTime = microtime(true);

        $excutionTime = round($endTime - $startTime, 1);
        $timeString = $excutionTime . ' sec.';

        $output->write("\nExecute time: $timeString", true);

        $memory = memory_get_peak_usage() / 1024 / 1024;
        $usage = number_format($memory, 2);

        $output->write("Memory MAX use: $usage M", true);

        $this->log("ChangeParentCommand finish.");
        $this->logger->popHandler()->close();
    }

    /**
     * 取得使用者
     *
     * @param integer $userId 使用者ID
     * @return User
     */
    private function findUser($userId)
    {
        $em = $this->getEntityManager();

        $user = $em->find('BB\DurianBundle\Entity\User', $userId);

        if (!$user) {
            throw new \RuntimeException('No such user', 150010029);
        }

        return $user;
    }

    /**
     * @return \Doctrine\ORM\EntityManager
     */
    protected function getEntityManager()
    {
        if ($this->em) {
            return $this->em;
        }

        $this->em = $this->getContainer()->get('doctrine.orm.entity_manager');
        return $this->em;
    }

    /**
     * 設定並記錄log
     *
     * @param String $message
     */
    private function log($msg)
    {
        if ($this->disableLog) {
            return;
        }

        if (null === $this->logger) {
            $this->logger = $this->getContainer()->get('durian.logger_manager')
                ->setUpLogger('ChangeParent.log');
        }

        $this->logger->addInfo($msg);
    }
}

<?php

namespace BB\DurianBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use BB\DurianBundle\Share\ValidateShareLimitHelper;

/**
 * 驗證所有佔成
 *
 * 操作方法：app/console durian:sl:validate 5 (驗證廳主開始算深度5現行啟用使用者佔成)
 *
 * --next     驗證預改佔成
 * --disable  驗證停用使用者
 * --domain 5 指定驗證某廳
 * --desc     指定倒序驗證會員佔成
 * --fix      自動修正停用使用者佔成
 *
 * 如有錯誤佔成會輸出 validate_sharelimit_output.csv
 */
class ValidateShareLimitCommand extends ContainerAwareCommand
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
     * 檔案存取
     * @var Opens file
     * @link http://php.net/manual/en/function.fopen.php
     */
    private $fileOpen;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function configure()
    {
        $this
            ->setName('durian:sl:validate')
            ->setDescription('驗證所有使用者佔成')
            ->addArgument('depth', InputArgument::REQUIRED, '驗證廳主開始算深度')
            ->addOption('next', null, InputOption::VALUE_NONE, '驗證預改佔成')
            ->addOption('disable', null, InputOption::VALUE_NONE, '驗證停用使用者')
            ->addOption('domain', null, InputOption::VALUE_OPTIONAL, '指定驗證某廳')
            ->addOption('desc', null, InputOption::VALUE_NONE, '指定倒序驗證會員佔成')
            ->addOption('fix', null, InputOption::VALUE_NONE, '自動修正停用使用者佔成')
            ->setHelp(<<<EOT
驗證所有使用者佔成

操作方法：app/console durian:sl:validate 5 (驗證廳主開始算深度5現行啟用使用者佔成)

 --next     驗證預改佔成
 --disable  驗證停用使用者
 --domain 5 指定驗證某廳
 --desc     指定倒序驗證會員佔成
 --fix      自動修正停用使用者佔成
如有錯誤佔成會輸出 validate_sharelimit_output.csv
EOT
            );
    }

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->getEntityManager();

        $this->input  = $input;
        $this->output = $output;

        //依據參數決定是否檢查停用使用者
        $isDisable = $this->input->getOption('disable');

        //依據參數決定是否自動修復停用的使用者佔成
        $fix = $this->input->getOption('fix');

        //檢查深度
        $depth = $this->input->getArgument('depth');

        //使用者id排序方式
        $order = $this->input->getOption('desc') ? 'desc' : 'asc';

        //依據參數決定是否檢查停用使用者
        $next = $this->input->getOption('next');

        //所有錯誤訊息收集陣列
        $errorMsgs = array();

        $this->log("ValidateShareLimitCommand Depth:$depth $order begin ...");

        $logDir = $this->getContainer()->getParameter('kernel.logs_dir');
        $this->fileOpen = fopen("$logDir/validate_sharelimit_output.csv", 'a');

        $helper = new ValidateShareLimitHelper($this->getContainer());

        $domains = array();
        $domains = $helper->loadDomains($this->input->getOption('domain'));

        foreach ($domains as $domain) {
            $count = $helper->countChildOf($domain, $depth, $isDisable);
            $totalPage = $helper->getTotalPage($count);

            $domainId = $domain->getId();
            $msg = "Validate Domain:$domainId Depth: $depth Childs: $count ShareLimit";
            $output->write($msg, true);
            $this->log($msg);

            $config = $this->getEntityManager('share')->find('BBDurianBundle:DomainConfig', $domainId);

            for ($page = 1; $page <= $totalPage; $page++) {
                $firstResult = $helper->processRecodes($page, $totalPage);
                $users = $helper->getChildOf($domain, $isDisable, $firstResult, $order);
                $errorMsgs = $helper->processUsers(
                    $users,
                    $next,
                    $fix,
                    $config->getName(),
                    $this->logger,
                    $this->fileOpen
                );

                if ($fix) {
                    $em->flush();
                    $this->getContainer()->get('durian.share_scheduled_for_update')->execute();
                    $em->flush();
                }

                //清除em，釋放已使用完的users變數資料
                unset($users);
                $em->clear();

                //計算百分比，並印出
                $per = number_format(($firstResult / $count) * 100, 2);
                $output->write("$per %  $firstResult / $count", true);
                unset($per);
            }
        }

        //如有錯誤資訊便印出
        foreach ($errorMsgs as $string) {
            $output->write($string, true);
        }

        if (empty($errorMsgs)) {
            $output->write('No error', true);
        }

        fclose($this->fileOpen);

        $this->log('ValidateShareLimitCommand finish.');
        $this->logger->popHandler()->close();
    }

    /**
     * 回傳 EntityManager 物件
     *
     * @param string $name Entity Manager 名稱
     * @return \Doctrine\ORM\EntityManager
     */
    private function getEntityManager($name = 'default')
    {
        return $this->getContainer()->get("doctrine.orm.{$name}_entity_manager");
    }

    /**
     * 設定並記錄log
     *
     * @param String $message
     */
    private function log($msg)
    {
        if (null === $this->logger) {
            $this->logger = $this->getContainer()->get('durian.logger_manager')
                ->setUpLogger('validate_sl.log');
        }

        $this->logger->addInfo($msg);
    }
}

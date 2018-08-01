<?php

namespace BB\DurianBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * 比對資料庫設定
 */
class CompareDbSettingCommand extends ContainerAwareCommand
{
    /**
     * @var InputInterface
     */
    private $input;

    /**
     * @var InputInterface
     */
    private $output;

    /**
     * master連線
     *
     * @var \Doctrine\DBAL\Connection
     */
    private $masterConn;

    /**
     * slave連線
     *
     * @var \Doctrine\DBAL\Connection
     */
    private $slaveConn;

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function configure()
    {
        $this
            ->setName('durian:compare-db-setting')
            ->setDescription('比對資料庫設定')
            ->addOption('master', null, InputOption::VALUE_REQUIRED, 'master ip', null)
            ->addOption('slave', null, InputOption::VALUE_REQUIRED, 'slave ip', null)
            ->addOption('user', null, InputOption::VALUE_REQUIRED, 'db帳號名稱', null)
            ->addOption('port', null, InputOption::VALUE_REQUIRED, 'port, 預設使用3306', null)
            ->setHelp(<<<EOT
以master db為基準, 與slave db的設定參數作比對, 若有相異之處, 會顯示出來
app/console durian:compare-db-setting --master=172.26.80.3 --slave=172.26.79.5 --user=durian

若要指定port, 則加上--port, 預設使用3306
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

        $this->init();

        // 抓master, slave全部設定
        $masterSetting = array();
        $slaveSetting = array();

        $ret = $this->masterConn->fetchAll('show variables');

        foreach ($ret as $setting) {
            $name = $setting['Variable_name'];
            $value = $setting['Value'];
            $masterSetting[$name] = $value;
        }

        $ret = $this->slaveConn->fetchAll('show variables');
        foreach ($ret as $setting) {
            $name = $setting['Variable_name'];
            $value = $setting['Value'];
            $slaveSetting[$name] = $value;
        }

        $noNeedToCheck = array(
            'general_log_file',
            'hostname',
            'log_error',
            'pid_file',
            'server_id',
            'slow_query_log_file',
            'pseudo_thread_id'
        );

        $output->write("\nslave資料庫設定與master資料庫設定不符合的地方:\n", true);
        foreach ($slaveSetting as $name => $slaveValue) {
            if (in_array($name, $noNeedToCheck)) {
                continue;
            }

            if (!isset($masterSetting[$name])) {
                $output->write("[$name] master: 未設定, slave: $slaveValue", true);
                continue;
            }

            $masterValue = $masterSetting[$name];
            if ($masterValue != $slaveValue) {
                $output->write("[$name] master: $masterValue, slave: $slaveValue", true);
            }
        }

        $output->write("\n");
    }

    /**
     * 初始化
     */
    private function init()
    {
        // 檢查輸入參數
        $masterIp = $this->input->getOption('master');
        $slaveIp = $this->input->getOption('slave');
        $user = $this->input->getOption('user');
        $port = $this->input->getOption('port');

        if (empty($masterIp)) {
            throw new \Exception("No master ip specfied");
        }

        if (empty($slaveIp)) {
            throw new \Exception("No slave ip specfied");
        }

        if (empty($user)) {
            throw new \Exception("No user specfied");
        }

        if (empty($port)) {
            // 連到mysql預設用port 3306
            $port = 3306;
        }

        $dialog = $this->getHelperSet()->get('dialog');
        $password = $dialog->askHiddenResponse($this->output, 'password:');

        // 產生connection
        $params = array(
            'user'     => $user,
            'port'     => $port,
            'password' => $password,
            'host'     => $masterIp,
            'charset'  => 'utf8',
            'driver'   => 'pdo_mysql',
        );

        $this->masterConn = \Doctrine\DBAL\DriverManager::getConnection($params);

        $params = array(
            'user'     => $user,
            'port'     => $port,
            'password' => $password,
            'host'     => $slaveIp,
            'charset'  => 'utf8',
            'driver'   => 'pdo_mysql',
        );

        $this->slaveConn = \Doctrine\DBAL\DriverManager::getConnection($params);
    }
}

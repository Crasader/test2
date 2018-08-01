<?php

namespace BB\DurianBundle\Logger;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Symfony\Component\DependencyInjection\ContainerAware;

/**
 * 建立Logger管理log檔案
 *
 * @author Michael 2016.01.08
 */
class LoggerManager extends ContainerAware
{
    /**
     * 設定log檔放置於對應運行環境資料夾
     *
     * @param String $name
     * @param StreamHandler $handler
     * @param String $folder
     * @return Logger $logger
     */
    public function setUpLogger($name, $handler = null, $folder = '')
    {
        $logger = new Logger('LOGGER');
        $env = $this->container->getParameter('kernel.environment');
        $envDir = $this->container->getParameter('kernel.logs_dir') . DIRECTORY_SEPARATOR . $env;

        if ($folder) {
            $envDir = $envDir . DIRECTORY_SEPARATOR . $folder;
        }

        $logPath = $envDir . DIRECTORY_SEPARATOR . $name;

        // 當資料夾不存在則新增；當log檔不存在則新增
        if (!file_exists($logPath)) {
            $dir = dirname($logPath);

            if (!file_exists($dir)) {
                mkdir($dir, 0775, true);
            }

            $handle = fopen($logPath, 'w+');
            fclose($handle);
        }

        if ($handler) {
            $logger->pushHandler($handler);

            return $logger;
        }

        $logger->pushHandler(new StreamHandler($logPath, Logger::DEBUG));

        return $logger;
    }
}

<?php

namespace BB\DurianBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * 公私鑰檢查修正
 */
class CheckMerchantRSAKeyCommand extends ContainerAwareCommand
{
    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function configure()
    {
        $this
            ->setName('durian:check-merchant-rsa-key')
            ->setDescription('公私鑰檢查修正')
            ->setHelp(<<<EOT
公私鑰檢查修正
EOT
            );
    }

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('rsa key 檢查修正開始');
        $redis = $this->getContainer()->get('snc_redis.default_client');
        $count = 0;
        $countPair = 0;

        while ($count < 1000) {
            $rsaKeyInfo = json_decode($redis->rpop('merchant_rsa_key_queue'), true);
            $publicKey = '';
            $privateKey = '';

            if (!$rsaKeyInfo) {
                break;
            }

            $commonInfo = 'id=' . $rsaKeyInfo['id'] . '&';
            $commonInfo .= 'payment_gateway_id=' . $rsaKeyInfo['payment_gateway_id'] . '&';
            $commonInfo .= 'at=' . $rsaKeyInfo['at'] . '&';
            $commonInfo .= 'method=' . $rsaKeyInfo['method'] . "&\n";
            $merchantInfo = $commonInfo . "public_key=\n" . $rsaKeyInfo['public_key'] . "\n";

            if ($rsaKeyInfo['public_key']) {
                $publicKey = $this->refreshPublicKey($rsaKeyInfo['public_key'], $merchantInfo);
            }

            $merchantInfo = $commonInfo . "private_key=\n" . $rsaKeyInfo['private_key'] . "\n";

            if ($rsaKeyInfo['private_key']) {
                $privateKey = $this->refreshPrivateKey($rsaKeyInfo['private_key'], $merchantInfo);
            }

            ++$count;

            if ($this->verifyRsaIsPair($publicKey, $privateKey, $rsaKeyInfo['id'] . ',' . $rsaKeyInfo['at'])) {
                ++$countPair;
            }
        }

        $output->writeln($count . '組 rsa key 檢查修正');
        $output->writeln($countPair . '筆公私鑰成對');
        $output->writeln('rsa key 檢查修正結束');
    }

    /**
     * 重整rsa公鑰
     *
     * @param string $content 金鑰字串
     * @param string $requestInfo 記錄商號相關訊息
     * @return string | null 訊息
     */
    private function refreshPublicKey($content, $requestInfo)
    {
        if (openssl_pkey_get_public($content)) {
            // 格式正確，log 記錄測試
            $this->log($requestInfo, "success format\n" . $content);

            return $content;
        }

        $value = preg_replace('/-+[a-zA-Z\s]*-+/', '', $content);
        $value = preg_replace('/[^a-zA-Z0-9\/\+=]/', '', $value);

        $type = $this->getPublicKeyType($value);
        $refreshKey = sprintf(
            '%s%s%s',
            '-----' . $type['header'] . '-----' . "\n",
            chunk_split($value, 64, "\n"),
            '-----' . $type['footer'] . '-----' . "\n"
        );

        if (openssl_pkey_get_public($refreshKey)) {
            // 修補成功，log 記錄測試
            $this->log($requestInfo, "success patch\n" . $refreshKey);

            return $refreshKey;
        }

        // 格式錯誤，log 記錄測試
        $this->log($requestInfo, "fail format\n" . $refreshKey);

        return null;
    }

    /**
     * 重整rsa私鑰
     *
     * @param string $content 金鑰字串
     * @param string $requestInfo 記錄商號相關訊息
     * @return string | null 訊息
     */
    private function refreshPrivateKey($content, $requestInfo)
    {
        if (openssl_pkey_get_private($content)) {
            // 格式正確，log 記錄測試
            $this->log($requestInfo, "success format\n" . $content);

            return $content;
        }

        $value = preg_replace('/-+[a-zA-Z\s]*-+/', '', $content);
        $value = preg_replace('/[^a-zA-Z0-9\/\+=]/', '', $value);

        $type = $this->getPrivateKeyType($value);
        $refreshKey = sprintf(
            '%s%s%s',
            '-----' . $type['header'] . '-----' . "\n",
            chunk_split($value, 64, "\n"),
            '-----' . $type['footer'] . '-----' . "\n"
        );

        if (openssl_pkey_get_private($refreshKey)) {
            // 修補成功，log 記錄測試
            $this->log($requestInfo, "success patch\n" . $refreshKey);

            return $refreshKey;
        }

        // 格式錯誤，log 記錄測試
        $this->log($requestInfo, "fail format\n" . $refreshKey);

        return null;
    }

    /**
     * 取得公鑰類型
     *
     * @param string $brokenKey 金鑰字串
     * @return array 公鑰頭尾
     */
    private function getPublicKeyType($brokenKey)
    {
        $header = 'BEGIN PUBLIC KEY';
        $footer = 'END PUBLIC KEY';

        // CERTIFICATE 公鑰格式字串數: 行數 * 每行字數 + 尾行字數
        if (strlen($brokenKey) == (10 * 76 + 28) ||
            strlen($brokenKey) == (11 * 76 + 4) ||
            strlen($brokenKey) == (13 * 64 + 12)
        ) {
            $header = 'BEGIN CERTIFICATE';
            $footer = 'END CERTIFICATE';
        }

        return [
            'header' => $header,
            'footer' => $footer
        ];
    }

    /**
     * 取得私鑰類型
     *
     * @param string $brokenKey 金鑰字串
     * @return array 私鑰頭尾
     */
    private function getPrivateKeyType($brokenKey)
    {
        $header = 'BEGIN RSA PRIVATE KEY';
        $footer = 'END RSA PRIVATE KEY';

        // PKCS8 1024 位元、2048 位元 (行數 * 每行字數 + 尾行字數)
        if (strlen($brokenKey) == (13 * 64 + 12) ||
            strlen($brokenKey) == (13 * 64 + 16) ||
            strlen($brokenKey) == (13 * 64 + 20) ||
            strlen($brokenKey) == (25 * 64 + 20) ||
            strlen($brokenKey) == (25 * 64 + 24) ||
            strlen($brokenKey) == (25 * 64 + 28)
        ) {
            $header = 'BEGIN PRIVATE KEY';
            $footer = 'END PRIVATE KEY';
        }

        return [
            'header' => $header,
            'footer' => $footer
        ];
    }

    /**
     * 檢查公私鑰是否成對
     *
     * @param string $publicKey 公鑰
     * @param string $privateKey 私鑰
     * @param string $requestInfo 記錄商號相關訊息
     * @return bool
     */
    private function verifyRsaIsPair($publicKey, $privateKey, $requestInfo)
    {
        if (!$publicKey || !$privateKey) {
            return false;
        }

        $data = 'rsa';

        $encrypted = '';
        openssl_public_encrypt($data, $encrypted, $publicKey);
        $value = base64_encode($encrypted);

        $decrypted = '';
        openssl_private_decrypt(base64_decode($value), $decrypted, $privateKey, OPENSSL_PKCS1_PADDING);

        if ($data == $decrypted) {
            // 公私鑰成對，log 記錄測試
            $this->log($requestInfo, "Is Pair\n");
        }

        return $data == $decrypted;
    }

    /**
     * log記錄
     *
     * @param string $requestInfo 記錄商號相關訊息
     * @param string $responseInfo 訊息
     */
    private function log($requestInfo, $responseInfo)
    {
        $logger = $this->getContainer()->get('durian.logger_manager')->setUpLogger('refresh_key.log');
        $logContent = sprintf(
            '%s %s "%s %s" "REQUEST:'."\n".'%s" "RESPONSE:'."\n".'%s"',
            '127.0.0.1',
            '127.0.0.1',
            'refreshKey',
            '---',
            $requestInfo,
            $responseInfo
        );

        $logger->addInfo($logContent);
        $logger->popHandler()->close();
    }
}

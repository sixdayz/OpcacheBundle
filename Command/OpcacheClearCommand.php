<?php

namespace Sixdays\OpcacheBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class OpcacheClearCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setDescription('Clear opcache cache')
            ->setName('opcache:clear')
            ->addOption('base-url', null, InputOption::VALUE_REQUIRED, 'Url for clear opcode cache')
            ->addOption('retry', null, InputOption::VALUE_REQUIRED, 'How many times the clearing should be retried if it fails')
            ->addOption('retry-timeout', null, InputOption::VALUE_REQUIRED, 'Timeout between retries in milliseconds');

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $webDir     = $this->getContainer()->getParameter('sixdays_opcache.web_dir');
        $baseUrl   = $input->getOption('base-url')
            ? $input->getOption('base-url')
            : $this->getContainer()->getParameter('sixdays_opcache.base_url');

        if (!is_dir($webDir)) {
            throw new \InvalidArgumentException(sprintf('Web dir does not exist "%s"', $webDir));
        }

        if (!is_writable($webDir)) {
            throw new \InvalidArgumentException(sprintf('Web dir is not writable "%s"', $webDir));
        }

        $filename = 'opcache-'.md5(uniqid().mt_rand(0, 9999999).php_uname()).'.php';
        $file = $webDir.'/'.$filename;

        $templateFile = __DIR__.'/../Resources/template.tpl';
        $template = file_get_contents($templateFile);

        if (false === @file_put_contents($file, $template)) {
            throw new \RuntimeException(sprintf('Unable to write "%s"', $file));
        }

        $error = null;
        $retries = intval($input->getOption('retry')) ?: 1;
        $url = sprintf('%s/%s', $baseUrl, $filename);

        for ($i = 0; $i <= $retries; $i++) {
            $ch = curl_init();
            curl_setopt_array($ch, array(
                CURLOPT_URL             => $url,
                CURLOPT_RETURNTRANSFER  => true,
                CURLOPT_FAILONERROR     => true,
                CURLOPT_HEADER          => false,
                CURLOPT_SSL_VERIFYPEER  => false,
                CURLOPT_SSL_VERIFYHOST  => false
            ));

            $result = curl_exec($ch);
            $error = curl_error($ch);
            curl_close($ch);

            if (!$error) {
                break;
            } elseif ($timeout = intval($input->getOption('retry-timeout'))) {
                // usleep is working with microseconds, so we have to multiply with 1000
                usleep($timeout * 1000);
            }
        }

        if ($error) {
            unlink($file);
            throw new \RuntimeException(sprintf('Curl error reading "%s": %s', $url, $error));
        }

        $result = json_decode($result, true);
        unlink($file);

        if (! $result) {
            throw new \RuntimeException(sprintf('The response did not return valid json: %s', $result));
        }

        if($result['success']) {
            $output->writeln($result['message']);
        } else {
            throw new \RuntimeException($result['message']);
        }
    }
}

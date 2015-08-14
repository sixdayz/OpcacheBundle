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
            ->addOption('host-name', null, InputOption::VALUE_REQUIRED, 'Url for clear opcode cache')
            ->addOption('host-ip', null, InputOption::VALUE_REQUIRED, 'IP for clear opcode cache')
            ->addOption('protocol', null, InputOption::VALUE_REQUIRED, 'Whether to use http or https');

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $webDir     = $this->getContainer()->getParameter('sixdays_opcache.web_dir');
        $hostName   = $input->getOption('host-name')
            ? $input->getOption('host-name')
            : $this->getContainer()->getParameter('sixdays_opcache.host_name');
        $hostIp     = $input->getOption('host-ip')
            ? $input->getOption('host-ip')
            : $this->getContainer()->getParameter('sixdays_opcache.host_ip');
        $protocol   = $input->getOption('protocol')
            ? $input->getOption('protocol')
            : $this->getContainer()->getParameter('sixdays_opcache.protocol');

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

        $url = sprintf('%s://%s/%s', $protocol, $hostName, $filename);

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

        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            unlink($file);
            throw new \RuntimeException(sprintf('Curl error reading "%s": %s', $url, $error));
        }

        curl_close($ch);

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

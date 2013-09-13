<?php

namespace Sixdays\OpcacheBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class OpcacheClearCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setDescription('Clear opcache cache')->setName('opcache:clear');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $webDir = $this->getContainer()->getParameter('sixdays_opcache.web_dir');

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

        if (!$host = $this->getContainer()->getParameter('sixdays_opcache.host')) {
            $host = sprintf(
                "%s://%s",
                $this->getContainer()->getParameter('router.request_context.scheme'),
                $this->getContainer()->getParameter('router.request_context.host')
            );
        }

        $url = $host.'/'.$filename;

        $ch = curl_init($url);
        curl_setopt_array($ch, array(
            CURLOPT_HEADER          => false,
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_FAILONERROR     => true
        ));

        $result = curl_exec($ch);

        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            unlink($file);
            throw new \RuntimeException(sprintf('Curl error reading "%s": %s', $url, $error));
        }

        curl_close($ch);

        var_dump($result);

        $result = json_decode($result, true);
        unlink($file);

        if($result['success']) {
            $output->writeln($result['message']);
        } else {
            throw new \RuntimeException($result['message']);
        }
    }
}

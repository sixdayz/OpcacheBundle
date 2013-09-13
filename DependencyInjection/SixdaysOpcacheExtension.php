<?php

namespace Sixdays\OpcacheBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class SixdaysOpcacheExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $processor = new Processor();
        $configuration = new Configuration();
        $config = $processor->process($configuration->getConfigTree(), $configs);

        $container->setParameter('sixdays_opcache.host_ip', $config['host_ip']);
        $container->setParameter('sixdays_opcache.host_name', $config['host_name']);
        $container->setParameter('sixdays_opcache.web_dir', $config['web_dir']);
    }
}

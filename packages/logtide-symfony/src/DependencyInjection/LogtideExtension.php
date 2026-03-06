<?php

declare(strict_types=1);

namespace LogTide\Symfony\DependencyInjection;

use LogTide\Symfony\EventSubscriber\ConsoleSubscriber;
use LogTide\Symfony\EventSubscriber\RequestSubscriber;
use LogTide\Symfony\Integration\DoctrineIntegration;
use LogTide\Symfony\Integration\SymfonyIntegration;
use LogTide\LogtideSdk;
use LogTide\State\Hub;
use LogTide\State\HubInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Reference;

class LogtideExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('logtide.dsn', $config['dsn']);
        $container->setParameter('logtide.service', $config['service']);
        $container->setParameter('logtide.environment', $config['environment']);
        $container->setParameter('logtide.release', $config['release']);
        $container->setParameter('logtide.batch_size', $config['batch_size']);
        $container->setParameter('logtide.flush_interval', $config['flush_interval']);
        $container->setParameter('logtide.max_buffer_size', $config['max_buffer_size']);
        $container->setParameter('logtide.max_retries', $config['max_retries']);
        $container->setParameter('logtide.traces_sample_rate', $config['traces_sample_rate']);
        $container->setParameter('logtide.debug', $config['debug']);
        $container->setParameter('logtide.send_default_pii', $config['send_default_pii']);

        $sdkConfig = [];
        foreach ($config as $key => $value) {
            if ($value !== null) {
                $sdkConfig[$key] = $value;
            }
        }

        $hubDefinition = new Definition(HubInterface::class);
        $hubDefinition->setFactory([LogtideSdk::class, 'init']);
        $hubDefinition->setArguments([$sdkConfig]);
        $hubDefinition->setPublic(true);
        $container->setDefinition(HubInterface::class, $hubDefinition);
        $container->setAlias(Hub::class, HubInterface::class);
        $container->setAlias('logtide.hub', HubInterface::class)->setPublic(true);

        $requestSubscriber = new Definition(RequestSubscriber::class);
        $requestSubscriber->setArguments([new Reference(HubInterface::class)]);
        $requestSubscriber->addTag('kernel.event_subscriber');
        $container->setDefinition(RequestSubscriber::class, $requestSubscriber);

        $consoleSubscriber = new Definition(ConsoleSubscriber::class);
        $consoleSubscriber->setArguments([new Reference(HubInterface::class)]);
        $consoleSubscriber->addTag('kernel.event_subscriber');
        $container->setDefinition(ConsoleSubscriber::class, $consoleSubscriber);

        $symfonyIntegration = new Definition(SymfonyIntegration::class);
        $symfonyIntegration->setArguments([
            new Reference(RequestSubscriber::class),
            new Reference(ConsoleSubscriber::class),
        ]);
        $container->setDefinition(SymfonyIntegration::class, $symfonyIntegration);

        $doctrineIntegration = new Definition(DoctrineIntegration::class);
        $doctrineIntegration->setArguments([new Reference(HubInterface::class)]);
        $container->setDefinition(DoctrineIntegration::class, $doctrineIntegration);
    }

    public function getAlias(): string
    {
        return 'logtide';
    }
}

<?php

declare(strict_types=1);

namespace LogTide\Symfony\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('logtide');

        $treeBuilder->getRootNode()
            ->children()
                ->scalarNode('dsn')
                    ->defaultNull()
                    ->info('LogTide DSN for connecting to the backend')
                ->end()
                ->scalarNode('service')
                    ->defaultValue('symfony')
                    ->info('Service name for identifying this application')
                ->end()
                ->scalarNode('environment')
                    ->defaultNull()
                    ->info('Environment name (e.g., production, staging)')
                ->end()
                ->scalarNode('release')
                    ->defaultNull()
                    ->info('Application release/version identifier')
                ->end()
                ->integerNode('batch_size')
                    ->defaultValue(100)
                    ->min(1)
                ->end()
                ->integerNode('flush_interval')
                    ->defaultValue(5000)
                    ->min(100)
                    ->info('Flush interval in milliseconds')
                ->end()
                ->integerNode('max_buffer_size')
                    ->defaultValue(10000)
                    ->min(1)
                ->end()
                ->integerNode('max_retries')
                    ->defaultValue(3)
                    ->min(0)
                ->end()
                ->floatNode('traces_sample_rate')
                    ->defaultValue(1.0)
                    ->min(0.0)
                    ->max(1.0)
                ->end()
                ->booleanNode('debug')
                    ->defaultFalse()
                ->end()
                ->booleanNode('send_default_pii')
                    ->defaultFalse()
                    ->info('Whether to send personally identifiable information')
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}

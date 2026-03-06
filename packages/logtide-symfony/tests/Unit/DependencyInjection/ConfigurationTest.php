<?php

declare(strict_types=1);

namespace LogTide\Symfony\Tests\Unit\DependencyInjection;

use LogTide\Symfony\DependencyInjection\Configuration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Processor;

final class ConfigurationTest extends TestCase
{
    public function testDefaultValues(): void
    {
        $processor = new Processor();
        $config = $processor->processConfiguration(new Configuration(), []);

        $this->assertNull($config['dsn']);
        $this->assertSame('symfony', $config['service']);
        $this->assertNull($config['environment']);
        $this->assertNull($config['release']);
        $this->assertSame(100, $config['batch_size']);
        $this->assertSame(5000, $config['flush_interval']);
        $this->assertSame(10000, $config['max_buffer_size']);
        $this->assertSame(3, $config['max_retries']);
        $this->assertSame(1.0, $config['traces_sample_rate']);
        $this->assertFalse($config['debug']);
        $this->assertFalse($config['send_default_pii']);
    }

    public function testCustomValues(): void
    {
        $processor = new Processor();
        $config = $processor->processConfiguration(new Configuration(), [[
            'dsn' => 'https://lp_key@example.com',
            'service' => 'my-app',
            'environment' => 'production',
            'release' => 'v1.0',
            'batch_size' => 50,
            'traces_sample_rate' => 0.5,
            'debug' => true,
        ]]);

        $this->assertSame('https://lp_key@example.com', $config['dsn']);
        $this->assertSame('my-app', $config['service']);
        $this->assertSame('production', $config['environment']);
        $this->assertSame('v1.0', $config['release']);
        $this->assertSame(50, $config['batch_size']);
        $this->assertSame(0.5, $config['traces_sample_rate']);
        $this->assertTrue($config['debug']);
    }

    public function testTracesRateClamped(): void
    {
        $this->expectException(\Symfony\Component\Config\Definition\Exception\InvalidConfigurationException::class);

        $processor = new Processor();
        $processor->processConfiguration(new Configuration(), [[
            'traces_sample_rate' => 1.5,
        ]]);
    }

    public function testBatchSizeMinimum(): void
    {
        $this->expectException(\Symfony\Component\Config\Definition\Exception\InvalidConfigurationException::class);

        $processor = new Processor();
        $processor->processConfiguration(new Configuration(), [[
            'batch_size' => 0,
        ]]);
    }
}

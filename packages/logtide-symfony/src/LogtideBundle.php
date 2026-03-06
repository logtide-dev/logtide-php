<?php

declare(strict_types=1);

namespace LogTide\Symfony;

use LogTide\Symfony\DependencyInjection\LogtideExtension;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class LogtideBundle extends Bundle
{
    public function getContainerExtension(): ExtensionInterface
    {
        return new LogtideExtension();
    }
}

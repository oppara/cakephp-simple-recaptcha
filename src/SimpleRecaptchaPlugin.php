<?php
declare(strict_types=1);

namespace Oppara\SimpleRecaptcha;

use Cake\Core\BasePlugin;
use Cake\Core\PluginApplicationInterface;

/**
 * Plugin for SimpleRecaptcha
 *
 * @psalm-api
 */
class SimpleRecaptchaPlugin extends BasePlugin
{
    /**
     * The name of this plugin
     *
     * @var string|null
     */
    protected ?string $name = 'SimpleRecaptcha';

    /**
     * Console middleware
     *
     * @var bool
     */
    protected bool $consoleEnabled = false;

    /**
     * Load routes or not
     *
     * @var bool
     */
    protected bool $routesEnabled = false;

    /**
     * Plugin bootstrap.
     *
     * @param \Cake\Core\PluginApplicationInterface<mixed> $app The host application
     * @return void
     */
    public function bootstrap(PluginApplicationInterface $app): void
    {
    }
}

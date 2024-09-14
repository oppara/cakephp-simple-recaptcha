<?php
declare(strict_types=1);

namespace Oppara\SimpleRecaptcha;

use Cake\Core\BasePlugin;
use Cake\Core\PluginApplicationInterface;

/**
 * Plugin for Recaptcha
 *
 * @psalm-api
 */
class RecaptchaPlugin extends BasePlugin
{
    /**
     * The name of this plugin
     *
     * @var string|null
     */
    protected ?string $name = 'Recaptcha';

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

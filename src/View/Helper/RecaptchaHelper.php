<?php
declare(strict_types=1);

namespace Oppara\SimpleRecaptcha\View\Helper;

use Cake\Core\Configure;
use Cake\View\Helper;
use RuntimeException;

/**
 * Recaptcha helper
 *
 * @property \Cake\View\Helper\HtmlHelper $Html
 * @property \Cake\View\Helper\FormHelper $Form
 * @psalm-api
 */
class RecaptchaHelper extends Helper
{
    /**
     * format of recaptcha api url
     *
     * @var string
     */
    public const FMT_SCRIPT = 'https://www.google.com/recaptcha/api.js?render=%s';

    /**
     * format of recaptcha script block
     *
     * @var string
     */
    public const FMT_SCRIPT_BLOCK = <<<EOF

grecaptcha.ready(function () {
  grecaptcha.execute('%s', {action: 'submit'}).then(function(token) {
    const recaptchaResponse = document.getElementById('%s');
    recaptchaResponse.value = token;
  });
});

EOF;

    /**
     * helpers to use
     *
     * @var array<array-key, mixed>
     */
    public array $helpers = [
        'Html',
        'Form',
    ];

    /**
     * Default configuration.
     *
     * - field: hidden field name for recaptcha token
     * - scriptBlock: block name to append recaptcha script
     *
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [
        'field' => 'recaptchaToken',
        'scriptBlock' => 'scriptBottom',
    ];

    /**
     * site key for reCAPTCHA
     *
     * @var string
     */
    private string $siteKey = '';

    /**
     * constructor hook method
     *
     * @param array<string, mixed> $config The configuration settings provided to this helper.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->siteKey = Configure::read('Recaptcha.v3.site_key', '');

        if (empty($this->siteKey)) {
            throw new RuntimeException('Recaptcha site key is not set.');
        }
    }

    /**
     * return reCAPTCHA site key
     *
     * @return string
     */
    public function getSiteKey(): string
    {
        return $this->siteKey;
    }

    /**
     * creates a hidden form input field for reCAPTCHA
     *
     * @return string
     */
    public function hidden(): string
    {
        $field = $this->getConfig('field');

        return $this->Form->hidden($field, [
            'id' => $field,
            'value' => '',
        ]);
    }

    /**
     * append reCAPTCHA script tab & block to a specific block
     *
     * @aaparam \Cake\Event\EventInterface $event
     * @return void
     */
    public function afterRender(): void
    {
        $this->script();
        $this->scriptBlock();
    }

    /**
     * append reCAPTCHA script tag to a specific block
     *
     * @return void
     */
    private function script(): void
    {
        $block = $this->getConfig('scriptBlock');
        $src = sprintf(self::FMT_SCRIPT, $this->siteKey);
        $this->Html->script($src, ['block' => $block]);
    }

    /**
     * append reCAPTCHA script block to a specific block
     *
     * @return void
     */
    private function scriptBlock(): void
    {
        $block = $this->getConfig('scriptBlock');
        $field = $this->getConfig('field');
        $script = sprintf(self::FMT_SCRIPT_BLOCK, $this->siteKey, $field);
        $this->Html->scriptBlock($script, ['block' => $block]);
    }
}

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
     * api url
     *
     * @var string
     */
    public const API_URL = 'https://www.google.com/recaptcha/api.js';

    /**
     * format of reCAPTCHA v3 script block
     *
     * @var string
     */
    public const FMT_V3_SCRIPT_BLOCK = <<<EOF

grecaptcha.ready(function () {
  grecaptcha.execute('%s', {action: 'submit'}).then(function(token) {
    const recaptchaResponse = document.getElementById('%s');
    recaptchaResponse.value = token;
  });
});

EOF;

    /**
     * format of reCAPTCHA v2 checkbox
     *
     * @var string
     */
    public const FMT_V2_CHECKBOX = '<div class="%s" data-sitekey="%s" %s></div>';

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
     * - scriptBlock: block name to append recaptcha script
     * - field: hidden field name for recaptcha v3 token
     * - classV2: class name for recaptcha v2
     * - useV2: use reCAPTCHA v2 ?
     *
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [
        'scriptBlock' => 'scriptBottom',
        'field' => 'recaptchaToken',
        'classV2' => 'g-recaptcha',
        'useV2' => false,
    ];

    /**
     * site key for reCAPTCHA v3
     *
     * @var string
     */
    private string $siteKeyV3 = '';

    /**
     * site key for reCAPTCHA v2
     *
     * @var string
     */
    private string $siteKeyV2 = '';

    /**
     * use reCAPTCHA v2 ?
     *
     * @var bool
     */
    private bool $useV2 = false;

    /**
     * constructor hook method
     *
     * @param array<string, mixed> $config The configuration settings provided to this helper.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->useV2 = $this->getConfig('useV2');

        $this->siteKeyV3 = Configure::read('Recaptcha.v3.site_key', '');
        $this->siteKeyV2 = Configure::read('Recaptcha.v2.site_key', '');

        if ($this->siteKeyV3 === '' && $this->siteKeyV2 === '') {
            throw new RuntimeException('Recaptcha site key is not set.');
        }

        if ($this->siteKeyV3 === '' && $this->siteKeyV2 !== '') {
            $this->useV2 = true;
        }
    }

    /**
     * return reCAPTCHA site key
     *
     * @return string
     */
    public function getSiteKey(): string
    {
        if ($this->useV2) {
            return $this->siteKeyV2;
        }

        return $this->siteKeyV3;
    }

    /**
     * return hidden input field for reCAPTCHA v3
     *
     * @return string
     */
    public function hidden(): string
    {
        if ($this->useV2) {
            return '';
        }

        $field = $this->getConfig('field');

        return $this->Form->hidden($field, [
            'id' => $field,
            'value' => '',
        ]);
    }

    /**
     * return checkbox for reCAPTCHA v2
     *
     * Additional attributes example:
     * ```
     * echo $this->Recaptcha->checkbox('data-theme="dark" data-size="compact"');
     * ```
     *
     * @link https://developers.google.com/recaptcha/docs/display#render_param g-recaptcha tag attributes
     * @param string $attr Additional attributes
     * @return string
     */
    public function checkbox(string $attr = ''): string
    {
        if (!$this->useV2) {
            return '';
        }

        $class = $this->getConfig('classV2');

        return sprintf(self::FMT_V2_CHECKBOX, $class, $this->siteKeyV2, $attr);
    }

    /**
     * append reCAPTCHA script tag & block to a specific block
     *
     * @param \Cake\Event\EventInterface $event
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

        if ($this->useV2) {
            $src = self::API_URL;
        } else {
            $src = self::API_URL . '?render=' . $this->siteKeyV3;
        }
        $this->Html->script($src, ['block' => $block]);
    }

    /**
     * append reCAPTCHA script block to a specific block
     *
     * @return void
     */
    private function scriptBlock(): void
    {
        if ($this->useV2) {
            return;
        }

        $block = $this->getConfig('scriptBlock');
        $field = $this->getConfig('field');
        $script = sprintf(self::FMT_V3_SCRIPT_BLOCK, $this->siteKeyV3, $field);
        $this->Html->scriptBlock($script, ['block' => $block]);
    }
}

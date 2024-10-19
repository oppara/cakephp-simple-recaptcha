<?php
declare(strict_types=1);

namespace Oppara\SimpleRecaptcha\Controller\Component;

use Cake\Controller\Component;
use Cake\Core\Configure;
use Cake\Event\EventInterface;
use Cake\Http\Client;
use Cake\Http\ServerRequest;
use Oppara\SimpleRecaptcha\Exception\RecaptchaV3Exception;
use RuntimeException;

/**
 * Recaptcha component
 *
 * @psalm-api
 */
class RecaptchaComponent extends Component
{
    /**
     * Default configuration.
     *
     * - actions: contoroller's actions that use reCAPTCHA
     * - score: minimum score to pass reCAPTCHA
     * - scriptBlock: script block name to insert reCAPTCHA script
     * - field: hidden field name for recaptcha v3 token
     * - classV2: class name for recaptcha v2
     *
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [
        'actions' => ['index'],
        'score' => 0.5,
        'scriptBlock' => 'scriptBottom',
        'field' => 'recaptchaToken',
        'classV2' => 'g-recaptcha',
    ];

    /**
     * Secret key for reCAPTCHA v3
     *
     * @var string
     */
    private string $secretKeyV3 = '';

    /**
     * site key for reCAPTCHA v2
     *
     * @var string
     */
    private string $secretKeyV2 = '';

    /**
     * Verification result of reCAPTCHA.
     *
     * @var array<string, mixed>
     */
    private array $result = [];

    /**
     * session key for reCAPTCHA
     *
     * @var string
     */
    private string $sessKey = 'Recaptcha.hasV3Error';

    /**
     * Constructor hook method.
     *
     * @param array<string, mixed> $config The configuration settings provided to this helper.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->secretKeyV3 = Configure::read('Recaptcha.v3.secret_key', '');
        if (empty($this->secretKeyV3)) {
            throw new RuntimeException('Recaptcha v3 secret key is not set.');
        }

        $this->secretKeyV2 = Configure::read('Recaptcha.v2.secret_key', '');
    }

    /**
     * Add reCAPTCHA helper.
     *
     * @param \Cake\Event\EventInterface $event
     */
    public function startUp(EventInterface $event): void
    {
        if (!$this->canUse()) {
            return;
        }

        $controller = $event->getSubject();
        $controller->viewBuilder()->addHelper('Oppara/SimpleRecaptcha.Recaptcha', [
            'scriptBlock' => $this->getConfig('scriptBlock'),
            'field' => $this->getConfig('field'),
            'classV2' => $this->getConfig('classV2'),
            'useV2' => $this->useV2(),
        ]);
    }

    /**
     * Can I use reCAPTCHA v2 ?
     *
     * @return bool
     */
    public function useV2(): bool
    {
        if ($this->hasV3Error()) {
            return true;
        }

        if ($this->getV2Token() !== '') {
            return true;
        }

        return false;
    }

    /**
     * Verify reCAPTCHA.
     *
     * @return bool
     * @throws \Oppara\SimpleRecaptcha\Exception\RecaptchaV3Exception
     * @throws \Cake\Http\Client\Exception\NetworkException Cake\Http\Client Exception
     * @throws \Cake\Http\Client\Exception\RequestException Cake\Http\Client Exception
     */
    public function verify(): bool
    {
        if ($this->useV2()) {
            return $this->verifyRecaptchaV2();
        }

        return $this->verifyRecaptchaV3();
    }

    /**
     * Verify reCAPTCHA v3.
     *
     * @return bool
     */
    public function verifyRecaptchaV3(): bool
    {
        $token = $this->getV3Token();
        $tmp = $this->verifyRecaptcha($this->secretKeyV3, $token);
        if ($tmp['success'] && $tmp['score'] >= $this->getConfig('score')) {
            return true;
        }

        if ($this->secretKeyV2 !== '') {
            $this->saveV3Error();
            throw new RecaptchaV3Exception(json_encode($this->result));
        }

        return false;
    }

    /**
     * Verify reCAPTCHA v2.
     *
     * @return bool
     */
    public function verifyRecaptchaV2(): bool
    {
        $token = $this->getV2Token();
        $tmp = $this->verifyRecaptcha($this->secretKeyV2, $token);
        if ($tmp['success']) {
            return true;
        }

        return false;
    }

    /**
     * Getting a response from reCAPTCHA
     *
     * @param string $secret
     * @param string $token
     * @return array<string, mixed>
     * @throws \Cake\Http\Client\Exception\NetworkException|\Cake\Http\Client\Exception\RequestException
     */
    public function verifyRecaptcha(string $secret, string $token): array
    {
        $http = new Client();
        $response = $http->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret' => $secret,
            'response' => $token,
        ]);

        if ($response->isOk()) {
            $this->result = json_decode($response->getBody()->getContents(), true);
        } else {
            $this->result = [
                'success' => false,
                'score' => 0,
                'status' => $response->getStatusCode(),
            ];
        }

        return $this->result;
    }

    /**
     * Gets the token of reCAPTCHA v3
     *
     * @return string
     */
    public function getV3Token(): string
    {
        $data = $this->getRequest()->getData();
        $key = $this->getConfig('field');

        return $data[$key] ?? '';
    }

    /**
     * Gets the token of reCAPTCHA v2
     *
     * @return string
     */
    public function getV2Token(): string
    {
        $data = $this->getRequest()->getData();
        $key = $this->getConfig('classV2') . '-response';

        return $data[$key] ?? '';
    }

    /**
     * Gets the verification result of reCAPTCHA.
     *
     * @return array<string, mixed>
     */
    public function getResult(): array
    {
        return $this->result;
    }

    /**
     * Save recaptcha v3 error in session.
     */
    private function saveV3Error(): void
    {
        $this->getRequest()->getSession()->write($this->sessKey, true);
    }

    /**
     * Has recaptcha v3 error in session?
     */
    private function hasV3Error(): bool
    {
        return !! $this->getRequest()->getSession()->consume($this->sessKey);
    }

    /**
     * Can I use the reCAPTCHA?
     *
     * @return bool
     */
    private function canUse(): bool
    {
        $action = $this->getRequest()->getParam('action');
        if (in_array($action, $this->getConfig('actions'), true)) {
            return true;
        }

        return false;
    }

    /**
     * Gets the request instance.
     *
     * @return \Cake\Http\ServerRequest
     */
    private function getRequest(): ServerRequest
    {
        return $this->getController()->getRequest();
    }
}

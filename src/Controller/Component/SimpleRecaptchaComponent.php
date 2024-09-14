<?php
declare(strict_types=1);

namespace Oppara\SimpleRecaptcha\Controller\Component;

use Cake\Controller\Component;
use Cake\Core\Configure;
use Cake\Event\EventInterface;
use Cake\Http\Client;
use RuntimeException;

/**
 * SimpleRecaptcha component
 *
 * @psalm-api
 */
class SimpleRecaptchaComponent extends Component
{
    /**
     * Default configuration.
     *
     * - actions: actions to use reCAPTCHA
     * - field: hidden field name for recaptcha token
     * - score: minimum score to pass reCAPTCHA
     *
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [
        'actions' => ['index'],
        'field' => 'recaptchaToken',
        'score' => 0.5,
    ];

    /**
     * Secret key for reCAPTCHA.
     *
     * @var string
     */
    private string $secretKey = '';

    /**
     * Verification result of reCAPTCHA.
     *
     * @var array<string, mixed>
     */
    private array $result = [];

    /**
     * Constructor hook method.
     *
     * @param array<string, mixed> $config The configuration settings provided to this helper.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->secretKey = Configure::read('Recaptcha.v3.secret_key', '');

        if (empty($this->secretKey)) {
            throw new RuntimeException('Recaptcha secret key is not set.');
        }
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
        $controller->viewBuilder()->addHelper('Oppara/SimpleRecaptcha.SimpleRecaptcha', [
            'field' => $this->getConfig('field'),
        ]);
    }

    /**
     * Verify reCAPTCHA.
     *
     * @throws \Cake\Http\Client\Exception\NetworkException|\Cake\Http\Client\Exception\RequestException
     * @return bool
     */
    public function verify(): bool
    {
        $token = $this->getToken();
        $tmp = $this->verifyRecaptcha($token);
        if ($tmp['success'] && $tmp['score'] >= $this->getConfig('score')) {
            return true;
        }

        return false;
    }

    /**
     * Getting a response from reCAPTCHA
     *
     * @param string $token
     * @return array<string, mixed>
     */
    public function verifyRecaptcha(string $token): array
    {
        $http = new Client();
        $response = $http->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret' => $this->secretKey,
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
     * Get token of reCAPTCHA.
     *
     * @return string
     */
    public function getToken(): string
    {
        $controller = $this->getController();
        $data = $controller->getRequest()->getData();

        return $data[$this->getConfig('field')] ?? '';
    }

    /**
     * Get verification result of reCAPTCHA.
     *
     * @return array<string, mixed>
     */
    public function getResult(): array
    {
        return $this->result;
    }

    /**
     * Can I use the reCAPTCHA?
     *
     * @return bool
     */
    private function canUse(): bool
    {
        $controller = $this->getController();
        $action = $controller->getRequest()->getParam('action');
        if (in_array($action, $this->getConfig('actions'), true)) {
            return true;
        }

        return false;
    }
}

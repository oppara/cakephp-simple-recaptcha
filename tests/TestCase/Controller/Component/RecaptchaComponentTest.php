<?php
declare(strict_types=1);

namespace Oppara\SimpleRecaptcha\Test\TestCase\Controller\Component;

use Cake\Controller\ComponentRegistry;
use Cake\Controller\Controller;
use Cake\Event\Event;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;
use Oppara\SimpleRecaptcha\Controller\Component\RecaptchaComponent;

/**
 * SimpleRecaptcha\Controller\Component\RecaptchaComponent Test Case
 *
 * @group component
 */
class RecaptchaComponentTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \Oppara\SimpleRecaptcha\Controller\Component\RecaptchaComponent
     */
    protected $Recaptcha;

    /**
     * Controller
     *
     * @var \Cake\Controller\Controller
     */
    protected $Controller;

    /**
     * config
     *
     * @var array<string, mixed>
     */
    private $config = [
            'actions' => ['index', 'confirm'],
            'field' => 'fooToken',
            'score' => 0.7,
        ];

    /**
     * setUp method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        static::setAppNamespace();

        $request = new ServerRequest();
        $this->Controller = new Controller($request);
        $this->Recaptcha = new RecaptchaComponent($this->Controller->components());
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->Recaptcha);

        parent::tearDown();
    }

    public function startUp(RecaptchaComponent $component, string $action, string $token = 'hogehoge'): void
    {
        $field = $component->getConfig('field');
        $request = $this->Controller->getRequest()
            ->withAttribute('params', [
                'controller' => 'Foo',
                'action' => $action,
            ])
            ->withData($field, $token);

        $this->Controller->setRequest($request);
        $component->startUp(new Event('Controller.startup', $this->Controller));
    }

    public function testLoadedHelper(): void
    {
        $this->startUp($this->Recaptcha, 'index');
        $helpers = $this->Controller->viewBuilder()->getHelpers();
        $this->assertTrue(array_key_exists('Recaptcha', $helpers));

        $field = $this->Recaptcha->getConfig('field');
        $this->assertSame($field, $helpers['Recaptcha']['field']);
    }

    public function testNotLoadedHelper(): void
    {
        $this->startUp($this->Recaptcha, 'other');
        $helpers = $this->Controller->viewBuilder()->getHelpers();
        $this->assertFalse(array_key_exists('Recaptcha', $helpers));
    }

    public function testLoadedHelperWithConfig(): void
    {
        $Recaptcha = new RecaptchaComponent($this->Controller->components(), $this->config);
        $this->startUp($Recaptcha, 'confirm');

        $helpers = $this->Controller->viewBuilder()->getHelpers();
        $this->assertTrue(array_key_exists('Recaptcha', $helpers));

        $field = $Recaptcha->getConfig('field');
        $this->assertSame($field, $helpers['Recaptcha']['field']);
    }

    public function testGetToken(): void
    {
        $token = 'bar';
        $this->startUp($this->Recaptcha, 'index', $token);
        $this->assertSame($token, $this->Recaptcha->getToken());
    }

    /**
     * @param array<string, mixed> $return
     */
    public function createVerifyMock(array $return): RecaptchaComponent
    {
        $mock = $this->getMockBuilder(RecaptchaComponent::class)
           ->onlyMethods(['verifyRecaptcha'])
            ->setConstructorArgs([
                new ComponentRegistry($this->Controller),
            ])
           ->getMock();

        $mock->expects($this->once())
            ->method('verifyRecaptcha')
            ->willReturn($return);

        return $mock;
    }

    /**
     * @dataProvider verifyProvider
     * @param bool $expected
     * @param array<string, mixed> $args
     */
    public function testVerify(bool $expected, array $args): void
    {
        $mock = $this->createVerifyMock($args);
        $this->assertSame($expected, $mock->verify());
    }

    /**
     * @return array<mixed>
     */
    public static function verifyProvider(): array
    {
        return [
           [true, ['success' => true, 'score' => 0.7]],
           [true, ['success' => true, 'score' => 0.5]],
           [false, ['success' => true, 'score' => 0.4]],
           [false, ['success' => false, 'score' => 0.7]],
        ];
    }
}
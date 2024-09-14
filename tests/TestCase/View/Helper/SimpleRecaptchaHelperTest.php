<?php
declare(strict_types=1);

namespace Oppara\SimpleRecaptcha\Test\TestCase\View\Helper;

use Cake\Http\ServerRequest;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;
use Cake\View\View;
use Oppara\SimpleRecaptcha\View\Helper\SimpleRecaptchaHelper;

/**
 * SimpleRecaptcha\View\Helper\SimpleRecaptchaHelper Test Case
 *
 * @group helper
 */
class SimpleRecaptchaHelperTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \Oppara\SimpleRecaptcha\View\Helper\SimpleRecaptchaHelper
     */
    protected $SimpleRecaptcha;

    /**
     * Mocked view
     *
     * @var \Cake\View\View|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $View;

    /**
     * setUp method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $request = new ServerRequest([
            'webroot' => '',
        ]);
        Router::reload();
        Router::setRequest($request);

        $this->View = $this->getMockBuilder(View::class)
            ->onlyMethods(['append'])
            ->setConstructorArgs([$request])
            ->getMock();
        $this->SimpleRecaptcha = new SimpleRecaptchaHelper($this->View);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->SimpleRecaptcha);

        parent::tearDown();
    }

    public function testHidden(): void
    {
        $name = $this->SimpleRecaptcha->getConfig('field');
        $this->assertHidden($this->SimpleRecaptcha, $name);
    }

    public function testHiddenWithConfig(): void
    {
        $name = 'foo';
        $config = [
            'field' => $name,
        ];
        $SimpleRecaptcha = new SimpleRecaptchaHelper($this->View, $config);

        $this->assertHidden($SimpleRecaptcha, $name);
    }

    public function assertHidden(SimpleRecaptchaHelper $helper, string $name): void
    {
        $expected = [ 'input' => [
            'type' => 'hidden',
            'name' => $name,
            'id' => $name,
            'value' => '',
        ]];
        $this->assertHtml($expected, $helper->hidden());
    }

    public function testAfterRender(): void
    {
        $key = $this->SimpleRecaptcha->getSiteKey();
        $expected = sprintf(SimpleRecaptchaHelper::FMT_SCRIPT, $key);

        $field = $this->SimpleRecaptcha->getConfig('field');
        $pattern = $this->makePattern($key, $field);

        $block = $this->SimpleRecaptcha->getConfig('scriptBlock');
        $this->View->expects($this->exactly(2))
           ->method('append')
           ->with(
               ...self::withConsecutive(
                   [$block, $this->stringContains($expected)],
                   [$block, $this->matchesRegularExpression($pattern)]
               )
           );

        $this->SimpleRecaptcha->afterRender();
    }

    public function testAfterRenderWithConfig(): void
    {
        $block = 'foo';
        $field = 'bar';
        $config = [
            'scriptBlock' => $block,
            'field' => $field,
        ];
        $SimpleRecaptcha = new SimpleRecaptchaHelper($this->View, $config);

        $key = $SimpleRecaptcha->getSiteKey();
        $expected = sprintf(SimpleRecaptchaHelper::FMT_SCRIPT, $key);

        $field = $SimpleRecaptcha->getConfig('field');
        $pattern = $this->makePattern($key, $field);

        $block = $SimpleRecaptcha->getConfig('scriptBlock');
        $this->View->expects($this->exactly(2))
           ->method('append')
           ->with(
               ...self::withConsecutive(
                   [$block, $this->stringContains($expected)],
                   [$block, $this->matchesRegularExpression($pattern)]
               )
           );

        $SimpleRecaptcha->afterRender();
    }

    public function makePattern(string $key, string $field): string
    {
        $fmt = "/grecaptcha.execute\('%s', {action: 'submit'}\).+document.getElementById\('%s'\)/ms";

        return sprintf($fmt, $key, $field);
    }
}

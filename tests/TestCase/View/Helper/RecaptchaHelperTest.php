<?php
declare(strict_types=1);

namespace Oppara\SimpleRecaptcha\Test\TestCase\View\Helper;

use Cake\Http\ServerRequest;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;
use Cake\View\View;
use Oppara\SimpleRecaptcha\View\Helper\RecaptchaHelper;

/**
 * SimpleRecaptcha\View\Helper\RecaptchaHelper Test Case
 *
 * @group helper
 */
class RecaptchaHelperTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \Oppara\SimpleRecaptcha\View\Helper\RecaptchaHelper
     */
    protected $Helper;

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
        $this->Helper = new RecaptchaHelper($this->View);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->Helper);
        unset($this->View);

        parent::tearDown();
    }

    public function createV2Helper(array $config = []): RecaptchaHelper
    {
        $config += [
            'useV2' => true,
        ];

        return new RecaptchaHelper($this->View, $config);
    }

    public function testHidden(): void
    {
        $name = $this->Helper->getConfig('field');
        $this->assertHidden($this->Helper, $name);
    }

    public function testHiddenWithConfig(): void
    {
        $name = 'foo';
        $config = [
            'field' => $name,
        ];
        $Helper = new RecaptchaHelper($this->View, $config);

        $this->assertHidden($Helper, $name);
    }

    public function assertHidden(RecaptchaHelper $helper, string $name): void
    {
        $expected = [ 'input' => [
            'type' => 'hidden',
            'name' => $name,
            'id' => $name,
            'value' => '',
        ]];
        $this->assertHtml($expected, $helper->hidden());
    }

    public function testCheckbox(): void
    {
        $this->assertSame('', $this->Helper->checkbox());
    }

    public function testAfterRender(): void
    {
        $key = $this->Helper->getSiteKey();
        $expected = RecaptchaHelper::API_URL;

        $field = $this->Helper->getConfig('field');
        $pattern = $this->makeV3Pattern($key, $field);

        $block = $this->Helper->getConfig('scriptBlock');
        $this->View->expects($this->exactly(2))
           ->method('append')
           ->with(
               ...self::withConsecutive(
                   [$block, $this->stringContains($expected)],
                   [$block, $this->matchesRegularExpression($pattern)]
               )
           );

        $this->Helper->afterRender();
    }

    public function testAfterRenderWithConfig(): void
    {
        $block = 'foo';
        $field = 'bar';
        $config = [
            'scriptBlock' => $block,
            'field' => $field,
        ];
        $SimpleRecaptcha = new RecaptchaHelper($this->View, $config);

        $key = $SimpleRecaptcha->getSiteKey();
        $expected = RecaptchaHelper::API_URL;

        $field = $SimpleRecaptcha->getConfig('field');
        $pattern = $this->makeV3Pattern($key, $field);

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

    public function makeV3Pattern(string $key, string $field): string
    {
        $fmt = "/grecaptcha.execute\('%s', {action: 'submit'}\).+document.getElementById\('%s'\)/ms";

        return sprintf($fmt, $key, $field);
    }

    public function testHiddenUseV2(): void
    {
        $Helper = $this->createV2Helper();

        $this->assertSame('', $Helper->hidden());
    }

    public function testCheckboxUseV2(): void
    {
        $Helper = $this->createV2Helper();
        $key = $Helper->getSiteKey();
        $class = $Helper->getConfig('classV2');
        $expected = sprintf(RecaptchaHelper::FMT_V2_CHECKBOX, $class, $key, '');
        $this->assertSame($expected, $Helper->checkbox());
    }

    public function testCheckboxUseV2WithConfig(): void
    {
        $class = 'foo';
        $Helper = $this->createV2Helper([
            'classV2' => $class,
        ]);
        $key = $Helper->getSiteKey();
        $expected = sprintf(RecaptchaHelper::FMT_V2_CHECKBOX, $class, $key, '');
        $this->assertSame($expected, $Helper->checkbox());
    }

    public function testCheckboxUseV2WitAttribute(): void
    {
        $attr = 'data-foo="bar"';
        $Helper = $this->createV2Helper();
        $key = $Helper->getSiteKey();
        $class = $Helper->getConfig('classV2');
        $expected = sprintf(RecaptchaHelper::FMT_V2_CHECKBOX, $class, $key, $attr);
        $this->assertSame($expected, $Helper->checkbox($attr));
    }

    public function testAfterRenderUseV2(): void
    {
        $Helper = $this->createV2Helper();
        $expected = RecaptchaHelper::API_URL;

        $block = $Helper->getConfig('scriptBlock');
        $this->View->expects($this->exactly(1))
           ->method('append')
           ->with(
               ...self::withConsecutive(
                   [$block, $this->stringContains($expected)],
               )
           );

        $Helper->afterRender();
    }
}

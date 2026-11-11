<?php

declare(strict_types=1);

namespace Denosys\View\Tests;

use Denosys\View\Engines\TemplateEngine;
use Denosys\View\ViewEngine;
use PHPUnit\Framework\TestCase;

final class TemplateCompilationTest extends TestCase
{
    private string $views;
    private string $cache;

    protected function setUp(): void
    {
        $this->views = __DIR__ . '/fixtures/views';
        $this->cache = sys_get_temp_dir() . '/denosys-view-test-' . bin2hex(random_bytes(4));
    }

    protected function tearDown(): void
    {
        foreach (glob($this->cache . '/*') ?: [] as $file) {
            unlink($file);
        }
        if (is_dir($this->cache)) {
            rmdir($this->cache);
        }
    }

    public function testTemplateEngineCompilesAndEscapesWithoutACacheDirectory(): void
    {
        $engine = new TemplateEngine([$this->views]);

        self::assertSame('<b>&lt;i&gt;x&lt;/i&gt;</b>', $engine->render('hello', ['name' => '<i>x</i>']));
    }

    public function testDefaultCacheDirectoryIsPrivateToTheCurrentUser(): void
    {
        $engine = new TemplateEngine([$this->views]);
        $engine->render('hello', ['name' => 'x']);

        $directory = TemplateEngine::defaultCacheDirectory();
        self::assertDirectoryExists($directory);
        self::assertSame(0, fileperms($directory) & 0077, 'Default cache directory must not be group or world accessible.');
    }

    public function testDefaultCacheDirectoryIsRefusedWhenOthersCanWriteToIt(): void
    {
        $directory = TemplateEngine::defaultCacheDirectory();
        if (!is_dir($directory)) {
            mkdir($directory, 0700, true);
        }
        $original = fileperms($directory) & 0777;
        chmod($directory, 0777);

        try {
            $this->expectException(\RuntimeException::class);
            (new TemplateEngine([$this->views]))->render('hello', ['name' => 'x']);
        } finally {
            chmod($directory, $original);
        }
    }

    public function testTemplateEngineWithoutACacheDirectoryNeverWritesBesideTheTemplate(): void
    {
        $before = file_get_contents($this->views . '/hello.php');

        (new TemplateEngine([$this->views]))->render('hello', ['name' => 'x']);

        self::assertSame($before, file_get_contents($this->views . '/hello.php'));
    }

    public function testViewEngineCompilesDirectivesAndEscapesOutput(): void
    {
        $view = new ViewEngine([$this->views]);
        $view->enableCache($this->cache);

        self::assertSame('<b>&lt;i&gt;x&lt;/i&gt;</b>', $view->render('hello', ['name' => '<i>x</i>']));
    }

    public function testViewEngineRendersLayoutsAndSections(): void
    {
        $view = new ViewEngine([$this->views]);

        self::assertSame('<main>Child &lt;b&gt;</main>', trim($view->render('child', ['name' => '<b>'])));
    }

    public function testViewEngineAppliesCustomDirectives(): void
    {
        $view = new ViewEngine([$this->views]);
        $view->directive('money', static fn (string $expression): string => "<?= number_format({$expression}, 2) ?>");

        self::assertSame('1,234.50', $view->render('money', ['amount' => 1234.5]));
    }

    public function testViewEngineFindsExistingTemplates(): void
    {
        $view = new ViewEngine([$this->views]);

        self::assertTrue($view->exists('hello'));
        self::assertFalse($view->exists('missing'));
    }

    public function testPlainPhpTemplatesStillRenderWithThePhpEngine(): void
    {
        $view = new ViewEngine([$this->views]);

        self::assertSame('Plain &lt;x&gt;', $view->render('plain', ['name' => '<x>']));
    }
}

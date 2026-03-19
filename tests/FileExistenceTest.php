<?php

declare(strict_types=1);

namespace Detain\MyAdminLiteSpeed\Tests;

use PHPUnit\Framework\TestCase;

/**
 * Test suite verifying that all expected source files exist.
 */
class FileExistenceTest extends TestCase
{
    /**
     * @var string
     */
    private string $basePath;

    protected function setUp(): void
    {
        $this->basePath = dirname(__DIR__);
    }

    /**
     * Test that Plugin.php exists in src directory.
     */
    public function testPluginFileExists(): void
    {
        self::assertFileExists($this->basePath . '/src/Plugin.php');
    }

    /**
     * Test that litespeed.inc.php exists in src directory.
     */
    public function testLitespeedIncFileExists(): void
    {
        self::assertFileExists($this->basePath . '/src/litespeed.inc.php');
    }

    /**
     * Test that litespeed_list.php exists in src directory.
     */
    public function testLitespeedListFileExists(): void
    {
        self::assertFileExists($this->basePath . '/src/litespeed_list.php');
    }

    /**
     * Test that composer.json exists.
     */
    public function testComposerJsonExists(): void
    {
        self::assertFileExists($this->basePath . '/composer.json');
    }

    /**
     * Test that README.md exists.
     */
    public function testReadmeExists(): void
    {
        self::assertFileExists($this->basePath . '/README.md');
    }

    /**
     * Test that Plugin.php contains the correct namespace declaration.
     */
    public function testPluginFileHasCorrectNamespace(): void
    {
        $content = file_get_contents($this->basePath . '/src/Plugin.php');
        self::assertStringContainsString('namespace Detain\\MyAdminLiteSpeed;', $content);
    }

    /**
     * Test that Plugin.php declares the Plugin class.
     */
    public function testPluginFileDeclaresClass(): void
    {
        $content = file_get_contents($this->basePath . '/src/Plugin.php');
        self::assertStringContainsString('class Plugin', $content);
    }

    /**
     * Test that litespeed.inc.php declares the activate_litespeed function.
     */
    public function testLitespeedIncDeclaresActivateFunction(): void
    {
        $content = file_get_contents($this->basePath . '/src/litespeed.inc.php');
        self::assertStringContainsString('function activate_litespeed(', $content);
    }

    /**
     * Test that litespeed.inc.php declares the deactivate_litespeed function.
     */
    public function testLitespeedIncDeclaresDeactivateFunction(): void
    {
        $content = file_get_contents($this->basePath . '/src/litespeed.inc.php');
        self::assertStringContainsString('function deactivate_litespeed(', $content);
    }

    /**
     * Test that litespeed.inc.php declares the activate_litespeed_new function.
     */
    public function testLitespeedIncDeclaresActivateNewFunction(): void
    {
        $content = file_get_contents($this->basePath . '/src/litespeed.inc.php');
        self::assertStringContainsString('function activate_litespeed_new(', $content);
    }

    /**
     * Test that litespeed.inc.php declares the deactivate_litespeed_new function.
     */
    public function testLitespeedIncDeclaresDeactivateNewFunction(): void
    {
        $content = file_get_contents($this->basePath . '/src/litespeed.inc.php');
        self::assertStringContainsString('function deactivate_litespeed_new(', $content);
    }

    /**
     * Test that litespeed_list.php declares the litespeed_list function.
     */
    public function testLitespeedListDeclaresFunction(): void
    {
        $content = file_get_contents($this->basePath . '/src/litespeed_list.php');
        self::assertStringContainsString('function litespeed_list(', $content);
    }

    /**
     * Test that all PHP source files use proper PHP opening tag.
     */
    public function testAllFilesHavePhpOpeningTag(): void
    {
        $files = [
            '/src/Plugin.php',
            '/src/litespeed.inc.php',
            '/src/litespeed_list.php',
        ];
        foreach ($files as $file) {
            $content = file_get_contents($this->basePath . $file);
            self::assertStringStartsWith('<?php', $content, "File {$file} should start with <?php");
        }
    }
}

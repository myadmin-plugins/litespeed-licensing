<?php

declare(strict_types=1);

namespace Detain\MyAdminLiteSpeed\Tests;

use PHPUnit\Framework\TestCase;
use ReflectionFunction;

/**
 * Test suite for the litespeed_list function structure.
 */
class LitespeedListTest extends TestCase
{
    /**
     * Ensure the list file is loaded for reflection.
     */
    public static function setUpBeforeClass(): void
    {
        // We only need to verify the function is defined in the file,
        // we don't actually load it since it depends on global functions.
    }

    /**
     * Test that litespeed_list.php file exists and contains the function definition.
     */
    public function testLitespeedListFunctionDefinedInFile(): void
    {
        $content = file_get_contents(dirname(__DIR__) . '/src/litespeed_list.php');
        self::assertStringContainsString('function litespeed_list()', $content);
    }

    /**
     * Test that litespeed_list function uses get_module_settings.
     */
    public function testLitespeedListUsesGetModuleSettings(): void
    {
        $content = file_get_contents(dirname(__DIR__) . '/src/litespeed_list.php');
        self::assertStringContainsString("get_module_settings('licenses')", $content);
    }

    /**
     * Test that litespeed_list function creates TFTable instances.
     */
    public function testLitespeedListCreatesTfTable(): void
    {
        $content = file_get_contents(dirname(__DIR__) . '/src/litespeed_list.php');
        self::assertStringContainsString('new \\TFTable()', $content);
    }

    /**
     * Test that litespeed_list function uses add_output.
     */
    public function testLitespeedListUsesAddOutput(): void
    {
        $content = file_get_contents(dirname(__DIR__) . '/src/litespeed_list.php');
        self::assertStringContainsString('add_output(', $content);
    }

    /**
     * Test that litespeed_list function references LiteSpeed image.
     */
    public function testLitespeedListReferencesImage(): void
    {
        $content = file_get_contents(dirname(__DIR__) . '/src/litespeed_list.php');
        self::assertStringContainsString('litespeed.gif', $content);
    }

    /**
     * Test that litespeed_list function has no parameters.
     */
    public function testLitespeedListHasNoParams(): void
    {
        $content = file_get_contents(dirname(__DIR__) . '/src/litespeed_list.php');
        // The function signature should be: function litespeed_list()
        self::assertMatchesRegularExpression('/function\s+litespeed_list\s*\(\s*\)/', $content);
    }

    /**
     * Test that litespeed_list has proper docblock.
     */
    public function testLitespeedListHasDocblock(): void
    {
        $content = file_get_contents(dirname(__DIR__) . '/src/litespeed_list.php');
        // Check for docblock before function
        self::assertStringContainsString('@return void', $content);
    }

    /**
     * Test that litespeed_list mentions LiteSpeed advantages content.
     */
    public function testLitespeedListContainsAdvantagesSection(): void
    {
        $content = file_get_contents(dirname(__DIR__) . '/src/litespeed_list.php');
        self::assertStringContainsString('LiteSpeed Advantages', $content);
    }
}

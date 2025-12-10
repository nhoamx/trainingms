<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class LocalizationTest extends TestCase
{
    /**
     * Test that Spanish translation file exists and is valid JSON.
     */
    public function test_spanish_translation_file_exists(): void
    {
        $path = lang_path('es.json');

        $this->assertFileExists($path);

        $content = File::get($path);
        $translations = json_decode($content, true);

        $this->assertNotNull($translations, 'Spanish translation file is not valid JSON');
        $this->assertIsArray($translations);
        $this->assertNotEmpty($translations);
    }

    /**
     * Test that English translation file exists and is valid JSON.
     */
    public function test_english_translation_file_exists(): void
    {
        $path = lang_path('en.json');

        $this->assertFileExists($path);

        $content = File::get($path);
        $translations = json_decode($content, true);

        $this->assertNotNull($translations, 'English translation file is not valid JSON');
        $this->assertIsArray($translations);
        $this->assertNotEmpty($translations);
    }

    /**
     * Test that both language files have the same keys.
     */
    public function test_translation_files_have_matching_keys(): void
    {
        $esPath = lang_path('es.json');
        $enPath = lang_path('en.json');

        $esTranslations = json_decode(File::get($esPath), true);
        $enTranslations = json_decode(File::get($enPath), true);

        $esKeys = array_keys($esTranslations);
        $enKeys = array_keys($enTranslations);

        sort($esKeys);
        sort($enKeys);

        $this->assertEquals($esKeys, $enKeys, 'Translation files have different keys');
    }

    /**
     * Test that SetLocale middleware sets locale from query parameter.
     */
    public function test_locale_can_be_set_via_query_parameter(): void
    {
        $this->get('/?lang=en');

        $this->assertEquals('en', App::getLocale());
    }

    /**
     * Test that default locale in config is Spanish.
     */
    public function test_default_locale_config_is_spanish(): void
    {
        // The config default is 'es', but .env may override it
        // We test that the config file has the correct default
        $configContent = file_get_contents(config_path('app.php'));

        $this->assertStringContainsString("'locale' => env('APP_LOCALE', 'es')", $configContent);
    }

    /**
     * Test that SetLocale middleware exists.
     */
    public function test_set_locale_middleware_exists(): void
    {
        $this->assertFileExists(app_path('Http/Middleware/SetLocale.php'));
    }

    /**
     * Test that translations composable file exists.
     */
    public function test_translations_composable_exists(): void
    {
        $this->assertFileExists(resource_path('js/Composables/useTranslations.ts'));
    }

    /**
     * Test that language switcher component exists.
     */
    public function test_language_switcher_component_exists(): void
    {
        $this->assertFileExists(resource_path('js/Components/LanguageSwitcher.vue'));
    }
}

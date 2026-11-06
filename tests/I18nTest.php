<?php
use PHPUnit\Framework\TestCase,
    Paliari\I18n;

/**
 * Class I18nTest
 */
class I18nTest extends TestCase
{

    /**
     * @var I18n
     */
    protected $i18n;

    /**
     * @var string
     */
    protected $cache_path;

    protected function setUp(): void
    {
        (new ReflectionProperty(I18n::class, '_instance'))->setValue(null, null);
        $this->cache_path = sys_get_temp_dir() . '/php-i18n-test-' . uniqid();
        $this->i18n       = I18n::instance();
        $this->i18n->setCachePath($this->cache_path);
    }

    protected function tearDown(): void
    {
        foreach (glob("$this->cache_path/*") ?: [] as $file) {
            unlink($file);
        }
        if (is_dir($this->cache_path)) {
            rmdir($this->cache_path);
        }
    }

    public function testCurrentLocaleExists()
    {
        $this->assertEquals('pt-BR', $this->i18n->getCurrentLocale());
    }

    public function testSetCurrentLocale()
    {
        $this->i18n->setCurrentLocale('en');
        $this->assertEquals('en', $this->i18n->getCurrentLocale());
    }

    public function testSetCurrentLocaleToBlank()
    {
        $this->expectException(Exception::class);
        $this->i18n->setCurrentLocale('');
    }

    public function testSetLocalesPathToBlank()
    {
        $this->expectException(Exception::class);
        $this->i18n->addLocalesPath('');
    }

    public function testSetAndGetLocalesPaths()
    {
        $paths = [__DIR__ . '/locale_examples'];
        $this->i18n->setLocalesPaths($paths);
        $this->assertEquals($paths, $this->i18n->getLocalesPaths());
    }

    public function testDefaultCachePath()
    {
        (new ReflectionProperty(I18n::class, '_instance'))->setValue(null, null);
        $this->assertEquals(sys_get_temp_dir(), I18n::instance()->getCachePath());
    }

    public function testSetLocalesPathAndTranslate()
    {
        $this->i18n->addLocalesPath(__DIR__ . '/locale_examples')->setCurrentLocale('en');
        $this->assertEquals('Hello I18n', $this->i18n->hum('hello'));
        $this->assertEquals('User Name', $this->i18n->hum('user.name'));
        $this->assertEquals('Unauthorized', $this->i18n->humErrorMessage('unauthorized'));
        $this->i18n->setCurrentLocale('pt-BR');
        $this->assertEquals('Olá I18n', $this->i18n->hum('hello'));
        $this->assertEquals('Não autorizado', $this->i18n->humErrorMessage('unauthorized'));
    }

    public function testMissingKeyReturnsNull()
    {
        $this->i18n->addLocalesPath(__DIR__ . '/locale_examples');
        $this->assertNull($this->i18n->hum('not_found'));
        $this->assertNull($this->i18n->hum('user.not_found'));
        $this->assertNull($this->i18n->hum('not_found', ['name' => 'Daniel']));
        $this->assertNull($this->i18n->humErrorMessage('not_found'));
    }

    public function testKeyInsideStringReturnsNull()
    {
        $this->i18n->addLocalesPath(__DIR__ . '/locale_examples');
        $this->assertNull($this->i18n->hum('hello.sub'));
    }

    public function testLocaleWithoutFiles()
    {
        $this->i18n->addLocalesPath(__DIR__ . '/locale_examples');
        $this->assertEquals([], $this->i18n->locale('es'));
        $this->assertNull($this->i18n->setCurrentLocale('es')->hum('hello'));
    }

    public function testYamlWithoutLocaleRootKey()
    {
        $this->i18n->addLocalesPath(__DIR__ . '/locale_examples');
        $this->assertEquals([], $this->i18n->locale('fr'));
    }

    public function testReplaceParams()
    {
        $str      = 'Olá %{first_name} %{second_name}';
        $params   = ['first_name' => 'Daniel', 'second_name' => 'Fernando'];
        $expected = 'Olá Daniel Fernando';
        $this->assertEquals($expected, $this->i18n->replaceParams($str, $params));
    }

    public function testReplaceParamsWithNullAndNumbers()
    {
        $str = 'Olá %{name}, você tem %{count} mensagens';
        $this->assertEquals('Olá , você tem 3 mensagens', $this->i18n->replaceParams($str, ['name' => null, 'count' => 3]));
        $this->assertNull($this->i18n->replaceParams(null, ['name' => 'Daniel']));
    }

    public function testReplaceParamsInArray()
    {
        $messages = ['a' => 'Olá %{name}', 'b' => 'Tchau %{name}'];
        $expected = ['a' => 'Olá Daniel', 'b' => 'Tchau Daniel'];
        $this->assertEquals($expected, $this->i18n->replaceParams($messages, ['name' => 'Daniel']));
    }

    public function testSaveCacheAndLoadFromCache()
    {
        $this->i18n->addLocalesPath(__DIR__ . '/locale_examples');
        $file = "$this->cache_path/i18n-locale-en.php";
        $this->assertEquals("Saved in $file.", $this->i18n->saveCache('en'));
        $this->assertFileExists($file);

        (new ReflectionProperty(I18n::class, '_instance'))->setValue(null, null);
        $i18n = I18n::instance();
        $i18n->setCachePath($this->cache_path);
        $i18n->setCurrentLocale('en');
        $this->assertEquals('Hello I18n', $i18n->hum('hello'));

        unlink($file);
        $this->assertEquals('Unauthorized', $i18n->humErrorMessage('unauthorized'));
    }

}

<?php
use PHPUnit\Framework\TestCase,
    PHPUnit\Framework\Attributes\DataProvider,
    Paliari\I18n;

/**
 * Class I18nTest
 */
class I18nTest extends TestCase
{

    const EXAMPLES = __DIR__ . '/locale_examples';

    /**
     * @var I18n
     */
    protected $i18n;

    /**
     * @var string
     */
    protected $tmp_dir;

    /**
     * @var string
     */
    protected $cache_path;

    protected function setUp(): void
    {
        $this->tmp_dir    = sys_get_temp_dir() . '/php-i18n-test-' . uniqid();
        $this->cache_path = "$this->tmp_dir/cache";
        mkdir($this->tmp_dir);
        $this->i18n = $this->newInstance();
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->tmp_dir);
    }

    protected function newInstance(): I18n
    {
        (new ReflectionProperty(I18n::class, '_instance'))->setValue(null, null);

        return I18n::instance()->setCachePath($this->cache_path);
    }

    protected function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        chmod($dir, 0777);
        foreach (array_diff(scandir($dir), ['.', '..']) as $name) {
            $path = "$dir/$name";
            is_dir($path) ? $this->removeDir($path) : unlink($path);
        }
        rmdir($dir);
    }

    protected function writeYml(string $dir, string $name, string $content): void
    {
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        file_put_contents("$dir/$name", $content);
    }

    // Configuração

    public function testCurrentLocaleExists()
    {
        $this->assertSame('pt-BR', $this->i18n->getCurrentLocale());
    }

    public function testSetCurrentLocale()
    {
        $this->assertSame($this->i18n, $this->i18n->setCurrentLocale('en'));
        $this->assertSame('en', $this->i18n->getCurrentLocale());
    }

    public function testSetCurrentLocaleToBlank()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Locale cannot be blank');
        $this->i18n->setCurrentLocale('');
    }

    public function testSetLocalesPathToBlank()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Locales path cannot be blank');
        $this->i18n->addLocalesPath('');
    }

    public function testSetAndGetLocalesPaths()
    {
        $paths = [self::EXAMPLES];
        $this->assertSame($this->i18n, $this->i18n->setLocalesPaths($paths));
        $this->assertSame($paths, $this->i18n->getLocalesPaths());
    }

    public function testSetCachePathIsFluent()
    {
        $this->assertSame($this->i18n, $this->i18n->setCachePath($this->tmp_dir));
        $this->assertSame($this->tmp_dir, $this->i18n->getCachePath());
    }

    public function testDefaultCachePath()
    {
        (new ReflectionProperty(I18n::class, '_instance'))->setValue(null, null);
        $this->assertSame(sys_get_temp_dir(), I18n::instance()->getCachePath());
    }

    public function testInstanceIsSingleton()
    {
        $this->assertSame($this->i18n, I18n::instance());
    }

    // Tradução

    public function testSetLocalesPathAndTranslate()
    {
        $this->i18n->addLocalesPath(self::EXAMPLES)->setCurrentLocale('en');
        $this->assertSame('Hello I18n', $this->i18n->hum('hello'));
        $this->assertSame('User Name', $this->i18n->hum('user.name'));
        $this->assertSame('Unauthorized', $this->i18n->humErrorMessage('unauthorized'));
        $this->i18n->setCurrentLocale('pt-BR');
        $this->assertSame('Olá I18n', $this->i18n->hum('hello'));
        $this->assertSame('Não autorizado', $this->i18n->humErrorMessage('unauthorized'));
    }

    public function testHumWithReplacesFromYaml()
    {
        $this->i18n->addLocalesPath(self::EXAMPLES)->setCurrentLocale('en');
        $this->assertSame('Welcome Daniel', $this->i18n->hum('welcome', ['name' => 'Daniel']));
        $this->assertSame('Must have at least 8 characters', I18n::humErrorMessage('min_length', ['count' => 8]));
        $this->i18n->setCurrentLocale('pt-BR');
        $this->assertSame('Deve ter no mínimo 8 caracteres', I18n::humErrorMessage('min_length', ['count' => 8]));
    }

    public function testHumReturnsSubtree()
    {
        $this->i18n->addLocalesPath(self::EXAMPLES)->setCurrentLocale('en');
        $this->assertSame(['name' => 'User Name'], $this->i18n->hum('user'));
    }

    public function testPlaceholderWithoutReplaceIsKept()
    {
        $this->i18n->addLocalesPath(self::EXAMPLES)->setCurrentLocale('en');
        $this->assertSame('Welcome %{name}', $this->i18n->hum('welcome'));
        $this->assertSame('Welcome %{name}', $this->i18n->hum('welcome', ['other' => 'x']));
    }

    public function testMissingKeyReturnsNull()
    {
        $this->i18n->addLocalesPath(self::EXAMPLES);
        $this->assertNull($this->i18n->hum('not_found'));
        $this->assertNull($this->i18n->hum('user.not_found'));
        $this->assertNull($this->i18n->hum('not_found', ['name' => 'Daniel']));
        $this->assertNull($this->i18n->humErrorMessage('not_found'));
    }

    public function testEmptyKeyReturnsNull()
    {
        $this->i18n->addLocalesPath(self::EXAMPLES);
        $this->assertNull($this->i18n->hum(''));
    }

    public function testKeyInsideStringReturnsNull()
    {
        $this->i18n->addLocalesPath(self::EXAMPLES);
        $this->assertNull($this->i18n->hum('hello.sub'));
    }

    // replaceParams

    public function testReplaceParams()
    {
        $str      = 'Olá %{first_name} %{second_name}';
        $params   = ['first_name' => 'Daniel', 'second_name' => 'Fernando'];
        $expected = 'Olá Daniel Fernando';
        $this->assertSame($expected, $this->i18n->replaceParams($str, $params));
    }

    public function testReplaceParamsWithoutReplaces()
    {
        $this->assertSame('Olá %{name}', $this->i18n->replaceParams('Olá %{name}'));
    }

    public function testReplaceParamsWithNullAndNumbers()
    {
        $str = 'Olá %{name}, você tem %{count} mensagens';
        $this->assertSame('Olá , você tem 3 mensagens', $this->i18n->replaceParams($str, ['name' => null, 'count' => 3]));
        $this->assertNull($this->i18n->replaceParams(null, ['name' => 'Daniel']));
    }

    public function testReplaceParamsWithBoolAndFloat()
    {
        $str = '%{on}|%{off}|%{price}';
        $this->assertSame('1||9.5', $this->i18n->replaceParams($str, ['on' => true, 'off' => false, 'price' => 9.5]));
    }

    public function testReplaceParamsWithStringable()
    {
        $name = new class {
            public function __toString(): string
            {
                return 'Daniel';
            }
        };
        $this->assertSame('Olá Daniel', $this->i18n->replaceParams('Olá %{name}', ['name' => $name]));
    }

    public function testReplaceParamsIgnoresArrayAndObjectValues()
    {
        $str    = 'Olá %{name} %{list} %{obj}';
        $params = ['name' => 'Daniel', 'list' => ['a', 'b'], 'obj' => new stdClass()];
        $this->assertSame('Olá Daniel %{list} %{obj}', $this->i18n->replaceParams($str, $params));
    }

    public function testReplaceParamsInArray()
    {
        $messages = ['a' => 'Olá %{name}', 'b' => 'Tchau %{name}'];
        $expected = ['a' => 'Olá Daniel', 'b' => 'Tchau Daniel'];
        $this->assertSame($expected, $this->i18n->replaceParams($messages, ['name' => 'Daniel']));
    }

    // Arquivos de locale

    public function testMultipleLocalesPaths()
    {
        $this->i18n->addLocalesPath(self::EXAMPLES)
            ->addLocalesPath(__DIR__ . '/locale_examples_extra')
            ->setCurrentLocale('en');
        $this->assertSame('Hello I18n', $this->i18n->hum('hello'));
        $this->assertSame('Extra path', $this->i18n->hum('extra'));
    }

    public function testMultipleFilesWithSeparators()
    {
        $this->i18n->addLocalesPath(self::EXAMPLES)->setCurrentLocale('en');
        $this->assertSame('User', $this->i18n->hum('models.user'));
        $this->assertSame('Home', $this->i18n->hum('menu.home'));
        $this->assertSame('Save', $this->i18n->hum('labels.save'));
    }

    public function testFileOnlyEndingWithLocaleIsNotLoaded()
    {
        $this->i18n->addLocalesPath(self::EXAMPLES)->setCurrentLocale('en');
        $this->assertNull($this->i18n->hum('screen'));
    }

    public function testShallowMergeBetweenFiles()
    {
        $this->i18n->addLocalesPath(__DIR__ . '/locale_merge')->setCurrentLocale('en');
        $this->assertSame(['messages' => ['forbidden' => 'Forbidden']], $this->i18n->hum('errors'));
        $this->assertNull($this->i18n->humErrorMessage('unauthorized'));
    }

    public function testLocaleWithoutFiles()
    {
        $this->i18n->addLocalesPath(self::EXAMPLES);
        $this->assertSame([], $this->i18n->locale('es'));
        $this->assertNull($this->i18n->setCurrentLocale('es')->hum('hello'));
    }

    public function testYamlWithoutLocaleRootKey()
    {
        $this->i18n->addLocalesPath(self::EXAMPLES);
        $this->assertSame([], $this->i18n->locale('fr'));
    }

    public function testYamlWithScalarRoot()
    {
        $this->i18n->addLocalesPath(self::EXAMPLES);
        $this->assertSame([], $this->i18n->locale('it'));
    }

    public function testEmptyYamlFile()
    {
        $this->i18n->addLocalesPath(self::EXAMPLES);
        $this->assertSame([], $this->i18n->locale('de'));
    }

    public function testNonexistentLocalesPath()
    {
        $this->i18n->addLocalesPath("$this->tmp_dir/not_found");
        $this->assertSame([], $this->i18n->locale('en'));
    }

    public function testLocalesPathWithGlobCharacters()
    {
        $dir = "$this->tmp_dir/app [v2]*?";
        $this->writeYml($dir, 'en.yml', "en:\n  hello: Hello brackets\n");
        $this->i18n->addLocalesPath($dir)->setCurrentLocale('en');
        $this->assertSame('Hello brackets', $this->i18n->hum('hello'));
    }

    // Validação de locale

    public static function validLocales(): array
    {
        return [['en'], ['pt-BR'], ['zh_Hant'], ['es-419'], ['sr-Latn-RS']];
    }

    public static function invalidLocales(): array
    {
        return [['../x'], ['a/b'], ['*'], ['en.yml'], ['e'], ['pt BR']];
    }

    #[DataProvider('validLocales')]
    public function testValidLocales(string $locale)
    {
        $this->i18n->addLocalesPath(__DIR__ . '/locale_examples_extra')->setCurrentLocale($locale);
        $this->assertSame($locale, $this->i18n->getCurrentLocale());
        $this->assertIsArray($this->i18n->locale($locale));
    }

    #[DataProvider('invalidLocales')]
    public function testSetCurrentLocaleRejectsInvalid(string $locale)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid locale: $locale");
        $this->i18n->setCurrentLocale($locale);
    }

    #[DataProvider('invalidLocales')]
    public function testLocaleRejectsInvalid(string $locale)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->i18n->locale($locale);
    }

    #[DataProvider('invalidLocales')]
    public function testSaveCacheRejectsInvalid(string $locale)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->i18n->saveCache($locale);
    }

    public function testInvalidCurrentLocalePropertyIsRejected()
    {
        $this->i18n->current_locale = '../x';
        $this->expectException(InvalidArgumentException::class);
        $this->i18n->hum('hello');
    }

    public function testNonStringLocalesAreRejected()
    {
        try {
            $this->i18n->locale(123);
            $this->fail('Expected InvalidArgumentException');
        } catch (InvalidArgumentException $e) {
            $this->assertSame('Invalid locale: 123', $e->getMessage());
        }
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid locale: array');
        $this->i18n->setCurrentLocale(['en']);
    }

    public function testInvalidLocaleIsAlsoAnException()
    {
        $this->expectException(Exception::class);
        $this->i18n->setCurrentLocale('../x');
    }

    // Cache

    public function testReadDoesNotCreateCacheDir()
    {
        $this->i18n->addLocalesPath(self::EXAMPLES);
        $this->assertSame('Olá I18n', $this->i18n->hum('hello'));
        $this->assertDirectoryDoesNotExist($this->cache_path);
    }

    public function testSaveCacheAndLoadFromCache()
    {
        $this->i18n->addLocalesPath(self::EXAMPLES);
        $file = "$this->cache_path/i18n-locale-en.php";
        $this->assertSame("Saved in $file.", $this->i18n->saveCache('en'));
        $this->assertFileExists($file);

        $i18n = $this->newInstance()->setCurrentLocale('en');
        $this->assertSame('Hello I18n', $i18n->hum('hello'));

        unlink($file);
        $this->assertSame('Unauthorized', $i18n->humErrorMessage('unauthorized'));
    }

    public function testSaveCacheCreatesNestedDir()
    {
        $this->i18n->setCachePath("$this->tmp_dir/a/b/c")->addLocalesPath(self::EXAMPLES);
        $this->i18n->saveCache('en');
        $this->assertFileExists("$this->tmp_dir/a/b/c/i18n-locale-en.php");
    }

    public function testSaveCacheWithUtf8()
    {
        $this->i18n->addLocalesPath(self::EXAMPLES)->saveCache('pt-BR');
        $i18n = $this->newInstance();
        $this->assertSame([], $i18n->getLocalesPaths());
        $this->assertSame('Não autorizado', $i18n->humErrorMessage('unauthorized'));
        $this->assertSame('Deve ter no mínimo 3 caracteres', $i18n->humErrorMessage('min_length', ['count' => 3]));
    }

    public function testCacheHasPriorityOverYaml()
    {
        $dir = "$this->tmp_dir/locales";
        $this->writeYml($dir, 'en.yml', "en:\n  hello: Old\n");
        $this->i18n->addLocalesPath($dir)->saveCache('en');
        $this->writeYml($dir, 'en.yml', "en:\n  hello: New\n");

        $i18n = $this->newInstance()->addLocalesPath($dir)->setCurrentLocale('en');
        $this->assertSame('Old', $i18n->hum('hello'));
    }

    public function testSaveCacheOverwritesAndLeavesNoTempFiles()
    {
        $dir = "$this->tmp_dir/locales";
        $this->writeYml($dir, 'en.yml', "en:\n  hello: Old\n");
        $this->i18n->addLocalesPath($dir)->saveCache('en');
        $this->writeYml($dir, 'en.yml', "en:\n  hello: New\n");
        $this->i18n->saveCache('en');

        $this->assertSame(['i18n-locale-en.php'], array_values(array_diff(scandir($this->cache_path), ['.', '..'])));
        $this->assertSame('New', $this->newInstance()->setCurrentLocale('en')->hum('hello'));
    }

    public function testSaveCacheFailsWhenCachePathIsFile()
    {
        $file = "$this->tmp_dir/file";
        touch($file);
        $this->i18n->setCachePath($file);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Cache path is not a directory: $file");
        $this->i18n->saveCache('en');
    }

    public function testSaveCacheFailsWhenCachePathIsNotWritable()
    {
        $this->skipIfRoot();
        mkdir($this->cache_path, 0555);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Cache path is not writable: $this->cache_path");
        $this->i18n->saveCache('en');
    }

    public function testSaveCacheFailsWhenCachePathCannotBeCreated()
    {
        $this->skipIfRoot();
        $parent = "$this->tmp_dir/readonly";
        mkdir($parent, 0555);
        $this->i18n->setCachePath("$parent/cache");
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Could not create cache path $parent/cache: mkdir()");
        $this->i18n->saveCache('en');
    }

    public function testSaveCacheFailsWhenFileCannotBeReplaced()
    {
        $file = "$this->cache_path/i18n-locale-en.php";
        mkdir($file, 0777, true);
        touch("$file/keep");
        $this->i18n->addLocalesPath(self::EXAMPLES);
        try {
            $this->i18n->saveCache('en');
            $this->fail('Expected RuntimeException');
        } catch (RuntimeException $e) {
            $this->assertStringStartsWith("Could not save cache in $file: rename(", $e->getMessage());
        }
        $this->assertSame(['i18n-locale-en.php'], array_values(array_diff(scandir($this->cache_path), ['.', '..'])));
    }

    protected function skipIfRoot(): void
    {
        if (function_exists('posix_geteuid') && posix_geteuid() === 0) {
            $this->markTestSkipped('root ignora permissões de escrita');
        }
    }

}

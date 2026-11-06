<?php

namespace Paliari;

use Symfony\Component\Yaml\Yaml,
    Exception,
    InvalidArgumentException,
    RuntimeException,
    Stringable;

/**
 * Class I18n
 *
 * @package Paliari
 */
class I18n
{

    const LOCALE_PATTERN = '/^[A-Za-z]{2,8}([_-][A-Za-z0-9]{1,8})*$/';

    private static $_instance;

    public $current_locale = 'pt-BR';

    /**
     *
     * @var array
     */
    protected $locales_paths = [];

    /**
     *
     * @var string
     */
    protected $cache_path;

    /**
     *
     * @var array
     */
    protected $locales = [];

    /**
     *
     * @return static
     */
    public static function instance()
    {
        return static::$_instance = static::$_instance ?: new static();
    }

    /**
     * @return string
     */
    public function getCachePath(): string
    {
        return $this->cache_path ?: sys_get_temp_dir();
    }

    /**
     * @param string $cache_path
     *
     * @return $this
     */
    public function setCachePath(string $cache_path): self
    {
        $this->cache_path = $cache_path;

        return $this;
    }

    /**
     * @return array
     */
    public function getLocalesPaths(): array
    {
        return $this->locales_paths;
    }

    /**
     * @param array $locales_paths
     *
     * @return $this
     */
    public function setLocalesPaths(array $locales_paths): self
    {
        $this->locales_paths = $locales_paths;

        return $this;
    }

    /**
     * @param string $path
     *
     * @return I18n
     * @throws Exception
     */
    public function addLocalesPath(string $path): self
    {
        if (!$path) {
            throw new Exception('Locales path cannot be blank');
        }
        $this->locales_paths[] = $path;

        return $this;
    }

    /**
     * Retorna as traduções do locale.
     *
     * @param string $locale
     *
     * @return array
     * @throws InvalidArgumentException
     */
    public function locale($locale = '')
    {
        $locale = $locale ?: $this->current_locale;

        return $this->locales[$locale] ?? $this->getLocale($locale);
    }

    /**
     *
     * @param string $key
     * @param array  $replaces
     *
     * @return string|array|null null quando a chave não existe
     */
    public function hum($key, $replaces = [])
    {
        $content = $this->locale();
        foreach (explode('.', $key) as $k) {
            if (!is_array($content) || !array_key_exists($k, $content)) {
                return null;
            }
            $content = $content[$k];
        }

        return $this->replaceParams($content, $replaces);
    }

    /**
     *
     * @param string $error
     * @param array  $replaces
     *
     * @return string|array|null
     */
    public static function humErrorMessage($error, $replaces = [])
    {
        return static::instance()->hum("errors.messages.$error", $replaces);
    }

    /**
     *
     * @param string $locale
     *
     * @return array
     */
    protected function parse($locale)
    {
        $i18n = [];
        foreach ($this->getLocalesPaths() as $path) {
            $i18n = $this->parseYml($path, $locale, $i18n);
        }

        return $this->locales[$locale] = $i18n;
    }

    protected function parseYml(string $path, string $locale, array $i18n)
    {
        foreach ($this->localeFiles($path, $locale) as $file) {
            $content = Yaml::parseFile($file)[$locale] ?? [];
            if (is_array($content)) {
                $i18n = array_merge($i18n, $content);
            }
        }

        return $i18n;
    }

    /**
     * Arquivos do locale no path: `{locale}.yml` ou `{prefixo}[.-_]{locale}.yml`, em ordem alfabética.
     *
     * @param string $path
     * @param string $locale
     *
     * @return array
     */
    protected function localeFiles(string $path, string $locale): array
    {
        if (!is_dir($path)) {
            return [];
        }
        $pattern = '/^(.+[._-])?' . preg_quote($locale, '/') . '\.yml$/';
        $files   = [];
        foreach (scandir($path) ?: [] as $name) {
            $file = "$path/$name";
            if (preg_match($pattern, $name) && is_file($file)) {
                $files[] = $file;
            }
        }

        return $files;
    }

    /**
     * Substitui `%{chave}` pelos valores escalares, null ou Stringable; outros valores são ignorados.
     *
     * @param string|array|null $message
     * @param array             $replaces
     *
     * @return string|array|null
     */
    public function replaceParams($message, $replaces = [])
    {
        if (!$replaces || (!is_string($message) && !is_array($message))) {
            return $message;
        }
        foreach ($replaces as $k => $v) {
            if (is_scalar($v) || $v === null || $v instanceof Stringable) {
                $message = str_replace('%{' . $k . '}', (string)$v, $message);
            }
        }

        return $message;
    }

    /**
     *
     * @return string
     */
    public function getCurrentLocale()
    {
        return $this->current_locale;
    }

    /**
     *
     * @param string $current_locale (pt-BR|en)
     *
     * @return $this
     * @throws Exception
     */
    public function setCurrentLocale($current_locale)
    {
        if (!$current_locale) {
            throw new Exception('Locale cannot be blank');
        }
        $this->assertValidLocale($current_locale);
        $this->current_locale = $current_locale;

        return $this;
    }

    /**
     * @param string $locale
     *
     * @return array
     * @throws InvalidArgumentException
     */
    protected function getLocale($locale)
    {
        $this->assertValidLocale($locale);
        $file = $this->cacheFile($locale);
        if (file_exists($file)) {
            return $this->locales[$locale] = include $file;
        }

        return $this->parse($locale);
    }

    /**
     * O locale é usado no nome de arquivo que recebe include, então precisa ser validado antes.
     *
     * @param mixed $locale
     *
     * @throws InvalidArgumentException
     */
    protected function assertValidLocale($locale): void
    {
        if (!is_string($locale) || !preg_match(static::LOCALE_PATTERN, $locale)) {
            throw new InvalidArgumentException('Invalid locale: ' . (is_scalar($locale) ? $locale : gettype($locale)));
        }
    }

    protected function cacheFile($locale)
    {
        return $this->getCachePath() . "/i18n-locale-$locale.php";
    }

    /**
     * @param string $locale
     *
     * @return string
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function saveCache($locale)
    {
        $this->assertValidLocale($locale);
        $path = $this->getCachePath();
        if (file_exists($path) && !is_dir($path)) {
            throw new RuntimeException("Cache path is not a directory: $path");
        }
        if (!is_dir($path)) {
            $this->runOrFail(fn() => mkdir($path, 0777, true) || is_dir($path), "Could not create cache path $path");
        }
        if (!is_writable($path)) {
            throw new RuntimeException("Cache path is not writable: $path");
        }
        $file    = $this->cacheFile($locale);
        $tmp     = $file . '.' . uniqid('', true) . '.tmp';
        $content = '<?php return ' . var_export($this->parse($locale), true) . ';';
        try {
            $this->runOrFail(fn() => file_put_contents($tmp, $content) !== false && rename($tmp, $file), "Could not save cache in $file");
        } finally {
            if (is_file($tmp)) {
                unlink($tmp);
            }
        }
        if (function_exists('opcache_invalidate')) {
            opcache_invalidate($file, true);
        }

        return "Saved in $file.";
    }

    /**
     * Executa a operação de disco convertendo o warning do PHP em RuntimeException, para não gerar log.
     *
     * @param callable $operation deve retornar false em caso de falha
     * @param string   $message
     *
     * @return mixed
     * @throws RuntimeException
     */
    protected function runOrFail(callable $operation, string $message)
    {
        $error = '';
        set_error_handler(function ($no, $msg) use (&$error) {
            $error = $msg;

            return true;
        });
        try {
            $result = $operation();
        } finally {
            restore_error_handler();
        }
        if ($result === false) {
            throw new RuntimeException($error ? "$message: $error" : $message);
        }

        return $result;
    }

}

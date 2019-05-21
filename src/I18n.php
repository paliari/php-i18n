<?php

namespace Paliari;

use Symfony\Component\Yaml\Yaml,
    Exception;

/**
 * Class I18n
 *
 * @package Paliari
 */
class I18n
{

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
     */
    public function setCachePath(string $cache_path): void
    {
        $this->cache_path = $cache_path;
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
     */
    public function setLocalesPaths(array $locales_paths): void
    {
        $this->locales_paths = $locales_paths;
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
     * Obtem o local.
     *
     * @param string $locale
     *
     * @return mixed
     */
    public function locale($locale = '')
    {
        $locale = $locale ?: $this->current_locale;

        return @$this->locales[$locale] ?: $this->getLocale($locale);
    }

    /**
     *
     * @param string $key
     * @param array  $replaces
     *
     * @return mixed
     */
    public function hum($key, $replaces = [])
    {
        $content = $this->locale();
        $keys    = explode('.', $key);
        foreach ($keys as $k) {
            $content = @$content[$k];
        }

        return $this->replaceParams($content, $replaces);
    }

    /**
     *
     * @param string $error
     * @param array  $replaces
     *
     * @return mixed
     */
    public static function hum_error_message($error, $replaces = [])
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
        $files = "{$path}/*$locale.yml";
        foreach (glob($files) as $file) {
            $i18n = array_merge($i18n, Yaml::parse(file_get_contents($file))[$locale]);
        }

        return $i18n;
    }

    /**
     *
     * @param       $message
     * @param array $replaces
     *
     * @return mixed
     */
    public function replaceParams($message, $replaces = [])
    {
        if (!$replaces) {
            return $message;
        }
        foreach ($replaces as $k => $v) {
            $message = str_replace('%{' . $k . '}', $v, $message);
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
        $this->current_locale = $current_locale;

        return $this;
    }

    /**
     * @param string $locale
     *
     * @return array
     */
    protected function getLocale($locale)
    {
        $file = $this->cacheFile($locale);
        if (file_exists($file)) {
            return include "$file";
        }

        return $this->parse($locale);
    }

    protected function cacheFile($locale)
    {
        $path = $this->getCachePath();
        if (!file_exists($path)) {
            @mkdir($path, 0777, true);
        }

        return "$path/i18n-locale-$locale.php";
    }

    /**
     * @param string $locale
     *
     * @return string
     */
    public function saveCache($locale)
    {
        $file    = $this->cacheFile($locale);
        $content = '<?php return ' . var_export($this->parse($locale), true) . ';';
        file_put_contents($file, $content);

        return "Saved in $file.php";
    }

}

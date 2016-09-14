<?php
namespace Paliari;

use Symfony\Component\Yaml\Yaml;

/**
 * Class I18n
 * @package Paliari
 */
class I18n
{
    private static $_instance;

    public $current_locale = 'pt-BR';

    /**
     * @var string
     */
    protected $locales_path = '';

    /**
     * @var array
     */
    protected $locales = [];

    /**
     * @return static
     */
    public static function instance()
    {
        return static::$_instance = static::$_instance ?: new static();
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

        return @$this->locales[$locale] ?: $this->parse($locale);
    }

    /**
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
     * @param string $locale
     *
     * @return mixed
     */
    protected function parse($locale)
    {
        $i18n  = [];
        $files = "{$this->getLocalesPath()}/*$locale.yml";
        foreach (glob($files) as $file) {
            $i18n = array_merge($i18n, Yaml::parse(file_get_contents($file))[$locale]);
        }

        return $this->locales[$locale] = $i18n;
    }

    /**
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
     * Obtem o caminho para os arquivos yml de traducao
     *
     * @return string
     * @throws \Exception
     */
    protected function getLocalesPath()
    {
        if (!$this->locales_path) {
            throw new \Exception('I18n locales path not found!');
        }

        return $this->locales_path;
    }

    /**
     * Atribui o caminho dos arquivos yml de traducao
     *
     * @param string $path
     *
     * @return $this
     * @throws \Exception
     */
    public function setLocalesPath($path)
    {
        if (!$path) {
            throw new \Exception('Locales path cannot be blank');
        }
        $this->locales_path = $path;

        return $this;
    }

    /**
     * @return string
     */
    public function getCurrentLocale()
    {
        return $this->current_locale;
    }

    /**
     * @param string $current_locale
     *
     * @return $this
     * @throws \Exception
     */
    public function setCurrentLocale($current_locale)
    {
        if (!$current_locale) {
            throw new \Exception('Locale cannot be blank');
        }
        $this->current_locale = $current_locale;

        return $this;
    }
}

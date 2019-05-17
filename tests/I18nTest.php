<?php
use PHPUnit\Framework\TestCase;
use Paliari\I18n;

/**
 * Class I18nTest
 */
class I18nTest extends TestCase
{

    protected $i18n;

    protected function setUp(): void
    {
        $this->i18n = I18n::instance();
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

    public function testWithoutConfigure()
    {
        $this->expectException(Exception::class);
        $this->i18n->hum('hello');
    }

    public function testSetLocalesPathToBlank()
    {
        $this->expectException(Exception::class);
        $this->i18n->setLocalesPath('');
    }

    public function testSetLocalesPathAndTranslate()
    {
        $this->i18n->setLocalesPath(__DIR__ . '/locale_examples');
        $this->assertEquals('Hello I18n', $this->i18n->hum('hello'));
        $this->assertEquals('Unauthorized', $this->i18n->hum_error_message('unauthorized'));
        $this->i18n->setCurrentLocale('pt-BR');
        $this->assertEquals('Olá I18n', $this->i18n->hum('hello'));
        $this->assertEquals('Não autorizado', $this->i18n->hum_error_message('unauthorized'));
    }

    public function testReplaceParams()
    {
        $str      = 'Olá %{first_name} %{second_name}';
        $params   = ['first_name' => 'Daniel', 'second_name' => 'Fernando'];
        $expected = 'Olá Daniel Fernando';
        $this->assertEquals($expected, $this->i18n->replaceParams($str, $params));
    }
}

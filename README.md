## php-i18n

#### Requirements

- PHP >= 8.2 (tested up to 8.5)
- symfony/yaml ^7.4 || ^8.0

#### Installation
	
	$ composer require paliari/php-i18n

#### Configuration

Create your yml locale files inside some folder in your project. See the yml file example:

```yaml
pt-BR:
  hello: Olá I18n
  user:
    name: Nome do usuário
  errors:
    messages:
      unauthorized: Não autorizado

```

> You can use multiple languages and multiple files for each language, ex:
> - pt-BR.yml
> - models.pt-BR.yml
> - errors.pt-BR.yml
> - en.yml
> - models.en.yml
> - errors.en.yml


Say to the plugin where this files were placed

```php
\Paliari\I18n::instance()->addLocalesPath(__DIR__ . '/config/locales')->setCurrentLocale('pt-BR');
```

#### Usage 

```php
Paliari\I18n::instance()->hum('hello');
Paliari\I18n::instance()->hum('user.name');
Paliari\I18n::instance()->hum('welcome', ['name' => 'Daniel']); // welcome: Olá %{name}
Paliari\I18n::humErrorMessage('unauthorized'); // errors.messages.unauthorized
```

Missing keys return `null` without triggering any warning or deprecation notice.

#### Cache

```php
$i18n = Paliari\I18n::instance();
$i18n->setCachePath(__DIR__ . '/tmp');
$i18n->saveCache('pt-BR');
```

When a cache file exists for a locale it is loaded instead of parsing the yml files.

#### Tests

	$ composer test

### Authors

- [Daniel Fernando Lourusso](http://dflourusso.com.br)
- [Marcos Paliari](http://paliari.com.br)

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

A file is loaded for a locale when its name is `{locale}.yml` or `{prefix}{separator}{locale}.yml`,
where the separator is `.`, `-` or `_` (`models.en.yml`, `models-en.yml`, `models_en.yml`).
Files such as `screen.yml` are **not** loaded for `en`.
Files of the same locale are loaded in alphabetical order and merged with a shallow `array_merge`:
a top level key repeated in two files is replaced, not merged.

Say to the plugin where this files were placed (you can add more than one path)

```php
\Paliari\I18n::instance()->addLocalesPath(__DIR__ . '/config/locales')->setCurrentLocale('pt-BR');
```

Locales must match `/^[A-Za-z]{2,8}([_-][A-Za-z0-9]{1,8})*$/` (ex.: `en`, `pt-BR`, `zh_Hant`, `es-419`).
An invalid locale throws `InvalidArgumentException`, because it is used to build file names.

#### Usage 

```php
Paliari\I18n::instance()->hum('hello');
Paliari\I18n::instance()->hum('user.name');
Paliari\I18n::instance()->hum('welcome', ['name' => 'Daniel']); // welcome: Olá %{name}
Paliari\I18n::humErrorMessage('unauthorized'); // errors.messages.unauthorized
```

Missing keys return `null` without triggering any warning or deprecation notice.

Replace values can be scalars, `null` or `Stringable` objects. Arrays and other objects are ignored
and the `%{placeholder}` is kept in the text.

#### Cache

```php
Paliari\I18n::instance()->setCachePath(__DIR__ . '/tmp')->saveCache('pt-BR');
```

When a cache file exists for a locale it is loaded instead of parsing the yml files, so run
`saveCache()` again after changing them. The cache is written atomically and `saveCache()` throws
`RuntimeException` when the cache path cannot be created or written.

#### Tests

	$ composer test

### Authors

- [Daniel Fernando Lourusso](http://dflourusso.com.br)
- [Marcos Paliari](http://paliari.com.br)

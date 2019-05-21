## php-i18n

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
```

### Authors

- [Daniel Fernando Lourusso](http://dflourusso.com.br)
- [Marcos Paliari](http://paliari.com.br)

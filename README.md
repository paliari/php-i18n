## php-i18n

#### Instalation

```
	{
		"repositories": [
			{
				"url": "https://github.com/paliari/php-i18n",
				"type": "git"
    		}
  		],
	    "require": {
	    	"paliari/php-i18n": "dev-master"
	  	}
	}
```	
	
	$ composer install

#### Configuration

	\Paliari\I18n::instance()->setLocalesPath(__DIR__ . '/config/locales')->setCurrentLocale('pt-BR');

#### Usage 

	Paliari\I18n::instance()->hum('hello');
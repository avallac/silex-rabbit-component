silex-rabbit-component
======================

silex-rabbit-component is a Silex service provider for working with Rabbit.

Installation
------------
Install the silex-rabbit-component using [composer](http://getcomposer.org/).  This project uses
[sematic versioning](http://semver.org/).

```bash
composer require avallac/silex-rabbit-component "~1.0"
```

Parameters
----------

Services
--------
* **rabbitChannel**: An instance of Rabbit Channel

Registering
-----------
```php
$app->register(new RabbitChannelProvider());
```

JSON Validation
---------------

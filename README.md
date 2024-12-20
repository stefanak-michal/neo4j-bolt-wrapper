# Neo4j Bolt wrapper

This library contains wrapper class to cover basic functionality with [Bolt library](https://github.com/neo4j-php/Bolt).

## Instalation

Run the following command in your project to install the latest applicable version of the package:

`composer require stefanak-michal/neo4j-bolt-wrapper`

[Packagist](https://packagist.org/packages/stefanak-michal/neo4j-bolt-wrapper)

## Usage

```php
Neo4j::$auth = ['scheme' => 'basic', 'principal' => 'neo4j', 'credentials' => 'password'];
$rows = Neo4j::query('RETURN $n as num', ['n' => 123]);
```

You can also use methods like `queryFirstField` and `queryFirstColumn`. 

_If you want to learn more about available query parameters check Bolt library [readme](https://github.com/neo4j-php/Bolt/blob/master/README.md)._

### Database server

Default connection is executed on 127.0.0.1:7687. You can change target server with static properties:

```php
Neo4j::$host = 'neo4j+s://demo.neo4jlabs.com';
Neo4j::$port = 7687;
```

### Transactions

Transaction methods are:

```php
Neo4j::begin();
Neo4j::commit();
Neo4j::rollback();
```

### Log handler

You can set callable function into `Neo4j::$logHandler` which is called everytime query is executed. Method will receive executed query with additional statistics.

_Check class property annotation for more information._

### Error handler

Standard behaviour on error is trigger_error with E_USER_ERROR. If you want to handle Exception by yourself you can set callable function into `Neo4j::$errorHandler`. 

### Statistics

Wrapper offers special method `Neo4j::statistic()`. This method returns specific information from last executed query. 

_Check method annotation for more information._

## Support

[![ko-fi](https://ko-fi.com/img/githubbutton_sm.svg)](https://ko-fi.com/Z8Z5ABMLW)

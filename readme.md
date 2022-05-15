anik/laravel-amqp
[![codecov](https://codecov.io/gh/ssi-anik/laravel-amqp/branch/master/graph/badge.svg?token=IeJwxqQOuD)](https://codecov.io/gh/ssi-anik/laravel-amqp)
[![PHP Version Require](http://poser.pugx.org/anik/laravel-amqp/require/php)](//packagist.org/packages/anik/laravel-amqp)
[![Latest Stable Version](https://poser.pugx.org/anik/laravel-amqp/v)](//packagist.org/packages/anik/laravel-amqp)
[![Total Downloads](https://poser.pugx.org/anik/laravel-amqp/downloads)](//packagist.org/packages/anik/laravel-amqp)
===

[anik/amqp](https://packagist.org/packages/anik/amqp) wrapper for Laravel-ish frameworks.

- [Laravel](https://github.com/laravel/laravel)
- [Lumen](https://github.com/laravel/lumen)
- [Laravel Zero](https://github.com/laravel-zero/laravel-zero)

# Examples

Checkout the [repository](https://github.com/ssi-anik/laravel-rabbitmq-producer-consumer-example) for example.

# Documentation

## Installation

To install the package, run
> composer require anik/laravel-amqp

### Laravel

The `Anik\Laravel\Amqp\Providers\AmqpServiceProvider::class` service provider should automatically get registered. If
not, then you can manually add the service provider in your `config/app.php` providers array:

```php
'providers' => [
    // ... 
    Anik\Laravel\Amqp\Providers\AmqpServiceProvider::class,
]
```

- Publish configuration with `php artisan vendor:publish --provider "Anik\Laravel\Amqp\Providers\AmqpServiceProvider"`
  command.

### Lumen

- Register `Anik\Laravel\Amqp\Providers\AmqpServiceProvider::class` service provider in your `bootstrap/app.php` file.

```php
$app->register(Anik\Laravel\Amqp\Providers\AmqpServiceProvider::class);
```

- Copy configuration `amqp.php` in your config directory from `vendor/anik/laravel-amqp/src/config/amqp.php`.

- Import your configuration using `$app->configure('amqp');` in your `bootstrap/app.php`.

### Laravel Zero

- Register `Anik\Laravel\Amqp\Providers\AmqpServiceProvider::class` service provider in your `config/app.php` providers
  array:

```php
'providers' => [
    /// ... 
    Anik\Laravel\Amqp\Providers\AmqpServiceProvider::class,
]
```

- Copy configuration `amqp.php` in your config directory from `vendor/anik/laravel-amqp/src/config/amqp.php`.

## Configuration

In your `config/amqp.php`, you can define multiple connections and use them from your code by pointing the connection
name.

- `amqp.default` denoting the default connection. Will be used if no connection is specified when producing or consuming
  messages.
- `amqp.connections.*.connection.class` denoting the underlying Amqp connection to be used. By default, it uses lazy
  connection. You can change it to any implementation of `PhpAmqpLib\Connection\AbstractConnection`.
- `amqp.connections.*.connection.hosts` can have multiple host configuration. Each host config must contain `host`
  , `port`, `user`, `password` keys. It can also contain `vhost` which is optional. Lazy connections cannot have **more
  than one host** configuration otherwise it'll throw error.
- You can also pass optional array of parameters through `amqp.connections.*.connection.options` when creating an
  instance of `amqp.connections.*.connection.class` internally.
- `amqp.connections.*.message` holds the default properties of a message when publishing.
- `amqp.connections.*.exchange` holds the default properties of your exchange when publishing & consuming.
- `amqp.connections.*.queue` holds the default properties of your queue when consuming.
- `amqp.connections.*.consumer` holds the default properties of consumer when consuming.
- `amqp.connections.*.qos` holds the default properties of QoS when consuming.

## Usage

The followings work the same.

```php
use Anik\Amqp\ConsumableMessage;
use Anik\Laravel\Amqp\Facades\Amqp;

$messages = 'my message';
// $messages = ['my first message', 'my second message'];
// $messages = new Anik\Amqp\ProducibleMessage('my message');
// $messages = ['another message', new Anik\Amqp\ProducibleMessage('also another message')];

Amqp::publish($messages); // publishes to default connection
Amqp::connection('rabbitmq')->publish($messages); // publishes to rabbitmq connection

app('amqp')->publish($messages); // publishes to default connection
app('amqp')->connection('rabbitmq')->publish($messages); // publishes to rabbitmq connection

app()->make('amqp')->publish($messages); // publishes to default connection
app()->make('amqp')->connection('rabbitmq')->publish($messages); // publishes to rabbitmq connection

/** @var \Anik\Laravel\Amqp\AmqpManager $amqpManager */
$amqpManager->publish($messages); // publishes to default connection
$amqpManager->connection('rabbitmq')->publish($messages); // publishes to rabbitmq connection


Amqp::consume(function(ConsumableMessage $message) {
    var_dump($message->getMessageBody());
    $message->ack();
}); // consumes from default connection
Amqp::connection('rabbitmq')->consume(function(ConsumableMessage $message) {
    var_dump($message->getMessageBody());
    $message->ack();
}); // consumes from rabbitmq connection

app('amqp')->consume(function(ConsumableMessage $message) {
    var_dump($message->getMessageBody());
    $message->ack();
}); // consumes from default connection
app('amqp')->connection('rabbitmq')->consume(function(ConsumableMessage $message) {
    var_dump($message->getMessageBody());
    $message->ack();
}); // consumes from rabbitmq connection

app()->make('amqp')->consume(function(ConsumableMessage $message) {
    var_dump($message->getMessageBody());
    $message->ack();
}); // consumes from default connection
app()->make('amqp')->connection('rabbitmq')->consume(function(ConsumableMessage $message) {
    var_dump($message->getMessageBody());
    $message->ack();
}); // consumes from rabbitmq connection

/** @var \Anik\Laravel\Amqp\AmqpManager $amqpManager */
$amqpManager->consume(function(ConsumableMessage $message) {
    var_dump($message->getMessageBody());
    $message->ack();
}); // consumes from default connection
$amqpManager->connection('rabbitmq')->consume(function(ConsumableMessage $message) {
    var_dump($message->getMessageBody());
    $message->ack();
}); // consumes from rabbitmq connection
```

### Note

In this documentation, it'll use **FACADE** afterwards. If you're using **Lumen**, then you can use other approaches.
The package **doesn't require enabling Facade**.

## Publishing messages

To publish messages,

```php
use Anik\Laravel\Amqp\Facades\Amqp;

Amqp::publish($messages, $routingKey, $exchange, $options);
Amqp::connection('rabbitmq')->publish($messages, $routingKey, $exchange, $options);
```

- `$messages` Type: `mixed`. **Required**. It can be a single message, or an array of messages of any scalar type or
  implementation of `Anik\Amqp\Producible`.
- `$routingKey` Type: `string`. **Optional**. Default: `''` (empty string).
- `$exchange` Type: `null | Anik\Amqp\Exchanges\Exchange`. **Optional**. Default: `null`.
- `$options` Type: `array`. **Optional**. Default: `[]`.
    * Key `message` - Accepts: `array`. Valid properties for `PhpAmqpLib\Message\AMQPMessage`.
    * Key `exchange` - Accepts: `array`. Refer to `amqp.connections.*.exchange`.
    * Key `publish` - Accepts: `array`. Refer
      to [`Anik\Amqp\Producer::publishBatch`](https://github.com/ssi-anik/amqp#documentation)

### Note

- If any of the `$messages` is not an implementation of `Anik\Amqp\Producible`, then that message will be converted
  to `Anik\Amqp\Producible` using `Anik\Amqp\ProducibleMessage`.
- When converting to `Anik\Amqp\Producible`, it'll try to use `$options['message']` as the message property. If not set,
  it'll then try to use `amqp.connections.*.message` properties if available.
- If `$exchange` is set to `null`, it'll check if `$options['exchange']` is set or not. If not set, it'll then
  use `amqp.connections.*.exchange` properties if available.
- If `$options['publish']` is not set, it'll try to use `amqp.connections.*.publish` properties if available.

## Consuming messages

To consume messages,

```php
use Anik\Laravel\Amqp\Facades\Amqp;

Amqp::consume($handler, $bindingKey, $exchange, $queue, $qos , $options);
Amqp::connection('rabbitmq')->consume($handler, $bindingKey, $exchange, $queue, $qos , $options);
```

- `$handler` Type: `callable | Anik\Amqp\Consumable`. **Required**.
- `$bindingKey` Type: `string`. **Optional**. Default: `''` (empty string).
- `$exchange` Type: `null | Anik\Amqp\Exchanges\Exchange`. **Optional**. Default: `null`.
- `$queue` Type: `null | Anik\Amqp\Queues\Queue`. **Optional**. Default: `null`.
- `$qos` Type: `null | Anik\Amqp\Qos\Qos`. **Optional**. Default: `null`.
- `$options` Type: `array`. **Optional**. Default: `[]`.
    * Key `exchange` - Accepts: `array`. Refer to `amqp.connections.*.exchange`.
    * Key `queue` - Accepts: `array`. Refer to `amqp.connections.*.queue`.
    * Key `qos` - Accepts: `array`. Refer to `amqp.connections.*.qos`.
    * Key `consumer` - Accepts: `array`. Refer to `amqp.connections.*.consumer`.
    * Key `bind` - Accepts: `array`. Refer
      to [`Anik\Amqp\Consumer::consume`](https://github.com/ssi-anik/amqp#documentation)

### Note

- If `$handler` is not an implementation of `Anik\Amqp\Consumable`, then the handler will be converted
  to `Anik\Amqp\Consumable` using `Anik\Amqp\ConsumableMessage`.
- If `$exchange` is set to `null`, it'll check if `$options['exchange']` is set or not. If not set, it'll then
  use `amqp.connections.*.exchange` properties if available.
- If `$queue` is set to `null`, it'll check if `$options['queue']` is set or not. If not set, it'll then
  use `amqp.connections.*.queue` properties if available.
- If `$qos` is set to `null`, it'll check if `$options['qos']` is set or not. If not set, it'll then
  use `amqp.connections.*.qos` properties if `amqp.connections.*.qos.enabled` is set to a **truthy** value.
- If `$options['bind']` is not set, it'll use `amqp.connections.*.bind` properties if available.
- If `$options['consumer']` is not set, it'll use `amqp.connections.*.consumer` properties if available.

## Testing

The package allows asserting a few scenarios. Before you can run those assertions, you'll need use `Amqp::fake()`.

```php
<?php

use Anik\Laravel\Amqp\Facades\Amqp;
use PHPUnit\Framework\TestCase;

class MyTest extends TestCase 
{
    public function testIfMessageWasProduced () {
        Amqp::fake();
        // ... Your code
        
        Amqp::assertPublished();
        // Amqp::assertPublished("my-message");
        // Amqp::assertPublishedCount(5, "my-message");
        // Amqp::assertPublished(Anik\Amqp\ProducibleMessage::class);
        // Amqp::assertPublished(Anik\Amqp\Producible::class);
        Amqp::assertPublishedOnConnection('rabbitmq');
    }
}
```

- `Anik\Laravel\Amqp\Facades\Amqp::assertPublishedOnConnection(string $name)` - To check if at least one message was
  published on the connection `$name`.
- `Anik\Laravel\Amqp\Facades\Amqp::assertPublishedOnExchange(string $name)` - To check if at least one message was
  published on exchange `$name`.
- `Anik\Laravel\Amqp\Facades\Amqp::assertPublishedOnExchangeType(string $type)` - To check if at least one message was
  published on exchange type `$type`.
- `Anik\Laravel\Amqp\Facades\Amqp::assertPublishedWithRoutingKey(string $key)` - To check if at least one message was
  published with routing key `$key`.
- `Anik\Laravel\Amqp\Facades\Amqp::assertPublished($message = null)`
    * If `$message` is `null`, it will check if at least one message was published.
    * Otherwise, checks in the following order.
        - If a message exactly matches the `$message`.
        - If a message exactly matches the `get_class($message)`.
        - If a message is an implementation of `$message`.
- `Anik\Laravel\Amqp\Facades\Amqp::assertNotPublished($message = null)`
    * If `$message` is `null`, it will check if no message was published.
    * Otherwise, checks in the following order.
        - No message was published that exactly matches the `$message`.
        - No message was published that exactly matches the `get_class($message)`.
        - No message was published that is an implementation of `$message`.
- `Anik\Laravel\Amqp\Facades\Amqp::assertPublishedCount(int $count, $message = null)`
    * If `$message` is `null`, it will check if exactly `$count` messages have been published.
    * Otherwise, checks in the following order.
        - If a message exactly matches the `$message`.
        - If a message exactly matches the `get_class($message)`.
        - If a message is an implementation of `$message`.
### Note

Using `Anik\Laravel\Amqp\Facades\Amqp::consume()` after `Anik\Laravel\Amqp\Facades\Amqp::fake()` will throw exception.

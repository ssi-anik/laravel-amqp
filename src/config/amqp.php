<?php

use PhpAmqpLib\Connection\AMQPLazySSLConnection;
use PhpAmqpLib\Message\AMQPMessage;

return [
    /**
     * Default connection
     */
    'default' => env('AMQP_CONNECTION', 'rabbitmq'),

    /**
     * Available connections
     */
    'connections' => [
        'rabbitmq' => [
            'connection' => [
                /**
                 * Lazy connection does not support more than 1 host
                 * Change connection **class** if you want to try more than one host
                 */
                'class' => AMQPLazySSLConnection::class,
                'hosts' => [
                    [
                        'host' => env('AMQP_HOST', 'localhost'),
                        'port' => env('AMQP_PORT', 5672),
                        'user' => env('AMQP_USER', ''),
                        'password' => env('AMQP_PASSWORD', ''),
                        'vhost' => env('AMQP_VHOST', '/'),
                    ],
                ],
                /**
                 * Pass additional options that are required for the AMQP*Connection class
                 * You can check *Connection::try_create_connection method to check
                 * if you want to pass additional data
                 */
                'options' => [],
            ],

            'message' => [
                'content_type' => env('AMQP_MESSAGE_CONTENT_TYPE', 'text/plain'),
                'delivery_mode' => env('AMQP_MESSAGE_DELIVERY_MODE', AMQPMessage::DELIVERY_MODE_PERSISTENT),
                'content_encoding' => env('AMQP_MESSAGE_CONTENT_ENCODING', 'UTF-8'),
            ],

            'exchange' => [
                'name' => env('AMQP_EXCHANGE_NAME', 'amq.direct'),
                'declare' => env('AMQP_EXCHANGE_DECLARE', false),
                'type' => env('AMQP_EXCHANGE_TYPE', 'direct'),
                'passive' => env('AMQP_EXCHANGE_PASSIVE', false),
                'durable' => env('AMQP_EXCHANGE_DURABLE', true),
                'auto_delete' => env('AMQP_EXCHANGE_AUTO_DELETE', false),
                'internal' => env('AMQP_EXCHANGE_INTERNAL', false),
                'no_wait' => env('AMQP_EXCHANGE_NOWAIT', false),
                'arguments' => [],
                'ticket' => env('AMQP_EXCHANGE_TICKET'),
            ],

            'queue' => [
                'declare' => env('AMQP_QUEUE_DECLARE', false),
                'passive' => env('AMQP_QUEUE_PASSIVE', false),
                'durable' => env('AMQP_QUEUE_DURABLE', true),
                'exclusive' => env('AMQP_QUEUE_EXCLUSIVE', false),
                'auto_delete' => env('AMQP_QUEUE_AUTO_DELETE', false),
                'no_wait' => env('AMQP_QUEUE_NOWAIT', false),
                'arguments' => [],
                'ticket' => env('AMQP_QUEUE_TICKET'),
            ],

            'consumer' => [
                'tag' => env('AMQP_CONSUMER_TAG', ''),
                'no_local' => env('AMQP_CONSUMER_NO_LOCAL', false),
                'no_ack' => env('AMQP_CONSUMER_NO_ACK', false),
                'exclusive' => env('AMQP_CONSUMER_EXCLUSIVE', false),
                'no_wait' => env('AMQP_CONSUMER_NOWAIT', false),
                'arguments' => [],
                'ticket' => env('AMQP_CONSUMER_TICKET'),
            ],

            'qos' => [
                'enabled' => env('AMQP_QOS_ENABLED', false),
                'prefetch_size' => env('AMQP_QOS_PREF_SIZE', 0),
                'prefetch_count' => env('AMQP_QOS_PREF_COUNT', 1),
                'global' => env('AMQP_QOS_GLOBAL', false),
            ],

            /**
             * Default Publish options
             */
            /*'publish' => [
                'mandatory' => false,
                'immediate' => false,
                'ticket' => null,
                'batch_count' => 500,
            ],*/
        ],
    ],
];

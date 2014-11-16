<?php

//Require and use AMQP files as directed by the RabbitMQ 
require_once __DIR__ . '/vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Message\AMQPMessage;

//Create connection and channel
$connection = new AMQPConnection('localhost', 5672, 'guest', 'guest');
$channel = $connection->channel();

//We declare a 'main_exchange' to which we will publish tasks
//Note the type of the exchange set to 'direct'
//
//Browing the AMQP documentation (http://www.rabbitmq.com/resources/specs/amqp0-9-1.pdf)
//one uncovers the following about a 'direct' exchange:
//
//The direct exchange type works as follows:
//1. A message queue binds to the exchange using a routing key, K.
//2. A publisher sends the exchange a message with the routing key R.
//3. The message is passed to the message queue if K = R.
$channel->exchange_declare('main_exchange','direct',false,false,false);

//Create the message with the number 1.
$msg = new AMQPMessage(sprintf('%u',1), array('delivery_mode'=>2));

//As per point (2) we publish a message to the
//'main_exchange' with routing key 'main_routing_key'
$channel->basic_publish($msg,'main_exchange','main_routing_key');

?>
<?php

//See publish.php first before reading this file.

require_once __DIR__ . '/vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Message\AMQPMessage;

$connection = new AMQPConnection('localhost', 5672, 'guest', 'guest');
$channel = $connection->channel();

//Declare the same 'main_exchange' as per publish.php
$channel->exchange_declare('main_exchange', 'direct', false, false, false);

//Declare the queue 'main_queue' from which we will consume our tasks sent by publish.php
//Make the messages durable (the 'true' arugment)
$channel->queue_declare('main_queue',false,true,false,false);

//Bind the 'main_queue' to the 'main_exchange' to receive messages
//routed with the 'main_routing_key'
//This takes care of point (1) in how a direct exchange works
$channel->queue_bind('main_queue','main_exchange','main_routing_key');

//RabbitMQ does not support a direct way to delay messages
//Instead we use a tool called 'dead-lettering' supported by RabbitMQ
//that allows one to redirect a message after it has 'died'.
//
//In our case, we want to delay the delivery of a message X to the 'main_exchange'.
//We do this by:
//1. publish X to a 'delay_queue' with an expiration time of 5 seconds
//2. ask the 'delay_queue' to republish dead messages to the 'main_exchange'
//
//So, we declare our 'delay_queue'
//Please note the extra parameters:
//1. x-message-ttl: how long before a message is declared 'dead' in the delay_queue
//2. x-dead-letter-exchange: the exchange to which to republish a 'dead' message to
//3. x-dead-letter-routing-key: the routing key to apply to this 'dead' message
$channel->queue_declare('delay_queue',false,true,false,false,false
                       ,array('x-message-ttl'=>array('I',5000)
                             , 'x-dead-letter-exchange'=>array('S','main_exchange')
                             , 'x-dead-letter-routing-key'=>array('S','main_routing_key')
                             )
                       );

//The callback to process our message.
//Recall that our message contains a number. We will,
//1. receive the message
//2. if the number is >=3 we are done
//   else we add 1 to the number and publish to the delay_queue
$callback = function($msg)
{
    list($num) = sscanf($msg->body,"%u");
    printf("[%s] Got message %u\n", strftime("%H:%M:%S"), $num);

    if ($num >= 3)
    {
        printf("[%s] Done!\n",strftime("%H:%M:%S"));
    }
    else
    {
        //republish message
        global $channel;
        $delayMsg = new AMQPMessage(sprintf('%u',$num+1), array('delivery_mode'=>2));
        $channel->basic_publish($delayMsg,'','delay_queue');
        printf("[%s] Republishing (%u) to delay_queue\n", strftime("%H:%M:%S"), $num+1);
    }
    $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
};

//setup our consumer to consume from the 'main_queue'
$channel->basic_qos(null, 1, null);
$channel->basic_consume('main_queue', '', false, false, false, false, $callback);

while(count($channel->callbacks)) {
    $channel->wait();
}

$channel->close();
$connection->close();

?>
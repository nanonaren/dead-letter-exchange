dead-letter-exchange
====================

Example of dead-letter-exchange in RabbitMQ using PHP to be able to
receive a message after some fixed delay. The examples I found on the
web didn't seem to explain what was going on with the setup so I hope
this example fills in the details. Browse the comments in the source!

Running this example
--------------------

1. Follow the RabbitMQ tutorial for setting up PHP and RabbitMQ
2. In one terminal run `php publish.php`
3. In another terminal run `php worker.php`

Expected output
---------------

```
[14:19:09] Got message 1
[14:19:09] Republishing (2) to delay_queue
[14:19:14] Got message 2
[14:19:14] Republishing (3) to delay_queue
[14:19:19] Got message 3
[14:19:19] Done!
```

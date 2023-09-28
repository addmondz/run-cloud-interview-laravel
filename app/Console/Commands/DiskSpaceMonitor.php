<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class DiskSpaceMonitor extends Command
{
    protected $signature = 'disk-space:monitor';
    protected $description = 'Monitor available disk space';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $this->printLog('Started Command: Monitor available disk space. Waiting for disk space information.');

        $host = '192.168.68.112';
        $port = 5672;
        $user = 'myappuser';
        $password = 'mypassword';
        $vhost = '/';

        $connection = new AMQPStreamConnection($host, $port, $user, $password, $vhost);
        $channel = $connection->channel();

        $channel->queue_declare('disk_space', false, false, false, false);

        $callback = function ($msg) {
            $message = "Received disk_space information: " . $msg->body;

            $this->printLog($message);
        };

        $channel->basic_consume('disk_space', '', false, true, false, false, $callback);

        while (count($channel->callbacks)) {
            $channel->wait();
        }

        $channel->close();
        $connection->close();
    }

    public function printLog($text)
    {
        $this->info("[" . date('Y-m-d H:i:s') . "]: " . $text);
    }
}

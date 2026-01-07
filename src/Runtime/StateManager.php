<?php

namespace Kyorion\MqBridge\Runtime;

use Kyorion\MqBridge\Metadata\ConsumerMetadata;

class StateManager
{
    public function isAlive(ConsumerMetadata $meta): bool
    {
        // check heartbeat + process
    }
}
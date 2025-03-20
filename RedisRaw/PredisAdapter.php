<?php

namespace BaksDev\Search\RedisRaw;

use Predis\Client;
use Psr\Log\LoggerInterface;

/**
 * Class PredisAdapter
 * @package BaksDev\Search\RedisRaw
 *
 * This class wraps the NRK client: https://github.com/nrk/predis
 */
class PredisAdapter extends AbstractRedisRawClient
{
       /** @var Client */
    public $redis;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }
    
    public function connect($hostname = '127.0.0.1', $port = 6379, $db = 0, $password = null): RedisRawClientInterface
    {
        $this->redis = new Client([
            'scheme' => 'tcp',
            'host' => $hostname,
            'port' => $port,
            'database' => $db,
            'password' => $password,
        ]);
        $this->redis->connect();
        return $this;
    }

    public function multi(bool $usePipeline = false)
    {
        return $this->redis->pipeline();
    }

    /**
     * @throws Exceptions\UnsupportedRedisDatabaseException
     * @throws Exceptions\RawCommandErrorException
     */
    public function rawCommand(string $command, array $arguments)
    {
        $preparedArguments = $this->prepareRawCommandArguments($command, $arguments);
        $rawResult = $this->redis->executeRaw($preparedArguments);
        $this->validateRawCommandResults($rawResult, $command, $arguments);
        return $this->normalizeRawCommandResult($rawResult);
    }
}

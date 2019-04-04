<?php

namespace Oilstone\ProcessLock;

use Exception;
use Illuminate\Support\Str;
use Oilstone\Logging\Log;
use Oilstone\ProcessLock\Exceptions\FailedToAcquireLockException;
use Oilstone\RedisCache\Cache;
use TH\RedisLock\RedisSimpleLock;
use TH\RedisLock\RedisSimpleLockFactory;

/**
 * Class Lock
 * @package App\Services\SimpleLock
 */
class Lock
{
    /**
     * @var Cache
     */
    protected $cache;

    /**
     * @var Log
     */
    protected $log;

    /**
     * @var int
     */
    protected $ttl;

    /**
     * @var string
     */
    protected $fileName;

    /**
     * @var int
     */
    protected $scriptLifetime;

    /**
     * Lock constructor.
     * @param string $fileName Name of the executing file
     * @param int $scriptLifetime Lock time in seconds
     * @param Cache $cache
     * @param Log $log
     */
    public function __construct(string $fileName, int $scriptLifetime, Cache $cache, Log $log)
    {
        $this->fileName = $fileName;

        $this->scriptLifetime = $scriptLifetime;

        $this->cache = $cache;

        $this->log = $log;
    }

    /**
     * @return RedisSimpleLock
     * @throws FailedToAcquireLockException
     */
    public function acquire(): RedisSimpleLock
    {
        $factory = new RedisSimpleLockFactory($this->cache->client(), $this->scriptLifetime * 1000, $this->log->enabled() ? $this->log->logger() : null);

        $lock = $factory->create(Str::slug(basename($this->fileName, '.php')));

        try {
            $lock->acquire();
        } catch (Exception $e) {
            throw new FailedToAcquireLockException($e->getMessage(), $e->getCode(), $e);
        }

        return $lock;
    }
}
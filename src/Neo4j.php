<?php

use Bolt\Bolt;
use Bolt\connection\{Socket, StreamSocket};
use Bolt\protocol\AProtocol;

/**
 * Class for Neo4j bolt driver
 * Wrapper for Bolt to cover basic functionality
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/neo4j-bolt-wrapper
 */
class Neo4j
{
    /**
     * Assigned handler is called every time query is executed
     * @var callable (string $query, array $params = [], int $executionTime = 0, array $statistics = [])
     */
    public static $logHandler;

    /**
     * Provided handler is invoked on Exception instead of trigger_error
     * @var callable (Exception $e)
     */
    public static $errorHandler;

    /**
     * Set your authentification
     * @see \Bolt\helpers\Auth
     */
    public static array $auth;

    public static string $host = '127.0.0.1';
    public static int $port = 7687;
    public static float $timeout = 15;

    private static AProtocol $protocol;
    private static array $statistics;

    /**
     * Get connection protocol for bolt communication
     * @return AProtocol
     */
    protected static function getProtocol(): AProtocol
    {
        if (!(self::$protocol instanceof AProtocol)) {
            try {
                if (strpos(self::$host, '+s://') > 0) {
                    $conn = new StreamSocket(self::$host, self::$port, self::$timeout);
                    $conn->setSslContextOptions([
                        'verify_peer' => true
                    ]);
                } else {
                    $conn = new Socket(self::$host, self::$port, self::$timeout);
                }

                $bolt = new Bolt($conn);
                self::$protocol = $bolt->build();
                self::$protocol->hello(self::$auth);

                register_shutdown_function(function () {
                    try {
                        if (method_exists(self::$protocol, 'goodbye'))
                            self::$protocol->goodbye();
                    } catch (Exception $e) {
                    }
                });
            } catch (Exception $e) {
                self::handleException($e);
            }
        }

        return self::$protocol;
    }

    /**
     * Return full output
     * @param string $query
     * @param array $params
     * @return array
     */
    public static function query(string $query, array $params = []): array
    {
        $run = $all = null;
        try {
            $run = self::getProtocol()->run($query, $params);
            $all = self::getProtocol()->pull();
        } catch (Exception $e) {
            self::handleException($e);
        }
        $last = array_pop($all);

        self::$statistics = $last['stats'] ?? [];
        self::$statistics['rows'] = count($all);

        if (is_callable(self::$logHandler)) {
            call_user_func(self::$logHandler, $query, $params, $run['t_first'] + $last['t_last'], self::$statistics);
        }

        return !empty($all) ? array_map(function ($element) use ($run) {
            return array_combine($run['fields'], $element);
        }, $all) : [];
    }

    /**
     * Get first value from first row
     * @param string $query
     * @param array $params
     * @return mixed
     */
    public static function queryFirstField(string $query, array $params = [])
    {
        $data = self::query($query, $params);
        if (empty($data)) {
            return null;
        }
        return reset($data[0]);
    }

    /**
     * Get first values from all rows
     * @param string $query
     * @param array $params
     * @return array
     */
    public static function queryFirstColumn(string $query, array $params = []): array
    {
        $data = self::query($query, $params);
        if (empty($data)) {
            return [];
        }
        $key = key($data[0]);
        return array_map(function ($element) use ($key) {
            return $element[$key];
        }, $data);
    }

    /**
     * Begin transaction
     * @return bool
     */
    public static function begin(): bool
    {
        try {
            self::getProtocol()->begin();
            if (is_callable(self::$logHandler)) {
                call_user_func(self::$logHandler, 'BEGIN TRANSACTION');
            }
            return true;
        } catch (Exception $e) {
            self::handleException($e);
        }
        return false;
    }

    /**
     * Commit transaction
     * @return bool
     */
    public static function commit(): bool
    {
        try {
            self::getProtocol()->commit();
            if (is_callable(self::$logHandler)) {
                call_user_func(self::$logHandler, 'COMMIT TRANSACTION');
            }
            return true;
        } catch (Exception $e) {
            self::handleException($e);
        }
        return false;
    }

    /**
     * Rollback transaction
     * @return bool
     */
    public static function rollback(): bool
    {
        try {
            self::getProtocol()->rollback();
            if (is_callable(self::$logHandler)) {
                call_user_func(self::$logHandler, 'ROLLBACK TRANSACTION');
            }
            return true;
        } catch (Exception $e) {
            self::handleException($e);
        }
        return false;
    }

    /**
     * Return statistic info from last executed query
     *
     * Possible keys:
     * <pre>
     * nodes-created
     * nodes-deleted
     * properties-set
     * relationships-created
     * relationship-deleted
     * labels-added
     * labels-removed
     * indexes-added
     * indexes-removed
     * constraints-added
     * constraints-removed
     * </pre>
     *
     * @param string $key
     * @return int
     */
    public static function statistic(string $key): int
    {
        return intval(self::$statistics[$key] ?? 0);
    }

    /**
     * @param Exception $e
     */
    private static function handleException(Exception $e)
    {
        if (is_callable(self::$errorHandler)) {
            call_user_func(self::$errorHandler, $e);
            return;
        }

        trigger_error('Database error occured: ' . $e->getMessage() . ' ' . $e->getCode(), E_USER_ERROR);
    }

}

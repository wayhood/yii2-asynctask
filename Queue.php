<?php
/**
 * @link http://www.wayhood.com/
 */

namespace wh\asynctask;

use Yii;
use yii\redis\Connection;
use yii\base\InvalidConfigException;

/**
 * Class Queue
 * @package wh\asynctask
 * @author Song Yeung <netyum@163.com>
 * @date 12/20/14
 */
class Queue extends \yii\base\Component
{
    public $redis = 'redis';

    //默认队列名
    public $default = 'default';

    public function init()
    {
        parent::init();
        if (is_string($this->redis)) {
            $this->redis = Yii::$app->get($this->redis);
        } elseif (is_array($this->redis)) {
            if (!isset($this->redis['class'])) {
                $this->redis['class'] = Connection::className();
            }
            $this->redis = Yii::createObject($this->redis);
        }
        if (!$this->redis instanceof Connection) {
            throw new InvalidConfigException("Queue::redis must be either a Redis connection instance or the application component ID of a Redis connection.");
        }
    }

    /**
     * write data to queue
     * @param $queue
     * @param $data
     * @return mixed
     */
    public function push($queue, $data)
    {
        $this->redis->sadd('queues', $queue);
        $queue = 'queue:'.$queue;
        return $this->redis->rpush($queue, json_encode($data));
    }

    public function quickPush($queue, $data)
    {
        $this->redis->sadd('queues', $queue);
        $queue = 'queue:'.$queue;
        return $this->redis->lpush($queue, json_encode($data));
    }

    /**
     * get a data from queue
     * @param $queue
     * @return mixed
     */
    public function pop($queue)
    {
        $queue = 'queue:'.$queue;
        $data = $this->redis->lpop($queue);
        $data = @json_decode($data, true);
        return $data;
    }

    /**
     * get all queues
     * @return array|null
     */
    public function getQueues()
    {
        return $this->redis->smembers('queues');
    }

    /**
     * write retry
     * @param $data
     * @return mixed
     */
    public function setRetry($data, $score=null)
    {
        $key = 'retry';
        if (is_null($score)) {
            $socre = doubleval(microtime(true));
        } else {
            $score = doubleval($score);
        }
        return $this->redis->zadd($key, $score, json_encode($data));
    }

    public function setSchedule($data, $score)
    {
        $key = 'schedule';
        return $this->redis->zadd($key, doubleval($score), json_encode($data));
    }

    public function getReties($remove=true)
    {
        $key = 'retry';
        $score = doubleval(microtime(true));
        $result = $this->redis->zrangebyscore($key, -1, $score);
        if ($remove) {
            $this->redis->zremrangebyscore($key, -1, $score);
        }
        return $result;
    }

    public function getSchedule($remove=true)
    {
        $key = 'schedule';
        $score = doubleval(microtime(true));
        $result = $this->redis->zrangebyscore($key, -1, $score);
        if ($remove) {
            $this->redis->zremrangebyscore($key, -1, $score);
        }
        return $result;
    }

    public function setStat($type = true)
    {
        $currentFailedKey = 'stat:failed';
        $currentProcessedKey = 'stat:processed';
        if ($type == true) {
            $this->redis->incr($currentProcessedKey);
        } else {
            $this->redis->incr($currentFailedKey);
        }
    }

    public function setStatDay($currentDate)
    {
        $currentFailedKey = 'stat:failed';
        $currentProcessedKey = 'stat:processed';

        $currentFailedNum = $this->redis->get($currentFailedKey);
        $currentProcessedNum = $this->redis->get($currentProcessedKey);

        $this->redis->set($currentFailedKey.':'.$currentDate, intval($currentFailedNum));
        $this->redis->set($currentProcessedKey.':'.$currentDate, intval($currentProcessedNum));

        $this->redis->set($currentFailedKey, 0);
        $this->redis->set($currentProcessedKey, 0);

    }

    public function getStatDay($currentDate, $type = true)
    {
        $currentFailedKey = 'stat:failed:'.$currentDate;
        $currentProcessedKey = 'stat:processed:'.$currentDate;
        if ($type == true) {
            $stat = $this->redis->get($currentProcessedKey);
        } else {
            $stat = $this->redis->get($currentFailedKey);
        }
        return intval($stat);
    }

    public function getStat($type = true)
    {
        $currentFailedKey = 'stat:failed';
        $currentProcessedKey = 'stat:processed';
        if ($type == true) {
            $stat = $this->redis->get($currentProcessedKey);
        } else {
            $stat = $this->redis->get($currentFailedKey);
        }
        return intval($stat);
    }

    public function getWorkerCount()
    {
        $count = $this->redis->executeCommand('SCARD', ['workers']);
        return intval($count);
    }

    public function getQueueCount()
    {
        $queueNames = $this->getQueues();

        $count = 0;
        foreach($queueNames as $queueName) {
            $count += intval($this->redis->llen('queue:'.$queueName));
        }
        return $count;
    }

    public function getRetryCount()
    {
        $key = 'retry';
        $count = $this->redis->executeCommand('ZLEXCOUNT', [$key, '-', '+']);
        return intval($count);
    }

    public function getScheduleCount()
    {
        $key = 'schedule';
        $count = $this->redis->executeCommand('ZLEXCOUNT', [$key, '-', '+']);
        return intval($count);
    }

    public function getAllSchedule()
    {
        $key = 'schedule';
        $result = $this->redis->zrange($key, 0, -1, 'WITHSCORES');
        return $result;
    }

    public function getAllRetries()
    {
        $key = 'retry';
        $result = $this->redis->zrange($key, 0, -1, 'WITHSCORES');
        return $result;
    }

    public function getQueueSize($queue)
    {
        $queue = 'queue:'.$queue;
        return $this->redis->llen($queue);
    }

    public function getQueueList($queue, $start, $stop)
    {
        $queue = 'queue:'.$queue;
        return $this->redis->lrange($queue, $start, $stop);
    }

    public function removeQueue($queue)
    {
        $this->redis->srem('queues', $queue);
        $queue = 'queue:'.$queue;
        return $this->redis->del($queue);
    }

    public function removeQueueItem($queue, $data)
    {
        $queue = 'queue:'.$queue;
        $this->redis->lrem($queue, -1, $data);
    }

    public function getWorkerIdentity()
    {
        $pid = @getmypid();
        $hostname = @gethostname();
        $ip = @gethostbyname($hostname);

        if (!$ip) {
            $ip = 'unknow';
        }
        if (!$hostname) {
            $hostname = 'unknow';
        }
        return $hostname. ':'. $ip .':'. $pid;
    }

    public function setWorkerStart($identity, $data)
    {
        $timeout = 180 * 24 * 60 * 60;
        $this->redis->sadd('workers', $identity);
        $this->redis->setex('worker:'. $identity. ':started', $timeout, microtime(true));

        $hash = [
            'queue' => $data['queue'],
            'playload' => $data,
            'run_at' => microtime(true),
        ];
        $this->redis->setex('worker:'. $identity, $timeout, json_encode($hash));
    }

    public function setWorkerEnd($identity)
    {
        $this->redis->srem('workers', $identity);
        $this->redis->del('worker:'. $identity);
        $this->redis->del('worker:'. $identity .':started');
    }

    public function getWorkerList()
    {
        return $this->redis->smembers('workers');
    }

    public function getWorkerStarted($identity)
    {
        return $this->redis->get('worker:'. $identity .':started');
    }

    public function getWorkerInfo($identity)
    {
        return $this->redis->get('worker:'. $identity);
    }

    public function getShowStat($days)
    {
        $currentDate = date('Y-m-d');
        $ret = [
            'processed' => [],
            'failed' => []
        ];
        $cacheKey = 'stat:'.$currentDate.':'.$day;
        if ($days == 7) {

            $data = $this->redis->get($cacheKey)
            if ($data == false) {
                $ret['processed'][date('Y-m-d')] = $this->getStat(true);
                $ret['failed'][date('Y-m-d')] = $this->getStat(false);
                for($i=1; $i<7; $i++) {
                    $date = date('Y-m-d', time()-3600*24*$i);
                    $ret['processed'][$date] = $this->getStatDay($date, true);
                    $ret['failed'][$date] = $this->getStat($date, false);
                }
                $this->redis->set($cacheKey, json_encode($ret));
                $this->redis->expire($cacheKey, 3600*24);
            } else {
                $ret = @json_decode($data);
            }
        } else if ($days == 30) {
            $data = $this->redis->get($cacheKey)
            if ($data == false) {
                $ret['processed'][date('Y-m-d')] = $this->getStat(true);
                $ret['failed'][date('Y-m-d')] = $this->getStat(false);
                for($i=1; $i<30; $i++) {
                    $date = date('Y-m-d', time()-3600*24*$i);
                    $ret['processed'][$date] = $this->getStatDay($date, true);
                    $ret['failed'][$date] = $this->getStat($date, false);
                }
                $this->redis->set($cacheKey, json_encode($ret));
                $this->redis->expire($cacheKey, 3600*24);
            } else {
                $ret = @json_decode($data);
            }
        }

        return $ret;
    }
}

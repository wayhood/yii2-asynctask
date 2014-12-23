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
            $socre = doubleval(microtime(true))
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

    public function getSchedules($remove=true)
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
}

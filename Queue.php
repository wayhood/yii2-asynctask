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
    public function setRetry($data)
    {
        $key = 'retry';
        return $this->redis->zadd($key, doubleval(microtime(true)), json_encode($data));
    }
}

<?php
/**
 * @link http://www.wayhood.com/
 */

namespace wh\asynctask;

use Yii;

/**
 * Class Worker
 * @package wh\asynctask
 * @author Song Yeung <netyum@163.com>
 * @date 12/20/14
 */
abstract class Worker extends \yii\base\Component
{
    // a queue name
    protected static $queue = 'default';

    // a yii2-redis application component name or configure array
    protected static $redis = 'redis';

    protected static $retry = true;

    /**
     * async process. please feature in subclass. 
     * @return mixed
     */
    //public static function run() {}

    /**
     * async push queue
     */
    public static function runAsync()
    {
        $payload = [
            'retry' => static::$retry,
            'queue' => static::$queue,
            'class' => static::className(),
            'args' => func_get_args(),
            'job_id' => self::getRandomId(),
            'enqueued_at' => microtime(true)
        ];

        $queue = Yii::createObject([
            'class' => 'wh\asynctask\Queue',
            'redis' => static::$redis
        ]);

        $queue->push(static::$queue, $payload);
        return $payload['job_id'];
    }

    /**
     * interval d day, h hour, m minute, s second
     * schedule
     */
    public static function runIn()
    {
        $args = func_get_args();
        $time = static::getTime(array_shift($args));

        $payload = [
            'retry' => static::$retry,
            'queue' => static::$queue,
            'class' => static::className(),
            'args' => $args,
            'job_id' => static::getRandomId(),
            'enqueued_at' => microtime(true)
        ];

        $score = microtime(true) + $time;

        $queue = Yii::createObject([
            'class' => 'dse\common\jobs\Queue',
            'redis' => static::$redis
        ]);

        $queue->setSchedule($payload, $score);
        return $payload['job_id'];
    }

    public static function runEach()
    {
        $args = func_get_args();
        $time = static::getTime(array_shift($args));

        $payload = [
            'retry' => static::$retry,
            'each' => $time,
            'queue' => static::$queue,
            'class' => static::className(),
            'args' => $args,
            'job_id' => static::getRandomId(),
            'enqueued_at' => microtime(true)
        ];
        $score = microtime(true) + $time;

        $queue = Yii::createObject([
            'class' => 'dse\common\jobs\Queue',
            'redis' => static::$redis
        ]);

        $queue->setSchedule($payload, $score);
        return $payload['job_id'];
    }

    protected static function getTime($time)
    {
        preg_match('/^([0-9]+)[ ]*([hdms]?)$/', $time, $match);
        $num = (int) $match[1];
        $interval = $match[2];
        $second = 0;
        switch($interval) {
            case 'h':
                $second = 60 * 60 * $num;
                break;
            case 'd':
                $second = 60 * 60 * 24 * $num;
                break;
            case 'm':
                $second = 60 * $num;
                break;
            case 's':
            default:
                $second = $num;
        }

        return $second;
    }

    protected static function getRandomId()
    {
        $queue = Yii::createObject([
            'class' => 'wh\asynctask\Queue',
            'redis' => static::$redis
        ]);

        return $queue->redis->incr('queue:'.static::$queue.':counter');
    }

    public static function delete($jobIds)
    {
        $queue = Yii::createObject([
            'class' => 'dse\common\jobs\Queue',
            'redis' => static::$redis
        ]);
        if(!is_array($jobIds)) {
            $jobIds = [$jobIds];
        }

        $list = $queue->getQueueList(static::$queue, 0, PHP_INT_MAX);
        foreach($list as $item) {
            $data = json_decode($item, true);
            if($data['class'] == get_called_class()
                && in_array($data['job_id'], $jobIds)
            ) {
                $queue->removeQueueItem(static::$queue, $item);
            }
        }

        $list = $queue->getAllSchedule(static::$queue);
        foreach($list as $item) {
            $data = json_decode($item, true);
            if($data['class'] == get_called_class()
                && in_array($data['job_id'], $jobIds)
            ) {
                $queue->removeScheduleItem($item);
            }
        }
    }

}

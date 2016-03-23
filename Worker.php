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
    }

    /**
     * interval d day, h hour, m minute, s second
     * schedule
     */
    public static function runIn()
    {
        $args = func_get_args();
        $time = array_shift($args);

        $time = preg_split('/\s*/', $time, -1, PREG_SPLIT_NO_EMPTY);
        if (count($time) == 2) {
            $num = $time[0];
            $interval = $time[1];

            $payload = [
                'retry' => static::$retry,
                'queue' => static::$queue,
                'class' => static::className(),
                'args' => $args,
                'job_id' => self::getRandomId(),
                'enqueued_at' => microtime(true)
            ];

            $second = 0;
            switch($interval) {
                case 'h':
                    $second = 60*60*$num;
                    break;
                case 'd':
                    $second = 60*60*24*$num;
                    break;
                case 'm':
                    $second = 60*$num;
                    break;
                case 's':
                    $second = $num;
            }

            $score = microtime(true) + $second;

            $queue = Yii::createObject([
                'class' => 'wh\asynctask\Queue',
                'redis' => static::$redis
            ]);

            $queue->setSchedule($payload, $score);
        }
    }

    protected static function getRandomId()
    {
        $pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        return substr(str_shuffle(str_repeat($pool, 5)), 0, 32);
    }
}

<?php
/**
 * @link http://www.wayhood.com/
 */

namespace wh\asynctask\console;

use wh\asynctask\Queue;
use Yii;

/**
 * Class AsyncTaskController
 * @package wh\asynctask\console
 * @author Song Yeung <netyum@163.com>
 * @date 12/21/14
 */
class AsyncTaskController extends \yii\console\Controller
{
    /**
     * @var \wh\asynctask\Module
     */
    public $module;

    /**
     * php 环境
     * @var string
     */
    public $phpEnv;

    /**
     * 选项
     * @param string $actionID
     * @return array
     */
    public function options($actionID)
    {
        return array_merge(
            parent::options($actionID),
            ['phpEnv'] // global for all actions
        );
    }

    /**
     * 处理一个Worker
     * @param string $q queue name
     */
    public function actionWorker($q=null)
    {
        $retried_at = date('Y-m-d H:i:s');

        $queue = Yii::createObject([
            'class' => 'wh\asynctask\Queue',
            'redis' => $this->module->redis
        ]);

        if ($q == null) {
            $data = $queue->popRetry();
        } else {
            $data = $queue->pop($q);
        }

        if (!is_null($data)) {
            try {
                forward_static_call_array([$data['class'], 'run'], $data['args']);
            } catch (\Exception $e) {
                if ($data['retry']) {
                    $queue->setRetry([
                        'retry' => $data['retry'],
                        'class' => $data['class'],
                        'args' => $data['args'],
                        'enqueued_at' => $data['enqueued_at'],
                        'error_message' => $e->getMessage(),
                        'failed_at' => date('Y-m-d H:i:s'),
                        'job_id' => $data['job_id'],
                        'queue' => $data['queue'],
                        'retried_at' => isset($data['retried_at']) ? $retried_at : date('Y-m-d H:i:s'),
                        'retry_count' => isset($data['retry_count']) ? intval($data['retry_count'])+1 : 0,
                    ]);
                }
            }
        }
    }

    /**
     * process worker main loop
     * @param string $q queue name. e.g. a, b, c
     */
    public function actionIndex($q='default')
    {
        $command  = $this->getCommandLine();

        $queue = Yii::createObject([
            'class' => 'wh\asynctask\Queue',
            'redis' => $this->module->redis
        ]);

        $queueNames = preg_split('/\s*,\s*/', $q, -1, PREG_SPLIT_NO_EMPTY);

        $currentQueues = $queue->getQueues();

        if (!is_array($currentQueues)) {
            $currentQueues = [];
        }

        foreach($queueNames as $key => $queueName) {
            if (!in_array($queueName, $currentQueues)) {
                unset($queueNames[$key]);
            }
        }

        while(1) {
            //处理schedule
            //TODO

            //处理retry
            $list = $queue->getReties();
            foreach($list as $data) {
                $data = @json_decode($data, true);
                if (!is_null($data)) {
                    if ($data['retry_count'] >= 20) {
                        break;
                    }
                    //checkout retry time
                    $second = pow($data['retry_count'], 4) + 15 + rand(0, 30) * ($data['retry_count'] + 1);
                    if (time() > strtotime($data['retried_at']) + $second) {
                        $queue->pushRetry($data);
                    } else { //no process
                        $queue->setRetry($data);
                    }
                }
            }
            exec(trim($command). '/worker &');

            //处理队列
            foreach($queueNames as $queueName) {
                //echo $command. '/worker "'. $queueName.'" &'."\n";
                exec(trim($command). '/worker "'. $queueName.'" &');
            }
        }
    }

    protected function getCommandLine()
    {
        $scriptName = isset($_SERVER['_']) ? $_SERVER['_'] : '/usr/bin/php';
        $yii = $_SERVER['argv'][0];
        if (substr($scriptName, strlen($yii)*-1) == $yii) {
            $command = $scriptName .' '. $this->module->id;
        } else {
            $yii = $_SERVER['PWD'].'/yii';
            $command = $scriptName .' '. $this->phpEnv .' '. $yii .' '. $this->module->id;
        }

        return $command;
    }

} 
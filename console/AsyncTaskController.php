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
     * max process num;
     * @var int
     */
    public $processMaxNum = 10;

    /**
     * 选项
     * @param string $actionID
     * @return array
     */
    public function options($actionID)
    {
        if ($actionID == 'index') {
            return array_merge(
                parent::options($actionID),
                ['phpEnv', 'processMaxNum']
            );
        } else {
            return parent::options($actionID);
        }
    }

    /**
     * 处理一个Worker
     * @param string $q queue name
     */
    public function actionWorker($q="default")
    {
        $retried_at = date('Y-m-d H:i:s');

        $queue = Yii::createObject([
            'class' => 'wh\asynctask\Queue',
            'redis' => $this->module->redis
        ]);

        $data = $queue->pop($q);

        if (!is_null($data)) {
            try {
                $queue->setStat();
                forward_static_call_array([$data['class'], 'run'], $data['args']);
            } catch (\Exception $e) {
                if ($data['retry']) {
                    $queue->setStat(false);
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

        $currentDate = date('Y-m-d');

        while(1) {
            if (date('d') != date('d', strtotime($currentDate))) {
                sleep(5);
                $this->setStatDay($currentDate);
                $currentDate = date('Y-m-d');
            }

            //process schedule
            $scheduleList = $queue->getSchedules();
            foreach($scheduleList as $data) {
                $data = @json_decode($data, true);
                if (!is_null($data)) {
                    $queue->quickPush($data['queue'], $data);
                }
            }
            unset($scheduleList);

            //process retry
            $retryList = $queue->getReties();
            foreach($retryList as $data) {
                $data = @json_decode($data, true);
                if (!is_null($data)) {
                    if ($data['retry_count'] >= 20) {
                        break;
                    }
                    //checkout retry time
                    $second = pow($data['retry_count'], 4) + 15 + rand(0, 30) * ($data['retry_count'] + 1);
                    if (time() > strtotime($data['retried_at']) + $second) {
                        $queue->quickPush($data['queue'], $data);
                    } else { //no process
                        $queue->setRetry($data);
                    }
                }
            }

            //process queue
            $max = $this->processMaxNum;

            $str = $this->module->id.'/worker';
            $current = intval(`ps -ef | grep "$str" |  grep -v grep | wc -l`);

            $subProcessNum = $max-$current;
            while($subProcessNum>0) {
                foreach($queueNames as $queueName) {
                    //echo $command. '/worker "'. $queueName.'" &'."\n";
                    exec(trim($command). '/worker "'. $queueName.'" &');
                }
                $subProcessNum--;
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
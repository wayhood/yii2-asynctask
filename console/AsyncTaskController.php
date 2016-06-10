<?php
/**
 * @link http://www.wayhood.com/
 */

namespace wh\asynctask\console;

use wh\asynctask\Queue;
use Yii;
use yii\helpers\FileHelper;

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
     * max process retries num;
     * @var int
     */
    public $processMaxRetries = 5;

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

        /** @var $identity string */
        $identity = $queue->getWorkerIdentity();

        $logPath = $this->module->getWorkerLogPath();
        FileHelper::createDirectory($logPath);
        $currentDate = date('Y-m-d');
        FileHelper::createDirectory($logPath.'/'.$currentDate);
        $logStderr = "{$logPath}/{$currentDate}/{$q}.stderr.log";
        $data = $queue->pop($q);

        if (!is_null($data)) {
            try {
                $queue->setWorkerStart($identity, $data);
                $this->setProcessTitle($data['class'].':run('.implode(', ', $data['args']).')');
                forward_static_call_array([$data['class'], 'run'], $data['args']);
            } catch (\Exception $e) {
                if($data['retry']
                    && (!isset($data['retry_count']) || $data['retry_count'] < $this->processMaxRetries)
                ) {
                    $queue->setStat(false);
                    $realCommand = sprintf('echo "%s">>%s &', $e->getMessage(), $logStderr);
                    exec($realCommand);
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
            $queue->setStat();
            $queue->setWorkerEnd($identity);
        }
    }

    /**
     * process worker main loop
     * @param string $q queue name. e.g. a, b, c
     */
    public function actionIndex($q='default')
    {
        $command  = $this->getCommandLine();

        $logPath = $this->module->getWorkerLogPath();
        FileHelper::createDirectory($logPath);
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
        FileHelper::createDirectory($logPath.'/'.$currentDate);
        while(1) {
            $ret = $queue->redis->executeCommand('PING');
            if (!$ret) {
                break;
            }

            if (date('d') != date('d', strtotime($currentDate))) {
                sleep(5);
                $queue->setStatDay($currentDate);
                $currentDate = date('Y-m-d');
                FileHelper::createDirectory($logPath.'/'.$currentDate);
            }

            //process schedule
            $scheduleList = $queue->getSchedule();
            foreach($scheduleList as $data) {
                $data = @json_decode($data, true);
                if (!is_null($data)) {
                    $queue->quickPush($data['queue'], $data);
                    if(!empty($data['each'])) {
                        $queue->setSchedule($data, microtime(true) + $data['each']);
                    }
                }
            }
            unset($scheduleList);

            //process retry
            $retryList = $queue->getRetries();
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
                        $queue->setRetry($data, microtime(true)+$second);
                    }
                }
            }

            //process queue
            $max = $this->processMaxNum;

            foreach($queueNames as $queueName) {
                if ($queue->getQueueSize($queueName) > 0) {
                    // has queue
                    $currentSubProcessNum = $this->getCurrentSubProcessNum();
                    $subProcessNum = $max - $currentSubProcessNum;
                    if ($subProcessNum > 0) {
                        $logStdout = "{$logPath}/{$currentDate}/{$queueName}.stdout.log";
                        $logStderr = "{$logPath}/{$currentDate}/{$queueName}.stderr.log";
                        $realCommand = sprintf('%s/worker "%s" >> %s  2>>%s &', trim($command), $queueName, $logStdout, $logStderr);
                        exec($realCommand);
                    }
                }
            }
        }
    }

    /**
     * get command line
     * @return string
     */
    protected function getCommandLine()
    {
        $this->phpEnv = is_null($this->phpEnv) ? '/usr/bin/php' : $this->phpEnv;
        $yii = $_SERVER['argv'][0];
        $command = $this->phpEnv .' '. $yii .' '. $this->module->id;

        return $command;
    }

    /**
     * get current sub process num
     * @return int
     */
    protected function getCurrentSubProcessNum()
    {
        $str = $this->module->id.'/worker';
        $current = intval(`ps -ef | grep "$str" |  grep -v grep | wc -l`);
        return $current;
    }

     /**
     * Set process title to given string.
     * Process title is changing by cli_set_process_title (PHP >= 5.5) or
     * setproctitle (if proctitle extension is available).
     *
     * @param string          $title
     */
    protected function setProcessTitle($title)
    {
        if (function_exists('cli_set_process_title')) {
            cli_set_process_title($title);
        } elseif (function_exists('setproctitle')) {
            setproctitle($title);
        }
    }

}

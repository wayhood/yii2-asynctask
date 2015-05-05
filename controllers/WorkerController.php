<?php
/**
 * @link http://www.wayhood.com/
 */

namespace wh\asynctask\controllers;

use yii\data\ArrayDataProvider;

/**
 * Class WorkerController
 * @package wh\asynctask\controllers
 * @author Song Yeung <netyum@163.com>
 * @date 12/20/14
 */
class WorkerController extends \wh\asynctask\base\Controller
{
    public function actionIndex()
    {
        $workers = $this->queue->getWorkerList();

        $newWorkers = [];

        foreach($workers as $identity) {
            $info = $this->queue->getWorkerInfo($identity);
            if (is_null($info)) {
                break;
            }
            $hash = json_decode($info, true);

            $newWorkers[] = [
                //'job_id' => $hash['playload']['job_id'],
                'worker' => $identity,
                'started' => $this->queue->getWorkerStarted($identity),
                'arguments' => $hash['playload']['args'],
                'queue' => $hash['queue'],
                'class' => $hash['playload']['class'],
            ];
        }
        $dataProvider = new ArrayDataProvider([
            'key' => 'worker',
            'allModels' => $newWorkers
        ]);

        return $this->render('index', ['dataProvider' => $dataProvider]);
    }


    public function actionRetry($identity)
    {
        //$this->queue->getWorkerInfo($identity);
        //$this->queue->setWorkerEnd($identity);
    }
} 
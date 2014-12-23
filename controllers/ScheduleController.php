<?php
/**
 * @link http://www.wayhood.com/
 */

namespace wh\asynctask\controllers;

use yii\data\ArrayDataProvider;

/**
 * Class ScheduleController
 * @package wh\asynctask\controllers
 * @author Song Yeung <netyum@163.com>
 * @date 12/20/14
 */
class ScheduleController extends \wh\asynctask\base\Controller
{
    public function actionIndex()
    {
        $schedule = $this->queue->getAllSchedule();

        $newSchedule = [];

        foreach($schedule as $key => $row) {
            if ($key % 2 != 0) {
                $newSchedule[$key-1]['when'] = $row;
            } else {
                $data = json_decode($row, true);
                $newSchedule[$key] = [
                    'job_id' => $data['job_id'],
                    'queue' => $data['queue'],
                    'worker' => $data['class'],
                    'arguments' => $data['args']
                ];
            }
        }


        $dataProvider = new ArrayDataProvider([
            'key' => 'job_id',
            'allModels' => $newSchedule
        ]);

        return $this->render('index', ['dataProvider' => $dataProvider]);
    }
} 
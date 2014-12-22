<?php
/**
 * @link http://www.wayhood.com/
 */

namespace wh\asynctask\controllers;

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
        return $this->render('index');
    }
} 
<?php
/**
 * @link http://www.wayhood.com/
 */

namespace wh\asynctask\controllers;


/**
 * Class QueueController
 * @package wh\asynctask\controllers
 * @author Song Yeung <netyum@163.com>
 * @date 12/20/14
 */
class QueueController extends \wh\asynctask\base\Controller
{
    public function actionIndex()
    {
        return $this->render('index');
    }
} 
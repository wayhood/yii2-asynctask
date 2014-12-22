<?php
/**
 * @link http://www.wayhood.com/
 */

namespace wh\asynctask;

/**
 * Class AsynctaskAsset
 * @package wh\asynctask
 * @author Song Yeung <netyum@163.com>
 * @date 12/20/14
 */
class AsynctaskAsset extends \yii\web\AssetBundle
{
    public $sourcePath = '@wh/asynctask/assets';
    public $css = [
        'main.css',
    ];
    public $js = [
        'gii.js',
    ];
    public $depends = [
        'yii\web\YiiAsset',
        'yii\bootstrap\BootstrapAsset',
        'yii\bootstrap\BootstrapPluginAsset',
        //'yii\gii\TypeAheadAsset',
    ];
}

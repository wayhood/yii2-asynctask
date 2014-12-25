<?php
/**
 * @link http://www.wayhood.com/
 */

use yii\data\ArrayDataProvider;
use yii\grid\GridView;
use yii\helpers\Html;

$this->title = 'Workers';
?>
<div>
    <p>&nbsp; </p>
    <p>
    <h3><?=Html::encode($this->title);?></h3>
    </p>

    <?= GridView::widget([
        'layout' => "\n{items}",
        'dataProvider' => $dataProvider,
        //'filterModel' => $searchModel,
        'columns' => [
            [
                'header' => 'Worker',
                'value' => function($data) {
                        return $data['worker'];
                }
            ],
            'queue',
            'class',
            [
                'class' => 'yii\grid\DataColumn',
                'header' => 'Arguments',
                'value' => function($data) {
                        return json_encode($data['arguments']);
                    }
            ],
            [
                'header' => 'Started',
                'value' => function($data) {
                    return date('Y-m-d H:i:s', $data['started']);
                }
            ]
            /*[
                'class' => 'yii\grid\ActionColumn',
            ],*/
        ],
    ]); ?>
</div>
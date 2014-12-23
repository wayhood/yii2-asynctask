<?php
/**
 * @link http://www.wayhood.com/
 */

use yii\data\ArrayDataProvider;
use yii\grid\GridView;
use yii\helpers\Html;

$this->title = 'Scheduled Jobs ';
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
                'class' => 'yii\grid\CheckboxColumn',
            ],
            [
                'header' => 'When',
                'value' => function($data) {
                    return date('Y-m-d H:i:s', intval($data['when']));
                }
            ],
            'queue',
            'worker',
            [
                'class' => 'yii\grid\DataColumn',
                'header' => 'Arguments',
                'value' => function($data) {
                    return json_encode($data['arguments']);
                }
            ]
            /*[
                'class' => 'yii\grid\ActionColumn',
            ],*/
        ],
    ]); ?>
</div>
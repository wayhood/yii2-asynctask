<?php
/**
 * @link http://www.wayhood.com/
 */

use yii\grid\GridView;
use yii\helpers\Html;

$this->title = 'Queues';

?>
<div>
    <p>&nbsp;</p>
    <p>
        <h3> <?=Html::encode($this->title);?> </h3>
    </p>
    <?= GridView::widget([
        'layout' => "\n{items}",
        'dataProvider' => $dataProvider,

        'columns' => [
            [
                'header' => 'Queue',
                'format' => 'raw',
                'value' => function($data) {
                    return \yii\helpers\Html::a($data['queue'], ['view', 'queue' => $data['queue']]);
                }
            ],
            'size',
            [
                'class' => 'yii\grid\ActionColumn',
                'header' => 'Actions',
                'template' => '{delete-queue}',
                'buttons' => [
                    'delete-queue' => function ($url, $model) {
                        return Html::a('<span class="glyphicon glyphicon-trash"></span>', $url, [
                            'title' => Yii::t('yii', 'Delete'),
                            'data-confirm' => Yii::t('yii', 'Are you sure you want to delete this item?'),
                            'data-method' => 'post',
                            'data-pjax' => '0',
                        ]);
                    }
                ]
            ]
        ],
    ]); ?>
</div>
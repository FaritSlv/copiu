<?php

use app\models\Buyer;
use app\models\PriceCategory;
use yii\helpers\Url;

return [
    [
        'class' => 'kartik\grid\SerialColumn',
        'width' => '30px',
    ],
    // [
    // 'class'=>'\kartik\grid\DataColumn',
    // 'attribute'=>'id',
    // ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'name',
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'user_login',
        'value' => function (Buyer $model) {
            return $model->user_id ? $model->user->login : 'Не назначен';
        },
        'label' => 'Логин'
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'user_password',
        'value' => function (Buyer $model) {
            return $model->user_id ? $model->user->open_pass : 'Не назначен';
        },
        'label' => 'Пароль'
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'pc_id',
        'filter' => PriceCategory::getList(),
        'value' => function (Buyer $model) {
            return $model->pc_id ? $model->pc->name : 'По умолчанию';
        }
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'discount',
        'content' => function (Buyer $model) {
            return Yii::$app->formatter->asPercent($model->discount, 2);
        }
//        'format' => 'percent',
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'delivery_cost',
        'format' => 'currency',
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'min_order_cost',
        'format' => 'currency',
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'work_mode',
        'filter' => Buyer::getWorkModeList(),
        'value' => function (Buyer $model) {
            return Buyer::getWorkModeList()[$model->work_mode];
        },
        'label' => 'Режим работы',
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'min_balance',
        'value' => function (Buyer $model) {
            $min_balance = Yii::$app->formatter->asCurrency($model->min_balance);
            $cur_balance = Yii::$app->formatter->asCurrency($model->balance);
            return "Мин.: {$min_balance} <br> Текущий: {$cur_balance}";
        },
        'noWrap' => true,
        'label' => 'Баланс',
        'format' => 'raw',
    ],
    [
        'class' => 'kartik\grid\ActionColumn',
        'dropdown' => false,
        'template' => '{update} {delete}',
        'vAlign' => 'middle',
        'urlCreator' => function ($action, $model, $key, $index) {
            return Url::to([$action, 'id' => $key]);
        },
        'viewOptions' => ['role' => 'modal-remote', 'title' => 'View', 'data-toggle' => 'tooltip'],
        'updateOptions' => ['role' => 'modal-remote', 'title' => 'Редактировать', 'data-toggle' => 'tooltip'],
        'deleteOptions' => [
            'role' => 'modal-remote',
            'title' => 'Удалить',
            'data-confirm' => false,
            'data-method' => false,// for overide yii data api
            'data-request-method' => 'post',
            'data-toggle' => 'tooltip',
            'data-confirm-title' => 'Вы уверены?',
            'data-confirm-message' => 'Подтвердите удаление.',
            'data-confirm-ok' => 'Удалить покупателя',
            'data-confirm-cancel' => 'Отмена',
        ],
    ],

];   
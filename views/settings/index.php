<?php
use yii\helpers\Html;
use yii\bootstrap\Modal;
use kartik\grid\GridView;
use johnitvn\ajaxcrud\CrudAsset; 

/* @var $this yii\web\View */
/* @var $searchModel app\models\search\SettingsSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Настрйоки';
$this->params['breadcrumbs'][] = $this->title;

CrudAsset::register($this);

?>
<div class="settings-index">
    <div id="ajaxCrudDatatable">
        <?php
        try {
            echo GridView::widget([
                'id' => 'crud-datatable',
                'dataProvider' => $dataProvider,
                'filterModel' => $searchModel,
                'pjax' => true,
                'columns' => require(__DIR__ . '/_columns.php'),
                'toolbar' => [
                    [
                        'content' =>
                            Html::a('<i class="glyphicon glyphicon-plus"></i>', ['create'],
                                [
                                    'role' => 'modal-remote',
                                    'title' => 'Create new Price Categories',
                                    'class' => 'btn btn-default'
                                ]) .
                            Html::a('<i class="glyphicon glyphicon-repeat"></i>', [''],
                                ['data-pjax' => 1, 'class' => 'btn btn-default', 'title' => 'Reset Grid']) .
                            '{toggleData}' .
                            '{export}'
                    ],
                ],
                'striped' => true,
                'condensed' => true,
                'responsive' => true,
                'panel' => [
                    'type' => 'primary',
                    'heading' => '<i class="glyphicon glyphicon-list"></i> Настройки',
                    'after' => '<div class="clearfix"></div>',
                ]
            ]);
        } catch (Exception $e) {
            echo $e->getMessage();
        } ?>
    </div>
</div>
<?php Modal::begin([
    "id"=>"ajaxCrudModal",
    "footer"=>"",// always need it for jquery plugin
])?>
<?php Modal::end(); ?>

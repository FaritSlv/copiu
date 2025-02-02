<?php

use app\models\BuyerAddress;
use kartik\date\DatePicker;
use kartik\select2\Select2;
use yii\base\InvalidConfigException;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\Order */
/* @var $form yii\widgets\ActiveForm */
/* @var $productsDataProvider \yii\data\ActiveDataProvider */
/* @var $orderToNomenclatureDataProvider \yii\data\ActiveDataProvider */

$title = 'Создание заказа.';
if ($model->step == 1) {
    $title = 'Создание заказа. Шаг 1.';
} elseif ($model->step == 2) {
    $title = 'Создание заказа. Шаг 2. (Формируем заказ на ' . Yii::$app->formatter->asDate($model->target_date) . ')';
} elseif ($model->step == 3) {
    $title = 'Создание заказа. Шаг 3. (Формируем заказ на ' . Yii::$app->formatter->asDate($model->target_date) . ')';
} elseif ($model->step == 4) {
    $title = 'Создание заказа. Шаг 4. (Формируем заказ на ' . Yii::$app->formatter->asDate($model->target_date) . ')';
} elseif ($model->step == 5) {
    $title = 'Заказ создан на ' . Yii::$app->formatter->asDate($model->target_date);
}
Yii::debug('Шаг: ' . $model->step, 'test');
Yii::debug($model->attributes, 'test');

$this->title = $title;
$this->params['breadcrumbs'][] = $this->title;


try {
    $this->registerJsFile('/js/order_form.js', [
        'depends' => [
            'yii\web\YiiAsset',
            'yii\bootstrap\BootstrapAsset',
        ]
    ]);
} catch (InvalidConfigException $e) {
    echo $e->getMessage();
}

try {
    $this->registerJsFile('/js/mobile_detect.min.js', [
        'depends' => [
            'yii\web\YiiAsset',
            'yii\bootstrap\BootstrapAsset',
        ]
    ]);
} catch (InvalidConfigException $e) {
    echo $e->getMessage();
}
?>
    <hr>
    <div class="order-form">
<?php $form = ActiveForm::begin(); ?>
<?php if ($model->step != 5): ?>
    <div class="buttons" style="margin-bottom: 2rem">
    <div class="row">
        <div class="col-md-2 col-sm-6">
            <?= Html::a('Отмена', ['/order/cancel', 'id' => $model->id], [
                'class' => 'btn btn-lg btn-light btn-block',
                'title' => 'Отменить формирование закзаза',
            ]) ?>
        </div>
        <?php if ($model->step == 2 || $model->step == 3): ?>
        <div class="col-md-2 col-sm-6">
            <?= Html::button('Назад', [
                'class' => 'btn btn-lg btn-info btn-block to-back',
                'title' => 'Вернуться к предыдущему шагу',
                'onClick' => 'history.go(-1);'
            ]) ?>
        </div>

        <?php if ($model->step == 2): ?>
            <div class="col-md-6">
        <?php else: ?>
            <div class="col-md-4">
        <?php endif; ?>

            <?php else: ?>
            <div class="col-md-8">
                <?php endif; ?>
            </div>
            <?php if ($model->step == 3): ?>
                <div class="col-md-2 col-sm-12">
                    <?= Html::button('Сохранить как черновик', [
                        'id' => 'save-to-draft-btn',
                        'class' => 'btn btn-lg btn-primary btn-block',
                        'title' => 'Сохранить заказ в черновики',
                    ]) ?>
                </div>
            <?php endif; ?>
            <div class="col-md-2 col-sm-12">
                <?php if ($model->step == 3): ?>
                    <?= Html::submitButton('Отправить в работу', [
                        'class' => 'btn btn-lg btn-success btn-block',
                        'id' => 'next-btn',
                        'title' => 'Сохранить и отправить',
                    ]) ?>
                <?php elseif ($model->step == 4): ?>
                    <?= Html::a('Завершить', ['/order'], [
                        'class' => 'btn btn-lg btn-success btn-block',
                        'id' => 'next-btn',
                        'title' => 'Продолжить',
                    ]) ?>
                <?php else: ?>
                    <?= Html::submitButton('Далее', [
                        'class' => 'btn btn-lg btn-success btn-block',
                        'id' => 'next-btn',
                        'title' => 'Сохранить и продолжить',
                        'style' => ($model->step == 1 && !$model->target_date) ? 'display: none;' : '',
                    ]) ?>
                <?php endif; ?>

                <?= Html::button('<i class="fa fa-spinner fa-pulse fa-fw"></i>',
                    [
                        'class' => 'btn btn-lg btn-success btn-block disabled',
                        'id' => 'fake-next-btn',
                        'style' => 'display:none'
                    ]); ?>
            </div>
        </div>
        <hr>
    </div>
    <?= $form->field($model, 'status')->hiddenInput([
        'value' => $model->status == $model::STATUS_DRAFT ?: $model::STATUS_IN_PROGRESS
    ])->label(false) ?>
<?php endif; ?>
<?php if ($model->step == 1): ?>
    <div class="row">
        <div class="col-md-3 col-xs-12 text-center"
             style="display: flex; flex-direction: column; align-items: center;">
            <?php
            try {
                echo $form->field($model, 'target_date')->widget(DatePicker::class, [
                    'type' => DatePicker::TYPE_INLINE,
                    'pluginOptions' => [
                        'todayHighlight' => true,
                        'format' => 'yyyy-mm-dd',
                        'multidate' => false,
                        'daysOfWeekDisabled' => $model->getDisabledDays(),
                        'startDate' => date('Y-m-d')
                    ],
                    'options' => [
                        // you can hide the input by setting the following
                        'style' => 'display:none'
                    ],
                    'pluginEvents' => [
                        'changeDate' => 'function(e) {  
                                    setTimeout(function(){
                                        $(".help-block").html("");
                                        $("#confirm-order-date").trigger("click");
                                    }, 500);
                                }',
                    ],
                ])->label('Выберите дату доставки');
            } catch (Exception $e) {
                echo $e->getMessage();
            } ?>
            <?= Html::button('Подтвердить дату ', [
                'class' => 'btn btn-success btn-block',
                'id' => 'confirm-order-date',
                'style' => 'display:none;'
            ]); ?>
            <!--                <input type="text" name="selected_date" id="selected-date" class="hidden">-->
        </div>
        <div class="col-md-9 col-xs-12" style="min-height: 300px;">
            <div class="nomenclature-loader" style="display: none;">
                <div class="preloader" style="color: #3c8dbc;">
                    <i class="fa fa-spinner fa-spin fa-fw fa-5x" aria-hidden="true"></i>
                </div>
            </div>
            <div class="nomenclature" style="display: none;"></div>
        </div>
    </div>
<?php elseif ($model->step == 2): ?>
    <h4>Выберите позиции и установите количество</h4>
    <?php if ($model->buyer->min_order_cost ?? 0): ?>
        <p>В случае суммы заказа меньше <?= Yii::$app->formatter->asCurrency($model->buyer->min_order_cost) ?> стоимость доставки <?= Yii::$app->formatter->asCurrency($model->buyer->delivery_cost) ?>.</p>
    <?php endif; ?>
    <div class="row">
        <!--Интервал, адрес, коментарий-->
        <div class="col-md-4 col-md-push-8">
            <div class="row">
                <div class="col-md-12 text-center">Укажите временной интервал доставки</div>
                <?php $message = \app\models\Settings::getValueByKey(\app\models\Settings::DELIVERY_MESSAGE); if ($message) : ?>
                <div class="col-md-12 alert alert-warning"><?= $message ?></div>
                <?php endif; ?>
                <div class="col-md-6 col-sm-12"><b>C</b><br><?php
                    $onchange = '$.post( "'.Yii::$app->urlManager->createUrl('order/delivery-period?start=').'"+$(this).val(), function( data ) {
                    $( "select#delivery-time-to" ).html( data );})';
                    if(isset($_POST['Order']['delivery_time_to'])) {
                        $value = $_POST['Order']['delivery_time_to'];
                        $onchange .= ".done(function() { $('select#delivery-time-to').val('$value').change(); });";
//                        $this->registerJs("$(function() { $('select#delivery-time-to').val('$value').change(); });");
                    }
                    echo Html::dropDownList('Order[delivery_time_from]',
                        $model->delivery_time_from,
                        $model->buyer->getDeliveryTimeIntervals('from'), [
                            'class' => 'form-control',
                            'id' => 'delivery_time_from',
                            'prompt' => '...',
                            'onchange'=>$onchange
                        ]) ?></div>
                <div class="col-md-6 col-sm-12"><b>По</b><br><?= Html::dropDownList('Order[delivery_time_to]',
                        $model->delivery_time_to,
                        [], [
                            'class' => 'form-control',
                            'prompt' => '...',
                            'id' => 'delivery-time-to',
                        ]) ?></div>
                <div class="error-time text-center col-sm-12">
                    <?= $form->field($model, 'error_delivery_time')->hiddenInput()->label(false) ?>
                </div>
                <div class="col-md-12">
                    <?php if ($model->buyer->addresses): ?>
                        <?php
                        try {
                            echo $form->field($model, 'delivery_address_id')
                                ->widget(Select2::class, [
                                    'data' => BuyerAddress::getList($model->buyer_id),
                                    'options' => [
                                        'prompt' => 'Выберите адрес доставки'
                                    ]
                                ])
                                ->label('Адрес доставки');
                        } catch (Exception $e) {
                            echo $e->getMessage();
                        } ?>
                    <?php endif; ?>
                </div>
                <div class="col-xs-12">
                    <div class="comment-label">
                        <b>Комментарий</b>
                        <span class="count-symbol"><?= mb_strlen($model->comment) ? '(' . mb_strlen($model->comment) . ' симв.)' : '' ?></span>
                    </div>
                    <br><?= Html::textarea('Order[comment]', $model->comment,
                        [
                            'id' => 'order-comment',
                            'class' => 'form-control',
                            'rows' => 5,
                            'placeholder' => 'Комментарий должен содержать не более 200 символов'
                        ]) ?>
                </div>
            </div>
        </div>
        <div class="col-md-8 col-md-pull-4">
            <div class="row step-2-content" style="margin-top: 10px">
                <div class="col-md-offset-3 col-md-6">
                    <div class="progress">
                        <div class="progress-bar progress-bar-striped active" role="progressbar"
                             aria-valuenow="100"
                             aria-valuemin="0" aria-valuemax="100" style="width: 100%">
                            <span>Загрузка...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php elseif ($model->step == 3): ?>
    <?= $this->render('_pre_order_form', [
        'model' => $model,
        'form' => $form,
    ]) ?>
<?php elseif ($model->step == 4): ?>
    <div class="done text-center">
        <?php if ($model->invoice_number && $model->invoice_number != 'error'): ?>
            <h4>Накладная № <?= $model->invoice_number; ?> успешно создана</h4>
        <?php else: ?>
            <h4 class="text-danger">Ошибка создания накладной</h4>
        <?php endif; ?>
        <?php if ($model->deliveryCost): ?>
            <?php if ($model->delivery_act_number && $model->delivery_act_number != 'error'): ?>
                <h4>Акт оказания услуг (доставка) <?= $model->delivery_act_number; ?> успешно создан</h4>
            <?php else: ?>
                <h4 class="text-danger">Ошибка создания акта оказания услуг</h4>
            <?php endif; ?>
        <?php endif; ?>
    </div>
<?php endif; ?>

    <div class="row">
        <div class="col-xs-12">
            <?= $form->field($model, 'id')->hiddenInput()->label(false) ?>
            <?= $form->field($model, 'buyer_id')->hiddenInput()->label(false) ?>
            <?= $form->field($model, 'step')->hiddenInput([
                'id' => 'order-step',
                'data-id' => $model->id,
            ])->label(false) ?>
        </div>
    </div>

<?php ActiveForm::end(); ?>
    </div>
<?php

$script = <<<JS
    $(function() {
        console.log('trigger');
        $('#delivery_time_from').trigger('change');
        
        var step_block = $('#order-step');
        var id = step_block.attr('data-id');
        var step = step_block.val();
        var detect = new MobileDetect(window.navigator.userAgent);
      
      if (step == 2 && detect.mobile){
          $.get('/order/get-content', {
              order_id:id,
              is_mobile:detect.mobile()
          })
          .done(function(response) {
              $('.step-2-content').html(response);
          });
      }
      $(document).on('click', '#next-btn', function() {
        $(this).parents('.buttons').find('button').attr('disabled', true);
        $(this).parents('.buttons').find('a').attr('disabled', true);
        $(this).attr('disabled', false);
        $(this).hide();
        $('#fake-next-btn').show();
    });
      $(document).on('keyup', '#order-comment', function() {
           let length = $(this).val().length; 
           let c_symbols = $('.count-symbol');
           if (length > 200){
                c_symbols.addClass('text-danger');
                c_symbols.html('(' + $(this).val().length + ' симв.)');
           } else if(length > 0) {
                c_symbols.removeClass('text-danger');
                c_symbols.html('(' + $(this).val().length + ' симв.)');
           } else {
               c_symbols.html('');
           }
        });
      
});

JS;
$this->registerJs($script);


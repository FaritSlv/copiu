<?php

/* @var $model app\models\Order */

use app\models\Settings;
use yii\helpers\Html;

/* @var $blank_id integer */
/* @var $products array */

$counter = 1;
$view_min_col = (bool)Settings::getValueByKey('check_quantity_enabled');

//Yii::debug($products, '_test');

?>
<?php if (isset($products)): ?>
<?php foreach ($products as $product): ?>
    <div class="card">
        <div class="row">
            <div class="col-md-12">
                <div class="favorite-star text-right">
                    <?= Html::a($product['is_favorite'] ? '<i class="fa fa-fw fa-star fa-2x"></i>' : '<i class="fa fa-fw fa-star-o fa-2x"></i>',
                        ['/order/order-update', 'id' => $model->id], [
                            'id' => 'change-favorite-btn',
                            'data-href' => '/favorite-product/change?id=' . $product['obtn_id'],
                            'class' => 'text-warning',
                            'title' => $product['is_favorite'] ? 'Нажмите для исключения из избранного' : 'Нажмите для включения в избранное',
                        ]); ?>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12 card-name">
                <?php if ($product['description']): ?>
                    <i class="glyphicon glyphicon-info-sign showDescription"></i>
                <?php endif; ?>
                <b> <?= $product['name'] ?></b>
            </div>
        </div>
        <div class="row" style="display: none">
            <div class="col-md-12 card-description">
                <span><?= $product['description'] ?> </span><br>
                <?php if ($view_min_col): ?>
                    <span><b>Минимальное кол-во для заказа:</b><?= $product['min_quantity'] . ' ' . $product['measure'] ?></span>
                <?php endif; ?>
            </div>
        </div>
        <div class="row">
            <div class="col-xs-6 card-count">
                <span><b>Кол-во (<?= $product['measure'] ?>):</b><br>
                      <div class="input-group">
                          <input type="number" class="form-control count-product"
                                 name="Order[count][<?= $product['obtn_id'] ?>]"
                                 aria-describedby="basic-addon2" value="<?= $product['count'] ?>">
                          <span class="input-group-btn">
                              <button class="btn btn-default count-inc"
                                      type="button" data-obtn-id="<?= $product['obtn_id'] ?>">+</button>
                              <button class="btn btn-default count-dec"
                                      type="button" data-obtn-id="<?= $product['obtn_id'] ?>">-</button>
                          </span>
                      </div>
                </span>
            </div>

            <div class="col-xs-3 card-cena">
                <span><b>Цена:</b><br><span class="product-price"><?= $product['price'] ?></span></span>
            </div>
            <div class="col-xs-3 card-amount">
                <span><b>Сумма:</b><br><span
                            class="product-total-price"><?= $product['price'] * $product['count'] ?></span> р.</span>
            </div>
        </div>
    </div>
<?php endforeach; ?>
<?php endif; ?>
<?php
$script = <<<JS
function setProduct(order_id, obtn_id, count, price){
     $.post('/order/add-product', {
            order_id: order_id,
            obtn_id:obtn_id,
            count:count,
            price:price
        })
        .fail(function (response) {
                console.log(response.responseText);
            });
};
$('.showDescription').on('click',function () {
    console.log ($(this).html());
      $(this).parent().parent().next().toggle();
  });
$('.count-inc').on('click',function () {
    let order_id = $('#order-step').attr('data-id');
    let obtn_id = $(this).attr('data-obtn-id');
    let price = $(this).parents('.card').find('.product-price').html();
    
    let input = $(this).parents('.input-group').find('input');
    let count = input.val();
    if (count.length < 1){
        count = 0;
    }
    let result = parseInt(count) + 1;
    input.val(result);
    input.parents('.card').find('.product-total-price').html(result * price);
    setProduct(order_id, obtn_id, result, price);
    
});
$('.count-dec').on('click',function () {
    let order_id = $('#order-step').attr('data-id');
    let obtn_id = $(this).attr('data-obtn-id');
    let price = $(this).parents('.card').find('.product-price').html();
    
    let input = $(this).parents('.input-group').find('input');
    let count = input.val();
    let result;
    if (count.length < 1){
        result = 0
    } else {
        if (count <= 0){
            result = 0;
        } else {
            result = parseInt(count) - 1;
        }
    }
    input.val(result);
    input.parents('.card').find('.product-total-price').html(result * price);
    setProduct(order_id, obtn_id, result, price);
});

JS;
$this->registerJs($script);
?>

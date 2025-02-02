<?php

namespace app\models;

use app\components\PostmanApiHelper;
use Yii;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "price_category_to_nomenclature".
 *
 * @property int $id
 * @property int|null $pc_id Ценовая категория
 * @property int|null $n_id Продукт (позиция номенклатуры)
 * @property float|null $price Цена продукта для ценовой группы
 *
 * @property Nomenclature $nomenclature
 * @property PriceCategory $pc
 */
class PriceCategoryToNomenclature extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'price_category_to_nomenclature';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['pc_id', 'n_id'], 'integer'],
            [['price'], 'number'],
            [
                ['n_id'],
                'exist',
                'skipOnError' => true,
                'targetClass' => Nomenclature::class,
                'targetAttribute' => ['n_id' => 'id']
            ],
            [
                ['pc_id'],
                'exist',
                'skipOnError' => true,
                'targetClass' => PriceCategory::class,
                'targetAttribute' => ['pc_id' => 'id']
            ],
            [['pc_id'], 'unique', 'targetAttribute' => ['pc_id', 'n_id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'pc_id' => 'Ценовая категория',
            'n_id' => 'Продукт (позиция номенклатуры)',
            'price' => 'Цена продукта для ценовой группы',
        ];
    }

    /**
     * Gets query for [[N]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getNomenclature()
    {
        return $this->hasOne(Nomenclature::class, ['id' => 'n_id']);
    }

    /**
     * Gets query for [[Pc]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPc()
    {
        return $this->hasOne(PriceCategory::class, ['id' => 'pc_id']);
    }

    public static function sync($data)
    {
        $skipped = 0;
        $added = 0;
        $errors = 0;
        $changed = 0;

        $xml = simplexml_load_string($data);
        $items = $xml->returnValue;
        $categories_in_base = ArrayHelper::map(PriceCategory::find()->all(), 'outer_id', 'id');
        $products_in_base = ArrayHelper::map(Nomenclature::find()->all(), 'outer_id', 'id');
        $base_cat_outer_ids = array_keys($categories_in_base);
        $base_product_outer_ids = array_keys($products_in_base);

        foreach ($items->v as $item) {
            $product_outer_id = (string)$item->i->product;

            if (!$product_outer_id) {
                //Yii::debug('Нет ID продукта. Пропускаем', 'test');
                $skipped++;
                continue;
            }

            //Yii::debug('Product outer ID: ' . $product_outer_id, 'test');
            $categories_and_prices = [];
            if ($item->i->pricesForCategories) {
                $categories_and_prices = json_decode(json_encode($item->i->pricesForCategories), true);
            }
//            Yii::debug($categories_and_prices, 'test');

            $categories_prep = isset($categories_and_prices['k']) ? $categories_and_prices['k'] : null;
            if (!is_array($categories_prep)) {
                $categories[] = (string)$categories_prep;
            } else {
                $categories = $categories_prep;
            }

            if (!$categories) {
                //Yii::debug('Нет категорий. Пропускаем', 'test');
                $skipped++;
                continue;
            }
            $prices_prep = isset($categories_and_prices['v']) ? $categories_and_prices['v'] : null;;
            if (!is_array($prices_prep)) {
                $prices[] = (string)$prices_prep;
            } else {
                $prices = $prices_prep;
            }


            if ($categories) {
//                Yii::debug($categories, 'test');
                for ($i = 0; $i < count($categories); $i++) {
//                    $category = PriceCategory::findOne(['outer_id' => $categories[$i]]);
                    $cat_outer_id = $categories[$i];
                    /** @var array $base_cat_outer_ids Внешине ключи категорий в базе */

                    if (!in_array($cat_outer_id, $base_cat_outer_ids)) {
                        //Yii::debug('Категория не найдена. Пропускаем', 'test');
                        continue;
                    }

                    $price = $prices[$i];
//                    $product = Nomenclature::findOne(['outer_id' => $product_outer_id]);

                    if (!in_array($product_outer_id, $base_product_outer_ids)) {
                        //Yii::debug("Продукт {$product_outer_id} не найден. Пропускаем", 'test');
                        continue;
                    }

                    $model = new PriceCategoryToNomenclature([
                        'pc_id' => $categories_in_base[$cat_outer_id],
                        'n_id' => $base_product_outer_ids[$product_outer_id],
                    ]);

                    $exists = PriceCategoryToNomenclature::find()
                        ->andWhere([
                            'pc_id' => $categories_in_base[$cat_outer_id],
                            'n_id' => $base_product_outer_ids[$product_outer_id]
                        ])->exists();

                    if ($exists) {
                        //Yii::debug($model->errors, 'test');
                        $model = PriceCategoryToNomenclature::find()
                            ->andWhere(['pc_id' => $model->pc_id, 'n_id' => $model->n_id])->one();
                    }

                    $model->price = $price;

                    if ($model->isNewRecord) {
                        $added++;
                    } else {
                        $changed++;
                    }

                    if (!$model->save()) {
                        Yii::error($model->errors, '_error');
                        $errors++;
                    }
                }
            }

        }

        $data = 'Синхронизация цен прошла успешно<br>';
        Settings::setValueByKey('sync_price_date', date('Y-m-d H:i:s', time()));
        if ($errors) {
            $data .= 'Ошибок: ' . $errors . '<br>';
        }
        if ($skipped) {
            $data .= 'Пропущено: ' . $skipped . '<br>';
        }

        if ($added) {
            $data .= 'Добавлено: ' . $added . '<br>';
        }

        if ($changed) {
            $data .= 'Изменено: ' . $changed . '<br>';
        }

        return [
            'success' => true,
            'data' => $data
        ];
    }

    /**
     * Синхронизирует цены для ценовых категорий
     * @param array $data Данные для импорта
     * @return array
     */
    public static function import(array $data): array
    {
        $categories_in_base = ArrayHelper::map(PriceCategory::find()->all(), 'outer_id', 'id');
        $products_in_base = ArrayHelper::map(Nomenclature::find()->all(), 'outer_id', 'id');
        $pctn_to_category = ArrayHelper::map(PriceCategoryToNomenclature::find()->all(), 'id', 'pc_id');
        $pctn_to_nomenclature = ArrayHelper::map(PriceCategoryToNomenclature::find()->all(), 'id', 'n_id');

        foreach ($data as $item) {
            $categories = [];
            $prices = [];
            $info = $item['i'];
            //Yii::debug($info, 'test');
            $product_outer_id = $info['product'];
            $prices_and_categories = $info['pricesForCategories'];

            if ($product_outer_id == '3e8ffe40-dde4-4095-8a00-e1cc5161396a'){
                Yii::warning('Gotcha! Слойка с яблоком и корицей 130г', 'test');
                Yii::warning($prices_and_categories, 'test');
                Yii::warning(round((double)$info['price']), 'test');
                Yii::warning(round((double)$info['price'][0]), 'test');
                Yii::warning($info, 'test');
            }

            $product_id = $products_in_base[$product_outer_id] ?? null;
            Yii::debug('Product ID: ' . $product_id, 'test');

            if (!$product_id) {
                //Продукт не найден в номенклатуре, пропускаем
                Yii::debug('Продукт не найден в номенклатуре, пропускаем', 'test');
                continue;
            }

            if (!$prices_and_categories){
                //Если нет ни категорий ни цен
                $price = round((double)$info['price'], 2);
                if ($price){
                    Yii::debug('New Default Price: ' . $price, 'test');
                    //Пишем цену в цену по умолчанию для продукта
                    $target_product = Nomenclature::find()->andWhere(['outer_id' => $product_outer_id])->one();
                    if ($price != $target_product->default_price){
                        $target_product->default_price = $price;
                        if (!$target_product->save()){
                            Yii::error($target_product->errors, '_error');
                        }
                        Yii::debug('Price changed', 'test');
                    } else {
                        Yii::debug('Price skipped', 'test');
                    }
                }
                continue;
            }

            //Категории
            $prep_category = $prices_and_categories['k'];
            if (!is_array($prep_category)) {
                $categories[] = $prep_category;
            } else {
                $categories = $prep_category;
            }

            //Цены
            $prep_price = $prices_and_categories['v'];
            if (!is_array($prep_price)) {
                $prices[] = $prep_price;
            } else {
                $prices = $prep_price;
            }
            //Yii::debug($prices_and_categories, 'test');
            //Yii::debug($categories, 'test');
            //Yii::debug($prices, 'test');

            for ($i = 0; $i < count($categories); $i++) {


                $category_outer_id = $categories[$i];
                $category_id = $categories_in_base[$category_outer_id];

                $pctn_in_base_cat = array_values($pctn_to_category);
                $pctn_in_base_nom = array_values($pctn_to_nomenclature);

                //Yii::debug('Категория: ' . $category_id . ' Продукт: ' . $product_id, 'test');
                //Yii::debug((int)in_array($category_id, $pctn_in_base_cat), 'test');
                //Yii::debug((int)in_array($product_id, $pctn_in_base_nom), 'test');

                if (!$category_id || !$product_id) {
                    continue;
                }

                if (in_array($category_id, $pctn_in_base_cat)
                    && in_array($product_id, $pctn_in_base_nom)) {
                    //Если и продукт и категория есть в базе, поверяем принадлежат ли они одной записи

                    //Yii::debug('Категория найдена', 'test');
                    $pctn_model = PriceCategoryToNomenclature::find()
                        ->andWhere([
                            'pc_id' => $category_id,
                            'n_id' => $product_id,
                        ])
                        ->one();

                    if (!$pctn_model){
                        //Если запись не найдена
                        $pctn_model = new PriceCategoryToNomenclature([
                            'pc_id' => $category_id,
                            'n_id' => $product_id,
                        ]);
                    }
                } else {
                    $pctn_model = new PriceCategoryToNomenclature([
                        'pc_id' =>$category_id,
                        'n_id' => $product_id,
                    ]);
                }
                $pctn_model->price = $prices[$i];
                //Yii::debug($pctn_model->attributes, 'test');

                if (!$pctn_model->save()) {
                    Yii::debug('Ошибка сохранения', 'test');
                    Yii::error($pctn_model->errors, '_error');
                }
            }
        }

        return [
            'success' => true,
        ];
    }

    /**
     * Синхронизация цен для ценовых категорий продуктов
     * @param array $prod_outer_ids UIID продукта
     * @return array
     */
    public static function syncForProducts(array $prod_outer_ids): array
    {
//        Yii::warning('syncForProducts', 'test');
        Yii::debug($prod_outer_ids, 'test');
        $products = ArrayHelper::map(Nomenclature::find()
            ->select(['id', 'outer_id'])
            ->andWhere(['IN', 'outer_id', $prod_outer_ids])
            ->all(), 'outer_id', 'id');

//        Yii::debug($products, 'test');

        $price_categories = ArrayHelper::map(PriceCategory::find()->all(), 'outer_id', 'id');

        $postmanApi = new PostmanApiHelper();

        //Получаем продукты с ценовыми категориями и ценами для ценовых категорий
        $data = $postmanApi->getPriceListItems();

        if (!$data['success']) {
            return $data;
        }

        $xml = simplexml_load_string($data['data']);

        //Проходимся по полученным продуктам
        foreach ($xml->returnValue->v as $item) {
            $json = json_encode($item->i);
            $arr = json_decode($json, true);
//            Yii::debug($arr['product'], 'test');

            if (count($arr) <= 2) {
                continue;
            }

            $product_id = $products[$arr['product']] ?? null;
//            Yii::debug($product_id, 'test');

            if (!$product_id) {
                //Продукта нет в переданном списке продуктов
//                Yii::debug('Продукта нет в переданном списке продуктов', 'test');
                continue;
            }

            $p_categories = $arr['pricesForCategories']['k'];
            $p_prices = $arr['pricesForCategories']['v'];

            if (!is_array($p_categories)) {
                $categories[] = $p_categories;
            } else {
                $categories = $p_categories;
            }

            if (!is_array($p_prices)) {
                $prices[] = $p_prices;
            } else {
                $prices = $p_prices;
            }

//            Yii::debug($categories, 'test');
//            Yii::debug($prices, 'test');



            for ($i = 0; $i < count($categories); $i++) {
                $price_category_id = $price_categories[$categories[$i]];
                /** @var PriceCategoryToNomenclature $model */
                $model = PriceCategoryToNomenclature::find()
                    ->andWhere(['pc_id' => $price_category_id, 'n_id' => $product_id])
                    ->one();

                if (!$price_category_id){
                    //Yii::debug("Категория {$categories[$i]} не найдена в базе", 'test');
                }

                if (!$model && $price_category_id) {
                    $model = new PriceCategoryToNomenclature([
                        'pc_id' => $price_category_id,
                        'n_id' => $product_id
                    ]);
                }

                if ($model){
                    $model->price = $prices[$i];
                    //Yii::debug($model->attributes, 'test');
                    if (!$model->save()) {
                        Yii::error($model->errors, '_error');
                    }
                }

            }
        }

//        Yii::debug($json, 'test');
        return [
            'success' => true,
        ];
    }
}

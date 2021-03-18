<?php

namespace app\models;

use app\models\query\NomenclatureQuery;
use Yii;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "nomenclature".
 *
 * @property int $id
 * @property string|null $name
 * @property string|null $description
 * @property string|null $outer_id
 * @property string|null $num Артикул
 * @property int|null $n_group_id Номенклатурная группа
 * @property int|null $measure_id Единица измерения
 * @property float|null $default_price Цена по умолчанию
 * @property string|null $unit_weight Вес одной единицы
 * @property string|null $unit_capacity Объём одной единицы
 * @property string|null $type Тип
 * @property array|null $count Кол-во
 * @property array|null $price Цена товара
 *
 * @property Measure $measure
 * @property NGroup $nGroup
 * @property PriceCategoryToNomenclature[] $priceCategoryToNomenclatures
 * @property OrderBlank[] $orderBlanks
 * @property OrderToNomenclature[] $orderToNomenclature
 * @property double $priceForBuyer
 */
class Nomenclature extends ActiveRecord
{

    public $count;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'nomenclature';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['description'], 'string'],
            [['n_group_id', 'measure_id'], 'integer'],
            [['default_price', 'unit_weight', 'unit_capacity'], 'number'],
            [['name', 'outer_id', 'num', 'type'], 'string', 'max' => 255],
            [
                ['measure_id'],
                'exist',
                'skipOnError' => true,
                'targetClass' => Measure::class,
                'targetAttribute' => ['measure_id' => 'id']
            ],
            [
                ['n_group_id'],
                'exist',
                'skipOnError' => true,
                'targetClass' => NGroup::class,
                'targetAttribute' => ['n_group_id' => 'id']
            ],
            [['outer_id'], 'unique'],
            ['count', 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Наименование',
            'description' => 'Описание',
            'outer_id' => 'Внещний идентификатор',
            'num' => 'Артикул',
            'n_group_id' => 'Номенклатурная группа',
            'measure_id' => 'Единица измерения',
            'default_price' => 'Цена по умолчанию',
            'unit_weight' => 'Вес одной единицы',
            'unit_capacity' => 'Объём одной единицы',
            'type' => 'Тип',
            'count' => 'Кол-во',
        ];
    }

    /**
     * Gets query for [[Measure]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getMeasure()
    {
        return $this->hasOne(Measure::className(), ['id' => 'measure_id']);
    }

    /**
     * Gets query for [[NGroup]].
     *
     * @return \yii\db\ActiveQuery|\app\models\query\NGroupQuery
     */
    public function getNGroup()
    {
        return $this->hasOne(NGroup::className(), ['id' => 'n_group_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     * @throws \yii\base\InvalidConfigException
     */
    public function getOrderBlanks()
    {
        return $this->hasMany(OrderBlank::class, ['id' => 'ob_id'])
            ->viaTable(OrderBlankToNomenclature::tableName(), ['n_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrderToNomenclature()
    {
        return $this->hasMany(OrderToNomenclature::class, ['nomenclature_id' => 'id']);
    }

    /**
     * Gets query for [[PriceCategoryToNomenclatures]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPriceCategoryToNomenclatures()
    {
        return $this->hasMany(PriceCategoryToNomenclature::className(), ['n_id' => 'id']);
    }

    /**
     * {@inheritdoc}
     * @return NomenclatureQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new NomenclatureQuery(get_called_class());
    }

    /**
     * Импорт номенклатуры
     * @param $data
     * @return array
     */
    public static function import($data)
    {
        $item_exist = Nomenclature::find()->select(['outer_id'])->column();
        $n_groups = [];
        $allowed_types = [
            'GOODS',
            'DISH',
            'PREPARED',
            'MODIFIER',
        ];
        $added_items = 0;
        $updated_items = 0;
        $skipped = 0;
        $message = '';

        /** @var NGroup $group */
        foreach (NGroup::find()->each() as $group) {
            $n_groups[$group->outer_id] = $group->id;
        }

        foreach ($data as $item) {
            if ($item['type'] && !in_array($item['type'], $allowed_types)) {
                $skipped++;
                continue;
            }

            if (in_array($item['id'], $item_exist)) {
                $model = Nomenclature::findOne(['outer_id' => $item['id']]);
                $updated_items++;
            } else {
                $model = new Nomenclature();
                $added_items++;
            }

            $n_group_id = $n_groups[$item['parent']];
            if (!$n_group_id && $item['parent']) {
                //Если группы нет в базе
                //(например если синхронизация номенклатуры запущена до синхронизации групп номенклатуры)
                //Добавляем группу
                $n_group_model = new NGroup([
                    'outer_id' => $item['parent'],
                ]);
                if (!$n_group_model->save()) {
                    Yii::error($n_group_model->errors, '_error');
                }
                $message = '<b>Синхронизируйте группы номенклатуры!</b>';
            }

            $model->name = $item['name'];
            $model->description = $item['description']?:'';
            $model->outer_id = $item['id'];
            $model->num = $item['num']?:0;
            $model->n_group_id = $n_group_id;
            $model->default_price = $item['defaultSalePrice']?:0;
            $model->unit_weight = $item['unitWeight']?:0;
            $model->unit_capacity = $item['unitCapacity']?:0;
            $model->type = $item['type'];

//            Yii::info($model->attributes, 'test');

            if (!$model->save()) {
                Yii::error($model->errors, '_error');
            }
        }

        return [
            'success' => true,
            'data' => 'Синхронизация завершена<br>'
                . 'Добавлено записей: ' . $added_items . '<br>'
                . 'Обновлено записей: ' . $updated_items . '<br>'
                . 'Пропущено: ' . $skipped . '<br>'
                . $message
        ];
    }

    /**
     * Типы елементов номенклатуры
     * https://ru.iiko.help/articles/#!api-documentations/base-types
     * параграф "Тип элемента номенклатуры"
     * @return array
     */
    public static function getTypeList()
    {
        return [
            'GOODS' => 'Товар',
            'DISH' => 'Блюдо',
            'PREPARED' => 'Заготовка',
            'SERVICE' => 'Услуга',
            'MODIFIER' => 'Модификатор',
            'OUTER' => 'Внешние товары',
            'PETROL' => 'Топливо',
            'RATE' => 'Тариф',
        ];
    }

    /**
     * Получает цену продукта для покупателя
     */
    public function getPriceForBuyer()
    {
        /** @var Users $user */
        $user = Yii::$app->user->identity;
        $buyer = $user->buyer;

        if (!$buyer || !$buyer->pc_id) return $this->default_price;

        /** @var PriceCategoryToNomenclature $pc_t_n */
        $pc_t_n = PriceCategoryToNomenclature::find()
            ->andWhere(['pc_id' => $buyer->pc_id, 'n_id' => $this->id])->one();

       return $pc_t_n->price * $buyer->discount;

    }

    /**
     * Колво продуктов в заказе
     * @param $order_id
     * @return mixed
     */
    public function getCount($order_id)
    {
        return OrderToNomenclature::find()
            ->andWhere(['nomenclature_id' => $this->id, 'order_id' => $order_id])
            ->one()->count;
    }

}

<?php

namespace app\models;

use app\models\query\OrderQuery;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "order".
 *
 * @property int $id
 * @property int|null $buyer_id Покупатель
 * @property string|null $created_at Дата создания
 * @property string|null $target_date Дата на которую формируется заказ
 * @property string|null $delivery_time_from Время доставки "от"
 * @property string|null $delivery_time_to Время доставки "до"
 * @property float|null $total_price Общая сумма заказа (включая доставку)
 * @property string|null $comment Комментарий
 * @property int $status Статус
 * @property string||null $blanks Бланки заказов
 *
 * @property Buyer $buyer
 */
class Order extends ActiveRecord
{
    const STATUS_DRAFT = 1;
    const STATUS_WORK = 2;
    const STATUS_DONE = 3;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'order';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['buyer_id', 'status'], 'integer'],
            [['created_at', 'target_date', 'delivery_time_from', 'delivery_time_to'], 'safe'],
            [['total_price'], 'number'],
            [['comment', 'blanks'], 'string'],
            [['buyer_id'], 'exist', 'skipOnError' => true, 'targetClass' => Buyer::class, 'targetAttribute' => ['buyer_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'buyer_id' => 'Покупатель',
            'created_at' => 'Дата создания',
            'target_date' => 'Дата на которую формируется заказ',
            'delivery_time_from' => 'Время доставки \"от\"',
            'delivery_time_to' => 'Время доставки \"до\"',
            'total_price' => 'Общая сумма заказа (включая доставку)',
            'comment' => 'Комментарий',
            'status' => 'Статус',
            'blanks' => 'Бланки заказов',
        ];
    }


    /**
     * Gets query for [[Buyer]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getBuyer()
    {
        return $this->hasOne(Buyer::class, ['id' => 'buyer_id']);
    }

    /**
     * {@inheritdoc}
     * @return OrderQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new OrderQuery(get_called_class());
    }

    /**
     * Список статусов заказа
     * @return array
     */
    public function getStatusList()
    {
        return [
          self::STATUS_DRAFT => 'Черновик',
          self::STATUS_DRAFT => 'В работе',
          self::STATUS_DRAFT => 'Завершен',
        ];
    }
}

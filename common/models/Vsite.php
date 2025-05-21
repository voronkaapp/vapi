<?php

namespace common\models;

use Yii;


class Vsite extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'vsite';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['created_at', 'updated_at'], 'integer'],
            [['email', 'role', 'status'], 'string', 'max' => 255],
            [['vclient'], 'string', 'max' => 8],
            [['vsite'], 'string', 'max' => 48],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
            'email' => 'email',
            'role' => 'role',
            'status' => 'status',
            'vclient' => 'vclient',
            'vsite' => 'vsite',
        ];
    }
}

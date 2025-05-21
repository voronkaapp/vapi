<?php

namespace common\models;

use Yii;


class Hostserver extends \yii\db\ActiveRecord
{
    public static function getActiveServer($type='shared')
    {
        //вернуть параметры доступного сервера. по умолчанию среди общих серверов. если нужно personal создать (для крупного клиента отдельный хост) то это передается в параметрах

        //вернем все параметры
        return Hostserver::find()->where(['status'=>'active', 'type'=>$type])->orderBy(['counthostmax' => SORT_DESC, 'countactivehost'=> SORT_ASC])->one();
    }

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'hostserver';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['created_at', 'countemptyhost', 'counthostmax', 'countactivehost'], 'integer'],
            [['ipserver',  'plandefault', 'groupdefault', 'operator'], 'string', 'max' => 255],
            [['type', 'status','pwd', 'dbrootpwd', 'php_version', 'bridge'], 'string', 'max' => 50],
        ];
    }

    /**
     * {@inheritdoc}
     *  `id` int(11) NOT NULL,
    `ipserver` varchar(255) NOT NULL,
    `status` varchar(50) NOT NULL,
    `pwd` varchar(50) NOT NULL,
    `countactivehost` int(11) NOT NULL,
    `counthostmax` int(11) NOT NULL,
    `plandefault` varchar(255) NOT NULL,
    `groupdefault` varchar(255) NOT NULL,
    `countemptyhost` int(11) NOT NULL,
    `operator` varchar(255) NOT NULL,
    `created_at` int(11) NOT NULL
     */

    public function attributeLabels()
    {
        return [
            'id' => 'id',
            'ipserver' => 'ipserver',
            'php_version' => 'php_version',
            'bridge' => 'bridge',
            'status' => 'status',
            'pwd' => 'pwd',
            'dbrootpwd' => 'dbrootpwd',
            'countactivehost' => 'countactivehost',
            'counthostmax' => 'counthostmax',
            'plandefault' => 'plandefault',
            'groupdefault' => 'groupdefault',
            'countemptyhost' => 'countemptyhost',
            'operator' => 'operator',
            'created_at' => 'created_at',
            'type' => 'type',
        ];
    }
}

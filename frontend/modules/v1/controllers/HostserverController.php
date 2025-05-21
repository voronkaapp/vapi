<?php

namespace frontend\modules\v1\controllers;

use frontend\controllers\BaseApiController;
use frontend\modules\v1\models\Hostserver;
use Yii;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;

class HostserverController extends BaseApiController
{
    public $modelClass = Hostserver::class;

    public function actionHostserverexists(){

        $request = Yii::$app->request;
        //$params = $request->bodyParams; // возвращает все параметры
        $vsite = $request->getBodyParam('vsite'); // возвращает параметр "vsite"

        $vclient=Hostserver::find()->select('vclient')->where(['status'=>'active', 'vsite'=>$vsite])->one();

        return ['success'=> true,
            'result'=>[
                'vsiteexists'=>$vclient ? 1 : 0,
                'vsite'=>$vsite ? $vsite : '',
                'vclient'=>$vclient ? $vclient['vclient'] : '',

            ]
        ];

       /* return ArrayHelper::map(
            Hostserver::find()
                ->select('vclient')
                ->where(['status'=>'active', 'vsite'=>$vsite])
                ->all()
            ,'vclient','vclient'); //->where(['status'=>'active'])*/


    }



    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['access'] = [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ];
        return $behaviors;
    }

    public function actionColors(){
        return ['yes it is v1'];
    }
}

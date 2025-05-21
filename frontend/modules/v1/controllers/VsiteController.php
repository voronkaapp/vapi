<?php

namespace frontend\modules\v1\controllers;

use frontend\controllers\BaseApiController;
use frontend\modules\v1\models\Vsite;
use Yii;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use yii\db\Query;

class VsiteController extends BaseApiController
{
    public $modelClass = Vsite::class;


    public function actionVsiteexists(){

        $request = Yii::$app->request;
        //$params = $request->bodyParams; // возвращает все параметры
        $vsite = $request->getBodyParam('vsite'); // возвращает параметр "vsite"

        $vclient=Vsite::find()->select('vclient')->where(['status'=>'active', 'vsite'=>$vsite])->one();

        return ['success'=> true,
            'result'=>[
                'vsiteexists'=>$vclient ? 1 : 0,
                'vsite'=>$vsite ? $vsite : '',
                'vclient'=>$vclient ? $vclient['vclient'] : '',

            ]
        ];

       /* return ArrayHelper::map(
            Vsite::find()
                ->select('vclient')
                ->where(['status'=>'active', 'vsite'=>$vsite])
                ->all()
            ,'vclient','vclient'); //->where(['status'=>'active'])*/


    }



    public function actionEmailexists(){

        $request = Yii::$app->request;
        //$params = $request->bodyParams; // возвращает все параметры
        $email = $request->getBodyParam('email'); // возвращает параметр "email"

        $vclientarray= (new Query())->select('vclient')->from('vsite')->where(['email'=>$email, 'status'=>'active'])->one(); //

        return ['success'=> true,
            'result'=>[
                'emailexists'=>$vclientarray['vclient'] ? 1 : 0,
                'email'=>$email ? $email : '',
                'vclient'=>$vclientarray['vclient'] ? $vclientarray['vclient'] : '',
            ]
        ];
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

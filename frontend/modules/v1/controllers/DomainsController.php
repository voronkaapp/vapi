<?php

namespace frontend\modules\v1\controllers;

use frontend\controllers\BaseApiController;
use frontend\modules\v1\models\Vsite;
use Yii;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use yii\db\Query;

class DomainsController extends BaseApiController
{
    public $modelClass = \frontend\modules\v1\models\Domains::class;


    public function actionDeletednsrecordchallenge(){
        $request = Yii::$app->request;
        $params = $request->bodyParams; // возвращает все параметры
        //$vsite = $request->getBodyParam('vsite'); // возвращает параметр "vsite"
        $domain = $request->getBodyParam('host');


        $result=\common\models\Domains::deldnsrecord($domain,  $params);

        return $result;
    }
    public function actionNewdnsrecord(){

        $request = Yii::$app->request;
        $params = $request->bodyParams; // возвращает все параметры
        //$vsite = $request->getBodyParam('vsite'); // возвращает параметр "vsite"
        $domain = $request->getBodyParam('host');
        $challenge = $request->getBodyParam('answer');

        $result=\common\models\Domains::newdnsrecord($domain, $challenge, $params);

        return $result;

        //$vclient=Vsite::find()->select('vclient')->where(['status'=>'active', 'vsite'=>$vsite])->one();

        /*return ['success'=> true,
            'result'=>[
                'vsiteexists'=>$vclient ? 1 : 0,
                'vsite'=>$vsite ? $vsite : '',
                'vclient'=>$vclient ? $vclient['vclient'] : '',

            ]
        ];*/

       /* return ArrayHelper::map(
            Vsite::find()
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

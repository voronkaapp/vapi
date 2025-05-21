<?php

namespace frontend\modules\v1;

use yii\filters\AccessControl;
use yii\filters\auth\CompositeAuth;
use yii\filters\auth\HttpBasicAuth;
use yii\filters\auth\HttpHeaderAuth;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\auth\QueryParamAuth;

/**
 * v1 module definition class
 */
class Module extends \yii\base\Module
{
    /**
     * {@inheritdoc}
     */
    public $controllerNamespace = 'frontend\modules\v1\controllers';

    /**
     * {@inheritdoc}
     */

    public function init()
    {
        parent::init();
        \Yii::$app->user->enableSession = false; //запретим сохранять сессию, т.к. API подразумевает отсутствие куки и сессий
    }


    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['authenticator'] = [
            'class' => CompositeAuth::class, //оставим все варианты авторизаций
            'authMethods' => [
                HttpHeaderAuth::class,//это наш основной инструмент авторизации 'X-Api-Key'  и токен из базы данных на сервере

                HttpBearerAuth::class,//это наш основной инструмент авторизации Bearer и токен из базы данных на сервере

               // HttpBasicAuth::class, /// это логин и пароль. для WEB чтобы пользователь вводил.
               // QueryParamAuth::class,  // &access-token=...  передача  в гет токена . в пост нельзя. только гет
            ],

        ];

        return $behaviors;
    }



}

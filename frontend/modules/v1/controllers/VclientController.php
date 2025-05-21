<?php
namespace frontend\modules\v1\controllers;

\set_time_limit(1200);

use frontend\controllers\BaseApiController;
use frontend\modules\v1\models\Vclient;
use frontend\modules\v1\models\Hostserver;
use Yii;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;


class VclientController extends BaseApiController
{
    public $modelClass = Vclient::class;

    public function actionVclientcreateregister(){



        $request = Yii::$app->request;
        $params = $request->bodyParams; // возвращает все параметры
        $params['vsite'] = $request->getBodyParam('vsite'); // возвращает параметр "vsite"



        $result=\common\models\Vclient::_createregisterVclientVsite($params);

        return $result;

    }

    public function actionVclientregisterOldDeleteMe(){

        //ответ базовый с функцией
        $response=[];
        $response['success']=true;
        $response['vclient']='';
        $response['result']=[];
        $response['result']['func']='VclientController::actionVclientregister';

        $response['tracking']=[];
        $response['tracking']['messages']=[];
        $response['tracking']['results']=[];

        $request = Yii::$app->request;

        $paramscreateuser=[];

        $response['params']=$params = $paramscreateuser['params']= $request->bodyParams; // возвращает все параметры
        $vsite = $request->getBodyParam('vsite'); // возвращает параметр "vsite"

        $response['tracking']['messages'][]='ok_get_bodyParams_from_Request ';
        $response['tracking']['results'][]=$params;
        //проверим $vsite на корректность (принадлежность к урл. вообще это делается на фронт
        //1 длина с учетом нашего домена 2 уровня должна быть не длиннее чем 43 символа
        //2 символы должны быть ...

        if(!$vsite){
            $response['result']['vclient']='';
            $response['success']=false;
            $response['result']['message']='unavailable vsite';
            return $response;
        }

        $response['result']['vsite']=$vsite;
        //0 проверим что это человек а не бот нам заливает
        //[TODO]Vclient-доработка проверка на живого пользователя а не робота бота

        if(!$params['order_data']['email']){
            $response['success']=false;
            $response['result']['message']='no email ';
            return $response;
        }
        $useremail=$params['order_data']['email'];

        if(!$params['order_data']['password']){
            $response['success']=false;
            $response['result']['message']='no password ';
            return $response;
        }
        $userpwd=$params['order_data']['password'];
        //переводем пароль в хэш тут чтобы напрямую внести в таблицу.
        //$userpwd_hash=crypt($userpwd, '$1$f1579dd9782acd47b6833ba30dc49ac3cef088dc');

        $userpwd_hash=\password_hash($userpwd, PASSWORD_BCRYPT, ['cost' => 10] );
        //$userpwd_hash=\password_hash($userpwd, PASSWORD_DEFAULT );



       // $vclient=$response['vclient']=$vclientarray['vclient'];
       // $response['vclient']=$vclient;

        //Asia/Krasnoyarsk ??? пока не знаю но надо будет доделать

        $vclient='';

        //------------------------------------------------------------------------------------------------

        //внесем сайт в NS

        $params=[];

        //заберем сервер с которым дальше будем работать
        $serverHost=self::getServerHost();






        //----------------------------------------------------------------------------------------------------




print_r($response); die;

        //заберем недостающие данные по Hostserver из базы
        $serverHost=[];//Hostserver::find()->where(['ipserver'=>$vclientarray['ipserver']])->one();












        //1 создадим мастер-пользователя на целевой СРМ



        $paramscreateuser['url']=$vclient.'.'.$vclientarray['domaincrm'];

        $paramscreateuser['vclientarray']=$vclientarray;
        $paramscreateuser['userdata']= [
            'user_name'=>$useremail,
            'first_name'=>$params['order_data']['firstname'],
            'last_name'=>'',
            'user_password_hash'=>$userpwd_hash,
            'user_password'=>$userpwd,
            'confirm_password'=>$userpwd,
            'confirm_password_hash'=>$userpwd_hash,
            'email1'=>$useremail,
            'roleid'=>"H47",
        ];

        $response['tracking']['messages'][]='ok array paramscreateuser';
        $response['tracking']['results'][]=$paramscreateuser;


        /*
        $paramscreateuser['user_name']=$useremail;
        $paramscreateuser['user_password']=$userpwd;
        $paramscreateuser['user_firstname']=$params['order_data']['firstname'];
        $paramscreateuser['user_email']=$useremail;
        */












        /*$statusns2=Vclient::requestNsBrainyCP($params); //функция отправки запроса через api панельки

        $response['tracking']['messages'][]='ok cname domen portal';
        $response['tracking']['results'][]=$statusns2;
        if(!$statusns1['success']){
            return [
                'success'=>false,
                'result'=>[
                    'func'=>'Vclient::createVclient',
                    'vclient'=>$vclient,
                    'message'=>'error add ns',
                    'result'=>$statusns['result']
                ]
            ];
        }*/


        //API панели долго отвечает. 2,5 секунд+ на запрос попробуем без этого, а через обращение на сайт//проблема с открытием сайтов. где то еще не доделано. - через подмену задержки . сайт не сразу открвается. сначала ошибка 500, потом секунд 20 сертификат. только через несколько минут начинает нормально работать. идем через апи. видимо кэши должны сбрасываться как то.

        //1 доббавим алиас к сайту СРМ
        /*$params['postfields']=['login','pass','module','subdo','server_control','edit_domain','ip','ips','domains','aliases','dir','php_version','bridge','user_edit']; //обозначим что нам нужно будет отправить на сервер какие поля важно, т.к. там может быть хлам 'IP','ip',

        $params['login']='root'; //
        $params['pass']=$serverHost['pwd']; //root pwd
        $params['module']='server_control';
        $params['subdo']='edit_domain';
        $params['server_control']='server_control';
        $params['edit_domain']='edit_domain';
        $params['ip']=$serverHost['ipserver'];
        $params['ips']=$serverHost['ipserver'];
        $params['user_edit']=$vclient;
        $params['php_version']=$serverHost['php_version'];//'php74w';
        $params['bridge']=$serverHost['bridge'];//'fastcgi';
        $params['domen']=Vclient::getDomenCRM()['crm'];
        $params['domains']=$vclient.".".$params['domen'];
        $params['dir'] = "/sites/".$vclient.".".$params['domen']."/public_html";
        $params['aliases']=$vsite.".".$params['domen']; //'www.'.$vclient.'.'.$params['domen'].', '.

        $result=Vclient::requestCRMBrainyCP($serverHost['ipserver'], $params);
        if(!$result['success']){
            //ошибка создания юзера на хосте
            $response['success']=false;
            $response['result']['message']='error ADD alias  site on '.$serverHost['ipserver'];
            $response['result']['params']=$params;
            $response['result']['result']=$result;
            return $response;
        }

        $response['tracking']['messages'][]='ok UPDATE aliace site crm';
        $response['tracking']['results'][]=$params;
        $response['tracking']['results'][]=$result;




        //1 добавим алиас к порталу

        $params['domen'] = $params['domenportal']=Vclient::getDomenCRM()['portal'];
        $params['domains'] = $vclient.".".$params['domen'];
         $params['dir'] = "/sites/".$vclient.".".$params['domen']."/public_html/vportal";

        $params['aliases']=$vsite.".".$params['domen'];

        $result=Vclient::requestCRMBrainyCP($serverHost['ipserver'], $params);
        if(!$result['success']){
            //ошибка создания юзера на хосте
            $response['success']=false;
            $response['result']['message']='error ADD ALIAS PORTAL on '.$serverHost['ipserver'];
            $response['result']['params']=$params;
            $response['result']['result']=$result;
            return $response;
        }

        $response['tracking']['messages'][]='ok UPDATE aliace site PORTAL';
        $response['tracking']['results'][]=$params;
        $response['tracking']['results'][]=$result;
*/



       //добавим алиасы через скрипт, т.к. панельнка сносит айпишник.
        // 2 изменим логин, пароль на сервере, вставим в настройках название сайта //не актуально поменяем через vip исправим айпишник на сервере, т.к. командой нам его снесло


        $result=Vclient::createUserInCRM($paramscreateuser);

        if(!$result['success']){
            $response['success']=false;
            $response['messages']='error createUserInCRM';

            return $response;
        }
        $response['tracking']['messages'][]='ok createUserInCRM update ip site';
        $response['tracking']['results'][]=$result;


        //3  создадим запись сайта в нашей таблице
        $db= Vclient::getDb();
        $db->createCommand()
            ->insert('vsite', [
                'status' => 'active',
                'role' => 'master',
                'vclient'=>$vclient,
                'vsite'=>$vsite,
                'email'=>$useremail,
                'created_at'=>date("U"),
            ] )
            ->execute();

        $lastVsiteid=$db->getLastInsertID();

        //4  свяжем эту запись с vclient в нашей локальной таблице
        $param=[];
        $param['status']='work';
        $param['updated_at']=date('U');
        $param['vsiteid']=$lastVsiteid;
        $param['vsite']=$vsite;
        $db->createCommand()
            ->update(Vclient::tableName(), $param, ['vclient'=>$vclient] )
            ->execute();


        $response['tracking']['messages'][]='ok update vclient vsite ';
        $response['tracking']['results'][]=$param;

        $response['vclient']=$vclient;

        return $response;
    }

    public function actionCreatevclient(){
        //дублирование console для ручного контроля

        $vclient=\common\models\Vclient::find()->select('vclient')->where(['status'=>'empty'])->orderBy('id')->scalar();

        if(!$vclient) {
            //свободного нет.
            // вызовем функцию создания клиента. в ответе будет $vclient

            //проверим что не зависли на создании предыдущего
            $exists=\common\models\Vclient::find()->where(['status'=>'create'])->orderBy('id')->limit(1)->exists();

            if(!$exists) {
                $result=\common\models\Vclient::createVclient();

                return $result;

            }
            else{

                return ['exit. in create'];
            }
            //иначе просто через минуту еще раз запустится.
        }
        return ['exists empty host'];
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


}

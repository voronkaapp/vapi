<?php

namespace common\models;

use Yii;
use yii\helpers\ArrayHelper;
use yii\db\Connection;
use yii\db\Expression;
use yii\db\Query;
use yii\di\Instance;

class Vclient extends \yii\db\ActiveRecord
{
    public static $countrecursive; //на случай работы с рекурсиями контроль чтобы не уйти в бесконечность точка выхода

    public static $trackingdebug=1;  //собираем лог в сам ответ

    public static function _createregisterVclientVsite($requestarray=[]){

        if(!$requestarray){
            return [
                'success'=>false,
                'result'=>[
                    'func'=>'Vclient::_registerVclientVsite',
                    'requestarray'=>$requestarray,
                    'message'=>'no requestarray',
                    'result'=>[]
                ]
            ];
        }

        $vsite=$requestarray['vsite'];

        if(!$vsite){
            return [
                'success'=>false,
                'result'=>[
                    'func'=>'Vclient::_registerVclientVsite',
                    'requestarray'=>$requestarray,
                    'message'=>'no vsite',
                    'result'=>[]
                ]
            ];
        }

        //ответ базовый с функцией
        $response=[];
        $response['success']=true;
        $response['func']='Vclient::_registerVclientVsite';
        $response['tracking']=[];
        $response['result']=[];
        $trackii=0;


        $vclient='';



        //заберем сервер с которым дальше будем работать
        $serverHost=self::getServerHost();

        if(self::$trackingdebug){
            $response['tracking'][$trackii]['messages']='0 _ get  serverHost = OK';
            $response['tracking'][$trackii]['serverHost']=$serverHost;
        }


        $params=[];

        $params['vsite']=$requestarray['vsite'];


        //1 определим IP сервера на котором будем вести работу
        $iphost=$serverHost['ipserver'];

        if(!$iphost){
            return [
                'success'=>false,
                'result'=>[
                    'func'=>'Vclient::_registerVclientVsite',
                    'vclient'=>$vclient,
                    'message'=>'no get ip host',
                    'result'=>[]
                ]
            ];
        }

        $params['iphost']=$iphost; //params['iphost'];

        //заберем root пароли сервера по этому IP
        $rootPass=$serverHost['pwd'];
        if(!$rootPass){
            return [
                'success'=>false,
                'result'=>[
                    'func'=>'Vclient::_registerVclientVsite',
                    'vclient'=>$vclient,
                    'message'=>'no get root_password  host ip',
                    'result'=>$serverHost
                ]
            ];
        }


        $idserverhost=$serverHost['id'];

     //----------------------------------------------------------------------------------------------------
        //определимся с именем VCLIENT. оно у нас сквозное. возьмем last вставленного ID и забронируем его в базе данных.
        //имя логина должно содержать не менее 4х символов (ограничение панели) и не более 8 символов (ограничение линукс).
        //первой цифров будем номер сервера маркировать. чтобы визуально. остальные 7 случайное число. будем перебирать пока не найдем уникальное значение

        self::$countrecursive=0;

        $vclient=self::getUniqVclient($idserverhost);

        if(!$vclient){
            return [
                'success'=>false,
                'result'=>[
                    'func'=>'Vclient::_registerVclientVsite',
                    'vclient'=>'',
                    'maxbdlastvclientnumber'=>999999,
                    'message'=>'no insert and get id new vclient in '.self::tableName(),
                    'result'=>[]
                ]
            ];
        }


        if(self::$trackingdebug){
            $trackii++;
            $response['tracking'][$trackii]['messages']='1 _ create OK vclient';
            $response['tracking'][$trackii]['vclient']=$vclient;
        }

        $params['vclient']=$vclient;



       //print_r($response); die;


        //----------------------------------------------------------------------------------------------------------------------------------------------------
        //0. NS команда добавить связку vclient  и $iphost в NS1 и NS2 и NS{.}, а vsite будет идти CNAME потом к этим записям. сейчас мы делаем опрежающий пустой инстанс
        $params['operationns']='add';
        $params['domen']=self::getDomenCRM()['crm'];

        $statusns1=self::requestNsBrainyCP($params);


        if(!$statusns1['success']){
            return [
                'success'=>false,
                'result'=>[
                    'func'=>'Vclient::_registerVclientVsite',
                    'vclient'=>$vclient,
                    'message'=>'error add ns',
                    'result'=>$statusns1['result']
                ]
            ];
        }

        if(self::$trackingdebug){
            $trackii++;
            $response['tracking'][$trackii]['messages']='ok insert NS vsite ip CRM';
            $response['tracking'][$trackii]['vclient']=$vclient;
            $response['tracking'][$trackii]['statusns1']=$statusns1;
        }


    //-------------------------------------------------------------------------------------------------------------------------------------
      //1   // HOST команда добавить хост на нужном сервере $iphost. логин=Vclient.   пароль случайно генерируемый. он нам не нужен.
        //сгенерим пароль для пользователя и базы данных
        $userhostpwd=substr(md5(mt_rand()), -10);

        $params['login']='root'; //
        $params['pass']=$serverHost['pwd']; //root pwd
        $params['module']='hostacc';
        $params['hostacc']='hostacc';
        $params['subdo']='adduseracc';
        $params['adduseracc']='adduseracc';
        $params['ps']=$userhostpwd;
        $params['lg']=$params['vclient'];
        $params['group']=$serverHost['groupdefault'];
        $params['plan']=$serverHost['plandefault'];
        $params['ip']=$serverHost['ipserver']; //мы клиенту даем тот же айпишник что и у сервера. пока нет кейса давать другой айпишник.

        $params['postfields']=['login','pass','module','subdo','hostacc','adduseracc','lg','ps','plan','group','ip']; //обозначим что нам нужно будет отправить на сервер какие поля важно, т.к. там может быть хлам


        $result=self::requestCRMBrainyCP($serverHost['ipserver'], $params);




        if(!$result['success']){
            //ошибка создания юзера на хосте
            $response['success']=false;
            $response['result']['message']='error create host '.$serverHost['ipserver'];
            $response['result']['params']=$params;
            $response['result']['result']=$result;
            return $response;
        }

        if(self::$trackingdebug){
            $trackii++;
            $response['tracking'][$trackii]['messages']='2 ok create vclient on host';
            $response['tracking'][$trackii]['vclient']=$vclient;
            $response['tracking'][$trackii]['hostuser']=$result;
        }

       // print_r($response); die;


    //---------------------------------------------------------------------------------------------------------------------
    //2. SITE  на этом хосте добавить  сайт v111.voronka.pro
        $params['postfields']=['login','pass','module','subdo','server_control','add_domain','domains','aliases','dir','php_version','bridge','ip','user_edit']; //обозначим что нам нужно будет отправить на сервер какие поля важно, т.к. там может быть хлам

        $params['module']='server_control';
        $params['subdo']='add_domain';
        $params['server_control']='server_control';
        $params['add_domain']='add_domain';

        $params['php_version']=$serverHost['php_version'];//'php74w';
        $params['bridge']=$serverHost['bridge'];//'fastcgi'; fpm
        $params['ip']=$serverHost['ipserver'];
        $params['user_edit']=$vclient;

        $params['aliases']=$vsite.".".$params['domen'];

        $params['dir']="".$vclient.".".$params['domen']."/vportal";

        $params['domains']=$vclient.".".$params['domen'];

        $result=self::requestCRMBrainyCP($serverHost['ipserver'], $params);

        if(!$result['success']){
            //ошибка создания юзера на хосте
            $response['success']=false;
            $response['result']['message']='error create site on '.$serverHost['ipserver'];
            $response['result']['params']=$params;
            $response['result']['result']=$result;
            return $response;
        }

        if(self::$trackingdebug){
            $trackii++;
            $response['tracking'][$trackii]['messages']='ok create site crm';
            $response['tracking'][$trackii]['vclient']=$vclient;
            $response['tracking'][$trackii]['result']=$result;
        }


    //------------------------------------------------------------------------------------------------------------------
    // 6  SSL к сайту прицепить сертификат SSL который ссылки сделаны скриптом. два домена. два запроса
        $params['postfields']=['login','pass','module','subdo','certs_control','savedomaincerts','domain','key','panel_user']; //обозначим что нам нужно будет отправить на сервер какие поля важно, т.к. там может быть хлам
        $params['login']='root';
        $params['pass']=$serverHost['pwd'];
        $params['module']='certs_control';
        $params['certs_control']='certs_control';
        $params['subdo']='savedomaincerts';
        $params['savedomaincerts']='savedomaincerts';
        $params['panel_user']=$vclient;

        $params['domain']=$vclient.'.'.$params['domen'];

        $params['key']=$params['domen'].'_wildcart_autorenew_letsen';

        $result=self::requestCRMBrainyCP($serverHost['ipserver'], $params);

        if(!$result['success']){
            //ошибка создания  на хосте
            $response['success']=false;
            $response['result']['message']='error  SSL DOMAIN CRM  '.$serverHost['ipserver'];
            $response['result']['params']=$params;
            $response['result']['result']=$result;
            return $response;
        }

        if(self::$trackingdebug){
            $trackii++;
            $response['tracking'][$trackii]['messages']='ok  add SSL DOMAIN CRM';
            $response['tracking'][$trackii]['result']=$result;
        }


    //------------------------------------------------------------------------------------------------------------------------------

        // 3. BD база данных: создаем пользователя, базу и  логин делается как VCLIENT_VCLIENT //пользователя создаем через апи. только пароль нужно задать этот H9x2B7n9
        $params['postfields']=['login','pass','module','subdo','dbusage','add_user','login_user','password_user','user_edit']; //обозначим что нам нужно будет отправить на сервер какие поля важно, т.к. там может быть хлам
        $params['module']='dbusage';
        $params['subdo']='add_user';
        $params['dbusage']='dbusage';
        $params['add_user']='add_user';
        $params['password_user']=$userhostpwd;
        $params['login_user']=$vclient;
        $params['user_edit']=$vclient;

        $result=self::requestCRMBrainyCP($serverHost['ipserver'], $params);

        if(!$result['success']){
            //ошибка создания  на хосте
            $response['success']=false;
            $response['result']['message']='error create DB USER  '.$serverHost['ipserver'];
            $response['result']['params']=$params;
            $response['result']['result']=$result;
            return $response;
        }

        if(self::$trackingdebug){
            $trackii++;
            $response['tracking'][$trackii]['messages']='ok create DB USER';
            $response['tracking'][$trackii]['vclient']=$vclient;
            $response['tracking'][$trackii]['result']=$result;
        }


        //-------------------------------------------------------------------------------------------------------------------
        //теперь создадим саму базу
        $params['postfields']=['login','pass','module','subdo','dbusage','add_db','name_db','user_edit']; //обозначим что нам нужно будет отправить на сервер какие поля важно, т.к. там может быть хлам
        $params['subdo']='add_db';
        $params['add_db']='add_db';
        $params['name_db']=$vclient;
        $result=self::requestCRMBrainyCP($serverHost['ipserver'], $params);
        if(!$result['success']){
            //ошибка создания  на хосте
            $response['success']=false;
            $response['result']['message']='error create DB DB  '.$serverHost['ipserver'];
            $response['result']['params']=$params;
            $response['result']['result']=$result;
            return $response;
        }

        if(self::$trackingdebug){
            $trackii++;
            $response['tracking'][$trackii]['messages']='ok create DB DB';
            $response['tracking'][$trackii]['vclient']=$vclient;
            $response['tracking'][$trackii]['result']=$result;
        }

            //------------------------------------------------------------------------------------------------------------------------

        //свяжем базу и пользователя
        $params['postfields']=['login','pass','module','subdo','dbusage','add_user_db','name_db','name_user','user_edit','privilegies']; //обозначим что нам нужно будет отправить на сервер какие поля важно, т.к. там может быть хлам
        $params['subdo']='add_user_db';
        $params['add_user_db']='add_user_db';
        $params['privilegies']='all_privilegies';
        $params['name_db']=$vclient."_".$vclient;
        $params['name_user']=$vclient."_".$vclient;

        $result=self::requestCRMBrainyCP($serverHost['ipserver'], $params);

        if(!$result['success']){
            //ошибка создания  на хосте
            $response['success']=false;
            $response['result']['message']='error  DB + USER  '.$serverHost['ipserver'];
            $response['result']['params']=$params;
            $response['result']['result']=$result;
            return $response;
        }


        if(self::$trackingdebug){
            $trackii++;
            $response['tracking'][$trackii]['messages']='ok  DB + USER';
            $response['tracking'][$trackii]['vclient']=$vclient;
            $response['tracking'][$trackii]['result']=$result;
        }

        //------------------------------------------------------
        //-------------------------------------------------------------------------------------------------------------------
        //теперь создадим  базу для базы данных
        $params['postfields']=['login','pass','module','subdo','dbusage','add_db','name_db','user_edit']; //обозначим что нам нужно будет отправить на сервер какие поля важно, т.к. там может быть хлам
        $params['subdo']='add_db';
        $params['add_db']='add_db';
        $params['name_db']='kb';
        $result=self::requestCRMBrainyCP($serverHost['ipserver'], $params);
        if(!$result['success']){
            //ошибка создания  на хосте
            $response['success']=false;
            $response['result']['message']='error create kb  DB  '.$serverHost['ipserver'];
            $response['result']['params']=$params;
            $response['result']['result']=$result;
            return $response;
        }

        if(self::$trackingdebug){
            $trackii++;
            $response['tracking'][$trackii]['messages']='ok create DB DB';
            $response['tracking'][$trackii]['vclient']=$vclient;
            $response['tracking'][$trackii]['result']=$result;
        }

        //------------------------------------------------------------------------------------------------------------------------

        //свяжем базу и пользователя
        $params['postfields']=['login','pass','module','subdo','dbusage','add_user_db','name_db','name_user','user_edit','privilegies']; //обозначим что нам нужно будет отправить на сервер какие поля важно, т.к. там может быть хлам
        $params['subdo']='add_user_db';
        $params['add_user_db']='add_user_db';
        $params['privilegies']='all_privilegies';
        $params['name_db']=$vclient."_".'kb';
        $params['name_user']=$vclient."_".$vclient;

        $result=self::requestCRMBrainyCP($serverHost['ipserver'], $params);

        if(!$result['success']){
            //ошибка создания  на хосте
            $response['success']=false;
            $response['result']['message']='error  DB + USER  '.$serverHost['ipserver'];
            $response['result']['params']=$params;
            $response['result']['result']=$result;
            return $response;
        }


        if(self::$trackingdebug){
            $trackii++;
            $response['tracking'][$trackii]['messages']='ok  DB + USER';
            $response['tracking'][$trackii]['vclient']=$vclient;
            $response['tracking'][$trackii]['result']=$result;
        }

    //------------------------------------------------------------------------------------------------------------------------------
        // 5. CRON установить CRON задачу для этого хоста
        $params['postfields']=['login','pass','module','subdo','crontab','addcommcron','panel_user','cron_command','cron_freq_minutes','cron_freq_hours','cron_freq_days','cron_freq_months','cron_freq_weekdays']; //обозначим что нам нужно будет отправить на сервер какие поля важно, т.к. там может быть хлам
        $params['module']='crontab';
        $params['crontab']='crontab';
        $params['subdo']='addcommcron';
        $params['addcommcron']='addcommcron';
        $params['panel_user']=$vclient;
        $params['cron_command']='php /home/'.$vclient.'/sites/'.$vclient.'.'.$params['domen'].'/cron.php "'.$vclient.'" "'.$params['domen'].'"';
        $params['cron_freq_minutes']='*';
        $params['cron_freq_hours']='*';
        $params['cron_freq_days']='*';
        $params['cron_freq_months']='*';
        $params['cron_freq_weekdays']='*';

        $result=self::requestCRMBrainyCP($serverHost['ipserver'], $params);

        if(!$result['success']){
            //ошибка создания  на хосте
            $response['success']=false;
            $response['result']['message']='error  add CRON  '.$serverHost['ipserver'];
            $response['result']['params']=$params;
            $response['result']['result']=$result;
            return $response;
        }

        if(self::$trackingdebug){
            $trackii++;
            $response['tracking'][$trackii]['messages']='ok  add CRON';
            $response['tracking'][$trackii]['result']=$result;
        }









    //-------------------------------------------------------------------------------------------------
        // 4 теперь внутри сервера запустим скрипт bash. он сделает ссылки в том числе SSL ссылки на исходные сертификаты
        //$command='sshpass -p "'.$serverHost['pwd'].'" ssh root@'.$serverHost['ipserver'].' /usr/createuser.sh --vclient='.$vclient.' --vdomen='.$domenCRM.' --vdomenportal='.$domainPortal.' --password='.$userhostpwd.' --dbrootpwd='.$serverHost['dbrootpwd']; не работает. будем


        $command='/usr/createuser.sh --vsite='.$vsite.' --vclient='.$vclient.' --vdomen='.$params['domen'].' --vdomenportal='.$params['domen'].' --password='.$userhostpwd.' --dbrootpwd='.$serverHost['dbrootpwd'];



        $connection = \ssh2_connect($serverHost['ipserver'], 22);
        ssh2_auth_password($connection, 'root', $serverHost['pwd']);
        $stream = ssh2_exec($connection, $command);
        stream_set_blocking($stream, true);
        $result = "";
        while($o = \fgets($stream))  $result .= " | ".$o;
        fclose($stream);

        $response['tracking']['messages'][]='4_ok  SHELL EXEC ';
        $response['tracking']['results']['4_ok_SHELL EXEC'][]=$command;
        $response['tracking']['results']['4_ok_SHELL EXEC'][]=$result;






        //---------------------------------------------------------------------------------------------------------------------
    //1 создадим мастер-пользователя на целевой СРМ
        $paramscreateuser['url']=$vclient.'.'.$params['domen'];

        $paramscreateuser['vclientarray']=[];
        $paramscreateuser['vclientarray']['ipserver']=$serverHost['ipserver'];
        $paramscreateuser['vclientarray']['domaincrm']=$params['domen'];
        $paramscreateuser['vclientarray']['vclient']=$vclient;

        $userpwd=$requestarray['order_data']['password'];
        $userpwd_hash=\password_hash($userpwd, PASSWORD_BCRYPT, ['cost' => 10] );
        //$userpwd_hash=\password_hash($userpwd, PASSWORD_DEFAULT );

        $paramscreateuser['userdata']= [
            'user_name'=>$requestarray['order_data']['email'],
            'first_name'=>$requestarray['order_data']['firstname'],
            'last_name'=>'',
            'user_password_hash'=>$userpwd_hash,
            'user_password'=>$userpwd,
            'confirm_password'=>$userpwd,
            'confirm_password_hash'=>$userpwd_hash,
            'email1'=>$requestarray['order_data']['email'],
            'roleid'=>"H47",
        ];


        //$result=Vclient::createUserInCRM($paramscreateuser);
/*
        if(!$result['success']){
            $response['success']=false;
            $response['messages']='error createUserInCRM';
            $response['result']=$result;
            return $response;
        }

        if(self::$trackingdebug){
            $trackii++;
            $response['tracking'][$trackii]['messages']='ok  createUserInCRM ';
            $response['tracking'][$trackii]['result']=$result;
        }

*/








        //------------------------------------------------------------------------------------------------------------------
   /*     //3  создадим запись сайта в нашей таблице 1 емаил может иметь несколько СРМ
        $db= Vclient::getDb();
        $db->createCommand()
            ->insert('vsite', [
                'status' => 'active',
                'role' => 'master',
                'vclient'=>$vclient,
                'vsite'=>$vsite,
                'email'=>$requestarray['order_data']['email'],
                'created_at'=>\date('U'),
                'updated_at'=>\date('U'),
                'created'=>\date('Y-m-d H:i:s'),
                'updated'=>\date('Y-m-d H:i:s'),
            ] )
            ->execute();

        $lastVsiteid=$db->getLastInsertID();

        //4  свяжем эту запись vsite с vclient в нашей локальной таблице
        $db= Vclient::getDb();
        $db->createCommand()
            ->insert(self::tableName(), [
                'status' => 'work',
                'ipserver' => $serverHost['ipserver'],
                'vclient'=>$vclient,
                'vsite'=>$vsite,
                'vsiteid'=>$lastVsiteid,
                'created_at'=>\date('U'),
                'updated_at'=>\date('U'),
                'created'=>\date('Y-m-d H:i:s'),
                'updated'=>\date('Y-m-d H:i:s'),
            ])
            ->execute();
        //$lastvclientnumber=$db->getLastInsertID();


        Vclient::getDb()->createCommand()
            ->update('hostserver', ['countemptyhost'=>'countemptyhost+1'], ['ipserver'=>$serverHost['ipserver']] )
            ->execute();
*/
    /*Vclient::getDb()->createCommand()->update('hostserver', ['countemptyhost'=>'countemptyhost+1'], ['ipserver'=>$serverHost['ipserver']] )
            ->execute();*/

        return $response;

    }



    public static function createVclient(){

        //надо где то забрать данные по свободному серверу и запускать создание по той панельке которая стоит на том сервере
        //$result=self::_createVclientBrainyCP();

        /*if($result['success']==false){
            //что то пошло не так. удалим метку create перед тем как вернем результат
            Vclient::getDb()->createCommand()
                ->delete('vclient', [ 'vclient'=>$result['vclient'] ] )
                ->execute();
        }*/

        return $result;
    }

    public static function delVclient($vclient=''){

        $result=self::_delVclientBrainyCP($vclient);

        return $result;
    }

/*
    public static function _createVclientBrainyCP(){



        //ответ базовый с функцией
        $response=[];
        $response['success']=true;
        $response['result']=[];

        $response['tracking']=[];
        $response['tracking']['messages']=[];
        $response['tracking']['results']=[];


        $response['result']['func']='Vclient::createVclient';

        $params=[];

        $vclient='';


        //заберем сервер с которым дальше будем работать
        $serverHost=self::getServerHost();

        //1 определим IP сервера на котором будем вести работу
        $iphost=$serverHost['ipserver'];

        if(!$iphost){
            return [
                'success'=>false,
                'result'=>[
                    'func'=>'Vclient::createVclient',
                    'vclient'=>$vclient,
                    'message'=>'no get ip host for vclient',
                    'result'=>[]
                ]
            ];
        }

        $params['iphost']=$iphost;

        //заберем root пароли сервера по этому IP
        $rootPass=$serverHost['pwd'];
        if(!$rootPass){
            return [
                'success'=>false,
                'result'=>[
                    'func'=>'Vclient::createVclient',
                    'vclient'=>$vclient,
                    'message'=>'no get root_password  host ip',
                    'result'=>$serverHost
                ]
            ];
        }

        $idserverhost=$serverHost['id'];

    //определимся с именем VCLIENT. оно у нас сквозное. возьмем last вставленного ID и забронируем его в базе данных.
        //имя логина должно содержать не менее 4х символов (ограничение панели) и не более 8 символов (ограничение линукс).
            //первой цифров будем номер сервера маркировать. чтобы визуально. остальные 7 случайное число. будем перебирать пока не найдем уникальное значение

        self::$countrecursive=0;

        $vclient=self::getUniqVclient($idserverhost);
        $lastvclientnumber=0;


        $db= Vclient::getDb();
        $db->createCommand()
            ->insert(self::tableName(), [
                'status' => 'create',
                'vclient'=>$vclient,
                'created_at'=>date('U'),
                'created'=>date('Y-m-d H:i:s')
            ])
            ->execute();
        $lastvclientnumber=$db->getLastInsertID();



        if(!$vclient){
            return [
                'success'=>false,
                'result'=>[
                    'func'=>'Vclient::createVclient',
                    'vclient'=>'',
                    'bdlastvclientnumber'=>$lastvclientnumber,
                    'maxbdlastvclientnumber'=>999999,
                    'message'=>'no insert and get id new vclient in '.self::tableName(),
                    'result'=>[]
                ]
            ];
        }

        $response['result']['vclient']=$vclient;

        $params['vclient']=$vclient;

        $response['tracking']['messages'][]='ok create vclient';



        //----------------------------------------------------------------------------------------------------------------------------------------------------
        //сделаем запросы на панельку

        //0. NS команда добавить связку vclient  и $iphost в NS1 и NS2 и NS{.}, а vsite будет идти CNAME потом к этим записям. сейчас мы делаем опрежающий пустой инстанс
        $params['operationns']='add';

        $params['domen']=self::getDomenCRM()['portal'];

        $statusns2=self::requestNsBrainyCP($params);

        if(!$statusns2['success']){
            return [
                'success'=>false,
                'result'=>[
                    'func'=>'Vclient::createVclient',
                    'vclient'=>$vclient,
                    'message'=>'error add ns',
                    'result'=>$statusns2['result']
                ]
            ];
        }

        $response['tracking']['messages'][]='0_ok insert NS vclient ip PORTAL';
        $response['tracking']['results'][]=$statusns2;


        $domenCRM=self::getDomenCRM()['crm'];
        $params['domen']=$domenCRM;

        $statusns1=self::requestNsBrainyCP($params);

        if(!$statusns1['success']){
            return [
                'success'=>false,
                'result'=>[
                    'func'=>'Vclient::createVclient',
                    'vclient'=>$vclient,
                    'message'=>'error add ns',
                    'result'=>$statusns1['result']
                ]
            ];
        }

        $response['tracking']['messages'][]='0_ok insert NS vclient ip CRM ';
        $response['tracking']['results'][]=$statusns1;


        //print_r($response);
       // die;





        //сгенерим пароль для пользователя и базы данных
        $userhostpwd=substr(md5(mt_rand()), -10);


    //1. HOST команда добавить хост на нужном сервере $iphost. логин=Vclient.   пароль случайно генерируемый. он нам не нужен.
        $params['login']='root'; //
        $params['pass']=$serverHost['pwd']; //root pwd
        $params['module']='hostacc';
        $params['hostacc']='hostacc';
        $params['subdo']='adduseracc';
        $params['adduseracc']='adduseracc';
        $params['ps']=$userhostpwd;
        $params['lg']=$params['vclient'];
        $params['group']=$serverHost['groupdefault'];
        $params['plan']=$serverHost['plandefault'];
        $params['ip']=$serverHost['ipserver']; //мы клиенту даем тот же айпишник что и у сервера. пока нет кейса давать другой айпишник.
        $params['postfields']=['login','pass','module','subdo','hostacc','adduseracc','lg','ps','plan','group','ip']; //обозначим что нам нужно будет отправить на сервер какие поля важно, т.к. там может быть хлам

        $result=self::requestCRMBrainyCP($serverHost['ipserver'], $params);

        if(!$result['success']){
            //ошибка создания юзера на хосте
            $response['success']=false;
            $response['result']['message']='error create host '.$serverHost['ipserver'];
            $response['result']['params']=$params;
            $response['result']['result']=$result;
            return $response;
        }

        $response['tracking']['messages'][]='1_ok create vclient on host';
        $response['tracking']['results'][]=$result;



    //2. SITE  на этом хосте добавить 2 сайта v111.voronka.pro и v111.umdoza.pro
        $params['postfields']=['login','pass','module','subdo','server_control','add_domain','domains','aliases','dir','php_version','bridge','ip','user_edit']; //обозначим что нам нужно будет отправить на сервер какие поля важно, т.к. там может быть хлам

        $params['module']='server_control';
        $params['subdo']='add_domain';
        $params['server_control']='server_control';
        $params['add_domain']='add_domain';

        $params['php_version']=$serverHost['php_version'];//'php74w';
        $params['bridge']=$serverHost['bridge'];//'fastcgi'; fpm
        $params['ip']=$serverHost['ipserver'];
        $params['user_edit']=$vclient;

        $params['domains']=$vclient.".".$params['domen'];
        //$params['aliases']='www.'.$vclient.".".$params['domen']; //алиастом будет в будущем vsite при регистрации клиента

        $params['dir']="".$vclient.".".$params['domen']."/vportal";


        $result=self::requestCRMBrainyCP($serverHost['ipserver'], $params);
        if(!$result['success']){
            //ошибка создания юзера на хосте
            $response['success']=false;
            $response['result']['message']='error create site on '.$serverHost['ipserver'];
            $response['result']['params']=$params;
            $response['result']['result']=$result;
            return $response;
        }

        $response['tracking']['messages'][]='2_ok create site crm';
        $response['tracking']['results'][]=$result;

        //теперь еще портал клиента добавим
        $domainPortal=self::getDomenCRM()['portal'];
        $params['domains']=$vclient.".".$domainPortal;
        //$params['aliases']='www.'.$vclient.".".$domainPortal; //алиастом будет в будущем vsite при регистрации клиента

        $params['dir']="".$vclient.".".$params['domen']."/vlms/public";

        $result=self::requestCRMBrainyCP($serverHost['ipserver'], $params);
        if(!$result['success']){
            //ошибка создания  на хосте
            $response['success']=false;
            $response['result']['message']='error create site on  '.$serverHost['ipserver'];
            $response['result']['params']=$params;
            $response['result']['result']=$result;
            return $response;
        }

        $response['tracking']['messages'][]='2_ok create site portal';
        $response['tracking']['results'][]=$result;





        // 3. BD база данных: создаем пользователя, базу и  логин делается как VCLIENT_VCLIENT //пользователя создаем через апи. только пароль нужно задать этот H9x2B7n9
        $params['postfields']=['login','pass','module','subdo','dbusage','add_user','login_user','password_user','user_edit']; //обозначим что нам нужно будет отправить на сервер какие поля важно, т.к. там может быть хлам
        $params['module']='dbusage';
        $params['subdo']='add_user';
        $params['dbusage']='dbusage';
        $params['add_user']='add_user';
        $params['password_user']=$userhostpwd;
        $params['login_user']=$vclient;
        $params['user_edit']=$vclient;
        $result=self::requestCRMBrainyCP($serverHost['ipserver'], $params);
        if(!$result['success']){
            //ошибка создания  на хосте
            $response['success']=false;
            $response['result']['message']='error create DB USER  '.$serverHost['ipserver'];
            $response['result']['params']=$params;
            $response['result']['result']=$result;
            return $response;
        }
        $response['tracking']['messages'][]='3_ok create DB USER';
        $response['tracking']['results'][]=$result;




        //теперь создадим саму базу
        $params['postfields']=['login','pass','module','subdo','dbusage','add_db','name_db','user_edit']; //обозначим что нам нужно будет отправить на сервер какие поля важно, т.к. там может быть хлам
        $params['subdo']='add_db';
        $params['add_db']='add_db';
        $params['name_db']=$vclient;
        $result=self::requestCRMBrainyCP($serverHost['ipserver'], $params);
        if(!$result['success']){
            //ошибка создания  на хосте
            $response['success']=false;
            $response['result']['message']='error create DB DB  '.$serverHost['ipserver'];
            $response['result']['params']=$params;
            $response['result']['result']=$result;
            return $response;
        }
        $response['tracking']['messages'][]='3_ok create DB DB';
        $response['tracking']['results'][]=$result;

        //свяжем базу и пользователя
        $params['postfields']=['login','pass','module','subdo','dbusage','add_user_db','name_db','name_user','user_edit','privilegies']; //обозначим что нам нужно будет отправить на сервер какие поля важно, т.к. там может быть хлам
        $params['subdo']='add_user_db';
        $params['add_user_db']='add_user_db';
        $params['privilegies']='all_privilegies';
        $params['name_db']=$vclient."_".$vclient;
        $params['name_user']=$vclient."_".$vclient;

        $result=self::requestCRMBrainyCP($serverHost['ipserver'], $params);

        if(!$result['success']){
            //ошибка создания  на хосте
            $response['success']=false;
            $response['result']['message']='error  DB + USER  '.$serverHost['ipserver'];
            $response['result']['params']=$params;
            $response['result']['result']=$result;
            return $response;
        }
        $response['tracking']['messages'][]='3_ok  DB + USER';
        $response['tracking']['results'][]=$result;



    // 5. CRON установить CRON задачу для этого хоста
        $params['postfields']=['login','pass','module','subdo','crontab','addcommcron','panel_user','cron_command','cron_freq_minutes','cron_freq_hours','cron_freq_days','cron_freq_months','cron_freq_weekdays']; //обозначим что нам нужно будет отправить на сервер какие поля важно, т.к. там может быть хлам
        $params['module']='crontab';
        $params['crontab']='crontab';
        $params['subdo']='addcommcron';
        $params['addcommcron']='addcommcron';
        $params['panel_user']=$vclient;
        $params['cron_command']='php /home/'.$vclient.'/sites/'.$vclient.'.'.$domenCRM.'/cron.php "'.$vclient.'" "'.$domenCRM.'"';
        $params['cron_freq_minutes']='*';
        $params['cron_freq_hours']='*';
        $params['cron_freq_days']='*';
        $params['cron_freq_months']='*';
        $params['cron_freq_weekdays']='*';
        $result=self::requestCRMBrainyCP($serverHost['ipserver'], $params);
        if(!$result['success']){
            //ошибка создания  на хосте
            $response['success']=false;
            $response['result']['message']='error  add CRON  '.$serverHost['ipserver'];
            $response['result']['params']=$params;
            $response['result']['result']=$result;
            return $response;
        }
        $response['tracking']['messages'][]='5_ok  add CRON ';
        $response['tracking']['results'][]=$result;



    // 6  SSL к сайту прицепить сертификат SSL который ссылки сделаны скриптом. два домена. два запроса
        $params['postfields']=['login','pass','module','subdo','certs_control','savedomaincerts','domain','key','panel_user']; //обозначим что нам нужно будет отправить на сервер какие поля важно, т.к. там может быть хлам
        $params['module']='certs_control';
        $params['certs_control']='certs_control';
        $params['subdo']='savedomaincerts';
        $params['savedomaincerts']='savedomaincerts';
        $params['panel_user']=$vclient;

        $params['domain']=$vclient.'.'.$domenCRM;
        $params['key']=$domenCRM.'_wildcart_autorenew_letsen';
        $result=self::requestCRMBrainyCP($serverHost['ipserver'], $params);
        if(!$result['success']){
            //ошибка создания  на хосте
            $response['success']=false;
            $response['result']['message']='error  SSL DOMAIN CRM  '.$serverHost['ipserver'];
            $response['result']['params']=$params;
            $response['result']['result']=$result;
            return $response;
        }
        $response['tracking']['messages'][]='6_ok  SSL DOMAIN CRM';
        $response['tracking']['results'][]=$result;


        $params['domain']=$vclient.'.'.$domainPortal;
        $params['key']=$domainPortal.'_wildcart_autorenew_letsen';
        $result=self::requestCRMBrainyCP($serverHost['ipserver'], $params);
        if(!$result['success']){
            //ошибка создания  на хосте
            $response['success']=false;
            $response['result']['message']='error  SSL DOMAIN PORTAL  '.$serverHost['ipserver'];
            $response['result']['params']=$params;
            $response['result']['result']=$result;
            return $response;
        }
        $response['tracking']['messages'][]='6_ok  SSL DOMAIN PORTAL';
        $response['tracking']['results'][]=$result;








    // 4 теперь внутри сервера запустим скрипт bash. он сделает ссылки в том числе SSL ссылки на исходные сертификаты
        //$command='sshpass -p "'.$serverHost['pwd'].'" ssh root@'.$serverHost['ipserver'].' /usr/createuser.sh --vclient='.$vclient.' --vdomen='.$domenCRM.' --vdomenportal='.$domainPortal.' --password='.$userhostpwd.' --dbrootpwd='.$serverHost['dbrootpwd']; не работает. будем


        $command='/usr/createuser.sh --vclient='.$vclient.' --vdomen='.$domenCRM.' --vdomenportal='.$domainPortal.' --password='.$userhostpwd.' --dbrootpwd='.$serverHost['dbrootpwd'];



        $connection = ssh2_connect($serverHost['ipserver'], 22);
        ssh2_auth_password($connection, 'root', $serverHost['pwd']);
        $stream = ssh2_exec($connection, $command);
        stream_set_blocking($stream, true);
        $result = "";
        while($o = fgets($stream))  $result .= " | ".$o;
        fclose($stream);

        $response['tracking']['messages'][]='4_ok  SHELL EXEC ';
        $response['tracking']['results']['4_ok_SHELL EXEC'][]=$command;
        $response['tracking']['results']['4_ok_SHELL EXEC'][]=$result;





        //-------------------------------------------------------------------------------------------------------------
        // 7 обновим информацию в нашей базе по VCLIENT




        $paramsupdate=[];
        $paramsupdate['status']='work';
        $paramsupdate['pwd']=$userhostpwd;

        $paramsupdate['ipserver']=$serverHost['ipserver'];
        $paramsupdate['domaincrm']=$domenCRM;
        $paramsupdate['domainportal']=$domainPortal;


        Vclient::getDb()->createCommand()
            ->update('vclient', $paramsupdate, ['vclient'=>$vclient] )
            ->execute();


        Hostserver::getDb()->createCommand()->update('hostserver', ['countemptyhost'=>'countemptyhost+1'], ['ipserver'=>$serverHost['ipserver']] )
            ->execute();


        return $response;

    }
*/
    public static function _delVclientBrainyCP($vclient=''){
        //ответ базовый с функцией
        $response=[];
        $response['success']=true;
        $response['vclient']=$vclient;
        $response['result']=[];
        $response['result']['func']='Vclient::_delVclientBrainyCP';

        $response['result']['tracking']=[];
        $itracking=0;

        /*$response['tracking']=[];
        $response['tracking']['messages']=[];
        $response['tracking']['results']=[];*/



        $vclientarraydel=[];

        //1. заберем данные если vclient пустой, значит есть пометка в базе данных на удаление. крон ежесуточно выбирает, поэтому ночью может быть запуск на удаление пачки устаревших данных
        if(!$vclient){
            $vclientarraydel=ArrayHelper::map(Vclient::find()->select('vclient')->where(['task'=>'delete'])->all(),'vclient','vclient');
        }

        if(!$vclientarraydel && $vclient)$vclientarraydel=[$vclient];

        $response['result']['tracking'][$itracking]['message']='get vclientarraydel ';
        $response['result']['tracking'][$itracking++]['vclientarraydel']=$vclientarraydel;



        if(!$vclientarraydel){
            $response['tracking']['messages'][]='no vclientarray ';

            $response['success']=false;
            $response['result']['message']='no vclientarray ';
            print_r($response);
            return $response;
        }


        $db= Vclient::getDb();

        foreach($vclientarraydel as $vclient){
        //2. заберем все по по  vclient т.к. нам нужно будет удялять сайт из ДНС записей
            $iii=0;
            echo "\n| ".$iii++.' start delete vclient='.$vclient;

            $vclientarray= (new Query())->select('*')->from('vclient')->where(['vclient'=>$vclient])->one(); //нужен будет айпишник хоста
            $response['result']['tracking'][$itracking]['message']='get vclient ';
            $response['result']['tracking'][$itracking++]['vclientarray']=$vclientarray;

            $vsitearray=(new Query())->select('*')->from('vsite')->where(['vclient'=>$vclient])->one(); //нужен будет vsite
            $response['result']['tracking'][$itracking]['message']='get vsite ';
            $response['result']['tracking'][$itracking++]['vsitearray']=$vsitearray;





            $params=[];
            $params['vclient']=$vclient;
            $params['vsite']=$vsite=$vsitearray['vsite'];
            $params['operationns']='delete';


            //3. уберем из ДНС запись  с сайтом
            $params['type']='CNAME';

            $params['zone']=$vclientarray['domainportal'];
            $params['domen']=$vclientarray['domainportal'];
            $params['findnsdomen']=$vsite.'.'.$vclientarray['domainportal'].'.';

            echo "|".$iii++.". request NS ";

            $statusns2=self::requestNsBrainyCP($params);

            echo "_ok||";

            $response['result']['tracking'][$itracking]['message']='request NS ';
            $response['result']['tracking'][$itracking]['params']=$params;
            $response['result']['tracking'][$itracking++]['statusns2']=$statusns2;



            /*if(!$statusns2['success']){
                return [
                    'success'=>false,
                    'result'=>[
                        'func'=>'Vclient::_delVclientBrainyCP',
                        'vclient'=>$vclient,
                        'message'=>'error add ns',
                        'result'=>$statusns2['result']
                    ]
                ];
            }*/

            $response['tracking']['messages'][]='0_ok delete NS vsite CNAME PORTAL';
            $response['tracking']['results'][]=$statusns2;

            $params['type']='CNAME';
            $params['zone']=$vclientarray['domaincrm'];
            $params['domen']=$vclientarray['domaincrm'];
            $params['findnsdomen']=$vsite.'.'.$vclientarray['domaincrm'].'.';

            echo "|".$iii++.". start delete NS vsite CNAME CRM ";
            $statusns1=self::requestNsBrainyCP($params);

            if(!$statusns1['success']){
                return [
                    'success'=>false,
                    'result'=>[
                        'func'=>'Vclient::_delVclientBrainyCP',
                        'vclient'=>$vclient,
                        'message'=>'error add ns',
                        'result'=>$statusns1['result']
                    ]
                ];
            }

            $response['tracking']['messages'][]='0_ok delete NS vsite CNAME CRM ';
            $response['tracking']['results'][]=$statusns1;

            echo "_ok||";


            //4. уберем из днс запись с клиентом// NS команда добавить связку vclient  и $iphost в NS1 и NS2 и NS{.},

            $params['type']='A';
            $params['zone']=$vclientarray['domainportal'];
            $params['domen']=$vclientarray['domainportal'];
            $params['findnsdomen']=$vclient.'.'.$vclientarray['domainportal'].'.';

            echo "|".$iii++.". start delete NS vclient ip PORTAL ";
            $statusns2=self::requestNsBrainyCP($params);

            if(!$statusns2['success']){
                return [
                    'success'=>false,
                    'result'=>[
                        'func'=>'Vclient::_delVclientBrainyCP',
                        'vclient'=>$vclient,
                        'message'=>'error add ns',
                        'result'=>$statusns2['result']
                    ]
                ];
            }

            $response['tracking']['messages'][]='0_ok delete NS vclient ip PORTAL';
            $response['tracking']['results'][]=$statusns2;

            echo "_ok||";

            $params['type']='A';
            $params['zone']=$vclientarray['domaincrm'];
            $params['domen']=$vclientarray['domaincrm'];
            $params['findnsdomen']=$vclient.'.'.$vclientarray['domaincrm'].'.';

            $statusns1=self::requestNsBrainyCP($params);

            if(!$statusns1['success']){
                return [
                    'success'=>false,
                    'result'=>[
                        'func'=>'Vclient::_delVclientBrainyCP',
                        'vclient'=>$vclient,
                        'message'=>'error add ns',
                        'result'=>$statusns1['result']
                    ]
                ];
            }

            $response['tracking']['messages'][]='0_ok delete NS vclient ip CRM ';
            $response['tracking']['results'][]=$statusns1;
            echo "|".$iii++.".. ok delete NS vclient ip CRM ";



        //5. удалим хост через панельку

            //заберем параметры (пароль) по этому серверу с базы, т.к. мы знаем IP адрес сервера.
            $hostserverarray= (new Query())->select('*')->from('hostserver')->where(['ipserver'=>$vclientarray['ipserver']])->one(); //нужен будет айпишник хоста

            $params=[];
            $params['login']='root'; //
            $params['pass']=$hostserverarray['pwd']; //root pwd
            $params['module']='hostacc';
            $params['hostacc']='hostacc';
            $params['subdo']='deluseracc';
            $params['deluseracc']='deluseracc';

            $params['panel_user']=$vclient;
            //$params['lg']=$vclient;


            $params['postfields']=['login','pass','module','subdo','hostacc','deluseracc','panel_user']; //обозначим что нам нужно будет отправить на сервер какие поля важно, т.к. там может быть хлам

            echo "|".$iii++.". start delete deluseracc ";
            $resultdelhost=self::requestCRMBrainyCP($vclientarray['ipserver'], $params);
            if(!$resultdelhost['success']){
                //ошибка удаления юзера на хосте
                $response['success']=false;
                $response['result']['tracking'][$itracking]['message']='error delete host '.$vclientarray['ipserver'];
                $response['result']['tracking'][$itracking]['params']=$params;
                $response['result']['tracking'][$itracking++]['response']=$resultdelhost;
            }


            echo "_ok||";



            //6 пометим  запись клиента как удаленную в реестре базе данных

            $param=[];
            $param['status']=$resultdelhost['success'] ? 'deleted' : 'delete_error';
            $param['updated_at']=date('U');
            $param['updated']=date('Y-m-d H:i:s');
            $param['task']=null;
            echo "|".$iii++.". start update vclient in MYSQL ";
            $db->createCommand()
                ->update(Vclient::tableName(), $param, ['vclient'=>$vclient] )
                ->execute();
            $response['tracking']['messages'][]='ok update vclient  ';
            $response['tracking']['results'][]=$param;


            echo "_ok||";

            //7 пометим запись сайта как удаленную в реестре в базе данных
            $param=[];
            $param['status']=$resultdelhost['success'] ? 'deleted' : 'delete_error';
            $param['updated_at']=date('U');
            $param['updated']=date('Y-m-d H:i:s');

            echo "|".$iii++.". start update vsite in MYSQL ";
            $db->createCommand()
                ->update('vsite', $param, ['vclient'=>$vclient] )
                ->execute();
            $response['tracking']['messages'][]='ok update vsite  ';
            $response['tracking']['results'][]=$param;
            echo "_ok||";


        }


        echo "| end  del all vclients \n\n\n";

        return $response;
    }

    public static function createUserInCRM($paramscreateuser){
        //ответ базовый с функцией
        $response=[];
        $response['success']=true;
        $response['result']=[];
        $response['result']['func']='Vclient::createUserInCRM';
        $response['result']['tracking']=[];
        $itracking=0;

        $response['result']['tracking'][$itracking]['message']='start update user ';
        $response['result']['tracking'][$itracking++]['paramscreateuser']=$paramscreateuser;


        //мы получаем параметры для создания
        /* $paramscreateuser['url']=$vclient.'.'.$vclientarray['domaincrm'];
        $paramscreateuser['userdata']= [
            'user_name'=>$useremail,
            'first_name'=>$params['order_data']['firstname'],
            'last_name'=>'',
            'user_password'=>$userpwd,
            'confirm_password'=>$userpwd,
            'email1'=>$useremail,
            'roleid'=>"H47",
        ];*/

        //заберем недостающие данные по VCLIENT из базы
        //$serverHost=Hostserver::find()->where(['ipserver'=>$paramscreateuser['vclientarray']['ipserver']])->one();

        $serverHost=self::getHosts()[$paramscreateuser['vclientarray']['ipserver']];

        $response['result']['tracking'][$itracking]['message']='get  serverHost ';
        $response['result']['tracking'][$itracking++]['serverHost']=$serverHost;

        if(!$serverHost){
            $response['success']=0;
            $response['tracking']['messages'][]='!serverHost';
            $response['result']['tracking'][$itracking]['message']='error  serverHost ';
            return $response;
        }


        //запустим скрипт замены логина и пароля  через SSH на выбранном сервере, там же установим vsite как основной


        $command='/usr/updatevclient.sh --vclient="'.$paramscreateuser['vclientarray']['vclient'].'" --vdomen="'.$paramscreateuser['vclientarray']['domaincrm'].'" --vdomenportal="'.$paramscreateuser['params']['domenportal'].'" --dbrootpwd="'.$serverHost['dbrootpwd'].'" --useremail="'.$paramscreateuser['params']['order_data']['email'].'" --userpwd="'.$paramscreateuser['userdata']['user_password_hash'].'" --userfirstname="'.$paramscreateuser['params']['order_data']['firstname'].'" --vsite="'.$paramscreateuser['params']['vsite'].'" --vip="'.$serverHost['ipserver'].'"';

        $command=str_replace("$", '\$', $command);

        $connection = ssh2_connect($serverHost['ipserver'], 22);
        ssh2_auth_password($connection, 'root', $serverHost['pwd']);
        $stream = ssh2_exec($connection, $command);
        stream_set_blocking($stream, true);
        // The command may not finish properly if the stream is not read to end
        //$output = stream_get_contents($stream);

        $iii=0;
        $result = "";
        while($iii< 60 && !strstr($result, "--OK--")){
            sleep(1);
            $iii++;

            while($o = fgets($stream))  $result .= "|".$o;
        }
        sleep(5);


        fclose($stream);


        $response['result']['tracking'][$itracking]['message']='send   comand ssh EXEC';
        $response['result']['tracking'][$itracking]['command']=$command;
        $response['result']['tracking'][$itracking++]['response']=$result;


        return $response;
    }

    public static function sendComandCRMWebservice($params){
        //ответ базовый с функцией
        $response=[];
        $response['success']=true;
        $response['result']=[];
        $response['result']['func']='Vclient::sendComandCRMWebservice';


        //выполнение команд на сервере от имени ivr с паролем $ivrpwd='RkNNBGzxPpVPGkkgkKRK';


        //1 получаем токен шифрования
        $url='https://'.$params['ipserver'].'/webservice.php?operation=getchallenge&username=ivr';

        //$postfields=[];
        //$result= self::setRequestBrainyCP($url, [], $postfields);

        $tokenchallenge='';
        //2Этап 2 - получаем код сессии. используя токен IVR 'RkNNBGzxPpVPGkkgkKRK' //для IMG токен всегда одинаковый. после копирования базы токены сбрасываются.



        $token='';

        //

        return $response;
    }

    /*
     * это запрос на панель управления сервером HOSTа где сидит VCLIENT
     * */
    public static function requestCRMBrainyCP($iphost='', $params){
        //ответ базовый с функцией
        $response=[];
        $response['success']=true;
        $response['result']=[];
        $response['result']['func']='Vclient::requestCRMBrainyCP';

        $url='https://'.$iphost.':8887/api/api.php';

        $postfields=[];
        foreach($params['postfields'] as $fieldname){
            $postfields[$fieldname]=$params[$fieldname];
        }

        //print_r($postfields);die;

        $result= self::setRequestBrainyCP($url, [], $postfields);

        $response['success']=$result['success'];
        $response['result']['result']=$result['result'];

        return $response;
    }


    /*
     * это запрос на сервер  NS по передаваемым параетрам
     * */
    public static function requestNsBrainyCP($params){

        //ответ базовый с функцией
        $response=[];
        $response['success']=true;
        $response['result']['message']=[];
        $response['result']['func']='Vclient::requestNsBrainyCP';
        $response['result']['tracking']=[];
        $itracking=0;

        $postfields=[];
        $postfields['login']='root';
        $postfields['module']='bindserver';
        $postfields['subdo']='edit_zones';
        $postfields['bindserver']='bindserver';
        $postfields['edit_zones']='edit_zones';
        $postfields['user_edit']='voronka';
        $postfields['zone']=$params['domen']; //voronka.pro



        //добавляем или удаляем?
        $delLineArray=[];
        if($params['operationns']=='add'){
            $postfields['type']='A';

            $postfields['value']=$params['iphost']; //188.120.236.126
            $postfields['name']=$params['vsite'].'.'.$params['domen'].'.'; //mango2.voronka.pro.
            $postfields['line']=''; //линия нужна только для удаления. если добавляем то линия не нужна
            $postfields['delete']=0; // только для удаления. если добавляем то 0
        }
        else if($params['operationns']=='delete'){
            //укажаем параметр удаления
            $postfields['delete'] = 'delete'; // только для удаления. если добавляем то 0

            //нужно сделать запрос и получить номер линии

            $resultns1=[];
            $resultns1['success']=false;
            $resultns1['result']=[];

            //на каждом NS сервере скорее всего номер линии будет разный. поэтому сделаем связку IP NS и номеров линий
            $paramGetZone=[];
            $paramGetZone['login']='root';
            $paramGetZone['module']='bindserver';
            $paramGetZone['subdo']='show_zones';
            $paramGetZone['bindserver']='bindserver';
            $paramGetZone['show_zones']='show_zones';
            $paramGetZone['user_edit']='voronka';
            $paramGetZone['zone']=$params['domen'];


            foreach(self::getBrainyCpNS() as $nsname => $nsparam){
                $delLineArray[$nsparam['ip']]='';
                $paramGetZone['pass']=$nsparam['pwd'];

                $url='https://'.$nsparam['ip'].':8887/api/api.php';




                $nsresultGetZone=self::setRequestBrainyCP($url, [], $paramGetZone); //возвращает array со всей информацией


                if($nsresultGetZone['success']){
                    //ответ ок. есть что разбирать

                    $response['result']['tracking'][$itracking]['message']='get nsresultGetZone '.$nsname;
                    $response['result']['tracking'][$itracking]['params']=$params;
                    $response['result']['tracking'][$itracking]['nsparam']=$nsparam;
                    $response['result']['tracking'][$itracking]['paramGetZone']=$paramGetZone;
                    $response['result']['tracking'][$itracking]['response']=$nsresultGetZone;




                    $dnsarray=$nsresultGetZone['result']['resultcurl']; //\json_decode($nsresultGetZone,true);


                    $zonestext=$dnsarray['zones'];


                    //$zonestext=str_replace('\n', '|', $zonestext);//там \n38: fort.voronka.pro.\t\t\tA\t188.120.236.126\n39: ns1.voronka.pro.\t\t\tA\t79.174.13.70



                    $zonesArr1=explode("\n",$zonestext);



                    foreach($zonesArr1 as $dnstext){ //38: fort.voronka.pro.\t\t\tA\t188.120.236.126
                        //нам приходит $params['findnsdomen'] если он есть в этой строчке , то взять то что стоит до двоеточия
                        $line='';
                        if(strstr($dnstext, $params['findnsdomen'])){
                            $strpos=strpos($dnstext, ":");
                            $line=substr($dnstext, 0, $strpos);
                        }
                        if($line!=''){

                            $delLineArray[$nsparam['ip']]=$line;
                        }
                    }
                    $response['result']['tracking'][$itracking++]['delLineArray']=$delLineArray;

                }


            }




        }
        else if($params['operationns']=='cname'){
            //добавить сайт к vclient

            $postfields['type']='CNAME';
            $postfields['value']=$params['vclient'].'.'.$params['domen'].'.'; //sfddsd11.voronka.pro.
            $postfields['name']=$params['vsite'].'.'.$params['domen'].'.'; //mango2.voronka.pro.

            $postfields['line']=''; //линия нужна только для удаления. если добавляем то линия не нужна
            $postfields['delete']=0; // только для удаления. если добавляем то 0
        }





        foreach(self::getBrainyCpNS() as $nsname => $nsparam){

            $postfields['pass']=$nsparam['pwd'];

            //если удаление то для этого НС надо указать номер линии с которой работаем
            if($params['operationns']=='delete' && $delLineArray[$nsparam['ip']] ){
                $postfields['line']=$delLineArray[$nsparam['ip']];
                $postfields['type']=$params['type'];
            }
            elseif($params['operationns']=='delete' && !$delLineArray[$nsparam['ip']] ){
                //удаляем домен, но строчку не нашли, ошибку не возрващаем, т.к. это не критично в целом но в треке укажем ошибку

                $response['result']['tracking'][$itracking]['message']='no line for delete  .-> continue; '.$nsname;
                $response['result']['tracking'][$itracking]['params']=$params;
                $response['result']['tracking'][$itracking]['nsparam']=$nsparam;
                $response['result']['tracking'][$itracking++]['delLineArray']=$delLineArray;
                continue;
            }


            $url='https://'.$nsparam['ip'].':8887/api/api.php';



            $nsresult=self::setRequestBrainyCP($url, [], $postfields);


            if(!$nsresult['success'])$response['success']=false;
            $response['result']['tracking'][$itracking]['message']='request nsresult '.$nsname;
            $response['result']['tracking'][$itracking]['params']=$params;
            $response['result']['tracking'][$itracking]['nsparam']=$nsparam;
            $response['result']['tracking'][$itracking]['url']=$url;
            $response['result']['tracking'][$itracking]['postfields']=$postfields;
            $response['result']['tracking'][$itracking++]['response']=$nsresult;
        }



        return $response;


    }


    public static function setRequestBrainyCP($url, $header=[], $postfields=[]){

        //ответ базовый с функцией
        $response=[];
        $response['success']=true;
        $response['result']=[];
        $response['result']['func']='Vclient::setRequestBrainyCP';
        $response['result']['url']=$url;


        $ch = curl_init($url);

        $options = array(
            CURLOPT_POST => 1,
            CURLOPT_URL => $url,
            CURLOPT_USERAGENT => "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1",
            CURLOPT_FRESH_CONNECT => 1,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_FORBID_REUSE => 1,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_POSTFIELDS => $postfields
        );



        curl_setopt_array($ch, $options);

        if($header) curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

        $curlresult = \curl_exec($ch);



        $response['result']['curlresult']= $curlresult;

        $response['result']['curl_info']= curl_getinfo($ch);
        $response['result']['curl_error']= curl_error($ch);

        curl_close($ch);



        $http_code=$response['result']['curl_info']['http_code'];

        if(is_numeric($http_code)){
            $http_code_1=$http_code/100;
        }
        else $http_code_1 =substr($http_code, 0,1);

        $http_code_1=(int)$http_code_1;

        $response['result']['curlhttp_code']= $http_code;
        $response['result']['curlhttp_code_1']= $http_code_1;



        if (!$curlresult or  $http_code_1 == 4 OR  $http_code_1 == 5 ){
            $response['success']=false;
            $response['result']['message']=$response['result']['curl_error'];
        }
        else{
            //ответ пришел
            $resultcurl=json_decode($curlresult,true);

            if(isset($resultcurl['code']))$resultcode=$resultcurl['code'];
            elseif(isset($resultcurl['err']))$resultcode=$resultcurl['err'];
            elseif(isset($resultcurl['error']))$resultcode=$resultcurl['error'];
            else $resultcode='nocode';

            $response['result']['resultcode']=$resultcode;
            
            if($resultcode==0 || $resultcode=='0' || $resultcode=='200' || $resultcode==200|| $resultcode==201){
                //ответ отработан корректно
                $response['success']=true;
                $response['result']['resultcurl']=$resultcurl;
                $response['result']['message']='ok';


            }
            else{
                //что то пошло не так
                $response['success']=false;
                $response['result']['message']='code not 0. error?';
                $response['result']['resultcurl']=$resultcurl;
            }

        }

        return $response;
    }


    public static function getServerHost()
    {
        //[TODO]Voronka-доработка балансировка:  тут логика балансировки клиентов между разными хост машинами. система берет следующую наименее загруженную хост систему или исходя из заявляемых активных пользователей.

        return Hostserver::getActiveServer();
    }


    public static function getDomenCRM()
    {
        //[TODO]Voronka-разработка-обязательно  - уточнить откуда берутся реестр актуальных доменов если они будут разные
        return [
            'crm' => 'voronka.pro',
            'portal' => 'umdoza.pro'
        ];
    }


    public static function getBrainyCpNS()
    {
        //[TODO]Voronka-разработка-обязательно
        return [
            'ns1' => ['ip'=>'188.120.236.126', 'pwd'=>'4XPvPWkLWvYX'],
           // 'ns2' => ['ip'=>'195.22.153.33', 'pwd'=>'S95bI6MKdDDu'] //там должна быть автосинхронизация кластера, но при удалении не удаляет.
        ];
    }


    public static function getHosts()
    {
        //[TODO]Voronka-разработка-обязательно
        return [
            '188.120.236.126' => ['ipserver'=>'188.120.236.126', 'pwd'=>'4XPvPWkLWvYX', 'dbrootpwd'=>'eYibq8zYts'],
           // 'ns2' => ['ip'=>'195.22.153.33', 'pwd'=>'S95bI6MKdDDu'] //там должна быть автосинхронизация кластера, но при удалении не удаляет.
        ];
    }


    public static function getUniqVclient($idhost)
    {
        //пока не знаю но на всякий случай защита от бесконечной рекурсии в этой функции которая вызываем сама себя. на 1000 попыток должен же получиться уникальный айди
        self::$countrecursive++;
        if(self::$countrecursive > 1000)return false;


       // $vclient=substr(uniqid($idhost),0,8);
        $vclient=$idhost.substr(md5(mt_rand()), -6);

        $exist=Vclient::find()->select('vclient')->where(['vclient'=>$vclient])->exists();

        if( $exist || strstr($vclient, "e") || strstr($vclient, "E") || ctype_digit($vclient) ){//Ошибка. Зарезервировано значение в mysql (xE или xe e разделяет значение с плавоющей точкой), т.е. буква е не может быть . + логин не может быть только цифрами
             return self::getUniqVclient($idhost);
        }
        else{
            return $vclient;
        }

    }

    public static function tableName()
    {
        return 'vclient';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['created_at', 'updated_at', 'vsiteid'], 'integer'],
            [['status'], 'string', 'max' => 50],
            [['vclient'], 'string', 'max' => 8],
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
            'ipserver' => 'ipserver',
            'status' => 'status',
            'vclient' => 'vclient',
            'vsiteid' => 'vsiteid',
        ];
    }
}

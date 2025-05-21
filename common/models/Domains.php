<?php

namespace common\models;

use Yii;


class Domains extends \yii\db\ActiveRecord
{

    public static function deldnsrecord($domain='',  $params=[])
    { //тут запускаем  работу с API сервером чтобы добавить TXT запись _acme-challenge.voronka.pro.


        //ответ базовый с функцией
        $response=[];
        $response['success']=true;
        $response['result']=[];

        $response['tracking']=[];
        $response['tracking']['messages']=[];
        $response['tracking']['results']=[];
        $itracking=0;
        $response['result']['func']='Domains::deldnsrecord';

        if(!$domain ){
            $response['success']=false;
            $response['result']['message']='! domain OR ! challenge';
            $response['tracking'][$itracking]['params']=$params;
            $response['tracking'][$itracking]['results']['domain']=$domain;
            //$response['tracking'][$itracking]['results']['challenge']=$challenge;
            return $response;
        }


        //--------------------- заберем настройки NS1
        $nsname='ns1'; //foreach не делаем, коприровать будет кластер сам между другими серверами NS
        $nsparam=\common\models\Vclient::getBrainyCpNS()[$nsname];

        $pwd=$nsparam['pwd'];
        $ip=$nsparam['ip'];

        //---------------------- отправкм туда

        $command="/usr/deletetxtdomain.sh $domain";
        $result='';
        $connection = \ssh2_connect($ip, 22);
        ssh2_auth_password($connection, 'root', $pwd);
        $stream = ssh2_exec($connection, $command);
        stream_set_blocking($stream, true);
        $result = "";

        while($o = \fgets($stream))  $result .= " | ".$o;
        \fclose($stream);




        /*$postfields=[];
        $postfields['login']='root';
        $postfields['module']='bindserver';
        $postfields['subdo']='edit_zones';
        $postfields['bindserver']='bindserver';
        $postfields['edit_zones']='edit_zones';
        $postfields['line']=''; //линия нужна только для удаления. если добавляем то линия не нужна
        $postfields['delete']=0; // только для удаления. если добавляем то 0

        $postfields['user_edit']='voronka';
        $postfields['zone']='voronka.pro'; //$params['domen']; //voronka.pro

        $postfields['type']='TXT';
        $postfields['value']=$challenge; //'"'.
        $postfields['name']=$domain; //_acme-challenge.voronka.pro.





        $url='https://'.$nsparam['ip'].':8000/api/api.php';

        $nsresult=\common\models\Vclient::setRequestBrainyCP($url, [], $postfields);*/

        //if(!$nsresult['success'])$response['success']=false;
        $response['result']['tracking'][$itracking]['message']='request nsresult nsname='.$nsname;
        //$response['result']['tracking'][$itracking]['params']=$params;
        $response['result']['tracking'][$itracking]['nsparam']=$nsparam;
        //$response['result']['tracking'][$itracking]['url']=$url;
        //$response['result']['tracking'][$itracking]['postfields']=$postfields;
        $response['result']['tracking'][$itracking++]['response']=$result;


        return $response;
    }


    public static function newdnsrecord($domain='', $challenge='', $params=[])
    { //тут запускаем  работу с API сервером чтобы добавить TXT запись _acme-challenge.voronka.pro.


        //ответ базовый с функцией
        $response=[];
        $response['success']=true;
        $response['result']=[];

        $response['tracking']=[];
        $response['tracking']['messages']=[];
        $response['tracking']['results']=[];
        $itracking=0;
        $response['result']['func']='Domains::newdnsrecord';

        if(!$domain OR !$challenge){
            $response['success']=false;
            $response['result']['message']='! domain OR ! challenge';
            $response['tracking'][$itracking]['params']=$params;
            $response['tracking'][$itracking]['results']['domain']=$domain;
            $response['tracking'][$itracking]['results']['challenge']=$challenge;
            return $response;
        }


        //--------------------- заберем настройки NS1
        $nsname='ns1'; //foreach не делаем, коприровать будет кластер сам между другими серверами NS
        $nsparam=\common\models\Vclient::getBrainyCpNS()[$nsname];

        $pwd=$nsparam['pwd'];
        $ip=$nsparam['ip'];




        //---------------------- отправкм туда NS1

        $command="/usr/addtxtdomain.sh $domain $challenge";
        $result='';
        $connection = \ssh2_connect($ip, 22);
        ssh2_auth_password($connection, 'root', $pwd);
        $stream = ssh2_exec($connection, $command);
        stream_set_blocking($stream, true);
        $result = "";
        while($o = \fgets($stream))  $result .= " | ".$o;
        \fclose($stream);




        /*$postfields=[];
        $postfields['login']='root';
        $postfields['module']='bindserver';
        $postfields['subdo']='edit_zones';
        $postfields['bindserver']='bindserver';
        $postfields['edit_zones']='edit_zones';
        $postfields['line']=''; //линия нужна только для удаления. если добавляем то линия не нужна
        $postfields['delete']=0; // только для удаления. если добавляем то 0

        $postfields['user_edit']='voronka';
        $postfields['zone']='voronka.pro'; //$params['domen']; //voronka.pro

        $postfields['type']='TXT';
        $postfields['value']=$challenge; //'"'.
        $postfields['name']=$domain; //_acme-challenge.voronka.pro.





        $url='https://'.$nsparam['ip'].':8000/api/api.php';

        $nsresult=\common\models\Vclient::setRequestBrainyCP($url, [], $postfields);*/

        //if(!$nsresult['success'])$response['success']=false;
        $response['result']['tracking'][$itracking]['message']='request nsresult nsname='.$nsname;
        //$response['result']['tracking'][$itracking]['params']=$params;
        $response['result']['tracking'][$itracking]['nsparam']=$nsparam;
        //$response['result']['tracking'][$itracking]['url']=$url;
        //$response['result']['tracking'][$itracking]['postfields']=$postfields;
        $response['result']['tracking'][$itracking++]['response']=$result;



        return $response;
    }






    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'domains';
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

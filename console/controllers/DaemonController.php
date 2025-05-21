<?php
namespace console\controllers;

use Yii;
use yii\helpers\Url;
use yii\console\Controller;


class DaemonController extends Controller
{

    public $vclient;

    //заберем параметры из аргументов консоли yii hello -vclient=ddfdfdfdf
    ///home/api/sites/api.voronka.pro/yii2/yii daemon/index -vclient="1rhjhjh"

    public function optionAliases()
    {
        return ['vclient' => 'vclient'];
    }
    public function options($actionID)
    {
        return ['vclient'];
    }

    //--------------------------------------------------------------------------

    public function actionIndex()
    {
        echo "Yes, cron service is running. vclient = ";
        echo $this->vclient . "\n";
    }

    public function actionDeletevclient()
    {
        echo "actionDeletevclient start -- ";

        //если в параметрах передается конкретный vclient то передадим его, если нет, то дальше там будем смотреть в базе
        $vclient=''; //\common\models\Vclient::find()->select('vclient')->where(['task'=>'delete'])->orderBy('id')->scalar();
        if($this->vclient)$vclient=$this->vclient;

        $result=\common\models\Vclient::delVclient($vclient);

        if($result['success']){
            echo "\n actionDeletevclient: ".$vclient." ok -- \n";
        }
        else{
            $resp=print_r($result,true);
            echo "\n actionDeletevclient: ".$vclient." error - ".$resp;
        }




    }


    public function actionCreatevclient()
    {
        echo "actionCreatevclient start -- ";
        //1 проверим что есть доступный VCLIENT, если нет, то создадим его
        $vclient=\common\models\Vclient::find()->select('vclient')->where(['status'=>'empty'])->orderBy('id')->scalar();

        if(!$vclient) {
            //свободного нет.
            // вызовем функцию создания клиента. в ответе будет $vclient

            //проверим что не зависли на создании предыдущего
            $exists=\common\models\Vclient::find()->where(['status'=>'create'])->orderBy('id')->limit(1)->exists();

            if(!$exists) {
                $result=\common\models\Vclient::createVclient();
                echo " actionCreatevclient ok -- ";
            }
            else{
                echo " actionCreatevclient exit. now in work -- ";
            }
            //иначе просто через минуту еще раз запустится.
        }

        echo " actionCreatevclient end -- ";
    }

    /*public function actionFrequent()
    {
        // called every two minutes
        // * / 2 * * * * ~/sites/www/yii2/yii test
        $time_start = microtime(true);
        $x = new \frontend\models\Twixxr();
        $x->process($time_start);
        $time_end = microtime(true);
        echo 'Processing for ' . ($time_end - $time_start) . ' seconds';
    }

    public function actionQuarter()
    {
        // called every fifteen minutes
        $x = new \frontend\models\Twixxr();
        $x->loadProfiles();
    }

    public function actionHourly()
    {
        // every hour
        $current_hour = date('G');
        if ($current_hour % 4) {
            // every four hours
        }
        if ($current_hour % 6) {
            // every six hours
        }
    }*/
}
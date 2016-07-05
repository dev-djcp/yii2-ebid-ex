<?php
namespace ebidex\controllers;

use Yii;
use yii\helpers\Json;
use yii\helpers\ArrayHelper;
use yii\helpers\Console;

use GearmanWorker;
use GearmanClient;

use ebidex\ExHttp;

use ebidex\models\BidKey;

class GmanSucController extends \yii\console\Controller
{
  public function actionConSearch(){
    $worker=new GearmanWorker;
    $worker->addServers($this->module->gman_server);
    $worker->addFunction('ebid_ex_con_suc_search',[$this,'con_search']);
    while($worker->work());
  }

  public function con_search($job){
    $workload=Json::decode($job->workload());

    try {
      $http=new ExHttp;
      $http->request_con_suc([
        'status'=>'Z',
        'bid_type'=>'C',
        'list_gubun'=>'R',
        'in_out'=>'I',
        'startnum'=>'1',
        'endnum'=>'10',
        's_noti_date'=>date('Ymd',strtotime('-1 month')),
        'e_noti_date'=>date('Ymd'),
      ],function($data){
        $this->stdout('[공사] '.implode(',',$data)."\n");
        $bid_key=BidKey::findOne([
          'notinum'=>$data['notinum'],
          'whereis'=>'08',
          'bidproc'=>'S',
          'state'=>'Y',
        ]);
        if($bid_key===null){
          $this->stdout(" > 개찰결과 누락!!\n",Console::FG_RED);
          $gman_client=new GearmanClient;
          $gman_client->addServers('192.168.1.242');
          $gman_client->doBackground('send_chat_message_from_admin',Json::encode([
            'recv_id'=>149,
            'message'=>"도로공사 공사개찰누락 ${data['notinum']}",
          ]));
        }
      });
    }
    catch(\Exception $e){
      $this->stdout($e,Console::FG_RED);
    }
    $this->stdout(sprintf("[%s] Peak memory usage: %s MB\n",date('Y-m-d H:i:s'),(memory_get_peak_usage(true)/1024/1024)),Console::FG_GREY);
  }
}


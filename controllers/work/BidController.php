<?php
namespace ebidex\controllers\work;

use yii\helpers\Json;
use yii\helpers\Console;
use yii\helpers\ArrayHelper;

use ebidex\workers\BidWorkerCon;
use ebidex\workers\BidWorkerSer;
use ebidex\workers\BidWorkerPur;

use ebidex\models\BidKey;

class BidController extends Controller
{
  public function actionIndex(){
    //공사
    $this->gman_worker->addFunction('ebidex_work_bid_con',function($job){
      $workload=Json::decode($job->workload());
      $this->stdout2("도로> [공사입찰] {$workload['notinum']} {$workload['constnm']}");

      $this->stdout2(" %g{$workload['bidproc']}%n\n");
      $this->run($workload,BidWorkerCon::className());
    });
    //용역
    $this->gman_worker->addFunction('ebidex_work_bid_ser',function($job){
      $workload=Json::decode($job->workload());
      $this->stdout2("도로> [용역입찰] {$workload['notinum']} {$workload['constnm']}");

      $this->stdout2(" %g{$workload['bidproc']}%n\n");
      $this->run($workload,BidWorkerSer::className());
    });
    //구매
    $this->gman_worker->addFunction('ebidex_work_bid_pur',function($job){
      $workload=Json::decode($job->workload());
      $this->stdout2("도로> [구매입찰] {$workload['notinum']} {$workload['constnm']}");

      $this->stdout2(" %g{$workload['bidproc']}%n\n");
      $this->run($workload,BidWorkerPur::className());
    });
    while($this->gman_worker->work());
  }

  public function run($workload,$className){
    try{
      $worker=new $className([
        'notino'=>$workload['notino'],
        'bidno'=>$workload['bidno'],
        'bidseq'=>$workload['bidseq'],
      ]);
      $data=$worker->run();
      //new
      list(,$bno)=explode('-',$data['notinum']);
      $data['bidid']=sprintf('%6sEX%5s%-02s-00-00-01',date('ymd'),$bno,$workload['bidno']);

      print_r($data);
    }catch(\Exception $e){
      $this->stdout("$e\n",Console::FG_RED);
      \Yii::error($e,'ebidex');
    }
    $this->module->db->close();
  }
}


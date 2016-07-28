<?php
namespace ebidex\controllers\work;

use yii\helpers\Json;
use yii\helpers\Console;
use yii\helpers\ArrayHelper;

use ebidex\workers\BidWorkerCon;
use ebidex\workers\BidWorkerSer;
use ebidex\workers\BidWorkerPur;

class BidController extends Controller
{
  public function actionCon(){
    $this->gman_worker->addFunction('ebidex_work_bid_con',function($job){
    });
    while($this->gman_worker->work());
  }

  public function actionSer(){
    $this->gman_worker->addFunction('ebidex_work_bid_ser',function($job){
    });
    while($this->gman_worker->work());
  }

  public function actionPur(){
    $this->gman_worker->addFunction('ebidex_work_bid_pur',function($job){
      echo $job->workload(),PHP_EOL;
      $workload=Json::decode($job->workload());
      try{
        $worker=new BidWorkerPur([
          'notino'=>$workload['notino'],
          'bidno'=>$workload['bidno'],
          'bidseq'=>$workload['bidseq'],
        ]);
        $data=$worker->run();
        print_r($data);
      }catch(\Exception $e){
        $this->stdout("$e\n",Console::FG_RED);
        \Yii::error($e,'ebidex');
      }
    });
    while($this->gman_worker->work());
  }
}


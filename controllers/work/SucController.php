<?php
namespace ebidex\controllers\work;

use yii\helpers\Json;
use yii\helpers\Console;

use ebidex\workers\SucWorkerCon;
use ebidex\workers\SucWorkerSer;
use ebidex\workers\SucWorkerPur;

class SucController extends Controller
{
  public function actionIndex(){
    $this->gman_worker->addFunction('ebidex_work_suc_con',function($job){
      echo $job->workload(),PHP_EOL;
      $workload=Json::decode($job->workload());
      try{
        $worker=new SucWorkerCon([
          'notino'=>$workload['notino'],
          'bidno'=>$workload['bidno'],
          'bidseq'=>$workload['bidseq'],
          'state'=>$workload['state'],
        ]);
        $worker->on('total_page',function($event){
          Console::startProgress(0,$event->sender->succom_total_page);
        });
        $worker->on('page',function($event){
          if($event->sender->succom_total_page==$event->sender->succom_page){
            Console::updateProgress($event->sender->succom_page,$event->sender->succom_total_page);
            Console::endProgress();
          }else{
            Console::updateProgress($event->sender->succom_page,$event->sender->succom_total_page);
          }
        });
        $data=$worker->run();
        print_r($data);
      }catch(\Exception $e){
        $this->stdout("$e\n",Console::FG_RED);
        \Yii::error($e,'ebidex');
      }
    });
    $this->gman_worker->addFunction('ebidex_work_suc_ser',function($job){
      echo $job->workload(),PHP_EOL;
      $workload=Json::decode($job->workload());
      try{
        $worker=new SucWorkerSer([
          'notino'=>$workload['notino'],
          'bidno'=>$workload['bidno'],
          'bidseq'=>$workload['bidseq'],
          'state'=>$workload['state'],
        ]);
        $data=$worker->run();
        print_r($data);
      }catch(\Exception $e){
        $this->stdout("$e\n",Console::FG_RED);
        \Yii::error($e,'ebidex');
      }
    });
    $this->gman_worker->addFunction('ebidex_work_suc_pur',function($job){
      echo $job->workload(),PHP_EOL;
      $workload=Json::decode($job->workload());
      try{
        $worker=new SucWorkerPur([
          'notino'=>$workload['notino'],
          'bidno'=>$workload['bidno'],
          'bidseq'=>$workload['bidseq'],
          'state'=>$workload['state'],
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


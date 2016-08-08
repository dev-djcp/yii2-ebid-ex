<?php
namespace ebidex\controllers\work;

use yii\helpers\Json;
use yii\helpers\Console;
use yii\helpers\ArrayHelper;

use ebidex\workers\SucWorkerCon;
use ebidex\workers\SucWorkerSer;
use ebidex\workers\SucWorkerPur;

use ebidex\models\BidKey;

class SucController extends Controller
{
  public function actionIndex(){
    //공사
    $this->gman_worker->addFunction('ebidex_work_suc_con',function($job){
      $workload=Json::decode($job->workload());
      $this->stdout2("도로> %y[공사]%n {$workload['notinum']} {$workload['constnm']}");
      if(ArrayHelper::isIn($workload['bidproc'],['유찰','재공고'])) $this->stdout2(" %y{$workload['bidproc']}%n\n");
      else $this->stdout2(" %g{$workload['bidproc']}%n\n");
      $this->run($workload,SucWorkerCon::className());
    });
    //용역
    $this->gman_worker->addFunction('ebidex_work_suc_ser',function($job){
      $workload=Json::decode($job->workload());
      $this->stdout2("도로> %g[용역]%n {$workload['notinum']} {$workload['constnm']}");
      if(ArrayHelper::isIn($workload['bidproc'],['유찰','재공고'])) $this->stdout2(" %y{$workload['bidproc']}%n\n");
      else $this->stdout2(" %g{$workload['bidproc']}%n\n");
      $this->run($workload,SucWorkerSer::className());
    });
    //구매
    $this->gman_worker->addFunction('ebidex_work_suc_pur',function($job){
      $workload=Json::decode($job->workload());
      $this->stdout2("도로> %b[구매]%n {$workload['notinum']} {$workload['constnm']}");
      if(ArrayHelper::isIn($workload['bidproc'],['유찰','재공고'])) $this->stdout2(" %y{$workload['bidproc']}%n\n");
      else $this->stdout2(" %g{$workload['bidproc']}%n\n");
      $this->run($workload,SucWorkerPur::className());
    });
    while($this->gman_worker->work());
  }

  public function run($workload,$className){
    try{
      $notinum_ex=$workload['bidno'];

      if(isset($workload['bidproc']) and ArrayHelper::isIn($workload['bidproc'],['유찰','재공고'])){
        $query=BidKey::find()->where(['whereis'=>'08','notinum'=>$data['notinum']]);
        if($notinum_ex==1){
          $query->andWhere("notinum_ex='' or notinum_ex='1'");
        }else{
          $query->andWhere(['notinum_ex'=>$notinum_ex]);
        }      
        $bidkey=$query->orderBy('bidid desc')->limit(1)->one();
        if($bidkey!==null and $bidkey->bidproc!=='F'){
          $this->gman_client->doBackground('i2_auto_suc_test',Json::encode([
            'bidid'=>$bidkey->bidid,
            'bidproc'=>'F',
          ]));
          $this->stdout2("    >>> $bidkey->bidid ($bidkey->bidproc) %y유찰%n\n");
        }
        return;
      }

      $worker=new $className([
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

      $query=BidKey::find()->where(['whereis'=>'08','notinum'=>$data['notinum']]);
      if($notinum_ex==1){
        $query->andWhere("notinum_ex='' or notinum_ex='1'");
      }else{
        $query->andWhere(['notinum_ex'=>$notinum_ex]);
      }      
      $bidkey=$query->orderBy('bidid desc')->limit(1)->one();
      if($bidkey===null) return;
      $bidvalue=$bidkey->bidValue;
      $data['multispare']=$bidvalue->multispare;
      if(is_array($data['selms'])){
        $selms=[];
        $multispares=explode('|',$data['multispare']);
        foreach($multispares as $i=>$v){
          if(ArrayHelper::isIn($v,$data['selms'])){
            $selms[]=$i+1;
          }
        }
        $data['selms']=join('|',$selms);
      }
      $data['bidid']=$bidkey->bidid;
      $data['bidproc']='S';
      $this->gman_client->doBackground('i2_auto_suc_test',Json::encode($data));
      $this->stdout2("    >>> $bidkey->bidid ($bidkey->bidproc) 예가:{$data['yega']} 참여수:{$data['innum']} %g개찰%n\n");
    }
    catch(\Exception $e){
      $this->stdout("$e\n",Console::FG_RED);
      \Yii::error($e,'ebidex');
    }
  }
}


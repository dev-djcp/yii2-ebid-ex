<?php
namespace ebidex\controllers;

use Yii;
use yii\helpers\Console;
use yii\helpers\Json;
use yii\helpers\ArrayHelper;

use Exception;
use GearmanClient;

use ebidex\WatchEvent;
use ebidex\watchers\ConBidWatcher;
use ebidex\watchers\SerBidWatcher;
use ebidex\models\BidKey;
use ebidex\models\BidModifyCheck;

use ebidex\watchers\BidWatcherCon;
use ebidex\watchers\BidWatcherSer;
use ebidex\watchers\BidWatcherPur;

use ebidex\watchers\SucWatcherCon;
use ebidex\watchers\SucWatcherSer;
use ebidex\watchers\SucWatcherPur;

class WatchController extends \yii\console\Controller
{
  public $gman_client;
  public function init(){
    $this->gman_client=new \GearmanClient;
    $this->gman_client->addServer($this->module->gman_server);
  }

  public function stdout2($string){
    return $this->stdout(Console::renderColoredString($string));
  }

  public function findBidKey($row){
    $notinum_ex=$row['bidno'];
    $query=BidKey::find()->where([
      'whereis'=>'08',
      'notinum'=>$row['notinum'],
    ]);
    if($notinum_ex==1){
      $query->andWhere("notinum_ex='' or notinum_ex='1'");
    }else{
      $query->andWhere(['notinum_ex'=>$notinum_ex]);
    }
    $bidkey=$query->orderBy('bidid desc')->limit(1)->one();
    return $bidkey;
  }

  public function bidcheck(BidKey $bidkey,array $row){
    if($bidkey->bidproc=='B' and $bidkey->state=='Y'){
      $bidcheck=BidModifyCheck::findOne($bidkey->bidid);
      if($bidcheck===null){
        $this->stdout2(" %yCHECK%n");
        $this->gman_client->doBackground('ebidex_work_bid_'.$bidkey->bidtype,Json::encode($row));
      }else{
        $diff=tiem()-$bidcheck->check_at;
        if($diff>=60*60*1){
          $this->stdout2(" %yCHECK%n");
          $this->gman_client->doBackground('ebidex_work_bid_'.$bidkey->bidtype,Json::encode($row));
        }
        sleep(3);
      }
    }
  }

  public function actionBid(){
    $con=new BidWatcherCon;
    $ser=new BidWatcherSer;
    $pur=new BidWatcherPur;
    while(true){
      $start=date('Ymd',strtotime('-1 month'));
      $end=date('Ymd');
      try{
        $con->watch($start,$end,function($row){
          $this->stdout2("도로> %y[공사입찰]%n {$row['notinum']} {$row['constnm']} ({$row['local']},{$row['multi']},{$row['bidproc']})");
          $bidkey=$this->findBidKey($row);
          if($bidkey!==null){
            if($row['bidproc']=='취소공고' and $bidkey->bidproc!=='C'){
              $this->gman_client->doBackground('ebidex_work_bid_con',Json::encode($row));
              sleep(3);
              return;
            }
            else if($row['bidseq']>1 and $bidkey->orgcode_y!=$row['bidseq']){
              $this->gman_client->doBackground('ebidex_work_bid_con',Json::encode($row));
              sleep(3);
              return;
            }
            $this->bidcheck($bidkey,$row);
            $this->stdout("\n");
            return;
          }
          $this->stdout2(" %yNEW%n\n");
          $this->gman_client->doBackground('ebidex_work_bid_con',Json::encode($row));
          sleep(5);
        });

        $ser->watch($start,$end,function($row){
          $this->stdout2("도로> %g[용역입찰]%n {$row['notinum']} {$row['constnm']} ({$row['local']},{$row['multi']},{$row['bidproc']})");
          $bidkey=$this->findBidKey($row);
          if($bidkey!==null){
            if($row['bidproc']=='취소공고' and $bidkey->bidproc!=='C'){
              $this->gman_client->doBackground('ebidex_work_bid_ser',Json::encode($row));
              sleep(3);
              return;
            }
            //정정
            else if($row['bidseq']>1 and $bidkey->orgcode_y!=$row['bidseq']){
              $this->gman_client->doBackground('ebidex_work_bid_con',Json::encode($row));
              sleep(3);
              return;
            }
            $this->bidcheck($bidkey,$row);
            $this->stdout("\n");
            return;
          }
          $this->stdout2(" %yNEW%n\n");
          $this->gman_client->doBackground('ebidex_work_bid_ser',Json::encode($row));
          sleep(5);
        });

        $pur->watch($start,$end,function($row){
          $this->stdout2("도로> %b[구매입찰]%n {$row['notinum']} {$row['constnm']} ({$row['local']},{$row['multi']},{$row['bidproc']})");
          $bidkey=$this->findBidKey($row);
          if($bidkey!==null){
            if($row['bidproc']=='취소공고' and $bidkey->bidproc!=='C'){
              $this->gman_client->doBackground('ebidex_work_bid_pur',Json::encode($row));
              sleep(3);
              return;
            }
            else if($row['bidseq']>1 and $bidkey->orgcode_y!=$row['bidseq']){
              $this->gman_client->doBackground('ebidex_work_bid_pur',Json::encode($row));
              $this->stdout(" > 정정공고 처리요청 : ebidex_work_bid_pur\n",Console::FG_GREEN);
              sleep(3);
              return;
            }
            $this->bidcheck($bidkey,$row);
            $this->stdout("\n");
            return;
          }
          $this->stdout2(" %yNEW%n\n");
          $this->gman_client->doBackground('ebidex_work_bid_pur',Json::encode($row));
          sleep(5);
        });
      }catch(\Exception $e){
        $this->stdout("$e\n",Console::FG_RED);
        \Yii::error($e,'ebidex');
      }
      $this->stdout(sprintf("[%s] Peak memory usage: %s MB\n",
        date('Y-m-d H:i:s'),
        (memory_get_peak_usage(true)/1024/1024))
      ,Console::FG_GREY);
      sleep(mt_rand(5,10));
    }
  }

  public function actionSuc(){
    $con=new SucWatcherCon;
    $ser=new SucWatcherSer;
    $pur=new SucWatcherPur;
    while(true){
      $start=date('Ymd',strtotime('-1 month'));
      $end=date('Ymd');
      try{
        $con->watch($start,$end,function($row){
          $this->stdout2("도로> %y[공사낙찰]%n {$row['notinum']} {$row['constnm']} ({$row['local']},{$row['multi']},{$row['bidproc']})");
          $bidkey=$this->findBidKey($row);
          if($bidkey===null){
            $this->stdout2(" %rERROR%n\n");
            return;
          }

          $this->stdout2(" [{$bidkey->bidproc}]");
          if(ArrayHelper::isIn($row['bidproc'],['유찰','재공고']) and $bidkey->bidproc=='F'){
            $this->stdout("\n");
            return;
          }
          else if($bidkey->bidproc=='S'){
            $this->stdout("\n");
            return;
          }
          $this->stdout2(" %yNEW%n\n");
          sleep(5);
          $this->gman_client->doBackground('ebidex_work_suc_con',Json::encode($row));
        });

        $ser->watch($start,$end,function($row){
          $this->stdout2("도로> %g[용역낙찰]%n {$row['notinum']} {$row['constnm']} ({$row['local']},{$row['multi']},{$row['bidproc']})");
          $bidkey=$this->findBidKey($row);
          if($bidkey===null){
            $this->stdout2(" %rERROR%n\n");
            return;
          }

          $this->stdout2(" [{$bidkey->bidproc}]");
          if(ArrayHelper::isIn($row['bidproc'],['유찰','재공고']) and $bidkey->bidproc=='F'){
            $this->stdout("\n");
            return;
          }
          else if($bidkey->bidproc=='S'){
            $this->stdout("\n");
            return;
          }
          $this->stdout2(" %yNEW%n\n");
          sleep(5);
          $this->gman_client->doBackground('ebidex_work_suc_ser',Json::encode($row));
        });

        $pur->watch($start,$end,function($row){
          $this->stdout2("도로> %b[구매낙찰]%n {$row['notinum']} {$row['constnm']} ({$row['local']},{$row['multi']},{$row['bidproc']})");
          $bidkey=$this->findBidKey($row);
          if($bidkey===null){
            $this->stdout2(" %rERROR%n\n");
            return;
          }
          $this->stdout2(" [{$bidkey->bidproc}]");
          if(ArrayHelper::isIn($row['bidproc'],['유찰','재공고']) and $bidkey->bidproc=='F'){
            $this->stdout("\n");
            return;
          }
          else if($bidkey->bidproc=='S'){
            $this->stdout("\n");
            return;
          }
          $this->stdout2(" %yNEW%n\n");
          $this->gman_client->doBackground('ebidex_work_suc_pur',Json::encode($row));
          sleep(5);
        });
      }catch(\Exception $e){
        $this->stdout("$e\n",Console::FG_RED);
        \Yii::error($e,'ebidex');
      }
      $this->stdout(sprintf("[%s] Peak memory usage: %s MB\n",
        date('Y-m-d H:i:s'),
        (memory_get_peak_usage(true)/1024/1024))
      ,Console::FG_GREY);
      sleep(mt_rand(5,10));
    }
  }

}


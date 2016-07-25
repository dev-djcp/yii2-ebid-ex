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
          $bidkey=BidKey::find()->where([
            'whereis'=>'08',
            'notinum'=>$row['notinum'],
          ])->orderBy('bidid desc')->limit(1)->one();
          if($bidkey!==null){
            $this->stdout("\n");
            return;
          }

          $this->stdout2(" %yNEW%n\n");
          //sleep(5);
        });

        $ser->watch($start,$end,function($row){
          $this->stdout2("도로> %g[용역입찰]%n {$row['notinum']} {$row['constnm']} ({$row['local']},{$row['multi']},{$row['bidproc']})");
          $bidkey=BidKey::find()->where([
            'whereis'=>'08',
            'notinum'=>$row['notinum'],
          ])->orderBy('bidid desc')->limit(1)->one();
          if($bidkey!==null){
            $this->stdout("\n");
            return;
          }

          $this->stdout2(" %yNEW%n\n");
          //sleep(5);
        });

        $pur->watch($start,$end,function($row){
          $this->stdout2("도로> %b[구매입찰]%n {$row['notinum']} {$row['constnm']} ({$row['local']},{$row['multi']},{$row['bidproc']})");
          $bidkey=BidKey::find()->where([
            'whereis'=>'08',
            'notinum'=>$row['notinum'],
          ])->orderBy('bidid desc')->limit(1)->one();
          if($bidkey!==null){
            $this->stdout("\n");
            return;
          }

          $this->stdout2(" %yNEW%n\n");
          sleep(5);
          $this->gman_client->doBackground('ebidex_work_bid_pur',Json::encode($row));
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
          $notinum=$row['notinum'];
          $bidkey=BidKey::find()->where([
            'whereis'=>'08',
          ])->andWhere("notinum like '{$notinum}%'")->orderBy('bidid desc')->limit(1)->one();
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
          //sleep(5);
        });

        $ser->watch($start,$end,function($row){
          $this->stdout2("도로> %g[용역낙찰]%n {$row['notinum']} {$row['constnm']} ({$row['local']},{$row['multi']},{$row['bidproc']})");
          $notinum=$row['notinum'];
          $bidkey=BidKey::find()->where([
            'whereis'=>'08',
          ])->andWhere("notinum like '{$notinum}%'")->orderBy('bidid desc')->limit(1)->one();
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
          //sleep(5);
        });

        $pur->watch($start,$end,function($row){
          $this->stdout2("도로> %b[구매낙찰]%n {$row['notinum']} {$row['constnm']} ({$row['local']},{$row['multi']},{$row['bidproc']})");
          $notinum=$row['notinum'];
          $bidkey=BidKey::find()->where([
            'whereis'=>'08',
          ])->andWhere("notinum like '{$notinum}%'")->orderBy('bidid desc')->limit(1)->one();
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
          //$this->gman_client->doBackground('ebidex_work_bid_pur',Json::encode($row));
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

  public function actionIndex(){
    $conBidWatcher=new ConBidWatcher;
    $conBidWatcher->on(WatchEvent::EVENT_BID,[$this,'onBid']);

    $serBidWatcher=new SerBidWatcher;
    $serBidWatcher->on(WatchEvent::EVENT_BID,[$this,'onBid']);

    while(true){
      try{
        $conBidWatcher->watch();
        sleep(mt_rand(600,900));
        $serBidWatcher->watch();
      }catch(Exception $e){
        $this->stdout($e.PHP_EOL,Console::FG_RED);
        Yii::error($e,'ebidex');
      }
      $this->stdout(sprintf("[%s] Peak memory usage: %s MB\n",date('Y-m-d H:i:s'),(memory_get_peak_usage(true)/1024/1024)),Console::FG_GREY);
      sleep(mt_rand(600,900));
    }
  }

  public function onBid($event){
    $bid=$event->bid;
    $this->stdout('['.$event->bidtype.'] '.implode(',',$event->bid)."\n");
    $bid_key=BidKey::findOne([
      'notinum'=>$bid['notinum'],
      'whereis'=>'08',
    ]);
    if($bid_key===null){
      $this->stdout(" > 공고누락!!\n",Console::FG_RED);
      $gman_client=new GearmanClient;
      $gman_client->addServers('192.168.1.242');
      $gman_client->doBackground('send_chat_message_from_admin',Json::encode([
        'recv_id'=>149,
        'message'=>"도로공사 공고누락\n".implode(',',$event->bid),
      ]));
    }
  }
}


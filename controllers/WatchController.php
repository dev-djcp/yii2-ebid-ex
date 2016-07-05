<?php
namespace ebidex\controllers;

use Yii;
use yii\helpers\Console;
use yii\helpers\Json;

use Exception;
use GearmanClient;

use ebidex\WatchEvent;
use ebidex\watchers\ConBidWatcher;
use ebidex\watchers\SerBidWatcher;
use ebidex\models\BidKey;

class WatchController extends \yii\console\Controller
{
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


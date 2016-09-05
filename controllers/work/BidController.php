<?php
namespace ebidex\controllers\work;

use yii\helpers\Json;
use yii\helpers\Console;
use yii\helpers\ArrayHelper;
use yii\helpers\VarDumper;

use ebidex\BidFile;
use ebidex\workers\BidWorkerCon;
use ebidex\workers\BidWorkerSer;
use ebidex\workers\BidWorkerPur;
use ebidex\models\BidKey;
use ebidex\models\BidModifyCheck;

class BidController extends Controller
{
  public $i2_gman_func='i2_auto_bid_test';

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
      $data['whereis']='08';
      $data['orgcode_y']=$workload['bidseq']; //차수

      if(isset($workload['changeConstnm']) and isset($workload['constnm'])){
        $data['constnm']=$workload['constnm'];
      }

      if($workload['bidproc']=='취소공고'){
        $query=BidKey::find()->where([
          'whereis'=>'08',
          'notinum'=>$data['notinum'],
        ]);
        if($data['notinum_ex']==1){
          $query->andWhere("notinum_ex='' or notinum_ex='1'");
        }else{
          $query->andWhere(['notinum_ex'=>$data['notinum_ex']]);
        }
        $bidkey=$query->orderBy('bidid desc')->limit(1)->one();
        if($bidkey!==null and $bidkey->bidproc!='C' and $bidkey->orgcode_y<=$workload['bidseq']){
          list($a,$b,$c,$d)=explode('-',$bidkey->bidid);
          $b=sprintf('%02s',intval($b)+1);
          $data['bidid']="$a-$b-$c-$d";
          $data['bidproc']='C';
          $this->gman_client->doNormal($this->i2_gman_func,Json::encode($data));
          $this->stdout2("   %g>>> do {$this->i2_gman_func} {$data['bidid']} {$data['bidproc']}%n\n");
        }
      }
      else if($workload['bidproc']=='공고중' or $workload['bidproc']=='정정공고중'){
        $query=BidKey::find()->where([
          'whereis'=>'08',
          'notinum'=>$data['notinum'],
        ]);
        if($data['notinum_ex']==1){
          $query->andWhere("notinum_ex='' or notinum_ex='1'");
        }else{
          $query->andWhere(['notinum_ex'=>$data['notinum_ex']]);
        }
        $bidkey=$query->orderBy('bidid desc')->limit(1)->one();
        if($bidkey===null){
          //new
          list(,$bno)=explode('-',$data['notinum']);
          $data['bidid']=sprintf('%6sEX%5s%-02s-00-00-01',date('ymd'),$bno,$workload['bidno']);
          $data['bidproc']='B';
          $this->gman_client->doNormal($this->i2_gman_func,Json::encode($data));
          $this->stdout2("   %g>>> do {$this->i2_gman_func} {$data['bidid']} {$data['bidproc']}%n\n");
        }else{
          if($workload['bidseq']>1 and $bidkey->orgcode_y!=$workload['bidseq']){ //정정공고
            list($a,$b,$c,$d)=explode('-',$bidkey->bidid);
            $b=sprintf('%02s',intval($b)+1);
            $data['bidid']="$a-$b-$c-$d";
            $data['bidproc']='M';
            $this->gman_client->doNormal($this->i2_gman_func,Json::encode($data));
            $this->stdout2(" %g> do {$this->i2_gman_func} {$data['bidid']} {$data['bidproc']}%n\n");
          }
        }
      }

      \Yii::info(VarDumper::dumpAsString($data),'ebidex');

      //-------------------------------
      // 임의수정 check
      //-------------------------------
      if($bidkey!==null){
        $bidcheck=BidModifyCheck::findOne($bidkey->bidid);
        if($bidcheck===null) $bidcheck=new BidModifyCheck(['bidid'=>$bidkey->bidid]);
        $bid_hash=md5(join('',$data));
        $noticeDoc=BidFile::findNoticeDoc($data['attchd_lnk']);
        if($noticeDoc!==null and $noticeDoc->download()){
          $file_hash=md5_file($noticeDoc->saveDir.'/'.$noticeDoc->savedName);
          $noticeDoc->remove();
        }
        if(!empty($bidcheck->bid_hash) and $bidcheck->bid_hash!=$bid_hash){
          $this->stdout(" > check : bid_hash diff\n",Console::FG_YELLOW);
          $this->sendMessage("도로공사 공고정보 확인필요! [{$data['notinum']}]");
        }
        else if(!empty($bidcheck->file_hash) and $bidcheck->file_hash!=$file_hash){
          $this->stdout(" > check : file_hash diff\n",Console::FG_YELLOW);
          $this->sendMessage("도로공사 공고원문 확인필요! [{$data['notinum']}]");
        }
        $bidcheck->bid_hash=$bid_hash;
        $bidcheck->file_hash=$file_hash;
        $bidcheck->check_at=time();
        $bidcheck->save();
        $this->stdout2(" %b>>> bid modify check%n\n");
      }

      //----------------------------
      // 복수공고처리
      //----------------------------
      if(isset($data['multi_list'])){
        $notinum=$data['notinum'];
        $bidtype=$data['bidtype'];
        foreach($data['multi_list'] as $next){
          if($next['bidno']>$worker->bidno){
            $this->gman_client->doBackground('ebidex_work_bid_'.$bidtype,Json::encode([
              'notinum'=>$notinum,
              'notino'=>$next['notino'],
              'bidno'=>$next['bidno'],
              'bidseq'=>$next['bidseq'],
              'constnm'=>$next['constnm'],
              'changeConstnm'=>'1',
              'bidproc'=>$workload['bidproc'],
            ]));
            break;
          }
        }
      }
    }catch(\Exception $e){
      $this->stdout("$e\n",Console::FG_RED);
      \Yii::error($e,'ebidex');
      $this->sendMessage('ebidex:'.$e->getMessage());
    }
    $this->stdout(sprintf("[%s] Peak memory usage: %sMb\n",
      date('Y-m-d H:i:s'),
      (memory_get_peak_usage(true)/1024/1024)
    ),Console::FG_GREY);
    $this->module->db->close();
    sleep(mt_rand(3,6));
  }

  public function sendMessage($msg){
    $gman=new \GearmanClient;
    $gman->addServers('115.68.48.242');
    $gman->doBackground('send_chat_message_from_admin',Json::encode([
      'recv_id'=>149,
      'message'=>$msg,
    ]));
  }
}


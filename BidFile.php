<?php
namespace ebidex;

use Yii;

class BidFile extends \yii\base\Component
{
  public $realname;
  public $downloadUrl='http://ebid.ex.co.kr/ebid/FileDownload';
  public $postData;
  public $saveDir='/tmp';
  public $savedName;

  public static function findNoticeDoc($attchd_lnk){
    $attchd_lnks=explode('|',$attchd_lnk);
    if(empty($attchd_lnks)) return null;

    foreach($attchd_lnks as $i=>$lnk){
      list($realname,$downinfo)=explode('#',$lnk);
      $postData=str_replace('("','',str_replace('")','',$downinfo));
      $postData=iconv('utf-8','euckr',$postData);
      if( (strpos($realname,'공고서')!==false) ||
          (strpos($realname,'입찰공고')!==false && strpos($realname,'내역서')===false) ||
          (strpos($realname,'공고문')!==false)
        ){
        return Yii::createObject([
          'class'=>static::className(),
          'realname'=>$realname,
          'postData'=>$postData,
        ]);
      }
    }

    list($realname,$downinfo)=explode('#',$attchd_lnks[0]);
    $postData=str_replace('("','',str_replace('")','',$downinfo));
    return Yii::createObject([
      'class'=>static::className(),
      'realname'=>$realname,
      'postData'=>$postData,
    ]);
  }

  public function download(){
    $this->savedName=md5($this->realname);
    $filename=$this->saveDir.'/'.$this->savedName;
    $cmd="http_proxy=http://115.68.47.39:3128 wget -U 'Mozilla/4.0' -q -T 30 -O $filename --post-data='$this->postData' '$this->downloadUrl'";
    $res=exec($cmd,$output,$ret);
    if($ret!=0){
      return false;
    }
    return true;
  }

  public function remove(){
    @unlink($this->saveDir.'/'.$this->savedName);
  }
}


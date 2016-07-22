<?php
namespace ebidex;

class Http extends \yii\base\Component
{
  public $client;
  const URL_BASE='http://ebid.ex.co.kr';
  const URL_BID_LIST_CON='/ebid/jsps/ebid/const/bidNoti/bidNotiCompanyList.jsp';
  const URL_BID_LIST_SER='/ebid/jsps/ebid/serv/bidNoti/bidNotiCompanyList.jsp';
  const URL_BID_LIST_PUR='/ebid/jsps/ebid/buy/bidNoti/bidNotiCompanyList.jsp';
  const URL_BID_DETAIL_CON='';
  const URL_BID_DETAIL_SER='';
  const URL_BID_DETAIL_PUR='';
  const URL_SUC_LIST_CON='';
  const URL_SUC_LIST_SER='';
  const URL_SUC_LIST_PUR='';

  public function init(){
    parent::init();

    $this->client=new \GuzzleHttp\Client([
      'base_uri'=>static::URL_BASE,
      'cookies'=>true,
      'allow_redirects'=>false,
      'proxy'=>'tcp://115.68.47.39:3128',
      //'debug'=>true,
      'headers'=>[
        'User-Agent'=>'Mozilla/5.0 (Windows NT 10.0; WOW64; Trident/7.0; rv:11.0) like Gecko',
        'Connection'=>'Keep-Alive',
      ],
    ]);
  }

  public function request($method,$uri='',array $options=[]){
    $res=$this->client->request($method,$uri,$options);
    $body=$res->getBody();
    $html=iconv('euckr','utf-8//IGNORE',$body);
    return $html;
  }

  public function get($uri,array $query=[]){
    $html=$this->request('GET',$uri,['query'=>$query]);
    return $html;
  }

  public static function match($pattern,$html,$label){
    $pattern=str_replace(' ','\s*',$pattern);
    $ret='';
    if(preg_match($p,$html,$m)){
      if(is_array($label)){
        $ret=[];
        foreach($label as $v){
          $ret[$v]=trim($m[$v]);
        }
      }
      else{
        $ret=trim($m[$label]);
      }
    }
    return $ret;
  }
}


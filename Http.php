<?php
namespace ebidex;

class Http extends \yii\base\Component
{
  public $client;
  const URL_BASE='http://ebid.ex.co.kr';
  const URL_BID_LIST_CON='/ebid/jsps/ebid/const/bidNoti/bidNotiCompanyList.jsp';
  const URL_BID_LIST_SER='/ebid/jsps/ebid/serv/bidNoti/bidNotiCompanyList.jsp';
  const URL_BID_LIST_PUR='/ebid/jsps/ebid/buy/bidNoti/bidNotiCompanyList.jsp';
  const URL_BID_DETAIL_CON='/ebid/jsps/ebid/const/bidNoti/bidNotiCompanyRead.jsp';
  const URL_BID_DETAIL_SER='/ebid/jsps/ebid/serv/bidNoti/bidNotiCompanyRead.jsp';
  const URL_BID_DETAIL_PUR='/ebid/jsps/ebid/buy/bidNoti/bidNotiCompanyRead.jsp';
  const URL_SUC_LIST_CON='/ebid/jsps/ebid/const/bidResult/bidResultList.jsp';
  const URL_SUC_LIST_SER='/ebid/jsps/ebid/serv/bidResult/bidResultList.jsp';
  const URL_SUC_LIST_PUR='/ebid/jsps/ebid/buy/bidResult/bidResultList.jsp';
  const URL_SUC_DETAIL_CON='/ebid/jsps/ebid/const/bidResult/bidResultDetail.jsp';
  const URL_SUC_DETAIL_CON_SUCCOM='/ebid/jsps/ebid/const/bidResult/bidResult.jsp';
  const URL_SUC_DETAIL_CON_MULTI='/ebid/jsps/ebid/const/bidResult/bidResultNego3.jsp';
  const URL_SUC_DETAIL_SER='/ebid/jsps/ebid/serv/bidResult/bidResultDetail.jsp';
  const URL_SUC_DETAIL_SER_SUCCOM='/ebid/jsps/ebid/serv/bidResult/bidResult.jsp';
  const URL_SUC_DETAIL_SER_MULTI='/ebid/jsps/ebid/serv/bidResult/bidResultNego3.jsp';
  const URL_SUC_DETAIL_PUR='/ebid/jsps/ebid/buy/bidResult/bidResultDetail.jsp';
  const URL_SUC_DETAIL_PUR_SUCCOM='/ebid/jsps/ebid/buy/bidResult/bidResult.jsp';
  const URL_SUC_DETAIL_PUR_MULTI='/ebid/jsps/ebid/buy/bidResult/bidResultNego.jsp';

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
    $p=str_replace(' ','\s*',$pattern);
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


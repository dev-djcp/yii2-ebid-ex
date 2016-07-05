<?php
namespace ebidex;

use yii\helpers\ArrayHelper;

class ExHttp extends \yii\base\Component
{
  public $client;

  public function init(){
    parent::init();

    $this->client=new \GuzzleHttp\Client([
      'base_uri'=>'http://ebid.ex.co.kr',
      'headers'=>[
        'User-Agent'=>'Mozilla/5.0 (Windows NT 10.0; WOW64; Trident/7.0; rv:11.0) like Gecko',
        'cookies'=>true,
      ],
    ]);
  }

  public function request_con_suc($params,$callback){
    $form_params=$params;
    $res=$this->client->request('GET','/ebid/jsps/ebid/const/bidResult/bidResultList.jsp',[
      'query'=>$form_params,
    ]);
    $body=$res->getBody();
    $html=(string)$body;
    $html=iconv('euckr','utf-8//IGNORE',$html);

    if(preg_match('#\[현재/전체페이지: \d+/(?<total_page>\d+)\]#',$html,$m)){
      $total_page=intval($m['total_page']);
    }

    if(!$total_page) return;

    for($i=1; $i<=$total_page;$i++){
      if($i>1){
        $form_params['page']=$i;
        $form_params['startnum']+=10;
        $form_params['endnum']+=10;
        $res=$this->client->request('GET','/ebid/jsps/ebid/const/bidResult/bidResultList.jsp',[
          'query'=>$form_params,
        ]);
        $body=$res->getBody();
        $html=iconv('euckr','utf-8//IGNORE',$body);
      }
      $html=strip_tags($html,'<tr><td><a>');
      $html=preg_replace('/<td[^>]*>/','<td>',$html);
      $p='#<tr>'.
          ' <td>\d+</td>'.
          ' <td>(?<notinum>\d{4}-\d{5})</td>'.
          ' <td>[^<]*</td>'.
          ' <td><a[^>]*>(?<constnm>[^<]*)</a></td>'.
          ' <td>(?<multi>[^<]*)</td>'.
          ' <td>[^<]*</td>'.
          ' <td>(?<resdate>\d{4}-\d{2}-\d{2})</td>'.
          ' <td>(?<state>[^<]*)</td>'.
         ' </tr>#';
      if(preg_match_all(str_replace(' ','\s*',$p),$html,$matches,PREG_SET_ORDER)){
        foreach($matches as $m){
          $callback([
            'notinum'=>trim($m['notinum']),
            'constnm'=>trim($m['constnm']),
            'multi'=>trim($m['multi']),
            'resdate'=>trim($m['resdate']),
            'state'=>trim($m['state']),
          ]);
        }
      }
      sleep(1);
    }
  }

  public function request($method,$uri='',array $options=[]){
    $res=$this->client->request($method,$uri,$options);
    $body=$res->getBody();
    $html=iconv('euckr','utf-8//IGNORE',$body);
    $html=strip_tags($html,'<tr><td><a>');
    $html=preg_replace('/<td[^>]*>/','<td>',$html);
    return $html;
  }
}


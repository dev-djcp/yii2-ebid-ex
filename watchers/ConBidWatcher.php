<?php
namespace ebidex\watchers;

use ebidex\WatchEvent;

/**
 * 공사입찰
 */
class ConBidWatcher extends \yii\base\Component
{
  const URL='/ebid/jsps/ebid/const/bidNoti/bidNotiCompanyList.jsp';

  public function watch(){
    $http=new \ebidex\ExHttp;
    $params=[
      'status'=>'Z',
      'startnum'=>1,
      'endnum'=>10,
      's_noti_date'=>date('Ymd',strtotime('-1 month')),
      'e_noti_date'=>date('Ymd'),
    ];
    try {
      $html=$http->request('GET',self::URL,[
        'query'=>$params
      ]);
      if(preg_match('#\[현재/전체페이지: \d+/(?<total_page>\d+)\]#',$html,$m)){
        $total_page=intval($m['total_page']);
      }
      if(!$total_page){
        return;
      }
      for($page=1; $page<=$total_page; $page++){
        if($page>1){
          $params['page']=$page;
          $params['startnum']+=10;
          $params['endnum']+=10;
          $html=$http->request('GET',self::URL,['query'=>$params]);
        }
        $p='#<tr>'.
            ' <td>\d+</td>'.
            ' <td>(?<notinum>\d{4}-\d{5})</td>'.
            ' <td>(?<local>[^<]*)</td>'. //지역
            ' <td> <a[^>]*>(?<constnm>[^<]*)</a> </td>'. //공고명
            ' <td>(?<multi>[^<]*)</td>'. //복수
            ' <td>[^<]*</td>'. //지문
            ' <td>[^<]*</td>'. //계약방법
            ' <td>(?<noticedt>\d{4}-\d{2}-\d{2})</td>'. //공고일자
            ' <td>(?<status>[^<]*)</td>'. //상태
            ' </tr>#';
        if(preg_match_all(str_replace(' ','\s*',$p),$html,$matches,PREG_SET_ORDER)){
          foreach($matches as $m){
            $data=[
              'notinum'=>trim($m['notinum']),
              'local'=>trim($m['local']),
              'constnm'=>trim($m['constnm']),
              'multi'=>trim($m['multi']),
              'noticedt'=>trim($m['noticedt']),
              'status'=>trim($m['status']),
            ];
            $event=new WatchEvent;
            $event->bidtype='con';
            $event->bid=$data;
            $this->trigger(WatchEvent::EVENT_BID,$event);
          }
        }
        sleep(1);
      }
    }
    catch(\Exception $e){
      throw $e;
    }
  }
}


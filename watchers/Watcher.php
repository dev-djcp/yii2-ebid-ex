<?php
namespace ebidex\watchers;

abstract class Watcher extends \ebidex\Http
{
  public $query;

  protected $_totalPage;
  protected $_html;
  protected $_page;

  protected $pattern;

  public function init(){
    parent::init();
    $this->setPattern();
  }

  protected function setPattern(){
    $this->pattern='#<tr>'.
      ' <td>\d+</td>'.
      ' <td> (?<notinum>\d{4}-\d{5}) </td>'.
      ' <td>(?<local>[^<]*)</td>'.
      ' <td> <a[^>]*>(?<constnm>[^<]*)</a> </td>'.
      ' <td>(?<multi>[^<]*)</td>'.
      ' <td>[^<]*</td>'.
      ' <td>(?<contract>[^<]*)</td>'.
      ' <td> (?<noticedt>\d{4}-\d{2}-\d{2}) </td>'.
      ' <td>(?<bidproc>[^<]*)</td>'.
      ' </tr>#';
    $this->pattern=str_replace(' ','\s*',$this->pattern);
  }

  public function watch($start,$end,$callback){
    $this->_page=1;
    $this->query=[
      'status'=>'Z',
      'startnum'=>1,
      'endnum'=>10,
      's_noti_date'=>$start,
      'e_noti_date'=>$end,
    ];
    try{
      $this->_html=$this->getList();
      if(!$this->_totalPage=$this->matchTotalPage()){
        throw new \Exception('전체페이지를 찾을 수 없습니다.');
      }
      for($this->_page=1; $this->_page<=$this->_totalPage; $this->_page++){
        if($this->_page>1){
          $this->query['page']=$this->_page;
          $this->query['startnum']+=10;
          $this->query['endnum']+=10;
          $this->_html=$this->getList();
        }
        $this->parseList($callback);
        sleep(1);
      }
    }catch(\Exception $e){
      throw $e;
    }
  }

  abstract protected function getList();

  protected function parseList($callback){
    if(preg_match_all($this->pattern,$this->_html,$matches,PREG_SET_ORDER)){
      foreach($matches as $m){
        $data=[
          'notinum'=>trim($m['notinum']),
          'local'=>trim($m['local']),
          'constnm'=>trim($m['constnm']),
          'multi'=>trim($m['multi']),
          'contract'=>trim($m['contract']),
          'noticedt'=>trim($m['noticedt']),
          'bidproc'=>trim($m['bidproc']),
        ];
        $callback($data);
      }
    }
  }

  public function matchTotalPage(){
    $p='#\[현재/전체페이지: \d+/(?<total_page>\d+)\]#';
    $p=str_replace(' ','\s*',$p);
    if(preg_match($p,$this->_html,$m)){
      $total_page=intval($m['total_page']);
      return $total_page;
    }
    return false;
  }

  public function getTotalPage(){
    return $this->_totalPage;
  }

  public function getHtml(){
    return $this->_html;
  }
}


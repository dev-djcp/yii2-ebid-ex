<?php
namespace ebidex\workers;

abstract class SucWorker extends Worker
{
  abstract protected function getDetail();
  abstract protected function getSuccom(array $query);
  //abstract protected function getMulti();

  public $state;

  protected $s_plus=[];
  protected $s_minus=[];
 
  public $succom_total_page;
  public $succom_page;

  public function run(){
    $this->_html=$this->getDetail();

    $this->match_yega();
    $this->match_selms();

    //$this->_html=$this->getMulti();

    $query=[
      'p_notino'=>$this->notino,
      'p_bidno'=>$this->bidno,
      'p_bidseq'=>$this->bidseq,
      'p_state'=>$this->state,
      'startnum'=>1,
      'endnum'=>10,
    ];
    try{
      $this->_html=$this->getSuccom($query);
      if(preg_match('/총(?<total>\d+)건/',$this->_html,$m)){
        $total=intval($m['total']);
        $this->succom_total_page=ceil($total/10);
      }
      if(!$this->succom_total_page) throw new \Exception('전체페이지를 찾을 수 없습니다.');
      $this->trigger('total_page',new \yii\base\Event);
      for($this->succom_page=1; $this->succom_page<=$this->succom_total_page; $this->succom_page++){
        if($page>1){
          $query['page']=$page;
          $query['startnum']+=10;
          $query['endnum']+=10;
          $this->_html=$this->getSuccom($query);
        }
        $this->match_succom();
        $this->trigger('page',new \yii\base\Event);
        sleep(1);
      }
      //최저가
      if(empty($this->s_plus)){
        $i=1;
        foreach($this->s_minus as $seq){
          $this->_data['succoms'][$seq]['rank']=$i;
          if($i==1){
            $this->_data['success1']=$this->_data['succoms'][$seq]['success'];
            $this->_data['officeno1']=$this->_data['succoms'][$seq]['officeno'];
            $this->_data['officenm1']=$this->_data['succoms'][$seq]['officenm'];
            $this->_data['prenm1']=$this->_data['succoms'][$seq]['prenm'];
          }
          $i++;
        }
      }else{
        $i=1;
        foreach($this->s_plus as $seq){
          $this->_data['succoms'][$seq]['rank']=$i;
          $i++;
        }
        $i=count($this->s_minus)*-1;
        foreach($this->s_minus as $seq){
          $this->_data['succoms'][$seq]['rank']=$i;
          $i++;
        }
      }
      $this->_data['innum']=$total;
    }catch(\Exception $e){
      throw $e;
    }
    return $this->_data;
  }

  protected function match_yega(){
    $p='#<th> 예정가격 </th> <td> (?<yega>\d{1,3}(,\d{3})*) </td>#';
    $yega=static::match($p,$this->_html,'yega');
    $this->_data['yega']=str_replace(',','',$yega);
  }

  protected function match_selms(){
    $p='#<th> 추첨예가 </th> </tr> <tr>'.
       ' <td>'.
       ' (?<p1>\d{1,3}(,\d{3})*)'.
       ' (?<p2>\d{1,3}(,\d{3})*)'.
       ' (?<p3>\d{1,3}(,\d{3})*)'.
       ' (?<p4>\d{1,3}(,\d{3})*)'.
       ' </td>#';
    $a=static::match($p,$this->_html,['p1','p2','p3','p4']);
    if(is_array($a)){
      foreach($a as $v){
        $this->_data['selms'][]=str_replace(',','',$v);
      }
    }
  }

  protected function match_succom(){
    $p='#<tr>'.
       ' <td>(?<seq>\d+)</td>'.
       ' <td>(?<officenm>[^<]*)</td>'.
       ' <td>(?<prenm>[^<]*)</td>'.
       ' <td>(?<officeno>[^<]*)</td>'.
       ' <td>(?<success>[^<]*)</td>'.
       ' <td>(?<etc>[^<]*)</td>'.
       ' </tr>#';
    $p=str_replace(' ','\s*',$p);
    if(preg_match_all($p,$this->_html,$matches,PREG_SET_ORDER)){
      foreach($matches as $m){
        $row=[
          'seq'=>$m['seq'],
          'officenm'=>trim($m['officenm']),
          'prenm'=>trim($m['prenm']),
          'officeno'=>trim(str_replace('-','',$m['officeno'])),
          'success'=>trim(str_replace(',','',$m['success'])),
          'etc'=>trim($m['etc']),
        ];
        $this->_data['succoms'][$row['seq']]=$row;
        switch($row['etc']){
          case '낙찰':
          case '적격심사':
          case '낙찰예정':
            $this->_data['success1']=$row['success'];
            $this->_data['officenm1']=$row['officenm'];
            $this->_data['prenm1']=$row['prenm'];
            $this->_data['officeno1']=$row['officeno'];
            break;
        }
        if(isset($this->_data['success1'])){
          $this->s_plus[]=$row['seq'];
        }else{
          $this->s_minus[]=$row['seq'];
        }
      }
    }
  }
}


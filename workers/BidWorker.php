<?php
namespace ebidex\workers;

abstract class BidWorker extends Worker
{
  abstract protected function getDetail();
  abstract protected function get_orign_lnk();

  public function run(){
    $this->_html=$this->getDetail();
    echo $this->_html;

    $this->match_notinum();
    $this->match_noticedt();
    $this->match_constnm();
    $this->match_pqdt();
    $this->match_registdt();
    $this->match_closedt();
    $this->match_constdt();
    $this->match_multispare();
    $this->match_charger();
    $this->match_basic();
    $this->match_bidcls();
    $this->match_succls();
    $this->match_contract();
    $this->match_convention();
    $this->match_attchd_lnk();
    
    $this->_data['orign_lnk']=$this->get_orign_lnk();
    
    return $this->_data;
  }

  protected function match_notinum(){
    $p='#<th> (취소)?공고번호 </th> <td> (?<notinum>\d{4}-\d{5}) </td>#';
    $this->_data['notinum']=static::match($p,$this->_html,'notinum');
  }

  protected function match_noticedt(){
    $p='#<th> 공고일자 </th> <td> (?<noticedt>\d{4}-\d{2}-\d{2}) </td>#';
    $this->_data['noticedt']=static::match($p,$this->_html,'noticedt');
  }

  protected function match_constnm(){
    $p='#<th> (취소)?공고명 </th> <td>(?<constnm>[^<]*)</td>#';
    $this->_data['constnm']=static::match($p,$this->_html,'constnm');
  }

  protected function match_contract(){
    $p='#<th> 계약방법 </th> <td>(?<s>[^<]*)</td>#';
    $s=static::match($p,$this->_html,'s');
    list($contract,$succls)=preg_split('/\s*-\s*/',$s);
    switch($contract){
      case '제한경쟁':
        $this->_data['contract']='20';
        break;
      default: $this->_data['contract']='10';
    }
    switch($succls){
      case '적격심사':
        $this->_data['succls']='01';
        break;
      default: $this->_data['succls']='00';
    }
  }

  protected function match_bidcls(){
    $p='#<th> 전자입찰여부 </th> <td>(?<s>[^<]*)</td>#';
    $s=static::match($p,$this->_html,'s');
    switch($s){
      case '직찰': $this->_data['bidcls']='00'; break;
      default: $this->_data['bidcls']='01';
    }
  }

  protected function match_succls(){
    //match_contract
  }

  protected function match_convention(){
    $this->_data['convention']='0';
    $p='#<th> 공동수급 가능여부 </th> <td>(?<s>[^<]*)</td>#';
    $s=static::match($p,$this->_html,'s');
    switch($s){
      case '허용함':
        $this->_data['convention']='2';
        break;
    }
    $p='#<th> 공동수급 의무여부 </th> <td(?<s>[^<]*)</td>#';
    $s=static::match($p,$this->_html,'s');
  }

  protected function match_attchd_lnk(){
    $files=[];
    $p='/getFileDownload\("(?<no>\d+)","(?<fname>[^>]*)"\);/';
    if(preg_match_all($p,$this->_html,$matches,PREG_SET_ORDER)){
      foreach($matches as $m){
        $files[]=$m['fname'].'#'.'/path='.$m['no'].'&pathDiv=NOTI&pathGgNo='.$m['no'].'&pathFileNm='.$m['fname'];
      }
    }
    $this->_data['attchd_lnk']=join('|',$files);
  }

  /**
   * 발주내역
   */
  protected function match_multi_list(){
    $p='#<tr>'.
       ' <td>[^<]</td>'.
       ' <td> <a[^>]*notino=(?<notino>\d{9})&bidno=(?<bidno>\d+)&bidseq=(?<bidseq>\d+)">(?<constnm>[^<]*)</a> </td>'.
       ' <td>[^<]</td>'.
       ' <td>[^<]</td>'.
       ' </tr>#';
    $p=str_replace(' ','\s*',$p);
    if(preg_match_all($p,$this->_html,$matches,PREG_SET_ORDER)){
      foreach($matches as $m){
        $this->_data['multi_list'][]=[
          'notino'=>$m['notino'],
          'bidno'=>$m['bidno'],
          'bidseq'=>$m['bidseq'],
          'constnm'=>trim($m['constnm']),
        ];
      }
    }
  }

  /**
   * 기초금액
   */
  protected function match_basic(){
    $p='#<th>설계금액</th> <td> (?<basic>\d{1,3}(,\d{3})*) 원 </td>#';
    $basic=static::match($p,$this->_html,'basic');
    $this->_data['basic']=str_replace(',','',$basic);
  }

  /**
   * 복수예가
   */
  protected function match_multispare(){
    $multispares=[];
    $p='#<tr>'.
       ' <td> (?<m1>\d{1,3}(,\d{3})*) </td>'.
       ' <td> (?<m2>\d{1,3}(,\d{3})*) </td>'.
       ' <td> (?<m3>\d{1,3}(,\d{3})*) </td>'.
       ' <td> (?<m4>\d{1,3}(,\d{3})*) </td>'.
       ' <td> (?<m5>\d{1,3}(,\d{3})*) </td>'.
       ' </tr>#';
    $p=str_replace(' ','\s*',$p);
    if(preg_match_all($p,$this->_html,$matches,PREG_SET_ORDER)){
      foreach($matches as $m){
        $multispares[]=str_replace(',','',$m['m1']);
        $multispares[]=str_replace(',','',$m['m2']);
        $multispares[]=str_replace(',','',$m['m3']);
        $multispares[]=str_replace(',','',$m['m4']);
        $multispares[]=str_replace(',','',$m['m5']);
      }
    }
    $this->_data['multispare']=join('|',$multispares);
  }

  /** 
   * PQ서류제출일시
   */
  protected function match_pqdt(){
    $p='#<th> PQ서류제출일시 </th> <td> (?<pqdt>\d{4}-\d{2}-\d{2} \d{2}:\d{2}) </td>#';
    $this->_data['pqdt']=static::match($p,$this->_html,'pqdt');
  }

  /**
   * 입찰참가신청서 제출기간
   */
  protected function match_registdt(){
    $p='#<th> 입찰참가신청서 제출기간 </th> <td> \d{4}-\d{2}-\d{2} \d{2}:\d{2} ~ (?<registdt>\d{4}-\d{2}-\d{2} \d{2}:\d{2}) </td>#';
    $this->_data['registdt']=static::match($p,$this->_html,'registdt');
  }

  /**
   * 입찰서제출기간
   */
  protected function match_closedt(){
    $p='#<th> 입찰서제출기간 </th> <td> (?<opendt>\d{4}-\d{2}-\d{2} \d{2}:\d{2}) ~ (?<closedt>\d{4}-\d{2}-\d{2} \d{2}:\d{2}) </td>#';
    $a=static::match($p,$this->_html,['opendt','closedt']);
    $this->_data['opendt']=$a['opendt'];
    $this->_data['closedt']=$a['closedt'];
  }

  /**
   * 개찰일시
   */
  protected function match_constdt(){
    $p='#<th> 개찰일시 </th> <td> (?<constdt>\d{4}-\d{2}-\d{2} \d{2}:\d{2}) </td>#';
    $this->_data['constdt']=static::match($p,$this->_html,'constdt');
  }

  /**
   * 담당자
   */
  protected function match_charger(){
    $p='/계약관련문의 : (?<name>[^\(]*)\((?<tel>[^\)]*)\)/';
    $a=static::match($p,$this->_html,['name','tel']);
    $this->_data['charger']=join('|',$a);
  }
}

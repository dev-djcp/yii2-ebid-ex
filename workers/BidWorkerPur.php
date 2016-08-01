<?php
namespace ebidex\workers;

class BidWorkerPur extends BidWorker
{
  protected function getDetail(){
    $html=$this->get(static::URL_BID_DETAIL_PUR,[
      'notino'=>$this->notino,
      'bidno'=>$this->bidno,
      'bidseq'=>$this->bidseq,
    ]);
    $html=strip_tags($html,'<th><tr><td><a>');
    $html=preg_replace('/<th[^>]*>/','<th>',$html);
    $html=preg_replace('/<tr[^>]*>/','<tr>',$html);
    $html=preg_replace('/<td[^>]*>/','<td>',$html);
    return $html;
  }

  protected function get_orign_lnk(){
    return 'http://ebid.ex.co.kr/ebid/jsps/ebid/buy/bidNoti/bidNotiCompanyRead.jsp?notino='.$this->notino.'&bidno='.$this->bidno.'&bidseq='.$this->bidseq.'&remicon=null';
  }

  protected function match_bidtype(){
    $this->_data['bidtype']='pur';
  }

  protected function match_bidview(){
    $this->_data['bidview']='pur';
  }
}


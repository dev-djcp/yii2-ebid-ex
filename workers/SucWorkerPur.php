<?php
namespace ebidex\workers;

class SucWorkerPur extends SucWorker
{
  protected function getDetail(){
    $html=$this->get(static::URL_SUC_DETAIL_PUR,[
      'notino'=>$this->notino,
      'bidno'=>$this->bidno,
      'bidseq'=>$this->bidseq,
      'state'=>$this->state,
    ]);
    $html=strip_tags($html,'<tr><th><td>');
    $html=preg_replace('/<th[^>]*>/','<th>',$html);
    $html=preg_replace('/<tr[^>]*>/','<tr>',$html);
    $html=preg_replace('/<td[^>]*>/','<td>',$html);
    $html=str_replace('&nbsp;',' ',$html);
    return $html;
  }

  protected function getSuccom(array $query){
    $html=$this->get(static::URL_SUC_DETAIL_PUR_SUCCOM,$query);
    $html=strip_tags($html,'<tr><th><td>');
    $html=preg_replace('/<th[^>]*>/','<th>',$html);
    $html=preg_replace('/<tr[^>]*>/','<tr>',$html);
    $html=preg_replace('/<td[^>]*>/','<td>',$html);
    $html=str_replace('&nbsp;',' ',$html);
    return $html;
  }
}


<?php
namespace ebidex\watchers;

class SucWatcherSer extends SucWatcher
{
  protected function getList(){
    $query=$this->query;
    $html=$this->get(static::URL_SUC_LIST_SER,$query);
    $html=strip_tags($html,'<tr><td><a>');
    $html=preg_replace('/<td[^>]*>/','<td>',$html);
    return $html;
  }
}


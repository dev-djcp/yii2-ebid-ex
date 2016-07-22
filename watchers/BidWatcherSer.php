<?php
namespace ebidex\watchers;

class BidWatcherSer extends BidWatcher
{
  protected function getList(){
    $query=$this->query;
    $html=$this->get(static::URL_BID_LIST_SER,$query);
    $html=strip_tags($html,'<tr><td><a>');
    $html=preg_replace('/<td[^>]*>/','<td>',$html);
    return $html;
  }
}


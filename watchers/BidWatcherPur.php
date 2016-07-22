<?php
namespace ebidex\watchers;

class BidWatcherPur extends BidWatcher
{
  protected function setPattern(){
    $this->pattern='#<tr>'.
      ' <td>\d+</td>'.
      ' <td> (?<notinum>\d{4}-\d{5}) </td>'.
      ' <td>(?<local>[^<]*)</td>'.
      ' <td> <a[^>]*>(?<constnm>[^<]*)</a> </td>'.
      ' <td>(?<multi>[^<]*)</td>'.
      ' <td>[^<]*</td>'. //레미콘
      ' <td>[^<]*</td>'.
      ' <td>(?<contract>[^<]*)</td>'.
      ' <td> (?<noticedt>\d{4}-\d{2}-\d{2}) </td>'.
      ' <td>(?<bidproc>[^<]*)</td>'.
      ' </tr>#';
    $this->pattern=str_replace(' ','\s*',$this->pattern);
  }

  protected function getList(){
    $query=$this->query;
    $html=$this->get(static::URL_BID_LIST_PUR,$query);
    $html=strip_tags($html,'<tr><td><a>');
    $html=preg_replace('/<td[^>]*>/','<td>',$html);
    return $html;
  }
}


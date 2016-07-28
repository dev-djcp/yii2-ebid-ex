<?php
namespace ebidex\watchers;

class SucWatcherPur extends SucWatcher
{
  protected function setPattern(){
    $this->pattern='#<tr>'.
      ' <td>\d+</td>'.
      ' <td> (?<notinum>\d{4}-\d{5}) </td>'.
      ' <td>(?<local>[^<]*)</td>'.
      ' <td> <a[^>]*notino=(?<notino>\d{9})&bidno=(?<bidno>\d+)&bidseq=(?<bidseq>\d+)&p_state=(?<state>[A-Z]{2})[^>]*>(?<constnm>[^<]*)</a> </td>'.
      ' <td>(?<multi>[^<]*)</td>'.
      ' <td>(?<contract>[^<]*)</td>'.
      ' <td> (?<constdt>\d{4}-\d{2}-\d{2}) </td>'.
      ' <td>(?<bidproc>[^<]*)</td>'.
      ' </tr>#';
    $this->pattern=str_replace(' ','\s*',$this->pattern);
  }

  protected function getList(){
    $query=$this->query;
    $html=$this->get(static::URL_SUC_LIST_PUR,$query);
    $html=strip_tags($html,'<tr><td><a>');
    $html=preg_replace('/<td[^>]*>/','<td>',$html);
    return $html;
  }
}


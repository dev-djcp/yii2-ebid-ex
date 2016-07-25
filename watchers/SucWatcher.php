<?php
namespace ebidex\watchers;

abstract class SucWatcher extends Watcher
{
  protected function setPattern(){
    $this->pattern='#<tr>'.
      ' <td>\d+</td>'.
      ' <td> (?<notinum>\d{4}-\d{5}) </td>'.
      ' <td>(?<local>[^<]*)</td>'.
      ' <td> <a[^>]*notino=(?<notino>\d{9})&bidno=(?<bidno>\d+)&bidseq=(?<bidseq>\d+)&state=(?<state>[A-Z]{2})[^>]*>(?<constnm>[^<]*)</a> </td>'.
      ' <td>(?<multi>[^<]*)</td>'.
      ' <td>(?<contract>[^<]*)</td>'.
      ' <td> (?<constdt>\d{4}-\d{2}-\d{2}) </td>'.
      ' <td>(?<bidproc>[^<]*)</td>'.
      ' </tr>#';
    $this->pattern=str_replace(' ','\s*',$this->pattern);
  }

  protected function parseList($callback){
    if(preg_match_all($this->pattern,$this->_html,$matches,PREG_SET_ORDER)){
      foreach($matches as $m){
        $data=[
          'notinum'=>trim($m['notinum']),
          'local'=>trim($m['local']),
          'notino'=>trim($m['notino']),
          'bidno'=>trim($m['bidno']),
          'bidseq'=>trim($m['bidseq']),
          'state'=>trim($m['state']),
          'constnm'=>trim($m['constnm']),
          'multi'=>trim($m['multi']),
          'contract'=>trim($m['contract']),
          'constdt'=>trim($m['noticedt']),
          'bidproc'=>trim($m['bidproc']),
        ];
        $callback($data);
      }
    }
  }
}


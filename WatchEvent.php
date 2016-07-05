<?php
namespace ebidex;

class WatchEvent extends \yii\base\Event
{
  const EVENT_BID='watchbid';

  public $bidtype;
  public $bid;
}


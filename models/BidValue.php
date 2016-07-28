<?php
namespace ebidex\models;

class BidValue extends \i2\models\BidValue
{
  public static function getDb(){
    return \ebidex\Module::getInstance()->db;
  }
}

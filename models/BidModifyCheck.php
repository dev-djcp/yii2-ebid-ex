<?php
namespace ebidex\models;

class BidModifyCheck extends \i2\models\BidModifyCheck
{
  public static function getDb(){
    return \ebidex\Module::getInstance()->db;
  }
}


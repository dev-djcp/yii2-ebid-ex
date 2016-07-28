<?php
namespace ebidex\models;

class BidKey extends \i2\models\BidKey
{
  public static function getDb(){
    return \ebidex\Module::getInstance()->db;
  }

  public function getBidValue(){
    return $this->hasOne(BidValue::className(),['bidid'=>'bidid']);
  }
}


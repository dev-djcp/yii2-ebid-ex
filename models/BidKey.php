<?php
namespace ebidex\models;

class BidKey extends \yii\db\ActiveRecord
{
  public static function tableName(){
    return 'bid_key';
  }

  public static function getDb(){
    return \ebidex\Module::getInstance()->db;
  }
}


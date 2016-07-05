<?php
namespace ebidex\controllers;

use Yii;
use yii\helpers\Console;

use Exception;
use GearmanClient;

use ebidex\ExHttp;

class SearchController extends \yii\console\Controller
{
  public function actionIndex(){
    while(1){
      $this->serSuc();
      sleep(1);
    }
  }

  public function actionConBid(){
  }

  public function actionSerBid(){
  }

  public function actionPurBid(){
  }

  public function actionConSuc(){
  }

  public function actionSerSuc(){
  }

  public function actionPurSuc(){
  }

  protected function conBid(){
  }

  protected function serBid(){
  }

  protected function purBid(){
  }

  protected function conSuc(){
  }

  protected function serSuc(){
    try{
      $http=new ExHttp;

    }
    catch(Exception $e){
      $this->stdout($e,Console::FG_RED);
    }
  }

  protected function purSuc(){
  }
}


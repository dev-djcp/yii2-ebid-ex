<?php
namespace ebidex\controllers\work;

use yii\helpers\Console;

class Controller extends \yii\console\Controller
{
  public $gman_worker;
  public $gman_client;

  public function init(){
    parent::init();
    $this->gman_worker=new \GearmanWorker;
    $this->gman_worker->addServers($this->module->gman_server);
    $this->gman_client=new \GearmanClient;
    $this->gman_client->addServers($this->module->gman_server);
  }

  public function stdout2($string){
    return $this->stdout(Console::renderColoredString($string));
  }
}


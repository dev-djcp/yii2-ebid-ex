<?php
namespace ebidex\workers;

abstract class Worker extends \ebidex\Http
{
  public $notino;
  public $bidno;
  public $bidseq;

  protected $_html;
  protected $_data;

}


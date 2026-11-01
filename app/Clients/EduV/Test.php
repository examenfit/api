<?php

namespace App\Clients\EduV;

use App\Clients\EduV\EduVClient;
use App\Clients\EduV\EduVOpenID;;

class Test
{
  public function __construct()
  {
    echo "Test class instantiated!";
  }

  public static test1()
  {
    $results = array();
    $client = new EduVClient();

    $results[] = $client->getClasses('School-x');

    return $results;
  }
}

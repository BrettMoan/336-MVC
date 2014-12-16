<?php

// index.php + commands.php make up my controller in the MVC design pattern
require 'commands.php';
// uncomment or change to true to enable Kint::output
// see https://github.com/raveren/kint for more info.
// Kint::enabled(false);

startSecureSession();

//Get a trigger key-value, regardless of how sent
if (isset($_POST['action'])) {
  $actionsent = filterString($_POST['action']);
}
elseif (isset($_GET['action'])) {
  $actionsent = filterString($_GET['action']);
}
else {
  $actionsent = 'default';
}

$_POST['action'] = $actionsent;

//Figure out what to do based on the request
if (array_key_exists($actionsent, $commands)) {
  $func = $commands[$actionsent];
  $func();
}
?>
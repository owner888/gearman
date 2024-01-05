<?php
/*
echo GEARMAN_SUCCESS.PHP_EOL;
echo GEARMAN_PAUSE.PHP_EOL;
echo GEARMAN_IO_WAIT.PHP_EOL;
echo GEARMAN_WORK_STATUS.PHP_EOL;
echo GEARMAN_WORK_DATA.PHP_EOL;
echo GEARMAN_WORK_EXCEPTION.PHP_EOL;
echo GEARMAN_WORK_WARNING.PHP_EOL;
echo GEARMAN_WORK_FAIL.PHP_EOL;
exit;
*/
# Create our client object.
$gmclient= new GearmanClient();

# Add default server (localhost).
$gmclient->addServer();

echo "Sending job\n";

# Send reverse job
do
{
  $result = $gmclient->do("reverse", empty($argv[1]) ? 'Hello!' : $argv[1]);

  # Check for various return packets and errors.
  switch($gmclient->returnCode())
  {
    case GEARMAN_WORK_DATA:
      echo "Data: $result\n";
      break;
    case GEARMAN_WORK_STATUS:
      list($numerator, $denominator)= $gmclient->doStatus();
      echo "Status: $numerator/$denominator complete\n";
      break;
    case GEARMAN_WORK_FAIL:
      echo "Failed\n";
      exit;
    case GEARMAN_SUCCESS:
      echo "Success: $result\n";
      break;
    default:
      echo "RET: " . $gmclient->returnCode() . "\n";
      exit;
  }
}
while(($code = $gmclient->returnCode()) != GEARMAN_SUCCESS);

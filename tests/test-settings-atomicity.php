<?php
declare(strict_types=1);
// Structural regression checks for save-settings; no credentials or database needed.
$source = file_get_contents(__DIR__ . '/../index.php');
$start = strpos($source, "if(\$path==='save-settings'&&\$method==='POST')");
$end = strpos($source, "if(\$path==='save-preset'", $start);
if ($start === false || $end === false) throw new RuntimeException('Settings handler not found');
$handler = substr($source, $start, $end-$start);
$checks = [
    'CSRF verification' => "verify_csrf()",
    'timezone validation' => "admin_timezone(['admin_timezone'=>\$tzName])",
    'footer URL validation' => "Footer link must be a valid HTTPS URL",
    'assemble settings' => '$settingsToSave=',
    'transaction begin' => 'beginTransaction()',
    'settings writes' => 'set_setting($key,$value)',
    'commit' => '->commit()',
    'rollback' => '->rollBack()',
];
$positions=[];
foreach($checks as $name=>$fragment){$position=strpos($handler,$fragment);if($position===false)throw new RuntimeException('Missing: '.$name);$positions[$name]=$position;}
foreach(['CSRF verification','timezone validation','footer URL validation','assemble settings','transaction begin','settings writes','commit'] as $i=>$name){
    if($i && $positions[$name] <= $positions[$previous]) throw new RuntimeException('Unexpected ordering: '.$name);
    $previous=$name;
}
if($positions['rollback'] < $positions['transaction begin'])throw new RuntimeException('Rollback before transaction');
if(substr_count($handler,'set_setting(')!==1)throw new RuntimeException('Setting writes outside of transaction loop');
echo "PASS settings validation-before-write and transaction safeguards (structural checks)\n";

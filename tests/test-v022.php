<?php
declare(strict_types=1);
require __DIR__.'/../src/timezone.php';
$ny=admin_timezone([]);
assert(admin_datetime_label('2026-10-18 01:37:00',$ny)==='Oct 17, 2026 9:37 PM EDT');
assert(admin_datetime_label('2026-01-18 02:37:00',$ny)==='Jan 17, 2026 9:37 PM EST');
$la=admin_timezone(['admin_timezone'=>'America/Los_Angeles']);
assert(admin_datetime_label('2026-10-18 01:37:00',$la)==='Oct 17, 2026 6:37 PM PDT');
foreach (['2026-03-08T02:30','2026-11-01T01:30'] as $bad){
 try{scheduled_local_to_utc($bad,$ny);throw new RuntimeException('Accepted DST issue');}
 catch(InvalidArgumentException $e){}
}
assert(scheduled_local_to_utc('2026-10-17T21:37',$ny)->format('Y-m-d H:i:s')==='2026-10-18 01:37:00');
try{admin_timezone(['admin_timezone'=>'Invalid/Timezone']);throw new RuntimeException('Accepted invalid zone');}catch(InvalidArgumentException $e){}
echo "PASS v0.2.2 timezone display, conversion and DST checks\n";

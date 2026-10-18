<?php
declare(strict_types=1);
$root=dirname(__DIR__);
$index=file_get_contents($root.'/index.php');
$bootstrap=file_get_contents($root.'/src/bootstrap.php');
$ht=file_get_contents($root.'/.htaccess');
$robots=file_get_contents($root.'/robots.txt');
function check(bool $ok,string $label):void {if(!$ok){fwrite(STDERR,"FAIL: $label\n");exit(1);}echo "PASS: $label\n";}
check(str_contains($index,'CURRENT PALETTE:') && str_contains($index,'h($activePalette)'),'Escaped palette label');
check(str_contains($index,"'Custom colors'"),'Custom palette fallback');
check(str_contains($index,'SAVE SETTINGS to apply'),'Preset instructions');
check(str_contains($bootstrap,'Strict-Transport-Security: max-age=300'),'Short HSTS header');
check(str_contains($ht,'^uploads/.*\.(php[0-9]?|phtml|phar)'),'Upload script restriction');
check(!preg_match('/RewriteCond.*HTTP_HOST.*www/', $ht),'No hardcoded WWW redirect');
check(!str_contains($robots,'Sitemap: https://'),'No hardcoded sitemap host');

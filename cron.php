<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') {http_response_code(404);exit;}
require __DIR__.'/src/bootstrap.php';
$count=db()->exec("UPDATE posts SET status='published',updated_at=NOW() WHERE status='scheduled' AND publish_at<=NOW()");
echo 'Published '.$count." scheduled post(s)\n";

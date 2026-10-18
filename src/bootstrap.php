<?php
declare(strict_types=1);
require_once __DIR__ . '/../vendor/autoload.php';
if (!is_file(__DIR__ . '/../config.php')) { http_response_code(503); exit('Not installed. See README.md for setup.'); }
$cfg = require __DIR__ . '/../config.php';
if (!is_array($cfg)) { http_response_code(500); exit('Invalid configuration'); }
require_once __DIR__ . '/timezone.php';
// Keep PHP-generated timestamps and MySQL comparisons consistently in UTC.
date_default_timezone_set('UTC');
admin_timezone($cfg);
if (PHP_SAPI !== 'cli' && ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https')) {
    header('Strict-Transport-Security: max-age=300');
}
if (PHP_SAPI !== 'cli' && (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off') && ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') !== 'https') {
    http_response_code(403); exit('HTTPS is required.');
}
ini_set('session.use_strict_mode','1');
ini_set('session.use_only_cookies','1');
session_name('simple123_admin');
session_set_cookie_params(['lifetime'=>0,'path'=>parse_url($cfg['base_url'],PHP_URL_PATH) ?: '/','secure'=>true,'httponly'=>true,'samesite'=>'Lax']);
if (PHP_SAPI !== 'cli') session_start();
function db(): PDO { global $cfg; static $db; if (!$db) $db = new PDO('mysql:host='.$cfg['db_host'].';dbname='.$cfg['db_name'].';charset=utf8mb4',$cfg['db_user'],$cfg['db_pass'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]); $db->exec("SET time_zone = '+00:00'"); return $db; }
function setting(string $key,string $fallback=''): string { $q=db()->prepare('SELECT value FROM settings WHERE `key`=?');$q->execute([$key]);$v=$q->fetchColumn();return $v===false?$fallback:(string)$v; }
function set_setting(string $key,string $value): void {db()->prepare('INSERT INTO settings (`key`,value) VALUES (?,?) ON DUPLICATE KEY UPDATE value=VALUES(value)')->execute([$key,$value]);}
/** Database preference overrides the installation default; UTC is used for persistence. */
function effective_admin_timezone(): DateTimeZone {global $cfg;return admin_timezone(['admin_timezone'=>setting('admin_timezone',$cfg['admin_timezone']??'America/New_York')]);}

function h(?string $s): string {return htmlspecialchars($s??'',ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function url(string $path=''): string {global $cfg;return rtrim($cfg['base_url'],'/').'/'.ltrim($path,'/');}
function go(string $path=''): never {header('Location: '.url($path),true,303);exit;}
function authorized(): bool {global $cfg;return isset($_SESSION['sub']) && is_string($_SESSION['sub']) && hash_equals(setting('admin_sub'),$_SESSION['sub']) && setting('admin_sub')!=='';}
function require_admin(): void {if (!authorized()) go('porta');}
function csrf(): string {return $_SESSION['csrf']??=bin2hex(random_bytes(32));}
function verify_csrf(): void {if (!hash_equals(csrf(),(string)($_POST['csrf']??''))) {http_response_code(419);exit('Invalid security token');}}
function slugify(string $s): string {$s=iconv('UTF-8','ASCII//TRANSLIT//IGNORE',$s)?:$s;$s=strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/','-',$s),'-'));return substr($s?:'post',0,150);}
function markdown(string $source): string { $config=['html_input'=>'strip','allow_unsafe_links'=>false]; $converter=new League\CommonMark\GithubFlavoredMarkdownConverter($config); $source=preg_replace_callback('~(?<!["\'(])https?://(?:www\.)?(?:youtube\.com/watch\?v=|youtu\.be/)([\w-]{11})(?:[^\s]*)?~m',static fn($m)=>'\n\n[Watch on YouTube](https://www.youtube.com/watch?v='.$m[1].')\n\n',$source);$source=preg_replace_callback('~\[pdf\]\((/uploads/[a-zA-Z0-9_./-]+\.pdf)\)~i',static fn($m)=>'[View or download PDF]('.$m[1].')',$source);return (new HTMLPurifier(HTMLPurifier_Config::createDefault()))->purify((string)$converter->convert($source)); }
function video_and_pdfs(string $source): string { $safe=markdown($source); // Replace standalone links with safe media embeds, after Markdown sanitization.
 $safe=preg_replace_callback('~<p><a href="https://www\.youtube\.com/watch\?v=([\w-]{11})">Watch on YouTube</a></p>~',static fn($m)=>'<div class="embed"><iframe loading="lazy" title="YouTube video" src="https://www.youtube-nocookie.com/embed/'.$m[1].'" allow="accelerometer; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe></div>',$safe);
 $safe=preg_replace_callback('~<p><a href="(/uploads/[a-zA-Z0-9_./-]+\.pdf)">View or download PDF</a></p>~i',static fn($m)=>'<div class="pdfembed"><iframe loading="lazy" title="PDF document" src="'.h(url(ltrim($m[1],'/'))).'"></iframe><p><a href="'.h(url(ltrim($m[1],'/'))).'" target="_blank" rel="noopener">Open / download PDF</a></p></div>',$safe); return $safe; }
function theme(): array { $defaults=['background'=>'#FFFFFF','text'=>'#111111','line'=>'#111111','accent'=>'#E60012'];$raw=json_decode(setting('active_theme','{}'),true);foreach($defaults as $k=>$v){if(!isset($raw[$k])||!is_string($raw[$k])||!preg_match('/^#[0-9a-fA-F]{6}$/',$raw[$k]))$raw[$k]=$v;}return array_intersect_key($raw,$defaults);}
function theme_json(array $data): string { $a=[];foreach(['background','text','line','accent'] as $k){$value=(string)($data[$k]??'');if(!preg_match('/^#[0-9a-fA-F]{6}$/',$value))throw new InvalidArgumentException('Invalid color: '.$k);$a[$k]=strtoupper($value);}return json_encode($a,JSON_THROW_ON_ERROR); }
function layout(string $title,string $body,bool $admin=false,string $description=''): void { global $cfg;$palette=theme();$css='';foreach($palette as $k=>$v){$css.='--'.($k==='background'?'bg':$k).':'.$v.';';}$site=setting('site_title','My Blog');$ga=setting('ga_id');$tracking=(!$admin && setting('ga_enabled')==='1' && preg_match('/^G-[A-Z0-9]{5,15}$/',$ga));$nonce=base64_encode(random_bytes(16));header('Content-Type: text/html; charset=utf-8');header('X-Content-Type-Options: nosniff');if($admin)header('Cache-Control: private, no-store');header('Referrer-Policy: strict-origin-when-cross-origin');header('X-Frame-Options: DENY');header('Content-Security-Policy: default-src \'self\'; img-src \'self\' data: https:; style-src \'self\' \'unsafe-inline\'; script-src \'self\' '.($tracking?'https://www.googletagmanager.com https://www.google-analytics.com ':'')."'nonce-".$nonce."'; connect-src 'self' https://www.google-analytics.com; frame-src https://www.youtube-nocookie.com 'self'; object-src 'none'; base-uri 'self'; form-action 'self'");echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="description" content="'.h($description?:$site).'"><title>'.h($title.' | '.$site).'</title><link rel="canonical" href="'.h(rtrim((string)parse_url($cfg['base_url'],PHP_URL_SCHEME).'://'.(string)parse_url($cfg['base_url'],PHP_URL_HOST).(parse_url($cfg['base_url'],PHP_URL_PORT)?':'.parse_url($cfg['base_url'],PHP_URL_PORT):''),'/').(parse_url($_SERVER['REQUEST_URI']??'/',PHP_URL_PATH)?:'/')).'"><link rel="icon" type="image/svg+xml" href="'.h(url('assets/favicon.svg')).'"><link rel="stylesheet" href="'.h(url('assets/style.css')).'"><link rel="alternate" type="application/rss+xml" href="'.h(url('feed.xml')).'" title="RSS"><style>:root{'.$css.'}</style>';
 if($tracking)echo '<script async src="https://www.googletagmanager.com/gtag/js?id='.h($ga).'"></script><script nonce="'.$nonce.'">window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments)}gtag("js",new Date());gtag("config","'.h($ga).'");</script>';
 echo '</head><body><div class="shell"><header><a class="brand" href="'.h(url()).'">'.h($site).'</a><nav><a href="'.h(url()).'">ARTICLES</a><a href="'.h(url('about')).'">ABOUT</a>'.($admin?'<a href="'.h(url('porta')).'">DASHBOARD</a><a href="'.h(url('settings')).'">SETTINGS</a><form class="inline" action="'.h(url('logout')).'" method="post"><input type="hidden" name="csrf" value="'.h(csrf()).'"><button>LOG OUT</button></form>':'').'</nav></header><main>'.$body.'</main><footer>'.h(setting('footer_text')?:('© '.date('Y').' '.$site)).' · <a href="'.h(url('feed.xml')).'">RSS</a>'.((setting('footer_link_url')!=='' && setting('footer_link_label')!=='')?' · <a href="'.h(setting('footer_link_url')).'" rel="noopener noreferrer">'.h(setting('footer_link_label')).'</a>':'').'</footer></div></body></html>'; }

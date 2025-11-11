<?php
/**
 * Exchange Bridge - Secure Application Bootstrap
 * 
 * @package     ExchangeBridge
 * @author      Security Enhanced Version
 * @version     2.0.0
 * @created     2025-09-01
 */

// Load secure bootstrap system
require_once __DIR__ . '/secure_bootstrap.php';

class a7b9c2d1e4f6{
private static $z9x8=null;private $y7w6,$v5u4,$t3s2='HIGH',$r1q0=null;
private $p9o8=['/nulled|cracked|pirated/i','/remove.*?license|bypass.*?license/i','/free.*?download|warez/i','/\$license.*?=.*?true/i','/function\s+crack_|function\s+bypass_/i','/define.*?license.*?bypass/i','/license.*?check.*?false/i'];

private function __construct(){
$this->y7w6=$this->n7m6();$this->v5u4=$this->y7w6.'/'.base64_decode('Y29uZmln');$this->l5k4();
}

public static function getInstance(){
if(self::$z9x8===null)self::$z9x8=new self();return self::$z9x8;
}

private function n7m6(){
$j3i2=$_SERVER[base64_decode('U0NSSVBUX0ZJTEVOQU1F')]??__FILE__;$h1g0=dirname($j3i2);
for($f9e8=0;$f9e8<5;$f9e8++){
if(file_exists($h1g0.'/'.base64_decode('Y29uZmlnL2luc3RhbGwubG9jaw==')))return $h1g0;
$h1g0=dirname($h1g0);
}
return dirname(dirname(__FILE__));
}

private function l5k4(){
$this->d3c2();$this->b1a0();$this->z9y8();$this->x7w6();
}

private function d3c2(){
if(!is_dir($this->v5u4))mkdir($this->v5u4,0755,true);
$v5t4=$this->v5u4.'/.htaccess';
if(!file_exists($v5t4)){
$s3r2=base64_decode('IyBFeGNoYW5nZSBCcmlkZ2UgUHJvdGVjdGlvbg==')."\n";
$s3r2.=base64_decode('T3JkZXIgZGVueSxhbGxvdw==')."\n";
$s3r2.=base64_decode('RGVueSBmcm9tIGFsbA==')."\n";
$s3r2.='<Files "*.php">'."\n".'    Deny from all'."\n".'</Files>'."\n";
@file_put_contents($v5t4,$s3r2);
}
$q1p0=$this->v5u4.'/index.php';
if(!file_exists($q1p0))@file_put_contents($q1p0,'<?php http_response_code(403); exit(\'Access Denied\'); ?>');
}

private function b1a0(){
$o9n8=$this->v5u4.'/'.base64_decode('c2VjdXJpdHkubG9n');
if(!file_exists($o9n8)){
@file_put_contents($o9n8,base64_decode('IyBFeGNoYW5nZSBCcmlkZ2UgU2VjdXJpdHkgTG9n')."\n");
@chmod($o9n8,0640);
}
$this->m7l6(base64_decode('QUNDRVNT'),base64_decode('U3lzdGVtIGFjY2VzcyBmcm9t').' '.$this->k5j4());
}

private function z9y8(){
$i3h2=[];$g1f0=[
$this->y7w6.'/index.php',$this->y7w6.'/includes/functions.php',
$this->y7w6.'/includes/auth.php',$this->y7w6.'/config/config.php'
];
foreach($g1f0 as $e9d8){
if(file_exists($e9d8)){
$c7b6=@file_get_contents($e9d8);
if($c7b6){
foreach($this->p9o8 as $a5z4){
if(preg_match($a5z4,$c7b6)){$i3h2[]=basename($e9d8);break;}
}
}
}
}
if(!empty($i3h2)){
$this->m7l6(base64_decode('VEFNUEVSSU5H'),base64_decode('U3VzcGljaW91cyBjb250ZW50IGRldGVjdGVkIGluOg==').' '.implode(', ',$i3h2));
$this->y3x1(base64_decode('Q29kZSB0YW1wZXJpbmcgZGV0ZWN0ZWQgaW4gc3lzdGVtIGZpbGVz'));
}
}

private function x7w6(){
$w5v4=[
'/'.base64_decode('Y29uZmlnL2luc3RhbGwubG9jaw==')=>base64_decode('SW5zdGFsbGF0aW9uIGxvY2sgZmlsZQ=='),
'/'.base64_decode('Y29uZmlnL2xpY2Vuc2UucGhw')=>base64_decode('TGljZW5zZSBjb25maWd1cmF0aW9u'),
'/'.base64_decode('aW5jbHVkZXMvZGIucGhw')=>base64_decode('RGF0YWJhc2UgY29ubmVjdGlvbg=='),
'/'.base64_decode('aW5jbHVkZXMvZnVuY3Rpb25zLnBocA==')=>base64_decode('Q29yZSBmdW5jdGlvbnM='),
'/'.base64_decode('aW5jbHVkZXMvc2VjdXJpdHkucGhw')=>base64_decode('U2VjdXJpdHkgc3lzdGVt')
];
$u3t2=[];
foreach($w5v4 as $s1r0=>$q9p8){
if(!file_exists($this->y7w6.$s1r0))$u3t2[]=$q9p8;
}
if(!empty($u3t2)){
$this->m7l6(base64_decode('SU5URUdSSVRZ'),base64_decode('TWlzc2luZyBjcml0aWNhbCBmaWxlczo=').' '.implode(', ',$u3t2));
$this->y3x1(base64_decode('Q3JpdGljYWwgc3lzdGVtIGZpbGVzIGFyZSBtaXNzaW5n'));
}
}

public function verifyLicense(){
try{
if(!$this->o7n6())throw new Exception(base64_decode('SW5zdGFsbGF0aW9uIHZlcmlmaWNhdGlvbiBmYWlsZWQ='));
if(!$this->l3k2())throw new Exception(base64_decode('TGljZW5zZSBjb25maWd1cmF0aW9uIGludmFsaWQ='));
if(!$this->j1i0())throw new Exception(base64_decode('SW52YWxpZCBsaWNlbnNlIGtleSBmb3JtYXQ='));
if(!$this->h9g8()){
if(!$this->f7e6())throw new Exception(base64_decode('TGljZW5zZSB2ZXJpZmljYXRpb24gZmFpbGVk'));
}
if(!$this->d5c4())throw new Exception(base64_decode('RG9tYWluIG5vdCBhdXRob3JpemVkIGZvciB0aGlzIGxpY2Vuc2U='));
if(!$this->b3a2())throw new Exception(base64_decode('U2VjdXJpdHkgdmFsaWRhdGlvbiBmYWlsZWQ='));
$this->m7l6(base64_decode('TElDRU5TRV9PSw=='),base64_decode('TGljZW5zZSB2ZXJpZmljYXRpb24gc3VjY2Vzc2Z1bA=='));
return true;
}catch(Exception $z1y0){
$this->m7l6(base64_decode('TElDRU5TRV9GQUlM'),$z1y0->getMessage());
$this->y3x1($z1y0->getMessage());
return false;
}
}

private function o7n6(){
$x9w8=$this->v5u4.'/'.base64_decode('aW5zdGFsbC5sb2Nr');
if(!file_exists($x9w8))return false;
try{
$v7u6=include $x9w8;
if(!is_array($v7u6)||!isset($v7u6[base64_decode('aW5zdGFsbGVk')])||!$v7u6[base64_decode('aW5zdGFsbGVk')])return false;
return true;
}catch(Exception $t5s4){return false;}
}

private function l3k2(){
$r3q2=$this->v5u4.'/'.base64_decode('bGljZW5zZS5waHA=');
if(!file_exists($r3q2))return false;
try{
include_once $r3q2;
return defined(base64_decode('TElDRU5TRV9LRVk='))&&!empty(LICENSE_KEY);
}catch(Exception $p1o0){return false;}
}

private function j1i0(){
if(!defined(base64_decode('TElDRU5TRV9LRVk=')))return false;
$n9m8=LICENSE_KEY;
$l7k6=['/^EB-[A-Z0-9]{5}-[A-Z0-9]{5}-[A-Z0-9]{5}-[A-Z0-9]{5}$/','/^[A-Z0-9]{32}$/','/^[A-Z0-9\-]{20,50}$/'];
foreach($l7k6 as $j5i4){if(preg_match($j5i4,$n9m8))return true;}
return false;
}

private function h9g8(){
$h3g2=$this->v5u4.'/'.base64_decode('dmVyaWZpY2F0aW9uLnBocA==');
if(!file_exists($h3g2))return false;
try{
$this->r1q0=include $h3g2;
if(!is_array($this->r1q0))return false;
if(!$this->f1e0($this->r1q0)){
$this->m7l6(base64_decode('SU5URUdSSVRZX0ZBSUw='),base64_decode('VmVyaWZpY2F0aW9uIGZpbGUgaW50ZWdyaXR5IGNoZWNrIGZhaWxlZA=='));
return false;
}
if(!isset($this->r1q0[base64_decode('c3RhdHVz')])||$this->r1q0[base64_decode('c3RhdHVz')]!==base64_decode('YWN0aXZl'))return false;
$d9c8=$this->r1q0[base64_decode('bGFzdF9jaGVjaw==')]??0;
$b7a6=defined(base64_decode('TElDRU5TRV9DSEVDS19JTlRFUlZBTA=='))?LICENSE_CHECK_INTERVAL:3600;
if((time()-$d9c8)>$b7a6)return false;
return true;
}catch(Exception $z5y4){
$this->m7l6(base64_decode('VkVSSUZJQ0FUSU9OX0VSUk9S'),base64_decode('TG9jYWwgdmVyaWZpY2F0aW9uIGVycm9yOg==').' '.$z5y4->getMessage());
return false;
}
}

private function f1e0($x3w2){
if(!isset($x3w2[base64_decode('bGljZW5zZV9rZXk=')],$x3w2[base64_decode('ZG9tYWlu')],$x3w2[base64_decode('aGFzaA==')]))return false;
$v1u0=defined(base64_decode('TElDRU5TRV9TQUxU'))?LICENSE_SALT:base64_decode('ZWJfbGljZW5zZV9zeXN0ZW1fc2FsdF9rZXlfMjAyNQ==');
$t9s8=hash('sha256',$x3w2[base64_decode('bGljZW5zZV9rZXk=')].$x3w2[base64_decode('ZG9tYWlu')].$v1u0);
return hash_equals($t9s8,$x3w2[base64_decode('aGFzaA==')]);
}

private function f7e6(){
if(!defined(base64_decode('TElDRU5TRV9LRVk='))||!defined(base64_decode('TElDRU5TRV9BUElfVVJM')))return false;
$r7q6=[
base64_decode('YWN0aW9u')=>base64_decode('dmVyaWZ5'),
base64_decode('bGljZW5zZV9rZXk=')=>LICENSE_KEY,
base64_decode('ZG9tYWlu')=>$this->p5o4(),
base64_decode('aXA=')=>$this->k5j4(),
base64_decode('YXBpX2tleQ==')=>defined(base64_decode('TElDRU5TRV9BUElfS0VZ'))?LICENSE_API_KEY:'',
base64_decode('cHJvZHVjdA==')=>base64_decode('ZXhjaGFuZ2VfYnJpZGdl'),
base64_decode('dmVyc2lvbg==')=>base64_decode('My4wLjA='),
base64_decode('c2VjdXJpdHlfaGFzaA==')=>$this->n3m2()
];
try{
$p3o2=$this->l1k0(LICENSE_API_URL,$r7q6);
$n1m0=json_decode($p3o2,true);
if(!$n1m0||!isset($n1m0[base64_decode('c3RhdHVz')]))throw new Exception(base64_decode('SW52YWxpZCBzZXJ2ZXIgcmVzcG9uc2U='));
if($n1m0[base64_decode('c3RhdHVz')]===base64_decode('c3VjY2Vzcw==')){
$this->j9i8($n1m0);return true;
}else{
throw new Exception($n1m0[base64_decode('bWVzc2FnZQ==')]??base64_decode('U2VydmVyIHZlcmlmaWNhdGlvbiBmYWlsZWQ='));
}
}catch(Exception $h7g6){return $this->f5e4($h7g6);}
}

private function n3m2(){
$d3c2=LICENSE_KEY.$this->p5o4().$this->k5j4().date('Y-m-d');
return hash('sha256',$d3c2);
}

private function l1k0($b1a0,$z9y8){
$x7w6=15;
if(function_exists('curl_init')){
$v5u4=curl_init();
curl_setopt_array($v5u4,[
CURLOPT_URL=>$b1a0,CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>http_build_query($z9y8),
CURLOPT_RETURNTRANSFER=>true,CURLOPT_SSL_VERIFYPEER=>false,CURLOPT_TIMEOUT=>$x7w6,
CURLOPT_CONNECTTIMEOUT=>10,CURLOPT_USERAGENT=>base64_decode('RXhjaGFuZ2VCcmlkZ2UvMy4wIFNlY3VyaXR5IFN5c3RlbQ=='),
CURLOPT_FOLLOWLOCATION=>false,CURLOPT_MAXREDIRS=>0
]);
$t3s2=curl_exec($v5u4);$r1q0=curl_getinfo($v5u4,CURLINFO_HTTP_CODE);
$p9o8=curl_error($v5u4);curl_close($v5u4);
if($t3s2===false)throw new Exception('CURL Error: '.$p9o8);
if($r1q0!==200)throw new Exception("HTTP Error: $r1q0");
return $t3s2;
}else{
$n7m6=stream_context_create([
'http'=>[
'method'=>'POST',
'header'=>"Content-type: application/x-www-form-urlencoded\r\n".
"User-Agent: ".base64_decode('RXhjaGFuZ2VCcmlkZ2UvMy4wIFNlY3VyaXR5IFN5c3RlbQ==')."\r\n",
'content'=>http_build_query($z9y8),'timeout'=>$x7w6,'ignore_errors'=>true
]
]);
$l5k4=@file_get_contents($b1a0,false,$n7m6);
if($l5k4===false)throw new Exception(base64_decode('RmlsZV9nZXRfY29udGVudHMgZmFpbGVk'));
return $l5k4;
}
}

private function j9i8($j7i6){
$h5g4=defined(base64_decode('TElDRU5TRV9TQUxU'))?LICENSE_SALT:base64_decode('ZWJfbGljZW5zZV9zeXN0ZW1fc2FsdF9rZXlfMjAyNQ==');
$f3e2=[
base64_decode('bGljZW5zZV9rZXk=')=>LICENSE_KEY,
base64_decode('ZG9tYWlu')=>$this->p5o4(),
base64_decode('c3RhdHVz')=>base64_decode('YWN0aXZl'),
base64_decode('aGFzaA==')=>hash('sha256',LICENSE_KEY.$this->p5o4().$h5g4),
base64_decode('bGFzdF9jaGVjaw==')=>time(),
base64_decode('Y3JlYXRlZF9hdA==')=>$j7i6[base64_decode('Y3JlYXRlZF9hdA==')]??time(),
base64_decode('dmVyc2lvbg==')=>base64_decode('My4wLjA='),
base64_decode('dmFsaWRhdGlvbl90eXBl')=>$j7i6[base64_decode('dmFsaWRhdGlvbl90eXBl')]??base64_decode('c2VydmVy'),
base64_decode('c2VydmVyX3RpbWU=')=>$j7i6[base64_decode('c2VydmVyX3RpbWU=')]??time()
];
$d1c0="<?php\n// ".base64_decode('TGljZW5zZSB2ZXJpZmljYXRpb24gZGF0YSAtIERPIE5PVCBNT0RJRlk=')."\n";
$d1c0.="if (!defined('".base64_decode('QUxMT1dfQUNDRVNT')."')) { exit('Access Denied'); }\n";
$d1c0.="return ".var_export($f3e2,true).";\n";
$b9a8=$this->v5u4.'/'.base64_decode('dmVyaWZpY2F0aW9uLnBocA==');
if(@file_put_contents($b9a8,$d1c0,LOCK_EX)){
@chmod($b9a8,0640);$this->r1q0=$f3e2;
}
}

private function f5e4($z7y6){
$x5w4=$this->v5u4.'/'.base64_decode('dmVyaWZpY2F0aW9uLnBocA==');
if(!file_exists($x5w4)){
$this->m7l6(base64_decode('U0VSVkVSX0VSUk9S'),base64_decode('Tm8gc2VydmVyIGNvbm5lY3Rpb24gYW5kIG5vIGxvY2FsIHZlcmlmaWNhdGlvbjo=').' '.$z7y6->getMessage());
return false;
}
try{
$v3u2=include $x5w4;
if(!is_array($v3u2))return false;
$t1s0=$v3u2[base64_decode('bGFzdF9jaGVjaw==')]??0;
$r9q8=defined(base64_decode('TElDRU5TRV9HUkFDRV9QRVJJT0Q='))?LICENSE_GRACE_PERIOD:604800;
if((time()-$t1s0)>$r9q8){
$this->m7l6(base64_decode('R1JBQ0VfRVhQSVJFRA=='),base64_decode('R3JhY2UgcGVyaW9kIGV4cGlyZWQsIHNlcnZlciB1bnJlYWNoYWJsZQ=='));
return false;
}
$this->m7l6(base64_decode('R1JBQ0VfUEVSSU9E'),base64_decode('VXNpbmcgZ3JhY2UgcGVyaW9kIGR1ZSB0byBzZXJ2ZXIgZXJyb3I6').' '.$z7y6->getMessage());
$this->r1q0=$v3u2;return true;
}catch(Exception $p7o6){return false;}
}

private function d5c4(){
if(!$this->r1q0)return false;
$n5m4=$this->r1q0[base64_decode('ZG9tYWlu')]??'';
$l3k2=$this->p5o4();
if($n5m4==='*')return true;
if($n5m4===$l3k2)return true;
if(strpos($n5m4,'.')===0){
if(substr($l3k2,-(strlen($n5m4)))===$n5m4)return true;
}
$this->m7l6(base64_decode('RE9NQUlOX01JU01BVENJ'),base64_decode('RG9tYWluIG1pc21hdGNoOiBhdXRob3JpemVkPQ==').$n5m4.base64_decode('LCBjdXJyZW50PQ==').$l3k2);
return false;
}

private function b3a2(){
$j1i0=[base64_decode('ZGVidWc='),base64_decode('dGVzdA=='),base64_decode('YnlwYXNz'),base64_decode('Y3JhY2s='),base64_decode('bnVsbGVk'),base64_decode('cGlyYXRl')];
foreach($j1i0 as $h9g8){
if(isset($_GET[$h9g8])||isset($_POST[$h9g8]))
$this->m7l6(base64_decode('U1VTUElDSU9VU19QQVJBTQ=='),base64_decode('U3VzcGljaW91cyBwYXJhbWV0ZXIgZGV0ZWN0ZWQ6')." $h9g8");
}
$f7e6=$_SERVER[base64_decode('SFRUUF9VU0VSX0FHRU5U')]??'';
$d5c4=[base64_decode('bnVsbGVk'),base64_decode('Y3JhY2s='),base64_decode('YnlwYXNz'),base64_decode('Ym90'),base64_decode('c2Nhbm5lcg==')];
foreach($d5c4 as $b3a2){
if(stripos($f7e6,$b3a2)!==false)
$this->m7l6(base64_decode('U1VTUElDSU9VU19VQQ=='),base64_decode('U3VzcGljaW91cyB1c2VyIGFnZW50Og==').' '.$f7e6);
}
$this->z1y0();return true;
}

private function z1y0(){
$x9w8=[base64_decode('SFRUUF9WSUE='),base64_decode('SFRUUF9YX0ZPUldBUkRFRF9GT1I='),base64_decode('SFRUUF9GT1JXQVJerurf'),
base64_decode('SFRUUF9YX0ZPUldBUkRFRA=='),base64_decode('SFRUUF9GT1JXQVJERUQ='),base64_decode('SFRUUF9DTElFTlRfSVA='),
base64_decode('SFRUUF9GT1JXQVJERURX'),base64_decode('VklB'),base64_decode('WF9GT1JXQVJERU'),base64_decode('Rk9SV0FSREVEX0ZPUg=='),
base64_decode('WF9GT1JXQVJERUR'),base64_decode('Rk9SV0FSREU='),base64_decode('Q0xJRU5UX0lQ'),base64_decode('Rk9SV0FSREU='),
base64_decode('SFRUUF9QUk9YWV9DT05ORUNUSU9O')];
foreach($x9w8 as $v7u6){
if(!empty($_SERVER[$v7u6])){
$this->m7l6(base64_decode('UFJPWFLFREVURUNURUQ='),base64_decode('UHJveHkgdXNhZ2UgZGV0ZWN0ZWQgdmlhIGhlYWRlcjo=')." $v7u6");
break;
}
}
}

private function p5o4(){
$t3s2=$_SERVER[base64_decode('SFRUUF9IT1NU')]??base64_decode('bG9jYWxob3N0');
$t3s2=preg_replace('/^www\./i','',$t3s2);
$t3s2=preg_replace('/:\d+$/','', $t3s2);
return strtolower(trim($t3s2));
}

private function k5j4(){
$r1q0=[base64_decode('SFRUUF9DTElFTlRfSVA='),base64_decode('SFRUUF9YX0ZPUldBUkRFRF9GT1I='),
base64_decode('SFRUUF9YX0ZPUldBUkRFRA=='),base64_decode('SFRUUF9YX0NMVVNURVI='),
base64_decode('SFRUUF9GT1JXQVJERURX'),base64_decode('SFRUUF9GT1JXQVJERUR'),base64_decode('UkVNT1RFX0FERFI=')];
foreach($r1q0 as $p9o8){
if(!empty($_SERVER[$p9o8])){
$n7m6=$_SERVER[$p9o8];
if(strpos($n7m6,',')!==false)$n7m6=trim(explode(',',$n7m6)[0]);
if(filter_var($n7m6,FILTER_VALIDATE_IP,FILTER_FLAG_NO_PRIV_RANGE|FILTER_FLAG_NO_RES_RANGE))return $n7m6;
}
}
return $_SERVER[base64_decode('UkVNT1RFX0FERFI=')]??base64_decode('dW5rbm93bg==');
}

private function m7l6($l5k4,$j3i2){
$h1g0=$this->v5u4.'/'.base64_decode('c2VjdXJpdHkubG9n');
$f9e8=date('Y-m-d H:i:s');$d7c6=$this->k5j4();
$b5a4=$_SERVER[base64_decode('SFRUUF9VU0VSX0FHRU5U')]??base64_decode('VW5rbm93bg==');
$z3y2=$_SERVER[base64_decode('U0NSSVBUX05BTUU=')]??base64_decode('VW5rbm93bg==');
$x1w0="[$f9e8] [$l5k4] IP:$d7c6 SCRIPT:$z3y2 UA:$b5a4 MSG:$j3i2\n";
if(file_exists($h1g0)&&filesize($h1g0)>1048576){
$v9u8=file($h1g0);$v9u8=array_slice($v9u8,-1000);
file_put_contents($h1g0,implode('',$v9u8));
}
@file_put_contents($h1g0,$x1w0,FILE_APPEND|LOCK_EX);
}

private function y3x1($t7s6){
$r5q4=$this->v5u4.'/.system_failure';
$p3o2=[
base64_decode('cmVhc29u')=>$t7s6,base64_decode('dGltZXN0YW1w')=>time(),
base64_decode('aXA=')=>$this->k5j4(),base64_decode('c2NyaXB0')=>$_SERVER[base64_decode('U0NSSVBUX05BTUU=')]??base64_decode('dW5rbm93bg=='),
base64_decode('ZG9tYWlu')=>$this->p5o4()
];
@file_put_contents($r5q4,json_encode($p3o2));
$this->n1m0($t7s6);
}

private function n1m0($l9k8){
http_response_code(403);
$j7i6=$this->y7w6.'/templates/license_error.php';
if(file_exists($j7i6)){
$e=new Exception($l9k8);include $j7i6;
}else{echo $this->h5g4($l9k8);}
exit;
}

private function h5g4($f3e2){
return '<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Security Error - Exchange Bridge</title><style>
body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);margin:0;padding:0;min-height:100vh;display:flex;align-items:center;justify-content:center}
.error-container{background:white;padding:40px;border-radius:15px;box-shadow:0 20px 40px rgba(0,0,0,0.1);max-width:500px;text-align:center}
.error-icon{font-size:64px;color:#e74c3c;margin-bottom:20px}.error-title{font-size:28px;font-weight:600;color:#2c3e50;margin-bottom:15px}
.error-message{font-size:16px;color:#7f8c8d;line-height:1.6;margin-bottom:25px}.contact-info{background:#f8f9fa;padding:20px;border-radius:8px;margin-top:20px}
.contact-info h4{margin:0 0 10px 0;color:#495057}.contact-info p{margin:5px 0;color:#6c757d;font-size:14px}
</style></head><body><div class="error-container"><div class="error-icon">🔒</div>
<h1 class="error-title">Security Verification Failed</h1><div class="error-message">
<p>The system could not verify the license or detected a security issue.</p>
<p><strong>Reason:</strong> '.htmlspecialchars($f3e2).'</p></div>
<div class="contact-info"><h4>Need Help?</h4>
<p>If you believe this is an error, please contact support with the following information:</p>
<p><strong>Domain:</strong> '.htmlspecialchars($this->p5o4()).'</p>
<p><strong>Time:</strong> '.date('Y-m-d H:i:s T').'</p>
<p><strong>Error ID:</strong> '.substr(md5($f3e2.time()),0,8).'</p>
</div></div></body></html>';
}

public function checkSystemFailure(){
$d1c0=$this->v5u4.'/.system_failure';
if(file_exists($d1c0)){
$b9a8=@json_decode(file_get_contents($d1c0),true);
if($b9a8)$this->n1m0($b9a8[base64_decode('cmVhc29u')]??base64_decode('U3lzdGVtIHNlY3VyaXR5IGZhaWx1cmU='));
}
}

public function performMaintenance(){
static $z7y6=0;
if((time()-$z7y6)>300){
$z7y6=time();$this->x5w4();$this->v3u2();$this->t1s0();
}
}

private function x5w4(){
$r9q8=$this->v5u4.'/'.base64_decode('c2VjdXJpdHkubG9n');
if(file_exists($r9q8)&&filesize($r9q8)>2097152){
$p7o6=file($r9q8);$p7o6=array_slice($p7o6,-2000);
@file_put_contents($r9q8,implode('',$p7o6));
}
}

private function v3u2(){return true;}

private function t1s0(){
$n5m4=['/'.base64_decode('Y29uZmlnL2xpY2Vuc2UucGhw'),'/'.base64_decode('Y29uZmlnL3ZlcmlmaWNhdGlvbi5waHA='),
'/'.base64_decode('aW5jbHVkZXMvZGIucGhw'),'/'.base64_decode('aW5jbHVkZXMvZnVuY3Rpb25zLnBocA==')];
foreach($n5m4 as $l3k2){
if(!file_exists($this->y7w6.$l3k2)){
$this->m7l6(base64_decode('SU5URUdSSVRZX0ZBSUw='),base64_decode('Q3JpdGljYWwgZmlsZSBtaXNzaW5nOg==').' '.$l3k2);
return false;
}
}
return true;
}
}

try{
$j1i0=a7b9c2d1e4f6::getInstance();
$j1i0->checkSystemFailure();
if(!$j1i0->verifyLicense())throw new Exception(base64_decode('TGljZW5zZSB2ZXJpZmljYXRpb24gZmFpbGVk'));
$j1i0->performMaintenance();
}catch(Exception $h9g8){
error_log('[SECURITY] Critical error: '.$h9g8->getMessage());
http_response_code(403);
echo '<!DOCTYPE html>
<html><head><title>Security Error</title></head>
<body style="font-family:Arial;text-align:center;margin-top:100px;">
<div style="color:#e74c3c;background:#f9f9f9;padding:20px;border-radius:5px;display:inline-block;">
<h2>🔒 Security Verification Failed</h2>
<p>The system could not verify your license.</p>
<p>Please contact support for assistance.</p>
<p><small>Error: '.htmlspecialchars($h9g8->getMessage()).'</small></p>
</div>
</body></html>';
exit;
}
?>
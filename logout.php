<?php
require __DIR__.'/lib/bootstrap.php';
if(!$config['demo_mode'] && isAuthenticated()) writeAudit('auth.logout',['id'=>(int)($_SESSION['user_id']??0)]);
$_SESSION=[];if(ini_get('session.use_cookies')){$p=session_get_cookie_params();setcookie(session_name(),'',time()-42000,$p['path'],$p['domain'],$p['secure'],$p['httponly']);}session_destroy();header('Location: login.php');exit;

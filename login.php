<?php
require __DIR__.'/lib/bootstrap.php';
if($config['demo_mode']){header('Location: index.php');exit;}
if(isAuthenticated()){header('Location: index.php');exit;}
$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
  try{
    verifyCsrf();
    $email=strtolower(trim((string)($_POST['email']??'')));$pass=(string)($_POST['password']??'');
    if(!filter_var($email,FILTER_VALIDATE_EMAIL)||$pass==='')throw new RuntimeException('Email atau password tidak valid.');
    $pdo=db();if(!$pdo)throw new RuntimeException('Database tidak tersedia.');$ip=(string)($_SERVER['REMOTE_ADDR']??'unknown');
    if(!loginRateAllowed($pdo,$email,$ip)){usleep(350000);throw new RuntimeException('Terlalu banyak percobaan login. Coba kembali beberapa menit lagi.');}
    $st=$pdo->prepare('SELECT id,name,email,password_hash,role,company_id,is_active FROM users WHERE email=? LIMIT 1');$st->execute([$email]);$u=$st->fetch();
    if(!$u||!$u['is_active']||!password_verify($pass,$u['password_hash'])){recordLoginAttempt($pdo,$email,$ip,false);usleep(250000);throw new RuntimeException('Email atau password salah.');}
    recordLoginAttempt($pdo,$email,$ip,true);$pdo->prepare('UPDATE users SET last_login_at=NOW() WHERE id=?')->execute([$u['id']]);if(password_needs_rehash($u['password_hash'],PASSWORD_DEFAULT)){$pdo->prepare('UPDATE users SET password_hash=? WHERE id=?')->execute([password_hash($pass,PASSWORD_DEFAULT),$u['id']]);}
    session_regenerate_id(true);$_SESSION['user_id']=(int)$u['id'];$_SESSION['user_name']=$u['name'];writeAudit('auth.login',['id'=>(int)$u['id']]);header('Location: index.php');exit;
  }catch(Throwable $e){$error=$e->getMessage();}
}
?><!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Login · NEXA Group Finance</title><link rel="stylesheet" href="assets/app.css?v=3"></head><body class="auth-body"><main class="auth-shell"><section class="auth-visual"><div class="brand auth-brand"><div class="brand-mark">N</div><div><strong>NEXA</strong><small>Group Finance</small></div></div><div><span class="eyebrow">SECURE FINANCE COMMAND CENTER</span><h1>Satu pusat kendali untuk seluruh badan usaha.</h1><p>Ledger, konsolidasi, approval, treasury dan analytics dalam satu sistem PHP + MySQL.</p></div><div class="auth-stats"><span><b>Double-entry</b><small>Ledger engine</small></span><span><b>RBAC</b><small>Role access</small></span><span><b>Audit</b><small>Traceable actions</small></span></div></section><section class="auth-card"><div><span class="eyebrow">WELCOME BACK</span><h2>Masuk ke NEXA</h2><p>Gunakan akun yang diberikan administrator grup.</p></div><?php if($error):?><div class="auth-error"><?=e($error)?></div><?php endif?><form method="post"><input type="hidden" name="csrf" value="<?=e(csrf())?>"><label>Email<input type="email" name="email" autocomplete="username" required></label><label>Password<input type="password" name="password" autocomplete="current-password" required></label><button class="primary-btn full" type="submit">Masuk Aman</button></form><a class="setup-link" href="setup.php">Instalasi pertama?</a></section></main></body></html>

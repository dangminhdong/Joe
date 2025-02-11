<?php
if (!defined('__TYPECHO_ROOT_DIR__')) {
	http_response_code(404);
	exit;
}
?>
<!DOCTYPE html>
<html lang="zh-cn">

<head>
	<?php $this->setArchiveTitle('重置密码') ?>
	<?php $this->need('module/head.php') ?>
	<?php $this->need('module/user/head.php') ?>
	<script src="<?= joe\theme_url('assets/js/joe.user.retrieve.js'); ?>"></script>
</head>

<body>
	<?php $this->need('module/header.php'); ?>
	<div id="Joe">
		<?php $referer = empty($_GET['referer']) ? '/' : urlencode(strip_tags($_GET['referer'])); ?>
		<div class="container">
			<div>
				<div class="card-body">
					<div class="title">
						<h4>重置密码</h4>
						<p>输入注册时的邮箱设置新密码</p>
					</div>
					<div class="form-group mb-3" id="post1">
						<label>邮箱</label>
						<input class="form-control" type="text" id="email" placeholder="请输入您的邮箱">
					</div>
					<div class="form-group" id="post2">
						<label>验证码</label>
						<div class="input-group">
							<input type="text" class="form-control" id="code" placeholder="请输入验证码">
							<button class="btn" id="send" type="button">获取验证码</button>
						</div>
					</div>
					<div class="form-group mb-3" id="new1" style="display:none">
						<label>新密码</label>
						<input class="form-control" type="password" id="password" placeholder="请输入您的新密码">
					</div>
					<div class="form-group mb-3" id="new2" style="display:none">
						<label>确认密码</label>
						<input class="form-control" type="password" id="confirm_password" placeholder="请确认您的新密码">
					</div>
					<button class="btn btn-light" id="check" type="submit">验 证</button>
					<button class="btn btn-light" id="retrieve" type="submit" style="display:none">设置新密码</button>
				</div>
				<?php
				if ($this->options->allowRegister) {
				?>
					<p class="text-muted">返回 <a href="/user/login<?= '?referer=' . $referer ?>" class="text-dark ml-1"><b>登陆</b></a></p>
				<?php
				}
				?>
			</div>
		</div>
		<?php $this->need('module/bottom.php'); ?>
	</div>
	<?php $this->need('module/footer.php') ?>
</body>

</html>
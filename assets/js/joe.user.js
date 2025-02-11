Joe.DOMContentLoaded.user ||= () => {
	console.log('调用：Joe.DOMContentLoaded.user');

	const ajax = (options) => {
		const button = document.querySelector(options.button);
		return $.ajax({
			url: options.url,
			type: 'post',
			dataType: 'json',
			data: options.data,
			beforeSend: function () {
				Joe.btnLoad(button, options.operate + '中...');
			},
			complete: function () {
				Joe.btnLoad(button, false);
			},
			error: function () {
				Joe.btnLoad(button, false);
				autolog.log('服务器繁忙', 'error');
			},
			success(data) {
				autolog.log(data.message, data.code == 200 ? 'success' : 'warn');
				if (data.location) {
					const location = data.location == true ? window.Joe.referer : data.location;
					setTimeout(() => window.location.href = location, 1500);
				}
				if (options.success) options.success(data);
			}
		});
	}

	const input = (name) => {
		return document.querySelector(`#user-form input[name=${name}]`);
	}

	/** 表单提交 */
	(() => {
		if (!document.querySelector('#user-form')) return;
		$("#user-form").submit(function (event) {
			event.preventDefault();
			ajax({
				url: $(this).attr('action'),
				data: $(this).serialize(),
				operate: $(this).attr('operate'),
				button: '#user-form button[type=submit]'
			});
		});
	})();

	/** 根据账号自动补全QQ邮箱 */
	(() => {
		if (!input('username') || !input('email')) return;
		$(input('username')).on('input propertychange', function () {
			if (input('email').value) return;
			let value = $(this).val();
			if (/^[1-9][0-9]{4,}$/.test(value)) input('email').value = value + '@qq.com';
		});
	})();


	/** 获取邮箱验证码 */
	(() => {
		if (!document.querySelector('#send-captcha')) return;
		var captchaCountdown = 60;
		const captchaTimer = () => {
			if (captchaCountdown == 0) {
				$("#send-captcha").html('获取验证码');
				$("#send-captcha").attr("disabled", false);
				$("#email").attr("disabled", false);
				captchaCountdown = 60;
				return;
			} else {
				$("#send-captcha").html(captchaCountdown + "秒后重可发");
				$("#email").attr("disabled", true);
				$("#send-captcha").attr("disabled", true);
				captchaCountdown--;
			}
			setTimeout(function () {
				captchaTimer()
			}, 1000);
		}
		$("#send-captcha").click(function () {
			let email = $("#user-login input[name=email]").val();
			if (!email) return autolog.log("请输入邮箱后发送验证码", 'warning');
			ajax({
				url: $(this).attr('action'),
				data: { email: email },
				operate: '发送',
				button: '#send-captcha',
				success: function (data) {
					if (data.code == 200) captchaTimer();
				}
			});
		});
	})();

}
document.addEventListener(Joe.DOMContentLoaded.event, Joe.DOMContentLoaded.user, { once: true });
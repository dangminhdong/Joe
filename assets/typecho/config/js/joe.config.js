document.addEventListener("DOMContentLoaded", function () {
	var e = document.querySelectorAll(".joe_config__aside .item"),
		t = document.querySelector(".joe_config__notice"),
		s = document.querySelector(".joe_config > form"),
		n = document.querySelectorAll(".joe_content");
	if (e.forEach(function (o) {
		o.addEventListener("click", function () {
			e.forEach(function (e) {
				e.classList.remove("active")
			}), o.classList.add("active");
			var c = o.getAttribute("data-current");
			sessionStorage.setItem("joe_config_current", c), "joe_notice" === c ? (t.style
				.display = "block", s.style.display = "none") : (t.style.display = "none", s
					.style.display = "block"), n.forEach(function (e) {
						e.style.display = "none";
						var t = e.classList.contains(c);
						t && (e.style.display = "block")
					})
		})
	}), sessionStorage.getItem("joe_config_current")) {
		var o = sessionStorage.getItem("joe_config_current");
		"joe_notice" === o ? (t.style.display = "block", s.style.display = "none") : (s.style.display = "block",
			t.style.display = "none"), e.forEach(function (e) {
				var t = e.getAttribute("data-current");
				t === o && e.classList.add("active")
			}), n.forEach(function (e) {
				e.classList.contains(o) && (e.style.display = "block")
			})
	} else e[0].classList.add("active"), t.style.display = "block", s.style.display = "none";
	if ($('[data-current="joe_code"]').hasClass('active')) {
		sessionStorage.setItem("joe_config_current", 'joe_notice');
		// sessionStorage.removeItem("joe_config_current");
	}
	Joe.service_domain = '//auth.bri6.cn/server/joe/';
	$.getJSON(`${Joe.service_domain}message`, function (data) {
		t.innerHTML = '<p class="title">最新版本：' + data.title + "</p>" + data.content;
	});
	function openLinkInNewTab(url) {
		const a = document.createElement('a');
		a.href = url;
		a.target = '_blank';
		a.dispatchEvent(new MouseEvent('click', {
			bubbles: true,
			cancelable: true,
			view: window
		}));
	}
	function update(type = 'passive') {
		if (type == 'active') var loading = layer.load(2, { shade: 0.3 });
		$.ajax({
			type: "post",
			url: `${Joe.service_domain}update`,
			data: {
				title: Joe.title,
				version: Joe.version,
				domain: window.location.host,
				logo: Joe.logo,
				favicon: Joe.Favicon
			},
			dataType: "json",
			success: (data) => {
				layer.close(loading);
				if (data.update) {
					layer.confirm(data.msg, { btn: data.btn }, () => {
						openLinkInNewTab(data.download);
					}, () => {
						layer.alert(`<p>最怕问初衷，大梦成空。</p><p>眉间鬓上老英雄，剑甲鞮鍪封厚土，说甚擒龙。</p><p>壮志付西风，逝去无踪。</p><p>少年早作一闲翁，诗酒琴棋终日里，岁月匆匆。</p><p>不更新等着养老吗？</p>`);
					});
				} else if (type == 'active') {
					autolog.log(data.msg, 'info');
				}
			},
			error: () => {
				layer.close(loading);
				autolog.log('请求错误，请检查您的网络', 'error');
			}
		});
	}
	update();
	$('#update').click(() => {
		update('active');
	});
});
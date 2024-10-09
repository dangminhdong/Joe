<?php

if (!defined('__TYPECHO_ROOT_DIR__')) {
	http_response_code(404);
	exit;
}

$JStaticAssetsUrl = new Typecho_Widget_Helper_Form_Element_Text(
	'JStaticAssetsUrl',
	null,
	null,
	'自定义存储空间资源',
	'介绍：将本主题所需要的CSS、JS等资源文件使用某个站点来提供，以便节省服务器宽带 提升小型服务器加载速度<br>
		 注意：必须保证对方站点同样为再续前缘版并且版本一致<br>
		 例如：http://blog.bri6.cn/usr/themes/Joe（此站点虽是同款，然已启用防盗链，无需再试了）<br>
		 其他：<a target="_blank" href="http://auth.bri6.cn/server/joe/sitelist">本站同款站点列表</a>'
);
$JStaticAssetsUrl->setAttribute('class', 'joe_content joe_global');
$form->addInput($JStaticAssetsUrl);

$JCdnUrl = new Typecho_Widget_Helper_Form_Element_Text(
	'JCdnUrl',
	null,
	'https://cdn.bootcdn.net/ajax/libs/',
	'公共静态资源CDN接口',
	'
	<span>介绍：默认使用BootCDN，不懂请勿乱填。若资源加载慢，可从下方接口列表选择或提供自己的接口</span><br>
	<span>格式：URL接口 || 版本号分隔符，不填写则默认为/，JsDelivr类型的需要使用@</span><br>
	<span>BootCDN：https://cdn.bootcdn.net/ajax/libs/</span><br>
	<span>Staticfile CDN：https://cdn.staticfile.net/</span><br>
	<span>字节跳动（距上次更新已两年多）：https://lf3-cdn-tos.bytecdntp.com/cdn/expire-1-M/</span><br>
	<span>Zstatic（又拍云赞助）：https://s4.zstatic.net/ajax/libs/</span><br>
	<span>7ED Services（www.7ed.net）：https://use.sevencdn.com/ajax/libs/</span><br>
	<span>渺软公益CDN回源JsDelivr（cdn.onmicrosoft.cn）：https://jsd.onmicrosoft.cn/npm/</span><br>
	<span>渺软公益CDN回源UNPKG（cdn.onmicrosoft.cn）：https://npm.onmicrosoft.cn/</span><br>
	<span>渺软公益CDN回源CDNJS（cdn.onmicrosoft.cn）：https://cdnjs.onmicrosoft.cn/ajax/libs/</span><br>
	<span>南方科技大学：https://mirrors.sustech.edu.cn/cdnjs/ajax/libs/</span><br>
	<span>360（单纯放到这里）: https://cdn.baomitu.com</span><br>
	<span>JsDelivr：https://cdn.jsdelivr.net/npm/</span><br>
	<span>Google Hosted Libraries（国内用不了）：https://ajax.googleapis.com/ajax/libs/</span><br>
	<span>CDNJS（国内不稳定）：https://cdnjs.cloudflare.com/ajax/libs/</span><br>
	<span>烧饼博客（CDNJS镜像）：https://cdnjs.loli.net/ajax/libs/</span><br>
	'
);
$JCdnUrl->setAttribute('class', 'joe_content joe_global');
$form->addInput($JCdnUrl);

$JNavMaxNum = new Typecho_Widget_Helper_Form_Element_Select(
	'JNavMaxNum',
	array(
		'3' => '3个（默认）',
		'4' => '4个',
		'5' => '5个',
		'6' => '6个',
		'7' => '7个',
	),
	'3',
	'选择导航栏最大显示的个数',
	'介绍：用于设置最大多少个后，以更多下拉框显示'
);
$JNavMaxNum->setAttribute('class', 'joe_content joe_global');
$form->addInput($JNavMaxNum->multiMode());

$JCustomNavs = new Typecho_Widget_Helper_Form_Element_Textarea(
	'JCustomNavs',
	NULL,
	NULL,
	'导航栏自定义链接（非必填）',
	'介绍：用于自定义导航栏链接 <br />
		 格式：跳转文字 || 跳转链接（中间使用两个竖杠分隔）<br />
		 其他：一行一个，一行代表一个超链接 <br />
		 例如：<br />
			百度一下 || https://baidu.com <br />
			腾讯视频 || https://v.qq.com
		 '
);
$JCustomNavs->setAttribute('class', 'joe_content joe_global');
$form->addInput($JCustomNavs);

$JFooter_Left = new Typecho_Widget_Helper_Form_Element_Textarea(
	'JFooter_Left',
	NULL,
	'2021 - ' . date('Y') . ' © <a href="http://blog.bri6.cn">易航博客</a>丨技术支持：<a href="http://blog.bri6.cn" target="_blank">易航</a>',
	'自定义底部栏左侧内容（非必填）',
	'介绍：用于修改全站底部左侧内容（wap端上方） <br>
		 例如：<style style="display:inline">2021 - ' . date('Y') . ' © <a href="{站点链接}">{站点标题}</a>丨技术支持：<a href="http://blog.bri6.cn" target="_blank">易航</a></style>'
);
$JFooter_Left->setAttribute('class', 'joe_content joe_global');
$form->addInput($JFooter_Left);

$JFooter_Right = new Typecho_Widget_Helper_Form_Element_Textarea(
	'JFooter_Right',
	NULL,
	'<a href="http://blog.bri6.cn/feed/" target="_blank">RSS</a>' . PHP_EOL . '<a href="http://blog.bri6.cn/sitemap.xml" target="_blank" style="margin-left: 15px">MAP</a>' . PHP_EOL . '<a href="https://beian.miit.gov.cn/#/Integrated/index" target="_blank" style="margin-left: 15px">冀ICP备2021010323号</a>',
	'自定义底部栏右侧内容（非必填）',
	'介绍：用于修改全站底部右侧内容（wap端下方） <br>
	例如：' . htmlentities('<a href="http://blog.bri6.cn/feed/" target="_blank">RSS</a>') . '<br>' . htmlentities('<a href="http://blog.bri6.cn/sitemap.xml" target="_blank" style="margin-left: 15px">MAP</a>') . '<br>' . htmlentities('<a href="https://beian.miit.gov.cn/#/Integrated/index" target="_blank" style="margin-left: 15px">冀ICP备2021010323号</a>')
);
$JFooter_Right->setAttribute('class', 'joe_content joe_global');
$form->addInput($JFooter_Right);

$JBirthDay = new Typecho_Widget_Helper_Form_Element_Text(
	'JBirthDay',
	NULL,
	date('Y/n/j H:i:s'),
	'网站成立日期（非必填）',
	'介绍：用于显示当前站点已经运行了多少时间。<br>
		 注意：填写时务必保证填写正确！例如：2021/1/1 00:00:00 <br>
		 其他：不填写则不显示，若填写错误，则不会显示计时'
);
$JBirthDay->setAttribute('class', 'joe_content joe_global');
$form->addInput($JBirthDay);

$JCustomFont = new Typecho_Widget_Helper_Form_Element_Text(
	'JCustomFont',
	NULL,
	NULL,
	'自定义网站字体（非必填）',
	'介绍：用于修改全站字体，填写则使用引入的字体，不填写使用默认字体 <br>
		 格式：字体URL链接（推荐使用woff2格式的字体，网页专用字体格式，占用空间小，加载速度更快） <br>
		 注意：字体文件一般能上MB大小，建议使用cdn链接，如果和本站不是同一个域名，则需要远程资源URL响应允许跨域的响应头规则'
);
$JCustomFont->setAttribute('class', 'joe_content joe_global');
$form->addInput($JCustomFont);

$JCustomAvatarSource = new Typecho_Widget_Helper_Form_Element_Text(
	'JCustomAvatarSource',
	NULL,
	NULL,
	'自定义头像源（非必填）',
	'介绍：用于修改全站头像源地址 <br>
		 例如：https://gravatar.ihuan.me/avatar/ <br>
		 其他：非必填，默认头像源为https://gravatar.helingqi.com/wavatar/ <br>
		 注意：填写时，务必保证最后有一个/字符，否则不起作用！'
);
$JCustomAvatarSource->setAttribute('class', 'joe_content joe_global');
$form->addInput($JCustomAvatarSource);

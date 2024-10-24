<?php

if (!defined('__TYPECHO_ROOT_DIR__')) {
	http_response_code(404);
	exit;
}

use Metowolf\Meting;

/* 获取文章列表 已测试 √  */

function _getPost($self)
{
	$self->response->setStatus(200);
	$page = $self->request->page;
	$pageSize = $self->request->pageSize;
	$type = $self->request->type;

	/* sql注入校验 */
	if (!preg_match('/^\d+$/', $page)) {
		return $self->response->throwJson(array("data" => "非法请求！已屏蔽！"));
	}
	if (!preg_match('/^\d+$/', $pageSize)) {
		return $self->response->throwJson(array("data" => "非法请求！已屏蔽！"));
	}
	if (!preg_match('/^[created|views|commentsNum|agree]+$/', $type)) {
		return $self->response->throwJson(array("data" => "非法请求！已屏蔽！"));
	}

	/* 如果传入0，强制赋值1 */
	if ($page == 0) $page = 1;
	$result = [];


	/* 增加置顶文章功能，通过JS判断（如果你想添加其他标签的话，请先看置顶如何实现的） */
	$sticky_text = Helper::options()->JIndexSticky;
	if ($sticky_text && $page == 1) {
		$sticky_arr = explode("||", $sticky_text);
		foreach ($sticky_arr as $cid) {
			$cid = trim($cid);
			$self->widget('Widget_Contents_Post@' . $cid, 'cid=' . $cid)->to($item);
			if ($item->next()) {
				$result[] = array(
					"cid" => $item->cid,
					"mode" => $item->fields->mode ? $item->fields->mode : 'default',
					"image" => joe\getThumbnails($item),
					"time" => date('Y-m-d', $item->created),
					'date_time' => date('Y-m-d H:i:s', $item->created),
					"created" => date('Y年m月d日', $item->created),
					'dateWord' => joe\dateWord($item->dateWord),
					"title" => $item->title,
					"abstract" => joe\getAbstract($item, false),
					"category" => $item->categories,
					"views" => joe\getViews($item, false),
					"commentsNum" => number_format($item->commentsNum),
					"agree" => joe\getAgree($item, false),
					"permalink" => $item->permalink,
					"lazyload" => joe\getLazyload(false),
					"type" => "sticky",
					'target' => Helper::options()->Jessay_target,
					'author_screenName' => $item->author->screenName,
					'author_permalink' => $item->author->permalink,
					'author_avatar' => joe\getAvatarByMail($item->author->mail, false),
					'tags' => $item->tags
				);
			}
		}
	} else {
		$sticky_arr = [];
	}
	$JIndex_Hide_Post = array_map('trim', explode("||", Helper::options()->JIndex_Hide_Post ?? ''));
	$hide_post_list = array_merge($sticky_arr, $JIndex_Hide_Post);
	$hide_categorize_list = array_map('trim', explode("||", Helper::options()->JIndex_Hide_Categorize ?? ''));
	$self->widget('Widget_Contents_Sort', 'page=' . $page . '&pageSize=' . $pageSize . '&type=' . $type)->to($item);
	while ($item->next()) {
		foreach ($item->categories as $key => $categorie) {
			if (in_array($categorie['slug'], $hide_categorize_list)) continue 2;
		}
		if (!in_array($item->cid, $hide_post_list)) {
			$result[] = array(
				"cid" => $item->cid,
				"mode" => $item->fields->mode ? $item->fields->mode : 'default',
				"image" => joe\getThumbnails($item),
				"time" => date('Y-m-d', $item->created),
				'date_time' => date('Y-m-d H:i:s', $item->created),
				"created" => date('Y年m月d日', $item->created),
				'dateWord' => joe\dateWord($item->dateWord),
				"title" => $item->title,
				"abstract" => joe\getAbstract($item, false),
				"category" => $item->categories,
				"views" => number_format($item->views),
				"commentsNum" => number_format($item->commentsNum),
				"agree" => number_format($item->agree),
				"permalink" => $item->permalink,
				"lazyload" => joe\getLazyload(false),
				"type" => "normal",
				'target' => Helper::options()->Jessay_target,
				'author_screenName' => $item->author->screenName,
				'author_permalink' => $item->author->permalink,
				'author_avatar' => joe\getAvatarByMail($item->author->mail, false),
				'tags' => $item->tags
			);
		}
	};

	$self->response->throwJson(array("data" => $result));
}

// 百度统计展示
function _getstatistics($self)
{
	$self->response->setStatus(200);
	$statistics_config = joe\baidu_statistic_config();
	if (!is_array($statistics_config)) {
		$self->response->throwJson(array('access_token' => 'off'));
	}
	if (empty($statistics_config['access_token'])) {
		$self->response->throwJson(array('access_token' => 'off'));
	}
	// 获取站点列表
	$baidu_list = function () use ($statistics_config, $self) {
		$url = 'https://openapi.baidu.com/rest/2.0/tongji/config/getSiteList?access_token=' . trim($statistics_config['access_token']);
		$data = json_decode(file_get_contents($url), true);
		if (isset($data['error_code'])) {
			if ($data['error_code'] == 111) {
				$self->response->throwJson(['msg' => '请更新您的access_token']);
			}
			$self->response->throwJson($data);
		}
		return $data['list'];
	};
	// 获取站点详情
	$web_metrics = function ($list, $start_date, $end_date) use ($statistics_config) {
		$access_token = trim($statistics_config['access_token']);
		$site_id = $list['site_id'];
		$url = "https://openapi.baidu.com/rest/2.0/tongji/report/getData?access_token=$access_token&site_id=$site_id&method=trend/time/a&start_date=$start_date&end_date=$end_date&metrics=pv_count,ip_count&gran=day";
		$data = \network\http\post($url)->toArray();
		if (is_array($data)) {
			$data = $data['result']['sum'][0];
		} else {
			$data = 0;
		}
		return $data;
	};
	$domain = $_SERVER['HTTP_HOST'];
	$list = $baidu_list();
	for ($i = 0; $i < count($list); $i++) {
		if ($list[$i]['domain'] == $domain) {
			$list = $list[$i];
			break;
		}
	}
	if (!isset($list['domain']) || $list['domain'] != $domain) {
		$data = array(
			'msg' => '没有当前站点'
		);
		$self->response->throwJson($data);
	}
	$today = $web_metrics($list, date('Ymd'), date('Ymd'));
	$yesterday = $web_metrics($list, date('Ymd', strtotime("-1 days")), date('Ymd', strtotime("-1 days")));
	$moon = $web_metrics($list, date('Ym') . '01', date('Ymd'));
	$data = array(
		'code' => 200,
		'today' => $today,
		'yesterday' => $yesterday,
		'month' => $moon
	);
	$self->response->throwJson($data);
}

/* 增加浏览量 已测试 √ */
function _handleViews($self)
{
	$self->response->setStatus(200);
	$cid = $self->request->cid;
	/* sql注入校验 */
	if (!preg_match('/^\d+$/',  $cid)) {
		return $self->response->throwJson(array("code" => 0, "data" => "非法请求！已屏蔽！"));
	}
	$db = Typecho_Db::get();
	$row = $db->fetchRow($db->select('views')->from('table.contents')->where('cid = ?', $cid));
	if (sizeof($row) > 0) {
		$db->query($db->update('table.contents')->rows(array('views' => (int)$row['views'] + 1))->where('cid = ?', $cid));
		$self->response->throwJson(array(
			"code" => 1,
			"data" => array('views' => number_format($db->fetchRow($db->select('views')->from('table.contents')->where('cid = ?', $cid))['views']))
		));
	} else {
		$self->response->throwJson(array("code" => 0, "data" => null));
	}
}

/* 点赞和取消点赞 已测试 √ */
function _handleAgree($self)
{
	$self->response->setStatus(200);
	$cid = $self->request->cid;
	$type = $self->request->type;
	/* sql注入校验 */
	if (!preg_match('/^\d+$/',  $cid)) {
		return $self->response->throwJson(array("code" => 0, "data" => "非法请求！已屏蔽！"));
	}
	/* sql注入校验 */
	if (!preg_match('/^[agree|disagree]+$/', $type)) {
		return $self->response->throwJson(array("code" => 0, "data" => "非法请求！已屏蔽！"));
	}
	$db = Typecho_Db::get();
	$row = $db->fetchRow($db->select('agree')->from('table.contents')->where('cid = ?', $cid));
	if (sizeof($row) > 0) {
		if ($type === "agree") {
			$db->query($db->update('table.contents')->rows(array('agree' => (int)$row['agree'] + 1))->where('cid = ?', $cid));
		} else {
			if (intval($row['agree']) - 1 >= 0) {
				$db->query($db->update('table.contents')->rows(array('agree' => (int)$row['agree'] - 1))->where('cid = ?', $cid));
			}
		}
		$self->response->throwJson(array(
			"code" => 1,
			"data" => array('agree' => number_format($db->fetchRow($db->select('agree')->from('table.contents')->where('cid = ?', $cid))['agree']))
		));
	} else {
		$self->response->throwJson(array("code" => 0, "data" => null));
	}
}

/* 查询是否收录 已测试 √ */
function _getRecord($self)
{
	$self->response->setStatus(200);
	$client = new \network\http\Client;
	$client->param([
		'url' => $self->request->site
	]);
	$output = $client->get('https://api.fish9.cn/api/baidu/')->toArray();
	if (is_array($output)) {
		if (isset($output['baidu']) && $output['baidu']) {
			$self->response->throwJson(array("data" => "已收录"));
		} else {
			$cid = $self->request->cid;
			/* sql注入校验 */
			if (!preg_match('/^\d+$/',  $cid)) {
				return $self->response->throwJson(array("code" => 0, "data" => "非法请求！已屏蔽！"));
			}
			$db = Typecho_Db::get();
			$sql = $db->select('str_value')->from('table.fields')->where('cid = ?', $cid)->where('name = ?', 'baidu_push');
			$row = $db->fetchRow($sql);
			if ($row && $row['str_value'] == 'yes') {
				$self->response->throwJson(["data" => "未收录，已推送"]);
			} else {
				$self->response->throwJson(array("data" => "未收录"));
			}
		}
	} else {
		$self->response->throwJson(array("data" => "检测失败"));
	}
}

/* 主动推送到百度收录 已测试 √ */
function _pushRecord($self)
{
	$self->response->setStatus(200);

	$cid = $self->request->cid;

	/* sql注入校验 */
	if (!preg_match('/^\d+$/',  $cid)) {
		return $self->response->throwJson(array("code" => 0, "data" => "非法请求！已屏蔽！"));
	}

	$db = Typecho_Db::get();
	$sql = $db->select('str_value')->from('table.fields')->where('cid = ?', $cid)->where('name = ?', 'baidu_push');
	$row = $db->fetchRow($sql);
	if ($row && $row['str_value'] == 'yes') {
		$self->response->throwJson(['already' => true]);
		return;
	}

	$token = trim(Helper::options()->JBaiduToken);
	$domain = $self->request->domain;
	$url = $self->request->url;
	$urls = explode(",", $url);
	$api = "http://data.zz.baidu.com/urls?site={$domain}&token={$token}";
	$ch = curl_init();
	$options =  [
		CURLOPT_URL => $api,
		CURLOPT_POST => true,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_POSTFIELDS => implode("\n", $urls),
		CURLOPT_HTTPHEADER => array('Content-Type: text/plain')
	];
	curl_setopt_array($ch, $options);
	$result = curl_exec($ch);
	curl_close($ch);
	$result = json_decode($result, true);
	if (empty($result['error'])) {
		// 存储推送记录到文章或者页面的自定义字段里面
		$db = Typecho_Db::get();
		if (isset($row['str_value']) && $row['str_value'] != 'yes') {
			$db->query(
				$db->update('table.fields')
					->rows(['str_value' => 'yes'])
					->where('cid = ?', $cid)
					->where('name = ?', 'baidu_push')
			);
		} else {
			$db->query(
				$db
					->insert('table.fields')
					->rows(array(
						'cid' => $cid,
						'name' => 'baidu_push',
						'type' => 'str',
						'str_value' => 'yes',
						'int_value' => '0',
						'float_value' => '0',
					))
			);
		}
	}
	if (!empty($result['message'])) {
		$messages = [
			'site error' => '站点未在站长平台验证',
			'empty content' => 'post内容为空',
			'only 2000 urls are allowed once' => '每次最多只能提交2000条链接',
			'over quota' => '超过每日配额了，超配额后再提交都是无效的',
			'token is not valid' => 'token错误',
			'not found' => '接口地址填写错误',
			'internal error, please try later' => '服务器偶然异常，通常重试就会成功'
		];
		foreach ($messages as $key => $value) {
			if ($result['message'] == $key) $result['message'] = $value;
		}
	}
	$self->response->throwJson(array(
		'domain' => $domain,
		'url' => $url,
		'data' => $result
	));
}

// 主动推送到必应收录
function _pushBing($self)
{
	$self->response->setStatus(200);
	$token = Helper::options()->JBingToken;
	if (empty($token)) {
		exit;
	}
	$domain = $self->request->domain;  //网站域名
	$url = $self->request->url;
	$urls = explode(",", $url);  //要推送的url
	$api = "https://www.bing.com/webmaster/api.svc/json/SubmitUrlbatch?apikey=$token";
	$data = array(
		'siteUrl' => $domain,
		'urlList' => $urls
	);
	$ch = curl_init();
	$options =  array(
		CURLOPT_URL => $api,
		CURLOPT_POST => true,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_POSTFIELDS => json_encode($data),
		CURLOPT_HTTPHEADER => array('Content-Type: application/json; charset=utf-8', 'Host: ssl.bing.com'),
	);
	curl_setopt_array($ch, $options);
	$result = curl_exec($ch);
	curl_close($ch);
	$self->response->throwJson(array(
		'domain' => $domain,
		'url' => $url,
		'data' => json_decode($result, TRUE)
	));
}

/* 获取壁纸分类 已测试 √ */
function _getWallpaperType($self)
{
	$self->response->setStatus(200);
	$json = \network\http\get("http://cdn.apc.360.cn/index.php?c=WallPaper&a=getAllCategoriesV2&from=360chrome");
	$res = json_decode($json, TRUE);
	if ($res['errno'] == 0) {
		$self->response->throwJson([
			"code" => 1,
			"data" => $res['data']
		]);
	} else {
		$self->response->throwJson([
			"code" => 0,
			"data" => null
		]);
	}
}

/* 获取壁纸列表 已测试 √ */
function _getWallpaperList($self)
{
	$self->response->setStatus(200);

	$cid = $self->request->cid;
	$start = $self->request->start;
	$count = $self->request->count;
	$json = \network\http\get("http://wallpaper.apc.360.cn/index.php?c=WallPaper&a=getAppsByCategory&cid={$cid}&start={$start}&count={$count}&from=360chrome");
	$res = json_decode($json, TRUE);
	if ($res['errno'] == 0) {
		$self->response->throwJson([
			"code" => 1,
			"data" => $res['data'],
			"total" => $res['total']
		]);
	} else {
		$self->response->throwJson([
			"code" => 0,
			"data" => null
		]);
	}
}

/* 抓取苹果CMS视频分类 已测试 √ */
function _getMaccmsList($self)
{
	$self->response->setStatus(200);

	$cms_api = Helper::options()->JMaccmsAPI;
	$ac = $self->request->ac ? $self->request->ac : '';
	$ids = $self->request->ids ? $self->request->ids : '';
	$t = $self->request->t ? $self->request->t : '';
	$pg = $self->request->pg ? $self->request->pg : '';
	$wd = $self->request->wd ? $self->request->wd : '';
	if ($cms_api) {
		$json = \network\http\get("{$cms_api}?ac={$ac}&ids={$ids}&t={$t}&pg={$pg}&wd={$wd}");
		$res = json_decode($json, TRUE);
		if ($res['code'] === 1) {
			$self->response->throwJson([
				"code" => 1,
				"data" => $res,
			]);
		} else {
			$self->response->throwJson([
				"code" => 0,
				"data" => "抓取失败！请联系作者！"
			]);
		}
	} else {
		$self->response->throwJson([
			"code" => 0,
			"data" => "后台苹果CMS API未填写！"
		]);
	}
}

/* 获取虎牙视频列表 已测试 √ */
function _getHuyaList($self)
{
	$self->response->setStatus(200);

	$gameId = $self->request->gameId;
	$page = $self->request->page;
	$json = \network\http\get("https://www.huya.com/cache.php?m=LiveList&do=getLiveListByPage&gameId={$gameId}&tagAll=0&page={$page}");
	$res = json_decode($json, TRUE);
	if ($res['status'] === 200) {
		$self->response->throwJson([
			"code" => 1,
			"data" => $res['data'],
		]);
	} else {
		$self->response->throwJson([
			"code" => 0,
			"data" => "抓取失败！请联系作者！"
		]);
	}
}

/* 获取服务器状态 */
function _getServerStatus($self)
{
	$self->response->setStatus(200);

	$api_panel = Helper::options()->JBTPanel;
	$api_sk = Helper::options()->JBTKey;
	if (!$api_panel) return $self->response->throwJson([
		"code" => 0,
		"data" => "宝塔面板地址未填写！"
	]);
	if (!$api_sk) return $self->response->throwJson([
		"code" => 0,
		"data" => "宝塔接口密钥未填写！"
	]);
	$request_time = time();
	$request_token = md5($request_time . '' . md5($api_sk));
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $api_panel . '/system?action=GetNetWork');
	curl_setopt($ch, CURLOPT_NOSIGNAL, 1);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, 3000);
	curl_setopt($ch, CURLOPT_TIMEOUT_MS, 3000);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS,  array("request_time" => $request_time, "request_token" => $request_token));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	$response  = json_decode(curl_exec($ch), true);
	curl_close($ch);
	$self->response->throwJson(array(
		/* 状态 */
		"status" => $response ? true : false,
		/* 信息提示 */
		"message" => $response['msg'] ?? '',
		/* 上行流量KB */
		"up" => $response["up"] ? $response["up"] : 0,
		/* 下行流量KB */
		"down" => $response["down"] ? $response["down"] : 0,
		/* 总发送（字节数） */
		"upTotal" => $response["upTotal"] ? $response["upTotal"] : 0,
		/* 总接收（字节数） */
		"downTotal" => $response["downTotal"] ? $response["downTotal"] : 0,
		/* 内存占用 */
		"memory" => $response["mem"] ? $response["mem"] : ["memBuffers" => 0, "memCached" => 0, "memFree" => 0, "memRealUsed" => 0, "memTotal" => 0],
		/* CPU */
		"cpu" => $response["cpu"] ? $response["cpu"] : [0, 0, [0], 0, 0, 0],
		/* 系统负载 */
		"load" => $response["load"] ? $response["load"] : ["fifteen" => 0, "five" => 0, "limit" => 0, "max" => 0, "one" => 0, "safe" => 0],
	));
}

/* 获取最近评论 */
function _getCommentLately($self)
{
	$self->response->setStatus(200);

	$time = time();
	$num = 7;
	$categories = [];
	$series = [];
	$db = Typecho_Db::get();
	$prefix = $db->getPrefix();
	for ($i = ($num - 1); $i >= 0; $i--) {
		$date = date("Y/m/d", $time - ($i * 24 * 60 * 60));
		$sql = "SELECT coid FROM `{$prefix}comments` WHERE FROM_UNIXTIME(created, '%Y/%m/%d') = '{$date}' limit 100";
		$count = count($db->fetchAll($sql));
		$categories[] = $date;
		$series[] = $count;
	}
	$self->response->throwJson([
		"categories" => $categories,
		"series" => $series,
	]);
}

/* 获取文章归档 */
function _getArticleFiling($self)
{
	$self->response->setStatus(200);

	$page = $self->request->page;
	$pageSize = 8;
	if (!preg_match('/^\d+$/', $page)) return $self->response->throwJson(array("data" => "非法请求！已屏蔽！"));
	if ($page == 0) $page = 1;
	$offset = $pageSize * ($page - 1);
	$time = time();
	$db = Typecho_Db::get();
	$prefix = $db->getPrefix();
	$result = [];
	$sql_version = $db->fetchAll('select VERSION()')[0]['VERSION()'];
	if ($sql_version >= 8) {
		$sql = "SELECT FROM_UNIXTIME(created, '%Y 年 %m 月') as date FROM `{$prefix}contents` WHERE created < {$time} AND (password is NULL or password = '') AND status = 'publish' AND type = 'post' GROUP BY FROM_UNIXTIME(created, '%Y 年 %m 月') LIMIT {$pageSize} OFFSET {$offset}";
	} else {
		$sql = "SELECT FROM_UNIXTIME(created, '%Y 年 %m 月') as date FROM `{$prefix}contents` WHERE created < {$time} AND (password is NULL or password = '') AND status = 'publish' AND type = 'post' GROUP BY FROM_UNIXTIME(created, '%Y 年 %m 月') DESC LIMIT {$pageSize} OFFSET {$offset}";
	}
	$temp = $db->fetchAll($sql);
	$options = Typecho_Widget::widget('Widget_Options');
	foreach ($temp as $item) {
		$date = $item['date'];
		$list = [];
		$sql = "SELECT * FROM `{$prefix}contents` WHERE created < {$time} AND (password is NULL or password = '') AND status = 'publish' AND type = 'post' AND FROM_UNIXTIME(created, '%Y 年 %m 月') = '{$date}' ORDER BY created DESC LIMIT 100";
		$_list = $db->fetchAll($sql);
		foreach ($_list as $_item) {
			$type = $_item['type'];
			$_item['categories'] = $db->fetchAll($db->select()->from('table.metas')
				->join('table.relationships', 'table.relationships.mid = table.metas.mid')
				->where('table.relationships.cid = ?', $_item['cid'])
				->where('table.metas.type = ?', 'category')
				->order('table.metas.order', Typecho_Db::SORT_ASC));
			$_item['category'] = urlencode(current(Typecho_Common::arrayFlatten($_item['categories'], 'slug')));
			$_item['slug'] = urlencode($_item['slug']);
			$_item['date'] = new Typecho_Date($_item['created']);
			$_item['year'] = $_item['date']->year;
			$_item['month'] = $_item['date']->month;
			$_item['day'] = $_item['date']->day;
			$routeExists = (NULL != Typecho_Router::get($type));
			$_item['pathinfo'] = $routeExists ? Typecho_Router::url($type, $_item) : '#';
			$_item['permalink'] = Typecho_Common::url($_item['pathinfo'], $options->index);
			$list[] = array(
				"title" => date('m/d', $_item['created']) . '：' . $_item['title'],
				"permalink" => $_item['permalink'],
			);
		}
		$result[] = array("date" => $date, "list" => $list);
	}
	$self->response->throwJson($result);
}

// 提交友情链接
function _friendSubmit($self)
{
	$self->response->setStatus(200);

	$captcha = $self->request->captcha;
	if (empty($captcha)) {
		$self->response->throwJson([
			'code' => 0,
			'msg' => '请输入验证码！'
		]);
	}
	if (empty($_SESSION['joe_captcha'])) {
		$self->response->throwJson([
			'code' => 0,
			'msg' => '验证码过期，请重新获取验证码'
		]);
	}
	if ($_SESSION['joe_captcha'] != $captcha) {
		unset($_SESSION['joe_captcha']);
		$self->response->throwJson([
			'code' => 0,
			'msg' => '验证码错误'
		]);
	}
	unset($_SESSION['joe_captcha']);

	$title = $self->request->title;
	$description = $self->request->description;
	$link = $self->request->link;
	$logo = $self->request->logo;
	$qq = $self->request->qq;
	if (empty($title) || empty($link) || empty($qq)) {
		$self->response->throwJson([
			'code' => 0,
			'msg' => '必填项不能为空'
		]);
	}
	if (empty($logo)) {
		$logo = 'http://q4.qlogo.cn/headimg_dl?dst_uin=' . $qq . '&spec=640';
	}

	$db = Typecho_Db::get();
	$sql = $db->insert('table.friends')->rows(
		array(
			'title' => $title,
			'url' =>  $link,
			'logo' =>  $logo,
			'description' =>  $description
		)
	);
	if ($db->query($sql)) {
		$EmailTitle = '友链申请';
		$subtitle = $title . '向您提交了友链申请：';
		$content = "<p>友链标题：$title</p>
		<p>站点链接：$link</p>
		<p>站点LOGO：$logo</p>
		<p>站点描述：$description</p>
		<p>对方QQ号：$qq</p>";
		$SendEmail = joe\send_email($EmailTitle, $subtitle, $content);
		$self->response->throwJson([
			'code' => 200,
			'msg' => '提交成功，管理员会在24小时内进行审核，请耐心等待'
		]);
	} else {
		$self->response->throwJson([
			'code' => 0,
			'msg' => '提交失败，请联系本站点管理员进行处理'
		]);
		// $self->response->throwJson([
		// 	'code' => 0,
		// 	'msg' => '提交失败，错误原因：' . $SendEmail
		// ]);
	}
}

function _Meting($self)
{
	$extension = ['bcmath', 'curl', 'openssl'];
	foreach ($extension as  $value) {
		if (!extension_loaded($value)) {
			$self->response->setStatus(404);
			$self->response->throwJson([
				'code' => 0,
				'msg' => '请开启PHP的' . $value . '扩展！'
			]);
		}
	}
	if (empty($_REQUEST['server']) || empty($_REQUEST['type']) || empty($_REQUEST['id'])) {
		$self->response->setStatus(404);
	}
	$api = new Meting($_REQUEST['server']);
	$type = $_REQUEST['type'];
	if ($type == 'playlist') {
		$data = $api->format(true)->cookie(Helper::options()->JMusicCookie)->playlist($_REQUEST['id']);
		$data = json_decode($data, true);
		foreach ($data as $key => $value) {
			unset($data[$key]);
			$data[$key]['author'] = is_array($value['artist']) ? implode(' / ', $value['artist']) : $value['artist'];
			$data[$key]['title'] = $value['name'];
			$base_url = (Helper::options()->rewrite == 0 ? Helper::options()->rootUrl . '/index.php/joe/api/' : Helper::options()->rootUrl . '/joe/api') . '/meting';
			$data[$key]['url'] = $base_url . '?server=' . $_REQUEST['server'] . '&type=url&id=' . $value['url_id'];
			$data[$key]['pic'] = $base_url . '?server=' . $_REQUEST['server'] . '&type=pic&size=1000&id=' . $value['pic_id'];
			$data[$key]['lrc'] = $base_url . '?server=' . $_REQUEST['server'] . '&type=lrc&id=' . $value['lyric_id'];
		}
		$self->response->setStatus(200);
		$self->response->throwJson($data);
	}
	if ($type == 'url') {
		$data = json_decode($api->format(true)->cookie(Helper::options()->JMusicCookie)->url($_REQUEST['id']), true);
		$url = $data['url'];
		$self->response->setStatus(302);
		header("Location: $url");
		exit;
	}
	if ($type == 'pic') {
		$data = json_decode($api->format(true)->cookie(Helper::options()->JMusicCookie)->pic($_REQUEST['id'], ($_REQUEST['size'] ?? 300)), true);
		$url = $data['url'];
		$self->response->setStatus(302);
		header("Location: $url");
		exit;
	}
	if ($type == 'lrc') {
		$data = json_decode($api->format(true)->cookie(Helper::options()->JMusicCookie)->lyric($_REQUEST['id']), true);
		// 计算180天后的日期
		$expireTime = gmdate('D, d M Y H:i:s', time() + (180 * 24 * 60 * 60)) . ' GMT';
		// 设置缓存控制头部
		$self->response->setStatus(200);
		header("Cache-Control: max-age=" . (180 * 24 * 60 * 60) . ", public");
		header("Expires: $expireTime");
		header("Content-Type: text/plain; charset=utf-8");
		if (empty($data['tlyric'])) {
			echo $data['lyric'];
		} else {
			echo $data['tlyric'];
		}
		exit;
	}
	if ($type == 'song') {
		$data = $api->format(true)->cookie(Helper::options()->JMusicCookie)->song($_REQUEST['id']);
		$data = array_shift(json_decode($data, true));
		$data['author'] = is_array($data['artist']) ? implode(' / ', $data['artist']) : $data['artist'];
		$data['title'] = $data['name'];
		$base_url = (Helper::options()->rewrite == 0 ? Helper::options()->rootUrl . '/index.php/joe/api/' : Helper::options()->rootUrl . '/joe/api') . '/meting';
		$data['url'] = $base_url . '?server=' . $_REQUEST['server'] . '&type=url&id=' . $data['url_id'];
		$data['pic'] = $base_url . '?server=' . $_REQUEST['server'] . '&type=pic&id=' . $data['pic_id'];
		$data['lrc'] = $base_url . '?server=' . $_REQUEST['server'] . '&type=lrc&id=' . $data['lyric_id'];
		$self->response->setStatus(200);
		$self->response->throwJson([$data]);
	}
}

function _payCashierModal($self)
{
	if (!is_numeric($self->request->cid)) {
		$self->response->setStatus(404);
		return;
	}
	$self->response->setStatus(200);

	if (empty(Helper::options()->JYiPayApi)) {
		$self->response->throwJson(['code' => 503, 'message' => '未配置易支付接口！']);
		return;
	}
	if (empty(Helper::options()->JYiPayID)) {
		$self->response->throwJson(['code' => 503, 'message' => '未配置易支付商户号！']);
		return;
	}
	if (empty(Helper::options()->JYiPayKey)) {
		$self->response->throwJson(['code' => 503, 'message' => '未配置易支付商户密钥！']);
		return;
	}

	if (Helper::options()->JWeChatPay != 'on' && Helper::options()->JAlipayPay != 'on' && Helper::options()->JQQPay != 'on') {
		$self->response->throwJson(['code' => 503, 'message' => '暂无可用的支付方式!']);
		return;
	}

	$cid = trim($self->request->cid);

	$self->widget('Widget_Contents_Post@' . $cid, 'cid=' . $cid)->to($item);
	$item->next();

	$pay_price = $item->fields->pay_price;

	if (!is_numeric($pay_price) || round($pay_price, 2) <= 0) {
		$self->response->throwJson(['code' => 503, 'message' => '金额设置错误！']);
		return;
	}

	$pay_price = round($pay_price, 2);
?>
	<div class="modal-colorful-header colorful-bg jb-blue">
		<button class="close" data-dismiss="modal">
			<svg class="ic-close svg" aria-hidden="true">
				<use xlink:href="#icon-close"></use>
			</svg>
		</button>
		<div class="colorful-make"></div>
		<div class="text-center">
			<div class="em2x">
				<i class="fa fa-cart-plus" style="margin-left: -6px;"></i>
			</div>
			<?= is_numeric(USER_ID) ? '<div class="mt10 em12 padding-w10">确认购买</div>' : '<div class="mt10 padding-w10">您当前未登录！建议登陆后购买，可保存购买订单</div>' ?>
		</div>
	</div>
	<div class="mb10 order-type-1">
		<span class="pay-tag badg badg-sm mr6">
			<i class="fa fa-book mr3"></i>
			付费阅读
		</span>
		<span><?= $item->title ?></span>
	</div>
	<div class="mb10 muted-box padding-h6 line-16">
		<div class="flex jsb ab">
			<span class="muted-2-color">价格</span>
			<div>
				<span>
					<span class="pay-mark px12">￥</span>
					<span><?= $pay_price ?></span>
					<!-- <span class="em14">0.01</span> -->
				</span>
			</div>
		</div>
	</div>
	<form>
		<input type="hidden" name="cid" value="<?= $item->cid ?>">
		<input type="hidden" name="order_type" value="1">
		<input type="hidden" name="order_name" value="<?= Helper::options()->title ?> - 付费阅读">
		<div class="dependency-box">
			<div class="muted-2-color em09 mb6">请选择支付方式</div>
			<div class="flex mb10">
				<?php
				if (Helper::options()->JWeChatPay == 'on') {
				?>
					<div class="flex jc hh payment-method-radio hollow-radio flex-auto pointer" data-for="payment_method" data-value="wxpay">
						<img src="<?= joe\theme_url('assets/images/pay/pay-wechat-logo.svg', false) ?>" alt="wechat-logo">
						<div>微信</div>
					</div>
				<?php
				}
				if (Helper::options()->JAlipayPay == 'on') {
				?>
					<div class="flex jc hh payment-method-radio hollow-radio flex-auto pointer" data-for="payment_method" data-value="alipay">
						<img src="<?= joe\theme_url('assets/images/pay/pay-alipay-logo.svg', false) ?>" alt="alipay-logo">
						<div>支付宝</div>
					</div>
				<?php
				}
				if (Helper::options()->JQQPay == 'on') {
				?>
					<div class="flex jc hh payment-method-radio hollow-radio flex-auto pointer" data-for="payment_method" data-value="qqpay">
						<img src="<?= joe\theme_url('assets/images/pay/pay-qq-logo.svg', false) ?>" alt="wechat-logo">
						<div>QQ</div>
					</div>
				<?php
				}
				?>
				<!-- <div class="flex jc hh payment-method-radio hollow-radio flex-auto pointer" data-for="payment_method" data-value="balance">
					<img src="<?= joe\theme_url('assets/images/pay/pay-balance-logo.svg', false) ?>" alt="balance-logo">
					<div>余额</div>
				</div> -->
			</div>
			<input type="hidden" name="payment_method" value="">
			<script>
				document.querySelector('.payment-method-radio').click()
			</script>
			<button class="mt6 but jb-red initiate-pay btn-block radius">
				立即支付
				<span class="pay-price-text">
					<span class="px12 ml10">￥</span>
					<span class="actual-price-number" data-price="<?= $pay_price ?>"><?= $pay_price ?></span>
				</span>
			</button>
		</div>
	</form>
	<script src="<?= joe\theme_url('assets/js/joe.pay.js'); ?>"></script>
<?php
	$self->response->throwContent('');
}

function _initiatePay($self)
{
	if (!is_numeric($self->request->cid)) {
		$self->response->setStatus(404);
		return;
	}

	$cid = trim($self->request->cid);

	$epay_config = [];

	if (empty(Helper::options()->JYiPayApi)) {
		$self->response->throwJson(['code' => 503, 'message' => '未配置易支付接口！']);
		return;
	}
	$epay_config['apiurl'] = trim(Helper::options()->JYiPayApi);

	if (empty(Helper::options()->JYiPayID)) {
		$self->response->throwJson(['code' => 503, 'message' => '未配置易支付商户号！']);
		return;
	}
	$epay_config['partner'] = trim(Helper::options()->JYiPayID);

	if (empty(Helper::options()->JYiPayKey)) {
		$self->response->throwJson(['code' => 503, 'message' => '未配置易支付商户密钥！']);
		return;
	}
	$epay_config['key'] = trim(Helper::options()->JYiPayKey);

	if (!empty(Helper::options()->JYiPayMapiUrl)) {
		$epay_config['mapi_url'] = trim(Helper::options()->JYiPayMapiUrl);
	}

	$self->widget('Widget_Contents_Post@' . $cid, 'cid=' . $cid)->to($item);
	$item->next();
	$pay_price = $item->fields->pay_price;
	if (!is_numeric($pay_price) || round($pay_price, 2) <= 0) {
		$self->response->throwJson(['code' => 503, 'message' => '金额设置错误！']);
		return;
	}
	$pay_price = round($pay_price, 2);
	$out_trade_no = date("YmdHis") . mt_rand(100, 999);
	//构造要请求的参数数组，无需改动
	$parameter = array(
		'pid' => $epay_config['partner'],
		"type" => $self->request->payment_method,
		"notify_url" => Helper::options()->themeUrl . '/library/pay/callback.php',
		"return_url" => Helper::options()->themeUrl . '/library/pay/callback.php?redirect_url=' . urlencode($self->request->return_url),
		"out_trade_no" => $out_trade_no,
		"name" =>  Helper::options()->title . ' - 付费阅读',
		"money"	=> $pay_price,
		'sitename' => Helper::options()->title,
	);

	//建立请求
	require_once JOE_ROOT . 'library/pay/EpayCore.php';
	$epay = new \Joe\library\pay\EpayCore($epay_config);
	$clientip = $self->request->getIp();

	$self->response->setStatus(200);

	$db = Typecho_Db::get();
	$sql = $db->insert('table.joe_pay')->rows([
		'trade_no' => $out_trade_no,
		"name" =>  Helper::options()->title . ' - 付费阅读',
		'content_title' => $item->title,
		'content_cid' => $cid,
		'type' => $self->request->payment_method,
		'money' => $pay_price,
		'ip' => $clientip,
		'user_id' => USER_ID
	]);

	if ($db->query($sql)) {
		if (Helper::options()->JYiPayMapi == 'on') {
			$parameter['clientip'] = $clientip;
			$data = $epay->apiPay($parameter);
			if ($data['code'] == 1) {
				if (empty($data['trade_no'])) {
					$self->response->throwJson(['code' => 500, 'msg' => '获取支付接口订单号失败！']);
				} else {
					// 更新订单状态
					$order_update_sql = $db->update('table.joe_pay')->rows([
						'api_trade_no' =>  $data['trade_no'],
					])->where('trade_no = ?', $out_trade_no);
					if ($db->query($order_update_sql)) {
						$result = [
							'check_sdk' => 'epay',
							'code' => 1,
							'ip_address' => $clientip,
							'msg' => '创建订单成功',
							'order_name' => Helper::options()->title . ' - 付费阅读',
							'trade_no' => $out_trade_no,
							'order_price' => $data['price'],
							'payment_method' => $self->request->payment_method,
							'price' => $pay_price,
							'return_url' => Helper::options()->themeUrl . '/library/pay/callback.php',
							'api_trade_no' => $data['trade_no'],
							'user_id' => USER_ID,
						];
						if (!empty($data['qrcode'])) {
							$result['qrcode'] = $data['qrcode'];
							$result['url_qrcode'] = Helper::options()->themeUrl . '/library/qrcode.php?text=' . urlencode($data['qrcode']);
						}
						if (!empty($data['payurl'])) {
							$result['open_url'] = true;
							$result['url'] = $data['payurl'];
						}
						$self->response->throwJson($result);
					} else {
						$self->response->throwJson(['code' => 500, 'msg' => '更新支付接口订单号失败！']);
					}
				}
			} else {
				$self->response->throwJson(['code' => 500, 'msg' => $data['msg']]);
			}
		} else {
			$html_text = $epay->pagePay($parameter);
			$self->response->throwJson(['code' => 200, 'form_html' => $html_text]);
		}
	} else {
		$self->response->throwJson(['code' => 500, 'msg' => '订单创建失败！']);
	}
}

function _checkPay($self)
{
	if (!is_numeric($self->request->trade_no)) {
		$self->response->setStatus(404);
		return;
	}

	$self->response->setStatus(200);

	$trade_no = trim($self->request->trade_no);

	$epay_config = [];

	if (empty(Helper::options()->JYiPayApi)) {
		$self->response->throwJson(['code' => 503, 'message' => '未配置易支付接口！']);
		return;
	}
	$epay_config['apiurl'] = trim(Helper::options()->JYiPayApi);

	if (empty(Helper::options()->JYiPayID)) {
		$self->response->throwJson(['code' => 503, 'message' => '未配置易支付商户号！']);
		return;
	}
	$epay_config['partner'] = trim(Helper::options()->JYiPayID);

	if (empty(Helper::options()->JYiPayKey)) {
		$self->response->throwJson(['code' => 503, 'message' => '未配置易支付商户密钥！']);
		return;
	}
	$epay_config['key'] = trim(Helper::options()->JYiPayKey);

	if (!empty(Helper::options()->JYiPayMapiUrl)) {
		$epay_config['mapi_url'] = trim(Helper::options()->JYiPayMapiUrl);
	}

	$db = Typecho_Db::get();
	$row = $db->fetchRow($db->select()->from('table.joe_pay')->where('trade_no = ?', $trade_no)->limit(1));
	if (sizeof($row) > 0) {
		//建立请求
		require_once JOE_ROOT . 'library/pay/EpayCore.php';
		$epay = new \Joe\library\pay\EpayCore($epay_config);
		$data = $epay->queryOrder($trade_no, $row['api_trade_no']);
		$status = isset($data['status']) ? $data['status'] : 0;
		$msg = empty($data['msg']) ? '支付失败，订单失效！' : $data['msg'];
		$self->response->throwJson(['status' => $status, 'msg' => $msg]);
	} else {
		$self->response->throwJson(['code' => 500, 'msg' => '订单不存在！']);
	}
}

function _userRewardsModal($self)
{
	$self->response->setStatus(200);
?>
	<style>
		.rewards-img {
			height: 140px;
			width: 140px;
			border-radius: var(--main-radius);
			overflow: hidden;
			margin: auto;
		}
	</style>
	<div class="modal-colorful-header colorful-bg jb-blue">
		<button class="close" data-dismiss="modal">
			<svg class="ic-close svg" aria-hidden="true">
				<use xlink:href="#icon-close"></use>
			</svg>
		</button>
		<div class="colorful-make"></div>
		<div class="text-center">
			<div class="em2x">
				<svg class="em12 svg" aria-hidden="true">
					<use xlink:href="#icon-money"></use>
				</svg>
			</div>
			<div class="mt10 em12 padding-w10"><?= Helper::options()->JRewardTitle ?></div>
		</div>
	</div>
	<ul class="flex jse mb10 text-center rewards-box">
		<?php
		if (!empty(Helper::options()->JWeChatRewardImg)) {
		?>
			<li>
				<p class="muted-2-color" style="margin-bottom: 10px;">微信扫一扫</p>
				<div class="rewards-img">
					<img class="fit-cover" src="<?= Helper::options()->JWeChatRewardImg ?>">
				</div>
			</li>
		<?php
		}
		if (!empty(Helper::options()->JAlipayRewardImg)) {
		?>
			<li>
				<p class="muted-2-color" style="margin-bottom: 10px;">支付宝扫一扫</p>
				<div class="rewards-img">
					<img class="fit-cover" src="<?= Helper::options()->JAlipayRewardImg ?>">
				</div>
			</li>
		<?php
		}
		if (!empty(Helper::options()->JQQRewardImg)) {
		?>
			<li>
				<p class="muted-2-color" style="margin-bottom: 10px;">QQ扫一扫</p>
				<div class="rewards-img">
					<img class="fit-cover" src="<?= Helper::options()->JQQRewardImg ?>">
				</div>
			</li>
		<?php
		}
		?>
	</ul>
<?php
	$self->response->throwContent('');
}

function _payDelete($self)
{
	if (!$GLOBALS['JOE_USER']->hasLogin() || $GLOBALS['JOE_USER']->group != 'administrator') {
		$self->response->setStatus(404);
		return;
	}
	$self->response->setStatus(200);
	$db = Typecho_Db::get();
	$id = isset($_POST['id']) ? $_POST['id'] : [];
	if (!is_array($id)) $id = [];
	$sql = $db->delete('table.joe_pay')->where('id in?', $id);
	if ($db->query($sql)) {
		if (isset($_SERVER['HTTP_REFERER'])) {
			header('Location: ' . $_SERVER['HTTP_REFERER'], true, 302);
			exit;
		} else {
			header('Location: /admin/extending.php?panel=..%2Fthemes%2FJoe%2Fadmin%2Forders.php', true, 302);
			exit;
		}
	}
}

<?php
if (!(defined('IN_IA'))) {
	exit('Access Denied');
}
class Member_EweiShopV2Model {

	private $members;
	//保存二级分享所有团队人员

	//会员等级升级方法
	//参数$money是订单金额,参数$levelnum是当前等级
	public function levelupdate($money, $levelnum) {
		global $_W;
		$member = $this -> getInfo($_W['openid']);
		$levels = array( array('name' => '银卡会员', 'm1' => 500, 'm2' => 1000), array('name' => '金卡会员', 'm1' => 1000, 'm2' => 5000), array('name' => '铂金卡会员', 'm1' => 5000, 'm2' => 10000), array('name' => '钻石卡会员', 'm1' => 10000, 'm2' => 100000000));
		$templevelnum = $levelnum;
		foreach ($levels as $key => $value) {
			if (!($levelnum >= ($key + 1))) {
				if ($money >= $value['m1'] && $money <= $value['m2']) {
					$templevelnum = $key + 1;
				}
			}
		}
		//升级
		if ($templevelnum != $levelnum && $member['levelid']<$templevelnum ) {
			pdo_update('ewei_shop_member',array('levelid'=>$templevelnum),array('openid'=>$_W['openid']));
			$cnum=$templevelnum-1;
			$content=$member['realname'].'成功升级为'.$levels[$cnum]['name'];
			m('util') -> insertnotice($content,$content);			
		}

	}

	/*
	 * 计算某用户个人业绩
	 * $type=1,2分别为上个月.本月
	 */
	public function GetPersonAchievement($type, $openid) {
		if ($type == 1) {
			$StartTime = strtotime(date('Y-m-1 00:00:00', strtotime('-1 month')));
			$EndTime = strtotime(date('Y-m-t 24:00:00', strtotime('-1 month')));
		} else {
			$StartTime = strtotime(date('Y-m-1 00:00:00'));
			$EndTime = strtotime(date('Y-m-t 24:00:00'));
		}
		$Orders = pdo_fetchall("select *  from " . tablename('ewei_shop_order') . " where status=3 and createtime>=" . $StartTime . " and createtime<=" . $EndTime . " and openid='" . $openid . "'");
		$TempM = 0;
		foreach ($Orders as $value) {
			$TempM += $value['ordergoodsprice'];
		}
		unset($orders);
		return $TempM;
	}

	/*
	 * 计算某用户团队业绩
	 * $type=1,2分别为上个月.本月
	 */
	public function GetTeamAchievement($type, $openid) {
		if ($type == 1) {
			$StartTime = strtotime(date('Y-m-1 00:00:00', strtotime('-1 month')));
			$EndTime = strtotime(date('Y-m-t 24:00:00', strtotime('-1 month')));
		} else {
			$StartTime = strtotime(date('Y-m-1 00:00:00'));
			$EndTime = strtotime(date('Y-m-t 24:00:00'));
		}
		$orders = $this -> GetTeamOrder($openid, $StartTime, $EndTime);
		$TempM = 0;
		foreach ($orders as $value) {
			$TempM += $value['ordergoodsprice'];
		}
		unset($orders);
		return $TempM;
	}

	/*
	 * 计算某个用户无限下级.
	 * 返回的数据并不包含用户自身.
	 */
	public function GetTeamMember($agentid) {
		$data = pdo_fetchall("SELECT id,agentid,openid FROM " . tablename('ewei_shop_member'));
		$result = array();
		$fids = array($agentid);
		do {
			$cids = array();
			$flag = false;
			foreach ($fids as $fid) {
				for ($i = count($data) - 1; $i >= 0; $i--) {
					$node = $data[$i];
					if ($node['agentid'] == $fid) {
						array_splice($data, $i, 1);
						$result[] = $node;
						$cids[] = $node['id'];
						$flag = true;
					}
				}
			}
			$fids = $cids;
		} while($flag === true);
		$this -> members = array_merge($this -> members, $result);
	}

	/*
	 * 计算某个用户无限下级.
	 * 返回的数据并不包含用户自身.
	 */
	public function GetTeamMember1($mid) {
		$Tempmerbers = pdo_fetchall("select id,openid from " . tablename('ewei_shop_member') . " where agentid=" . $mid);

		if (count($Tempmerbers) > 0) {
			$this -> members = array_merge($this -> members, $Tempmerbers);
			foreach ($Tempmerbers as $value) {
				$this -> GetTeamMember($value['id']);
			}
		}

	}

	//获取某个会员团队的指定时间段的已完成订单
	public function GetTeamOrder($openid = '', $StartTime, $EndTime) {
		$member = $this -> getInfo($openid);
		$Members = array();
		$Orders = array();
		$this -> members = array();
		//初始化保存member的属性
		//获取全部成员
		$this -> GetTeamMember($member['id']);
		$Members = $this -> members;
		//生成opendis(我的下线openis)
		$openids = array();

		foreach ($Members as $key => $value) {
			array_push($openids, "'" . $value['openid'] . "'");
		}
		array_push($openids, "'" . $member['openid'] . "'");
		$openids = implode(",", $openids);

		$Orders = pdo_fetchall("select ordergoodsprice  from " . tablename('ewei_shop_order') . " where status=3 and createtime>=" . $StartTime . " and createtime<=" . $EndTime . " and openid in(" . $openids . ")");
		return $Orders;
	}

	//会员注册生成算法
	//参数1是用户上级id
	//只用检查,当前上级是否满足升级条件.满足升级
	public function checkupgroup($mid) {
		global $_W;
		global $_GPC;
		//获取全部等级
		$AllGroup = pdo_fetchall('select id,groupname,upnum  from ' . tablename('ewei_shop_member_group') . ' where id<6');
		//获取上级用户的基本信息
		$member = m('member') -> getMember($mid);
		if (!$member) {
			return;
		}
		//获取上级用户拥有的下级(同级)
		$lwmember = pdo_getall('ewei_shop_member', array('groupid' => intval($member['groupid']), 'agentid' => $mid));
		$lowernums = count($lwmember);
		//小于高级副总裁
		if ($member['groupid'] < 5 && $lowernums > 0) {
			//可以升级
			if ($lowernums >= $AllGroup[$member['groupid']]['upnum']) {
				pdo_update('ewei_shop_member', array('groupid' => $AllGroup[$member['groupid']]['id']), array('id' => $mid));
				//升级后通知用户.
				$data = array('first' => array('value' => '恭喜您成功升级为' . $AllGroup[$member['groupid']]['groupname'], 'color' => '#4a5077'), 'keyword' => array('title' => '日期', 'value' => date('Y-m-d H:i:s'), 'color' => '#4a5077'));
				$url = $_W['siteroot'] . 'app/index.php?i=' . $_W['uniacid'] . '&c=entry&m=ewei_shopv2&do=mobile&r=member';
				m('message') -> sendCustomNotice($member['openid'], $data, $url);
				//自己升级可能导致上级升级
				if ($member['agentid'] != 0) {
					$this -> checkupgroup($member['agentid']);
				} else {
					return;
				}
			}
		} else {
			return;
		}
		unset($member, $lwmember, $AllGroup);
	}

	//计算对应的等级应该返佣的比例
	//参数1,1是一级,2是二级
	//参数2是订单金额
	public function CalculateDiscount($ComType = 0, $OrderPrice = 0) {
		if ($ComType == 0 || $OrderPrice == 0) {
			return 0;
		}
		$data = array();
		switch ($ComType) {
			case 1 :
				$data = array('type' => 1);
				break;
			case 2 :
				$data = array('type' => 2);
				break;
		}
		$AllList = pdo_getall('ewei_shop_commission_discount', $data);
		$TempRate = 0;
		foreach ($AllList as $key => $value) {
			if ($OrderPrice >= $value['start1'] && $OrderPrice < $value['start2']) {
				$TempRate = $value['rate'] / 100;
			}
		}
		return $TempRate;
	}

	//获取会员享受的折扣和折扣id
	public function getMemberDiscount($openid = '') {
		$data = array('discountid' => 0, 'discount' => 0);
		$member = $this -> getInfo($openid);
		if (!$member) {
			return $data;
		}
		$dis = pdo_get('ewei_shop_member_discount', array('id' => $member['discountid']));
		if (!$dis) {
			return $data;
		} else {
			if ($dis['id'] == '') {
				return $data;
			} else {
				$data['discountid'] = $dis['id'];
				$data['discount'] = $dis['discount'] / 100;
				return $data;
			}
		}
	}

	//获取当前会员是否第一次下单
	public function GetIsOnce($openid = '') {
		global $_W;
		global $_GPC;

		//最优先系统
		$wapset = m('common') -> getSysset('shop');
		if ($wapset['issysbi'] == 2) {
			return false;
		}

		$OneOrder = pdo_get('ewei_shop_order', array('openid' => $openid, 'status' => 3));

		//优先分享权限
		$m = pdo_get('ewei_shop_member', array('openid' => $openid));
		if ($m['ismember']) {
			return false;
		}

		//下单模式
		if ($_GPC['id'] != '') {
			$goods = pdo_get('ewei_shop_goods', array('id' => intval($_GPC['id'])));
			if ($goods['isbi'] == 1) {
				return false;
			} else if ($OneOrder) {
				return false;
			} else {
				return true;
			}
		}
		//购物车模式
		else {

			//检查购物车是否包含必买商品,并且select=1
			$allcart = pdo_getall('ewei_shop_member_cart', array('openid' => $openid, 'selected' => 1));
			//var_dump($allcart);
			foreach ($allcart as $key => $value) {
				$oneres = pdo_get('ewei_shop_goods', array('id' => $value['goodsid']));
				if ($oneres['isbi'] == 1 && $_GPC['r'] == 'order.create' && $_GPC['id'] == "") {
					return false;
				}
			}
			if ($OneOrder) {
				return false;
			} else {
				return true;
			}
		}

	}

	//返回我的团队需要的HTML
	public function GetTeamInfo($openid = '') {
		$data = array('Speed' => 0, 'EndNum' => 0, 'Info' => '', 'text' => '');
		$M = $this -> getInfo($openid);
		$AllGroup = pdo_fetchall("select *  from " . tablename('ewei_shop_member_group') . " where id <7");

		//如果是高级副总裁提前处理
		if ($M['groupid'] >= 5) {
			$retmoney = $this -> GetOrderYear($openid);
			$data['Speed'] = number_format($retmoney / 2000000 * 100, 2);
			$data['EndNum'] = $retmoney;
			$data['Info'] = '200万';
			$data['text'] = '年度业绩200万，申请公司高级副总裁';
		} else {
			foreach ($AllGroup as $key => $value) {
				if ($value['id'] == $M['groupid']) {
					$agints = pdo_getall('ewei_shop_member', array('agentid' => $M['id'], 'groupid' => $M['groupid']));
					$data['Speed'] = number_format(count($agints) / $AllGroup[$key + 1]['upnum'] * 100, 2);
					$data['EndNum'] = count($agints);
					$data['Info'] = $AllGroup[$key + 1]['upnum'] . '人';
					$data['text'] = '分享推荐' . $AllGroup[$key + 1]['upnum'] . '个' . $AllGroup[$key]['groupname'] . '升级' . $AllGroup[$key + 1]['groupname'];
					break;
				}
			}
		}

		//Others

		return $data;

	}

	public function getInfo($openid = '') {
		global $_W;
		$uid = intval($openid);
		if ($uid == 0) {
			$info = pdo_fetch('select * from ' . tablename('ewei_shop_member') . ' where openid=:openid and uniacid=:uniacid limit 1', array(':uniacid' => $_W['uniacid'], ':openid' => $openid));
		} else {
			$info = pdo_fetch('select * from ' . tablename('ewei_shop_member') . ' where id=:id  and uniacid=:uniacid limit 1', array(':uniacid' => $_W['uniacid'], ':id' => $uid));
		}
		if (!(empty($info['uid']))) {
			load() -> model('mc');
			$uid = mc_openid2uid($info['openid']);
			$fans = mc_fetch($uid, array('credit1', 'credit2', 'birthyear', 'birthmonth', 'birthday', 'gender', 'avatar', 'resideprovince', 'residecity', 'nickname'));
			$info['credit1'] = $fans['credit1'];
			$info['credit2'] = $fans['credit2'];
			$info['birthyear'] = ((empty($info['birthyear']) ? $fans['birthyear'] : $info['birthyear']));
			$info['birthmonth'] = ((empty($info['birthmonth']) ? $fans['birthmonth'] : $info['birthmonth']));
			$info['birthday'] = ((empty($info['birthday']) ? $fans['birthday'] : $info['birthday']));
			$info['nickname'] = ((empty($info['nickname']) ? $fans['nickname'] : $info['nickname']));
			$info['gender'] = ((empty($info['gender']) ? $fans['gender'] : $info['gender']));
			$info['sex'] = $info['gender'];
			$info['avatar'] = ((empty($info['avatar']) ? $fans['avatar'] : $info['avatar']));
			$info['headimgurl'] = $info['avatar'];
			$info['province'] = ((empty($info['province']) ? $fans['resideprovince'] : $info['province']));
			$info['city'] = ((empty($info['city']) ? $fans['residecity'] : $info['city']));
		}
		if (!(empty($info['birthyear'])) && !(empty($info['birthmonth'])) && !(empty($info['birthday']))) {
			$info['birthday'] = $info['birthyear'] . '-' . ((strlen($info['birthmonth']) <= 1 ? '0' . $info['birthmonth'] : $info['birthmonth'])) . '-' . ((strlen($info['birthday']) <= 1 ? '0' . $info['birthday'] : $info['birthday']));
		}
		if (empty($info['birthday'])) {
			$info['birthday'] = '';
		}
		if (!(empty($info))) {
			if (!(strexists($info['avatar'], 'http://')) && !(strexists($info['avatar'], 'https://'))) {
				$info['avatar'] = tomedia($info['avatar']);
			}
			if ($_W['ishttps']) {
				$info['avatar'] = str_replace('http://', 'https://', $info['avatar']);
			}
		}
		return $info;
	}

	//检查会员是否已经注册过
	public function checklogin($openid) {
		$member = $this -> getMember($openid, true);
		if ($member['mobile'] == '') {
			$url = mobileUrl('member/reg');
			header("Location: " . $url);
			exit();
		}
	}

	//返回会员的从本年开始到现在的团队的业绩
	public function GetOrderYear($openid = '') {
		$first = strtotime(date("Y", time()) . "-01-01");
		$end = time();

		$Money = 0;
		//要返回的参数,个人团队业绩

		//1.获取当前会员的团队信息
		$member = m('member') -> getMember($openid, true);
		$TeamMember = array();
		$OneTeam = pdo_fetchall("select openid from " . tablename('ewei_shop_member') . ' where agentid=' . $member['id']);
		if (count($OneTeam) > 0) {$TeamMember = array_merge($TeamMember, $OneTeam);
		}
		$TwoTeam = pdo_fetchall("select openid from  " . tablename('ewei_shop_member') . " where agentid  in (" . "select id from " . tablename('ewei_shop_member') . " where  agentid=" . $member['id'] . ")");
		if (count($TwoTeam) > 0) {$TeamMember = array_merge($TeamMember, $TwoTeam);
		}
		array_push($TeamMember, array('openid' => $openid));

		//2.循环出openids,方便查询订单
		$openids = array();
		for ($i = 0; $i < count($TeamMember); $i++) {
			array_push($openids, '"' . $TeamMember[$i]['openid'] . '"');
		}
		$openids = implode(",", $openids);
		//3.获取我的团队的订单
		$orders = pdo_fetchall("select id,ordergoodsprice,ordergoodspayprice from " . tablename('ewei_shop_order') . " where status=3 and openid in(" . $openids . ")" . " and createtime>=" . $first . " and createtime<=" . $end);

		//4.通过全部订单计算商品价格(分享商城的).业绩
		if (count($orders) > 0) {

			foreach ($orders as $value) {
				if ($value['ordergoodsprice'] == $value['ordergoodspayprice']) {
					$Money += $value['ordergoodsprice'];
				} else {
					$Money += $value['ordergoodspayprice'];
				}
			}
			//金额超过
			if ($Money > 2000000 && $member['groupid'] == 5) {
				pdo_update('ewei_shop_member', array('groupid' => 6), array('openid' => $openid));
			}

			return $Money;
		} else {
			return $Money;
		}
	}

	public function getMember($openid = '') {
		global $_W;
		$uid = (int)$openid;
		if ($uid == 0) {
			$info = pdo_fetch('select * from ' . tablename('ewei_shop_member') . ' where  openid=:openid and uniacid=:uniacid limit 1', array(':uniacid' => $_W['uniacid'], ':openid' => $openid));
			if (empty($info)) {
				if (strexists($openid, 'sns_qq_')) {
					$openid = str_replace('sns_qq_', '', $openid);
					$condition = ' openid_qq=:openid ';
					$bindsns = 'qq';
				} else if (strexists($openid, 'sns_wx_')) {
					$openid = str_replace('sns_wx_', '', $openid);
					$condition = ' openid_wx=:openid ';
					$bindsns = 'wx';
				} else if (strexists($openid, 'sns_wa_')) {
					$openid = str_replace('sns_wa_', '', $openid);
					$condition = ' openid_wa=:openid ';
					$bindsns = 'wa';
				}
				if (!(empty($condition))) {
					$info = pdo_fetch('select * from ' . tablename('ewei_shop_member') . ' where ' . $condition . '  and uniacid=:uniacid limit 1', array(':uniacid' => $_W['uniacid'], ':openid' => $openid));
					if (!(empty($info))) {
						$info['bindsns'] = $bindsns;
					}
				}
			}
		} else {
			$info = pdo_fetch('select * from ' . tablename('ewei_shop_member') . ' where id=:id and uniacid=:uniacid limit 1', array(':uniacid' => $_W['uniacid'], ':id' => $openid));
		}
		if (!(empty($info))) {
			if (!(strexists($info['avatar'], 'http://')) && !(strexists($info['avatar'], 'https://'))) {
				$info['avatar'] = tomedia($info['avatar']);
			}
			if ($_W['ishttps']) {
				$info['avatar'] = str_replace('http://', 'https://', $info['avatar']);
			}
			$info = $this -> updateCredits($info);
		}
		return $info;
	}

	public function updateCredits($info) {
		global $_W;
		$openid = $info['openid'];
		if (empty($info['uid'])) {
			$followed = m('user') -> followed($openid);
			if ($followed) {
				load() -> model('mc');
				$uid = mc_openid2uid($openid);
				if (!(empty($uid))) {
					$info['uid'] = $uid;
					$upgrade = array('uid' => $uid);
					if (0 < $info['credit1']) {
						mc_credit_update($uid, 'credit1', $info['credit1']);
						$upgrade['credit1'] = 0;
					}
					if (0 < $info['credit2']) {
						mc_credit_update($uid, 'credit2', $info['credit2']);
						$upgrade['credit2'] = 0;
					}
					if (!(empty($upgrade))) {
						pdo_update('ewei_shop_member', $upgrade, array('id' => $info['id']));
					}
				}
			}
		}
		$credits = $this -> getCredits($openid);
		$info['credit1'] = $credits['credit1'];
		$info['credit2'] = $credits['credit2'];
		return $info;
	}

	public function getMobileMember($mobile) {
		global $_W;
		$info = pdo_fetch('select * from ' . tablename('ewei_shop_member') . ' where mobile=:mobile and uniacid=:uniacid limit 1', array(':uniacid' => $_W['uniacid'], ':mobile' => $mobile));
		if (!(empty($info))) {
			$info = $this -> updateCredits($info);
		}
		return $info;
	}

	public function getMid() {
		global $_W;
		$openid = $_W['openid'];
		$member = $this -> getMember($openid);
		return $member['id'];
	}

	public function setCredit($openid = '', $credittype = 'credit1', $credits = 0, $log = array()) {
		global $_W;
		load() -> model('mc');
		$uid = mc_openid2uid($openid);
		if (empty($log)) {
			$log = array($uid, '未记录');
		} else if (!(is_array($log))) {
			$log = array(0, $log);
		}
		if (($credittype == 'credit1') && empty($log[0]) && (0 < $credits)) {
			$shopset = m('common') -> getSysset('trade');
			$member = $this -> getMember($openid);
			if (empty($member['diymaxcredit'])) {
				if (0 < $shopset['maxcredit']) {
					if ($shopset['maxcredit'] <= $member['credit1']) {
						return error(-1, '用户积分已达上限');
					}
					if ($shopset['maxcredit'] < ($member['credit1'] + $credits)) {
						$credits = $shopset['maxcredit'] - $member['credit1'];
					}
				}
			} else if (0 < $member['maxcredit']) {
				if ($member['maxcredit'] <= $member['credit1']) {
					return error(-1, '用户积分已达上限');
				}
				if ($member['maxcredit'] < ($member['credit1'] + $credits)) {
					$credits = $member['maxcredit'] - $member['credit1'];
				}
			}
		}
		if (!(empty($uid))) {
			$value = pdo_fetchcolumn('SELECT ' . $credittype . ' FROM ' . tablename('mc_members') . ' WHERE `uid` = :uid', array(':uid' => $uid));
			$newcredit = $credits + $value;
			if ($newcredit <= 0) {
				$newcredit = 0;
			}
			pdo_update('mc_members', array($credittype => $newcredit), array('uid' => $uid));
			if (empty($log)) {
				$log = array($uid, '未记录');
			} else if (!(is_array($log))) {
				$log = array(0, $log);
			}
			$data = array('uid' => $uid, 'credittype' => $credittype, 'uniacid' => $_W['uniacid'], 'num' => $credits, 'createtime' => TIMESTAMP, 'module' => 'ewei_shopv2', 'operator' => intval($log[0]), 'remark' => $log[1]);
			pdo_insert('mc_credits_record', $data);
		} else {
			$value = pdo_fetchcolumn('SELECT ' . $credittype . ' FROM ' . tablename('ewei_shop_member') . ' WHERE  uniacid=:uniacid and openid=:openid limit 1', array(':uniacid' => $_W['uniacid'], ':openid' => $openid));
			$newcredit = $credits + $value;
			if ($newcredit <= 0) {
				$newcredit = 0;
			}
			pdo_update('ewei_shop_member', array($credittype => $newcredit), array('uniacid' => $_W['uniacid'], 'openid' => $openid));
		}
	}

	public function getCredit($openid = '', $credittype = 'credit1') {
		global $_W;
		load() -> model('mc');
		$uid = mc_openid2uid($openid);
		if (!(empty($uid))) {
			return pdo_fetchcolumn('SELECT ' . $credittype . ' FROM ' . tablename('mc_members') . ' WHERE `uid` = :uid', array(':uid' => $uid));
		}
		return pdo_fetchcolumn('SELECT ' . $credittype . ' FROM ' . tablename('ewei_shop_member') . ' WHERE  openid=:openid and uniacid=:uniacid limit 1', array(':uniacid' => $_W['uniacid'], ':openid' => $openid));
	}

	public function getCredits($openid = '', $credittypes = array('credit1', 'credit2')) {
		global $_W;
		load() -> model('mc');
		$uid = mc_openid2uid($openid);
		$types = implode(',', $credittypes);
		if (!(empty($uid))) {
			return pdo_fetch('SELECT ' . $types . ' FROM ' . tablename('mc_members') . ' WHERE `uid` = :uid limit 1', array(':uid' => $uid));
		}
		return pdo_fetch('SELECT ' . $types . ' FROM ' . tablename('ewei_shop_member') . ' WHERE  openid=:openid and uniacid=:uniacid limit 1', array(':uniacid' => $_W['uniacid'], ':openid' => $openid));
	}

	public function checkMember() {
		global $_W;
		global $_GPC;
		$member = array();
		$shopset = m('common') -> getSysset(array('shop', 'wap'));
		$openid = $_W['openid'];
		if (($_W['routes'] == 'order.pay_alipay') || ($_W['routes'] == 'creditshop.log.dispatch_complete') || ($_W['routes'] == 'threen.register.threen_complete') || ($_W['routes'] == 'creditshop.detail.creditshop_complete') || ($_W['routes'] == 'order.pay_alipay.recharge_complete') || ($_W['routes'] == 'order.pay_alipay.complete') || ($_W['routes'] == 'newmr.alipay') || ($_W['routes'] == 'newmr.callback.gprs') || ($_W['routes'] == 'newmr.callback.bill') || ($_W['routes'] == 'account.sns') || ($_W['plugin'] == 'mmanage')) {
			return;
		}
		if ($shopset['wap']['open']) {
			if (($shopset['wap']['inh5app'] && is_h5app()) || (empty($shopset['wap']['inh5app']) && empty($openid))) {
				return;
			}
		}
		$redis = redis();
		if (!(is_error($redis))) {
			$member = $redis -> get($_W['openid']);
			if (!(empty($member))) {
				return array('openid' => $_W['openid']);
			}
		}
		if (empty($openid) && !(EWEI_SHOPV2_DEBUG)) {
			$diemsg = ((is_h5app() ? 'APP正在维护, 请到公众号中访问' : '请在微信客户端打开链接'));
			exit('<!DOCTYPE html>' . "\r\n" . '                <html>' . "\r\n" . '                    <head>' . "\r\n" . '                        <meta name=\'viewport\' content=\'width=device-width, initial-scale=1, user-scalable=0\'>' . "\r\n" . '                        <title>抱歉，出错了</title><meta charset=\'utf-8\'><meta name=\'viewport\' content=\'width=device-width, initial-scale=1, user-scalable=0\'><link rel=\'stylesheet\' type=\'text/css\' href=\'https://res.wx.qq.com/connect/zh_CN/htmledition/style/wap_err1a9853.css\'>' . "\r\n" . '                    </head>' . "\r\n" . '                    <body>' . "\r\n" . '                    <div class=\'page_msg\'><div class=\'inner\'><span class=\'msg_icon_wrp\'><i class=\'icon80_smile\'></i></span><div class=\'msg_content\'><h4>' . $diemsg . '</h4></div></div></div>' . "\r\n" . '                    </body>' . "\r\n" . '                </html>');
		}
		$member = $this -> getMember($openid);
		$followed = m('user') -> followed($openid);
		$uid = 0;
		$mc = array();
		load() -> model('mc');
		if ($followed || empty($shopset['shop']['getinfo']) || ($shopset['shop']['getinfo'] == 1)) {
			$uid = mc_openid2uid($openid);
			if (!(EWEI_SHOPV2_DEBUG)) {
				$userinfo = mc_oauth_userinfo();
			} else {
				$userinfo = array('openid' => $member['openid'], 'nickname' => $member['nickname'], 'headimgurl' => $member['avatar'], 'gender' => $member['gender'], 'province' => $member['province'], 'city' => $member['city']);
			}
			$mc = array();
			$mc['nickname'] = $userinfo['nickname'];
			$mc['avatar'] = $userinfo['headimgurl'];
			$mc['gender'] = $userinfo['sex'];
			$mc['resideprovince'] = $userinfo['province'];
			$mc['residecity'] = $userinfo['city'];
		}
		if (!(is_error($redis))) {
			$redis -> set($_W['openid'], 1, 10);
		}
		if (empty($member) && !(empty($openid))) {
			$member = array('uniacid' => $_W['uniacid'], 'uid' => $uid, 'openid' => $openid, 'realname' => (!(empty($mc['realname'])) ? $mc['realname'] : ''), 'mobile' => (!(empty($mc['mobile'])) ? $mc['mobile'] : ''), 'nickname' => (!(empty($mc['nickname'])) ? $mc['nickname'] : ''), 'nickname_wechat' => (!(empty($mc['nickname'])) ? $mc['nickname'] : ''), 'avatar' => (!(empty($mc['avatar'])) ? $mc['avatar'] : ''), 'avatar_wechat' => (!(empty($mc['avatar'])) ? $mc['avatar'] : ''), 'gender' => (!(empty($mc['gender'])) ? $mc['gender'] : '-1'), 'province' => (!(empty($mc['resideprovince'])) ? $mc['resideprovince'] : ''), 'city' => (!(empty($mc['residecity'])) ? $mc['residecity'] : ''), 'area' => (!(empty($mc['residedist'])) ? $mc['residedist'] : ''), 'createtime' => time(), 'status' => 0);
			pdo_insert('ewei_shop_member', $member);
			$member['id'] = pdo_insertid();
		} else {
			if ($member['isblack'] == 1) {
				show_message('暂时无法访问，请稍后再试!');
			}
			$upgrade = array('uid' => $uid);
			if (isset($mc['nickname']) && ($member['nickname_wechat'] != $mc['nickname'])) {
				$upgrade['nickname_wechat'] = $mc['nickname'];
			}
			if (isset($mc['nickname']) && empty($member['nickname'])) {
				$upgrade['nickname'] = $mc['nickname'];
			}
			if (isset($mc['avatar']) && ($member['avatar_wechat'] != $mc['avatar'])) {
				$upgrade['avatar_wechat'] = $mc['avatar'];
			}
			if (isset($mc['avatar']) && empty($member['avatar'])) {
				$upgrade['avatar'] = $mc['avatar'];
			}
			if (isset($mc['gender']) && ($member['gender'] != $mc['gender'])) {
				$upgrade['gender'] = $mc['gender'];
			}
			if (!(empty($upgrade))) {
				pdo_update('ewei_shop_member', $upgrade, array('id' => $member['id']));
			}
		}
		if (p('commission')) {
			p('commission') -> checkAgent($openid);
		}
		if (p('poster')) {
			p('poster') -> checkScan($openid);
		}
		if (empty($member)) {
			return false;
		}
		return array('id' => $member['id'], 'openid' => $member['openid']);
	}

	public function getLevels($all = true) {
		global $_W;
		$condition = '';
		if (!($all)) {
			$condition = ' and enabled=1';
		}
		return pdo_fetchall('select * from ' . tablename('ewei_shop_member_level') . ' where uniacid=:uniacid' . $condition . ' order by level asc', array(':uniacid' => $_W['uniacid']));
	}

	public function getLevel($openid) {
		global $_W;
		global $_S;
		if (empty($openid)) {
			return false;
		}
		$member = m('member') -> getMember($openid);
		if (!(empty($member)) && !(empty($member['level']))) {
			$level = pdo_fetch('select * from ' . tablename('ewei_shop_member_level') . ' where id=:id and uniacid=:uniacid limit 1', array(':id' => $member['level'], ':uniacid' => $_W['uniacid']));
			if (!(empty($level))) {
				return $level;
			}
		}
		return array('levelname' => (empty($_S['shop']['levelname']) ? '普通会员' : $_S['shop']['levelname']), 'discount' => (empty($_S['shop']['leveldiscount']) ? 10 : $_S['shop']['leveldiscount']));
	}

	public function getOneGoodsLevel($openid, $goodsid) {
		global $_W;
		$uniacid = $_W['uniacid'];
		$level_info = $this -> getLevel($openid);
		$level = intval($level_info['level']);
		$data = array();
		$levels = pdo_fetchall('select * from ' . tablename('ewei_shop_member_level') . ' where uniacid=:uniacid and buygoods=1 and level and level > :level order by level asc', array(':uniacid' => $uniacid, ':level' => $level));
		if (!(empty($levels))) {
			foreach ($levels as $k => $v) {
				$goodsids = iunserializer($v['goodsids']);
				if (!(empty($goodsids))) {
					if (in_array($goodsid, $goodsids)) {
						$data = $v;
					}
				}
			}
		}
		return $data;
	}

	public function getGoodsLevel($openid, $orderid) {
		global $_W;
		$uniacid = $_W['uniacid'];
		$order_goods = pdo_fetchall('select goodsid from ' . tablename('ewei_shop_order_goods') . ' where orderid=:orderid and uniacid=:uniacid', array(':uniacid' => $uniacid, ':orderid' => $orderid));
		$levels = array();
		$data = array();
		if (!(empty($order_goods))) {
			foreach ($order_goods as $k => $v) {
				$item = $this -> getOneGoodsLevel($openid, $v['goodsid']);
				if (!(empty($item))) {
					$levels[$item['level']] = $item;
				}
			}
		}
		if (!(empty($levels))) {
			$level = max(array_keys($levels));
			$data = $levels[$level];
		}
		return $data;
	}

	public function upgradeLevel($openid, $orderid = 0) {
		global $_W;
		if (empty($openid)) {
			return;
		}
		$shopset = m('common') -> getSysset('shop');
		$leveltype = intval($shopset['leveltype']);
		$member = m('member') -> getMember($openid);
		if (empty($member)) {
			return;
		}
		$level = false;
		if (empty($leveltype)) {
			$ordermoney = pdo_fetchcolumn('select ifnull( sum(og.realprice),0) from ' . tablename('ewei_shop_order_goods') . ' og ' . ' left join ' . tablename('ewei_shop_order') . ' o on o.id=og.orderid ' . ' where o.openid=:openid and o.status=3 and o.uniacid=:uniacid ', array(':uniacid' => $_W['uniacid'], ':openid' => $member['openid']));
			$level = pdo_fetch('select * from ' . tablename('ewei_shop_member_level') . ' where uniacid=:uniacid  and enabled=1 and ' . $ordermoney . ' >= ordermoney and ordermoney>0  order by level desc limit 1', array(':uniacid' => $_W['uniacid']));
		} else if ($leveltype == 1) {
			$ordercount = pdo_fetchcolumn('select count(*) from ' . tablename('ewei_shop_order') . ' where openid=:openid and status=3 and uniacid=:uniacid ', array(':uniacid' => $_W['uniacid'], ':openid' => $member['openid']));
			$level = pdo_fetch('select * from ' . tablename('ewei_shop_member_level') . ' where uniacid=:uniacid and enabled=1 and ' . $ordercount . ' >= ordercount and ordercount>0  order by level desc limit 1', array(':uniacid' => $_W['uniacid']));
		}
		if (!(empty($orderid))) {
			$goods_level = $this -> getGoodsLevel($openid, $orderid);
			if (empty($level)) {
				$level = $goods_level;
			} else if (!(empty($goods_level))) {
				if ($level['level'] < $goods_level['level']) {
					$level = $goods_level;
				}
			}
		}
		if (empty($level)) {
			return;
		}
		if ($level['id'] == $member['level']) {
			return;
		}
		$oldlevel = $this -> getLevel($openid);
		$canupgrade = false;
		if (empty($oldlevel['id'])) {
			$canupgrade = true;
		} else if ($oldlevel['level'] < $level['level']) {
			$canupgrade = true;
		}
		if ($canupgrade) {
			pdo_update('ewei_shop_member', array('level' => $level['id']), array('id' => $member['id']));
			m('notice') -> sendMemberUpgradeMessage($openid, $oldlevel, $level);
		}
	}

	public function getGroups() {
		global $_W;
		return pdo_fetchall('select * from ' . tablename('ewei_shop_member_group') . ' where uniacid=:uniacid order by id asc', array(':uniacid' => $_W['uniacid']));
	}

	public function getGroup($openid) {
		if (empty($openid)) {
			return false;
		}
		$member = m('member') -> getMember($openid);
		return $member['groupid'];
	}

	public function setRechargeCredit($openid = '', $money = 0) {
		if (empty($openid)) {
			return;
		}
		global $_W;
		$credit = 0;
		$set = m('common') -> getSysset(array('trade', 'shop'));
		if ($set['trade']) {
			$tmoney = floatval($set['trade']['money']);
			if (!(empty($tmoney))) {
				$tcredit = intval($set['trade']['credit']);
				if ($tmoney <= $money) {
					if (($money % $tmoney) == 0) {
						$credit = intval($money / $tmoney) * $tcredit;
					} else {
						$credit = (intval($money / $tmoney) + 1) * $tcredit;
					}
				}
			}
		}
		if (0 < $credit) {
			$this -> setCredit($openid, 'credit1', $credit, array(0, $set['shop']['name'] . '会员充值积分:credit2:' . $credit));
		}
	}

	public function getCalculateMoney($money, $set_array) {
		$charge = $set_array['charge'];
		$begin = $set_array['begin'];
		$end = $set_array['end'];
		$array = array();
		$array['deductionmoney'] = round(($money * $charge) / 100, 2);
		if (($begin <= $array['deductionmoney']) && ($array['deductionmoney'] <= $end)) {
			$array['deductionmoney'] = 0;
		}
		$array['realmoney'] = round($money - $array['deductionmoney'], 2);
		if ($money == $array['realmoney']) {
			$array['flag'] = 0;
		} else {
			$array['flag'] = 1;
		}
		return $array;
	}

	public function checkMemberFromPlatform($openid = '') {
		global $_W;
		$acc = WeiXinAccount::create($_W['acid']);
		$userinfo = $acc -> fansQueryInfo($openid);
		$userinfo['avatar'] = $userinfo['headimgurl'];
		load() -> model('mc');
		$uid = mc_openid2uid($openid);
		if (!(empty($uid))) {
			pdo_update('mc_members', array('nickname' => $userinfo['nickname'], 'gender' => $userinfo['sex'], 'nationality' => $userinfo['country'], 'resideprovince' => $userinfo['province'], 'residecity' => $userinfo['city'], 'avatar' => $userinfo['headimgurl']), array('uid' => $uid));
		}
		pdo_update('mc_mapping_fans', array('nickname' => $userinfo['nickname']), array('uniacid' => $_W['uniacid'], 'openid' => $openid));
		$member = $this -> getMember($openid);
		if (empty($member)) {
			$mc = mc_fetch($uid, array('realname', 'nickname', 'mobile', 'avatar', 'resideprovince', 'residecity', 'residedist'));
			$member = array('uniacid' => $_W['uniacid'], 'uid' => $uid, 'openid' => $openid, 'realname' => $mc['realname'], 'mobile' => $mc['mobile'], 'nickname' => (!(empty($mc['nickname'])) ? $mc['nickname'] : $userinfo['nickname']), 'avatar' => (!(empty($mc['avatar'])) ? $mc['avatar'] : $userinfo['avatar']), 'gender' => (!(empty($mc['gender'])) ? $mc['gender'] : $userinfo['sex']), 'province' => (!(empty($mc['resideprovince'])) ? $mc['resideprovince'] : $userinfo['province']), 'city' => (!(empty($mc['residecity'])) ? $mc['residecity'] : $userinfo['city']), 'area' => $mc['residedist'], 'createtime' => time(), 'status' => 0);
			pdo_insert('ewei_shop_member', $member);
			$member['id'] = pdo_insertid();
			$member['isnew'] = true;
		} else {
			$member['nickname'] = $userinfo['nickname'];
			$member['avatar'] = $userinfo['headimgurl'];
			$member['province'] = $userinfo['province'];
			$member['city'] = $userinfo['city'];
			pdo_update('ewei_shop_member', $member, array('id' => $member['id']));
			$member['isnew'] = false;
		}
		return $member;
	}

	public function mc_update($mid, $data) {
		global $_W;
		if (empty($mid) || empty($data)) {
			return;
		}
		$wapset = m('common') -> getSysset('wap');
		$member = $this -> getMember($mid);
		if (!(empty($wapset['open'])) && isset($data['mobile']) && ($data['mobile'] != $member['mobile'])) {
			unset($data['mobile']);
		}
		load() -> model('mc');
		mc_update($this -> member['uid'], $data);
	}

	public function checkMemberSNS($sns) {
		global $_W;
		global $_GPC;
		if (empty($sns)) {
			$sns = $_GPC['sns'];
		}
		if (empty($sns)) {
			return;
		}
		if (($sns == 'wx') && !(empty($_GPC['token']))) {
			load() -> func('communication');
			$snsurl = 'https://api.weixin.qq.com/sns/userinfo?access_token=' . $_GPC['token'] . '&openid=' . $_GPC['openid'] . '&lang=zh_CN';
			$userinfo = ihttp_request($snsurl);
			$userinfo = json_decode($userinfo['content'], true);
			$userinfo['openid'] = 'sns_wx_' . $userinfo['openid'];
		} else if ($sns == 'qq') {
			$userinfo = htmlspecialchars_decode($_GPC['userinfo']);
			$userinfo = json_decode($userinfo, true);
			$userinfo['openid'] = 'sns_qq_' . $_GPC['openid'];
			$userinfo['headimgurl'] = $userinfo['figureurl_qq_2'];
			$userinfo['gender'] = (($userinfo['gender'] == '男' ? 1 : 2));
		}
		$data = array('nickname' => $userinfo['nickname'], 'avatar' => $userinfo['headimgurl'], 'province' => $userinfo['province'], 'city' => $userinfo['city'], 'gender' => $userinfo['sex'], 'comefrom' => 'h5app_sns_' . $sns);
		$openid = trim($_GPC['openid']);
		if ($sns == 'qq') {
			$data['openid_qq'] = trim($_GPC['openid']);
			$openid = 'sns_qq_' . trim($_GPC['openid']);
		}
		if ($sns == 'wx') {
			$data['openid_wx'] = trim($_GPC['openid']);
			$openid = 'sns_wx_' . trim($_GPC['openid']);
		}
		$member = $this -> getMember($openid);
		if (empty($member)) {
			$data['openid'] = $userinfo['openid'];
			$data['uniacid'] = $_W['uniacid'];
			$data['comefrom'] = 'sns_' . $sns;
			$data['createtime'] = time();
			$data['salt'] = m('account') -> getSalt();
			$data['pwd'] = rand(10000, 99999) . $data['salt'];
			pdo_insert('ewei_shop_member', $data);
		} else {
			if (empty($member['bindsns']) || ($member['bindsns'] == $sns)) {
				pdo_update('ewei_shop_member', $data, array('id' => $member['id'], 'uniacid' => $_W['uniacid']));
			}
		}
	}

	public function compareLevel(array $level, array $levels = array()) {
		global $_W;
		$levels = ((!(empty($levels)) ? $levels : $this -> getLevels()));
		$old_key = -1;
		$new_key = -1;
		foreach ($levels as $kk => $vv) {
			if ($vv['id'] == $level[0]) {
				$old_key = $vv['level'];
			}
			if ($vv['id'] == $level[1]) {
				$new_key = $vv['level'];
			}
		}
		return $old_key < $new_key;
	}

	public function wxuser($appid, $secret, $snsapi = 'snsapi_base', $expired = '600') {
		global $_W;
		if ($wxuser = $_COOKIE[$_W['config']['cookie']['pre'] . $appid] === NULL) {
			$code = ((isset($_GET['code']) ? $_GET['code'] : ''));
			if (!($code)) {
				$url = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
				$oauth_url = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid=' . $appid . '&redirect_uri=' . urlencode($url) . '&response_type=code&scope=' . $snsapi . '&state=wxbase#wechat_redirect';
				header('Location: ' . $oauth_url);
				exit();
			}
			load() -> func('communication');
			$getOauthAccessToken = ihttp_get('https://api.weixin.qq.com/sns/oauth2/access_token?appid=' . $appid . '&secret=' . $secret . '&code=' . $code . '&grant_type=authorization_code');
			$json = json_decode($getOauthAccessToken['content'], true);
			if (!(empty($json['errcode'])) && ($json['errcode'] != '40029')) {
				return $json['errmsg'];
			}
			if (!(empty($json['errcode'])) && ($json['errcode'] == '40029')) {
				$url = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . ((strpos($_SERVER['REQUEST_URI'], '?') ? '' : '?'));
				$parse = parse_url($url);
				if (isset($parse['query'])) {
					parse_str($parse['query'], $params);
					unset($params['code']);
					unset($params['state']);
					$url = 'http://' . $_SERVER['HTTP_HOST'] . $parse['path'] . '?' . http_build_query($params);
				}
				$oauth_url = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid=' . $appid . '&redirect_uri=' . urlencode($url) . '&response_type=code&scope=' . $snsapi . '&state=wxbase#wechat_redirect';
				header('Location: ' . $oauth_url);
				exit();
			}
			if ($snsapi == 'snsapi_userinfo') {
				$userinfo = ihttp_get('https://api.weixin.qq.com/sns/userinfo?access_token=' . $json['access_token'] . '&openid=' . $json['openid'] . '&lang=zh_CN');
				$userinfo = $userinfo['content'];
			} else if ($snsapi == 'snsapi_base') {
				$userinfo = array();
				$userinfo['openid'] = $json['openid'];
			}
			$userinfostr = json_encode($userinfo);
			isetcookie($appid, $userinfostr, $expired);
			return $userinfo;
		}
		return json_decode($wxuser, true);
	}

}
?>
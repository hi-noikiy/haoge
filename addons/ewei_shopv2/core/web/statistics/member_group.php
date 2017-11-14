<?php

if (!defined('IN_IA')) {
	exit('Access Denied');
}



class Member_group_EweiShopV2Page extends WebPage
{
	

	public function main()
	{
		global $_W;
		global $_GPC;
		
		
		//生成报表.为每个用户生成报表信息.保存上个月的业绩信息
		$members=pdo_fetchall("SELECT id,agentid,openid FROM ".tablename('ewei_shop_member').' where id<>2242');

		
		foreach ($members as $key => $value) {
			$data['m1']=m('member')->GetTeamAchievement(1,$value['openid']);//上月团队业绩
			$data['m2']=m('member')->GetPersonAchievement(1,$value['openid']);//上月个人业绩
			$data['m3']=m('member')->GetTeamAchievement(2,$value['openid']);//本月团队业绩
			$data['m4']=m('member')->GetPersonAchievement(2,$value['openid']);//本月个人业绩	
			pdo_update('ewei_shop_member',$data,array('openid'=>$value['openid']));					
		}

		$allgrouplist = pdo_fetchall('SELECT * FROM ' . tablename('ewei_shop_member_group'));

		
		$condition = ' and o.uniacid=' . $_W['uniacid'];
		$pindex = max(1, intval($_GPC['page']));
		$psize = 20;
		$params = array();
		$shop = m('common')->getSysset('shop');

		if (!empty($_GPC['datetime'])) {
			$starttime = strtotime($_GPC['datetime']['start']);
			$endtime = strtotime($_GPC['datetime']['end']);
			$condition .= ' AND o.createtime >=' . $starttime . ' AND o.createtime <= ' . $endtime . ' ';
		}


		$condition1 = ' and m.uniacid=:uniacid';
		
		if(!empty($_GPC['from'])){
			$condition1.=' and m.groupid='.intval($_GPC['from']);
		}
		
		$params1 = array(':uniacid' => $_W['uniacid']);

		if (!empty($_GPC['keyword'])) {
			$_GPC['keyword'] = trim($_GPC['keyword']);
			$condition1 .= ' and ( m.realname like :keyword or m.mobile like :keyword or m.nickname like :keyword)';
			$params1[':keyword'] = '%' . $_GPC['keyword'] . '%';
		}


		$orderby = ((empty($_GPC['orderby']) ? 'ordermoney' : 'ordercount'));
		
		if(empty($_GPC['orderby'])){
			$orderby='m1';
		}
		else
		{
			$orderby=$_GPC['orderby'];	
		}	
		
		
		$sql = 'SELECT m.realname,m.groupid,m.m1,m.m2,m.m3,m.m4 ,m.mobile,m.avatar,m.nickname,m.openid,l.levelname,' . '(select ifnull( count(o.id) ,0) from  ' . tablename('ewei_shop_order') . ' o where o.openid=m.openid and o.status>=1 ' . $condition . ')  as ordercount,' . '(select ifnull(sum(o.price),0) from  ' . tablename('ewei_shop_order') . ' o where o.openid=m.openid  and o.status>=1 ' . $condition . ')  as ordermoney' . ' from ' . tablename('ewei_shop_member') . ' m  ' . ' left join ' . tablename('ewei_shop_member_level') . ' l on l.id = m.level' . ' where 1 ' . $condition1 . ' order by ' . $orderby . ' desc';

		if (empty($_GPC['export'])) {
			$sql .= ' LIMIT ' . (($pindex - 1) * $psize) . ',' . $psize;
		}


		$list = pdo_fetchall($sql, $params1);
		$total = pdo_fetchcolumn('select  count(1) from ' . tablename('ewei_shop_member') . ' m ' . ' where 1 ' . $condition1 . ' ', $params1);
		$pager = pagination($total, $pindex, $psize);
		
		
		foreach ($list as $key => $value) {
				$oneres=pdo_get('ewei_shop_member_group',array('id'=>$value['groupid']));
				$list[$key]['groupname']=$oneres['groupname'];
		}
		
		if ($_GPC['export'] == 1) {
			ca('statistics.member_cost.export');
			m('excel')->export($list, array(
	'title'   => '会员消费排行报告-' . date('Y-m-d-H-i', time()),
	'columns' => array(
		array('title' => '昵称', 'field' => 'nickname', 'width' => 12),
		array('title' => '姓名', 'field' => 'realname', 'width' => 12),
		array('title' => '手机号', 'field' => 'mobile', 'width' => 12),
		array('title' => 'openid', 'field' => 'openid', 'width' => 24),
		array('title' => '消费金额', 'field' => 'ordermoney', 'width' => 12),
		array('title' => '订单数', 'field' => 'ordercount', 'width' => 12)
		)
	));
			plog('statistics.member_cost.export', '导出会员消费排行');
		}


		load()->func('tpl');
		include $this->template('statistics/member_group');
	}
}


?>
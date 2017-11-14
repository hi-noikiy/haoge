<?php
if (!(defined('IN_IA'))) 
{
	exit('Access Denied');
}
class Customer_EweiShopV2Page extends WebPage 
{
	public function main() 
	{
		global $_W;
		global $_GPC;
		
		$id=$_GPC['id'];
				
		$oneres=pdo_get('ewei_shop_member', array('id' =>$id));
		
		

		$list=pdo_fetchall('select id,openid,mobile,nickname, realname,createtime,avatar  from ' . tablename('ewei_shop_member') . '  where uniacid=:uniacid and  agentid=:agentid   order by id desc  ', array(':uniacid' => $_W['uniacid'],':agentid' => $id));
		 
		//时间格式化
		foreach ($list as  $key=>$value) {
			$list[$key]['createtime']=date('Y-m-d',$list[$key]['createtime']);
			$list[$key]['TeamM1']=m('member')->GetTeamAchievement(1,$list[$key]['openid']);
			$list[$key]['TeamM2']=m('member')->GetTeamAchievement(2,$list[$key]['openid']);
			
			$list[$key]['PerM1']=m('member')->GetPersonAchievement(1,$list[$key]['openid']);
			$list[$key]['PerM2']=m('member')->GetPersonAchievement(2,$list[$key]['openid']);			
		}		
		
		/*
		$TeamM1 = m('member')->GetTeamAchievement(1,$oneres['openid']);//上月团队业绩
		$TeamM2 = m('member')->GetTeamAchievement(2,$oneres['openid']);//本月团队业绩
		$PerM1 = m('member')->GetPersonAchievement(1,$oneres['openid']);//上月个人业绩
		$PerM2 = m('member')->GetPersonAchievement(2,$oneres['openid']);//本月个人业绩
		*/		
		include $this->template();

	}



}
?>
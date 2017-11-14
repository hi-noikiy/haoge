<?php
if (!(defined('IN_IA'))) 
{
	exit('Access Denied');
}
//ini_set("display_errors", "On");
//error_reporting(E_ALL | E_STRICT);
class User_EweiShopV2Page extends PluginWebPage 
{
	public function main() 
	{
		
		$pindex = max(1, intval($_GPC['page']));
		$psize = 20;
		$list = pdo_fetchall('select * from ' . tablename('ewei_shop_MonthCommission') . '    where 1 ' . $condition . ' order by createtime desc LIMIT ' . (($pindex - 1) * $psize) . ',' . $psize);
		$total = pdo_fetchcolumn('select count(1) from ' . tablename('ewei_shop_MonthCommission') . '   where 1 ' . $condition);
		$pager = pagination($total, $pindex, $psize);
		foreach ($list as $key => $value) {
			$member = m('member')->getMember($value['openid'], true);
			$list[$key]['avatar']=$member['avatar'];
			$list[$key]['nickname']=$member['nickname'];
			$list[$key]['realname']=$member['realname'];
			$list[$key]['userid']=$member['id'];
			$oneres=pdo_get('ewei_shop_member_group',array('id'=>$value['groupid']));
			$list[$key]['groupname']=$oneres['groupname'];	
			if($value['othergroupid']==0){
				$list[$key]['othergroupname']='未完成指标';	
			}
			else{
				$oneres=pdo_get('ewei_shop_member_group',array('id'=>$value['othergroupid']));
				$list[$key]['othergroupname']='完成'.$oneres['groupname'].'的指标';					
			}									
		}
		
		$M1=m('order')->caculateorderprice(1,1);
		$M2=m('order')->caculateorderprice(2,1);
		$M3=m('order')->caculateorderprice(1,2);
		
		$allred=pdo_getall('ewei_shop_MonthCommission');
		foreach ($allred as $value) {
			if($value['red']>0){
				$M3=$M3-$value['red'];
			}
		}
		
		
		load()->func('tpl');
		include $this->template();
	}
	public function create() 
	{
		global $_W;
		global $_GPC;
		
		
		//清空数据库中的数据
		pdo_delete('ewei_shop_MonthCommission');
		
		//我是最终的结果,用来保存到数据
		$insertdata=array();
		
		//1.全平台订单金额
		$Allorderprice=m('order')->caculateorderprice(1,1);
		
		
		//2.获取全平台可以享受分红的高管,并清除业绩为0的人员(高级副总裁除外)
		$Allmember=pdo_fetchall('SELECT id,groupid,openid FROM ' . tablename('ewei_shop_member')." where groupid>2");
		foreach ($Allmember as $key => $value) {
			
			/*
			if($value['groupid']==3 || $value['groupid']==4 || $value['groupid']==5 || $value['groupid']==7 || $value['groupid']==8 ){
				$onememberach=m('member')->GetPersonAchievement(2,$value['openid']);
				if($onememberach<=0){
					unset($Allmember[$key]);
				}
			}
			else{
				//记录过滤后的每个用户的团队业绩,个人业绩
				$Allmember[$key]['personachievement']=m('member')->GetPersonAchievement(2,$value['openid']);
				$Allmember[$key]['teamchievement']=m('member')->GetTeamAchievement(2,$value['openid']);								
			}
			 */ 
				$Allmember[$key]['personachievement']=m('member')->GetPersonAchievement(1,$value['openid']);
				$Allmember[$key]['teamchievement']=m('member')->GetTeamAchievement(1,$value['openid']);					 

		}
		
		$Allmember=array_merge($Allmember);

		//3.所有高管请在3分钟完成归队,业绩不合格的自觉点
		$allgrouplist = pdo_fetchall('SELECT * FROM ' . tablename('ewei_shop_member_group'));
		
			
		foreach ($Allmember as $key => $value) {
				
				$Allmember[$key]['othergroupid']=$value['groupid'];
			
				//3.1.过滤经理,总监,副总裁,股东
				if($value['groupid']==3 || $value['groupid']==4 || $value['groupid']==5 || $value['groupid']==8){
					$othergroupid=$this->checkothergroupid($value['groupid'],$value['teamchievement']);
					if($othergroupid==0){
						unset($Allmember[$key]);
					}
					else{
						$Allmember[$key]['othergroupid']=$othergroupid;
					}
				}
				//3.2过滤高级副总裁
				if($value['groupid']==6){
					$othergroupid=$this->checkothergroupid($value['groupid'],$value['teamchievement']);
					$Allmember[$key]['othergroupid']=$othergroupid;
				}

				//3.3过滤员工
				if($value['groupid']==7){
					$othergroupid=$this->checkothergroupid($value['groupid'],$value['teamchievement']);
					$Allmember[$key]['othergroupid']=$othergroupid;					
				}
		
		}

		//4.计算每个人员的人数(股东最终还是属于高管,员工计算按照真实计算)和分润总额
		$membercount=array(
					'A'=>0,//经理总人数的业绩和
					'B'=>0,//总监总人数的业绩和
					'C'=>0,//副总总人数的业绩和
					'D'=>0,//高级副总裁1总人数的业绩和
					'E'=>0,//高级副总裁2总人数的业绩和
					'F'=>0,//员工的总人数的业绩和
					'G'=>0,//只要是高级副总裁的人数都要统计
					'A1'=>0,//经理共同享受的分红
					'B1'=>0,//总监共同享受的分红
					'C1'=>0,//副总裁共同享受的分红
					'D1'=>0,//高级副总裁1共同享受的分红(5%*30%)
					'E1'=>0,//高级副总裁2共同享受的分红(5%*70%)
					'F1'=>0,//员工共同享受的分红
		);
		//4.1计算每个级别的人员业绩和相加
		foreach ($Allmember as $key => $value) {
				if($value['othergroupid']==3){
					$membercount['A']=$membercount['A']+$value['teamchievement'];
				}
				if($value['othergroupid']==4){
					$membercount['B']=$membercount['B']+$value['teamchievement'];
				}	
				if($value['othergroupid']==5){
					$membercount['C']=$membercount['C']+$value['teamchievement'];
				}	
				if($value['groupid']==6){
					$membercount['D']=$membercount['D']+$value['teamchievement'];
					$membercount['G']++;//高级副总裁只要是就要统计人数,因为30%是需要平分的
				}					
				if($value['othergroupid']==6){
					$membercount['E']=$membercount['E']+$value['teamchievement'];
				}
				if($value['groupid']==7){
					$membercount['F']=$membercount['F']+$value['teamchievement'];
				}																
		}

		
		//4.2.计算每个等级总共会分多少钱
		$membercount['A1']=$Allorderprice*$allgrouplist[2]['rate']/100;
		$membercount['B1']=$Allorderprice*$allgrouplist[3]['rate']/100;
		$membercount['C1']=$Allorderprice*$allgrouplist[4]['rate']/100;
		$membercount['D1']=$Allorderprice*$allgrouplist[5]['rate']/100*0.3;//30%给所有高级副总裁的
		$membercount['E1']=$Allorderprice*$allgrouplist[5]['rate']/100*0.7;//70%给所有高级副总裁分红
		
		//4.3 提前计算利润
		$profit=(m('order')->caculateorderprice(1,2))-($membercount['A1']+$membercount['B1']+$membercount['C1']+$membercount['D1']+$membercount['E1']);
		$profit=$profit*0.92;
		
		
		//5.分红开始
		foreach ($Allmember as $key => $value) {
				//经理分红
				if($value['othergroupid']==3){
					$Allmember[$key]['red']=$membercount['A1']*($value['teamchievement']/$membercount['A']);
				}
				//总监分红
				if($value['othergroupid']==4){
					$Allmember[$key]['red']=$membercount['B1']*($value['teamchievement']/$membercount['B']);
				}
				//副总裁分红	
				if($value['othergroupid']==5){
					$Allmember[$key]['red']=$membercount['C1']*($value['teamchievement']/$membercount['C']);
					$membercount['C']=$membercount['C']+$value['teamchievement'];
				}
				//只要是高级副总裁,都享受30%	
				if($value['groupid']==6){
					$Allmember[$key]['red']=$membercount['D1']/$membercount['G'];
				}
				//完成的高级副总裁70%平坦					
				if($value['othergroupid']==6){
					$Allmember[$key]['red']+=$membercount['E1']*($value['teamchievement']/$membercount['E']);
				}
				//员工分红
				if($value['groupid']==7){
					$Allmember[$key]['red']=$profit*($value['teamchievement']/$membercount['F']);
				}	
				$month=intval(date('m',strtotime("-1 month")));
				$data=array(
					'openid'=>$value['openid'],
					'groupid'=>$value['groupid'],
					'othergroupid'=>$value['othergroupid'],					
					'achievement'=>$value['teamchievement'],
					'red'=>$Allmember[$key]['red'],
					'month'=>$month,
					'createtime'=>time()     
				);

				pdo_insert('ewei_shop_MonthCommission',$data);			
		}	  			
		

		message('生成数据成功!',webUrl('commission/statistics/user'),'success');
	}
	
	
	/*
	 * 计算某个用户的业绩求出othergroupid
	 * $groupid用户的groupid,$ach是用户的团队业绩
	 * 返回为0,所有都没有匹配到.
	 */
	public function checkothergroupid($groupid,$ach) 
	{
		global $_W;
		global $_GPC;
		
		$resid=$groupid;
		$state=1;
		$alllist = pdo_fetchall('SELECT * FROM ' . tablename('ewei_shop_member_group').' where id in(2,3,4,5,6)');		
		rsort($alllist);
		foreach ($alllist as $key => $value) {
			if($value['id']<=$groupid){
				if($ach>=$value['achievement']){
					$resid=$value['id'];
					$state=2;
					break;
				}
			}
		}
		
		if($state==1){
			return 0;
		}
		else{
			return $resid;
		}
	}
	
	
	public function check(){
		global $_W;
		global $_GPC;		
		$month=intval(date('m',strtotime("-1 month")));
		$oneres=pdo_get('ewei_shop_MonthCommission',array('month'=>$month));
		if($oneres){
			show_json(1,'存在');
		}
		else
		{
			show_json(0,'不存在');
		}
		
	}
	
	
	
	public function dealwith() 
	{
		global $_W;
		global $_GPC;
		$id = intval($_GPC['id']);
		if (empty($id)) 
		{
			$id = ((is_array($_GPC['ids']) ? implode(',', $_GPC['ids']) : 0));
		}
		$res = pdo_fetchall('SELECT * FROM ' . tablename('ewei_shop_MonthCommission') . ' WHERE id in( ' . $id . ' )');
		
		foreach ($res as $r ) 
		{
			if($r['status']==1){
				pdo_update('ewei_shop_MonthCommission', array('status' => 2));
				if($r['red']>0){
					pdo_update('ewei_shop_member',array('money +='=>$r['red']),array('openid'=>$r['openid']));
				}			
			}
		}
		show_json(1, array('url' => referer()));
	}	
	public function delete() 
	{
		global $_W;
		global $_GPC;
		$id = intval($_GPC['id']);
		if (empty($id)) 
		{
			$id = ((is_array($_GPC['ids']) ? implode(',', $_GPC['ids']) : 0));
		}
		$res = pdo_fetchall('SELECT * FROM ' . tablename('ewei_shop_MonthCommission') . ' WHERE id in( ' . $id . ' )');
		
		foreach ($res as $r ) 
		{
			pdo_delete('ewei_shop_MonthCommission', array('id' => $r['id']));
		}
		
		
		show_json(1, array('url' => referer()));
	}
}
?>
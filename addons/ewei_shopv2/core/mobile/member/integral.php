<?php
if (!(defined('IN_IA'))) 
{
	exit('Access Denied');
}
class Integral_EweiShopV2Page extends MobileLoginPage 
{
	public function main() 
	{
		global $_W;
		global $_GPC;
		$member = m('member')->getMember($_W['openid'], true);
		include $this->template();
	}
	
	public function get_list() 
	{
		
		global $_W;
		
		global $_GPC;
		
		$type = $_GPC['type'];
		
		$pindex = max(1, intval($_GPC['page']));
		
		$psize = 10;
		
		$member = m('member') -> getMember($_W['openid']);
		
		$condition = ' and credittype=:credittype and module=:module and uniacid=:uniacid and uid=:uid';
		
		$params = array(':uniacid' => $_W['uniacid'], ':credittype' => 'credit1', ':uid' => $member['uid'] , ':module' => 'ewei_shopv2');
		
		$list = array();
		
		$list = pdo_fetchall('select * from ' . tablename('mc_credits_record') . '    where 1 ' . $condition . ' order by createtime desc LIMIT ' . (($pindex - 1) * $psize) . ',' . $psize, $params);
		
		$total = pdo_fetchcolumn('select count(1) from ' . tablename('mc_credits_record') . '   where 1 ' . $condition ,$params );
		
		foreach($list as &$value){
			
			$value['createtime'] = date('Y-m-d H:i' , $value['createtime']);
			$nums=stripos($value['num'],'-');
			if($nums===false){
				$value['num']='+'.$value['num'];
			}
			$value['remark'] = eregi_replace("[0-9]+","",$value['remark']);
			
		}
		
		unset($value);
		
		show_json(1, array('list' => $list, 'total' => $total, 'pagesize' => $psize ));
	}	
	
	
}
?>
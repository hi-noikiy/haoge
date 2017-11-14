<?php
if (!(defined('IN_IA'))) 
{
	exit('Access Denied');
}
require EWEI_SHOPV2_PLUGIN . 'commission/core/page_login_mobile.php';
class Cash_EweiShopV2Page extends CommissionMobileLoginPage 
{
	public function main() 
	{
		global $_W;
		global $_GPC;
		$this->diypage('commission');
		$member = $this->model->getInfo($_W['openid'], array('total', 'ordercount0', 'ok', 'ordercount', 'wait', 'pay'));		
		$cansettle = (1 <= $member['commission_ok']) && (floatval($this->set['withdraw']) <= $member['commission_ok']);
		$agentid = $member['agentid'];
		if (!(empty($agentid))) 
		{
			$data = pdo_fetch('select sum(charge) as sumcharge from ' . tablename('ewei_shop_commission_log') . ' where mid=:mid and uniacid=:uniacid  limit 1', array(':uniacid' => $_W['uniacid'], ':mid' => $agentid));
			$commission_charge = $data['sumcharge'];
			$member['commission_charge'] = $commission_charge;
		}
		else 
		{
			$member['commission_charge'] = 0;
		}
		
		//返利佣金已经体现的金额
		$fcash=0;
		$allfcash=pdo_getall('ewei_shop_member_log',array('othertype'=>1,'type'=>1,'status'=>1));
		foreach ($allfcash as $key => $value) {
			$fcash=$fcash+$value['money'];
		}
		
		//返利申请中的佣金
		$fscash=0;
		$allfscash=pdo_getall('ewei_shop_member_log',array('othertype'=>1,'type'=>1,'status'=>0));
		foreach ($allfcash as $key => $value) {
			$fscash=$fscash+$value['money'];
		}		
		
		
		
		//分红佣金成功体现
		$fredcash=0;
		$allfredcash=pdo_getall('ewei_shop_member_log',array('othertype'=>2,'type'=>1,'status'=>1));
		foreach ($allfredcash as $key => $value) {
			$fredcash=$fredcash+$value['money'];
		}
		
		//分红申请中的佣金
		$fsredcash=0;
		$allfredcash=pdo_getall('ewei_shop_member_log',array('othertype'=>2,'type'=>1,'status'=>0));
		foreach ($allfredcash as $key => $value) {
			$fsredcash=$fsredcash+$value['money'];
		}		
				
				
		include $this->template();
	}
}
?>
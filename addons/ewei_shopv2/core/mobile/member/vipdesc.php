<?php
if (!(defined('IN_IA'))) 
{
	exit('Access Denied');
}
class Vipdesc_EweiShopV2Page extends MobileLoginPage 
{
	public function main() 
	{
		global $_W;
		global $_GPC;
		$wapset = m('common')->getSysset('shop');
		$wapset['vipdesc']=htmlspecialchars_decode($wapset['vipdesc']);
		include $this->template();
	}
}
?>
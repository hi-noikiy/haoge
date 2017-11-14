<?php
if (!(defined('IN_IA'))) 
{
	exit('Access Denied');
}
class Rule_EweiShopV2Page extends MobileLoginPage 
{
	public function main() 
	{
		global $_W;
		global $_GPC;
		$wapset = m('common')->getSysset('shop');
		$wapset['leveldesc']=htmlspecialchars_decode($wapset['leveldesc']);
		include $this->template();
	}
}
?>
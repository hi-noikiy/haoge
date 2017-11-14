<?php
if (!(defined('IN_IA'))) 
{
	exit('Access Denied');
}
class Getcodehelp_EweiShopV2Page extends MobileLoginPage 
{
	public function main() 
	{
		global $_W;
		global $_GPC;
		$wapset = m('common')->getSysset('shop');
		$wapset['invitecode']=htmlspecialchars_decode($wapset['invitecode']);
		include $this->template();
	}
}
?>
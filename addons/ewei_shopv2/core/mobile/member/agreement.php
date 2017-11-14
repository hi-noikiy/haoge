<?php
if (!(defined('IN_IA'))) 
{
	exit('Access Denied');
}
class Agreement_EweiShopV2Page extends MobileLoginPage 
{
	protected $member;
	public function __construct() 
	{
		global $_W;
		global $_GPC;
		parent::__construct();
	}
	public function main() 
	{

		global $_W;
		global $_GPC;
		$wapset = m('common')->getSysset('shop');
		$wapset['MembershipAgreement']=htmlspecialchars_decode($wapset['MembershipAgreement']);
		include $this->template();
	}
	

}
?>
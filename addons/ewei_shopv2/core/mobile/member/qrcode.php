<?php
if (!(defined('IN_IA'))) 
{
	exit('Access Denied');
}
class Qrcode_EweiShopV2Page extends MobileLoginPage 
{
	public function main() 
	{
		global $_W;
		global $_GPC;

		$member = m('member') -> getMember($_W['openid'], true);
		if($member['invitecode']==''){
			pdo_update('ewei_shop_member',array('invitecode'=>uniqid()),array('openid'=>$_W['openid']));
		}
		$id = intval($_GPC['uid']);
		if (!empty($id)) {
			$member = pdo_get('ewei_shop_member', array('id' => $id), array('id', 'avatar', 'nickname'));
		}
		$agentid=$member['invitecode'];
		$signurl = 'http://' . $_SERVER['SERVER_NAME'] . '/app' . substr(mobileUrl('member/reg', array('agentid' =>$agentid)), 1);
		$thisqrcode = m('qrcode') -> createQrcode($signurl);

		include $this -> template();

	}
}
?>
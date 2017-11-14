<?php
if (!(defined('IN_IA'))) {
	exit('Access Denied');
}
class Reg_EweiShopV2Page extends MobileLoginPage {
	protected $member;
	public function __construct() {
		global $_W;
		global $_GPC;
		parent::__construct();
	}

	public function main() {
		global $_W;
		global $_GPC;
		@session_start();
		$member = m('member') -> getMember($_W['openid']);
		//已经注册过的用户请绕道
		if($member['mobile']!=''){
			$url=mobileUrl('member');
			header("Location: ".$url); 
			exit();
		}		
		//已经注册过的用户请绕道			
		$wapset = m('common') -> getSysset('shop');

		if ($_W['ispost']) {
			$data = array('realname' => $_GPC['realname'], 'mobile' => $_GPC['mobile'], 'verify' => $_GPC['verifycode'], 'agentid' => $_GPC['agentid']);
			if (empty($data['agentid'])) {
				show_json(0, '邀请码不得为空!');
			}
			if ($data['verify'] != $_SESSION['verify']) {
				show_json(0,'您输入的验证码不正确!');
			}
			
			//检查手机号是否已经被注册
			$IsMobileReg=pdo_get('ewei_shop_member',array('mobile'=>$data['mobile']));
			if($IsMobileReg){
				show_json(0, '当前手机已经被注册!');
			}
			
			unset($data['verify']);

			if ($data['agentid'] == $wapset['agentid']) {
				unset($data['agentid']);
			} else 
			{
				$oneres=pdo_get('ewei_shop_member',array('invitecode'=>$data['agentid']));
				if (!$oneres) {
					show_json(0, '邀请码不存在!');
				}
				$data['agentid'] = $oneres['id'];				
			}
			//Update操作
			$data['invitecode']=uniqid();
			$data['isview']=0;
			$data['viewnum']=m('util') -> updateregnum();
			pdo_update('ewei_shop_member',$data,array('openid'=>$member['openid']));
			m('member') ->checkupgroup($data['agentid']);
			$content=$data['realname'].'注册成功,成为第'.$data['viewnum'].'位会员!';	
			m('util') -> insertnotice($content,$content);		
			show_json(1, $data);
		}

		include $this -> template();
	}

	//发送短信接口
	public function sendsms() {
		global $_W;
		global $_GPC;
		@session_start();
		$mobile=$_GPC['mobile'];
		load()->func('communication'); 
		$code=rand(1000,9999);
		$_SESSION['verify']=$code;
		$text1=iconv("UTF-8","GBK", '您的验证码是');
		$text2=iconv("UTF-8","GBK", '打死也不要告诉别人');
		$loginurl = 'http://service.winic.org:8009/sys_port/gateway/index.asp?id=hgoo1&pwd=bjhgsz01&to='.$mobile.'&content='.$text1.$code.$text2;
		//$loginurl=urlencode($loginurl);
		file_put_contents('1.txt', $loginurl);
		file_get_contents($loginurl);
		show_json(1,'发送成功!')	;
	}

}
?>
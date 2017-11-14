<?php
if (!(defined('IN_IA'))) 
{
	exit('Access Denied');
}
class Myteam_EweiShopV2Page extends MobileLoginPage 
{
	protected $member;
	public function __construct() 
	{
		global $_W;
		global $_GPC;
		parent::__construct();
		$this->member = m('member')->getInfo($_W['openid']);
	}
	protected function diyformData() 
	{
		$template_flag = 0;
		$diyform_plugin = p('diyform');
		if ($diyform_plugin) 
		{
			$set_config = $diyform_plugin->getSet();
			$user_diyform_open = $set_config['user_diyform_open'];
			if ($user_diyform_open == 1) 
			{
				$template_flag = 1;
				$diyform_id = $set_config['user_diyform'];
				if (!(empty($diyform_id))) 
				{
					$formInfo = $diyform_plugin->getDiyformInfo($diyform_id);
					$fields = $formInfo['fields'];
					$diyform_data = iunserializer($this->member['diymemberdata']);
					$f_data = $diyform_plugin->getDiyformData($diyform_data, $fields, $this->member);
				}
			}
		}
		return array('template_flag' => $template_flag, 'set_config' => $set_config, 'diyform_plugin' => $diyform_plugin, 'formInfo' => $formInfo, 'diyform_id' => $diyform_id, 'diyform_data' => $diyform_data, 'fields' => $fields, 'f_data' => $f_data);
	}
	public function main() 
	{
		global $_W;
		global $_GPC;
		$ret=m('member')->GetTeamInfo($_W['openid']);
		
		//var_dump($ret);
		$member = $this->member;
		$oneres=pdo_get('ewei_shop_member_group',array('id'=>$member['groupid']));
		$member['groupname']=$oneres['groupname'];
		
		if($_W['isajax']){
			$status=intval($_GPC['status']);
			$data=array();
			switch ($status) {
				case 1:
						$data['id']=$member['agentid'];
						$list=pdo_getall('ewei_shop_member',$data);
					break;
				case 2:
						$data['agentid']=$member['id'];
						$list=pdo_getall('ewei_shop_member',$data);
					break;
				case 3:
						$templist=array();
						$list=pdo_fetchall("select * from  ". tablename('ewei_shop_member')." where agentid  in ("."select id from ".tablename('ewei_shop_member')." where  agentid=".$member['id'].")");
					break;									
				default:
					
					break;
			}	
			
			
			foreach ($list as $key => $value) {
				$list[$key]['createtime']=date('Y-m-d H:i:s',$list[$key]['createtime']);
			}
			show_json(1,array('list'=>$list));
		}
					
		include $this->template();
	}

}
?>
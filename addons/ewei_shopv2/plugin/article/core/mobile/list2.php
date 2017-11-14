<?php

if (!defined('IN_IA')) {
	exit('Access Denied');
}

class List2_EweiShopV2Page extends PluginMobilePage
{
	public function main()
	{
		global $_W;
		global $_GPC;
		
		$type=intval($_GPC['type']);
		include $this->template();
	}


}


?>
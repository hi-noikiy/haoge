<?php
if (!(defined('IN_IA'))) 
{
	exit('Access Denied');
}
class Exchange_EweiShopV2Page extends MobilePage 
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
		global $_GPC;
		global $_W;
		$args = array('pagesize' => 1000, 'page' => intval($_GPC['page']), 'isnew' => trim($_GPC['isnew']), 'ishot' => trim($_GPC['ishot']), 'isrecommand' => trim($_GPC['isrecommand']), 'isdiscount' => trim($_GPC['isdiscount']), 'istime' => trim($_GPC['istime']), 'issendfree' => trim($_GPC['issendfree']), 'keywords' => trim($_GPC['keywords']), 'cate' => 1180, 'order' => trim($_GPC['order']), 'by' => trim($_GPC['by']));
		$plugin_commission = p('commission');
		if ($plugin_commission && (0 < intval($_W['shopset']['commission']['level'])) && empty($_W['shopset']['commission']['closemyshop']) && !(empty($_W['shopset']['commission']['select_goods']))) 
		{
			$frommyshop = intval($_GPC['frommyshop']);
			$mid = intval($_GPC['mid']);
			if (!(empty($mid)) && !(empty($frommyshop))) 
			{
				$shop = p('commission')->getShop($mid);
				if (!(empty($shop['selectgoods']))) 
				{
					$args['ids'] = $shop['goodsids'];
				}
			}
		}
		$this->_condition($args);
	}

	private function _condition($args) 
	{
		global $_GPC;
		$merch_plugin = p('merch');
		$merch_data = m('common')->getPluginset('merch');
		if ($merch_plugin && $merch_data['is_openmerch']) 
		{
			$args['merchid'] = intval($_GPC['merchid']);
		}
		if (isset($_GPC['nocommission'])) 
		{
			$args['nocommission'] = intval($_GPC['nocommission']);
		}
		$goods = m('goods')->getList($args);
		show_json(1, array('list' => $goods['list'], 'total' => $goods['total'], 'pagesize' => $args['pagesize']));
	}
}
?>
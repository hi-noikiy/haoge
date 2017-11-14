<?php
if (!(defined('IN_IA'))) {
	exit('Access Denied');
}
class Discount_EweiShopV2Page extends PluginWebPage {
	public function main() {
		global $_W;
		global $_GPC;
		global $_S;
		$set = $_S['commission'];
		$leveltype = $set['leveltype'];
		$list = pdo_fetchall('SELECT * FROM ' . tablename('ewei_shop_commission_discount') . ' WHERE uniacid = \'' . $_W['uniacid'] . '\' ORDER BY id asc');
		include $this -> template();
	}

	public function add() {
		$this -> post();
	}

	public function edit() {
		$this -> post();
	}

	protected function post() {
		global $_W;
		global $_GPC;
		global $_S;
		$set = $_S['commission'];
		$leveltype = $set['leveltype'];
		$id = trim($_GPC['id']);

		$level = pdo_fetch('SELECT * FROM ' . tablename('ewei_shop_commission_discount') . ' WHERE id=:id and uniacid=:uniacid limit 1', array(':id' => intval($id), ':uniacid' => $_W['uniacid']));

		if ($_W['ispost']) {
			$data = array('uniacid' => $_W['uniacid'], 'levelname' => trim($_GPC['levelname']), 'type' => trim(trim($_GPC['type']), '%'), 'start1' => trim(trim($_GPC['start1']), '%'), 'start2' => trim(trim($_GPC['start2']), '%'), 'rate' => $_GPC['rate']);
			if (!(empty($id))) {

				$updatecontent = '<br/>等级名称: ' . $level['levelname'] . '->' . $data['levelname'] . '<br/>一级佣金比例: ' . $level['start1'] . '->' . $data['start1'] . '<br/>二级佣金比例: ' . $level['start2'] . '->' . $data['start2'] . '<br/>三级佣金比例: ' . $level['commission3'] . '->' . $data['commission3'];
				pdo_update('ewei_shop_commission_discount', $data, array('id' => $id, 'uniacid' => $_W['uniacid']));
				plog('commission.level.edit', '修改分销商等级 ID: ' . $id . $updatecontent);

			} else {
				pdo_insert('ewei_shop_commission_discount', $data);
				$id = pdo_insertid();
				plog('commission.level.add', '添加分销商等级 ID: ' . $id);
			}
			show_json(1, array('url' => webUrl('commission/discount')));
		}
		include $this -> template();
	}

	public function delete() {
		global $_W;
		global $_GPC;
		$id = intval($_GPC['id']);
		if (empty($id)) {
			$id = ((is_array($_GPC['ids']) ? implode(',', $_GPC['ids']) : 0));
		}
		$items = pdo_fetchall('SELECT id,levelname FROM ' . tablename('ewei_shop_commission_discount') . ' WHERE id in( ' . $id . ' ) AND uniacid=' . $_W['uniacid']);
		foreach ($items as $item) {
			pdo_delete('ewei_shop_commission_discount', array('id' => $item['id']));
			plog('commission.level.delete', '删除分销商等级 ID: ' . $id . ' 等级名称: ' . $level['levelname']);
		}
		show_json(1);
	}

}
?>
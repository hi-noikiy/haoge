<?php
if (!(defined('IN_IA'))) 
{
	exit('Access Denied');
}
class Op_EweiShopV2Page extends MobileLoginPage 
{
	
	//催单系统
	public function tomessage(){
		global $_W;
		global $_GPC;
		$orderid=$_GPC['orderid'];		
		
		if(m('notice')->sendOrderMessage($orderid)){
			show_json(1,'通知成功!');
		}
		else{
			show_json(0,'通知失败!');
		}
				
	}		
	
	
	
	public function cancel() 
	{
		global $_W;
		global $_GPC;
		$orderid = intval($_GPC['id']);
		$order = pdo_fetch('select id,ordersn,openid,status,deductcredit,deductcredit2,deductprice,couponid from ' . tablename('ewei_shop_order') . ' where id=:id and uniacid=:uniacid and openid=:openid limit 1', array(':id' => $orderid, ':uniacid' => $_W['uniacid'], ':openid' => $_W['openid']));
		if (empty($order)) 
		{
			show_json(0, '订单未找到');
		}
		if (0 < $order['status']) 
		{
			show_json(0, '订单已支付，不能取消!');
		}
		if ($order['status'] < 0) 
		{
			show_json(0, '订单已经取消!');
		}
		m('order')->setStocksAndCredits($orderid, 2);
		if (0 < $order['deductprice']) 
		{
			m('member')->setCredit($order['openid'], 'credit1', $order['deductcredit'], array('0', $_W['shopset']['shop']['name'] . '购物返还抵扣积分 积分: ' . $order['deductcredit'] . ' 抵扣金额: ' . $order['deductprice'] . ' 订单号: ' . $order['ordersn']));
		}
		m('order')->setDeductCredit2($order);
		if (com('coupon') && !(empty($order['couponid']))) 
		{
			com('coupon')->returnConsumeCoupon($orderid);
		}
		pdo_update('ewei_shop_order', array('status' => -1, 'canceltime' => time(), 'closereason' => trim($_GPC['remark'])), array('id' => $order['id'], 'uniacid' => $_W['uniacid']));
		m('notice')->sendOrderMessage($orderid);
		show_json(1);
	}
	public function finish() 
	{
		global $_W;
		global $_GPC;
		$wapset = m('common')->getSysset('shop');
		$member = m('member')->getMember($_W['openid'], true);
		$orderid = intval($_GPC['id']);

		$order = pdo_fetch('select ordergoodsprice,ordergoodspayprice,id,discountid,status,openid,couponid,refundstate,refundid,ordersn,price from ' . tablename('ewei_shop_order') . ' where id=:id and uniacid=:uniacid and openid=:openid limit 1', array(':id' => $orderid, ':uniacid' => $_W['uniacid'], ':openid' => $_W['openid']));
		if (empty($order)) 
		{
			show_json(0, '订单未找到');
		}
		if ($order['status'] != 2) 
		{
			show_json(0, '订单不能确认收货');
		}
		if ((0 < $order['refundstate']) && !(empty($order['refundid']))) 
		{
			$change_refund = array();
			$change_refund['status'] = -2;
			$change_refund['refundtime'] = time();
			pdo_update('ewei_shop_order_refund', $change_refund, array('id' => $order['refundid'], 'uniacid' => $_W['uniacid']));
		}
		pdo_update('ewei_shop_order', array('status' => 3, 'finishtime' => time(), 'refundstate' => 0), array('id' => $order['id'], 'uniacid' => $_W['uniacid']));
		//m('order')->fullback($orderid);
		m('member')->upgradeLevel($order['openid']);
		//m('order')->setGiveBalance($orderid, 1);
		if (com('coupon')) 
		{
			$refurnid = com('coupon')->sendcouponsbytask($orderid);
		}
		if (com('coupon') && !(empty($order['couponid']))) 
		{
			com('coupon')->backConsumeCoupon($orderid);
		}
		m('notice')->sendOrderMessage($orderid);
		com_run('printer::sendOrderMessage', $orderid);
		if (p('lineup')) 
		{
			p('lineup')->checkOrder($order);
		}
		if (p('commission')) 
		{
			p('commission')->checkOrderFinish($orderid);
		}
		if (p('lottery')) 
		{
			$res = p('lottery')->getLottery($_W['openid'], 1, array('money' => $order['price'], 'paytype' => 2));
			if ($res) 
			{
				p('lottery')->getLotteryList($_W['openid'], array('lottery_id' => $res));
			}
		}
		
		if($member['groupid']<7)
		{	
			//1.确认收货返利积分.条件:1.前6000名.2.当前用户未返利.3.订单中的分享商城订单价格达到系统设置的值	
			$Sysactivenum=intval($wapset['activenum']);
			$Nowactivenum=count(pdo_getall('ewei_shop_member'),array('isdeduct'=>1));
			$Sysactivemoney=intval($wapset['activemoney']);
			if($Nowactivenum<$Sysactivenum){
				if($member['isdeduct']==0){
					//(3).计算订单中的分享商城订单价格
					$ordermoney=0;
					if($order['ordergoodsprice']==$order['ordergoodspayprice']){
						$ordermoney=$order['ordergoodsprice'];
					}
					else{
						$ordermoney=$order['ordergoodspayprice'];
					}				
					if($ordermoney>=$Sysactivemoney){
						m('member')->setCredit($_W['openid'], 'credit1',$ordermoney, array($_W['uid'], '活动前6000名在分享商城下单返豪格币 '));
						$content=$member['realname'].'通过前6000名会员下单获得'.$ordermoney.'豪格币';
						m('util') -> insertnotice($content,$content);	
						pdo_update('ewei_shop_member',array('isdeduct'=>1),array('openid'=>$_W['openid']));
					}			
				}
			}		
			//确认收货返利积分End
			
	
			//2.保存用户折扣并且返利Start,应该返利折扣
			if($order['discountid']!=0){
				pdo_update('ewei_shop_member',array('discountid'=>$order['discountid']),array('openid'=>$_W['openid']));
				$Tempmoney=$order['ordergoodsprice']-$order['ordergoodspayprice'];
				if($Tempmoney>0){
					$content=$member['realname'].'根据消费折扣返利,获得'.$Tempmoney.'奖金';
					m('util') -> insertnotice($content,$content);						
				}			
				m('member')->setCredit($_W['openid'], 'credit2',$Tempmoney, array($_W['uid'], '根据消费折扣返利 '));
			}
			//保存用户折扣End
			
			
			//3.用户允许是否分享
			if($order['price']>500){
				$content=$member['realname'].'通过下单成功开启分享权限';
				if($member['ismember']==0){
					m('util') -> insertnotice($content,$content);						
				}
				pdo_update('ewei_shop_member',array('ismember'=>1),array('openid'=>$_W['openid']));
			}
		}

		//4.处理等级升级
		m('member')->levelupdate($order['price'],$member['levelid']);	
		show_json(1);
	}
	public function delete() 
	{
		global $_W;
		global $_GPC;
		$orderid = intval($_GPC['id']);
		pdo_delete('ewei_shop_order',array('id'=>$orderid));
		show_json(1);
	}
}
?>
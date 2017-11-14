<?php
if (!(defined('IN_IA'))) 
{
	exit('Access Denied');
}
class Message_EweiShopV2Model 
{
	public function sendTplNotice($touser, $template_id, $postdata, $url = '', $account = NULL) 
	{
		if (!($account)) 
		{
			$account = m('common')->getAccount();
		}
		if (!($account)) 
		{
			return;
		}
		return $account->sendTplNotice($touser, $template_id, $postdata, $url);
	}
	
	public function sendOrderMessage($orderid = '0', $delRefund = false) 
	{
		global $_W;
		if (empty($orderid)) 
		{
			return;
		}
		$order = pdo_fetch('select * from ' . tablename('ewei_shop_order') . ' where id=:id limit 1', array(':id' => $orderid));
		if (empty($order)) 
		{
			return;
		}
		$is_merch = 0;
		$openid = $order['openid'];
		$url = $this->getUrl('order/detail', array('id' => $orderid));
		$param = array();
		$param[':uniacid'] = $_W['uniacid'];
		if ($order['isparent'] == 1) 
		{
			$scondition = ' og.parentorderid=:parentorderid';
			$param[':parentorderid'] = $orderid;
		}
		else 
		{
			$scondition = ' og.orderid=:orderid';
			$param[':orderid'] = $orderid;
		}
		$order_goods = pdo_fetchall('select g.id,g.title,og.realprice,og.total,og.price,og.optionname as optiontitle,g.noticeopenid,g.noticetype,og.sendtype,og.expresscom,og.expresssn,og.sendtime from ' . tablename('ewei_shop_order_goods') . ' og ' . ' left join ' . tablename('ewei_shop_goods') . ' g on g.id=og.goodsid ' . ' where ' . $scondition . ' and og.uniacid=:uniacid ', $param);
		$goods = '';
		$goodsname = '';
		$goodsnum = 0;
		foreach ($order_goods as $og ) 
		{
			$goods .= "\n\n" . $og['title'] . '( ';
			if (!(empty($og['optiontitle']))) 
			{
				$goods .= ' 规格: ' . $og['optiontitle'];
			}
			$goods .= ' 单价: ' . ($og['realprice'] / $og['total']) . ' 数量: ' . $og['total'] . ' 总价: ' . $og['realprice'] . '); ';
			$goodsname .= $og['title'] . ' ' . "\n\n";
			$goodsnum += $og['total'];
		}
		$orderpricestr = ' 订单总价: ' . $order['price'] . '(包含运费:' . $order['dispatchprice'] . ')';
		$member = m('member')->getMember($openid);
		$carrier = false;
		$store = false;
		if (!(empty($order['storeid']))) 
		{
			if (0 < $order['merchid']) 
			{
				$store = pdo_fetch('select * from ' . tablename('ewei_shop_merch_store') . ' where id=:id and uniacid=:uniacid and merchid = :merchid limit 1', array(':id' => $order['storeid'], ':uniacid' => $_W['uniacid'], ':merchid' => $order['merchid']));
			}
			else 
			{
				$store = pdo_fetch('select * from ' . tablename('ewei_shop_store') . ' where id=:id and uniacid=:uniacid limit 1', array(':id' => $order['storeid'], ':uniacid' => $_W['uniacid']));
			}
		}
		$buyerinfo = '';
		$buyerinfo_name = '';
		$buyerinfo_mobile = '';
		$addressinfo = '';
		if (!(empty($order['address']))) 
		{
			$address = iunserializer($order['address_send']);
			if (!(is_array($address))) 
			{
				$address = iunserializer($order['address']);
				if (!(is_array($address))) 
				{
					$address = pdo_fetch('select id,realname,mobile,address,province,city,area from ' . tablename('ewei_shop_member_address') . ' where id=:id and uniacid=:uniacid limit 1', array(':id' => $order['addressid'], ':uniacid' => $_W['uniacid']));
				}
			}
			if (!(empty($address))) 
			{
				$addressinfo = $address['province'] . $address['city'] . $address['area'] . ' ' . $address['address'];
				$buyerinfo = '收件人: ' .$order['realname'] . "\n" . '联系电话: ' . $order['realmobile'] . "\n" . '收货地址: ' . $order['realaddress'];
				$buyerinfo_name = $address['realname'];
				$buyerinfo_mobile = $address['mobile'];
			}
		}
		else 
		{
			$carrier = iunserializer($order['carrier']);
			if (is_array($carrier)) 
			{
				$buyerinfo = '联系人: ' . $carrier['carrier_realname'] . "\n" . '联系电话: ' . $carrier['carrier_mobile'];
				$buyerinfo_name = $carrier['carrier_realname'];
				$buyerinfo_mobile = $carrier['carrier_mobile'];
			}
		}
		$datas = array( array('name' => '商城名称', 'value' => $_W['shopset']['shop']['name']), array('name' => '粉丝昵称', 'value' => $member['nickname']), array('name' => '订单号', 'value' => $order['ordersn']), array('name' => '订单金额', 'value' => $order['price']), array('name' => '运费', 'value' => $order['dispatchprice']), array('name' => '商品详情', 'value' => $goods), array('name' => '快递公司', 'value' => $order['expresscom']), array('name' => '快递单号', 'value' => $order['expresssn']), array('name' => '购买者姓名', 'value' => $buyerinfo_name), array('name' => '购买者电话', 'value' => $buyerinfo_mobile), array('name' => '收货地址', 'value' => $addressinfo), array('name' => '下单时间', 'value' => date('Y-m-d H:i', $order['createtime'])), array('name' => '支付时间', 'value' => date('Y-m-d H:i', $order['paytime'])), array('name' => '发货时间', 'value' => date('Y-m-d H:i', $order['sendtime'])), array('name' => '收货时间', 'value' => date('Y-m-d H:i', $order['finishtime'])), array('name' => '取消时间', 'value' => date('Y-m-d H:i', $order['canceltime'])), array('name' => '门店', 'value' => (!(empty($store)) ? $store['storename'] : '')), array('name' => '门店地址', 'value' => (!(empty($store)) ? $store['address'] : '')), array('name' => '门店联系人', 'value' => (!(empty($store)) ? $store['realname'] . '/' . $store['mobile'] : '')), array('name' => '门店营业时间', 'value' => (!(empty($store)) ? ((empty($store['saletime']) ? '全天' : $store['saletime'])) : '')), array('name' => '虚拟物品自动发货内容', 'value' => $order['virtualsend_info']), array('name' => '虚拟卡密自动发货内容', 'value' => $order['virtual_str']), array('name' => '自提码', 'value' => $order['verifycode']), array('name' => '备注信息', 'value' => $order['remark']), array('name' => '商品数量', 'value' => $goodsnum), array('name' => '商品名称', 'value' => $goodsname) );
		$usernotice = unserialize($member['noticeset']);
		if (!(is_array($usernotice))) 
		{
			$usernotice = array();
		}
		$set = m('common')->getSysset();
		$shop = $set['shop'];
		$tm = $set['notice'];
		if (!(empty($order['merchid'])) && p('merch')) 
		{
			$is_merch = 1;
			$merch_tm = p('merch')->getSet('notice', $order['merchid']);
		}
		if ($delRefund) 
		{
			$r_type = array('退款', '退货退款', '换货');
			if (!(empty($order['refundid']))) 
			{
				$refund = pdo_fetch('select * from ' . tablename('ewei_shop_order_refund') . ' where id=:id limit 1', array(':id' => $order['refundid']));
				if (empty($refund)) 
				{
					return;
				}
				$datas[] = array('name' => '售后类型', 'value' => $r_type[$refund['rtype']]);
				$datas[] = array('name' => '申请金额', 'value' => ($refund['rtype'] == 2 ? '-' : $refund['applyprice']));
				$datas[] = array('name' => '退款金额', 'value' => $refund['price']);
				$datas[] = array('name' => '换货快递公司', 'value' => $refund['rexpresscom']);
				$datas[] = array('name' => '换货快递单号', 'value' => $refund['rexpresssn']);
				if ($refund['status'] == 5) 
				{
					if ($refund['rtype'] == 2) 
					{
						if (empty($address)) 
						{
							return;
						}
						$remark = '<a href=\'' . $url . '\'>点击快速查询物流信息</a>';
						$text = '您申请换货的宝贝已经成功发货，请注意查收 ' . "\n\n" . '订单编号：' . "\n" . '[订单号]' . "\n" . '快递公司：[换货快递公司]' . "\n" . '快递单号：[换货快递单号]' . "\n\n" . $remark;
						$msg = array( 'first' => array('value' => '您申请换货的宝贝已经成功发货，请注意查收！' . "\n", 'color' => '#ff0000'), 'keyword1' => array('title' => '订单编号', 'value' => $order['ordersn'], 'color' => '#000000'), 'keyword2' => array('title' => '快递公司', 'value' => $refund['rexpresscom'], 'color' => '#000000'), 'keyword3' => array('title' => '快递单号', 'value' => $refund['rexpresssn'], 'color' => '#000000'), 'remark' => array('value' => "\n" . '点击快速查询物流信息', 'color' => '#000000') );
						$this->sendNotice(array('openid' => $openid, 'tag' => 'refund4', 'default' => $msg, 'cusdefault' => $text, 'url' => $url, 'datas' => $datas));
						com_run('sms::callsms', array('tag' => 'refund4', 'datas' => $datas, 'mobile' => $member['mobile']));
					}
				}
				else if ($refund['status'] == 3) 
				{
					if (($refund['rtype'] == 2) || ($refund['rtype'] == 1)) 
					{
						$salerefund = pdo_fetch('select * from ' . tablename('ewei_shop_refund_address') . ' where uniacid=:uniacid and isdefault=1 limit 1', array(':uniacid' => $_W['uniacid']));
						$datas[] = array('name' => '卖家收货地址', 'value' => $salerefund['province'] . $salerefund['city'] . $salerefund['area'] . ' ' . $salerefund['address']);
						$datas[] = array('name' => '卖家联系电话', 'value' => $salerefund['mobile']);
						$datas[] = array('name' => '卖家收货人', 'value' => $salerefund['name']);
						if (!(empty($usernotice['refund3']))) 
						{
							return;
						}
						$text = '您好，您的换货申请已经通过，请您及时发送快递。' . "\n\n" . '申请换货订单号：' . "\n" . '[订单号]' . "\n" . '请将快递发送到以下地址，并随包裹填写您的订单编号以及联系方式，我们将尽快为您处理' . "\n" . '邮寄地址：[卖家收货地址]' . "\n" . '联系电话：[卖家联系电话]' . "\n" . '收货人：[卖家收货人]' . "\n\n" . '感谢您关注，如有疑问请联系在线客服或<a href=\'' . $url . '\'>点击查看详情</a>';
						$remark2 = '请将快递发送到以下地址，并随包裹填写您的订单编号以及联系方式，我们将尽快为您处理' . "\n\n" . '邮寄地址：' . $salerefund['province'] . $salerefund['city'] . $salerefund['area'] . ' ' . $salerefund['address'] . "\n" . '联系电话：' . $salerefund['mobile'] . "\n" . '收货人：' . $salerefund['name'] . "\n\n" . '感谢您关注，如有疑问请联系在线客服或点击查看详情';
						$msg = array( 'first' => array('value' => '您好，您的换货申请已经通过，请您及时发送快递。' . "\n", 'color' => '#ff0000'), 'keyword1' => array('title' => '任务名称', 'value' => '退换货申请', 'color' => '#000000'), 'keyword2' => array('title' => '通知类型', 'value' => '换货通过', 'color' => '#4b9528'), 'remark' => array('value' => $remark2, 'color' => '#000000') );
						$this->sendNotice(array('openid' => $openid, 'tag' => 'refund3', 'default' => $msg, 'cusdefault' => $text, 'url' => $url, 'datas' => $datas));
						com_run('sms::callsms', array('tag' => 'refund3', 'datas' => $datas, 'mobile' => $member['mobile']));
					}
				}
				else if ($refund['status'] == 1) 
				{
					if (($refund['rtype'] == 0) || ($refund['rtype'] == 1)) 
					{
						if (!(empty($usernotice['refund1']))) 
						{
							return;
						}
						$refundtype = '';
						if (empty($refund['refundtype'])) 
						{
							$refundtype = '余额账户';
						}
						else if ($refund['refundtype'] == 1) 
						{
							$refundtype = '您的对应支付渠道（如银行卡，微信钱包等, 具体到账时间请您查看微信支付通知)';
						}
						else 
						{
							$refundtype = ' 请联系客服进行退款事项！';
						}
						$text = '您好，您有一笔退款已经成功，[退款金额].元已经退回您的申请退款账户内，请及时查看 。' . "\n\n" . '订单编号：' . "\n" . '[订单号]' . "\n" . '退款金额：[退款金额]元' . "\n" . '退款原因：[售后类型]' . "\n" . '退款去向：' . $refundtype . "\n\n" . '感谢您关注，如有疑问请联系在线客服或<a href=\'' . $url . '\'>点击查看详情</a>';
						$msg = array( 'first' => array('value' => '您好，您有一笔退款已经成功，' . $refund['price'] . '元已经退回您的申请退款账户内，请及时查看 。', 'color' => '#ff0000'), 'orderProductPrice' => array('title' => '退款金额', 'value' => $refund['price'] . '元', 'color' => '#000000'), 'orderProductName' => array('title' => '商品名称', 'value' => str_replace("\n", '', $goodsname), 'color' => '#000000'), 'orderName' => array('title' => '订单编号', 'value' => $order['ordersn'], 'color' => '#000000'), 'remark' => array('value' => "\n" . '感谢您关注，如有疑问请联系在线客服或点击查看详情', 'color' => '#000000') );
						$this->sendNotice(array('openid' => $openid, 'tag' => 'refund1', 'default' => $msg, 'cusdefault' => $text, 'url' => $url, 'datas' => $datas));
						com_run('sms::callsms', array('tag' => 'refund1', 'datas' => $datas, 'mobile' => $member['mobile']));
					}
				}
				else if ($refund['status'] == -1) 
				{
					if (!(empty($usernotice['refund2']))) 
					{
						return;
					}
					$remark = "\n" . '感谢您关注，如有疑问请联系在线客服或<a href=\'' . $url . '\'>点击查看详情</a>';
					$text = '您好，你那有一笔' . $r_type[$refund['rtype']] . '被驳回，您可以与我们取得联系！' . "\n\n" . '退款金额：[申请金额]元' . "\n" . '订单编号：' . "\n" . '[订单号]' . "\n" . $remark;
					$remark2 = '商品详情：' . substr_replace(str_replace("\n\n", "\n", $goodsname), '', strrpos($goodsname, "\n"), strlen("\n")) . '订单编号：' . $order['ordersn'] . "\n" . '退款金额：' . (($refund['rtype'] == 2 ? '-' : $refund['applyprice'])) . '元' . "\n\n" . '感谢您关注，如有疑问请联系在线客服或点击查看详情';
					$msg = array( 'first' => array('value' => '您好，你有一笔' . $r_type[$refund['rtype']] . '被驳回，您可以与我们取得联系！' . "\n", 'color' => '#ff0000'), 'keyword1' => array('title' => '任务名称', 'value' => '退换货申请', 'color' => '#000000'), 'keyword2' => array('title' => '通知类型', 'value' => '驳回通知', 'color' => '#ff0000'), 'remark' => array('value' => $remark2, 'color' => '#000000') );
					$this->sendNotice(array('openid' => $openid, 'tag' => 'refund2', 'default' => $msg, 'cusdefault' => $text, 'url' => $url, 'datas' => $datas, 'mobile' => $buyerinfo_mobile));
					com_run('sms::callsms', array('tag' => 'refund2', 'datas' => $datas, 'mobile' => $member['mobile']));
				}
			}
			return;
		}
		if ($order['status'] == -1) 
		{
			if (!(empty($usernotice['cancel']))) 
			{
				return;
			}
			$remark = '，或<a href=\'' . $url . '\'>点击查看详情</a>';
			$text = '您好，您的订单由于长时间未付款已经关闭！！！' . "\n\n" . '商品名称：' . substr_replace($goodsname, '', strrpos($goodsname, "\n\n"), strlen("\n\n")) . "\n" . '订单编号：' . "\n" . '[订单号]' . "\n" . '订单金额：[订单金额]' . "\n" . '下单时间：[下单时间]' . "\n" . '关闭时间：[取消时间]' . "\n\n" . '感谢您的关注，如有疑问请联系在线客服咨询' . $remark;
			$msg = array( 'first' => array('value' => '您好，您的订单由于长时间未付款已经关闭！！！', 'color' => '#ff0000'), 'keyword1' => array('title' => '订单商品', 'value' => substr_replace($goodsname, '', strrpos($goodsname, "\n\n"), strlen("\n\n")), 'color' => '#000000'), 'keyword2' => array('title' => '订单编号', 'value' => $order['ordersn'], 'color' => '#000000'), 'keyword3' => array('title' => '下单时间', 'value' => date('Y-m-d H:i', $order['createtime']), 'color' => '#000000'), 'keyword4' => array('title' => '订单金额', 'value' => $order['price'], 'color' => '#000000'), 'keyword5' => array('title' => '关闭时间', 'value' => date('Y-m-d H:i', $order['canceltime']), 'color' => '#000000'), 'remark' => array('value' => "\n" . '感谢您关注，如有疑问请联系在线客服或点击查看详情！', 'color' => '#000000') );
			$this->sendNotice(array('openid' => $openid, 'tag' => 'cancel', 'default' => $msg, 'cusdefault' => $text, 'url' => $url, 'datas' => $datas, 'mobile' => $buyerinfo_mobile));
			com_run('sms::callsms', array('tag' => 'cancel', 'datas' => $datas, 'mobile' => $member['mobile']));
		}
		else if (($order['status'] == 0) && ($order['paytype'] == 3)) 
		{
			$is_send = 0;
			if (empty($is_merch)) 
			{
				if (empty($usernotice['saler_pay'])) 
				{
					$is_send = 1;
				}
			}
			else if (!(empty($merch_tm)) && empty($merch_tm['saler_pay_close_advanced'])) 
			{
				$is_send = 1;
				$tm['openid'] = $merch_tm['openid'];
			}
			if (!(empty($is_send))) 
			{
				$msg = array( 'first' => array('value' => '您有新的货到付款订单于' . date('Y-m-d H:i', $order['createtime']) . '已下单！！' . "\n" . '请登录后台查看详情并及时安排发货。', 'color' => '#ff0000'), 'keyword1' => array('title' => '订单编号', 'value' => $order['ordersn'], 'color' => '#000000'), 'keyword2' => array('title' => '商品名称', 'value' => $goods, 'color' => '#000000'), 'keyword3' => array('title' => '商品数量', 'value' => $goodsnum, 'color' => '#000000'), 'keyword4' => array('title' => '支付金额', 'value' => $order['price'], 'color' => '#000000') );
				$text = '您有新的货到付款订单！！' . "\n" . '请及时安排发货。' . "\n\n" . '订单号：' . "\n" . '[订单号]' . "\n" . '订单金额：[订单金额]' . "\n" . '下单时间：[下单时间]' . "\n" . '---------------------' . "\n" . '购买商品信息：[商品详情]' . "\n" . '备注信息：[备注信息]' . "\n" . '---------------------' . "\n" . '收货人：[购买者姓名]' . "\n" . '收货人电话:[购买者电话]' . "\n" . '收货地址:[收货地址]' . "\n\n" . '请及时安排发货';
				$account = m('common')->getAccount();
				if (!(empty($tm['openid']))) 
				{
					$openids = explode(',', $tm['openid']);
					foreach ($openids as $tmopenid ) 
					{
						if (empty($tmopenid)) 
						{
							continue;
						}
						$this->sendNotice(array('openid' => $tmopenid, 'tag' => 'saler_pay', 'default' => $msg, 'cusdefault' => $text, 'datas' => $datas, 'is_merch' => $is_merch, 'merch_tm' => $merch_tm));
					}
				}
			}
			if (!(empty($tm['mobile'])) && empty($tm['saler_pay_close_sms']) && empty($is_merch)) 
			{
				$mobiles = explode(',', $tm['mobile']);
				foreach ($mobiles as $mobile ) 
				{
					if (empty($mobile)) 
					{
						continue;
					}
					com_run('sms::callsms', array('tag' => 'saler_pay', 'datas' => $datas, 'mobile' => $mobile));
				}
			}
			$i = 0;
			foreach ($order_goods as $og ) 
			{
				if (!(empty($og['noticeopenid'])) && !(empty($og['noticetype']))) 
				{
					$noticetype = explode(',', $og['noticetype']);
					if (($og['noticetype'] == '1') || (is_array($noticetype) && in_array('1', $noticetype))) 
					{
						++$i;
						$goodstr = $og['title'] . '( ';
						if (!(empty($og['optiontitle']))) 
						{
							$goodstr .= ' 规格: ' . $og['optiontitle'];
							$optiontitle = '( 规格: ' . $og['optiontitle'] . ')';
						}
						$goodstr .= ' 单价: ' . ($og['price'] / $og['total']) . ' 数量: ' . $og['total'] . ' 总价: ' . $og['price'] . '); ';
						$text = '您有新的货到付款订单！！' . "\n" . '请及时安排发货。' . "\n\n" . '订单号：' . "\n" . '[订单号]' . "\n" . '订单金额：[订单金额]' . "\n" . '下单时间：[下单时间]' . "\n" . '---------------------' . "\n" . '购买商品信息：[单品详情]' . "\n" . '备注信息：[备注信息]' . "\n" . '---------------------' . "\n" . '收货人：[购买者姓名]' . "\n" . '收货人电话:[购买者电话]' . "\n" . '收货地址:[收货地址]' . "\n\n" . '请及时安排发货';
						$remark = '订单号：' . "\n" . $order['ordersn'] . "\n" . '商品详情：' . $goodstr;
						$msg = array( 'first' => array('value' => '您有新的货到付款订单于' . date('Y-m-d H:i', $order['createtime']) . '已下单！！' . "\n" . '请登录后台查看详情并及时安排发货。' . "\n", 'color' => '#ff0000'), 'keyword1' => array('title' => '任务名称', 'value' => '商品付款通知', 'color' => '#000000'), 'keyword2' => array('title' => '通知类型', 'value' => '已付款', 'color' => '#000000'), 'remark' => array('value' => $remark, 'color' => '#000000') );
						$datas['gooddetail'] = array('name' => '单品详情', 'value' => $goodstr);
						$noticeopenids = explode(',', $og['noticeopenid']);
						foreach ($noticeopenids as $noticeopenid ) 
						{
							$this->sendNotice(array('openid' => $noticeopenid, 'tag' => 'saler_goodpay', 'cusdefault' => $text, 'default' => $msg, 'datas' => $datas));
						}
					}
				}
			}
		}
		else if (($order['status'] == 1) && empty($order['sendtype'])) 
		{
			$is_send = 0;
			if (empty($is_merch)) 
			{
				if (empty($usernotice['saler_pay'])) 
				{
					$is_send = 1;
				}
			}
			else if (!(empty($merch_tm)) && empty($merch_tm['saler_pay_close_advanced'])) 
			{
				$is_send = 1;
				$tm['openid'] = $merch_tm['openid'];
			}
			if (!(empty($is_send))) 
			{
				$msg = array( 'first' => array('value' => '您有新的订单于' . date('Y-m-d H:i', $order['paytime']) . '已付款！！' . "\n" . '请登录后台查看详情并及时安排发货。', 'color' => '#ff0000'), 'keyword1' => array('title' => '订单编号', 'value' => $order['ordersn'], 'color' => '#000000'), 'keyword2' => array('title' => '商品名称', 'value' => $goods, 'color' => '#000000'), 'keyword3' => array('title' => '商品数量', 'value' => $goodsnum, 'color' => '#000000'), 'keyword4' => array('title' => '支付金额', 'value' => $order['price'], 'color' => '#000000') );
				$text = '您有新的已付款订单！！' . "\n" . '请及时安排发货。' . "\n\n" . '订单号：' . "\n" . '[订单号]' . "\n" . '订单金额：[订单金额]' . "\n" . '支付时间：[支付时间]' . "\n" . '---------------------' . "\n" . '购买商品信息：[商品详情]' . "\n" . '备注信息：[备注信息]' . "\n" . '---------------------' . "\n" . '收货人：'.$order['realname'] . "\n" . '收货人电话:'.$order['realmobile']. "\n" . '收货地址:'.$order['realaddress'] . "\n\n" . '请及时安排发货';
				$account = m('common')->getAccount();
				if (!(empty($tm['openid']))) 
				{
					$openids = explode(',', $tm['openid']);
					foreach ($openids as $tmopenid ) 
					{
						if (empty($tmopenid)) 
						{
							continue;
						}
						$this->sendNotice(array('openid' => $tmopenid, 'tag' => 'saler_pay', 'default' => $msg, 'cusdefault' => $text, 'datas' => $datas, 'is_merch' => $is_merch, 'merch_tm' => $merch_tm));
					}
				}
			}
			if (!(empty($tm['mobile'])) && empty($tm['saler_pay_close_sms']) && empty($is_merch)) 
			{
				$mobiles = explode(',', $tm['mobile']);
				foreach ($mobiles as $mobile ) 
				{
					if (empty($mobile)) 
					{
						continue;
					}
					com_run('sms::callsms', array('tag' => 'saler_pay', 'datas' => $datas, 'mobile' => $mobile));
				}
			}
			$i = 0;
			foreach ($order_goods as $og ) 
			{
				if (!(empty($og['noticeopenid'])) && !(empty($og['noticetype']))) 
				{
					$noticetype = explode(',', $og['noticetype']);
					if (($og['noticetype'] == '1') || (is_array($noticetype) && in_array('1', $noticetype))) 
					{
						++$i;
						$goodstr = $og['title'] . '( ';
						if (!(empty($og['optiontitle']))) 
						{
							$goodstr .= ' 规格: ' . $og['optiontitle'];
							$optiontitle = '( 规格: ' . $og['optiontitle'] . ')';
						}
						$goodstr .= ' 单价: ' . ($og['price'] / $og['total']) . ' 数量: ' . $og['total'] . ' 总价: ' . $og['price'] . '); ';
						$text = '您有新的已付款订单！！' . "\n" . '请及时安排发货。' . "\n\n" . '订单号：' . "\n" . '[订单号]' . "\n" . '订单金额：[订单金额]' . "\n" . '支付时间：[支付时间]' . "\n" . '---------------------' . "\n" . '购买商品信息：[单品详情]' . "\n" . '备注信息：[备注信息]' . "\n" . '---------------------' . "\n" . '收货人：[购买者姓名]' . "\n" . '收货人电话:[购买者电话]' . "\n" . '收货地址:[收货地址]' . "\n\n" . '请及时安排发货';
						$remark = '订单号：' . "\n" . $order['ordersn'] . "\n" . '商品详情：' . $goodstr;
						$msg = array( 'first' => array('value' => '您有新的订单于' . date('Y-m-d H:i', $order['paytime']) . '已付款！！' . "\n" . '请登录后台查看详情并及时安排发货。' . "\n", 'color' => '#ff0000'), 'keyword1' => array('title' => '任务名称', 'value' => '商品付款通知', 'color' => '#000000'), 'keyword2' => array('title' => '通知类型', 'value' => '已付款', 'color' => '#000000'), 'remark' => array('value' => $remark, 'color' => '#000000') );
						$datas['gooddetail'] = array('name' => '单品详情', 'value' => $goodstr);
						$noticeopenids = explode(',', $og['noticeopenid']);
						foreach ($noticeopenids as $noticeopenid ) 
						{
							$this->sendNotice(array('openid' => $noticeopenid, 'tag' => 'saler_goodpay', 'cusdefault' => $text, 'default' => $msg, 'datas' => $datas));
						}
					}
				}
			}
			if (empty($usernotice['pay'])) 
			{
				$remark = "\n";
				if ($order['isverify']) 
				{
					$remark = "\n" . '点击订单详情查看可消费门店, 【' . $shop['name'] . '】欢迎您的再次购物！' . "\n";
				}
				else if ($order['dispatchtype']) 
				{
					$remark = "\n" . '您可以到选择的自提点进行取货了,【' . $shop['name'] . '】欢迎您的再次购物！' . "\n";
				}
				$cusurl = '<a href=\'' . $url . '\'>点击查看详情</a>';
				$text = '您的订单已经成功支付，我们将尽快为您安排发货！！ ' . "\n\n" . '订单号：' . "\n" . '[订单号]' . "\n" . '商品名称：' . "\n" . '[商品名称]商品数量：[商品数量]' . "\n" . '下单时间：[下单时间]' . "\n" . '订单金额：[订单金额]' . "\n" . $remark . $cusurl;
				$msg = array( 'first' => array('value' => '您的订单已于' . date('Y-m-d H:i', $order['paytime']) . '成功支付，我们将尽快为您安排发货！!' . "\n", 'color' => '#4b9528'), 'keyword1' => array('title' => '订单编号', 'value' => $order['ordersn'], 'color' => '#000000'), 'keyword2' => array('title' => '商品名称', 'value' => substr_replace($goodsname, "\n", strrpos($goodsname, "\n\n"), strlen("\n\n")), 'color' => '#000000'), 'keyword3' => array('title' => '商品数量', 'value' => $goodsnum, 'color' => '#000000'), 'keyword4' => array('title' => '支付金额', 'value' => $order['price'], 'color' => '#000000'), 'remark' => array('value' => $remark, 'color' => '#000000') );
				$this->sendNotice(array('openid' => $openid, 'tag' => 'pay', 'default' => $msg, 'cusdefault' => $text, 'url' => $url, 'datas' => $datas));
				com_run('sms::callsms', array('tag' => 'pay', 'datas' => $datas, 'mobile' => $member['mobile']));
			}
			if (($order['dispatchtype'] == 1) && empty($order['isverify'])) 
			{
				if (!(empty($usernotice['carrier']))) 
				{
					return;
				}
				if (!($carrier) || !($store)) 
				{
					return;
				}
				$remark = "\n" . '请您到选择的自提点进行取货, 自提联系人: ' . $store['realname'] . ' 联系电话: ' . $store['mobile'] . "\n\n" . '<a href=\'' . $url . '\'>点击查看详情</a>';
				$text = '自提订单提交成功!！' . "\n" . '自提码：[自提码]' . "\n" . '商品详情：[商品详情]' . "\n" . '提货地址：[门店地址]' . "\n" . '提货时间：[门店营业时间]' . "\n" . $remark;
				$msg = array( 'first' => array('value' => '自提订单提交成功!', 'color' => '#000000'), 'keyword1' => array('title' => '自提码', 'value' => $order['verifycode'], 'color' => '#000000'), 'keyword2' => array('title' => '商品详情', 'value' => $goods . $orderpricestr, 'color' => '#000000'), 'keyword3' => array('title' => '提货地址', 'value' => $store['address'], 'color' => '#000000'), 'keyword4' => array('title' => '提货时间', 'value' => $store['saletime'], 'color' => '#000000'), 'remark' => array('value' => "\n" . '请您到选择的自提点进行取货, 自提联系人: ' . $store['realname'] . ' 联系电话: ' . $store['mobile'], 'color' => '#000000') );
				$this->sendNotice(array('openid' => $openid, 'tag' => 'carrier', 'default' => $msg, 'cusdefault' => $text, 'url' => $url, 'datas' => $datas));
				com_run('sms::callsms', array('tag' => 'carrier', 'datas' => $datas, 'mobile' => $member['mobile']));
			}
		}
		else 
		{
			if (($order['status'] == 2) || (($order['status'] == 1) && !(empty($order['sendtype'])))) 
			{
				if (empty($order['dispatchtype'])) 
				{
					if (!(empty($usernotice['send']))) 
					{
						return;
					}
					$datas[] = array('name' => '发货类型', 'value' => (empty($order['sendtype']) ? '按订单发货' : '按包裹发货'));
					if (empty($order['sendtype'])) 
					{
						if (empty($address)) 
						{
							//return;
						}
						$remark = '<a href=\'' . $url . '\'>点击快速查询物流信息</a>';
						$text = '您的采样盒已经成功发货！ ' . "\n" . '商品名称：[商品详情]' . "\n" . '快递公司：[快递公司]' . "\n" . '快递单号：[快递单号]' . "\n" . $remark;
						$msg = array( 'first' => array('value' => '您的采样盒已经发货！', 'color' => '#000000'), 'keyword1' => array('title' => '订单编号', 'value' => $order['ordersn'], 'color' => '#000000'), 'keyword2' => array('title' => '快递公司', 'value' => $order['expresscom'], 'color' => '#000000'), 'keyword3' => array('title' => '快递单号', 'value' => $order['expresssn'], 'color' => '#000000'), 'remark' => array('value' => "\n" . '我们正加速送到您的手上，请您耐心等候。', 'color' => '#000000') );
						$this->sendNotice(array('openid' => $openid, 'tag' => 'send', 'default' => $msg, 'cusdefault' => $text, 'url' => $url, 'datas' => $datas));
						com_run('sms::callsms', array('tag' => 'send', 'datas' => $datas, 'mobile' => $member['mobile']));
					}
					else 
					{
						$package_goods = array();
						$package_expresscom = '';
						$package_expresssn = '';
						$package_sendtime = '';
						$package_goodsdetail = '';
						$package_goodsname = '';
						foreach ($order_goods as $og ) 
						{
							if ($og['sendtype'] == $order['sendtype']) 
							{
								$package_goods[] = $og;
								if (empty($package_expresscom)) 
								{
									$package_expresscom = $og['expresscom'];
								}
								if (empty($package_expresssn)) 
								{
									$package_expresssn = $og['expresssn'];
								}
								if (empty($package_sendtime)) 
								{
									$package_sendtime = $og['sendtime'];
								}
								$package_goodsdetail .= "\n\n" . $og['title'] . '( ';
								if (!(empty($og['optiontitle']))) 
								{
									$package_goodsdetail .= ' 规格: ' . $og['optiontitle'];
								}
								$package_goodsdetail .= ' 单价: ' . ($og['realprice'] / $og['total']) . ' 数量: ' . $og['total'] . ' 总价: ' . $og['realprice'] . '); ';
								$package_goodsname .= $og['title'] . ' ' . "\n\n";
							}
						}
						if (empty($package_goods)) 
						{
							return;
						}
						$datas[] = array('name' => '包裹快递公司', 'value' => $package_expresscom);
						$datas[] = array('name' => '包裹快递单号', 'value' => $package_expresssn);
						$datas[] = array('name' => '包裹发送时间', 'value' => date('Y-m-d H:i', $package_sendtime));
						$datas[] = array('name' => '包裹商品详情', 'value' => $package_goodsdetail);
						$datas[] = array('name' => '包裹商品名称', 'value' => $package_goodsname);
						$remark = '<a href=\'' . $url . '\'>点击快速查询物流信息</a>';
						$text = '您的采样盒已经成功发货！ ' . "\n" . '商品名称：[包裹商品名称]快递公司：[包裹快递公司]' . "\n" . '快递单号：[包裹快递单号]' . "\n" . $remark;
						$msg = array( 'first' => array('value' => '您的包裹已经发货！', 'color' => '#000000'), 'keyword1' => array('title' => '订单编号', 'value' => $order['ordersn'], 'color' => '#000000'), 'keyword2' => array('title' => '快递公司', 'value' => $package_expresscom, 'color' => '#000000'), 'keyword3' => array('title' => '快递单号', 'value' => $package_expresssn, 'color' => '#000000'), 'remark' => array('value' => "\n" . '我们正加速送到您的手上，请您耐心等候。', 'color' => '#000000') );
						$this->sendNotice(array('openid' => $openid, 'tag' => 'send', 'default' => $msg, 'cusdefault' => $text, 'url' => $url, 'datas' => $datas));
						com_run('sms::callsms', array('tag' => 'send', 'datas' => $datas, 'mobile' => $member['mobile']));
					}
				}
			}
			else if ($order['status'] == 3) 
			{
				$pv = com('virtual');
				if ($pv && !(empty($order['virtual']))) 
				{
					if (empty($usernotice['virtualsend'])) 
					{
						$text = '您的商品已购买成功，以下为您的购物信息。' . "\n\n" . '商品名称:' . str_replace("\n", '', $goodsname) . "\n" . '订单金额：[订单金额]' . "\n" . '卡密信息：<a href=\'' . $url . '\'> 点击查看</a>';
						$msg = array( 'first' => array('value' => '您的商品已购买成功，以下为您的购物信息。', 'color' => '#4b9528'), 'keyword1' => array('title' => '商品名称', 'value' => str_replace("\n", '', $goodsname), 'color' => '#000000'), 'keyword2' => array('title' => '订单号', 'value' => $order['ordersn'], 'color' => '#000000'), 'keyword3' => array('title' => '订单金额', 'value' => '¥' . $order['price'] . '元', 'color' => '#000000'), 'keyword4' => array('title' => '卡密信息', 'value' => '点击查看详情', 'color' => '#ff0000') );
						$this->sendNotice(array('openid' => $openid, 'tag' => 'virtualsend', 'default' => $msg, 'cusdefault' => $text, 'url' => $url, 'datas' => $datas));
						com_run('sms::callsms', array('tag' => 'virtualsend', 'datas' => $datas, 'mobile' => $member['mobile']));
					}
					$first = '买家购买的商品已经自动发货!' . "\n";
					$remark = '订单号：' . $order['ordersn'] . "\n" . '收货时间：' . date('Y-m-d H:i', $order['finishtime']) . "\n" . '商品详情：' . $goods . "\n\n" . '购买者信息:' . "\n" . $buyerinfo;
					$text = $first . "\n" . '订单号：' . $order['ordersn'] . "\n" . '收货时间：' . date('Y-m-d H:i', $order['finishtime']) . "\n" . '商品详情：' . $goods . "\n\n" . '购买者信息:' . "\n" . $buyerinfo;
					$is_send = 0;
					if (empty($is_merch)) 
					{
						if (empty($usernotice['saler_finish'])) 
						{
							$is_send = 1;
						}
					}
					else if (!(empty($merch_tm)) && empty($merch_tm['saler_finish_close_advanced'])) 
					{
						$is_send = 1;
						$tm['openid2'] = $merch_tm['openid2'];
					}
					if (!(empty($is_send))) 
					{
						$msg = array( 'first' => array('value' => $first, 'color' => '#4b9528'), 'keyword1' => array('title' => '任务名称', 'value' => '订单收货通知', 'color' => '#000000'), 'keyword2' => array('title' => '通知类型', 'value' => '虚拟物品及卡密自动发货', 'color' => '#000000'), 'remark' => array('title' => '', 'value' => $remark, 'color' => '#000000') );
						$account = m('common')->getAccount();
						if (!(empty($tm['openid2']))) 
						{
							$openids = explode(',', $tm['openid2']);
							foreach ($openids as $tmopenid ) 
							{
								if (empty($tmopenid)) 
								{
									continue;
								}
								$this->sendNotice(array('openid' => $tmopenid, 'tag' => 'saler_finish', 'cusdefault' => $text, 'default' => $msg, 'datas' => $datas, 'is_merch' => $is_merch, 'merch_tm' => $merch_tm));
							}
						}
					}
					if (!(empty($tm['mobile2'])) && empty($tm['saler_finish_close_sms'])) 
					{
						$mobiles = explode(',', $tm['mobile2']);
						foreach ($mobiles as $mobile ) 
						{
							if (empty($mobile)) 
							{
								continue;
							}
							com_run('sms::callsms', array('tag' => 'saler_finish', 'datas' => $datas, 'mobile' => $mobile));
						}
					}
					foreach ($order_goods as $og ) 
					{
						$noticetype = explode(',', $og['noticetype']);
						if (($og['noticetype'] == '2') || (is_array($noticetype) && in_array('2', $noticetype))) 
						{
							$goodstr = $og['title'] . '( ';
							if (!(empty($og['optiontitle']))) 
							{
								$goodstr .= ' 规格: ' . $og['optiontitle'];
							}
							$goodstr .= ' 单价: ' . ($og['price'] / $og['total']) . ' 数量: ' . $og['total'] . ' 总价: ' . $og['price'] . '); ';
							$remark = '订单号：' . $order['ordersn'] . "\n" . '收货时间：' . date('Y-m-d H:i', $order['finishtime']) . "\n" . '商品详情：' . $goodstr . "\n\n" . '购买者信息:' . "\n" . $buyerinfo;
							$text = $first . "\n" . '订单号：' . $order['ordersn'] . "\n" . '收货时间：' . date('Y-m-d H:i', $order['finishtime']) . "\n" . '商品详情：' . $goodstr . "\n\n" . '购买者信息:' . "\n" . $buyerinfo;
							$msg = array( 'first' => array('value' => $first, 'color' => '#4b9528'), 'keyword1' => array('title' => '任务名称', 'value' => '订单收货通知', 'color' => '#000000'), 'keyword2' => array('title' => '通知类型', 'value' => '虚拟物品及卡密自动发货', 'color' => '#000000'), 'remark' => array('title' => '', 'value' => $remark, 'color' => '#000000') );
							$datas[] = array('name' => '单品详情', 'value' => $goodstr);
							$noticeopenids = explode(',', $og['noticeopenid']);
							foreach ($noticeopenids as $noticeopenid ) 
							{
								$this->sendNotice(array('openid' => $noticeopenid, 'tag' => 'saler_finish', 'cusdefault' => $text, 'default' => $msg, 'datas' => $datas));
							}
						}
					}
				}
				else if ($order['isvirtualsend']) 
				{
					if (empty($usernotice['virtualsend'])) 
					{
						$text = '您的商品已购买成功，以下为您的购物信息。' . "\n\n" . '商品名称:' . str_replace("\n", '', $goodsname) . "\n" . '订单金额：[订单金额]' . "\n" . '卡密信息：<a href=\'' . $url . '\'> 点击查看</a>';
						$msg = array( 'first' => array('value' => '您的商品已购买成功，以下为您的购物信息。', 'color' => '#4b9528'), 'keyword1' => array('title' => '商品名称', 'value' => str_replace("\n", '', $goodsname), 'color' => '#000000'), 'keyword2' => array('title' => '订单号', 'value' => $order['ordersn'], 'color' => '#000000'), 'keyword3' => array('title' => '订单金额', 'value' => '¥' . $order['price'] . '元', 'color' => '#000000'), 'keyword4' => array('title' => '卡密信息', 'value' => '点击查看详情', 'color' => '#ff0000') );
						$this->sendNotice(array('openid' => $openid, 'tag' => 'virtualsend', 'default' => $msg, 'cusdefault' => $text, 'url' => $url, 'datas' => $datas));
						com_run('sms::callsms', array('tag' => 'virtualsend', 'datas' => $datas, 'mobile' => $member['mobile']));
					}
					$first = '买家购买的商品已经自动发货!' . "\n";
					$remark = '订单号：' . $order['ordersn'] . "\n" . '收货时间：' . date('Y-m-d H:i', $order['finishtime']) . "\n" . '商品详情：' . $goods . "\n\n" . '购买者信息:' . "\n" . $buyerinfo;
					$text = $first . "\n" . '订单号：' . $order['ordersn'] . "\n" . '收货时间：' . date('Y-m-d H:i', $order['finishtime']) . "\n" . '商品详情：' . $goods . "\n\n" . '购买者信息:' . "\n" . $buyerinfo;
					$is_send = 0;
					if (empty($is_merch)) 
					{
						if (empty($usernotice['saler_finish'])) 
						{
							$is_send = 1;
						}
					}
					else if (!(empty($merch_tm)) && empty($merch_tm['saler_finish_close_advanced'])) 
					{
						$is_send = 1;
						$tm['openid2'] = $merch_tm['openid2'];
					}
					if (!(empty($is_send))) 
					{
						$msg = array( 'first' => array('value' => $first, 'color' => '#4b9528'), 'keyword1' => array('title' => '任务名称', 'value' => '订单收货通知', 'color' => '#000000'), 'keyword2' => array('title' => '通知类型', 'value' => '商品自动发货', 'color' => '#000000'), 'remark' => array('title' => '', 'value' => $remark, 'color' => '#000000') );
						$account = m('common')->getAccount();
						if (!(empty($tm['openid2']))) 
						{
							$openids = explode(',', $tm['openid2']);
							foreach ($openids as $tmopenid ) 
							{
								if (empty($tmopenid)) 
								{
									continue;
								}
								$this->sendNotice(array('openid' => $tmopenid, 'tag' => 'saler_finish', 'cusdefault' => $text, 'default' => $msg, 'datas' => $datas, 'is_merch' => $is_merch, 'merch_tm' => $merch_tm));
							}
						}
					}
					if (!(empty($tm['mobile2'])) && empty($tm['saler_finish_close_sms'])) 
					{
						$mobiles = explode(',', $tm['mobile2']);
						foreach ($mobiles as $mobile ) 
						{
							if (empty($mobile)) 
							{
								continue;
							}
							com_run('sms::callsms', array('tag' => 'saler_finish', 'datas' => $datas, 'mobile' => $mobile));
						}
					}
					foreach ($order_goods as $og ) 
					{
						$noticetype = explode(',', $og['noticetype']);
						if (($og['noticetype'] == '2') || (is_array($noticetype) && in_array('2', $noticetype))) 
						{
							$goodstr = $og['title'] . '( ';
							if (!(empty($og['optiontitle']))) 
							{
								$goodstr .= ' 规格: ' . $og['optiontitle'];
							}
							$goodstr .= ' 单价: ' . ($og['price'] / $og['total']) . ' 数量: ' . $og['total'] . ' 总价: ' . $og['price'] . '); ';
							$remark = '订单号：' . $order['ordersn'] . "\n" . '收货时间：' . date('Y-m-d H:i', $order['finishtime']) . "\n" . '商品详情：' . $goodstr . "\n\n" . '购买者信息:' . "\n" . $buyerinfo;
							$text = $first . "\n" . '订单号：' . $order['ordersn'] . "\n" . '收货时间：' . date('Y-m-d H:i', $order['finishtime']) . "\n" . '商品详情：' . $goodstr . "\n\n" . '购买者信息:' . "\n" . $buyerinfo;
							$msg = array( 'first' => array('value' => $first, 'color' => '#4b9528'), 'keyword1' => array('title' => '任务名称', 'value' => '订单收货通知', 'color' => '#000000'), 'keyword2' => array('title' => '通知类型', 'value' => '虚拟物品及卡密自动发货', 'color' => '#000000'), 'remark' => array('title' => '', 'value' => $remark, 'color' => '#000000') );
							$datas[] = array('name' => '单品详情', 'value' => $goodstr);
							$noticeopenids = explode(',', $og['noticeopenid']);
							foreach ($noticeopenids as $noticeopenid ) 
							{
								$this->sendNotice(array('openid' => $noticeopenid, 'tag' => 'saler_finish', 'cusdefault' => $text, 'default' => $msg, 'datas' => $datas));
							}
						}
					}
				}
				else 
				{
					$first = '买家购买的商品已经确认收货!' . "\n";
					if ($order['isverify'] == 1) 
					{
						$first = '买家购买的商品已经确认核销!' . "\n";
					}
					$text = $first . "\n" . '订单号：' . "\n" . $order['ordersn'] . "\n" . '收货时间：' . date('Y-m-d H:i', $order['finishtime']) . "\n" . '商品详情：' . $goods;
					$remark = '订单号：' . $order['ordersn'] . "\n" . '收货时间：' . date('Y-m-d H:i', $order['finishtime']) . "\n" . '商品详情：' . $goods;
					if (!(empty($buyerinfo))) 
					{
						$remark = $remark . "\n" . '购买者信息:' . "\n" . $buyerinfo;
						$text = $text . "\n\n" . '购买者信息:' . "\n" . $buyerinfo;
					}
					$is_send = 0;
					if (empty($is_merch)) 
					{
						if (empty($usernotice['saler_finish'])) 
						{
							$is_send = 1;
						}
					}
					else if (!(empty($merch_tm)) && empty($merch_tm['saler_finish_close_advanced'])) 
					{
						$is_send = 1;
						$tm['openid2'] = $merch_tm['openid2'];
					}
					if (!(empty($is_send))) 
					{
						$msg = array( 'first' => array('value' => $first, 'color' => '#4b9528'), 'keyword1' => array('title' => '任务名称', 'value' => '订单收货通知', 'color' => '#000000'), 'keyword2' => array('title' => '通知类型', 'value' => '商品确认收货', 'color' => '#000000'), 'remark' => array('title' => '', 'value' => $remark, 'color' => '#000000') );
						$account = m('common')->getAccount();
						if (!(empty($tm['openid2']))) 
						{
							$openids = explode(',', $tm['openid2']);
							foreach ($openids as $tmopenid ) 
							{
								if (empty($tmopenid)) 
								{
									continue;
								}
								$this->sendNotice(array('openid' => $tmopenid, 'tag' => 'saler_finish', 'cusdefault' => $text, 'default' => $msg, 'datas' => $datas, 'is_merch' => $is_merch, 'merch_tm' => $merch_tm));
							}
						}
					}
					if (!(empty($tm['mobile2'])) && empty($tm['saler_finish_close_sms']) && empty($is_merch)) 
					{
						$mobiles = explode(',', $tm['mobile2']);
						foreach ($mobiles as $mobile ) 
						{
							if (empty($mobile)) 
							{
								continue;
							}
							com_run('sms::callsms', array('tag' => 'saler_finish', 'datas' => $datas, 'mobile' => $mobile));
						}
					}
					foreach ($order_goods as $og ) 
					{
						$noticetype = explode(',', $og['noticetype']);
						if (($og['noticetype'] == '2') || (is_array($noticetype) && in_array('2', $noticetype))) 
						{
							$goodstr = $og['title'] . '( ';
							if (!(empty($og['optiontitle']))) 
							{
								$goodstr .= ' 规格: ' . $og['optiontitle'];
							}
							$goodstr .= ' 单价: ' . ($og['price'] / $og['total']) . ' 数量: ' . $og['total'] . ' 总价: ' . $og['price'] . '); ';
							$remark = '订单号：' . $order['ordersn'] . "\n" . '收货时间：' . date('Y-m-d H:i', $order['finishtime']) . "\n" . '商品详情：' . $goods;
							$text = $first . "\n" . '订单号：' . "\n" . $order['ordersn'] . "\n" . '收货时间：' . date('Y-m-d H:i', $order['finishtime']) . "\n" . '商品详情：' . $goods;
							if (!(empty($buyerinfo))) 
							{
								$remark = $remark . "\n" . '购买者信息:' . "\n" . $buyerinfo;
								$text = $text . "\n\n" . '购买者信息:' . "\n" . $buyerinfo;
							}
							$msg = array( 'first' => array('value' => $first, 'color' => '#4b9528'), 'keyword1' => array('title' => '任务名称', 'value' => '订单收货通知', 'color' => '#000000'), 'keyword2' => array('title' => '通知类型', 'value' => '虚拟物品及卡密自动发货', 'color' => '#000000'), 'remark' => array('title' => '', 'value' => $remark, 'color' => '#000000') );
							$datas[] = array('name' => '单品详情', 'value' => $goodstr);
							$noticeopenids = explode(',', $og['noticeopenid']);
							foreach ($noticeopenids as $noticeopenid ) 
							{
								$this->sendNotice(array('openid' => $noticeopenid, 'tag' => 'saler_finish', 'cusdefault' => $text, 'default' => $msg, 'datas' => $datas));
							}
						}
					}
				}
			}
		}
	}	
	
	public function sendCustomNotice($openid, $msg, $url = '', $account = NULL) 
	{
		if (!($account)) 
		{
			$account = m('common')->getAccount();
		}
		if (!($account)) 
		{
			return;
		}
		$content = '';
		if (is_array($msg)) 
		{
			foreach ($msg as $key => $value ) 
			{
				if (!(empty($value['title']))) 
				{
					$content .= $value['title'] . ':' . $value['value'] . "\n";
				}
				else 
				{
					$content .= $value['value'] . "\n";
					if ($key == 0) 
					{
						$content .= "\n";
					}
				}
			}
		}
		else 
		{
			$content = $msg;
		}
		if (!(empty($url))) 
		{
			$content .= '<a href=\'' . $url . '\'>点击查看详情</a>';
		}
		return $account->sendCustomNotice(array( 'touser' => $openid, 'msgtype' => 'text', 'text' => array('content' => urlencode($content)) ));
	}
	public function sendImage($openid, $mediaid) 
	{
		$account = m('common')->getAccount();
		return $account->sendCustomNotice(array( 'touser' => $openid, 'msgtype' => 'image', 'image' => array('media_id' => $mediaid) ));
	}
	public function sendNews($openid, $articles, $account = NULL) 
	{
		if (!($account)) 
		{
			$account = m('common')->getAccount();
		}
		return $account->sendCustomNotice(array( 'touser' => $openid, 'msgtype' => 'news', 'news' => array('articles' => $articles) ));
	}
	public function sendTexts($openid, $content, $url = '', $account = NULL) 
	{
		if (!($account)) 
		{
			$account = m('common')->getAccount();
		}
		if (!(empty($url))) 
		{
			$content .= "\n" . '<a href=\'' . $url . '\'>点击查看详情</a>';
		}
		return $account->sendCustomNotice(array( 'touser' => $openid, 'msgtype' => 'text', 'text' => array('content' => urlencode($content)) ));
	}
}
?>
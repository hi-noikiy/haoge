<?php
if (!defined('IN_IA')) 
{
	exit('Access Denied');
}
class Index_EweiShopV2Page extends PluginMobilePage 
{
	public function main() 
	{
		global $_W;
		global $_GPC;
		//获取全部的一级分类
		$category=pdo_getall('ewei_shop_article_category',array('uid'=>0));
		
		if($_W['ispost']){
			$cate=intval($_GPC['cate']);
			
			$page = intval($_GPC['page']);
			$pindex =max(1, $page);	
			$psize=5;	
			$where=' where 1 ';
			if(!empty($cate)){
				$where .='  and article_category='.$cate.' ';
			}	

			$list=pdo_fetchall('SELECT  *  FROM ' . tablename('ewei_shop_article'). $where . ' order by id desc limit ' . (($pindex - 1) * $psize) . ',' . $psize);			
			
			foreach ($list as $key => $value) {
				$list[$key]['resp_img']=tomedia($value['resp_img']);
			}
			
			if(count($list)>0){
				show_json(1,array('list'=>$list));					
			}
			else
			{
				show_json(0,'nothing to do!');					
			}	
		}
		
		
		
		
		include $this->template();
	}
	public function share($params = array()) 
	{
		global $_W;
		global $_GPC;
		$myid = m('member')->getMid();
		$shareid = intval($_GPC['shareid']);
		$aid = intval($_GPC['aid']);
		$this->model->doShare($aid, $shareid, $myid);
	}
	public function getcontent() 
	{
		global $_W;
		global $_GPC;
		$aid = intval($_GPC['aid']);
		if (empty($aid)) 
		{
			show_json(0, '参数错误');
		}
		$article = pdo_fetch('SELECT article_content FROM ' . tablename('ewei_shop_article') . ' WHERE id=:aid and article_state=1 and uniacid=:uniacid limit 1 ', array(':aid' => $aid, ':uniacid' => $_W['uniacid']));
		if (empty($article)) 
		{
			show_json(0, '文章不存在');
		}
		show_json(1, array('content' => base64_encode($article['article_content'])));
	}
	public function like() 
	{
		global $_W;
		global $_GPC;
		$aid = intval($_GPC['aid']);
		$openid = $_W['openid'];
		if (!empty($aid) && !empty($openid)) 
		{
			$state = pdo_fetch('SELECT * FROM ' . tablename('ewei_shop_article_log') . ' WHERE openid=:openid and aid=:aid and uniacid=:uniacid limit 1 ', array(':openid' => $_W['openid'], ':aid' => $aid, ':uniacid' => $_W['uniacid']));
			if (empty($state['like'])) 
			{
				pdo_update('ewei_shop_article', 'article_likenum=article_likenum+1', array('id' => $aid));
				pdo_update('ewei_shop_article_log', array('like' => $state['like'] + 1), array('id' => $state['id']));
				show_json(0, array('status' => 1));
				return NULL;
			}
			pdo_update('ewei_shop_article', 'article_likenum=article_likenum-1', array('id' => $aid));
			pdo_update('ewei_shop_article_log', array('like' => $state['like'] - 1), array('id' => $state['id']));
			show_json(0, array('status' => 0));
		}
	}
}
?>
<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com

 * =========================================================
 */


namespace addon\pintuan\event;

use addon\pintuan\model\Pintuan;

/**
 * 营销活动商品分类id
 */
class GoodsListCategoryIds
{

	/**
	 * 商品营销活动信息
	 * @param $param
	 * @return array
	 */
	public function handle($param)
	{
		if (empty($param[ 'promotion' ]) || $param[ 'promotion' ] != 'pintuan') return [];

		$condition[] = [
			['pp.site_id', '=', $param[ 'site_id' ]],
			['pp.status', '=', 1],
            ['g.is_delete','=',0],
            ['g.goods_state','=',1],
		];

		$model = new Pintuan();
		$res = $model->getGoodsCategoryIds($condition);
		return $res;
	}
}
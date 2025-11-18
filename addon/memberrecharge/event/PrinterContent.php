<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\memberrecharge\event;

use app\model\member\Member;
use app\model\shop\Shop;
use addon\printer\model\Printer;
use addon\printer\model\PrinterTemplate;
use app\Controller;
use addon\memberrecharge\model\MemberrechargeOrder;
use app\model\system\Site;

/**
 * 打印内容
 */
class PrinterContent extends Controller
{

    public function handle($params)
    {
        if ($params[ 'type' ] == 'recharge') {
            $order_id = $params[ 'order_id' ];

            $order_common_model = new MemberrechargeOrder();
            $order_info = $order_common_model->getMemberRechargeOrderInfo([ [ 'order_id', '=', $order_id ] ])[ 'data' ];
            $params[ 'site_id' ] = $order_info[ 'site_id' ] ?? 0;

            $member = new Member();
            $member_info = $member->getMemberInfo([ [ 'member_id', '=', $order_info[ 'member_id' ] ] ], 'balance, balance_money')[ 'data' ] ?? [];
            $account = number_format($member_info[ 'balance' ] + $member_info[ 'balance_money' ], 2, '.', '');

            //获取店铺信息
            $shop_model = new Shop();
            $shop_info = $shop_model->getShopInfo([ [ 'site_id', '=', $params[ 'site_id' ] ] ])[ 'data' ];

            $site_name = ( new Site() )->getSiteInfo([ [ 'site_id', '=', $params[ 'site_id' ] ] ], 'site_name')[ 'data' ][ 'site_name' ] ?? '';

            //获取打印机列表
            $print_model = new Printer();
            $printer_condition = [
                [ 'site_id', '=', $params[ 'site_id' ] ],
                [ 'store_id', '=', $order_info[ 'store_id' ] ],
                [ 'recharge_open', '=', 1 ],
            ];
            //指定打印机
            if(isset($params['printer_ids']) && $params['printer_ids'] != 'all'){
                if(empty($params['printer_ids'])) $params['printer_ids'] = '0';
                $printer_condition[] = ['printer_id', 'in', $params['printer_ids']];
            }
            $printer_data = $print_model->getPrinterList($printer_condition)[ 'data' ] ?? [];

            $res_data = [];

            foreach ($printer_data as $k => $v) {
                //此处应该根据打印机不同分别设置返回不同的数据。当前为易联云
                $array = [];
                $array[ 'printer_info' ] = $v;
                $print_template_model = new PrinterTemplate();

                $template_id = $v[ 'recharge_template_id' ];

                $print_template = $print_template_model->getPrinterTemplateInfo([ [ 'template_id', '=', $template_id ] ])[ 'data' ];

                $array[ 'printer_code' ] = $v[ 'printer_code' ];    //商户授权机器码
                $array[ 'origin_id' ] = $order_info[ 'order_no' ];        //内部订单号(32位以内)
                /**文本接口开始**/
                $content = "<MN>" . $v[ 'recharge_print_num' ] . "</MN>";
                //小票名称
                if ($print_template[ 'title' ] != '') {
                    $content .= "<center>" . $print_template[ 'title' ] . "</center>";
                    $content .= str_repeat('.', 32);
                }
                //商城名称
                if ($print_template[ 'head' ] == 1) {
                    $content .= "<FH2><FS><center>" . $site_name . "</center></FS></FH2>";
                    $content .= str_repeat('.', 32);
                }
                $content .= "订单时间:" . date("Y-m-d H:i", $order_info[ 'pay_time' ]) . "\n";
                $content .= "订单编号:" . $order_info[ 'order_no' ] . "\n";
                $content .= "支付方式:" . $order_info[ 'pay_type_name' ] . "\n";
                $content .= str_repeat('.', 32);

                $content .= "充值金额：￥" . $order_info[ "face_value" ] . "\n";
                $content .= "实付金额：￥" . $order_info[ "price" ] . "\n";
                $content .= "会员余额：￥" . $account . "\n";

                /******************** 买家信息 **************************/
                //买家姓名
                if ($print_template[ 'buy_name' ] == 1) {
                    $content .= "会员：" . $order_info[ "nickname" ] . "\n";
                    $content .= str_repeat('.', 32);
                }

                /******************** 商城信息 **************************/
                //联系方式
                if ($print_template[ 'shop_mobile' ] == 1) {
                    $content .= "" . $shop_info[ "mobile" ] . "\n";
                }
                //地址
                if ($print_template[ 'shop_address' ] == 1) {
                    $content .= "" . $shop_info[ 'province_name' ] . $shop_info[ 'city_name' ] . $shop_info[ 'district_name' ] . $shop_info[ 'address' ] . "\n";
                }

                if ($print_template[ 'shop_mobile' ] == 1 || $print_template[ 'shop_address' ] == 1) {
                    $content .= str_repeat('.', 32);
                }
                //二维码
                if ($print_template[ 'shop_qrcode' ] == 1) {
                    $content .= "<QR>" . $print_template[ 'qrcode_url' ] . "</QR>";
                    $content .= str_repeat('.', 32);
                }

                //底部内容
                if (!empty($print_template[ 'bottom' ])) {

                    $content .= "<center>" . $print_template[ 'bottom' ] . "</center>";
                }
                $array[ 'content' ] = $content;

                $res_data[] = $array;
            }

            return $res_data;

        }
    }
}
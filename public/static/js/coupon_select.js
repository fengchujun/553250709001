(function (){
    function CouponSelect(param){
        param = param || {};
        let that = this;
        that.selectedIds = param.selectedIds || [];
        that.selectedList = [];
        that.tableElem = param.tableElem;
        that.selectElem = param.selectElem;
        that.layui = null;

        if(typeof that.selectedIds == 'string' && that.selectedIds){
            that.selectedIds = that.selectedIds.split(',');
        }

        that.init(()=>{
            that.bindEvent();
            that.getSelectedList(()=>{
                that.renderTable();
            })
            $(that.selectElem).on('click', ()=>{
                ns.selectCoupon({
                    select_id:that.selectedIds.toString(),
                    success:function (res){
                        that.selectedList = res;
                        that.selectedListToIds();
                        that.renderTable();
                    }
                })
            })
        })
    }

    CouponSelect.prototype.getSelectedData = function (){
        let that = this;
        return {
            selectedIds:that.selectedIds,
            selectedList:that.selectedList,
        }
    }

    CouponSelect.prototype.init = function (callback){
        let that = this;
        layui.use(['form', 'laytpl'], function() {
            that.layui = layui;
            callback && callback();
        })
    }

    CouponSelect.prototype.getSelectedList = function (callback){
        let that = this;
        $.ajax({
            url: ns.url("coupon://shop/coupon/couponselect"),
            data: {
                page:1,
                page_size:0,
                coupon_type_ids:that.selectedIds.toString() || -1,
            },
            dataType: 'JSON', //服务器返回json格式数据
            type: 'POST', //http请求类型
            success: function(res) {
                that.selectedList = res.data.list;
                that.selectedListToIds();
                callback && callback();
            }
        });
    }

    CouponSelect.prototype.selectedListToIds = function (){
        let that = this;
        let selectedIds = [];
        that.selectedList.forEach((item)=>{
            selectedIds.push(item.coupon_type_id);
        })
        that.selectedIds = selectedIds;
    }

    CouponSelect.prototype.renderTable = function (){
        let that = this;
        that.layui.laytpl(templete).render(that.selectedList, function (html) {
            $(that.tableElem).html(html);
        })
    }

    CouponSelect.prototype.bindEvent = function (){
        let that = this;
        $(that.tableElem).on('click', '.table-btn .delete-btn', function (){
            let index = $(this).parents('tr').data('index');
            that.selectedList.splice(index, 1);
            that.selectedListToIds();
            that.renderTable();
        })
    }

    var templete = `
        <table class="layui-table" id="coupon_selected" lay-skin="line">
            <colgroup>
                <col width="16%">
                <col width="16%">
                <col width="16%">
                <col width="16%">
                <col width="16%">
                <col width="16%">
            </colgroup>
            <thead>
                <tr>
                    <th>优惠券名称</th>
                    <th>优惠内容</th>
                    <th>活动商品</th>
                    <th>有效期</th>
                    <th>适用场景</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                {{# d.forEach((item, index)=>{ }}
                <tr data-index="{{index}}">
                    <td>{{item.coupon_name}}</td>
                    <td>
                        {{# if(item.type == 'reward'){ }}
                            <div class="text">满{{item.at_least.replace('.00','')}}减{{item.money.replace('.00','')}}元</div>
                        {{# }else{ }}
                            <div class="text">满{{item.at_least.replace('.00','')}}打{{item.discount.replace('.00','')}}折<br/>最多可抵{{item.discount_limit.replace('.00','')}}元</div>
                        {{# } }}
                    </td>
                    <td>{{item.goods_type_name}}</td>
                    <td>
                        {{#  if(d.validity_type == 0){  }}
                        至 {{ ns.time_to_date(d.end_time) }}
                        {{#  } else if(d.validity_type == 1) {  }}
                        领取后，{{ d.fixed_term }}天有效
                        {{#  } else { }}
                        长期有效
                        {{#  }  }}
                    </td>
                    <td>{{item.use_channel_name}}</td>
                    <td>
                        <div class="table-btn">
                            <a  class="layui-btn delete-btn">删除</a>
                        </div>
                    </td>
                </tr>
                {{# }) }}
                {{# if(d.length == 0){ }}
                <tr>
                    <td class="goods-empty" colspan="4">
                        <div>尚未选择赠送优惠券</div>
                    </td>
                </tr>
                {{# } }}
            </tbody>
        </table>
    `;

    window.CouponSelect = CouponSelect;
})()
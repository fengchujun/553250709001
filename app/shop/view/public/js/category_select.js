(function(){
    let boxCount = 0;
    let laytpl;
    let template = '';
    let tree;

    function CategorySelect(param = {}){
        boxCount ++;

        let that = this;
        that.elem = param.elem;//绑定显示输入框元素
        that.data = param.data || [];//分类数据
        that.callback = param.callback || null;//选择完成后回调方法
        that.level = param.level || 'end';//等级 any 任意 end 最末级
        that.count = boxCount;
        that.boxSign = ".category-select-popup[data-count='"+ that.count +"']";
        that.showData = [];
        that.position = null;
        that.isInit = false;

        that.bindEvent();
    }

    CategorySelect.prototype.initShowData = function (){
        let that = this;
        if(that.isInit) return;
        if(that.showData.length === 0){
            if(that.data.length > 0){
                var currData = tree;
                that.data.forEach(function(item, index){
                    var selected = -1;
                    currData.forEach(function(ditem, dindex){
                        if(ditem.category_id == item.category_id){
                            selected = dindex;
                            return;
                        }
                    })
                    that.showData.push({
                        data : JSON.parse(JSON.stringify(currData)),
                        selected : selected,
                    })
                    if(selected != -1){
                        currData = currData[selected].child_list;
                    }
                })

            }else{
                that.showData.push({data : tree, selected : -1});
            }
        }
        that.isInit = true;
    }

    CategorySelect.prototype.getPosition = function (){
        let that = this;
        let dom = $(that.elem);
        if(dom.length <= 0) throw '元素不存在';
        that.position = {
            left : dom.offset().left,
            top : dom.offset().top + dom.height() + 3,
        }
    }

    CategorySelect.prototype.bindEvent = function (){
        let that = this;
        //点击绑定元素
        $('body').off('click', that.elem).on('click', that.elem, function(){
            that.initShowData();
            that.render();
        })

        $(document).on('mouseup', function(e){
            var targetArea = $(that.boxSign);   // 设置目标区域
            if(targetArea.length > 0){
                if(!targetArea.is(e.target) && targetArea.has(e.target).length === 0){
                    targetArea.parent().remove();
                    that.isInit = false;
                    that.showData = [];
                }
            }
        });

        //点击分类
        $('body').off('click', that.boxSign + ' .category-select-ul-box ul li').on('click', that.boxSign + ' .category-select-ul-box ul li', function(){
            let target = $(this).attr('data-target').split('|');
            let level = target[0];
            let index = target[1];
            //重置数据
            let tempShowData = [];
            for(let i in that.showData){
                if(i <= level){
                    tempShowData.push(that.showData[i]);
                }
            }
            that.showData = tempShowData;
            that.showData[level].selected = index;

            //追加新数据
            if(that.showData[level]['data'][index].child_num > 0){
                that.showData.push({
                    data : that.showData[level]['data'][index].child_list,
                    selected : -1,
                })
                that.render();
            }else{
                that.selectEnd();
            }
        })

        $('body').off('click', that.boxSign + ' .category-select-btn-box button').on('click', that.boxSign + ' .category-select-btn-box button', function(){
            let action = $(this).data('action');
            switch (action) {
                case 'confirm':
                    that.selectEnd();
                    break;
                case 'clear':
                    that.showData = [];
                    that.selectEnd();
                    break;
            }
            that.isInit = false;
        })
    }

    CategorySelect.prototype.render = function (){
        let that = this;
        //获取原先的滚动高度
        let scroll_top_arr = [];
        if($(that.boxSign).length > 0){
            $(that.boxSign).find('.category-select-ul-box>ul').each(function () {
                scroll_top_arr.push($(this).scrollTop());
            })
            $(that.boxSign).parent().remove();
        }
        //getPosition();
        laytpl(template).render({
            count : that.count,
            position : that.position,
            showData : that.showData,
            level : that.level,
        }, function(html) {
            $(that.elem).after(html);

            //调整选择框位置
            let popup_dom = $(".category-select-popup[data-count="+that.count+"]").parent();
            let popup_offset = popup_dom.offset();
            let elem_offset = $(that.elem).offset();
            let elem_height = $(that.elem).height();
            let left = elem_offset.left - popup_offset.left;
            let top = elem_offset.top - popup_offset.top + elem_height + 5;
            popup_dom.css({left:left+'px',top:top+'px'});

            //重新渲染后恢复滚动高度
            $(that.boxSign).find('.category-select-ul-box>ul').each(function (index) {
                let scroll_top = scroll_top_arr[index] || 0;
                $(this).scrollTop(scroll_top);
            })
        });
    }

    CategorySelect.prototype.selectEnd = function (){
        let that = this;
        that.data = [];
        that.showData.forEach(function(item, index){
            if(item.selected !== -1){
                that.data.push({
                    category_id : item.data[item.selected].category_id,
                    category_name : item.data[item.selected].category_name,
                    level : item.data[item.selected].level,
                    attr_class_id : item.data[item.selected].attr_class_id,
                })
            }
        })
        $(that.boxSign).parent().remove();
        if(typeof that.callback == 'function') that.callback(that.data);
    }

    layui.use(['laytpl','form'], function () {
        laytpl = layui.laytpl;
    })

    template =
        `<div class="category-select-popup-position">
        <div class="category-select-popup" data-count="{{ d.count }}" style="width: {{ d.showData.length * 140 }}px;">
            <div class="category-select-ul-box">
                {{# d.showData.forEach(function(data, level){ }}
                <ul>
                    {{# data.data.forEach(function(item, index){ }}
                    <li data-target="{{ level }}|{{ index }}" class="{{ index == data.selected ? 'selected' : '' }}">
                        <span title="{{ item.category_name }}">{{ item.category_name }}</span>
                        {{# if(item.child_num > 0){ }}
                        <i class="layui-icon-right layui-icon"></i>
                        {{# } }}
                    </li>
                    {{# }) }}
                </ul>
                {{# }) }}
            </div>
            
            <div class="category-select-btn-box">
                <button class="layui-btn layui-btn-primary" data-action="clear">清空</button>
                {{# if(d.level == 'any'){ }}
                <button class="layui-btn ns-bg-color" data-action="confirm">确认</button>
                {{# } }}
            </div>
        </div>
    </div>`;

    //获取分类数据
    function getGoodsCategoryTree() {
        return new Promise(function(resolve, reject){
            let url = ns.url("shop/goodscategory/getCategoryTree");
            if(location.href.indexOf('cardservice://') !== -1){
                url = ns.url("cardservice://shop/servicecategory/lists");
            }
            $.ajax({
                url: url,
                dataType: 'JSON',
                type: 'POST',
                data: {
                    category_id:'category_id',
                    category_name:'category_name',
                    children:'child_list',
                },
                success: function(res) {
                    resolve(res);
                }
            })
        })
    }
    getGoodsCategoryTree().then(function(res){
        tree = res.data;
    })

    //向外暴露接口
    window.CategorySelect = CategorySelect;
})()
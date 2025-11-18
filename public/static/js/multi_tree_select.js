(function (){
    let typeConfig = {
        category:{
            title:'选择分类',
            url:'shop/goodscategory/getCategoryTree',
        },
        area:{
            title:'选择地区',
            url:'shop/address/getAreaTree',
        },
    };
    function MultiTreeSelect(param){
        param = param || {};
        let that = this;
        that.disabledIds = param.disabledIds || '';
        that.selectedIds = param.selectedIds || '';
        that.selectedNames = '';
        that.elem = param.elem;
        that.treeType = param.treeType || 'category';
        that.treeData = [];
        that.layui = null;
        that.success = param.success || null;

        that.init(()=>{
            that.getTreeData();
            $(that.elem).click(()=>{
                that.open(()=>{
                    that.renderTree();
                })
            })
        })
    }

    MultiTreeSelect.prototype.init = function (callback){
        let that = this;
        layui.use(['form', 'tree', 'laytpl'], function() {
            that.layui = layui;
            callback && callback();
        })
    }

    MultiTreeSelect.prototype.open = function (callback){
        let that = this;
        layer.open({
            title: typeConfig[that.treeType].title,
            skin: 'layer-tips-class',
            type: 1,
            area: ['500px', '500px'],
            content: '<div style="height: 100%;overflow-y: auto;" id="multi_tree_select_container"></div>',
            btn: ['确认', '取消'],
            yes: function (index, layero) {
                that.getSelectedData();
                that.success && that.success({
                    selectedIds:that.selectedIds,
                    selectedNames:that.selectedNames,
                })
                layer.close(index);
            },
            btn2: function (index, layero) {
                layer.close(index);
            },
            success:function (){
                that.layui.form.render();
                callback && callback();
            }
        });
    }

    MultiTreeSelect.prototype.getTreeData = function (callback){
        let that = this;
        $.ajax({
            url: ns.url(typeConfig[that.treeType].url),
            data: {},
            type: "POST",
            dataType: "JSON",
            success: function (res) {
                that.treeData = res.data;
                callback && callback();
            }
        });
    }

    MultiTreeSelect.prototype.renderTree = function(){
        let that = this;
        that.layui.tree.render({
            elem: '#multi_tree_select_container',
            data: that.treeData,
            showCheckbox:true,
            id:'#multi_tree_select_container',
        });
        if(that.disabledIds){
            let disabled_id_arr = that.disabledIds.split(',');
            for(let i in disabled_id_arr){
                $("input[name='layuiTreeCheck_"+ disabled_id_arr[i] +"']").prop('disabled', true).next().addClass('layui-checkbox-disbaled layui-disabled').next().addClass('layui-disabled').append('（不可选）');
            }
        }
        if(that.selectedIds){
            let selected_id_arr = that.selectedIds.split(',');
            for(let i in selected_id_arr){
                $("input[name='layuiTreeCheck_"+ selected_id_arr[i] +"']").prop('checked', true).next().addClass('layui-form-checked');
            }
        }
    }

    MultiTreeSelect.prototype.getSelectedData = function (){
        let that = this;
        let selectedTreeData = that.layui.tree.getChecked("#multi_tree_select_container");
        let selectedData = that.getSelectedIdsAndNames(selectedTreeData, that.treeData);
        that.selectedIds = selectedData.id_arr.join(',');
        that.selectedNames = selectedData.name_arr.join('、');
    }

    MultiTreeSelect.prototype.getSelectedIdsAndNames = function(tree_selected, tree_all){
        let name_arr = [];
        let id_arr = [];
        let selected_num = 0;
        for(let i in tree_selected){
            let item_selected = tree_selected[i];
            let item_all = null;
            tree_all.forEach((item)=>{
                if(item.id === item_selected.id){
                    item_all = item;
                    return;
                }
            })
            if(!item_all) throw '对比数据有误';
            let title = item_selected.title;
            id_arr.push(item_selected.id);
            if(item_selected.child_num > 0){
                let res = this.getSelectedIdsAndNames(item_selected.children, item_all.children);
                if(res.selected_num == item_all.child_num){
                    selected_num ++;
                }else{
                    title += '（'+ res.name_arr.join('、') +'）';
                }
                id_arr = id_arr.concat(res.id_arr);
            }else{
                selected_num ++;
            }
            name_arr.push(title);
        }
        return {
            selected_num : selected_num,
            name_arr : name_arr,
            id_arr : id_arr,
        };
    }

    window.MultiTreeSelect = MultiTreeSelect;
})()
var _scope = {
    view: {
        init: function() {
            _scope.view.initEvent();
        },
        initEvent: function() {
            _scope.events.initPageElement();
            _scope.events.bindDimensionalityDragEvent();
            _scope.events.bingLabelDeleteEvent();
            _scope.events.bingReportSubmitEvent();//提交报表
            _scope.events.bingSourceTypeChangeEvent();//选择数据源
            _scope.events.bingInputModelSetting();//用户筛选值弹窗设值
            _scope.events.bingReportDeleteEvent();//用户删除报告
            _scope.events.bindReportMoveEvent();//移动用户报告
            _scope.events.bindReportFolderEvent();//报表目录
            _scope.events.bindReportCopyEvent();//复制用户报告
            _scope.events.editFolderEvent();//弹出目录编辑页面
            _scope.events.bindaddFolderEvent();//添加报表目录
            _scope.events.bindeditFolderEvent();//编辑报表目录
            _scope.events.bindmoveFolderEvent();//移动报表目录
            _scope.events.binddeleteFolderEvent();//编辑报表目录
            _scope.events.bindchangeFieldTypeEvent();//修改维度事件
        },
    },
    events: {
        initPageElement:function () {
            if(typeof(rconfig) != "undefined") {
                $(rconfig.field_enum.method).each(function (k, v) {
                    _scope.vars.method_str = _scope.vars.method_str + "<option value='" + k + "'>" + v + "</option>";
                });
                $(rconfig.field_enum.field_type).each(function (k, v) {
                    if (k != 0) {
                        _scope.vars.field_type_str = _scope.vars.field_type_str + "<option value='" + k + "'>" + v + "</option>";
                    }
                });
                $(rconfig.field_enum.furl_mode).each(function (k, v) {
                    _scope.vars.furl_mode_str = _scope.vars.furl_mode_str + "<option value='" + k + "'>" + v + "</option>";
                });
                $(rconfig.field_enum.display_mode).each(function (k, v) {
                    _scope.vars.display_mode_str = _scope.vars.display_mode_str + "<option value='" + k + "'>" + v + "</option>";
                });
                $(rconfig.field_enum.special).each(function (k, v) {
                    _scope.vars.special_str = _scope.vars.special_str + "<option value='" + k + "'>" + v + "</option>";
                });
                $(rconfig.field_enum.display_chart).each(function (k, v) {
                    _scope.vars.display_chart_str = _scope.vars.display_chart_str + "<option value='" + k + "'>" + v + "</option>";
                });
                $(rconfig.field_enum.sort).each(function (k, v) {
                    _scope.vars.sort_str = _scope.vars.sort_str + "<option value='" + k + "'>" + v + "</option>";
                });
                _scope.vars.default_str = "<option value='0'>否</option><option value='1'>是</option>";
            }
            if(typeof(menusstr) != "undefined"){
                var opts = {
                    data: menusstr,
                    select: '#folder'
                };
                var linkageSel = new LinkageSel(opts);
                _scope.vars.linkageSel = linkageSel;
            }
        },
        bindDimensionalityDragEvent: function() {
            document.addEventListener("dragend", function(event) {
                if (event.target.classList.contains('dimensionality')==true) {
                    _scope.methods.addTableItem('dimensionality_table',event.target.innerHTML);
                    event.target.remove();
                }else if(event.target.classList.contains('items')==true){
                    _scope.methods.addTableItem('items_table',event.target.innerHTML);
                    event.target.remove();
                }
            });
        },
        bingLabelDeleteEvent: function () {
            $('body').on('click','.label-delete-di',function () {
                var label = $(this).parent('td').next('td').text();
                $('.dimensionality-head').closest('p').append("<span class=\"dimensionality label label-primary\" draggable=\"true\">"+label+"</span>");
                $(this).closest('tr').remove();
            });
            $('body').on('click','.label-delete-it',function () {
                var label = $(this).parent('td').next('td').text();
                $('.items-head').closest('p').append("<span class=\"dimensionality label label-primary\" draggable=\"true\">"+label+"</span>");
                $(this).closest('tr').remove();
            });
            $('body').on('click','.label-up',function () {
                _scope.methods.labelUp($(this));
            });
            $('body').on('click','.label-down',function () {
                _scope.methods.labelDown($(this));
            });
            $('body').on('click','.add-item',function () {
                _scope.methods.addNoneTableItem();
            });
        },
        bingReportSubmitEvent:function () {
            $('body').on('click','.report-save',function () {
                $.post("/report/setting/save", $("#report_form").serializeArray() ,
                    function(result){
                        if(result.code==0){
                            location.href=result.url;
                        }else{
                            alert(result.message);
                        }
                });
            });
        },
        bingSourceTypeChangeEvent:function () {
            $('body').on('change','#source_type',function () {
                var source_type = $(this).val();
                $.ajax({
                    type: "POST",
                    url: "/report/setting/getschemas",
                    data: {source_type:source_type},
                    async:false,
                    success: function(res){
                        if(res.code==0){
                            $("#source_name" ).autocomplete({
                                source: res.data
                            });
                        }else{
                            alert(res.message);
                        }
                    }
                });

            });
            $('body').on('keydown','#source_name',function () {
                var value = $(this).val();
                if(event.which==190){
                    var tables = value.split(".");
                    $.ajax({
                        type: "POST",
                        url: "/report/setting/gettables",
                        data: {schema_name:tables[0]},
                        async:false,
                        success: function(res){
                            if(res.code==0){
                                $("#source_name" ).autocomplete({
                                    source: res.data
                                },{
                                    select: function( event, ui ) {
                                        $.ajax({
                                            type: "POST",
                                            url: "/report/setting/gettableinfo",
                                            data: {table_name: ui.item.value},
                                            async: false,
                                            success: function (res) {
                                                if(res.code==0){
                                                    _scope.vars.dimensionality_str = '<span class=\"dimensionality-head label label-primary\">*</span>';
                                                    i = 0;
                                                    $(res.data.dimension).each(function (k,v) {
                                                        if(i%10==0&&i!=0){
                                                            _scope.vars.dimensionality_str = _scope.vars.dimensionality_str+"<span class=\"dimensionality label label-primary\" draggable=\"true\">"+v+"</span><br/>";
                                                        }else{
                                                            _scope.vars.dimensionality_str = _scope.vars.dimensionality_str+"<span class=\"dimensionality label label-primary\" draggable=\"true\">"+v+"</span>";
                                                        };
                                                        i++;
                                                    });
                                                    $('.dimensionality-span').html(_scope.vars.dimensionality_str);
                                                    _scope.vars.items_str = '<span class="items-head label label-primary">*</span>';
                                                    i = 0;
                                                    $(res.data.quota).each(function (k,v) {
                                                        if(i%10==0&&i!=0){
                                                            _scope.vars.items_str = _scope.vars.items_str+"<span class=\"items label label-primary\" draggable=\"true\">"+v+"</span><br/>";
                                                        }else{
                                                            _scope.vars.items_str = _scope.vars.items_str+"<span class=\"items label label-primary\" draggable=\"true\">"+v+"</span>";
                                                        };
                                                        i++;
                                                    });
                                                    $('.items-span').html(_scope.vars.items_str);
                                                }else{
                                                    alert(res.message);
                                                }
                                            }
                                        });
                                    }
                                });
                            }else{
                                alert(res.message);
                            }
                        }
                    });
                }
            });
        },
        bingInputModelSetting :function () {
            $('body').on('click','.btn-setting',function () {
                var input_default = $(this).parent('td').find('.input_default').val();
                var input_user = $(this).parent('td').find('.input_user').val();
                $('#model_input_default').val(input_default);
                $('#model_input_user').val(input_user);
                $('#modal-form').show();
                $('#modal-form').addClass('in');
                $('#modal-backdrop').show();
                $('#modal-backdrop').addClass('in');
                _scope.events.comfirmValue($(this).parent('td'));
            });
            $('body').on('click','.close',function () {
                $('#model_input_default').val('');
                $('#model_input_user').val('');
                $('#modal-form').hide();
                $('#modal-form').removeClass('in');
                $('#modal-folder').hide();
                $('#modal-folder').removeClass('in');
                $('#modal-backdrop').hide();
                $('#modal-backdrop').removeClass('in');
            });
        },
        comfirmValue:function(ele){
            $('body').on('click','.comfirm_value',function () {
                var input_default = $('#model_input_default').val();
                var input_user = $('#model_input_user').val();
                ele.find('.input_default').val(input_default);
                ele.find('.input_user').val(input_user);
                $('#modal-form').hide();
                $('#modal-form').removeClass('in');
                $('#modal-backdrop').hide();
                $('#modal-backdrop').removeClass('in');
            });
        },
        bingReportDeleteEvent:function () {
            $('body').on('click','.report-delete',function () {
                mid = $('input[name="mid"]').val();
                rid = $('input[name="rid"]').val();
                $.ajax({
                    type: "POST",
                    url: "/report/setting/delete",
                    data: {mid: mid,rid: rid},
                    success: function (res) {
                        if(res.code==0){
                            location.href = res.url;
                        }else{
                            alert(res.message);
                        }
                    }
                });
            });
        },
        bindReportMoveEvent:function () {
            $('body').on('click','.report-move',function () {
                $('#modal-folder').show();
                $('#modal-folder').addClass('in');
                $('#modal-backdrop').show();
                $('#modal-backdrop').addClass('in');

                $('.changeFolder').show();
                $('.addFolder').hide();
                $('.editFolder').hide();
                $('.moveFolder').hide();
                $('.deleteFolder').hide();
                $('.modal-name').hide();
            });
        },
        bindReportFolderEvent:function () {
            $('.changeFolder').click(function() {
                var rid = $('input[name="rid"]').val();
                var price = _scope.vars.linkageSel.getSelectedData('price');
                if(price){
                    $.ajax({
                        type: "POST",
                        url: "/report/setting/changefolder",
                        data: {mid: price,rid: rid},
                        success: function (res) {
                            if(res.code==0){
                                location.href = res.url;
                            }else{
                                alert(res.message);
                            }
                        }
                    });
                }else{
                    alert("请选择根节点");
                }
            });
        },
        bindReportCopyEvent:function () {
            $('body').on('click','.report-copy',function () {
                var rid = $('input[name="rid"]').val();
                $('input[name="rid"]').val(0);
                $('input[name="postdata[dimensionality][fid][]"]').val('');
                $('input[name="postdata[items][fid][]"]').val('');
                $('.tab'+rid).after($('.tab'+rid).clone());
            });
        },
        editFolderEvent:function () {
            //阻止浏览器默认右键点击事件
            $("body").bind("contextmenu", function(){
                return false;
            });
            $("#side-menu a").mousedown(function(e) {
                var mid = $(this).attr('data-mid');
                $('.folder_id').val(mid);
                var name = $.trim($(this).text());
                var display_order = $(this).attr("data-sort");

                if (3 == e.which) {//右键
                    $('input[name="name"]').val(name);
                    $('input[name="display_order"]').val(display_order);

                    $('#modal-folder').show();
                    $('#modal-folder').addClass('in');
                    $('#modal-backdrop').show();
                    $('#modal-backdrop').addClass('in');

                    $('.changeFolder').hide();
                    $('.addFolder').show();
                    $('.editFolder').show();
                    $('.moveFolder').show();
                    $('.deleteFolder').show();
                    $('.modal-name').show();
                }
            });
        },
        bindaddFolderEvent:function () {
            $('body').on('click','.addFolder',function () {
                var mid = $('.folder_id').val();
                var name = $('.model-name').val();
                $.ajax({
                    type: "POST",
                    url: "/report/menu/add",
                    data:{mid:mid,name:name},
                    success:function (res) {
                        if(res.code==0){
                            location.reload();
                        }else{
                            alert(res.message);
                        }
                    }
                });
            });
        },
        bindeditFolderEvent:function () {
            $('body').on('click','.editFolder',function () {
                var mid = $('.folder_id').val();
                var name = $('.model-name').val();
                var display_order = $("input[name='display_order']").val();

                $.ajax({
                    type: "POST",
                    url: "/report/menu/edit",
                    data:{mid:mid,name:name,display_order:display_order},
                    success:function (res) {
                        if(res.code==0){
                            location.reload();
                        }else{
                            alert(res.message);
                        }
                    }
                });
            });
        },
        bindmoveFolderEvent:function () {
            $('body').on('click','.moveFolder',function () {
                var mid = $('.folder_id').val();
                var fid = _scope.vars.linkageSel.getSelectedArr();
                $.ajax({
                    type: "POST",
                    url: "/report/menu/move",
                    data:{mid:mid,fid:fid},
                    success:function (res) {
                        if(res.code==0){
                            location.reload();
                        }else{
                            alert(res.message);
                        }
                    }
                });
            });
        },
        binddeleteFolderEvent:function () {
            $('body').on('click','.deleteFolder',function () {
                var mid = $('.folder_id').val();
                $.ajax({
                    type: "POST",
                    url: "/report/menu/delete",
                    data:{mid:mid},
                    success:function (res) {
                        if(res.code==0){
                            location.reload();
                        }else{
                            alert(res.message);
                        }
                    }
                });
            });
        },
        bindchangeFieldTypeEvent:function () {
            $('body').on('change','.select_field_type,.select_input_switch',function () {
                self =$(this);
                field_tpye = self.closest('tr').find('.select_field_type').val();
                input_switch = self.closest('tr').find('.select_input_switch').val();
                if(field_tpye!=3&&input_switch==1){
                    self.closest('tr').find('.btn-primary').attr('disabled',false);
                    self.closest('tr').find('.btn-primary').addClass('btn-setting');
                }else{
                    self.closest('tr').find('.btn-primary').attr('disabled',true);
                    self.closest('tr').find('.btn-primary').removeClass('btn-setting');
                }
            });
        },
    },
    methods: {
        addTableItem: function(table,field) {
            if(table=='dimensionality_table'){
                var html =  "<tr class=\"dimensionality_tr\">\n" +
                    "<td><span class=\"label label-danger label-delete-di\" style=\"cursor: pointer;margin-right: 2px;\">删除</span><span class=\"label label-info label-up\" style=\"cursor: pointer;margin-right: 2px;\">上移</span><span class=\"label label-primary label-down\" style=\"cursor: pointer;margin-right: 2px;\">下移</span></td>" +
                    "<td><input name=\"postdata[dimensionality][field_name][]\" type='hidden' value='"+field+"'>"+field+"</td>" +
                    "<td><input class=\"form-control\" name=\"postdata[dimensionality][field_name_cn][]\" type='text'></td>" +
                    "<td><select class=\"form-control select_field_type\" name=\"postdata[dimensionality][field_type][]\">"+_scope.vars.field_type_str+"</select></td>" +
                    "<td><select class=\"form-control\" name=\"postdata[dimensionality][furl_mode][]\">"+_scope.vars.furl_mode_str+"</select></td>" +
                    "<td><select class=\"form-control\" name=\"postdata[dimensionality][sort][]\">"+_scope.vars.sort_str+"</select></td>" +
                    "<td><select class=\"form-control select_input_switch\" name=\"postdatadimensionality][input_switch][]\">"+_scope.vars.default_str+"</select></td>" +
                    "<td><a class=\"btn btn-primary btn-setting\" href=\"javascript:void(0);\">设置</a><input name=\"postdata[dimensionality][input_default][]\" class=\"input_default\" type=\"hidden\" value=\"\"><input name=\"postdata[dimensionality][input_user][]\" class=\"input_user\" type=\"hidden\" value=\"\"></td>" +
                    "<td><select class=\"form-control\" name=\"postdata[dimensionality][row_filter][]\">"+_scope.vars.default_str+"</select></td>" +
                    "<td><select class=\"form-control\" name=\"postdata[dimensionality][special][]\">"+_scope.vars.special_str+"</td>" +
                    "<td><input class=\"form-control\" name=\"postdata[dimensionality][special_value][]\" type='text'></td>" +
                    "<td><input class=\"form-control\" name=\"postdata[dimensionality][tips][]\" type='text'></td>" +
                    "</tr>";
            }else if(table=='items_table'){
                var html =  "<tr class=\"items_tr\"><input type='hidden' name=\"postdata[items][field_type][]\" value='0'/>" +
                    "<td><span class=\"label label-danger label-delete-it\" style=\"cursor: pointer;margin-right: 2px;\">删除</span><span class=\"label label-info label-up\" style=\"cursor: pointer;margin-right: 2px;\">上移</span><span class=\"label label-primary label-down\" style=\"cursor: pointer;margin-right: 2px;\">下移</span></td>" +
                    "<td><input name=\"postdata[items][field_name][]\" type='hidden' value='"+field+"'>"+field+"</td>" +
                    "<td><input class=\"form-control w60\" name=\"postdata[items][field_name_cn][]\" type='text'></td>" +
                    "<td><input class=\"form-control w60\" name=\"postdata[items][parent_name][]\" type='text'></td>" +
                    "<td><select class=\"form-control\" name=\"postdata[items][method][]\">"+_scope.vars.method_str+"</select></td>" +
                    "<td><input class=\"form-control\" name=\"postdata[items][formula][]\" type='text'></td>" +
                    "<td><select class=\"form-control\" name=\"postdata[items][display_mode][]\">"+_scope.vars.display_mode_str+"</select></td>" +
                    "<td><input class=\"form-control w60\" name=\"postdata[items][display_mode_value][]\" type='text'></td>" +

                    "<td><select class=\"form-control\" name=\"postdata[items][sort][]\">"+_scope.vars.sort_str+"</select></td>" +
                    "<td><select class=\"form-control\" name=\"postdata[items][input_switch][]\">"+_scope.vars.default_str+"</select></td>" +
                    "<td><select class=\"form-control\" name=\"postdata[items][display_chart][]\">"+_scope.vars.display_chart_str+"</select></td>" +

                    "<td><select class=\"form-control\" name=\"postdata[items][special][]\">"+_scope.vars.special_str+"</td>" +
                    "<td><input class=\"form-control\" name=\"postdata[items][special_value][]\" type='text'></td>" +
                    "<td><input class=\"form-control\" name=\"postdata[items][tips][]\" type='text'></td>" +
                    "</tr>";
            }

            $('.'+table+' tbody').append(html);
        },
        addNoneTableItem: function(table,field) {
            var html =  "<tr class=\"items_tr\"><input type='hidden' name=\"postdata[items][field_type][]\" value='0'/>" +
                "<td><span class=\"label label-danger label-delete-it\" style=\"cursor: pointer;margin-right: 2px;\">删除</span><span class=\"label label-info label-up\" style=\"cursor: pointer;margin-right: 2px;\">上移</span><span class=\"label label-primary label-down\" style=\"cursor: pointer;margin-right: 2px;\">下移</span></td>" +
                "<td><input class=\"form-control w60\" name=\"postdata[items][field_name][]\" type='text' value=''></td>" +
                "<td><input class=\"form-control w60\" name=\"postdata[items][field_name_cn][]\" type='text'></td>" +
                "<td><input class=\"form-control w60\" name=\"postdata[items][parent_name][]\" type='text'></td>" +
                "<td><select class=\"form-control\" name=\"postdata[items][method][]\">"+_scope.vars.method_str+"</select></td>" +
                "<td><input class=\"form-control\" name=\"postdata[items][formula][]\" type='text'></td>" +
                "<td><select class=\"form-control\" name=\"postdata[items][display_mode][]\">"+_scope.vars.display_mode_str+"</select></td>" +
                "<td><input class=\"form-control w60\" class=\"form-control\" name=\"postdata[items][display_mode_value][]\" type='text'></td>" +

                "<td><select class=\"form-control\" name=\"postdata[items][sort][]\">"+_scope.vars.sort_str+"</select></td>" +
                "<td><select class=\"form-control\" name=\"postdata[items][input_switch][]\">"+_scope.vars.default_str+"</select></td>" +
                "<td><select class=\"form-control\" name=\"postdata[items][display_chart][]\">"+_scope.vars.display_chart_str+"</select></td>" +

                "<td><select class=\"form-control\" name=\"postdata[items][special][]\">"+_scope.vars.special_str+"</td>" +
                "<td><input class=\"form-control\" name=\"postdata[items][special_value][]\" type='text'></td>" +
                "<td><input class=\"form-control\" class=\"form-control\" name=\"postdata[items][tips][]\" type='text'></td>" +
                "</tr>";

            $('.items_table tbody').append(html);
        },
        removeTableItem: function (event) {
            event.target.remove();
        },
        labelUp: function (ele) {
            var $tr = ele.parents("tr");
            if ($tr.index() != 0) {
                $tr.fadeOut().fadeIn();
                $tr.prev().before($tr);
            }

        },
        labelDown:function (ele) {
            var len = ele.length;
            var $tr = ele.parents("tr");
            if ($tr.index() != len - 2) {
                $tr.fadeOut().fadeIn();
                $tr.next().after($tr);
            }

        },

    },
    vars: {
        'field_type_str':'',
        'furl_mode_str':'',
        'method_str':'',
        'display_mode_str':'',
        'display_chart_str':'',
        'special_str':'',
        'input_switch_str':'',
        'default_str':'',
        'dimensionality_str':'',
        'items_str':'',
        'linkageSel':'',
        'sort_str':'',
    }
};
// 初始化
$(function() {
    _scope.view.init();
});

/**
 * javascript 根据配置生成对应筛选值
 *
 * @copyright
 * Copyright (C) 2018 Gerrant jackyang0565@163.com
 *
 */
function Filters(opts) {
    // 实例属性
    this.opts = opts;
    this.html = '';//渲染内容
    this.renderpos	= '#filter_pos';//渲染位置
    this.time_type = 'date';//时间插件格式
    this.colnum	= 0;//栅格值
    this.dimensionarr = [];
    this.select_v = 0;
    this.chart_key = [];
    that = this;

    //分派处理
    this.deal = function () {
        //图形指标
        if(typeof chartType !='undefined') {
            if (chartType != 0 && typeof chartData.yValue != 'undefined') {
                this.chart_key = [];
                $.each(chartData.yValue, function (key, value) {
                    that.chart_key.push(key);
                });
            }
        }
        //处理组件
        $.each(that.opts,function (key,value) {
            var handle_html = that.handle(value);
            if(typeof handle_html!='undefined'){
                that.html = that.html + handle_html;
            }
        });
    };

    //渲染内容
    this.render = function () {
        html_header = "<div class=\"form-group\">";
        html_bottom = "</div>";
        $(this.renderpos).html(html_header+that.html);
    };

    //处理
    this.handle = function (elem) {
        switch(elem['field_type'])
        {
            case 0:
                return that.quotawidget(elem);
                break;
            case 1:
                return that.dimensionwidget(elem);
                break;
            case 2:
                return that.filterwidget(elem);
                break;
            case 3:
                return that.timewidget(elem)+that.querywidget();
                break;
            default:
                alert('没有该组件');
        }
    };

    //处理维度组件
    this.dimensionwidget = function (elem) {
        //处理维度数据
        var select_show = [];
        $.each(elem.value,function (k,v) {
            if(v.indexOf(":") >= 0){
                temp = v.split(':');
                if ($.inArray(temp[1], select_show) === -1){
                    select_show.push(temp[1]);
                }
            }else{
                if ($.inArray(v, select_show) === -1) {
                    select_show.push(v);
                }
            }
        });
        //设置栅格
        that.colnum = that.colnum + 2;
        that.select_v = that.select_v + 1;
        //默认选中
        if(elem['default']=='展开'){
            var value_html = "<option value=\"展开\" selected>展开</option><option value=\"收拢\">收拢</option>";
        }else if(elem['default']=='收拢'){
            var value_html = "<option value=\"展开\">展开</option><option value=\"收拢\" selected>收拢</option>";
        }else{
            var value_html = "<option value=\"展开\">展开</option><option value=\"收拢\">收拢</option>";
        }
        $.each(select_show,function (k,v) {
            if(elem.default.indexOf(v) >= 0){
                value_html = value_html + "<option value=\""+v+"\" selected>"+v+"</option>";
            }else{
                value_html = value_html + "<option value=\""+v+"\">"+v+"</option>";
            }
        });
        var html = "    <div class=\"col-sm-3 filter-menu\"><label class=\"control-label\">"+elem['field_name']+":</label><input type=\"hidden\" name=\"fil_"+that.select_v+"\" value=\""+elem.default+"\"/>\n" +
            "<select class=\"selectpicker\" data-style=\"btn-white\" id=\"fil_"+that.select_v+"\" name=\""+elem['field']+"\" multiple data-live-search=\"true\" data-live-search-placeholder=\"搜索\" title=\"选择"+elem['field_name']+"\" data-actions-box=\"true\">" +
            value_html +
            "        </select>\n" +
            "    </div>\n";
        return this.checkhr(html);
    };

    //处理过滤组件
    this.filterwidget = function (elem) {
        //处理过滤数据
        var select_show = [];
        $.each(elem.value,function (k,v) {
            if(v.indexOf(":") >= 0){
                temp = v.split(':');
                if ($.inArray(temp[1], select_show) === -1){
                    select_show.push(temp[1]);
                }
            }else{
                if ($.inArray(v, select_show) === -1) {
                    select_show.push(v);
                }
            }
        });
        //设置栅格
        that.colnum = that.colnum + 2;
        that.select_v = that.select_v + 1;
        //默认选中
        if(elem['default']=='展开'){
            var value_html = "<option value=\"展开\" selected>展开</option><option value=\"收拢\">收拢</option>";
        }else if(elem['default']=='收拢'){
            var value_html = "<option value=\"展开\">展开</option><option value=\"收拢\" selected>收拢</option>";
        }else{
            var value_html = "<option value=\"展开\">展开</option><option value=\"收拢\">收拢</option>";
        }
        $.each(select_show,function (k,v) {
            if(elem.default.indexOf(v) >= 0){
                value_html = value_html + "<option value=\""+v+"\" selected>"+v+"</option>";
            }else{
                value_html = value_html + "<option value=\""+v+"\">"+v+"</option>";
            }
        });
        var html = "    <div class=\"col-sm-3 filter-menu\"><label class=\"control-label\">"+elem['field_name']+":</label><input type=\"hidden\" name=\"fil_"+that.select_v+"\" value=\""+elem.default+"\"/>\n" +
            "<select class=\"selectpicker\" data-style=\"btn-white\" id=\"fil_"+that.select_v+"\" name=\""+elem['field']+"\" multiple data-live-search=\"true\" data-live-search-placeholder=\"搜索\" title=\"选择"+elem['field_name']+"\" data-actions-box=\"true\">" +
            value_html +
            "        </select>\n" +
            "    </div>\n";
        return this.checkhr(html);
    };

    //处理时间组件
    this.timewidget = function (elem) {
        //设置栅格
        that.colnum = that.colnum + 7;
        //设置插件格式
        if(elem['time_type']=='date'){
            that.time_type = 'date';
        }else{
            that.time_type = 'datetime';
        }
        //判断是否有时间对比参数
        if(elem['vs']==1){
            var html_check = '<input type="checkbox" name="vs" value="1" style="position: absolute; opacity: 0;" checked>\n';
        }else{
            var html_check = '<input type="checkbox" name="vs" value="1" style="position: absolute; opacity: 0;">\n';
        }
        //判断是否有时间汇总参数
        if(elem['gather']==1){
            var html_gather = '<input type="checkbox" name="gather" value="1" style="position: absolute; opacity: 0;" checked>\n';
        }else{
            var html_gather = '<input type="checkbox" name="gather" value="1" style="position: absolute; opacity: 0;">\n';
        }

        var html = "    <div class=\"col-sm-6\">\n" +
            "        <label class=\"control-label\">开始:</label><input id=\"start_date\" type=\"text\" class=\"form-control date\" name=\"start_date\" value=\""+elem['default']['time_start']+"\">\n" +
            "        <label class=\"control-label\">结束:</label><input id=\"end_date\" type=\"text\" class=\"form-control date\" name=\"end_date\" value=\""+elem['default']['time_end']+"\">\n" +
            "        <label class=\"checkbox-inline i-checks\" style=\"margin-left: 20px;\">\n" +
            "            <div class=\"icheckbox_square-green\" style=\"position: relative;\">\n" +
            html_check +
            "                <ins class=\"iCheck-helper\" style=\"position: absolute; top: 0%; left: 0%; display: block; width: 100%; height: 100%; margin: 0px; padding: 0px; background: rgb(255, 255, 255); border: 0px; opacity: 0;\"></ins>\n" +
            "            </div>时间对比" +
            "        </label>\n" +
            "        <label class=\"checkbox-inline i-checks\">\n" +
            "            <div class=\"icheckbox_square-green\" style=\"position: relative;\">\n" +
            html_gather +
            "                <ins class=\"iCheck-helper\" style=\"position: absolute; top: 0%; left: 0%; display: block; width: 100%; height: 100%; margin: 0px; padding: 0px; background: rgb(255, 255, 255); border: 0px; opacity: 0;\"></ins>\n" +
            "            </div>时间汇总" +
            "        </label>\n" +
            "<a type=\"button\" class=\"btn btn-normal btn-forward\"><<</a>\n<a type=\"button\" class=\"btn btn-normal btn-backward\">>></a>\n" +
            "    </div>\n";
        html = this.checkhr(html);
        return html;
    };

    //处理查询组件
    this.querywidget = function (elem) {
        //设置栅格
        that.colnum = that.colnum + 6;
        //图表类型
        var html_chart_type = "    <div class=\"col-sm-6 col-charts\">\n" +
            "<label class=\"control-label\">图表类型:</label><select class=\"form-control chart\" name=\"chart_type\" id=\"chart_type\">" +
            "<option value=\"0\">无图形</option><option value=\"1\">饼图</option><option value=\"2\">线图</option><option value=\"3\">柱图</option>" +
            "        </select>\n";
        //图表指标
        value_html = '';
        if(typeof chartType != 'undefined'){
            if(typeof chartData.yValue != 'undefined'){
                $.each(chartData.yValue,function (k,v) {
                    if($.inArray(k,chartData['defaultYData'])>=0){
                        value_html = value_html + "<option value=\""+k+"\" selected>"+k+"</option>";
                    }else{
                        value_html = value_html + "<option value=\""+k+"\">"+k+"</option>";
                    }
                });
            }
        }

        var html_chart = "<label class=\"control-label\">图表指标:</label><select class=\"selectpicker\" data-style=\"btn-white\" name=\"图形指标\" multiple id=\"fil_chart\" data-live-search=\"true\" data-live-search-placeholder=\"搜索\" title=\"选择图形指标\" data-actions-box=\"true\">" +
            value_html +
            "        </select>\n";

        var html_bottom = "    <a type=\"button\" class=\"btn btn-normal2 download\">下载</a>\n" +
            "    <a type=\"button\" class=\"btn btn-normal report\">查看</a>\n" +
            "</div>";
        html = this.checkhr(html_chart_type+html_chart+html_bottom);
        return html;
    };

    //处理指标筛选
    this.quotawidget = function (elem) {
        //设置栅格
        that.colnum = that.colnum + 2;
        var html = "    <div class=\"col-sm-3 filter-menu\">" +
            "<label class=\"control-label\">"+elem['field_name']+":</label><input class=\"form-control item-filter\" name=\""+elem.field+"\" type=\"text\" value=\""+elem.default+"\" style=\"width: 70%;min-width: 60px;display: inline-block;\"/>" +
            "    </div>\n";
        return this.checkhr(html);
    };

    //添加分割线
    this.checkhr = function (html) {
        if(that.colnum>=12){
            that.colnum = 0;
            var hr_html = "</div>\n" +
                "<div class=\"hr-line-dashed\"></div>\n" +
                "<div class=\"form-group\">";
            return html+hr_html;
        }else{
            return html;
        }
    };

    //处理组件事件
    this.event = function () {
        //时间插件
        if(typeof(that.time_type)!='undefined'){
            if(that.time_type=='date'){
                laydate.render({
                    elem: '#start_date'
                    ,type: that.time_type
                    ,format: 'yyyyMMdd'
                });
                laydate.render({
                    elem: '#end_date'
                    ,type: that.time_type
                    ,format: 'yyyyMMdd'
                });
            }else{
                laydate.render({
                    elem: '#start_date'
                    ,type: that.time_type
                });
                laydate.render({
                    elem: '#end_date'
                    ,type: that.time_type
                });
            };
        };
        //checked按钮
        $(".i-checks").iCheck({checkboxClass:"icheckbox_square-green",radioClass:"iradio_square-green",});
        //设置下拉框
        $('.selectpicker').selectpicker('refresh');
        $(".selectpicker").on('shown.bs.select',function(){

            var that2 = $(this);

            pre_arr = that2.attr('id').split('_');
            current_id = pre_arr[1];
            pre_id = pre_arr[1] - 1;
            var all_field;
            if(pre_id>0){
                pre_value = $('#fil_'+pre_id).val();
                current_value = $('#fil_'+current_id).val();
                current_name = $('#fil_'+current_id).attr('name');
                //选中
                if(pre_value!=null&&pre_value.indexOf("展开")==-1&&pre_value.indexOf("收拢")==-1){
                    $.each(that.opts,function (k,v) {
                        if(v['field']==current_name){
                            all_field = v.value;
                        }
                    });
                    var deviceStr="<option value=\"展开\">展开</option><option value=\"收拢\">收拢</option>" ;
                    var select_show = [];
                    $.each(all_field,function (fk,fv) {
                        fv_arr = fv.split(':');
                        if(pre_value.indexOf(fv_arr[0])>=0){
                            if ($.inArray(fv_arr[1], select_show) === -1) {
                                select_show.push(fv_arr[1]);
                            }
                        }
                    });
                    $.each(select_show,function (sk,sv) {
                        deviceStr+="<option value=\""+sv+"\">"+sv+"</option>";
                    });
                //全部
                }else{
                    $.each(that.opts,function (k,v) {
                        if(v['field']==current_name){
                            all_field = v.value;
                        }
                    });
                    var deviceStr="<option value=\"展开\">展开</option><option value=\"收拢\">收拢</option>" ;
                    var select_show = [];
                    $.each(all_field,function (fk,fv) {
                        fv_arr = fv.split(':');
                        if ($.inArray(fv_arr[1], select_show) === -1) {
                            select_show.push(fv_arr[1]);
                        }
                    });
                    $.each(select_show,function (sk,sv) {
                        deviceStr+="<option value=\""+sv+"\">"+sv+"</option>";
                    });
                }
                $(this).html("");
                $(this).append(deviceStr);
                $(this).val(current_value);
                $(this).selectpicker('refresh');
            }
        });
        //选中聚拢和展开无法再勾选其它
        $(".selectpicker").on('change',function(){
            var that2 = $(this);

            var id = that2.attr('id');

            pre_arr = id.split('_');
            if(pre_arr[1]>0){
                pre_select_value = $("input[name="+id+"]").val();
                select_value = that2.val();
                click_value = '';
                if(select_value!=null) {
                    $.each(select_value, function (k, v) {

                        if (pre_select_value.indexOf(v) == -1) {
                            click_value = v;
                        }
                    });
                };
                if(select_value!=null) {
                    if(click_value=="展开"){
                        $(this).val("展开");
                    }else if(click_value=="收拢"){
                        $(this).val("收拢");
                    }else{
                        finish_arr = [];
                        $.each(select_value,function (k,v) {
                            if(v!="展开"&&v!="收拢"){
                                finish_arr.push(v);
                            }
                        });
                        $(this).val(finish_arr);
                    }
                }
                $("input[name="+id+"]").val($(this).val());
                $(this).selectpicker('refresh');
            }
        });
    };

}

Filters.prototype = {
    //constructor属性始终指向创建当前对象的构造函数
    //因为原型被替换，所以需要恢复construtor的默认指向
    constructor: Filters,
    show: function () {
        this.deal();
        this.render();
        this.event();
    },
};

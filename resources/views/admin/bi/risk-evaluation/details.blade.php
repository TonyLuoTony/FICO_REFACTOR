@extends('admin.index')
@section('meta')
    <style>
        .table th, .table td {
            text-align: left;
            vertical-align: middle !important;
        }
    </style>
    @css('css/plugins/blueimp/css/blueimp-gallery.min.css')
    @css('css/animate.css')
@endsection
@section('content')

    <div class="col-lg-9" style="margin-left: -15px">
        <div class="ibox float-e-margins">
            <div class="ibox-title">
                <h2>普租情报详情</h2>
                <div class="ibox-tools">
                    <a class="collapse-link">
                        <i class="fa fa-chevron-up"></i>
                    </a>
                </div>
            </div>

            <div class="ibox-content">
                <div class="row">
                    <div class="col-md-4 col-xs-12">
                        <div style="margin-top: 5px;">
                            <span style="font-weight:600">
                                信息编号:
                            </span>&nbsp;&nbsp;
                            <span>{{str_pad($info_res->id, 5, "0", STR_PAD_LEFT)}}</span>&nbsp;&nbsp;
                            <span style="color:red">({{$info_res->check_status}})</span>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 col-xs-12">
                        <div style="margin-top: 5px;">
                            <span style="font-weight:600;">
                                楼盘:
                            </span>&nbsp;&nbsp;
                            <span>{{$info_res->city.'-'.$info_res->block.'-'.$info_res->xiaoqu_name}}</span>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 col-xs-12">
                        <div style="margin-top: 5px;">
                            <span style="font-weight:600;">
                                楼盘:
                            </span>&nbsp;&nbsp;
                            <span>{{$info_res->building_code}}</span>
                        </div>
                    </div>
                    <div class="col-md-4 col-xs-12">
                        <div style="margin-top: 5px;">
                            <span style="font-weight:600;">
                                卧室数:
                            </span>&nbsp;&nbsp;
                            <span>{{$info_res->bedroom_num}}</span>
                        </div>
                    </div>
                    <div class="col-md-4 col-xs-12">
                        <div style="margin-top: 5px;">
                            <span style="font-weight:600;">装修情况:</span>&nbsp;&nbsp;
                            <span>{{$info_res->room_status ? $room_status_list[$info_res->room_status] : ''}}</span>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 col-xs-12">
                        <div style="margin-top: 5px;">
                            <span style="font-weight:600;">楼层:</span>&nbsp;&nbsp;<span>{{$info_res->floor ? $floor_list[$info_res->floor] : ''}}</span>
                        </div>
                    </div>

                    <div class="col-md-4 col-xs-12">
                        <div style="margin-top: 5px;">
                            <span style="font-weight:600;">公卫数:</span>&nbsp;&nbsp;<span>{{$info_res->bef_gw}}</span>
                        </div>
                    </div>
                    <div class="col-md-4 col-xs-12">
                        <div style="margin-top: 5px;">
                            <span style="font-weight:600;">环境情况:</span>&nbsp;&nbsp;<span>{{$info_res->enviorment_level ? $enviorment_level_list[$info_res->enviorment_level] : ''}}</span>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 col-xs-12">
                        <div style="margin-top: 5px;">
                            <span style="font-weight:600;">建筑面积:</span>&nbsp;&nbsp;<span>{{$info_res->area}}</span>
                        </div>
                    </div>
                    <div class="col-md-4 col-xs-12">
                        <div style="margin-top: 5px;">
                            <span style="font-weight:600;">独卫数:</span>&nbsp;&nbsp;<span>{{$info_res->bef_dw}}</span>
                        </div>
                    </div>
                    <div class="col-md-4 col-xs-12">
                        <div style="margin-top: 5px;">
                            <span style="font-weight:600;">价格来源:</span>&nbsp;&nbsp;<span>{{$info_res->source}}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-9" style="margin-left: -15px">
        <div class="ibox float-e-margins">
            <div class="ibox-content">
                    {!! Form::open(['url' => action('Admin\BI\RiskEvaluationController@anyInputInfo'),'id'=>'info_form','method' => 'post','class'=>'form-horizontal']) !!}
                    <div class="form-group">
                        <label class="col-sm-2 control-label">情报价格:</label>
                            <span class="col-sm-1 control-label" style="text-align: left;">{{$info_res->sale_price}}</span>
                        </div>
                        @if(count($pic_arr)>0)
                            <div class="form-group">
                                <label class="col-sm-2 control-label">情报图片:</label>
                                <?php
                                    $pic_url = '';
                                    foreach ($pic_arr as $pic_val){
                                        if($pic_url == ''){
                                            $pic_url = 'http://'.$pic_val;
                                ?>
                                    <span class="col-sm-1 control-label" style="text-align: left;">
                                        <a href="{{$pic_url}}" data-gallery="">查看</a>
                                    </span>
                                <?php
                                        break;
                                        }
                                    }
                                ?>
                                <div style="display: none;">
                                    <?php
                                    $img_first_url = '';
                                    foreach ($pic_arr as $pic_val)
                                    {
                                        $img_first_url = 'http://'.$pic_val;
                                        if($pic_url != $img_first_url){
                                        ?>
                                            <a href="<?php echo $img_first_url;?>" title="Image from Unsplash" data-gallery=""></a>
                                        <?php
                                        }
                                    }
                                    ?>
                                </div>
                            </div>
                        @endif
                    @if($info_res->check_status == '待审核')
                        <div class="hr-line-dashed"></div>
                        <div class="form-group"><label class="col-sm-2 control-label">审核价格:</label>
                            <div class="col-sm-10">
                                <?php
                                echo Form::number('check_price', $info_res->sale_price,['id' => 'check_price', 'class' => 'form-control input-lg', 'placeholder' => '审核价格','style'=>'max-width: 400px']);
                                ?>
                            </div>
                        </div>
                        <div class="hr-line-dashed"></div>
                        <div class="form-group">
                            <label class="col-sm-2 control-label">备注:</label>
                            <div class="col-sm-10">
                                <?php
                                echo Form::textarea('check_note', '',['rows'=>'4', 'cols'=>'20', 'class' => 'form-control input-lg', 'placeholder' => '备注','style'=>'max-width: 400px']);
                                ?>
                            </div>
                        </div>
                        <div class="hr-line-dashed"></div>
                        <?php
                        echo Form::hidden('info_id', $info_res->id);
                        ?>
                    @else
                        @if($info_res->check_status == '已入库')
                            <div class="form-group">
                                <label class="col-sm-2 control-label">审核价格:</label>
                                <span class="col-sm-1 control-label" style="text-align: left;">{{$info_res->check_price}}</span>
                            </div>
                        @endif
                        <div class="form-group">
                            <label class="col-sm-2 control-label"><span style="text-align: right;padding-left: 24px">备注:</span></label>
                            <span class="col-sm-8 control-label" style="text-align: left;">{{$info_res->check_note}}</span>
                        </div>
                        <div class="hr-line-dashed"></div>
                    @endif

                    <div style="text-align: center;">
                        @if($info_res->check_status == '待审核' && (can('BI_普租情报_审核')))
                            <button type="button" class="btn btn-danger" data-dismiss="modal" onclick="cancleRisk()">作废</button>
                            &nbsp;&nbsp;&nbsp;&nbsp;
                            <?php echo Form::submit('入库', ['class' => 'btn btn-primary']);?>
                        @endif
                    </div>
                {!! Form::close() !!}
            </div>
        </div>
    </div>
    <div class="col-lg-9" style="margin-left: -15px">
        <div class="ibox float-e-margins">
            <div class="ibox-title">
                <h5>操作日志</h5>
                <div class="ibox-tools">
                    <a class="collapse-link">
                        <i class="fa fa-chevron-up"></i>
                    </a>
                </div>
            </div>
            <div class="ibox-content">
                @if($logs)
                    @foreach($logs as $log_key=>$log_val)
                        <div class="row">
                            <div class="col-md-1 col-xs-12">
                                <div>
                                    <span>{{\CorpUser::find($log_val->creator)->name}}</span>
                                </div>
                            </div>
                            <div class="col-md-2 col-xs-12">
                                <div>
                                    <span>{{$log_val->updated_at}}</span>
                                </div>
                            </div>
                            <div class="col-md-3 col-xs-12">
                                <div>
                                    <span>{{$log_val->action}}</span></div>
                            </div>
                            <div class="col-md-6 col-xs-12">
                                <div>
                                    <span>{{$log_val->content}}</span></div>
                            </div>
                        </div>
                        <div class="hr-line-dashed"></div>
                    @endforeach
                @endif

            </div>
        </div>
    </div>
    <div class="lightBoxGallery">
        <!-- The Gallery as lightbox dialog, should be a child element of the document body -->
        <div id="blueimp-gallery" class="blueimp-gallery">
            <div class="slides"></div>
            <h3 class="title"></h3>
            <a class="prev">‹</a>
            <a class="next">›</a>
            <a class="close">×</a>
            <a class="play-pause"></a>
            <ol class="indicator"></ol>
        </div>
    </div>
    <br>
    @js('js/plugins/slimscroll/jquery.slimscroll.min.js')
    <!-- blueimp gallery -->
    @js('js/plugins/blueimp/jquery.blueimp-gallery.min.js')
    <script>
        function cancleRisk() {
            var form_obj = $("#info_form");
            form_obj.action = "{{action('\App\Http\Controllers\Admin\BI\RiskEvaluationController@anyCancleInfo')}}";
            $("#info_form").attr("action",form_obj.action);
            form_obj.submit();
        }
    </script>
@endsection
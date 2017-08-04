@extends('admin.index')

@section('content')
    <div class="col-lg-12" style="margin-left: -15px">
        <div class="ibox float-e-margins">
            <div class="ibox-title">
                <h2>普租情报管理</h2>
            </div>
            <div class="ibox-content">
                <div class="form-group">
                    {!! Form::open(['url' => action('Admin\BI\RiskEvaluationController@anyIndex'), 'method' => 'get','class'=>'form-inline','style'=>'line-height: 40px;']) !!}
                    <div class="form-group">
                        {{Form::label('check_status', '状态',['class'=>'sr-only','for'=>'check_status'])}}
                        {{Form::select('check_status', \App\Models\BI\GeneralRentInformationCollection::listState(), $search_arr['check_status'] ?? null, ['placeholder' => '* 状态 *','class'=>'form-control'])}}
                    </div>
                    <div class="form-group">
                        {{Form::label('city', '城市',['class'=>'sr-only','for'=>'city'])}}
                        <select class="form-control" name="city">
                            <option value="">* 城市 *</option>
                            @forelse ($cityList as $city)
                                <option value="{{ $city }}" {{ isset($search_arr['city']) && $search_arr['city'] == $city ? 'selected' : '' }}>{{ $city }}</option>
                            @empty
                                <option value="">暂无可选项</option>
                            @endforelse
                        </select>
                    </div>
                    <div class="form-group">
                        {{Form::label('id', '信息编号',['class'=>'sr-only','for'=>'id'])}}
                        {{Form::text('id', $search_arr['id'] ?? '',['class'=>'form-control','id'=>'id','placeholder'=>'信息编号'])}}
                    </div>
                    <div class="form-group">
                        <input type="text" class="form-control" id="txt_search" name="block" placeholder="商圈" value="{{$search_arr['block'] ?? ''}}">
                        <div id="searchresult" style="display: none;margin-top: 15px;"></div>
                    </div>
                    <div class="form-group">
                        {{Form::label('xiaoqu_name', '小区',['class'=>'sr-only','for'=>'id'])}}
                        {{Form::text('xiaoqu_name', $search_arr['xiaoqu_name'] ?? '',['class'=>'form-control','id'=>'block','placeholder'=>'小区'])}}
                    </div>
                    <div class="form-group">
                        {{Form::label('creator', '录入人',['class'=>'sr-only','for'=>'id'])}}
                        {{Form::text('creator', $search_arr['creator_name'] ?? '',['class'=>'form-control','id'=>'block','placeholder'=>'录入人'])}}
                    </div>

                    <div class="form-group">
                        <label class="font-noraml"></label>
                        <div class="input-daterange input-group" id="create_time_zone" placeholder="录入时间">
                            @if(UserAgent::isMobile())
                                <input type="date" class="form-control" style="width: 138px;" name="create_time_lower" value="{{$search_arr['create_time_lower'] ?? ''}}" placeholder="录入时间">
                            @else
                                <input type="text" class="form-control" name="create_time_lower" value="{{$search_arr['create_time_lower'] ?? ''}}" placeholder="录入时间">
                            @endif
                            <span class="input-group-addon">至</span>
                                @if(UserAgent::isMobile())
                                    <input type="date" class="form-control" style="width: 138px;" name="create_time_upper" value="{{$search_arr['create_time_upper'] ?? ''}}" placeholder="录入时间">
                                @else
                                    <input type="text" class="form-control"  name="create_time_upper" value="{{$search_arr['create_time_upper'] ?? ''}}" placeholder="录入时间">
                                @endif
                        </div>
                    </div>


                    <div class="form-group">
                        {{Form::label('checker', '审核人',['class'=>'sr-only','for'=>'checker'])}}
                        {{Form::text('checker', $search_arr['checker_name'] ?? '',['class'=>'form-control','id'=>'checker','placeholder'=>'审核人'])}}
                    </div>

                    <div class="form-group">
                        <label class="font-noraml"></label>
                        <div class="input-daterange input-group" id="check_time_zone" placeholder="审核时间">
                            @if(UserAgent::isMobile())
                            <input type="date" class="form-control" style="width: 138px;" name="check_time_lower" value="{{$search_arr['check_time_lower'] ?? ''}}" placeholder="审核时间">
                            @else
                                <input type="text" class="form-control" name="check_time_lower" value="{{$search_arr['check_time_lower'] ?? ''}}" placeholder="审核时间">
                            @endif
                            <span class="input-group-addon">至</span>
                                @if(UserAgent::isMobile())
                                    <input type="date" class="form-control" style="width: 138px;" name="check_time_upper" value="{{$search_arr['check_time_upper'] ?? ''}}" placeholder="审核时间">
                                @else
                                    <input type="text" class="form-control" name="check_time_upper" value="{{$search_arr['check_time_upper'] ?? ''}}" placeholder="审核时间">
                                @endif
                        </div>
                    </div>

                    {{--{{Form::hidden('list_type',$list_type ?? 0) }}--}}
                    {{ csrf_field() }}
                    <div class="form-group">
                        <input type="submit" class="btn btn-primary" value="查询" style="margin-top: 8px;">

                        <a href="?" class="btn btn-default" style="margin-top: 8px;">清空</a>
                    </div>
                    {!! Form::close() !!}
                </div>
                <h5>共找到 {{$count}} 条符合条件的记录</h5>
                @if(can('BI_普租情报_新增'))
                    <div style="text-align: right;">
                        <a target="_blank" href="{{action('\App\Http\Controllers\Admin\BI\RiskEvaluationController@anyCreateInfo')}}"><span class="btn btn-default">提交情报</span></a>
                    </div>
                @endif
                <div class="table-responsive">
                    <table class="table table-striped table-bordered table-hover ">
                        <thead>
                        <tr>
                            <th>信息编号</th>
                            <th>状态</th>
                            <th>楼盘</th>
                            <th>楼号</th>
                            <th>楼层</th>
                            <th>情报价</th>
                            <th>审核价</th>
                            <th>建筑面积</th>
                            <th>户型</th>
                            <th>录入人</th>
                            <th>录入时间</th>
                            <th>审核人</th>
                            <th>审核时间</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($source as $s_key=>$s_val)
                            <tr class="gradeX">
                                <td>{{link_to_blank(action('\App\Http\Controllers\Admin\BI\RiskEvaluationController@getDetail',
        ['id'=>$s_val->id]),str_pad($s_val->id, 5, "0", STR_PAD_LEFT))}}</td>
                                <td>{{$s_val->check_status}}</td>
                                <td>
                                    {{link_to_blank(action('\App\Http\Controllers\Admin\BI\RiskEvaluationController@getDetail',
        ['id'=>$s_val->id]),$s_val->city . '-' . $s_val->block . '-' . $s_val->xiaoqu_name)}}
                                </td>
                                <td>{{$s_val->building_code}}</td>
                                <td>{{$floor_list[$s_val->floor]}}</td>
                                <td>{{$s_val->sale_price}}</td>
                                <td>{{$s_val->check_price}}</td>
                                <td>{{$s_val->area}}</td>
                                <td>{{$s_val->bedroom_num.'室'.($s_val->bef_gw+$s_val->bef_dw).'卫'}}</td>
                                <td>{{\CorpUser::find($s_val->user_id)->name}}</td>
                                <td>{{$s_val->create_time}}</td>
                                <td>{{$s_val->checker_id ? \CorpUser::find($s_val->checker_id)->name : ''}}</td>
                                <td>{{$s_val->check_time}}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                    <div class="text-center">
                        {{$source->links()}}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style type="text/css">
        #searchresult
        {
            width: 180px;
            position: absolute;
            z-index: 100;
            overflow: hidden;
            left: 184px;
            top: 71px;
            background: #E0E0E0;
            border-top: none;
        }
        .line
        {
            font-size: 14px;
            background: #E0E0E0;
            width: 184px;
            padding: 2px;
        }
        .hover
        {
            background: #007ab8;
            width: 184px;
            color: #fff;
        }
        .std
        {
            width: 180px;
            padding-left: 10px;
        }
    </style>
    <script>
        var keyword_url = "{{action('Admin\BI\RiskEvaluationController@anyBlockWords')}}";
        $(document).ready(function(){
            $('#create_time_zone').datepicker({
                language:  'zh-CN',
                autoclose: 1,
                format: 'yyyy-mm-dd',
            });
            $('#check_time_zone').datepicker({
                language:  'zh-CN',
                autoclose: 1,
                format: 'yyyy-mm-dd',
            });
        })

    </script>
    
@endsection

@section('boot-scripts')
    @css('css/plugins/datapicker/datepicker3.css')
    @css('css/plugins/daterangepicker/daterangepicker-bs3.css')
    @js('js/plugins/datapicker/bootstrap-datepicker.js')
    @js('js/plugins/datapicker/bootstrap-datepicker.zh-CN.js')
    @js('js/plugins/daterangepicker/daterangepicker.js')
    @js('js/plugins/fullcalendar/moment.min.js')
    @js('js/keyword_search/keyword.js')
@endsection

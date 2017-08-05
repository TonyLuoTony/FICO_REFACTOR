@extends('admin.index')
@section('meta')
    <style>
        .table th, .table td {
            text-align: left;
            vertical-align: middle !important;
        }
    </style>
    @css('css/plugins/keyword_search/jquery-ui.css')
@endsection

@section('content')
    <div class="ibox">
        <div class="col-lg-12" style="margin-left: -15px">
            <div class="ibox float-e-margins">
                <div class="ibox-title">
                    <h2>
                        {{ $title }}
                    </h2>
                    <div class="ibox-tools">
                        <a class="collapse-link">
                            <i class="fa fa-chevron-up"></i>
                        </a>
                    </div>
                </div>
                <div class="ibox-content">
                    <form method="post" class="form-horizontal" enctype='multipart/form-data'>
                        {{ csrf_field() }}
                        <input type="hidden" name="lng" value="{{ $data['lng'] or null }}">
                        <input type="hidden" name="lat" value="{{ $data['lat'] or null }}">
                        <div class="form-group">
                            <label class="col-sm-2 control-label"><span class="text-danger">*</span> 城市：</label>
                            <div class="col-sm-10">
                                <select class="form-control m-b" id="city-select" name="city">
                                    <option value="">选择下拉选项</option>
                                    @forelse ($cityList as $city)
                                        <option value="{{ $city }}" {{ isset($data['city']) && $data['city'] == $city ? 'selected' : '' }}>{{ $city }}</option>
                                    @empty
                                        <option value="">暂无可选项</option>
                                    @endforelse
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-2 control-label"><span class="text-danger">*</span> 小区名：</label>
                            <div class="col-sm-10 ui-widget">
                                <input type="text" name="xiaoqu_name" id="xiaoqu-input" class="form-control" value="{{ $data['xiaoqu_name'] or '' }}">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-2 control-label"><span class="text-danger">*</span> 商圈：</label>
                            <div class="col-sm-10">
                                <input type="text" name="block" id="xiaoqu-block" value="{{ $data['block'] or '' }}"
                                       class="form-control" readonly>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-2 control-label"><span class="text-danger">*</span> 楼号：</label>
                            <div class="col-sm-10">
                                <input type="text" name="building_code" class="form-control">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-2 control-label"><span class="text-danger">*</span> 楼层：</label>
                            <div class="col-sm-10">
                                <select class="form-control m-b" name="floor">
                                    <option value="">选择下拉选项</option>
                                    @forelse ($floorList as $fkey => $floor)
                                        <option value="{{ $fkey }}">{{ $floor }}</option>
                                    @empty
                                        <option value="">暂无可选项</option>
                                    @endforelse
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-2 control-label"><span class="text-danger">*</span> 建筑面积：</label>
                            <div class="col-sm-10">
                                <input type="number" name="area" class="form-control"
                                       value="{{ $data['area'] or null }}">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-2 control-label"><span class="text-danger">*</span> 卧室数：</label>
                            <div class="col-sm-10">
                                <input type="number" name="bedroom_num" class="form-control"
                                       value="{{ $data['bedroom_num'] or null }}">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-2 control-label"><span class="text-danger">*</span> 公卫数：</label>
                            <div class="col-sm-10">
                                <input type="number" name="bef_gw" class="form-control"
                                       value="{{ $data['bef_gw'] or null }}">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-2 control-label"><span class="text-danger">*</span> 独卫数：</label>
                            <div class="col-sm-10">
                                <input type="number" name="bef_dw" class="form-control"
                                       value="{{ $data['bef_dw'] or 0 }}">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-2 control-label"><span class="text-danger">*</span> 装修情况：</label>
                            <div class="col-sm-10">
                                <select class="form-control m-b" name="room_status">
                                    <option value="">选择下拉选项</option>
                                    @forelse ($roomStatusList as $rsk => $rs)
                                        <option value="{{ $rsk }}" {{ isset($data['room_status']) && $data['room_status'] == $rsk ? 'selected' : '' }}>{{ $rs }}</option>
                                    @empty
                                        <option value="">暂无可选项</option>
                                    @endforelse
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-2 control-label"><span class="text-danger">*</span> 环境情况：</label>
                            <div class="col-sm-10">
                                <select class="form-control m-b" name="enviorment_level">
                                    <option value="">选择下拉选项</option>
                                    @forelse ($enviormentLevelList as $k => $v)
                                        <option value="{{ $k }}">{{ $v }}</option>
                                    @empty
                                        <option value="">暂无可选项</option>
                                    @endforelse
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-2 control-label"><span class="text-danger">*</span> 价格来源：</label>
                            <div class="col-sm-10">
                                <select class="form-control m-b" name="source">
                                    <option value="">选择下拉选项</option>
                                    @forelse ($sourceList as $v)
                                        <option value="{{ $v }}">{{ $v }}</option>
                                    @empty
                                        <option value="">暂无可选项</option>
                                    @endforelse
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-2 control-label">证明图片：</label>
                            <div class="col-sm-10">
                                <input type="file" name="picture[]" class="form-control" multiple>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-2 control-label"><span class="text-danger">*</span> 普租价格：</label>
                            <div class="col-sm-10">
                                <input type="number" name="sale_price" class="form-control">
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="col-sm-4 col-sm-offset-2">
                                <button class="btn btn-white" type="submit">提交</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @js('js/keyword_search/jquery-ui.js')
    <script>
        $(document).ready(function () {
            var xiaoQuList = new Array();
            var xiaoQus = new Array();
            var citySelect = $('#city-select');
            var xqInput = $('#xiaoqu-input');
            var block = $('#xiaoqu-block');

            citySelect.change(function () {
                var cs = $(this).val();
                if (cs != "") {
                    $.post(
                        '{{action('\App\Http\Controllers\Admin\BI\RiskEvaluationController@anyQueryXiaoQu')}}',
                        {city: cs},
                        function (data) {
                            xiaoQus = data;
                            block.val('');
                            for (var i = 0; i < data.length; i++) {
                                xiaoQuList[i] = data[i]['v'];
                            }
                            console.log(xiaoQuList);
                        });
                }
                xqInput.val('');
                block.val('');
            });

            xqInput.autocomplete({
                source: xiaoQuList,
                delay: 300,
                minLength: 2
            });

            xqInput.blur(function () {
                var xq = $(this).val();
                console.log(xq);
                arr = xq.split(' - ');console.log(arr);
                if (arr.length >= 2){
                    block.val(arr[1]);
                }
            });

        });
    </script>
@endsection
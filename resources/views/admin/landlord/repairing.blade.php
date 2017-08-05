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
                        财务管理
                    </h2>
                    <div class="ibox-tools">
                        <a class="collapse-link">
                            <i class="fa fa-chevron-up"></i>
                        </a>
                    </div>
                </div>
                <div class="ibox-content" style="text-align: center;">
                    开发中。。。
                </div>
            </div>
        </div>
    </div>

    @js('js/keyword_search/jquery-ui.js')
    <script>

    </script>
@endsection
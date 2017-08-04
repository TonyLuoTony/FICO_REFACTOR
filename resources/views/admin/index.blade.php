<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
    <meta name="renderer" content="webkit">

    {{--Avoid Browser Cache CSRF Token--}}
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Cache-Control" content="no-cache">
    <meta http-equiv="Expires" content="0">

    <title>@yield('title') {{ "蛋壳后台" }}</title>

    <script>var _defer = [];</script>
    @css('bower/bootstrap/dist/css/bootstrap.min.css')
    @css('bower/fontawesome/css/font-awesome.min.css')
    @css('inspinia/css/plugins/toastr/toastr.min.css')
    @css('inspinia/css/animate.css')
    @css('inspinia/css/style.css')
    @css('css/admin.css')
    @js('bower/jquery/dist/jquery.min.js')
    {!! Rapyd::styles() !!}
    {{--@include('lego::styles')--}}
    @yield('meta')

    @if( isProduction() )
        <style>
            #page-wrapper {
                border-left: 3px solid red;
            }
        </style>
    @endif
</head>

<body class="{{ UserAgent::isPC() ? '' : 'fixed-sidebar boxed-layout' }}">

<script>
    // 作为Template存在, 并非全局都生效, 比较坑爹。
    @include('widget.ajax_setup')
</script>

<!--[if lte IE 9]>
<div class="alert-danger text-center">你正在使用<strong>过时</strong>的浏览器，后台不能很好的支持。
    <a href="http://browser.qq.com/" target="_blank">升级到最新QQ浏览器</a> 以获得更好的使用体验！
</div>
<![endif]-->

<div id="wrapper">

    <nav class="navbar-default navbar-static-side" role="navigation" id="admin-menu">
        <div class="sidebar-collapse">
            <ul class="nav metismenu" id="side-menu">
                <li class="nav-header">
                    <div class="profile-element">
                        <a href="{{ env('LAPUTA_API_URL').'/admin' }}">
                            <span class="block">蛋壳公寓 &middot; {{ CorpAuth::name() }}</span>
                        </a>
                    </div>
                    <div class="logo-element">
                        <img alt="image" class="img-circle" width="48" height="48" src="//cdn.wutongwan.org/logo.jpg"/>
                    </div>
                </li>

                <? $menus = (new AdminMenu())->visibleLinks()?>
                @foreach( $menus as $groupName => $group)
                    <li>
                        @if(is_array($group))
                            <a class="nav-btn">
                                {{ $groupName }} <span class="fa arrow"></span>
                            </a>
                            <ul class="nav nav-second-level collapse">
                                @foreach($group as $name => $second)
                                    @if (is_array($second))   {{-- 三级菜单 --}}
                                    <li>
                                        <a class="nav-btn">{{$name}}<span class="fa arrow"></span></a>
                                        <ul class="nav nav-third-level collapse">
                                            @foreach($second as $_name => $third)
                                                <li class="{{ Input::url() === $third ? 'active' : '' }} nav-last">
                                                    <a href="{{$third}}"> > {{$_name}}</a>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </li>
                                    @else
                                        <li class='{{ Input::url() === $second ? 'active' : '' }} nav-last'>
                                            <a href="{{$second}}">{{$name}}</a>
                                        </li>
                                    @endif
                                @endforeach
                            </ul>
                        @else
                            <a href="{{ $group }}">{{ $groupName }}</a>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    </nav>

    <div id="page-wrapper" class="gray-bg dashbard-1">
        {{-- top nav --}}
        <div class="row border-bottom">
            <nav class="navbar navbar-static-top" role="navigation" style="margin-bottom: 0">
                <div class="navbar-header">
                    <a class="navbar-minimalize minimalize-styl-2 btn btn-primary " href="#">
                        <i class="fa fa-bars"></i> 菜单
                    </a>


                    <form role="form" class="navbar-form-custom" action="{{ env('LAPUTA_API_URL').'/admin/faster-suite-search' }}">
                        <input type="text" placeholder="房间编号或地址" name="keywords" class="form-control">
                    </form>
                </div>
                <ul class="nav navbar-top-links navbar-right">
                    <li><a href="{{ env('LAPUTA_API_URL').'/admin/faster-suite-search' }}" target="_blank"><i class="fa fa-search" aria-hidden="true"></i>快搜</a></li>
                    <li class="dropdown visible-xs-inline-block visible-sm-inline-block visible-md-inline-block visible-lg-inline-block">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button"
                           aria-haspopup="true" aria-expanded="false">
                            <i class="fa fa-map-marker" aria-hidden="true"></i>
                            {{ City::current() }} <span class="caret"></span>
                        </a>
                        <ul class="dropdown-menu">
                            @foreach(Area::listCity() as $_city)
                                <li>
                                    <a href="{{ env('LAPUTA_API_URL').'/admin/change-city/'.$_city }}">
                                        切换到【{{ $_city }}】
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </li>
                    @if( !isProduction() )
                        <li class="hidden-xs">
                            <a href="{{ env('LAPUTA_API_URL').'/admin/choose-mask/' }}">
                                <i class="fa fa-users"></i>变身!
                            </a>
                        </li>
                    @endif
                    <li class="hidden-xs">
                        <a href="{{ env('LAPUTA_API_URL').'/qywechat/logout/' }}">
                            <i class="fa fa-sign-out"></i>退出登陆
                        </a>
                    </li>
                </ul>
            </nav>
        </div>

        {{-- body --}}
        <div class="row">
            <div class="col-lg-12">
                <div class="wrapper wrapper-content">
                    <div class="row">
                        @include('vendor.flash.message')
                        @section('content')
                            <? $groupName = Input::get('team') ?>
                            @if(isset($menus[$groupName]))
                                <div class="panel panel-default">
                                    <div class="panel-heading">
                                        <h2 class="text-center">{{$groupName}}工具</h2>
                                    </div>
                                    <div class="panel-body text-center">
                                        <ul class="list-group">
                                            @foreach($menus[$groupName] as $name => $second)
                                                @if ( is_array($second) )
                                                    @foreach($second as $_name => $third)
                                                        <li class="list-group-item">
                                                            <a href="{{$third}}">{{$name}}_{{$_name}}</a>
                                                        </li>
                                                    @endforeach
                                                @else
                                                    <li class="list-group-item">
                                                        <a href="{{ $second }}">{{ $name }}</a>
                                                    </li>
                                                @endif
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                            @else
                                <h3 class="text-center">
                                    如在使用中碰到故障，请将屏幕截图发邮件给 <a href="mailto:dev@wutongwan.org">dev@dankegongyu.com</a>
                                </h3>
                            @endif
                        @show
                    </div>
                </div>
            </div>
        </div>

        {{-- footer --}}
        <div class="footer">
            <div class="pull-right">
                <strong>Copyright &copy; {{ date('Y') }} 紫梧桐（北京）资产管理有限公司</strong>
            </div>
        </div>


    </div>
</div>
<!-- Mainly scripts -->
@js('js/global.js')
@js('bower/bootstrap/dist/js/bootstrap.min.js')
@js('inspinia/js/plugins/metisMenu/jquery.metisMenu.js')
@js('inspinia/js/plugins/slimscroll/jquery.slimscroll.min.js')
<!-- Custom and plugin javascript -->
{{-- inport fot for Input Mask Feature --}}
@css('inspinia/css/plugins/jasny/jasny-bootstrap.min.css')
@js('inspinia/js/plugins/jasny/jasny-bootstrap.min.js')
@js('inspinia/js/inspinia.js')
@include('widget.rapyd-scripts')
{{--@include('lego::scripts')--}}
<script>
    $(document).ready(function () {
        for (var i in _defer) {
            _defer[i]();
        }
    });

    $('li.active.nav-last').parents('li').addClass('active');

    //所有列表上的编辑按钮默认新窗口打开。
    $('a[href*="?modify="]').attr('target', '_blank');
</script>
@yield('boot-scripts')
</body>
</html>

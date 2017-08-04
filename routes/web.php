<?php

Route::group(['prefix'=>'auth'],function (){
    Route::get('login','Auth\AccountController@login');
    Route::get('checkResult','Auth\AccountController@checkResult')->name('check-result');

    Route::group(['middleware'=>'check_login'], function (){
        Route::get('check','Auth\AccountController@check');
    });
});

Route::group(['prefix' => 'api/v1'], function () {
    Route::any('/', 'Api\IndexController@getIndex');
    Route::any('/menu', 'Api\IndexController@getMenu');
    Route::any('/jump', 'Api\JumpController@getIndex');
});

Route::group(['prefix'=>'admin','middleware'=>'check_login'], function (){

   Route::any('payment','Auth\AccountController@payment');

   Route::group(['prefix'=>'settlement'], function (){
       Route::any('customer','Auth\AccountController@customer');
       Route::any('landlord','Auth\AccountController@landlord');
   });

   Route::group(['prefix'=> 'bi/risk-evaluation'], function () {
       Route::any('create-info','Admin\BI\RiskEvaluationController@anyCreateInfo');
       Route::any('/','Admin\BI\RiskEvaluationController@anyIndex');
       Route::get('detail','Admin\BI\RiskEvaluationController@getDetail');
       Route::any('block-words','Admin\BI\RiskEvaluationController@anyBlockWords');
       Route::any('query-xiao-qu','Admin\BI\RiskEvaluationController@anyQueryXiaoQu');
       Route::any('input-info','Admin\BI\RiskEvaluationController@anyInputInfo');
       Route::any('cancle-info','Admin\BI\RiskEvaluationController@anyCancleInfo');
   });

});

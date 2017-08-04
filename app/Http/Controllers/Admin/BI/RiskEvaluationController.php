<?php

namespace App\Http\Controllers\Admin\BI;

use App\Http\Controllers\Admin\BaseController;
use App\Http\Controllers\Admin\Traits\BI\RapidAssessment;
use App\Models\BI\CommunityHouseType;
use App\Models\BI\GeneralRentInformationCollection;
use App\Models\Finance\FicoLog;
use Carbon\Carbon;
use Illuminate\Http\Request;

class RiskEvaluationController extends BaseController
{
    use RapidAssessment;

    public function anyIndex(Request $request)
    {
        $this->assertCan('BI_普租情报_列表');
        $title = '普租情报列表';

        $check_status = $request->input('check_status', '');
        $city = $request->input('city', '');
        $id = $request->input('id', '');
        $block = $request->input('block', '');
        $xiaoqu_name = $request->input('xiaoqu_name', '');
        $creator = $request->input('creator', '');
        $create_time_lower = $request->input('create_time_lower', '');
        $create_time_upper = $request->input('create_time_upper', '');
        $checker = $request->input('checker', '');
        $check_time_lower = $request->input('check_time_lower', '');
        $check_time_upper = $request->input('check_time_upper', '');

        if (role('BI-风控-评估师')) {
            $source = GeneralRentInformationCollection::where('id', '>', 0);
        } elseif (role('BI-风控-查看') || role('出房团队')) {
            $source = GeneralRentInformationCollection::where('user_id', '=', \CorpAuth::id());
        } else {
            $this->error("您不是没有权限");
        }

        $search_arr = [];
        if (!empty($check_status)) {
            $search_arr['check_status'] = $check_status;
            $source = $source->where('check_status', '=', $check_status);
        }
        if (!empty($city)) {
            $search_arr['city'] = $city;
            $source = $source->where('city', '=', $city);
        }
        if (!empty($id)) {
            $search_arr['id'] = $id;
            $source = $source->where('id', 'like', '%' . $id . '%');
        }
        if (!empty($block)) {
            $search_arr['block'] = $block;
            $source = $source->where('block', 'like', '%' . $block . '%');
        }
        if (!empty($xiaoqu_name)) {
            $search_arr['xiaoqu_name'] = $xiaoqu_name;
            $source = $source->where('xiaoqu_name', 'like', '%' . $xiaoqu_name . '%');
        }
        if (!empty($creator)) {
            $search_arr['creator_name'] = $creator;
            $user_id_arr = \CorpUser::where('name', 'like', '%' . $creator . '%')->pluck('id')->toArray();
            $source = $source->whereIn('user_id', $user_id_arr);
        }
        if (!empty($create_time_lower)) {
            $search_arr['create_time_lower'] = $create_time_lower;
            $source = $source->where('create_time', '>=', $create_time_lower);
        }
        if (!empty($create_time_upper)) {
            $search_arr['create_time_upper'] = $create_time_upper;
            $source = $source->where('create_time', '<=', $create_time_upper);
        }
        if (!empty($checker)) {
            $search_arr['checker_name'] = $checker;
            $checker_id_arr = \CorpUser::where('name', 'like', '%' . $creator . '%')->pluck('id')->toArray();
            $source = $source->whereIn('checker_id', $checker_id_arr);
        }
        if (!empty($check_time_lower)) {
            $search_arr['check_time_lower'] = $check_time_lower;
            $source = $source->where('check_time', '<=', $check_time_lower);
        }
        if (!empty($check_time_upper)) {
            $search_arr['check_time_upper'] = $check_time_upper;
            $source = $source->where('check_time', '<=', $check_time_upper);
        }

        $floor_list = $this->floorList();
        // 城市列表
        $cityList = \Area::where('level', \Area::LEVEL_城市)->pluck('name', 'id')->toArray();
        // 商圈列表
        $blockList = \Area::where('level', \Area::LEVEL_商圈)->pluck('name', 'id')->toArray();

        $count = $source->count();
        $source = $source->orderBy('id', 'desc');
        $source = $source->paginate('30');

        return view('admin.bi.risk-evaluation.index',
            compact('source', 'count', 'search_arr', 'floor_list', 'cityList', 'blockList'));
    }

    public function getDetail(Request $request)
    {
        $this->assertCan('BI_普租情报_详情');

        $id = $request->input('id', '');
        if (empty($id)){
            $this->error("参数错误");
        }

        if (role('BI-风控-评估师')) {
            $info_res = GeneralRentInformationCollection::where('id', '=', $id)->first();
        } elseif (role('BI-风控-查看') || role('出房团队')) {
            $info_res = GeneralRentInformationCollection::where('id', '=', $id)->where('user_id', '=',
                \CorpAuth::id())->first();
        } else {
            $this->error("您没有权限");
        }
        //$info_res = GeneralRentInformationCollection::where('id', '=', $id)->first();

        $info_res = GeneralRentInformationCollection::where('id', '=', $id)->first();
        $logs = FicoLog::where(['related_doc_id' => $id, 'related_doc_type' => '普租情报操作日志'])->get();
        $pic_arr = $this->rentInformationUrls($id);
        $floor_list = $this->floorList();
        $room_status_list = $this->roomStatusList();
        $enviorment_level_list = $this->enviormentLevelList();
        return view('admin.bi.risk-evaluation.details', compact(
            'info_res',
            'logs',
            'pic_arr',
            'floor_list',
            'room_status_list',
            'enviorment_level_list'
        ));
    }

    // 入库操作
    public function anyInputInfo(Request $request)
    {
        $this->assertCan('BI_普租情报_审核');

        $info_id = $request->input('info_id', '');
        $check_price = $request->input('check_price', '');
        $check_note = $request->input('check_note', '');
        if (!$info_id) {
            flash('操作失败');
            return redirect(action('Admin\BI\RiskEvaluationController@anyIndex'));
        }
        if ($check_price < 0) {
            flash('操作失败,审核价格不能为空。');
            return redirect(action('Admin\BI\RiskEvaluationController@anyIndex'));
        }
        try {
            $exception = \DB::transaction(function () use ($info_id, $check_price, $check_note) {
                $info_res = GeneralRentInformationCollection::where('id', '=', $info_id)->first();
                $info_res->update([
                    'check_price' => $check_price,
                    'check_note' => $check_note,
                    'check_status' => GeneralRentInformationCollection::STATUS_已入库,
                    'check_time' => Carbon::now(),
                    'checker_id' => \CorpAuth::id()
                ]);

                $community_res = CommunityHouseType::where('source_id', '=', $info_id)->first();
                if (empty($community_res)) {
                    $community_res = new CommunityHouseType();
                }

                $community_res->city = $info_res->city ?? '';
                $community_res->block = $info_res->block ?? '';
                $community_res->xiaoqu_id = $info_res->xiaoqu_id ?? '';
                $community_res->xiaoqu_name = $info_res->xiaoqu_name ?? '';
                $community_res->lng = $info_res->lng ?? null;
                $community_res->lat = $info_res->lat ?? '';
                $community_res->building_code = $info_res->building_code ?? '';
                $community_res->floor = $info_res->floor ?? '';
                $community_res->bedroom_num = $info_res->bedroom_num ?? '';
                $community_res->toilet_num = $info_res->bef_gw + $info_res->bef_dw ?? '';
                $community_res->area = $info_res->area ?? '';
                $community_res->room_status = $info_res->room_status ?? '';
                $community_res->price = $info_res->check_price ?? '';
                $community_res->sample_num = 1;
                $community_res->data_date = Carbon::now() ?? '';
                $community_res->source = $info_res->source ?? '';
                $community_res->source_id = $info_res->id ?? '';
                $community_res->user = \CorpAuth::id();
                $community_res->save();

                FicoLog::inputLog('普租情报操作日志', $info_id, '审核通过', $check_note);
            });
            flash('操作完成');
            return redirect(action('Admin\BI\RiskEvaluationController@anyIndex'));
        } catch (Exception $e) {
            flash('操作失败');
            return redirect(action('Admin\BI\RiskEvaluationController@anyIndex'));
        }

    }

    // 作废
    public function anyCancleInfo(Request $request)
    {
        $this->assertCan('BI_普租情报_审核');
        $id = $request->input('info_id', '');
        $check_note = $request->input('check_note', '');
        if ($id) {
            $info_res = GeneralRentInformationCollection::where('id', '=', $id)->first();
            $res = $info_res->update([
                'check_status' => GeneralRentInformationCollection::STATUS_作废,
                'check_note' => $check_note,
                'check_time' => Carbon::now(),
                'checker_id' => \CorpAuth::id()
            ]);
            if ($res) {
                FicoLog::inputLog('普租情报操作日志', $id, '作废', $check_note);
                flash('操作完成');
            } else {
                flash('操作失败');
            }
        } else {
            flash('操作失败');
        }

        return redirect(action('Admin\BI\RiskEvaluationController@anyIndex'));
    }

    public function anyBlockWords(Request $request)
    {
        $keyword = $request->input('keyword');
        // 商圈列表
        $blockList = \Area::where('level', '=', \Area::LEVEL_商圈)->where('name', 'like',
            '%' . $keyword . '%')->pluck('name', 'id')->toJson();
        if ($blockList) {
            return $this->ajaxSuccess($blockList);
        }
    }

    private function ajaxSuccess($msg = '')
    {
        return [
            'message' => $msg,
            'status' => 'success',
        ];
    }

    private function ajaxError($msg = '', $error = 'error')
    {
        return [
            'message' => $msg,
            'status' => $error,
        ];
    }

}

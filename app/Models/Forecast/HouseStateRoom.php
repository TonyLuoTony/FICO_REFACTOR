<?php namespace Forecast;

use FileStore\ImagesTrait;
use Traits\ModelAllowTrait;


/**
 * Forecast\HouseStateRoom
 *
 * @property integer $id
 * @property float $room_length                 房间长度
 * @property float $room_width                  房间宽度
 * @property float $area                        面积
 * @property string $room_face                  朝向
 * @property string $room_type                  房间类型
 * @property boolean $has_private_bathroom      是否有独卫
 * @property boolean $has_private_balcony       是否有独立阳台
 * @property boolean $has_cloakroom             是否有储物间
 * @property boolean $has_terrace               是否有露台
 * @property boolean $has_bay_window            是否有飘窗
 * @property boolean $is_wired_loft             大斜顶
 * @property boolean $is_wired_long_and_thin    狭长型
 * @property boolean $is_small_window           是否有小窗户
 * @property boolean $is_bad_lighting           是否采光差
 * @property boolean $is_private_window         是否非私密采光
 * @property boolean $is_near_street            是否邻大街
 * @property \Carbon\Carbon $created_at
 * @property integer $house_id                  房源id
 * @property integer $owner_id                  拥有者id
 * @property integer $plan_id                   所处方案id
 * @property string $note                       备注
 * @property boolean $is_reform                 是否优化间
 * @property string $cleaning_tools_tag         洁具
 * @property string $door_tag                   门
 * @property string $floor_tag                  地
 * @property boolean $is_house_state_readonly   房态是否锁定
 * @property boolean $is_price_readonly         价格是否锁定
 * @property boolean $is_room_info_readonly     房间基本信息是否锁定
 * @property boolean $is_rough_room             是否毛坯
 * @property string $kitchen_ware_tag           厨具
 * @property string $pics_json                  图片json
 * @property integer $revalue_price_yuan        收到修正价
 * @property string $roof_tag                   顶
 * @property integer $temp_price_yuan           模型计算机
 * @property string $toilet_tag                 马桶
 * @property string $wall_floor_tag             墙地
 * @property string $wall_tag                   墙
 * @property string $window_tag                 窗
 * @property \Carbon\Carbon $updated_at
 * @property string $others_tear_down_tag
 * @property boolean $has_private_kitchen       是否有独立厨房
 * @property boolean $has_toilet_in_bathroom    是否带马桶
 */
class HouseStateRoom extends \BaseModel
{
    use ModelAllowTrait;
    use ImagesTrait;

    protected $connection = 'forecast';
    protected $table = 'house_state_room';

    const ROOM_TYPE_卧室 = '卧室';
    const ROOM_TYPE_厨房 = '厨房';
    const ROOM_TYPE_卫生间 = '卫生间';
    const ROOM_TYPE_淋浴房 = '淋浴房';
    const ROOM_TYPE_公共区域 = '公共区域';

    const ROOM_FACE_正东 = '正东';
    const ROOM_FACE_正南 = '正南';
    const ROOM_FACE_正西 = '正西';
    const ROOM_FACE_正北 = '正北';
    const ROOM_FACE_东南 = '东南';
    const ROOM_FACE_东北 = '东北';
    const ROOM_FACE_西南 = '西南';
    const ROOM_FACE_西北 = '西北';

    const TAG_定价标签 = '定价标签';
    const TAG_房态标签 = '房态标签';

    const YES = 1;
    const NO = 0;

    /**
     * @param $roomType
     * @param $planId
     */
    public function createEmptyRoom($roomType, HouseStatePlanTicket $plan)
    {
        $this->plan_id = $plan->id;
        $this->owner_id = $plan->owner_id;
        $this->house_id = $plan->house_id;
        $this->room_type = $roomType;
        return $this->saveOrError();
    }

    public function save(array $options = [])
    {
        $priceParams = $this->getLockParams(self::TAG_定价标签);
        $stateParams = $this->getLockParams(self::TAG_房态标签);

        $this->is_room_info_readonly = self::YES;
        $this->is_house_state_readonly = self::YES;

        // 房间图片参与定价标签是否锁定
        if (!$this->hasImage('pics_json')) {
            $this->is_house_state_readonly = self::NO;
        }

        foreach ($priceParams as $param) {
            if (!$this->{$param} && $this->{$param} !== '0' && $this->{$param} !== 0) {
                $this->is_room_info_readonly = self::NO;
            }
        }

        foreach ($stateParams as $param) {
            if (!$this->{$param} && $this->{$param} !== '0' && $this->{$param} !== 0) {
                $this->is_house_state_readonly = self::NO;
            }
        }

        // 若房间是毛坯且有照片,则房态标签锁定
        if ($this->is_rough_room && $this->hasImage('pics_json')) {
            $this->is_house_state_readonly = self::YES;
        }

        return parent::save($options); // TODO: Change the autogenerated stub
    }

    public function getLockParams($tagType)
    {
        return config('house_state')['lock_tags'][$this->room_type][$tagType] ?? [];
    }

    public function prepareEditorConfig()
    {
        // 获取房态配置
        $houseStateTags = HouseStateNineTag::where('room_type', $this->room_type)
            ->where('is_active', HouseStateNineTag::STATUS_ACTIVITY)
            ->orderBy('id', 'desc')
            ->get();

        $houseStateConfig = [];
        foreach ($houseStateTags as $houseStateTag) {
            $houseStateConfig[$houseStateTag->state_category] = $houseStateConfig[$houseStateTag->state_category] ?? ['' => '选择房态'];
            $houseStateConfig[$houseStateTag->state_category][$houseStateTag->slug] = $houseStateTag->describes;
        }

        // 返回格式:字段中文名 类型 选项列表
        return [
            'is_reform' => [
                'name' => '是否优化间',
                'type' => 'select',
                'options' => self::listIf(),
            ],
            'room_length' => [
                'name' => '长度(米)',
                'type' => 'text',
                'options' => [],
            ],
            'room_width' => [
                'name' => '宽度(米)',
                'type' => 'text',
                'options' => [],
            ],
            'area' => [
                'name' => '居住面积(平米)',
                'type' => 'text',
                'options' => []
            ],
            'room_face' => [
                'name' => '朝向',
                'type' => 'select',
                'options' => array_merge(['' => '请选择'], self::listFace()),
            ],
            'has_private_bathroom' => [
                'name' => '是否有独卫',
                'type' => 'select',
                'options' => self::listIf(),
            ],
            'has_private_balcony' => [
                'name' => '是否有阳台',
                'type' => 'select',
                'options' => self::listIf(),
            ],
            'has_cloakroom' => [
                'name' => '是否有储物间/衣帽间',
                'type' => 'select',
                'options' => self::listIf(),
            ],
            'has_terrace' => [
                'name' => '是否有露台',
                'type' => 'select',
                'options' => self::listIf(),
            ],
            'has_bay_window' => [
                'name' => '是否有飘窗',
                'type' => 'select',
                'options' => self::listIf(),
            ],
            'is_wired_loft' => [
                'name' => '是否有大斜顶',
                'type' => 'select',
                'options' => self::listIf(),
            ],
            'is_wired_long_and_thin' => [
                'name' => '是否狭长型',
                'type' => 'select',
                'options' => self::listIf(),
            ],
            'is_small_window' => [
                'name' => '是否小窗户',
                'type' => 'select',
                'options' => self::listIf(),
            ],
            'is_bad_lighting' => [
                'name' => '是否采光差',
                'type' => 'select',
                'options' => self::listIf(),
            ],
            'is_private_window' => [
                'name' => '是否非私密采光',
                'type' => 'select',
                'options' => self::listIf(),
            ],
            'is_near_street' => [
                'name' => '是否靠近大街',
                'type' => 'select',
                'options' => self::listIf(),
            ],
            'is_rough_room' => [
                'name' => '是否毛坯',
                'type' => 'select',
                'options' => self::listIf(),
            ],
            'has_toilet_in_bathroom' => [
                'name' => '独卫是否有马桶',
                'type' => 'select',
                'options' => self::listIf(),
            ],
            'has_private_kitchen' => [
                'name' => '是否有独立厨房',
                'type' => 'select',
                'options' => self::listIf(),
            ],
            'wall_tag' => [
                'name' => '墙',
                'type' => 'select',
                'options' => $houseStateConfig[HouseStateNineTag::CATEGORY_墙] ?? [],
            ],
            'floor_tag' => [
                'name' => '地',
                'type' => 'select',
                'options' => $houseStateConfig[HouseStateNineTag::CATEGORY_地] ?? [],
            ],
            'roof_tag' => [
                'name' => '顶',
                'type' => 'select',
                'options' => $houseStateConfig[HouseStateNineTag::CATEGORY_顶] ?? [],
            ],
            'others_tear_down_tag' => [
                'name' => '拆除项',
                'type' => 'select',
                'options' => $houseStateConfig[HouseStateNineTag::CATEGORY_拆除项] ?? [],
            ],
            'wall_floor_tag' => [
                'name' => '墙地',
                'type' => 'select',
                'options' => $houseStateConfig[HouseStateNineTag::CATEGORY_墙地] ?? [],
            ],
            'cleaning_tools_tag' => [
                'name' => '洗脸池/柱盆',
                'type' => 'select',
                'options' => $houseStateConfig[HouseStateNineTag::CATEGORY_洁具] ?? [],
            ],
            'toilet_tag' => [
                'name' => '座便器',
                'type' => 'select',
                'options' => $houseStateConfig[HouseStateNineTag::CATEGORY_座便器] ?? [],
            ],
            'kitchen_ware_tag' => [
                'name' => '橱柜',
                'type' => 'select',
                'options' => $houseStateConfig[HouseStateNineTag::CATEGORY_橱柜] ?? [],
            ],
            'door_tag' => [
                'name' => '门',
                'type' => 'select',
                'options' => $houseStateConfig[HouseStateNineTag::CATEGORY_门] ?? [],
            ],
            'window_tag' => [
                'name' => '窗',
                'type' => 'select',
                'options' => $houseStateConfig[HouseStateNineTag::CATEGORY_窗] ?? [],
            ],
        ];
    }

    public static function prepareGridConfig()
    {
        // 返回格式:字段中文名 类型 选项列表
        return [
            'is_reform' => [
                'name' => '优化间',
                'relation' => '',
                'type' => 'text',
            ],
            'room_length' => [
                'name' => '长度(米)',
                'relation' => '',
                'type' => 'text',
            ],
            'room_width' => [
                'name' => '宽度(米)',
                'relation' => '',
                'type' => 'text',
            ],
            'area' => [
                'name' => '居住面积(平米)',
                'relation' => '',
                'type' => 'text',
            ],
            'room_face' => [
                'name' => '房间朝向',
                'relation' => '',
                'type' => 'text',
            ],
            'has_private_bathroom' => [
                'name' => '独卫',
                'relation' => '',
                'type' => 'radio',
            ],
            'has_private_balcony' => [
                'name' => '阳台',
                'relation' => '',
                'type' => 'radio',
            ],
            'has_cloakroom' => [
                'name' => '储物间/衣帽间',
                'relation' => '',
                'type' => 'radio',
            ],
            'has_terrace' => [
                'name' => '露台',
                'relation' => '',
                'type' => 'radio',
            ],
            'has_bay_window' => [
                'name' => '飘窗',
                'relation' => '',
                'type' => 'radio',
            ],
            'is_wired_loft' => [
                'name' => '大斜顶',
                'relation' => '',
                'type' => 'radio',
            ],
            'is_wired_long_and_thin' => [
                'name' => '狭长型',
                'relation' => '',
                'type' => 'radio',
            ],
            'is_small_window' => [
                'name' => '小窗户',
                'relation' => '',
                'type' => 'radio',
            ],
            'is_bad_lighting' => [
                'name' => '采光差',
                'relation' => '',
                'type' => 'radio',
            ],
            'is_private_window' => [
                'name' => '非私密采光',
                'relation' => '',
                'type' => 'radio',
            ],
            'is_near_street' => [
                'name' => '靠近大街',
                'relation' => '',
                'type' => 'radio',
            ],
            'is_rough_room' => [
                'name' => '毛坯',
                'relation' => '',
                'type' => 'radio',
            ],
            'has_toilet_in_bathroom' => [
                'name' => '独卫是否有马桶',
                'relation' => '',
                'type' => 'radio',
            ],
            'has_private_kitchen' => [
                'name' => '独立厨房',
                'relation' => '',
                'type' => 'radio',
            ],
            'wall_tag' => [
                'name' => '墙',
                'relation' => HouseStateNineTag::CATEGORY_墙,
                'type' => 'text',
            ],
            'floor_tag' => [
                'name' => '地',
                'relation' => HouseStateNineTag::CATEGORY_地,
                'type' => 'text',
            ],
            'roof_tag' => [
                'name' => '顶',
                'relation' => HouseStateNineTag::CATEGORY_顶,
                'type' => 'text',
            ],
            'others_tear_down_tag' => [
                'name' => '拆除项',
                'relation' => HouseStateNineTag::CATEGORY_拆除项,
                'type' => 'text',
            ],
            'wall_floor_tag' => [
                'name' => '墙地',
                'relation' => HouseStateNineTag::CATEGORY_墙地,
                'type' => 'text',
            ],
            'cleaning_tools_tag' => [
                'name' => '洗脸池/柱盆',
                'relation' => HouseStateNineTag::CATEGORY_洁具,
                'type' => 'text',
            ],
            'toilet_tag' => [
                'name' => '座便器',
                'relation' => HouseStateNineTag::CATEGORY_座便器,
                'type' => 'text',
            ],
            'kitchen_ware_tag' => [
                'name' => '橱柜',
                'relation' => HouseStateNineTag::CATEGORY_橱柜,
                'type' => 'text',
            ],
            'door_tag' => [
                'name' => '门',
                'relation' => HouseStateNineTag::CATEGORY_门,
                'type' => 'text',
            ],
            'window_tag' => [
                'name' => '窗',
                'relation' => HouseStateNineTag::CATEGORY_窗,
                'type' => 'text',
            ],
        ];
    }

    public static function listFace()
    {
        return selectOpts(self::constants('room_face'));
    }

    public static function listIf()
    {
        return [
            self::NO => \Constant::IF_否,
            self::YES => \Constant::IF_是,
        ];
    }

    protected function processAllow($action)
    {
        switch ($action) {
            case 'manger' :
                return can('维护房态采集器');
            case 'room_info_editable' :
                return !$this->is_room_info_readonly || $this->allow('manger');
            case 'house_state_editable' :
                return !$this->is_house_state_readonly || $this->allow('manger');
        }
    }

    /**
     * 判断字段是否都锁定
     */
    public function isAllLocked()
    {
        return $this->is_room_info_readonly && $this->is_house_state_readonly;
    }

    public function plan()
    {
        return $this->belongsTo(HouseStatePlanTicket::class);
    }
}

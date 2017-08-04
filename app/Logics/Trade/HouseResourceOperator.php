<?php namespace Trade;

use Carbon\Carbon;
use Tracking\HouseResource;
use Tracking\HouseResourceBill;
use Tracking\HouseResourceUser;

class HouseResourceOperator extends BaseOperator
{
    /**
     * 发放一条房源的奖励到用户的钱包
     *
     * @param HouseResourceUser $resourceUser 外部房源用户
     * @param integer $yuan                   金额，单位为 ** 元 **
     * @param string $tag                     账单标签
     * @param HouseResource $resource        外部房源信息
     * @return mixed
     */
    public function award(HouseResourceUser $resourceUser, $yuan, $tag, HouseResource $resource = null)
    {
        return \DB::transaction(function () use ($resourceUser, $resource, $yuan, $tag) {

            if ($resource) {
                $count = \DB::table('house_resource_bills')
                    ->where(['house_resource_id' => $resource->id])
                    ->count();
                if ($count > 0) {
                    $this->setErrMsg('当前信息已发放奖励.');
                    return null;
                }
            }

            $row = [
                'house_resource_user_id' => $resourceUser->id,
                'tag' => $tag,
                'type' => HouseResourceBill::TYPE_转入,
                'amount' => $yuan,
                'client_ip' => \Input::ip(),
                'note' => "{$tag}{$resourceUser->user->nickname}{$yuan}元",
                'updated_at' => Carbon::now(),
                'created_at' => Carbon::now(),
            ];

            if ($resource) {
                $row['house_resource_id'] = $resource->id;
                $row['note'] = "{$resource->address}（{$resource->landlord_name}）";

                $this->getRow('house_resource_users', $resourceUser->id)
                    ->increment('total_accept_records', 1);
                $this->getRow('house_resource_users', $resourceUser->id)
                    ->increment('sum_awards', $yuan);
            }

            $billId = \DB::table('house_resource_bills')->insertGetId($row);
            $this->getRow('house_resource_users', $resourceUser->id)
                ->increment('balance', $yuan, ['updated_at' => Carbon::now()]);

            return $billId;
        });
    }

    /**
     * 提现
     * @param HouseResourceUser $resourceUser
     */
    public function withdraw(HouseResourceUser $resourceUser)
    {
        $operator = new TransferOperator();

        // 记账
        $transferId = \DB::transaction(function () use ($resourceUser, $operator) {
            // 检查余额
            $item = $this->getRow('house_resource_users', $resourceUser->id)->first(['balance']);
            if (object_get($item, 'balance', 0) < HouseResourceBill::MIN_WITHDRAW) {
                $this->setErrMsg("余额不足" . HouseResourceBill::MIN_WITHDRAW . "元，禁止提现！");
                return null;
            }
            $yuan = $item->balance;

            // 转账总账单
            $transferId = $operator->create(
                \PingPPHelper::CHANNEL_微信公众号,
                Amount::yuan2fen($yuan),
                $resourceUser->user->mobile
            );

            // 房源账单
            \DB::table('house_resource_bills')->insertGetId([
                'house_resource_user_id' => $resourceUser->id,
                'type' => HouseResourceBill::TYPE_转出,
                'amount' => $yuan,
                'client_ip' => \Input::ip(),
                'tag' => HouseResourceBill::TAG_提现,
                'note' => "{$resourceUser->user->mobile}提现{$yuan}元",
                'transfer_id' => $transferId,
                'updated_at' => Carbon::now(),
                'created_at' => Carbon::now(),
            ]);

            // 改用户余额
            $this->getRow('house_resource_users', $resourceUser->id)
                ->update(['balance' => 0, 'updated_at' => Carbon::now()]);

            return $transferId;
        });

        // 实际转账
        $operator->transfer($resourceUser->user, $transferId);
    }
}
<?php

namespace App\Traits\Models;

use App\Enums\OfferPointTypes;
use App\Enums\RewardRequestStatus;
use App\Models\Point;
use App\Models\RewardRequest;
use App\Models\User;
use sloom\Promocodes\Models\Promocode;

trait CollectPoints
{
    public function points()
    {
        return $this->morphMany(Point::class, 'collector');
    }

    public function rewardsRequests()
    {
        return $this->morphMany(RewardRequest::class, 'requester');
    }

    public function getTotalPointsAttribute()
    {
        return
            $this->points()->sum('points')
            -
            $this->rewardsRequests()->with(['reward'])->ofNotStatus(RewardRequestStatus::REJECTED)->get()->sum('reward.require_points');
    }

    public function addPointToCreateCoupon(Promocode $coupon)
    {
        $points = $coupon->store->created_coupon_points;

        $this->points()->create([
            'title' => "إنشاء كوبون جديد : {$coupon->title} ",
            'description' => null,
            'type' => OfferPointTypes::STORE_CREATED_COUPON,
            'rewardable_id' => $coupon->id,
            'rewardable_type' => Promocode::class,
            'points' => $points,
        ]);
    }

    public function addPointToApplyCoupon(Promocode $coupon, User $user)
    {
        $store_points = $coupon->apply_store_points;
        $user_points = $coupon->apply_user_points;

        $this->points()->create([
            'title' => "استخدام جديد للكوبون",
            'description' => null,
            'type' => OfferPointTypes::STORE_RECEIVED_APPLY_COUPON,
            'rewardable_id' => $user->id,
            'rewardable_type' => User::class,
            'points' => $store_points,
        ]);

        $user->points()->create([
            'title' => "تم استخدام كوبون من متجر : {$coupon->store->name} ",
            'description' => null,
            'type' => OfferPointTypes::USER_APPLY_COUPON,
            'rewardable_id' => $coupon->id,
            'rewardable_type' => Promocode::class,
            'points' => $user_points,
        ]);
    }
}

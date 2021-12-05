<?php

use Illuminate\Http\Request;
use App\Http\Controllers\PassportController;
use App\Http\Controllers\PersonalController;
use App\Http\Controllers\PersonalLoginController;
use App\Http\Controllers\DriverController;
use App\Http\Controllers\DriverLoginController;
use App\Http\Controllers\PublicController;



//public api route
Route::post('send-otp-message', [PublicController::class,'SendOTPMessage']);

Route::get('test-fireabse', [PassportController::class,'testFireabse']);
Route::get('driver-lat-long/{userid}/{lat}/{long}', [PassportController::class,'driverLog']);
Route::post('delivery-login', [PassportController::class,'deliverylogin']);
Route::get('app-settings', [PassportController::class,'appSettings']);
Route::post('set-parcel-service',[PassportController::class,'setParcelService']);
Route::get('last-orders', [PassportController::class,'lastFiveminutesOrder']);

// driver login and register api route
Route::post('register-delivery', [DriverLoginController::class,'registerdelivery']);
Route::post('driver-phone-number-check', [DriverLoginController::class,'driverphoneNumberCheck']);

// personal login and register api route
Route::post('register-personal', [PersonalLoginController::class,'registerPersonal']);
Route::post('personal-phone-number-check', [PersonalLoginController::class,'personalphoneNumberCheck']);

// personal auth api route
Route::group(['prefix' => 'personal', 'middleware' => 'personal'], function(){

    Route::post('orderlist', [PersonalController::class,'orderlistPersonal']);
    Route::get('orderlist/{id}', [PersonalController::class,'orderDetails']);
    Route::post('pendingorder-list', [PersonalController::class,'pendingorderlist']);
    Route::post('collected-order-list-quick', [PersonalController::class,'collectedorderlistQuick']);
    Route::post('collected-order-list-express', [PersonalController::class,'collectedorderlistExpress']);
    Route::get('user', [PersonalController::class,'userInfo']);
    Route::post('rating', [PersonalController::class,'rating']);

    Route::post('current-order-list-regular', [PersonalController::class,'currentorderlistQuick']);
    Route::post('all-ongoing-order', [PersonalController::class,'allOngoingOrder']);
    Route::post('current-order-list-express', [PersonalController::class,'currentorderlistExpress']);

    Route::post('order-list-express', [PersonalController::class,'orderListExpress']);
    Route::post('order-list-regular', [PersonalController::class,'orderlistRegular']);
    Route::post('all-order-list', [PersonalController::class,'allOrderList']);

    Route::post('user-update', [PersonalController::class,'userupdate']);
    Route::post('set-parcel',[PersonalController::class,'setParcel']);
    Route::get('delete-fcm-token', [PersonalController::class,'deleteFcmToken']);

    Route::post('add-fcm-token', [PersonalController::class,'addFcmToken']);
    Route::get('profile', [PersonalController::class,'personalprofile']);

    Route::post('check-coupon', [PersonalController::class,'checkCoupons']);
    Route::get('coupon-image', [PersonalController::class,'couponImage']);
    Route::post('set-rating', [PersonalController::class,'setRating']);
    Route::get('user-based-coupons', [PersonalController::class,'userBasedCoupon']);
    Route::post('get-driver-location', [PersonalController::class,'getDriverArea']);
    Route::post('configuration-merge', [PersonalController::class,'MergeApi']);

});

// driver auth api route
Route::group(['prefix' => 'delivery', 'middleware' => 'driver'], function(){

    Route::post('configuration-update', [DriverController::class,'configUpdate']);
    Route::get('area', [DriverController::class,'allArea']);
    Route::post('prefered-area-list-add',[DriverController::class,'preferedAreaListAdd']);
    Route::post('prefered-area-list-view',[DriverController::class,'preferedAreaListView']);
    Route::post('prefered-area-list-delete',[DriverController::class,'preferedAreaListDelete']);
    Route::post('all-order-list',[DriverController::class,'preferedAreaOrderList']);
    Route::post('search-order-list',[DriverController::class,'preferedAreaSearchOrderList']);
    Route::post('single-search-order-list',[DriverController::class,'preferedAreaSingleSearchOrderList']);
    Route::post('delivery-current-orderlist', [DriverController::class,'deliverycurrentorderlist']);
    Route::get('company-wise-order',[DriverController::class,'CompanyWiseOrder']);
    Route::post('order-status-change',[DriverController::class,'orderstatuschange']);
    Route::post('order-history',[DriverController::class,'orderHistory']);
    Route::post('user-update',[DriverController::class,'deliveryUserUpdate']);
    Route::get('profile',[DriverController::class,'driverProfile']);
    Route::get('delete-fcm-token', [DriverController::class,'deleteFcmToken']);

    Route::post('orderlist', [DriverController::class,'rangeorderlist']);
    Route::post('prefered-order-list', [DriverController::class,'preferedorderlist']);
    Route::get('orderlist/{id}', [DriverControllerD::class,'deliveryorderDetails']);
    Route::post('order-logistics-assign', [DriverController::class,'orderLogisticsAssign']);
    Route::post('delivery-confirm-orderlist', [DriverController::class,'deliveryconfirmorderlist']);

    Route::get('profile', [DriverController::class,'deliveryprofile']);
    Route::post('prefered-area-list',[DriverController::class,'preferedAreaList']);

    Route::post('prefered-area-order-list',[DriverController::class,'preferedAreaOrderList']);

    Route::get('wallet', [DriverController::class,'wallet']);
    Route::get('get-average-rating', [DriverController::class,'getAvgRating']);
    Route::post('pending-order-list',[DriverController::class,'pendingOrderList']);

});


Route::post('auth/login', [PassportController::class,'authLogin']);
Route::post('v1/create-parcel', [PassportController::class,'createParcel']);
Route::get('v1/check-parcel', [PassportController::class,'checkParcel']);

<?php

use Illuminate\Http\Request;
use App\Http\Controllers\PassportController;
use App\Http\Controllers\PersonalController;
use App\Http\Controllers\PersonalLoginController;
use App\Http\Controllers\DriverController;
use App\Http\Controllers\DriverLoginController;





Route::get('test-fireabse', [PassportController::class,'testFireabse']);
Route::get('driver-lat-long/{userid}/{lat}/{long}', [PassportController::class,'driverLog']);
Route::post('driver-phone-number-check', [DriverLoginController::class,'driverphoneNumberCheck']);
Route::post('personal-phone-number-check', [PersonalLoginController::class,'personalphoneNumberCheck']);
Route::post('delivery-login', [PassportController::class,'deliverylogin']);
Route::post('register-personal', [PersonalLoginController::class,'registerPersonal']);
Route::post('register-delivery', [DriverLoginController::class,'registerdelivery']);
Route::get('app-settings', [PassportController::class,'appSettings']);
Route::post('send-otp-message', [PassportController::class,'SendOTPMessage']);
Route::post('set-parcel-service',[PassportController::class,'setParcelService']);
Route::get('last-orders', [PassportController::class,'lastFiveminutesOrder']);




// personal route
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



Route::group(['prefix' => 'delivery', 'middleware' => 'driver'], function(){

    Route::get('area', [PassportController::class,'area']);
    Route::post('orderlist', [PassportController::class,'rangeorderlist']);
    Route::post('prefered-order-list', [PassportController::class,'preferedorderlist']);
    Route::get('orderlist/{id}', [PassportController::class,'deliveryorderDetails']);
    Route::post('order-logistics-assign', [PassportController::class,'orderLogisticsAssign']);
    Route::post('delivery-confirm-orderlist', [PassportController::class,'deliveryconfirmorderlist']);
    Route::post('delivery-current-orderlist', [PassportController::class,'deliverycurrentorderlist']);
    Route::get('profile', [PassportController::class,'deliveryprofile']);
    Route::post('prefered-area-list',[PassportController::class,'preferedAreaList']);
    Route::post('prefered-area-list-add',[DriverController::class,'preferedAreaListAdd']);
    Route::post('prefered-area-list-view',[PassportController::class,'preferedAreaListView']);
    Route::post('prefered-area-list-delete',[PassportController::class,'preferedAreaListDelete']);
    Route::post('prefered-area-order-list',[PassportController::class,'preferedAreaOrderList']);
    Route::post('search-order-list',[PassportController::class,'preferedAreaSearchOrderList']);
    Route::post('single-search-order-list',[PassportController::class,'preferedAreaSingleSearchOrderList']);
    Route::post('order-history',[PassportController::class,'orderHistory']);
    Route::post('user-update',[PassportController::class,'deliveryUserUpdate']);
    Route::post('order-status-change',[PassportController::class,'orderstatuschange']);
    Route::get('delete-fcm-token', [PassportController::class,'deleteFcmToken']);

    Route::post('configuration-update', [PassportController::class,'configUpdate']);
    Route::get('wallet', [PassportController::class,'wallet']);
    Route::get('get-average-rating', [PassportController::class,'getAvgRating']);
    Route::post('all-order-list',[PassportController::class,'preferedAreaOrderList']);
    Route::get('company-wise-order',[PassportController::class,'CompanyWiseOrder']);
    Route::get('profile',[PassportController::class,'driverProfile']);
    Route::post('pending-order-list',[PassportController::class,'pendingOrderList']);

});


Route::post('auth/login', [PassportController::class,'authLogin']);
Route::post('v1/create-parcel', [PassportController::class,'createParcel']);
Route::get('v1/check-parcel', [PassportController::class,'checkParcel']);

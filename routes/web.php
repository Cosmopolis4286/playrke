<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Cookie;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\ExchangeRateController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\BinanceController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\CommerceController;
use App\Http\Controllers\SellerController;
use App\Http\Controllers\BingoController;
use App\Http\Controllers\TriviaController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\DirectoryController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\ReferralController;
use App\Http\Controllers\IframeController;
use App\Http\Controllers\PagoMovilController;
use App\Http\Controllers\PixController;
use App\Http\Controllers\InformationController;
use App\Http\Controllers\PaymentMethodsCOLController;
use App\Http\Controllers\PollaController;
use App\Http\Controllers\PredictionController;
use App\Http\Controllers\YankenpoController;
use App\Models\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

require __DIR__ . '/auth.php';

Route::get('/', function () {
    $register = Settings::getStatus('REGISTER');
    return view('auth.login', compact('register'));
});

Route::get('get/info/{page}', function ($page) {
    if (in_array($page, ['terms', 'about', 'privacy'])) {
        return view("includes.info.$page");
    }
    abort(404);
})->name('info-rke');

Route::group(['middleware' => ['auth'], 'prefix' => 'profile', 'as' => 'profile.'], function () {
    Route::get('', [ProfileController::class, 'index'])->name('index');
    Route::put('{id}', [ProfileController::class, 'update'])->name('update');
    Route::patch('change-password/{id}', [ProfileController::class, 'changePassword'])->name('change-password');
});

Route::get('dashboard', [HomeController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');
Route::post('upd/ts', [HomeController::class, 'upd_ts'])->name('upd_ts')->middleware('auth');

Route::group(['middleware' => ['auth'], 'prefix' => 'referidos', 'as' => 'refe.'], function () {
    Route::get('{referralCode}', [ReferralController::class, 'index'])->name('index');
    Route::get('r/{referralCode}', [ReferralController::class, 'link'])->name('referral.link');
});

Route::get('cookie', function () {
    return Cookie::get('referral');
})->middleware('auth');

Route::post('/clear-flash-messages', function (Request $request) {
    // Obtener los nombres de los mensajes flash a eliminar desde la solicitud
    $messageKeys = $request->input('message_keys', []);

    foreach ($messageKeys as $messageKey) {
        if (Session::has($messageKey)) {
            Session::forget($messageKey); // Eliminar el mensaje flash
        }
    }
    return response()->json(['status' => 'success']);
})->name('clear.flash.messages');

// Obtener hora del servidor
Route::get('get/RKETIMESERV/{timeZone?}', [InformationController::class, 'getTimeServer'])->name('info.getTimeServer');

Route::group(['middleware' => 'auth', 'prefix' => 'image', 'as' => 'image.'], function () {
    Route::get('payments/prizes/{filename}', [FileController::class, 'showImagePaymentPrizeCtns'])->name('show-image-payment-prize-ctns');
    Route::get('payments/{filename}', [FileController::class, 'showImagePaymentCtns'])->name('show-image-payment-ctns');
});

Route::group(['middleware' => ['auth', 'admin'], 'prefix' => 'admin', 'as' => 'admin.'], function () {
    Route::get('users', [AdminController::class, 'users'])->name('users');
    Route::get('commerces', [AdminController::class, 'commerces'])->name('commerces');
    Route::get('subsidiaries', [AdminController::class, 'subsidiaries'])->name('subsidiaries');
    Route::get('sellers', [AdminController::class, 'sellers'])->name('sellers');
    Route::get('customers', [AdminController::class, 'customers'])->name('customers');
});

Route::group(['middleware' => ['auth'], 'prefix' => 'info', 'as' => 'info.'], function () {
    Route::get('terms', [InformationController::class, 'terms'])->name('terms');
    Route::get('about', [InformationController::class, 'about'])->name('about');
    Route::get('privacy', [InformationController::class, 'privacy'])->name('privacy');
    Route::get('help-center', [InformationController::class, 'helpCenter'])->name('help-center');
    Route::get('power-of-knowledge', [InformationController::class, 'powerOfKnowledge'])->name('power-of-knowledge');
});

// Rutas del Conn
Route::group(['middleware' => ['auth'], 'prefix' => 'payment', 'as' => 'payment.'], function () {
    Route::get('proof/{fileName}', [PaymentController::class, 'showPaymentProof'])->name('proof');
});

// Tasa de Cambio/ExhangeRate
Route::group(['prefix' => 'exchange-rate', 'as' => 'exchange-rate.'], function () {
    Route::post('create', [ExchangeRateController::class, 'create'])->name('create');
    Route::get('getInfo/{id}', [ExchangeRateController::class, 'getInfo'])->where('id', '.*')->name('getInfo');
    Route::put('update/{id}', [ExchangeRateController::class, 'update'])->name('update');
    Route::put('delete/{id}', [ExchangeRateController::class, 'delete'])->name('delete');
    Route::put('change-status/{id}', [ExchangeRateController::class, 'changeStatus'])->name('change-status');
    Route::get('getLastValueByCoin/{coin}', [ExchangeRateController::class, 'getLastValueByCoin'])->name('getLastValueByCoin');
    Route::get('historical/{coin?}/{creatorType}/{creatorID}', [ExchangeRateController::class, 'historical'])->name('historical');
    Route::get('{creatorType}/{creatorID?}', [ExchangeRateController::class, 'index'])->name('index');
});

// Iframes
Route::group(['middleware' => 'auth', 'prefix' => 'iframe', 'as' => 'iframe.'], function () {
    Route::get('', [IframeController::class, 'index'])->name('index');
    Route::get('get/route/game-rooms/{userToken}', [IframeController::class, 'getRouteGameRooms'])->name('get-route-game-rooms');
});

// Directorio-Datos Bancarios
Route::group(['middleware' => 'auth', 'prefix' => 'directory', 'as' => 'directory.'], function () {
    Route::get('{countryCode}', [DirectoryController::class, 'index'])->name('index');
});

//Cliente
Route::group(['middleware' => ['auth', 'customer'], 'prefix' => 'customer', 'as' => 'customer.'], function () {
    Route::get('getUserId/{userToken}', [CustomerController::class, 'getUserId'])->name('getUserId');
    Route::get('getUserBalance/{userToken}', [CustomerController::class, 'getUserBalance'])->name('getUserBalance');
    Route::get('increaseBalance/{userToken}/{balance?}', [CustomerController::class, 'increaseBalance'])->name('increaseBalance');
});

// Cliente Options ADMIN
Route::group(['middleware' => ['auth', 'admin'], 'prefix' => 'admin-customer', 'as' => 'admin-customer.'], function () {
    Route::post('create', [CustomerController::class, 'create'])->name('create');
    Route::get('getInfo/{id}', [CustomerController::class, 'getInfo'])->name('getInfo');
    Route::put('update/{id}', [CustomerController::class, 'update'])->name('update');
    Route::put('delete/{id}', [CustomerController::class, 'delete'])->name('delete');
    Route::put('change-status/{id}', [CustomerController::class, 'changeStatus'])->name('change-status');
    Route::put('change-passw/{id}', [CustomerController::class, 'changePassword'])->name('change-pasww');
});

//Comercio/Comercializadora/Banca
Route::group(['middleware' => ['auth', 'commerce'], 'prefix' => 'commerce', 'as' => 'commerce.'], function () {
    Route::get('getPaymentCommerceDetails/{commerce}/{coin}', [CommerceController::class, 'getPaymentCommerceDetails'])
        ->withoutMiddleware(['auth', 'commerce'])->name('getPaymentCommerceDetails');
    Route::get('all-directories', [CommerceController::class, 'allDirectories'])->name('allDirectories');
    Route::get('sellers', [CommerceController::class, 'sellers'])->name('sellers');
    Route::get('seller/getCoinByCountry/{country}/{seller}', [CommerceController::class, 'getCoinByCountry'])->name('getCoinByCountry');
    Route::get('seller/getSellerInfoByCtn/{ctnId}', [CommerceController::class, 'getSellerInfoByCtn'])->name('getSellerInfoByCtn');
    Route::get('bingo/players/my-winners/{dateFilter?}', [CommerceController::class, 'getBingoWinnersByCommerce'])->name('getBingoWinnersByCommerce');
    Route::get('bingo/players/other-winners/{dateFilter?}', [CommerceController::class, 'getBingoWinnersByOtherCommerces'])->name('getBingoWinnersByOtherCommerces');
    Route::get('bingo/carton-sold/{dateFilter?}/{coin?}', [CommerceController::class, 'ctnsSold'])->name('ctnsSold');
    Route::put('bingo/carton-lot/payment', [CommerceController::class, 'ctnsLotPayment'])->name('ctnsLotPayment');
    Route::get('bingo/sales-report/{coin?}/{dateFilter?}', [CommerceController::class, 'reportSalesRooms'])->name('reportSalesRooms');
    Route::get('bingo/sales-report/room/{room}/{coin}', [CommerceController::class, 'reportSalesByRoom'])->name('reportSalesByRoom');
});

// Commerce Options ADMIN
Route::group(['middleware' => ['auth', 'admin'], 'prefix' => 'admin-commerce', 'as' => 'admin-commerce.'], function () {
    Route::post('create', [CommerceController::class, 'create'])->name('create');
    Route::get('getInfo/{id}', [CommerceController::class, 'getInfo'])->name('getInfo');
    Route::put('update/{id}', [CommerceController::class, 'update'])->name('update');
    Route::put('delete/{id}', [CommerceController::class, 'delete'])->name('delete');
    Route::put('change-status/{id}', [CommerceController::class, 'changeStatus'])->name('change-status');
    Route::put('change-passw/{id}', [CommerceController::class, 'changePassword'])->name('change-pasww');
});

// Seller Options 
Route::group(['middleware' => ['auth', 'commerce'], 'prefix' => 'seller', 'as' => 'seller.'], function () {
    Route::post('create', [SellerController::class, 'create'])->name('create');
    Route::get('getInfo/{id}', [SellerController::class, 'getInfo'])->name('getInfo');
    Route::put('update/{id}', [SellerController::class, 'update'])->name('update');
    Route::put('delete/{id}', [SellerController::class, 'delete'])->name('delete');
    Route::put('change-status/{id}', [SellerController::class, 'changeStatus'])->name('change-status');
    Route::put('change-passw/{id}', [SellerController::class, 'changePassword'])->name('change-pasww');
});

//Vendedor/Agencia/Taquilla
Route::group(['middleware' => ['auth', 'seller'], 'prefix' => 'seller', 'as' => 'seller.'], function () {
    Route::get('getPaymentCommerceDetails/{coin}', [SellerController::class, 'getPaymentCommerceDetails'])->name('getPaymentCommerceDetails');
    Route::get('bingo/carton/generate', [SellerController::class, 'cartonGenerate'])->name('cartonGenerate');
    Route::post('bingo/carton/generate/store', [BingoController::class, 'generateCarton'])->name('generateCarton');
    Route::get('bingo/carton/get/price/{roomID}', [SellerController::class, 'getCtnPrice'])->name('getCtnPrice');
    Route::get('bingo/get/ctn-price-coin/{room}/{country}', [SellerController::class, 'getCtnPriceCoin'])->name('getCtnPriceCoin');
    Route::post('bingo/player/get/player', [SellerController::class, 'getPlayer'])->name('getPlayer');
    Route::get('bingo/carton/sold/{coin?}/{dateFilter?}', [SellerController::class, 'ctnsSold'])->name('ctnsSold');
    Route::get('bingo/player/cartons/{playerLink}{room}', [SellerController::class, 'showPlayerCartons'])->name('showPlayerCartons');
    Route::get('bingo/carton/awarded/{coin?}/{dateFilter?}', [SellerController::class, 'ctnsAwarded'])->name('ctnsAwarded');
    Route::get('bingo/getWinnerInfoForCtn/{ctnId}', [SellerController::class, 'getWinnerInfoForCtn'])->name('getWinnerInfoForCtn');
    Route::get('bingo/player/{playerLink}/info', [SellerController::class, 'getPlayerInfo'])->name('getPlayerInfo');
    Route::post('bingo/carton/payment/status', [SellerController::class, 'ctnsLotPayment'])->name('ctnsLotPayment');
    Route::get('bingo/report/sales/rooms/{coin?}/{dateFilter?}', [SellerController::class, 'reportSalesRooms'])->name('reportSalesRooms');
    Route::get('bingo/sales-report/room/{room}/{coin}', [SellerController::class, 'reportSalesByRoom'])->name('reportSalesByRoom');
    Route::get('bingo/report/sales/room/{room}/player/{playerLink}', [SellerController::class, 'reportSalesByRoomPlayer'])->name('reportSalesByRoomPlayer');
});

Route::group(['middleware' => ['auth'], 'prefix' => 'report', 'as' => 'report.'], function () {
    Route::get('', [ReportsController::class, 'index'])->name('index');
    Route::post('store', [ReportsController::class, 'store'])->name('store');
});

Route::group(['middleware' => ['auth'], 'prefix' => 'pagomovil', 'as' => 'pagomovil.'], function () {
    Route::get('', [PagoMovilController::class, 'index'])->name('index');
    Route::post('create', [PagoMovilController::class, 'create'])->name('create');
    Route::put('update/{id}', [PagoMovilController::class, 'update'])->name('update');
    Route::put('delete/{id}', [PagoMovilController::class, 'delete'])->name('delete');
    Route::get('getInfo/{id}', [PagoMovilController::class, 'getInfo'])->name('getInfo');
});

Route::group(['middleware' => ['auth'], 'prefix' => 'pix', 'as' => 'pix.'], function () {
    Route::get('', [PixController::class, 'index'])->name('index');
    Route::post('create', [PixController::class, 'create'])->name('create');
    Route::put('update/{id}', [PixController::class, 'update'])->name('update');
    Route::put('delete/{id}', [PixController::class, 'delete'])->name('delete');
    Route::get('getInfo/{id}', [PixController::class, 'getInfo'])->name('getInfo');
    Route::get('get/banks', [PixController::class, 'getBanks'])->name('getBanks');
});

Route::group(['middleware' => ['auth'], 'prefix' => 'payment-method-col', 'as' => 'payment-method-col.'], function () {
    Route::post('create', [PaymentMethodsCOLController::class, 'create'])->name('create');
    Route::put('update/{id}', [PaymentMethodsCOLController::class, 'update'])->name('update');
    Route::put('delete/{id}', [PaymentMethodsCOLController::class, 'delete'])->name('delete');
    Route::get('getInfo/{id}', [PaymentMethodsCOLController::class, 'getInfo'])->name('getInfo');
});

Route::group(['middleware' => ['auth'], 'prefix' => 'binance', 'as' => 'binance.'], function () {
    Route::post('create', [BinanceController::class, 'create'])->name('create');
    Route::put('update/{id}', [BinanceController::class, 'update'])->name('update');
    Route::put('delete/{id}', [BinanceController::class, 'delete'])->name('delete');
    Route::get('getInfo/{id}', [BinanceController::class, 'getInfo'])->name('getInfo');
});

Route::group(['middleware' => ['auth'], 'prefix' => 'wallet', 'as' => 'wallet.'], function () {
    Route::get('', [WalletController::class, 'index'])->name('index');
    Route::get('deposit/pagomovil', [WalletController::class, 'createPM'])->name('createPM');
    Route::post('storePM_T', [WalletController::class, 'storePM_T'])->name('storePM_T');
    Route::get('withdrawal/pagomovil', [WalletController::class, 'rt_PM'])->name('rt_PM');
    Route::post('run_rt_PM', [WalletController::class, 'run_rt_PM'])->name('run_rt_PM');
    Route::get('deposit/bpay', [WalletController::class, 'createBPay'])->name('createBPay');
    Route::get('?transk_id={$id}{$id2}', [WalletController::class, 'createTransWebPay'])->name('createTransWebPay');
    Route::post('storeBPay', [WalletController::class, 'storeBPay'])->name('storeBPay');
    Route::get('withdrawal/bpay', [WalletController::class, 'retiroBPay'])->name('retiroBPay');
    Route::post('rtBPay', [WalletController::class, 'rtBPay'])->name('rtBPay');
    Route::get('deposit/blockchain', [WalletController::class, 'createBlock'])->name('createBlock');
    Route::post('storeBlock', [WalletController::class, 'storeBlock'])->name('storeBlock');
    Route::get('withdrawal/blockchain', [WalletController::class, 'retiroBlock'])->name('retiroBlock');
    Route::post('retiroBlock', [WalletController::class, 's_retiroBlock'])->name('s_retiroBlock');
    Route::post('transferUser', [WalletController::class, 'transferUser'])->name('transferUser');
    Route::get('transfer/coins', [WalletController::class, 'transfer_coins'])->name('transfer_coins');
    Route::get('transfer/usdt', [WalletController::class, 'transfer_usdt'])->name('transfer_usdt');
    Route::get('triv_multi/{id}/{id2}', [WalletController::class, 'debit_triv_multi'])->name('debit_triv_multi');
    Route::post('triv_indv', [WalletController::class, 'debit_triv_indv'])->name('debit_triv_indv');
    Route::post('triv_pvp', [WalletController::class, 'debit_triv_pvp'])->name('debit_triv_pvp');
    Route::get('Wallet/dbusdt/{id}/{id2}', [WalletController::class, 'dbusdt'])->name('dbusdt');
});

Route::group(['middleware' => ['auth'], 'prefix' => 'games', 'as' => 'games.'], function () {
    Route::put('handleCustomerGames/{id?}', [GameController::class, 'handleCustomerGames'])
        ->name('handleCustomerGames')->withoutMiddleware(['auth']);
});

Route::group(['middleware' => ['auth'], 'prefix' => 'trivia', 'as' => 'trivia.'], function () {
    Route::get('all', [TriviaController::class, 'allRooms'])->name('allRooms');
    Route::get('{coin}', [TriviaController::class, 'rooms'])->name('rooms');
    Route::get('time_serv', [TriviaController::class, 'time_serv'])->name('time_serv');
    Route::get('timer/{id}', [TriviaController::class, 'timer'])->name('timer');
    Route::get('{id}/status', [TriviaController::class, 'changeStatusRoom'])->name('status');
    Route::get('cod', [TriviaController::class, 'cod_tri'])->name('cod_tri');
    Route::get('time', [TriviaController::class, 'dateTrivi'])->name('date_tri');
    Route::get('all/bs', [TriviaController::class, 'list'])->name('list');
    Route::get('all/usdt', [TriviaController::class, 'listusdt'])->name('listusdt');
    Route::get('create', [TriviaController::class, 'create'])->middleware(['admin'])->name('create');
    Route::post('store', [TriviaController::class, 'store'])->name('store');
    Route::post('store/cod', [TriviaController::class, 'storeCod'])->name('storeCod');
    Route::get('edit/sound', [TriviaController::class, 'edit_sound'])->name('edit_sound');
    Route::post('sound/store', [TriviaController::class, 'store_sound'])->name('store_sound');
    Route::get('multi/{coin}/{id}', [TriviaController::class, 'roomMulti'])->name('roomMulti');
    // Route::get('usdt/{id}', [TriviaController::class, 'room_usdt]'])->name('show_usdt');
    // Route::get('room/{id}', [TriviaController::class, 'room_multi'])->name('room_multi');
    Route::get('edit/{id}', [TriviaController::class, 'editTrivaMulti'])->middleware(['admin'])->name('editTrivaMulti');
    Route::post('update', [TriviaController::class, 'updateTriviaMulti'])->name('updateTriviaMulti');
    Route::post('insertp', [TriviaController::class, 'insertp'])->name('insertp');
    Route::post('update_p', [TriviaController::class, 'update_p'])->name('update_p');
    Route::post('update_questions', [TriviaController::class, 'respuestas'])->name('respuestas');
    Route::post('start', [TriviaController::class, 'start'])->name('start');
    Route::post('comodin', [TriviaController::class, 'comond_rest'])->name('comond_rest');
    Route::post('fin_timeQ', [TriviaController::class, 'fin_timeQ'])->name('liveUp');
    Route::get('buy_life', [TriviaController::class, 'buy_lives'])->name('b_lives');
    Route::get('revive', [TriviaController::class, 'revive'])->name('revive');
    Route::get('endQ', [TriviaController::class, 'fin_pre'])->name('endQ');
    Route::post('discount', [TriviaController::class, 'discount_off'])->name('united');
    Route::get('update_info/{id}', [TriviaController::class, 'update_info'])->name('update_info');
});

Route::group(['middleware' => ['auth'], 'prefix' => 'triv_in', 'as' => 'triv_in.'], function () {
    Route::get('create/quest/indv', [TriviaController::class, 'create_quest_indv'])->name('create_quest');
    Route::post('insert/quest/indv', [TriviaController::class, 'insert_quest_indv'])->name('insert_quest');
    Route::post('edit/quest/indv', [TriviaController::class, 'edit_quest_indv'])->name('edit_quest');
    Route::get('room', [TriviaController::class, 'room_indv'])->name('room');
    Route::post('spy/triv/indv', [TriviaController::class, 'spy_triv_indv'])->name('spy');
    Route::post('start/triv/indv', [TriviaController::class, 'start_triv_indv'])->name('start');
    Route::post('end_time/triv/indv', [TriviaController::class, 'end_time_triv_indv'])->name('end_time');
    Route::post('c_half/triv/indv', [TriviaController::class, 'c_half_indv'])->name('c_half');
    Route::post('anwers/triv/indv', [TriviaController::class, 'anwers_triv_indv'])->name('anwers');
    Route::post('next_anwers/triv/indv', [TriviaController::class, 'insert_next_question'])->name('next_question');
    Route::post('retire/triv/indv', [TriviaController::class, 'retire_triv_indv'])->name('retire');
    Route::post('time_retire/triv/indv', [TriviaController::class, 'time_retire_triv_indv'])->name('time_retire');
});

Route::group(['middleware' => ['auth'], 'prefix' => 'triv_pvp', 'as' => 'triv_pvp.'], function () {
    Route::get('room', [TriviaController::class, 'room_pvp'])->name('room');
    Route::get('refresh', [TriviaController::class, 'refresh_pvp'])->name('refresh');
});

Route::group(['prefix' => 'bingo', 'as' => 'bingo.'], function () {
    Route::get('configuration/plays/{userToken?}', [BingoController::class, 'handleBingoPlays'])->name('handleBingoPlays');
    Route::get('configuration/{userToken?}', [BingoController::class, 'configuration'])->name('configuration');
    
    Route::get('getInfo/{id}/{starTime?}', [BingoController::class, 'getInfo'])->name('getInfo');
    Route::get('getInfoByEncrypt/{id}/{creatorUser}/{coin}', [BingoController::class, 'getInfoByEncrypt'])->name('getInfoByEncrypt');
    Route::get('getDateTimeRooms/{creatorUser}', [BingoController::class, 'getDateTimeRooms'])->name('get-datetime-rooms');
    Route::get('getCtnPriceForCoin/{creation}/{creatorType}/{creatorID}/{room?}', [BingoController::class, 'getCtnPriceForCoin'])->name('getCtnPriceForCoin');

    Route::post('carton/notify/payment/win-ctn', [BingoController::class, 'notifyPaymentPrizeWinningCtn'])->name('notifyPaymentPrizeWinningCtn');

    Route::put('handle-bingo-rules/{id?}', [BingoController::class, 'handleBingoRules'])->name('handleBingoRules');
    Route::post('create', [BingoController::class, 'create'])->name('create');
    Route::put('update/{id}', [BingoController::class, 'update'])->name('update');
    Route::put('delete/{id}', [BingoController::class, 'delete'])->name('delete');
    Route::put('status/{id}', [BingoController::class, 'changeStatus'])->name('change-status');
    Route::put('replay/{id}', [BingoController::class, 'replay'])->name('replay');
    Route::put('publish/{id}', [BingoController::class, 'publish'])->name('publish');
    Route::put('refund/{id}', [BingoController::class, 'refund'])->name('refund');
    Route::put('copy/{id}', [BingoController::class, 'copy'])->name('copy');

    /// Rutas de Salas Automaticas
    Route::get('automatic-room/getInfo/{id}', [BingoController::class, 'getInfoAutomaticRoom'])->name('automatic-room.getInfo');
    Route::post('automatic-room/create', [BingoController::class, 'createAutomaticRoom'])->name('automatic-room.create');
    Route::put('automatic-room/update/{id}', [BingoController::class, 'updateAutomaticRoom'])->name('automatic-room.update');
    Route::put('automatic-room/change-status/{id}', [BingoController::class, 'changeStatusAutomaticRoom'])->name('automatic-room.change-status');
    Route::put('automatic-room/delete/{id}', [BingoController::class, 'deleteAutomaticRoom'])->name('automatic-room.delete');
    Route::put('automatic-room/copy/{id}', [BingoController::class, 'copyAutomaticRoom'])->name('automatic-room.copy');
    Route::get('automatic-room/executeGenerateBingoRoomsJob/{creatorType}/{creatorID}', [BingoController::class, 'executeGenerateBingoRoomsJob'])->name('automatic-room.executeGenerateBingoRoomsJob');

    Route::post('test/regenerateDraw', [BingoController::class, 'regenerateDraw'])->name('test.regenerateDraw');

    Route::get('game-rooms/admin/{user}/{coin?}', [BingoController::class, 'gamesRooms'])
        ->middleware(['auth', 'admin'])->name('game-rooms-admin');

    Route::get('game-rooms/cust/{customer}/{user}/{coin?}', [BingoController::class, 'gamesRoomsCustomer'])->name('game-rooms-customer');

    Route::get('{roomType}/game-rooms/{coin?}', [BingoController::class, 'gamesRoomsCommerce'])
        ->middleware(['auth', 'commerce'])->name('game-rooms-commerce');

    Route::get('{roomType}/{creatorType}/{creatorID?}/{coin?}', [BingoController::class, 'allRooms'])->name('all-rooms');

    Route::post('actWn', [BingoController::class, 'actWn'])->middleware(['auth', 'admin'])->name('actWn');
});

// BingoCash -Jugador-
Route::group(['prefix' => 'bingocash', 'as' => 'bingocash.'], function () {

    // Rutas Jugador/Comercio TODOS
    Route::get('player/{player}/{room?}', [BingoController::class, 'inGame'])->name('in-game');
    Route::get('game-room/{uuidCode}/{room?}/{coin?}', [BingoController::class, 'outGame'])->name('out-game');

    // Rutas Jugador/Cliente
    Route::get('game/{customer}/{userToken}/room/{room}', [BingoController::class, 'inGameCustomer'])->name('in-game-customer');
    Route::get('game/{customer}/room/{room}/{coin}', [BingoController::class, 'outGameCustomer'])->name('out-game-customer');

    // Procesar Compra de Cartones
    Route::post('carton/generate/store', [BingoController::class, 'generateCarton'])->name('generateCarton');
    Route::get('getDraw/{room}', [BingoController::class, 'getDraw'])->name('getDraw');
    Route::get('get/chips/{room}', [BingoController::class, 'getChips'])->name('getChips');
    Route::get('get/result/{room}/{value}/{column?}', [BingoController::class, 'getResult'])->name('getResult');
    Route::get('viewCtn/{ctnId}', [BingoController::class, 'viewCtn'])->name('viewCtn');
});

Route::group(['prefix' => 'polla', 'as' => 'polla.'], function () {
    Route::get('', [PollaController::class, 'index'])->name('game');
    Route::get('generate-play', [PollaController::class, 'generatePlay'])->name('generate-play');
    Route::post('create', [PollaController::class, 'create'])->name('create');
    Route::get('getInfo/{id}/{starTime?}', [PollaController::class, 'getInfo'])->name('getInfo');
    Route::put('update/{id}', [PollaController::class, 'update'])->name('update');
    Route::put('delete/{id}', [PollaController::class, 'delete'])->name('delete');
    Route::put('status/{id}', [PollaController::class, 'changeStatus'])->name('change-status');
    Route::put('replay/{id}', [PollaController::class, 'replay'])->name('replay');
    Route::put('publish/{id}', [PollaController::class, 'publish'])->name('publish');
    Route::put('refund/{id}', [PollaController::class, 'refund'])->name('refund');
    Route::put('copy/{id}', [PollaController::class, 'copy'])->name('copy');
    Route::post('generate-ticket', [PollaController::class, 'generateTicket'])->name('generate-ticket');

    Route::get('sales-report/draw/{draw}/{coin}/{creatorID?}', [PollaController::class, 'reportSalesByDraw'])->name('reportSalesByDraw');
    Route::get('getTicketPriceForCoin/{creation}/{creatorType}/{creatorID}/{draw?}', [PollaController::class, 'getTicketPriceForCoin'])->name('getTicketPriceForCoin');
    Route::get('{roomType}/{creatorType}/{creatorID?}/{coin?}', [PollaController::class, 'allRooms'])->name('all-rooms');
});

Route::group(['prefix' => 'predict', 'as' => 'predict.'], function () {
    Route::get('/predict', [PredictionController::class, 'generatePrediction']);
});

Route::group(['middleware' => ['auth'], 'prefix' => 'yankenpo', 'as' => 'yankenpo.'], function () {
    Route::get('', [YankenpoController::class, 'rooms'])->name('rooms');
    Route::post('debit', [YankenpoController::class, 'debit_publish_room'])->name('debit_publish_room');
    Route::get('room/{id}', [YankenpoController::class, 'game'])->name('game');
    Route::post('select', [YankenpoController::class, 'select_ppt'])->name('select_ppt');
    Route::get('check_ppt/{id}', [YankenpoController::class, 'check_ppt'])->name('check_ppt');
});

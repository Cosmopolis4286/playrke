<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Cookie;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\ExchangeRateController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\RewardsController;
use App\Http\Controllers\BinanceController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\CommerceController;
use App\Http\Controllers\SellerController;
use App\Http\Controllers\BingoController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\DirectoryController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\ReferralController;
use App\Http\Controllers\IframeController;
use App\Http\Controllers\PagoMovilController;
use App\Http\Controllers\PixController;
use App\Http\Controllers\InformationController;
use App\Http\Controllers\OtherTreasurersController;
use App\Http\Controllers\PaymentMethodsCOLController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\TreasurerController;
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
    Route::get('treasurers', [AdminController::class, 'treasurers'])->name('treasurers');
    Route::get('other-treasurers', [AdminController::class, 'otherTreasurers'])->name('otherTreasurers');
    Route::get('get-user-info/{userRole}/{exclude?}', [AdminController::class, 'getUserInfo'])->name('getUserInfo');
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
    Route::get('getExchangeRates', [ExchangeRateController::class, 'getExchangeRates'])->name('getExchangeRates');
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

// Treasurer/Tesorero
Route::group(['middleware' => ['auth', 'treasurer'], 'prefix' => 'commerce', 'as' => 'commerce.'], function () {
    Route::get('getPaymentTreasurerDetails/{treasurer}/{coin}', [TreasurerController::class, 'getPaymentTreasurerDetails'])
        ->withoutMiddleware(['auth', 'treasurer'])->name('getPaymentTreasurerDetails');
});

// Treasurer Options ADMIN
Route::group(['middleware' => ['auth', 'admin'], 'prefix' => 'admin-treasurer', 'as' => 'admin-treasurer.'], function () {
    Route::post('create', [TreasurerController::class, 'create'])->name('create');
    Route::get('getInfo/{id}', [TreasurerController::class, 'getInfo'])->name('getInfo');
    Route::put('update/{id}', [TreasurerController::class, 'update'])->name('update');
    Route::put('delete/{id}', [TreasurerController::class, 'delete'])->name('delete');
    Route::put('change-status/{id}', [TreasurerController::class, 'changeStatus'])->name('change-status');
    Route::put('change-passw/{id}', [TreasurerController::class, 'changePassword'])->name('change-passw');
});

Route::group(['middleware' => ['auth', 'admin'], 'prefix' => 'admin-other-treasurers', 'as' => 'admin-other-treasurers.'], function () {
    Route::get('getInfo/{id}', [OtherTreasurersController::class, 'getInfo'])->name('getInfo');
    Route::put('update/{id}', [OtherTreasurersController::class, 'update'])->name('update');
    Route::put('delete/{id}', [OtherTreasurersController::class, 'delete'])->name('delete');
    Route::put('change-status/{id}', [OtherTreasurersController::class, 'changeStatus'])->name('change-status');
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
    Route::put('change-passw/{id}', [CustomerController::class, 'changePassword'])->name('change-passw');
});

//Comercio/Comercializadora/Banca
Route::group(['middleware' => ['auth', 'commerce'], 'prefix' => 'commerce', 'as' => 'commerce.'], function () {
    Route::get('getPaymentCommerceDetails/{commerce}/{coin}', [CommerceController::class, 'getPaymentCommerceDetails'])
        ->withoutMiddleware(['auth', 'commerce'])->name('getPaymentCommerceDetails');
    Route::get('sellers', [CommerceController::class, 'sellers'])->name('sellers');
    Route::get('rewards', [CommerceController::class, 'rewards'])->name('rewards');
    Route::get('seller/getCoinByCountry/{country}/{seller}', [CommerceController::class, 'getCoinByCountry'])->name('getCoinByCountry');
    Route::get('seller/getSellerInfoByCtn/{ctnId}', [CommerceController::class, 'getSellerInfoByCtn'])->name('getSellerInfoByCtn');
    Route::put('bingo/carton-lot/payment', [CommerceController::class, 'ctnsLotPayment'])->name('ctnsLotPayment');
});

// Commerce Options ADMIN
Route::group(['middleware' => ['auth', 'admin'], 'prefix' => 'admin-commerce', 'as' => 'admin-commerce.'], function () {
    Route::post('create', [CommerceController::class, 'create'])->name('create');
    Route::get('getInfo/{id}', [CommerceController::class, 'getInfo'])->name('getInfo');
    Route::put('update/{id}', [CommerceController::class, 'update'])->name('update');
    Route::put('delete/{id}', [CommerceController::class, 'delete'])->name('delete');
    Route::put('change-status/{id}', [CommerceController::class, 'changeStatus'])->name('change-status');
    Route::put('change-passw/{id}', [CommerceController::class, 'changePassword'])->name('change-passw');
});

// Seller Options 
Route::group(['middleware' => ['auth', 'commerce'], 'prefix' => 'seller', 'as' => 'seller.'], function () {
    Route::post('create', [SellerController::class, 'create'])->name('create');
    Route::get('getInfo/{id}', [SellerController::class, 'getInfo'])->name('getInfo');
    Route::put('update/{id}', [SellerController::class, 'update'])->name('update');
    Route::put('delete/{id}', [SellerController::class, 'delete'])->name('delete');
    Route::put('change-status/{id}', [SellerController::class, 'changeStatus'])->name('change-status');
    Route::put('change-passw/{id}', [SellerController::class, 'changePassword'])->name('change-passw');
});

//Vendedor/Agencia/Taquilla
Route::group(['middleware' => ['auth', 'seller'], 'prefix' => 'seller', 'as' => 'seller.'], function () {
    Route::get('getPaymentCommerceDetails/{coin}', [SellerController::class, 'getPaymentCommerceDetails'])->name('getPaymentCommerceDetails');
    Route::get('bingo/carton/generate', [SellerController::class, 'cartonGenerate'])->name('cartonGenerate');
    Route::post('bingo/carton/generate/store', [BingoController::class, 'generateCarton'])->name('generateCarton');
    Route::get('bingo/carton/get/price/{roomID}', [SellerController::class, 'getCtnPrice'])->name('getCtnPrice');
    Route::get('bingo/get/ctn-price-coin/{room}/{country}', [SellerController::class, 'getCtnPriceCoin'])->name('getCtnPriceCoin');
    Route::post('bingo/player/get/player', [SellerController::class, 'getPlayer'])->name('getPlayer');
    Route::get('bingo/player/cartons/{playerLink}{room}', [SellerController::class, 'showPlayerCartons'])->name('showPlayerCartons');
    Route::get('bingo/getWinnerInfoForCtn/{ctnId}', [SellerController::class, 'getWinnerInfoForCtn'])->name('getWinnerInfoForCtn');
    Route::get('bingo/player/{playerLink}/info', [SellerController::class, 'getPlayerInfo'])->name('getPlayerInfo');
    Route::post('bingo/carton/payment/status', [SellerController::class, 'ctnsLotPayment'])->name('ctnsLotPayment');
});

Route::group(['middleware' => ['auth', 'commerce'], 'prefix' => 'rewards', 'as' => 'rewards.'], function () {
    Route::get('', [RewardsController::class, 'index'])->name('index');
    Route::post('create', [RewardsController::class, 'create'])->name('create');
    Route::get('getInfo/{id}', [RewardsController::class, 'getInfo'])->name('getInfo');
    Route::put('update/{id}', [RewardsController::class, 'update'])->name('update');
    Route::put('delete/{id}', [RewardsController::class, 'delete'])->name('delete');
    Route::put('change-status/{id}', [RewardsController::class, 'changeStatus'])->name('change-status');
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

Route::group(['prefix' => 'bingo-patterns', 'as' => 'bingo-patterns.'], function () {
    Route::get('getInfo/{id}', [BingoController::class, 'getInfoBingoPlay'])->name('getInfo');
    Route::put('handler/{id?}/{action?}', [BingoController::class, 'handleBingoPlays'])->name('handler');
});

Route::group(['prefix' => 'bingo', 'as' => 'bingo.'], function () {
    Route::get('configuration/patterns/{userToken?}', [BingoController::class, 'bingoPatterns'])->name('bingoPatterns');
    Route::get('configuration/{userToken?}', [BingoController::class, 'configuration'])->name('configuration');

    Route::get('getInfo/{id}/{starTime?}', [BingoController::class, 'getInfo'])->name('getInfo');
    Route::get('getInfoByEncrypt/{id}/{creatorUser}/{coin}', [BingoController::class, 'getInfoByEncrypt'])->name('getInfoByEncrypt');
    Route::get('getInfoPublicRoom', [BingoController::class, 'getInfoPublicRoom'])->name('getInfoPublicRoom');
    Route::get('getInfoPublicRoomPendingPayments', [BingoController::class, 'getInfoPublicRoomPendingPayments'])->name('getInfoPublicRoomPendingPayments');
    Route::get('getRoundsConfig/{room}/{type}', [BingoController::class, 'getRoundsConfig'])->name('getRoundsConfig');
    Route::get('getDateTimeRooms/{creatorUser}/{player}', [BingoController::class, 'getDateTimeRooms'])->name('get-datetime-rooms');
    Route::get('getCtnPriceForCoin/{creation}/{creatorType}/{creatorID}/{room?}', [BingoController::class, 'getCtnPriceForCoin'])->name('getCtnPriceForCoin');

    Route::put('participate/public-room/{id?}/{getOut?}', [BingoController::class, 'participateInPublicRoom'])->name('participateInPublicRoom');
    Route::post('carton/notify/payment/win-ctn', [BingoController::class, 'notifyPaymentPrizeWinningCtn'])->name('notifyPaymentPrizeWinningCtn');

    Route::put('handle-bingo-rules/{id?}', [BingoController::class, 'handleBingoRules'])->name('handleBingoRules');

    // Reporte de ventas de salas
    Route::get('sales-report/{coin?}/{dateFilter?}', [BingoController::class, 'reportSalesRooms'])->name('reportSalesRooms');
    Route::get('sales-report-by-room/{room}/{coin}', [BingoController::class, 'reportSalesByRoom'])->name('reportSalesByRoom');
    Route::get('sales-report-by-room-winners/{room}/{coin}', [BingoController::class, 'reportSalesByRoomWinners'])->name('reportSalesByRoomWinners');
    Route::get('sales-report-by-room/{room}/player/{playerLink}', [SellerController::class, 'reportSalesByRoomPlayer'])->name('reportSalesByRoomPlayer');

    Route::get('winners/{type}/{dateFilter}/{coin?}', [BingoController::class, 'ctnsAwarded'])->name('ctnsAwarded');
    Route::get('cartons-sold/{coin?}/{dateFilter?}', [BingoController::class, 'ctnsSold'])->name('ctnsSold');

    // CRUD de BINGO
    Route::post('create', [BingoController::class, 'create'])->name('create');
    Route::put('update/{id}', [BingoController::class, 'update'])->name('update');
    Route::put('delete/{id}', [BingoController::class, 'delete'])->name('delete');
    Route::put('status/{id}', [BingoController::class, 'changeStatus'])->name('change-status');
    Route::put('replay/{id}', [BingoController::class, 'replay'])->name('replay');
    Route::put('publish/{id}', [BingoController::class, 'publish'])->name('publish');
    Route::put('refund/{id}', [BingoController::class, 'refund'])->name('refund');
    Route::put('copy/{id}', [BingoController::class, 'copy'])->name('copy');

    Route::post('test/regenerate-draw', [BingoController::class, 'regenerateDraw'])->name('test.regenerateDraw');

    Route::get('game-rooms/admin/{user}/{coin?}', [BingoController::class, 'gamesRooms'])
        ->middleware(['auth', 'admin'])->name('game-rooms-admin');

    Route::get('{roomType}/game-rooms/{coin?}', [BingoController::class, 'gamesRoomsCommerce'])
        ->middleware(['auth', 'commerce'])->name('game-rooms-commerce');

    Route::post('actWn', [BingoController::class, 'actWn'])->middleware(['auth', 'admin'])->name('actWn');
});

Route::get('bingo/view-ctn/{ctnId}', [BingoController::class, 'viewCtn'])->name('bingo.viewCtn');
// Salas de Juego BINGO 
Route::get('bingo/{roomType}/{creatorType}/{creatorID?}/{coin?}', [BingoController::class, 'allRooms'])->name('bingo.all-rooms');
// URL de Compra CUSTOMERS
//Route::get('{customer}/{user}/{coin?}', [BingoController::class, 'gamesRoomsCustomer'])->name('bingo.game-rooms-customer');
Route::get('bingoplay/player/{player}/{room?}', [BingoController::class, 'inGame'])->name('bingo.in-game');

Route::group(['prefix' => 'bingo-automatic-room', 'as' => 'bingo-automatic-room.'], function () {
    /// Rutas de Salas Automaticas
    Route::get('automatic-room/getInfo/{id}', [BingoController::class, 'getInfoAutomaticRoom'])->name('getInfo');
    Route::post('automatic-room/create', [BingoController::class, 'createAutomaticRoom'])->name('create');
    Route::put('automatic-room/update/{id}', [BingoController::class, 'updateAutomaticRoom'])->name('update');
    Route::put('automatic-room/change-status/{id}', [BingoController::class, 'changeStatusAutomaticRoom'])->name('change-status');
    Route::put('automatic-room/delete/{id}', [BingoController::class, 'deleteAutomaticRoom'])->name('delete');
    Route::put('automatic-room/copy/{id}', [BingoController::class, 'copyAutomaticRoom'])->name('copy');
    Route::get('automatic-room/executeGenerateBingoRoomsJob/{creatorType}/{creatorID}', [BingoController::class, 'executeGenerateBingoRoomsJob'])->name('executeGenerateBingoRoomsJob');
});

// BingoCash -Jugador-
Route::group(['prefix' => 'bingocash', 'as' => 'bingo.'], function () {

    // Rutas Jugador/Comercio TODOS
    Route::get('game-room/{uuidCode}/{room?}/{coin?}', [BingoController::class, 'outGame'])->name('out-game');

    // Procesar Compra de Cartones
    Route::post('carton/generate/store', [BingoController::class, 'generateCarton'])->name('generateCarton');
    Route::get('getDraw/{room}', [BingoController::class, 'getDraw'])->name('getDraw');
    Route::get('get/chips/{room}', [BingoController::class, 'getChips'])->name('getChips');
    Route::get('get/result/{room}/{value}/{column?}', [BingoController::class, 'getResult'])->name('getResult');
});

// Rutas Jugador/Cliente
Route::get('{customer}/{userToken}/room/{room}', [BingoController::class, 'inGameCustomer'])->name('bingo.in-game-customer');
Route::get('game/{customer}/room/{room}/{coin}', [BingoController::class, 'outGameCustomer'])->name('bingo.out-game-customer');

<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Cookie;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\ExchangeRateController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\CommerceController;
use App\Http\Controllers\SellerController;
use App\Http\Controllers\BingoOLDController;
use App\Http\Controllers\BingoController;
use App\Http\Controllers\PollaExpressController;
use App\Http\Controllers\BancosController;
use App\Http\Controllers\DoubleController;
use App\Http\Controllers\RuletaController;
use App\Http\Controllers\TriviaController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\LimitsController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\RetirosController;
use App\Http\Controllers\DirectoryController;
use App\Http\Controllers\RecargasController;
use App\Http\Controllers\ReferralController;
use App\Http\Controllers\HistorialController;
use App\Http\Controllers\PagoMovilController;
use App\Http\Controllers\PixController;
use App\Http\Controllers\InformationController;
use App\Http\Controllers\TrikiController;
use App\Http\Controllers\YankenpoController;
use App\Models\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

require __DIR__ . '/auth.php';

Route::get('/', function () {
    $register = Settings::getStatus('REGISTER');
    return view('auth.login', compact('register'));
});

Route::get('/info/{page}', function ($page) {
    $validPages = ['terms', 'about', 'privacy'];

    if (in_array($page, $validPages)) {
        return view("components.info.$page");
    }

    abort(404);
})->name('info-rke');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
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

Route::group(['middleware' => 'auth', 'prefix' => 'image', 'as' => 'image.'], function () {
    Route::get('payments/prizes/{filename}', [FileController::class, 'showImagePaymentPrizeCtns'])->name('show-image-payment-prize-ctns');
    Route::get('payments/{filename}', [FileController::class, 'showImagePaymentCtns'])->name('show-image-payment-ctns');
});

Route::group(['middleware' => ['auth', 'admin'], 'prefix' => 'admin', 'as' => 'admin.'], function () {
    Route::get('commerces', [AdminController::class, 'commerces'])->name('commerces');
    Route::get('sellers', [AdminController::class, 'sellers'])->name('sellers');
});

Route::group(['middleware' => ['auth'], 'prefix' => 'perfil', 'as' => 'perfil.'], function () {
    Route::get('', [ProfileController::class, 'index'])->name('index');
    Route::put('{id}', [ProfileController::class, 'update'])->name('update');
});

Route::group(['middleware' => ['auth'], 'prefix' => 'info', 'as' => 'info.'], function () {
    Route::get('terms', [InformationController::class, 'terms'])->name('terms');
    Route::get('about', [InformationController::class, 'about'])->name('about');
    Route::get('privacy', [InformationController::class, 'privacy'])->name('privacy');
    Route::get('help-center', [InformationController::class, 'helpCenter'])->name('help-center');
    Route::get('pds', [InformationController::class, 'pds'])->name('pds');
});

Route::group(['middleware' => ['auth', 'admin'], 'prefix' => 'limits', 'as' => 'limits.'], function () {
    Route::get('', [LimitsController::class, 'index'])->name('index');
    Route::post('dp', [LimitsController::class, 'store_dp'])->name('store_dp');
    Route::post('rt', [LimitsController::class, 'store_rt'])->name('store_rt');
    Route::post('transfer', [LimitsController::class, 'store_transfer'])->name('store_transfer');
});

// Rutas del Conn
Route::group(['middleware' => ['auth'], 'prefix' => 'payment', 'as' => 'payment.'], function () {
    Route::get('proof/{fileName}', [PaymentController::class, 'showPaymentProof'])->name('proof');
});

// Tasa de Cambio/ExhangeRate
Route::group(['middleware' => ['auth', 'commerce'], 'prefix' => 'exchange-rate', 'as' => 'exchange-rate.'], function () {
    Route::get('', [ExchangeRateController::class, 'index'])->name('index');
    Route::post('create', [ExchangeRateController::class, 'create'])->name('create');
    Route::put('update/{id}', [ExchangeRateController::class, 'update'])->name('update');
    Route::put('delete/{id}', [ExchangeRateController::class, 'delete'])->name('delete');
    Route::get('getInfo/{id}', [ExchangeRateController::class, 'getInfo'])->name('getInfo');
    Route::get('getLastValueByCoin/{coin}', [ExchangeRateController::class, 'getLastValueByCoin'])->name('getLastValueByCoin');
});

// Directorio-Datos Bancarios
Route::group(['middleware' => 'auth', 'prefix' => 'directory', 'as' => 'directory.'], function () {
    Route::get('{countryCode}', [DirectoryController::class, 'index'])->name('index');
});

//Cliente
Route::group(['middleware' => ['auth', 'customer'], 'prefix' => 'customer', 'as' => 'customer.'], function () {
    Route::get('getPaymentCustomerDetails/{idCustomer}/{coin}', [CustomerController::class, 'getPaymentCustomerDetails'])->name('getPaymentCommerceDetails');
    Route::get('exchange-rate', [CustomerController::class, 'exchangeRate'])->name('exchangeRate');
    Route::get('all-directories', [CustomerController::class, 'allDirectories'])->name('allDirectories');
    Route::get('sellers', [CustomerController::class, 'sellers'])->name('sellers');
    Route::get('seller/getCoinByCountry/{country}/{seller}', [CustomerController::class, 'getCoinByCountry'])->name('getCoinByCountry');
    Route::get('seller/getSellerInfoByCtn/{ctn}', [CustomerController::class, 'getSellerInfoByCtn'])->name('getSellerInfoByCtn');
    Route::get('bingocash/players/other-winners/{dateFilter?}', [CustomerController::class, 'getBingoWinnersByOtherCustomers'])->name('getBingoWinnersByOtherCustomers');
    Route::get('bingocash/players/my-winners/{dateFilter?}', [CustomerController::class, 'getBingoWinnersByCustomer'])->name('getBingoWinnersByCustomer');
    Route::get('bingocash/carton-sold/{coin}/{dateFilter?}', [CustomerController::class, 'ctnsSold'])->name('ctnsSold');
    Route::put('bingocash/carton-lot/payment', [CustomerController::class, 'ctnsLotPayment'])->name('ctnsLotPayment');
    Route::post('bingocash/carton/notify/payment/win-ctn', [CustomerController::class, 'notifyPaymentPrizeWinningCtn'])->name('notifyPaymentPrizeWinningCtn');
    Route::get('bingocash/sales-report/{coin}/{dateFilter?}', [CustomerController::class, 'reportSalesRooms'])->name('reportSalesRooms');
    Route::get('bingocash/sales-report/room/{room}/{coin}', [CustomerController::class, 'reportSalesByRoom'])->name('reportSalesByRoom');
});

//Comercio/Comercializadora/Banca
Route::group(['middleware' => ['auth', 'commerce'], 'prefix' => 'commerce', 'as' => 'commerce.'], function () {
    Route::get('getPaymentCommerceDetails/{commerce}/{coin}', [CommerceController::class, 'getPaymentCommerceDetails'])->name('getPaymentCommerceDetails');
    Route::get('exchange-rate', [CommerceController::class, 'exchangeRate'])->name('exchangeRate');
    Route::get('all-directories', [CommerceController::class, 'allDirectories'])->name('allDirectories');
    Route::get('sellers', [CommerceController::class, 'sellers'])->name('sellers');
    Route::get('seller/getCoinByCountry/{country}/{seller}', [CommerceController::class, 'getCoinByCountry'])->name('getCoinByCountry');
    Route::get('seller/getSellerInfoByCtn/{ctn}', [CommerceController::class, 'getSellerInfoByCtn'])->name('getSellerInfoByCtn');
    Route::get('bingocash/players/other-winners/{dateFilter?}', [CommerceController::class, 'getBingoWinnersByOtherCommerces'])->name('getBingoWinnersByOtherCommerces');
    Route::get('bingocash/players/my-winners/{dateFilter?}', [CommerceController::class, 'getBingoWinnersByCommerce'])->name('getBingoWinnersByCommerce');
    Route::get('bingocash/carton-sold/{coin}/{dateFilter?}', [CommerceController::class, 'ctnsSold'])->name('ctnsSold');
    Route::put('bingocash/carton-lot/payment', [CommerceController::class, 'ctnsLotPayment'])->name('ctnsLotPayment');
    Route::post('bingocash/carton/notify/payment/win-ctn', [CommerceController::class, 'notifyPaymentPrizeWinningCtn'])->name('notifyPaymentPrizeWinningCtn');
    Route::get('bingocash/sales-report/{coin}/{dateFilter?}', [CommerceController::class, 'reportSalesRooms'])->name('reportSalesRooms');
    Route::get('bingocash/sales-report/room/{room}/{coin}', [CommerceController::class, 'reportSalesByRoom'])->name('reportSalesByRoom');
});

// Commerce Options
Route::group(['middleware' => ['auth', 'admin'], 'prefix' => 'commerce', 'as' => 'commerce.'], function () {
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
    Route::get('bingocash/carton/generate', [SellerController::class, 'cartonGenerate'])->name('cartonGenerate');
    Route::post('bingocash/carton/generate/store', [SellerController::class, 'generateCarton'])->name('generateCarton');
    Route::get('bingocash/carton/get/price/{roomID}', [SellerController::class, 'getCtnPrice'])->name('getCtnPrice');
    Route::get('bingocash/get/exchange-rate/{country}', [SellerController::class, 'getExchangeRate'])->name('getExchangeRate');
    Route::post('bingocash/player/get/player', [SellerController::class, 'getPlayer'])->name('getPlayer');
    Route::get('getPaymentCommerceDetails/{coin}', [SellerController::class, 'getPaymentCommerceDetails'])->name('getPaymentCommerceDetails');
    Route::get('bingocash/carton/sold/{coin}/{dateFilter?}', [SellerController::class, 'ctnsSold'])->name('ctnsSold');
    Route::get('bingocash/player/cartons/{PJLink}{room}', [SellerController::class, 'showPlayerCartons'])->name('showPlayerCartons');
    Route::get('bingocash/carton/awarded/{coin}/{dateFilter?}', [SellerController::class, 'ctnsAwarded'])->name('ctnsAwarded');
    Route::get('bingocash/getWinnerInfoForCtn/{ctnId}', [SellerController::class, 'getWinnerInfoForCtn'])->name('getWinnerInfoForCtn');
    Route::get('bingocash/player/{PJLink}/info', [SellerController::class, 'getPlayerInfo'])->name('getPlayerInfo');
    Route::post('bingocash/carton/payment/status', [SellerController::class, 'ctnsLotPayment'])->name('ctnsLotPayment');
    Route::post('bingocash/carton/notify/payment/win-ctn', [SellerController::class, 'notifyPaymentPrizeWinningCtn'])->name('notifyPaymentPrizeWinningCtn');
    Route::get('bingocash/report/sales/rooms/{coin}/{dateFilter?}', [SellerController::class, 'reportSalesRooms'])->name('reportSalesRooms');
    Route::get('bingocash/report/sales/room/{room}/{coin}/{dateFilter?}', [SellerController::class, 'reportSalesByRoom'])->name('reportSalesByRoom');
    Route::get('bingocash/report/sales/room/{room}/player/{PJLink}', [SellerController::class, 'reportSalesByRoomPlayer'])->name('reportSalesByRoomPlayer');
});

Route::group(['middleware' => ['auth'], 'prefix' => 'report', 'as' => 'report.'], function () {
    Route::get('', [ReportsController::class, 'index'])->name('index');
    Route::post('store', [ReportsController::class, 'store'])->name('store');
});

Route::group(['middleware' => ['auth'], 'prefix' => 'banco', 'as' => 'banco.'], function () {
    Route::get('', [BancosController::class, 'index'])->name('index');
    Route::get('add', [BancosController::class, 'create'])->name('create');
    Route::get('edit/{id}', [BancosController::class, 'edit'])->name('edit');
    Route::post('store', [BancosController::class, 'store'])->name('store');
    Route::put('edit/{id}', [BancosController::class, 'update'])->name('update');
    Route::get('delete/{id}', [BancosController::class, 'delete'])->name('delete');
});

Route::group(['middleware' => ['auth'], 'prefix' => 'pagomovil', 'as' => 'pagomovil.'], function () {
    Route::get('', [PagoMovilController::class, 'index'])->name('index');
    Route::get('add', [PagoMovilController::class, 'create'])->name('create');
    Route::get('pagomovil/{id}', [PagoMovilController::class, 'edit'])->name('edit');
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

// Route::group(['middleware' => ['auth'], 'prefix' => 'wallet', 'as' => 'wallet.'], function () {
//     Route::get('', [CryptoWalletController::class, 'index'])->name('index');
//     Route::get('BinancePay', [CryptoWalletController::class, 'BPay'])->name('BPay');
//     Route::get('add', [CryptoWalletController::class, 'create'])->name('create');
//     Route::post('store', [CryptoWalletController::class, 'store'])->name('store');
//     Route::post('store/PayID', [CryptoWalletController::class, 'storeBPay'])->name('storeBPay');
// });

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

Route::group(['middleware' => ['auth', 'admin'], 'prefix' => 'recarga', 'as' => 'recarga.'], function () {
    Route::get('bs', [RecargasController::class, 'transk_bs'])->name('transk_bs');
    Route::get('accept_bs/{id}', [RecargasController::class, 'accept_bs'])->name('accept_bs');
    Route::get('decline_bs', [RecargasController::class, 'decline_bs'])->name('decline_bs');
    Route::get('freeze_bs/{id}', [RecargasController::class, 'freeze_bs'])->name('freeze_bs');
    Route::post('run_freeze/ves', [RecargasController::class, 'run_freeze'])->name('run_freeze');
    Route::get('liberate_bs/{id}', [RecargasController::class, 'liberate_bs'])->name('liberate_bs');
    Route::get('clp', [RecargasController::class, 'transk_clp'])->name('transk_clp');
    Route::get('BPay', [RecargasController::class, 'transk_BPay'])->name('transk_BPay');
    Route::get('usdt', [RecargasController::class, 'cryptos'])->name('cryptos');
    Route::get('accept_bpay/{id}', [RecargasController::class, 'accept_bpay'])->name('accept_bpay');
    Route::get('decline_bpay', [RecargasController::class, 'decline_bpay'])->name('decline_bpay');
    Route::get('freeze_bpay/{id}', [RecargasController::class, 'freeze_bpay'])->name('freeze_bpay');
    Route::post('run_freeze/bpay', [RecargasController::class, 'run_freeze_bpay'])->name('run_freeze_bpay');
    Route::get('liberate_bpay/{id}', [RecargasController::class, 'liberate_bpay'])->name('liberate_bpay');
});

Route::group(['middleware' => ['auth', 'admin'], 'prefix' => 'retiro', 'as' => 'retiro.'], function () {
    Route::get('ves', [RetirosController::class, 'transk_bs'])->name('transk_bs');
    Route::get('accept_bs/{id}', [RecargasController::class, 'accept_bs'])->name('accept_bs');
    Route::get('decline_bs/{id}', [RecargasController::class, 'decline_bs'])->name('decline_bs');
    Route::get('BPay', [RetirosController::class, 'transk_BPay'])->name('transk_BPay');
    Route::get('accept_bpay/{id}', [RecargasController::class, 'accept_bpay'])->name('accept_bpay');
    Route::get('decline_bpay/{id}', [RecargasController::class, 'decline_bpay'])->name('decline_bpay');
    Route::get('usdt', [RetirosController::class, 'cryptos'])->name('cryptos');
});

Route::group(['middleware' => ['auth'], 'prefix' => 'historial', 'as' => 'historial.'], function () {
    Route::get('', [HistorialController::class, 'index'])->name('index');
    Route::get('codigos', [HistorialController::class, 'codes'])->name('codes');
    Route::get('sorteos', [HistorialController::class, 'luck'])->name('luck');
});

Route::group(['middleware' => ['auth'], 'prefix' => 'trivia', 'as' => 'trivia.'], function () {
    Route::get('all', [TriviaController::class, 'allRooms'])->name('allRooms');
    Route::get('{coin}', [TriviaController::class, 'rooms'])->name('rooms');
    Route::get('time_serv', [TriviaController::class, 'time_serv'])->name('time_serv');
    Route::get('timer/{id}', [TriviaController::class, 'timer'])->name('timer');
    Route::get('{id}/status', [TriviaController::class, 'changeStatusRoom'])->name('status');
    Route::get('cod', [TriviaController::class, 'cod_tri'])->name('cod_tri');
    Route::get('time', [TriviaController::class, 'dateTrivi'])->name('date_tri');
    Route::get('demos', [TriviaController::class, 'demo'])->name('demo');
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
    Route::get('demo', [TriviaController::class, 'demo'])->name('demo');
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

Route::group(['middleware' => ['auth'], 'prefix' => 'bingo', 'as' => 'bingo.'], function () {
    // Route::get('getInfo', [BingoOLDController::class, 'updateInfo'])->name('getInfoRoom');
    // Route::post('myprofit', [BingoOLDController::class, 'myprofit'])->name('myprofit');
    // Route::get('winners', [BingoOLDController::class, 'winners'])->name('winners');
    // Route::post('store', [BingoOLDController::class, 'store'])->name('store');
    // Route::post('update', [BingoOLDController::class, 'update'])->name('update');
    // Route::post('replay', [BingoOLDController::class, 'replay'])->name('replay');
    // Route::post('publish', [BingoOLDController::class, 'publish'])->name('publish');
    // Route::post('delete', [BingoOLDController::class, 'delete'])->name('delete');
    // Route::post('refund_room', [BingoOLDController::class, 'refund_room'])->name('refund_room');
    // Route::get('time_serv', [BingoOLDController::class, 'time_serv'])->name('time_serv');
    // Route::get('timer/{id}', [BingoOLDController::class, 'timer'])->name('timer');
    // Route::get('status/{id}', [BingoOLDController::class, 'changeStatusRoom'])->name('status');
    // Route::post('players', [BingoOLDController::class, 'consult_players'])->name('consult_players');
    // Route::post('carton/store', [BingoOLDController::class, 'carton_store'])->name('carton_store');
    // Route::get('room/{id}', [BingoOLDController::class, 'game'])->name('game');
    // Route::get('viewCtn/{ctnId}', [BingoOLDController::class, 'view_ctn'])->name('viewCtn');
    // Route::post('check', [BingoOLDController::class, 'check_ctn'])->name('check_ctn');
    // Route::get('penalty/{id_rm}/{id_user}', [BingoOLDController::class, 'penalty'])->name('penalty');
    // Route::get('info/{id}', [BingoOLDController::class, 'info_win'])->name('info_win');
    // Route::get('get/chips/{id}', [BingoOLDController::class, 'get/chips'])->name('get/chips');
    // Route::post('end', [BingoOLDController::class, 'end_game'])->name('end_game');
});

Route::group(['middleware' => ['auth', 'customer-commerce'], 'prefix' => 'bingocash', 'as' => 'bingo.'], function () {
    Route::get('rooms/{user}/{coin?}', [BingoController::class, 'allRooms'])->name('all-rooms');
    Route::get('games-room/{user}', [BingoController::class, 'gamesRooms'])->name('game-rooms');
    Route::post('bingocash/carton/generate/store', [BingoController::class, 'generateCarton'])->name('generateCarton');
    Route::post('create', [BingoController::class, 'create'])->name('create');
    Route::get('getInfo/{id}', [BingoController::class, 'getInfo'])->name('getInfo');
    Route::put('update/{id}', [BingoController::class, 'update'])->name('update');
    Route::put('delete/{id}', [BingoController::class, 'delete'])->name('delete');
    Route::put('replay/{id}', [BingoController::class, 'replay'])->name('replay');
    Route::put('publish/{id}', [BingoController::class, 'publish'])->name('publish');
    Route::put('refund/{id}', [BingoController::class, 'refund'])->name('refund');
    Route::put('copy/{id}', [BingoController::class, 'copy'])->name('copy');
});

Route::post('bingocash/actWn', [BingoController::class, 'actWn'])
    ->name('bingocash.actWn')->middleware(['auth', 'admin']);

// BingoCash -Jugador-
Route::group(['prefix' => 'bingocash', 'as' => 'bingocash.'], function () {

    // Rutas Jugador/Comercio TODOS
    Route::get('game/{dateFilter?}', [BingoController::class, 'outGame'])->name('outGame');
    Route::get('{PlayerLink}', [BingoController::class, 'inGame'])->name('inGame');

    // Ruta /Jugador/Comercio Especifico
    Route::get('comm/{PlayerLink}', [BingoController::class, 'inGameCommerce'])->name('in-game-commerce');
    Route::get('comm/{commerce}/game/{dateFilter?}', [BingoController::class, 'outGameCommerce'])->name('out-game-commerce');

    // Rutas Jugador/Cliente
    Route::get('rke/{cust}/game/{dateFilter?}', [BingoController::class, 'outGameCustomer'])->name('out-game');
    Route::get('rke/{cust}/room/{room}/{userToken}', [BingoController::class, 'inGameCustomer'])->name('in-game-customer');

    Route::get('getSort/{room}', [BingoController::class, 'getSort'])->name('getSort');
    Route::get('get/chips/{room}', [BingoController::class, 'getChips'])->name('getChips');
    Route::get('get/result/{room}/{PJLink?}', [BingoController::class, 'getResult'])->name('getResult');
    Route::get('viewCtn/{ctnId}', [BingoController::class, 'viewCtn'])->name('viewCtn');
});
// BingoCash -Jugador-

// PollaExpress -Jugador-
Route::group(['prefix' => 'pollaexpress', 'as' => 'pollaexpress.'], function () {
    Route::get('getSort/{room}', [PollaExpressController::class, 'getSort'])->name('getSort');
    Route::get('{PlayerLink}', [PollaExpressController::class, 'game'])->name('game');
    Route::get('get/chips/{room}', [PollaExpressController::class, 'getChips'])->name('getChips');
    Route::get('getResult/{room}/{PJLink}', [PollaExpressController::class, 'getResult'])->name('getResult');
    Route::get('viewCtn/{ctnId}', [PollaExpressController::class, 'viewCtn'])->name('viewCtn');
});
// PollaExpress -Jugador-

Route::group(['middleware' => ['auth'], 'prefix' => 'ruleta', 'as' => 'ruleta.'], function () {
    Route::get('room', [RuletaController::class, 'index'])->name('index');
    Route::post('store', [RuletaController::class, 'num_store'])->name('store');
});

Route::group(['middleware' => ['auth'], 'prefix' => 'triki', 'as' => 'triki.'], function () {
    Route::get('', [TrikiController::class, 'rooms'])->name('rooms');
    Route::get('pdf', [TrikiController::class, 'pdf'])->name('pdf');
});

Route::group(['middleware' => ['auth'], 'prefix' => 'yankenpo', 'as' => 'yankenpo.'], function () {
    Route::get('', [YankenpoController::class, 'rooms'])->name('rooms');
    Route::post('debit', [YankenpoController::class, 'debit_publish_room'])->name('debit_publish_room');
    Route::get('room/{id}', [YankenpoController::class, 'game'])->name('game');
    Route::post('select', [YankenpoController::class, 'select_ppt'])->name('select_ppt');
    Route::get('check_ppt/{id}', [YankenpoController::class, 'check_ppt'])->name('check_ppt');
});

Route::group(['middleware' => ['auth'], 'prefix' => 'double', 'as' => 'double.'], function () {
    Route::get('', [DoubleController::class, 'index'])->name('index');
});

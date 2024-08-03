<?php

namespace App\Http\Controllers;

use App\Models\Banco_CHL;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Banks_VENS;
use App\Models\CommerceExchangeRate;
use App\Models\BPay;
use App\Models\PagoMovil;
use App\Models\CuentaBancaria;
use App\Models\Transactions_BPay;
use App\Models\Transactions_VES;
use App\Models\Transactions_CLP;
use App\Models\DP_Block;
use App\Models\RT_Block;
use App\Models\SaldoCongelado;
use App\Models\Transbank;
use App\Traits\UserVal;
use Illuminate\Http\Request;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class WalletController extends Controller
{
    use UserVal;
    // public function index()
    // {
    //     $user = auth()->user()->id;
    //     $wallet = Wallet::where("usuario_id", $user)
    //         ->leftjoin('monedas', 'monedas.id', 'Wallets.coin')->first();
    //     $pais = auth()->user()->country;
    //     $bancos = Banks_VENS::all();
    //     $CommerceExchangeRate = CommerceExchangeRate::orderBy('created_at', 'DESC')->first();
    //     $BPay = BPay::where("user", $user)->first();
    //     $PagoMovil = PagoMovil::where("usuario_id", $user)->first();
    //     $account_clp = CuentaBancaria::where('user', $user)->get();
    //     $transactions_BPay = Transactions_BPay::where("user", $user)
    //         ->leftjoin('transk_motives', 'transk_motives.id_mtv', 'transactions_bpay.motivo')->orderByDesc('created_at')->get();
    //     $transactions_ves = Transactions_VES::where("user", $user)
    //         ->leftjoin('transk_motives', 'transk_motives.id_mtv', 'transactions_ves.motivo')
    //         ->orderBy('status')->orderBy('created_at', 'DESC')->get();
    //     $transactions_clp = Transactions_CLP::join('transbanks', 'transbanks.id', 'transactions_clp.id_transk')
    //         ->leftjoin('payment_type_transbank', 'payment_type_transbank.sym', 'transactions_clp.paymentTypeCode')
    //         ->join('transk_status', 'transk_status.id', 'transbanks.status')
    //         ->orderByDesc('transbanks.id')->where('user', $user)->get();
    //     if (count($transactions_clp) > 0 && $transactions_clp[0]->status == 1) {
    //         $tm_unix = strtotime($transactions_clp[0]->created_at);
    //         if ($tm_unix + 250 < time()) {
    //             Transbank::where('buy_order', $transactions_clp[0]->buyOrder)
    //                 ->update(['status' => 4]);
    //         }
    //         $transactions_clp = Transactions_CLP::join('transbanks', 'transbanks.id', 'transactions_clp.id_transk')
    //             ->leftjoin('payment_type_transbank', 'payment_type_transbank.sym', 'transactions_clp.paymentTypeCode')
    //             ->join('transk_status', 'transk_status.id', 'transbanks.status')
    //             ->orderByDesc('transbanks.id')->where('user', $user)->get();
    //     }
    //     $Bs_cold = SaldoCongelado::where('user', $user)
    //         ->where('coin', 2)->where('status', 0)->sum('monto');
    //     $USDT_cold = SaldoCongelado::where('user', $user)
    //         ->where('coin', 1)->where('status', 0)->sum('monto');
    //     $exuser_type = DB::table('extras_user')->where('user', $user)->first();
    //     if ($exuser_type == null) {
    //         DB::table('extras_user')->insertGetId(['user' => $user]);
    //     }
    //     return view('modules/user/Wallet/index')->with([
    //         'wallet' => $wallet,
    //         'CommerceExchangeRate' => $CommerceExchangeRate,
    //         'pais' => $pais,
    //         'bancos' => $bancos,
    //         'BPay' => $BPay,
    //         'PagoMovil' => $PagoMovil,
    //         'Account_CLP' => $account_clp,
    //         'transactions_BPay' => $transactions_BPay,
    //         'transactions_ves' => $transactions_ves,
    //         'transactions_clp' => $transactions_clp,
    //         'Bs_cold' => $Bs_cold,
    //         'USDT_cold' => $USDT_cold
    //     ]);
    // }

    public function index(){

        $wallet = $this->wallet();

         return view('modules.all.wallet.index', compact(
            'wallet'
         ));
    }

    public function createBPay()
    {
        $user = auth()->user()->id;
        $wallet = Wallet::where("usuario_id", $user)
            ->leftjoin('monedas', 'monedas.id', 'Wallets.coin')->first();
        $userAdmin = User::where("user_type", 2)->get();
        $limits = DB::table('limits')->get();
        $comp_dp = DB::table('transactions_bpay')->where('tipo', 2)->where('user', $user)->latest()->first();
        if (empty($userAdmin)) {
            $BPay = BPay::where("user", $userAdmin[0]->id)->first();
        } else {
            $BPay = '';
        }

        return view('modules/user/Wallet/dp_bpay')->with([
            'BPay' => $BPay,
            'comp_dp' => $comp_dp,
            'limits' => $limits,
            'wallet' => $wallet,
        ]);
    }

    public function storeBPay(Request $request)
    {
        if ($request->hasFile('capture')) {
            $files = $request->file('capture');
            $nombreimg = $files->getClientOriginalName();
            $ruta = public_path('img/refers/bpay/' . $nombreimg);
            Image::make($request->file('capture'))
                ->resize(480, null, function ($constraint) {
                    $constraint->aspectRatio();
                })->save($ruta);
        } else {
            return redirect()->back();
        }
        $dpCrypto = new Transactions_BPay;
        $dpCrypto->user = auth()->user()->id;
        $dpCrypto->crypto_mon = 1;
        $dpCrypto->fecha = Carbon::now()->format('Y-m-d');
        $dpCrypto->tipo = 1;
        $dpCrypto->monto = $request->crypto_cant;
        $dpCrypto->id_orden = $request->id_orden;
        $dpCrypto->capture = $nombreimg;
        $dpCrypto->status = 1;

        $dpCrypto->save();
        return redirect()->route('Wallet.index');
    }

    public function createPM()
    {
        $user = auth()->user()->id;
        $wallet = Wallet::where("usuario_id", $user)
            ->leftjoin('monedas', 'monedas.id', 'Wallets.coin')->first();
        if ($wallet->coin != 2) {
            return redirect()->back();
        }
        $bancos = Banks_VENS::All();
        $limits = DB::table('limits')->get();
        $comp_dp = Transactions_VES::where('user', $user)->where('tipo', 1)->latest()->first();
        $userAdmin = User::where("user_type", 2)->get();
        if (empty($userAdmin)) {
            $pagoMovil = PagoMovil::where("usuario_id", $userAdmin[0]->id)->first();
        } else {
            $pagoMovil = '';
        }
        $CommerceExchangeRate = CommerceExchangeRate::orderBy('created_at', 'DESC')->first();

        return view('modules/user/Wallet/dp_bs_pm')->with([
            'pagoMovil' => $pagoMovil,
            'bancos' => $bancos,
            'comp_dp' => $comp_dp,
            'min_dp_bs' => $limits[0]->valor * $CommerceExchangeRate->CommerceExchangeRate_RKE,
            'max_dp_bs' => $limits[1]->valor * $CommerceExchangeRate->CommerceExchangeRate_RKE,
            'wallet' => $wallet,
            'CommerceExchangeRate' => $CommerceExchangeRate
        ]);
    }

    public function storePM(Request $request)
    {
        $bank_user = PagoMovil::where("usuario_id", auth()->user()->id)->first();
        $banco_admin = Banks_VENS::where('id', $request->id_banco)->first();
        $banco_emisor = Banks_VENS::where('id', $bank_user->banco_id)->first();
        $CommerceExchangeRate = CommerceExchangeRate::orderBy('created_at', 'DESC')->first();
        if ($request->hasFile('capture')) {
            $files = $request->file('capture');
            $nombreimg = $files->getClientOriginalName();
            $ruta = public_path('img/refers/ves/' . $nombreimg);
            Image::make($request->file('capture'))
                ->resize(480, null, function ($constraint) {
                    $constraint->aspectRatio();
                })->save($ruta);
        } else {
            return redirect()->back();
        }
        $recarga = new Transactions_VES;
        $recarga->fecha = Carbon::now()->format('Y-m-d');
        $recarga->user = auth()->user()->id;
        $recarga->banco_e = $banco_emisor->id;
        $recarga->monto = $request->monto;
        $recarga->tipo = 1;
        $recarga->CommerceExchangeRate = $CommerceExchangeRate->CommerceExchangeRate_RKE;
        $recarga->num_ref = $request->num_ref;
        $recarga->banco_r = $banco_admin->id;
        $recarga->capture = $nombreimg;
        $recarga->status = 1;
        $recarga->save();
        return redirect()->route('Wallet.index');
    }

    public function rt_BPay()
    {
        $user = auth()->user()->id;
        $wallet = Wallet::where("usuario_id", $user)
            ->leftjoin('monedas', 'monedas.id', 'Wallets.coin')->first();
        $limits = DB::table('limits')->get();
        $Bpay = DB::table('accounts_bpay')->where('user', $user)->first();
        if ($Bpay == null) {
            return redirect()->back();
        }
        $comp_rtB = Transactions_BPay::where('tipo', 2)->where('user', $user)->latest()->first();

        return view('modules/user/Wallet/rt_bpay')->with([
            'comp_rtB' => $comp_rtB,
            'Bpay' => $Bpay,
            'limits' => $limits,
            'wallet' => $wallet,
        ]);
    }

    public function run_rt_BPay(Request $request)
    {
        $user = auth()->user()->id;
        $walletADM = Wallet::where("usuario_id", 1)->first();
        $wallet = Wallet::where("usuario_id", $user)->first();
        $limits = DB::table('limits')->get();
        $monto_rt = $request->monto;
        $com_rt = number_format(($monto_rt * $limits[3]->valor) / 100, 2);
        $comp_rt = Transactions_BPay::where('user', $user)->latest()->first();

        if ($comp_rt == null || $comp_rt->status == 1) {
            if ($wallet->saldo >= $monto_rt) {
                if ($limits[3]->valor && $monto_rt <= $limits[6]->valor) {
                    $retiro = new Transactions_BPay;
                    $retiro->tipo = 2;
                    $retiro->crypto_mon = 1;
                    $retiro->monto = $monto_rt - $com_rt;
                    $retiro->fecha = Carbon::now()->format('Y-m-d');
                    $retiro->status = 1;
                    $retiro->user = $user;
                    DB::table('Wallets')->where("usuario_id", $user)
                        ->update(['usdt' => $wallet->usdt - $monto_rt]);
                    DB::table('Wallets')->where("usuario_id", 1)
                        ->update(['usdt' => $walletADM->usdt + $com_rt]);
                    $retiro->save();
                    return redirect()->route('Wallet.index');
                } else {
                    return redirect()->back();
                }
            } else {
                return redirect()->back();
            }
        } else {
            return redirect()->back();
        }
    }

    public function rt_PM()
    {
        $user = auth()->user()->id;
        $wallet = Wallet::where("usuario_id", $user)
            ->leftjoin('monedas', 'monedas.id', 'Wallets.coin')->first();
        if ($wallet->coin != 2) {
            return redirect()->back();
        }
        $limits = DB::table('limits')->get();
        $comp_rt = Transactions_VES::where('user', $user)->where('tipo', 2)->latest()->first();
        $CommerceExchangeRate = CommerceExchangeRate::orderBy('created_at', 'DESC')->first();
        $pagoMovil = PagoMovil::where("usuario_id", $user)->first();

        return view('modules/user/Wallet/rt_bs_pm')->with([
            'pagoMovil' => $pagoMovil,
            'wallet' => $wallet,
            'comp_rt' => $comp_rt,
            'limits' => $limits,
            'min_dp_bs' => $CommerceExchangeRate->CommerceExchangeRate_RKE * $limits[4]->valor,
            'CommerceExchangeRate' => $CommerceExchangeRate
        ]);
    }

    public function run_rt_PM(Request $request)
    {
        $user = auth()->user()->id;
        $walletADM = Wallet::where("usuario_id", 1)->first();
        $wallet = Wallet::where("usuario_id", $user)
            ->leftjoin('monedas', 'monedas.id', 'Wallets.coin')->first();
        $banco_user = Banks_VENS::where('id', $request->id_banco)->first();
        $comp_rt = Transactions_VES::where('user', $user)->where('tipo', 2)->orderByDesc('id')->first();
        $limits = DB::table('limits')->get();
        $monto_rt = $request->monto;
        $CommerceExchangeRate = CommerceExchangeRate::orderBy('created_at', 'DESC')->first();
        $com_rt = number_format(($monto_rt * ($limits[3]->valor / 100)), 2);
        $min_bs = $CommerceExchangeRate->CommerceExchangeRate_RKE * $limits[4]->valor;
        $max_bs = $CommerceExchangeRate->CommerceExchangeRate_RKE * $limits[5]->valor;
        if ($comp_rt == null || $comp_rt->status == 1) {
            if ($wallet->saldo >= $monto_rt) {
                if ($monto_rt <= $max_bs && $monto_rt >= $min_bs && $monto_rt <= $max_bs) {
                    $retiro = new Transactions_VES;
                    $retiro->fecha = Carbon::now()->format('Y-m-d');
                    $retiro->user = $user;
                    $retiro->monto = $monto_rt - $com_rt;
                    $retiro->tipo = 2;
                    $retiro->CommerceExchangeRate = $CommerceExchangeRate->CommerceExchangeRate_RKE;
                    $retiro->banco_r = $banco_user->id;
                    $retiro->status = 1;
                    $retiro->motivo = 2;
                    DB::table('Wallets')->where("usuario_id", $user)
                        ->update(['saldo' => $wallet->saldo - $monto_rt]);
                    DB::table('Wallets')->where("usuario_id", 1)
                        ->update(['usdt' => $walletADM->saldo + $com_rt]);
                    $retiro->save();
                    return redirect()->route('Wallet.index');
                }
            } else {
                return redirect()->back();
            }
        } else {
            return redirect()->back();
        }
    }

    public function rt_CLP()
    {
        $user = auth()->user()->id;
        $wallet = Wallet::where("usuario_id", $user)
            ->leftjoin('monedas', 'monedas.id', 'Wallets.coin')->first();
        $limits = DB::table('limits')->get();
        $bpay = DB::table('accounts_bpay')->where('user', $user)->first();
        $comp_rtB = Transactions_CLP::where('tipo', 2)->where('user', $user)->latest()->first();

        return view('modules/user/Wallet/rt_clp')->with([
            'comp_rtB' => $comp_rtB,
            'bpay' => $bpay,
            'limits' => $limits,
            'wallet' => $wallet,
        ]);
    }

    public function run_rt_CLP(Request $request)
    {
        $user = auth()->user()->id;
        $walletADM = Wallet::where("usuario_id", 1)->first();
        $wallet = Wallet::where("usuario_id", $user)
            ->leftjoin('monedas', 'monedas.id', 'Wallets.coin')->first();
        $banco_user = Banco_CHL::where('id', $request->id_banco)->first();
        $comp_rt = Transactions_CLP::where('user', $user)->where('tipo', 2)->orderByDesc('id')->first();
        $limits = DB::table('limits')->get();
        $monto_rt = $request->monto;
        $CommerceExchangeRate = CommerceExchangeRate::orderBy('created_at', 'DESC')->first();
        $com_rt = number_format(($monto_rt * ($limits[3]->valor / 100)), 2);
        $min_bs = $CommerceExchangeRate->CommerceExchangeRate_RKE * $limits[4]->valor;
        $max_bs = $CommerceExchangeRate->CommerceExchangeRate_RKE * $limits[5]->valor;
        if ($comp_rt == null || $comp_rt->status == 1) {
            if ($wallet->saldo >= $monto_rt) {
                if ($monto_rt <= $max_bs && $monto_rt >= $min_bs && $monto_rt <= $max_bs) {
                    $retiro = new Transactions_CLP;
                    $retiro->fecha = Carbon::now()->format('Y-m-d');
                    $retiro->user = $user;
                    $retiro->monto = $monto_rt - $com_rt;
                    $retiro->tipo = 2;
                    $retiro->CommerceExchangeRate = $CommerceExchangeRate->CommerceExchangeRate_RKE;
                    $retiro->banco_r = $banco_user->id;
                    $retiro->status = 1;
                    $retiro->motivo = 2;
                    DB::table('Wallets')->where("usuario_id", $user)
                        ->update(['saldo' => $wallet->saldo - $monto_rt]);
                    DB::table('Wallets')->where("usuario_id", 1)
                        ->update(['usdt' => $walletADM->saldo + $com_rt]);
                    $retiro->save();
                    return redirect()->route('Wallet.index');
                }
            } else {
                return redirect()->back();
            }
        } else {
            return redirect()->back();
        }
    }

    public function createBlock()
    {
        return redirect()->back();
        $user = auth()->user()->id;
        $wallet = Wallet::where("usuario_id", $user)
            ->leftjoin('monedas', 'monedas.id', 'Wallets.coin')->first();
        $userAdmin = User::where("user_type", 2)->get();
        $limits = DB::table('limits')->get();
        $comp_dp = DB::table('dp_cryptos')->where('usuario_id', $user)->latest()->first();
        $comp_dpB = DB::table('transactions_bpay')->where('tipo', 1)->where('user', $user)->latest()->first();
        $list_wallet = Wallet::where("usuario_id", $userAdmin[0]->id)->get();

        return view('modules/user/Wallet/dp_crypto')->with([
            'list_wallet' => $list_wallet,
            'comp_dp' => $comp_dp,
            'comp_dpB' => $comp_dpB,
            'limits' => $limits,
            'wallet' => $wallet,
        ]);
    }

    public function storeBlock(Request $request)
    {
        $dpCrypto = new DP_Block;
        $dpCrypto->crypto_mon = 'Tether USDT';
        $dpCrypto->red = 'TRC20';
        $dpCrypto->fecha = $request->fecha_recarga;
        $dpCrypto->cantidad = $request->crypto_cant;
        $dpCrypto->txid = $request->crypto_txid;
        $dpCrypto->estatus = 0;
        $dpCrypto->usuario_id = auth()->user()->id;
        $dpCrypto->save();
        return redirect()->route('Wallet.index');
    }

    public function retiroBlock()
    {
        return redirect()->back();
        $user = auth()->user()->id;
        $wallet = Wallet::where("usuario_id", $user)
            ->leftjoin('monedas', 'monedas.id', 'Wallets.coin')->first();
        $limits = DB::table('limits')->get();
        $comp_rt = DB::table('rt_cryptos')->where('usuario_id', $user)->latest()->first();
        $list_wallet = Wallet::where("usuario_id", $user)->get();

        return view('modules/user/Wallet/rt_crypto')->with([
            'list_wallet' => $list_wallet,
            'comp_rt' => $comp_rt,
            'limits' => $limits,
            'wallet' => $wallet,
        ]);
    }

    public function s_retiroBlock(Request $request)
    {
        $user = auth()->user()->id;
        $wallet = Wallet::where("usuario_id", $user)
            ->leftjoin('monedas', 'monedas.id', 'Wallets.coin')->first();
        $limits = DB::table('limits')->get();
        $com_rt = number_format(($request->monto * $limits->com_rt) / 100, 2);
        $comp_rt = DB::table('rt_cryptos')->where('usuario_id', $user)->latest()->first();

        if ($comp_rt->estatus == 1) {
            if ($wallet->usdt >= $request->monto && $request->monto >= $limits->com_rt && $request->monto <= $limits->max_rt_usdt) {
                $retiro = new RT_Block;
                $retiro->cantidad = $request->monto - $com_rt;
                $retiro->fecha = Carbon::now()->format('Y-m-d');
                $retiro->estatus = 0;
                $retiro->usuario_id = $user;
                $retiro->save();
                DB::table('Wallets')->where("usuario_id", $user)
                    ->update(['usdt' => $wallet->usdt - $request->monto]);
                return redirect()->route('Wallet.index');
            } else {
                return redirect()->back();
            }
        } else {
            return redirect()->back();
        }
    }

    public function transfer_coins()
    {
        $user = auth()->user()->id;
        $wallet = Wallet::where("usuario_id", $user)
            ->leftjoin('monedas', 'monedas.id', 'Wallets.coin')->first();
        if ($wallet->coin == 1) {
            return redirect()->back();
        }
        $CommerceExchangeRate = CommerceExchangeRate::orderBy('created_at', 'DESC')->first();
        $limits = DB::table('limits')->where('id', 7)->first();
        $mnt_min_bs = $CommerceExchangeRate->CommerceExchangeRate_RKE * 1.5;
        $ts_cm_bs = $mnt_min_bs + (($mnt_min_bs * $limits->valor) / 100);
        return view('modules/user/Wallet/transfer_coins')->with([
            'wallet' => $wallet,
            'ts_cm_bs' => $ts_cm_bs
        ]);
    }

    public function transfer_usdt()
    {
        $user = auth()->user()->id;
        $wallet = Wallet::where("usuario_id", $user)
            ->leftjoin('monedas', 'monedas.id', 'Wallets.coin')->first();
        return view('modules/user/Wallet/transfer_usdt')->with([
            'wallet' => $wallet,
        ]);
    }

    public function transferUser(Request $request)
    {
        $user = auth()->user()->id;
        $wallet = Wallet::where("usuario_id", $user)
            ->leftjoin('monedas', 'monedas.id', 'Wallets.coin')->first();
        $users = User::pluck('username')->toArray();
        $CommerceExchangeRate = CommerceExchangeRate::orderBy('created_at', 'DESC')->first();
        $comision = ($request->monto * 1.6) / 100;
        if (auth()->user()->username === $request->username) {
            return redirect()->back()->with($message = array('message' => 'Operación Inválida', 'repetido' => 1));
        } else {
            if (in_array($request->username, $users)) {
                if ($request->moneda == 1) {
                    if (($wallet->saldo - ($request->monto + $comision)) > (2 * $CommerceExchangeRate->CommerceExchangeRate_RKE)) {
                        if ($wallet->saldo >= ($request->monto + $comision) && $request->monto >= (1.5 * $CommerceExchangeRate->CommerceExchangeRate_RKE)) {
                            $emisor = User::where('username', auth()->user()->username)->first();
                            $receptor = User::where('username', $request->username)->first();
                            $wallet_receptor = Wallet::where("usuario_id", $receptor->id)->first();
                            $walletADM = Wallet::where("usuario_id", 1)->first();
                            Wallet::where("usuario_id", $emisor->id)->update([
                                'saldo' => $wallet->saldo = $wallet->saldo - $request->monto - $comision
                            ]);
                            Wallet::where("usuario_id", $receptor->id)->update([
                                'saldo' => $wallet_receptor->saldo + $request->monto
                            ]);
                            Wallet::where("usuario_id", $receptor->id)->update([
                                'saldo' => $walletADM->saldo + $comision
                            ]);
                            return redirect()->back()->with($message = array('message' => 'Operación exitosa', 'exito' => 1));
                        } else {
                            return redirect()->back()->with($message = array('message' => 'El monto debe ser mayor o igual a ' .  1.5 * ($CommerceExchangeRate->CommerceExchangeRate_RKE) . 'Bs', 'insuf' => 1));
                        }
                    } else {
                        return redirect()->back()->with($message = array('message' => 'Debe tener un saldo mayor a ' . 2 * ($CommerceExchangeRate->CommerceExchangeRate_RKE) . 'Bs', 'exce' => 1));
                    }
                } else if ($request->moneda == 2) {
                    if (($wallet->usdt - ($request->monto + $comision)) > 2) {
                        if ($wallet->usdt >= ($request->monto + $comision) && $request->monto >= 1.5) {
                            $emisor = User::where('username', auth()->user()->username)->first();
                            $receptor = User::where('username', $request->username)->first();
                            $wallet_receptor = Wallet::where("usuario_id", $receptor->id)->first();
                            Wallet::where("usuario_id", $emisor->id)->update([
                                'usdt' => $wallet->usdt = $wallet->usdt - $request->monto - $comision
                            ]);
                            Wallet::where("usuario_id", $receptor->id)->update([
                                'usdt' => $wallet_receptor->usdt = $wallet_receptor->usdt + $request->monto
                            ]);
                            return redirect()->back()->with($message = array('message' => 'Operación exitosa', 'exito' => 1));
                        } else {
                            return redirect()->back()->with($message = array('message' => 'El monto debe ser mayor o igual  1.5$', 'insuf' => 1));
                        }
                    } else {
                        return redirect()->back()->with($message = array('message' => 'Debe tener un saldo mayor a 2$', 'exce' => 1));
                    }
                }
            } else {
                return redirect()->back()->with($message = array('message' => 'Usuario Inexistente', 'inex' => 1));
            }
        }
    }

    public function debit_triv_indv(Request $request)
    {
        $user = auth()->user()->id;
        $wallet = Wallet::where("usuario_id", $user)
            ->leftjoin('monedas', 'monedas.id', 'Wallets.coin')->first();
        $comp_quest = DB::table('triv_indv_user')->where('id_user', $user)
            ->where('lose_win', null)->orderBy('nm_quest', 'desc')->first();
        if ($comp_quest == null || $comp_quest->lose_win == null) {
            if ($wallet->saldo >= $request->aps_triv) {
                DB::table("Wallets")->where('usuario_id', $user)
                    ->update(['saldo' =>  $wallet->saldo - $request->aps_triv]);
                $quser_type = DB::table('triv_indv_user')->where('id_user', $user)
                    ->where('lose_win', '!=', null)->pluck('id_quest')->toArray();
                $triv_indv = DB::table('triv_indv_quest')->pluck('id')->toArray();

                $j = 0;
                $arr_quest = [];
                while ($j < 8) {
                    $rnd = $triv_indv[array_rand($triv_indv)];
                    if (in_array($rnd, $quser_type)) {
                        continue;
                    } else {
                        if (in_array($rnd, $arr_quest)) {
                            continue;
                        } else {
                            array_push($arr_quest, $rnd);
                            $j++;
                        }
                    }
                }
                DB::table('triv_indv_cdin')->insertGetId([
                    'start' => 0, 'bet' => $request->aps_triv,
                    'c_half' => 2, 'id_user' => $user, 'coin' => $request->coin
                ]);
                $triv_lt = DB::table('triv_indv_cdin')->where('id_user', $user)
                    ->orderBy('id', 'desc')->first();
                $m_c = 0.25;
                foreach ($arr_quest as $key => $value) {
                    DB::table('triv_indv_user')->insertGetId([
                        'multi' => $m_c,
                        'id_user' => $user,
                        'id_triv' => $triv_lt->id,
                        'id_quest' => $value,
                        'nm_quest' => $key + 1
                    ]);
                    $m_c += 0.30;
                }
                return redirect()->route('triv_in.show');
            } else {
                return redirect()->back()->with($message = array('message' => 'Saldo Insuficiente'));
            }
        } else {
            return redirect()->back();
        }
    }

    public function debit_triv_pvp(Request $request)
    {
        $user = auth()->user()->id;
        $wallet = Wallet::where("usuario_id", $user)
            ->leftjoin('monedas', 'monedas.id', 'Wallets.coin')->first();
        $bets_user = DB::table('triv_pvp_bets')->whereIn('status', [2, 3])->get();
        if (count($bets_user) == 0) {
            $all_bets = DB::table('triv_pvp_bets')->where('coin', $wallet->id)
                ->where('value', $request->aps_triv)->where('status', 2)->get();
            if ($wallet->coin == 1) {
                $wallety = $wallet->usdt;
            } else {
                $wallety = $wallet->saldo;
            }
            if ($wallety >= $request->aps_triv) {
                if (count($all_bets) > 0) {
                    foreach ($all_bets as $key => $value) {
                        # code...
                    }
                    DB::table('triv_pvp_cdin')->insertGetId([
                        'start' => 0, 'bet' => $request->aps_triv,
                        'c_half' => 2, 'id_user' => $user, 'coin' => $request->coin
                    ]);
                    DB::table('triv_pvp_user')->insertGetId([
                        'multi' => $m_c,
                        'id_user' => $user,
                        'id_triv' => $triv_lt->id,
                        'id_quest' => $value,
                        'nm_quest' => $key + 1
                    ]);
                    return redirect()->route('triv_pvp.show');
                } else {
                    DB::table('triv_pvp_bets')
                        ->insertGetId([
                            'user' => $user, 'coin' => $wallet->id,
                            'value' => $request->aps_triv, 'jackpot' => $request->aps_triv
                        ]);
                }
            } else {
                return redirect()->back()->with($message = array('message' => 'Saldo Insuficiente'));
            }
            if ($wallet->coin == 1) {
                DB::table("Wallets")->where('usuario_id', $user)
                    ->update(['usdt' =>  $wallet->usdt - $request->aps_triv]);
            } else {
                DB::table("Wallets")->where('usuario_id', $user)
                    ->update(['saldo' =>  $wallet->saldo - $request->aps_triv]);
            }
        }
        $quser_type = DB::table('triv_indv_user')->where('id_user', $user)
            ->where('lose_win', '!=', null)->pluck('id_quest')->toArray();
        $triv_indv = DB::table('triv_indv_quest')->pluck('id')->toArray();
        $j = 0;
        $arr_quest = [];
        while ($j < 8) {
            $rnd = $triv_indv[array_rand($triv_indv)];
            if (in_array($rnd, $quser_type)) {
                continue;
            } else {
                if (in_array($rnd, $arr_quest)) {
                    continue;
                } else {
                    array_push($arr_quest, $rnd);
                    $j++;
                }
            }
        }
    }

    public function debit_triv_multi($id, $precio)
    {
        $user = auth()->user()->id;
        $triv_user = DB::table("triv_multi_user")->where('user', $user)->where('id_gm', $id)->first();
        if ($triv_user != null) {
            return redirect()->back();
        } else {
            $wallet = Wallet::where("usuario_id", $user)
                ->leftjoin('monedas', 'monedas.id', 'Wallets.coin')->first();
            $trivia = DB::table("triv_multi")->where('id', $id)->first();
            if ($trivia->coin == 2) {
                $CommerceExchangeRate = CommerceExchangeRate::orderBy('created_at', 'DESC')->first();
                $v_bs = number_format($precio * $CommerceExchangeRate->CommerceExchangeRate_RKE, 2);
                $v_com_tv = round(($trivia->com * $v_bs) / 100, 3);
                $rest = $v_bs - $v_com_tv;
            } else {
                $v_com_tv = round(($trivia->com * $precio) / 100, 3);
                $rest = $precio - $v_com_tv;
            }
            $walletADM = Wallet::where("usuario_id", 1)->first();
            if ($trivia->coin == 1) {
                DB::table("Wallets")->where('usuario_id', $user)->update(['usdt' =>  $wallet->usdt - $rest]);
                DB::table("Wallets")->where('usuario_id', 1)->update(['usdt' =>  $walletADM->usdt - $rest]);
            } else {
                DB::table("Wallets")->where('usuario_id', $user)->update(['saldo' =>  $wallet->saldo - $rest]);
                DB::table("Wallets")->where('usuario_id', 1)->update(['saldo' =>  $walletADM->saldo - $rest]);
            }
            $wn_ghosts = DB::table('wn_ghosts')->where('game', 2)->where('id_game', $id)->first();
            if ($wn_ghosts == null) {
                DB::table('wn_ghosts')->insert([
                    'game' => 2, 'id_game' => $id, 'players' => 1, 'winners' => 1
                ]);
            }
            DB::table("triv_multi_user")->insert(['user' => $user, 'room' => $id]);
            DB::table('triv_multi')->where('id', $id)->update([
                'players' => $trivia->players + 1, 'jackpot' => $trivia->jackpot + $rest
            ]);
            $hist_gm = DB::table('hist_games')->where('game', 2)
                ->where('id_game', $id)->where('user', $user)->first();
            if ($hist_gm == null) {
                DB::table('hist_games')->insertGetId([
                    'date' => $trivia->fecha, 'time' => $trivia->hora, 'user' => $user,
                    'game' => 2, 'id_game' => $id, 'coin' => $trivia->coin
                ]);
            }
            return redirect()->back()->with($message = array('message' => 'Su pago fué procesado', 'buy' => 1));
        }
    }
}

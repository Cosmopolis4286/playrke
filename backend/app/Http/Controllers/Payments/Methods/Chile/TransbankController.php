<?php

namespace App\Http\Controllers\Payments\Methods\Chile;

use Illuminate\Http\Request;
use App\Models\Transbank;
use App\Models\Transactions_CLP;
use Illuminate\Support\Facades\DB;
use Transbank\Webpay\WebpayPlus;
use Transbank\Webpay\WebpayPlus\Transaction;

class TransbankController extends Controller
{
    public function __construct()
    {
        if (app()->environment('production')) {
            WebpayPlus::configureForIntegration(
                '597055555532',
                '579B532A7440BB0C9079DED94D31EA1615BACEB56610332264630D42D0A36B1C'
            );
        } else {
            WebpayPlus::configureForTesting();
        }
    }

    public function init_pay(Request $request)
    {
        $last_transk = Transbank::orderByDesc('id')->first();
        if ($last_transk == null || $last_transk->status > 1) {

            if ($last_transk == null) {
                $last_transk = 1;
            } else {
                $last_transk = $last_transk->id + 1;
            }
            $user = $request->id_user;
            $new_pay = new Transbank();
            $new_pay->buy_order = 'R' . date('my') . 'K' . $last_transk
                . $user . 'E' . date('Hi');
            $new_pay->session_id = $user;
            $new_pay->amount = $request->amount;
            $new_pay->save();
            $last_transk = Transbank::where('session_id', $user)->orderByDesc('id')->first();
            Transactions_CLP::insert([
                'user' => $user,
                'type_transk' => 1,
                'id_transk' => $last_transk->id,
                'buyOrder' => $last_transk->buy_order,
                'transk_date' => date('d/m/y'),
                'transk_hour' => date('H:i')
            ]);
            $url_to_pay = self::start_webpay_plus($new_pay);
            echo json_encode(array('url' => $url_to_pay));
        }
    }

    public function start_webpay_plus($new_pay)
    {
        $transaction = (new Transaction)->create(
            $new_pay->buy_order, //buy_order
            $new_pay->session_id, //session_id
            $new_pay->amount, //amount
            route('transbank.confirm_pay') //return_url
        );
        $url = $transaction->getUrl() . '?token_ws=' . $transaction->getToken();
        return $url;
    }

    public function confirm_pay()
    {
        if (isset($_GET['token_ws'])) {
            $response = (new Transaction)->commit($_GET['token_ws']);
            Transbank::where('buy_order', $response->buyOrder)->update(['token' => $_GET['token_ws']]);
            $buy = Transbank::where('buy_order', $response->buyOrder)->first();
            $transk_datetime_unix = date("U", strtotime($response->transactionDate));
            $transk_date = date('d/m/y', $transk_datetime_unix);
            $transk_hour = date('H:i', $transk_datetime_unix - 1);
            if ($response->isApproved()) {
                $buy->status = 2; //Aprobada
                $buy->update();
                $wallet = DB::table('Wallets')->where("usuario_id", $buy->session_id)->first();
                DB::table('Wallets')->where("usuario_id", $wallet->usuario_id)
                    ->update(['saldo' => $wallet->balance + $response->amount]);
                Transactions_CLP::where('buyOrder', $response->buyOrder)
                    ->where('user', $response->sessionId)->update([
                        'vci' => $response->vci, 'accounting_date' => $response->accountingDate,
                        'transactionDate' => $response->transactionDate,
                        'transk_date' => $transk_date, 'transk_hour' => $transk_hour,
                        'paymentTypeCode' => $response->amountmentTypeCode,
                        'authorizationCode' => $response->authorizationCode,
                        'installmentsNumber' => $response->installmentsNumber,
                        'installmentsAmount' => $response->installmentsAmount,
                        'cardNumber' => $response->cardNumber
                    ]);
                return redirect()->route('Wallet.index');
            } else {
                $buy->status = 4; //Fallida
                $buy->update();
                Transactions_CLP::where('buyOrder', $response->buyOrder)
                    ->where('user', $response->sessionId)->update([
                        'vci' => $response->vci, 'accounting_date' => $response->accountingDate,
                        'transactionDate' => $response->transactionDate,
                        'transk_date' => $transk_date, 'transk_hour' => $transk_hour,
                        'paymentTypeCode' => $response->amountmentTypeCode,
                        'authorizationCode' => $response->authorizationCode,
                        'installmentsNumber' => $response->installmentsNumber,
                        'installmentsAmount' => $response->installmentsAmount,
                        'cardNumber' => $response->cardNumber
                    ]);
                return redirect()->route('Wallet.index');
            }
        } else if (isset($_GET['TBK_TOKEN'])) {
            Transbank::where('buy_order', $_GET['TBK_ORDEN_COMPRA'])
                ->update(['status' => 6, 'token' => $_GET['TBK_TOKEN']]);
            return redirect()->route('Wallet.index');
        } else {
            if (isset($_GET['TBK_ID_SESION'])) {
                Transbank::where('buy_order', $_GET['TBK_ORDEN_COMPRA'])
                    ->update(['status' => 6]);
                return redirect()->route('Wallet.index');
            } else {
                return redirect()->route('Wallet.index')->with($message = 'Ocurrió un error de conexión');
            }
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\ReportUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use RealRashid\SweetAlert\Facades\Alert;

class ReportsController extends Controller
{
    public function store(Request $request)
    {
        $report = DB::table('report_problem')->pluck('usuario_id');
        foreach ($report as $key => $value) {
            if (auth()->user()->id != $value) {
                $reportUser = new ReportUser;
                $reportUser->problem = $request->problem;
                $reportUser->usuario_id = auth()->user()->id;
                $reportUser->save();
                Alert::success('', 'Su reporte se ha enviado con exito');
                return redirect()->back();
            } else {
                Alert::warning('', 'Ya tienes un reporte en revisión');
                return redirect()->back();
            }
        }
    }
}

<?php

namespace App\Http\Controllers\Games\Trivia;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Games\Trivia\Room\TriviaRoomController;
use App\Http\Controllers\Games\Trivia\Report\TriviaReportController;
use App\Http\Controllers\Games\Trivia\Game\TriviaGameController;
use App\Http\Controllers\Games\Trivia\Question\TriviaQuestionController;
use Illuminate\Http\Request;

/**
 * Controlador Legacy de Trivia.
 * 
 * Este controlador mantiene los métodos antiguos para compatibilidad
 * y redirige a los nuevos controladores separados.
 * 
 * @deprecated Usar los controladores específicos por área:
 * - TriviaRoomController para administración de salas
 * - TriviaReportController para reportes y estadísticas  
 * - TriviaGameController para lógica del juego
 * - TriviaQuestionController para gestión de preguntas
 */
class TriviaLegacyController extends Controller
{
    private TriviaRoomController $roomController;
    private TriviaReportController $reportController;
    private TriviaGameController $gameController;
    private TriviaQuestionController $questionController;

    public function __construct(
        TriviaRoomController $roomController,
        TriviaReportController $reportController,
        TriviaGameController $gameController,
        TriviaQuestionController $questionController
    ) {
        $this->roomController = $roomController;
        $this->reportController = $reportController;
        $this->gameController = $gameController;
        $this->questionController = $questionController;
    }

    // ====================================
    // MÉTODOS DE REPORTES (DELEGADOS)
    // ====================================

    public function index()
    {
        return $this->reportController->index();
    }

    public function winners(Request $request)
    {
        return $this->reportController->winners($request);
    }

    public function winnersRoom($coin)
    {
        return $this->reportController->winnersRoom($coin);
    }

    public function cardsSold(Request $request)
    {
        return $this->reportController->cardsSold($request);
    }

    public function salesReport(Request $request)
    {
        return $this->reportController->salesReport($request);
    }

    public function salesReportRoom($coin, Request $request)
    {
        return $this->reportController->salesReportRoom($coin, $request);
    }

    // ====================================
    // MÉTODOS DE SALAS (DELEGADOS)
    // ====================================

    public function roomsIndex()
    {
        return $this->roomController->index();
    }

    public function roomsCreate()
    {
        return $this->roomController->create();
    }

    public function roomsStore(Request $request)
    {
        return $this->roomController->store($request);
    }

    public function roomsEdit($id)
    {
        return $this->roomController->edit($id);
    }

    public function roomsUpdate(Request $request, $id)
    {
        return $this->roomController->update($request, $id);
    }

    public function roomsDestroy($id)
    {
        return $this->roomController->destroy($id);
    }

    public function roomsShow($id)
    {
        return $this->roomController->show($id);
    }

    // ====================================
    // MÉTODOS DE JUEGO (DELEGADOS)
    // ====================================

    public function rooms($coinSym)
    {
        return $this->gameController->rooms($coinSym);
    }

    public function room($id)
    {
        return $this->gameController->room($id);
    }

    public function rmMulti($id)
    {
        return $this->gameController->inGame($id);
    }

    public function timer($id)
    {
        return $this->gameController->timer($id);
    }

    public function individual($id)
    {
        return $this->gameController->individual($id);
    }

    public function checkIndividual(Request $request)
    {
        return $this->gameController->checkIndividual($request);
    }

    // ====================================
    // MÉTODOS LEGADOS ADICIONALES
    // ====================================

    /**
     * Mantener compatibilidad con método allRooms existente
     */
    public function allRooms()
    {
        return $this->reportController->index();
    }

    /**
     * Mantener compatibilidad con método store existente
     */
    public function store(Request $request)
    {
        return $this->roomController->store($request);
    }
}

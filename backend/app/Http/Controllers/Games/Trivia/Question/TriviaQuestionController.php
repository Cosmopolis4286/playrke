<?php

namespace App\Http\Controllers\Games\Trivia\Question;

use App\Http\Controllers\Controller;
use App\Models\Games\Trivia\TriviaMulti;
use App\Models\Games\Trivia\TriviaMultiQuest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controlador de gestión de Preguntas de Trivia.
 *
 * Responsabilidades:
 * - Gestionar preguntas para salas multiusuario
 * - Gestionar preguntas para trivia individual
 * - Validar y almacenar preguntas
 *
 * NO contiene lógica de juego ni reportes.
 */
final class TriviaQuestionController extends Controller
{
    /**
     * Display questions for a specific room.
     */
    public function index($roomId): Response
    {
        $room = TriviaMulti::findOrFail($roomId);
        $questions = TriviaMultiQuest::where('room', $roomId)
            ->orderBy('num_quest', 'asc')
            ->get();

        return Inertia::render('Modules/Admin/Trivia/Questions/Index', [
            'title' => "Preguntas - {$room->name}",
            'room' => $room,
            'questions' => $questions
        ]);
    }

    /**
     * Show the form for creating a new question.
     */
    public function create($roomId): Response
    {
        $room = TriviaMulti::findOrFail($roomId);
        
        return Inertia::render('Modules/Admin/Trivia/Questions/Create', [
            'title' => 'Crear Pregunta',
            'room' => $room
        ]);
    }

    /**
     * Store a newly created question.
     */
    public function store(Request $request, $roomId)
    {
        $room = TriviaMulti::findOrFail($roomId);

        $validated = Validator::make($request->all(), [
            'question' => 'required|string|max:1000',
            'answer' => 'required|string|max:500',
            'option_a' => 'required|string|max:500',
            'option_b' => 'required|string|max:500',
            'option_c' => 'required|string|max:500',
            'option_d' => 'nullable|string|max:500',
            'time_limit' => 'required|integer|min:5|max:120',
            'points' => 'required|integer|min:1|max:100',
        ]);

        if ($validated->fails()) {
            return redirect()->back()
                ->withErrors($validated)
                ->withInput();
        }

        try {
            // Obtener el siguiente número de pregunta
            $nextNumber = TriviaMultiQuest::where('room', $roomId)->max('num_quest') + 1;

            // Verificar que no exceda el límite de preguntas de la sala
            if ($nextNumber > $room->questions_count) {
                Session::flash('error', 'La sala ya tiene el número máximo de preguntas permitidas.');
                return redirect()->back()->withInput();
            }

            TriviaMultiQuest::create([
                'room' => $roomId,
                'num_quest' => $nextNumber,
                'quest' => $request->question,
                'answer' => $request->answer,
                'option_a' => $request->option_a,
                'option_b' => $request->option_b,
                'option_c' => $request->option_c,
                'option_d' => $request->option_d,
                'time_limit' => $request->time_limit,
                'points' => $request->points,
                'created_by' => auth()->user()->id,
            ]);

            Session::flash('message', 'Pregunta creada correctamente.');
            Session::flash('type', 'success');
            return redirect()->route('trivia-questions.index', $roomId);

        } catch (\Exception $e) {
            Log::error('Error al crear pregunta de trivia.', [
                'error' => $e->getMessage(),
                'usuario' => Auth::id() ?? 'Invitado',
                'room_id' => $roomId,
            ]);

            Session::flash('error', 'Hubo un error al crear la pregunta. Inténtelo de nuevo.');
            return redirect()->back()->withInput();
        }
    }

    /**
     * Display the specified question.
     */
    public function show($roomId, $questionId): Response
    {
        $room = TriviaMulti::findOrFail($roomId);
        $question = TriviaMultiQuest::where('room', $roomId)
            ->where('id', $questionId)
            ->firstOrFail();

        return Inertia::render('Modules/Admin/Trivia/Questions/Show', [
            'title' => 'Detalles de Pregunta',
            'room' => $room,
            'question' => $question
        ]);
    }

    /**
     * Show the form for editing the specified question.
     */
    public function edit($roomId, $questionId): Response
    {
        $room = TriviaMulti::findOrFail($roomId);
        $question = TriviaMultiQuest::where('room', $roomId)
            ->where('id', $questionId)
            ->firstOrFail();

        return Inertia::render('Modules/Admin/Trivia/Questions/Edit', [
            'title' => 'Editar Pregunta',
            'room' => $room,
            'question' => $question
        ]);
    }

    /**
     * Update the specified question.
     */
    public function update(Request $request, $roomId, $questionId)
    {
        $room = TriviaMulti::findOrFail($roomId);
        $question = TriviaMultiQuest::where('room', $roomId)
            ->where('id', $questionId)
            ->firstOrFail();

        $validated = Validator::make($request->all(), [
            'question' => 'required|string|max:1000',
            'answer' => 'required|string|max:500',
            'option_a' => 'required|string|max:500',
            'option_b' => 'required|string|max:500',
            'option_c' => 'required|string|max:500',
            'option_d' => 'nullable|string|max:500',
            'time_limit' => 'required|integer|min:5|max:120',
            'points' => 'required|integer|min:1|max:100',
        ]);

        if ($validated->fails()) {
            return redirect()->back()
                ->withErrors($validated)
                ->withInput();
        }

        try {
            $question->update([
                'quest' => $request->question,
                'answer' => $request->answer,
                'option_a' => $request->option_a,
                'option_b' => $request->option_b,
                'option_c' => $request->option_c,
                'option_d' => $request->option_d,
                'time_limit' => $request->time_limit,
                'points' => $request->points,
            ]);

            Session::flash('message', 'Pregunta actualizada correctamente.');
            Session::flash('type', 'success');
            return redirect()->route('trivia-questions.index', $roomId);

        } catch (\Exception $e) {
            Log::error('Error al actualizar pregunta de trivia.', [
                'error' => $e->getMessage(),
                'usuario' => Auth::id() ?? 'Invitado',
                'room_id' => $roomId,
                'question_id' => $questionId,
            ]);

            Session::flash('error', 'Hubo un error al actualizar la pregunta. Inténtelo de nuevo.');
            return redirect()->back()->withInput();
        }
    }

    /**
     * Remove the specified question.
     */
    public function destroy($roomId, $questionId)
    {
        $room = TriviaMulti::findOrFail($roomId);
        $question = TriviaMultiQuest::where('room', $roomId)
            ->where('id', $questionId)
            ->firstOrFail();

        try {
            // Verificar que la sala no esté en progreso
            if (in_array($room->status, [2, 3])) {
                Session::flash('error', 'No se pueden eliminar preguntas de una sala en progreso.');
                return redirect()->back();
            }

            $question->delete();

            // Reordenar las preguntas restantes
            $questions = TriviaMultiQuest::where('room', $roomId)
                ->orderBy('num_quest', 'asc')
                ->get();

            foreach ($questions as $index => $q) {
                $q->update(['num_quest' => $index + 1]);
            }

            Session::flash('message', 'Pregunta eliminada correctamente.');
            Session::flash('type', 'success');
            return redirect()->route('trivia-questions.index', $roomId);

        } catch (\Exception $e) {
            Log::error('Error al eliminar pregunta de trivia.', [
                'error' => $e->getMessage(),
                'usuario' => Auth::id() ?? 'Invitado',
                'room_id' => $roomId,
                'question_id' => $questionId,
            ]);

            Session::flash('error', 'Hubo un error al eliminar la pregunta. Inténtelo de nuevo.');
            return redirect()->back();
        }
    }

    /**
     * Bulk import questions for a room.
     */
    public function import(Request $request, $roomId)
    {
        $room = TriviaMulti::findOrFail($roomId);

        $validated = Validator::make($request->all(), [
            'questions' => 'required|array|min:1|max:' . $room->questions_count,
            'questions.*.question' => 'required|string|max:1000',
            'questions.*.answer' => 'required|string|max:500',
            'questions.*.option_a' => 'required|string|max:500',
            'questions.*.option_b' => 'required|string|max:500',
            'questions.*.option_c' => 'required|string|max:500',
            'questions.*.option_d' => 'nullable|string|max:500',
            'questions.*.time_limit' => 'required|integer|min:5|max:120',
            'questions.*.points' => 'required|integer|min:1|max:100',
        ]);

        if ($validated->fails()) {
            return redirect()->back()
                ->withErrors($validated)
                ->withInput();
        }

        try {
            DB::beginTransaction();

            // Eliminar preguntas existentes si se solicita
            if ($request->has('replace_existing') && $request->replace_existing) {
                TriviaMultiQuest::where('room', $roomId)->delete();
            }

            // Obtener el número inicial
            $startNumber = TriviaMultiQuest::where('room', $roomId)->max('num_quest') + 1;

            foreach ($request->questions as $index => $questionData) {
                TriviaMultiQuest::create([
                    'room' => $roomId,
                    'num_quest' => $startNumber + $index,
                    'quest' => $questionData['question'],
                    'answer' => $questionData['answer'],
                    'option_a' => $questionData['option_a'],
                    'option_b' => $questionData['option_b'],
                    'option_c' => $questionData['option_c'],
                    'option_d' => $questionData['option_d'] ?? null,
                    'time_limit' => $questionData['time_limit'],
                    'points' => $questionData['points'],
                    'created_by' => auth()->user()->id,
                ]);
            }

            DB::commit();

            Session::flash('message', 'Preguntas importadas correctamente.');
            Session::flash('type', 'success');
            return redirect()->route('trivia-questions.index', $roomId);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error al importar preguntas de trivia.', [
                'error' => $e->getMessage(),
                'usuario' => Auth::id() ?? 'Invitado',
                'room_id' => $roomId,
            ]);

            Session::flash('error', 'Hubo un error al importar las preguntas. Inténtelo de nuevo.');
            return redirect()->back()->withInput();
        }
    }
}

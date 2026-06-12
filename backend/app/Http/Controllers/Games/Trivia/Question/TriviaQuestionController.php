<?php

declare(strict_types=1);

namespace App\Http\Controllers\Games\Trivia\Question;

use App\Http\Controllers\Controller;
use App\Domains\Games\Trivia\Models\TriviaQuestion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controlador: Banco de Preguntas de Trivia.
 *
 * Gestiona el CRUD del banco global de preguntas (trivia_questions).
 * roomId="all" → banco completo sin filtro de sala.
 */
final class TriviaQuestionController extends Controller
{
    private const DIFFICULTIES = ['easy', 'medium', 'hard'];

    /**
     * Listar preguntas. roomId="all" → banco global.
     */
    public function index(string $roomId): Response
    {
        $query = TriviaQuestion::query()->orderBy('created_at', 'desc');

        // Filtros opcionales via querystring
        if (request('search')) {
            $query->where('question_text', 'ilike', '%' . request('search') . '%');
        }
        if (request('category')) {
            $query->where('category', request('category'));
        }
        if (request('difficulty')) {
            $query->where('difficulty', request('difficulty'));
        }
        if (request('active') !== null && request('active') !== '') {
            $query->where('is_active', request('active') === '1');
        }

        $questions = $query->paginate(20)->withQueryString();

        // Stats para cards
        $stats = [
            'total'    => TriviaQuestion::count(),
            'active'   => TriviaQuestion::where('is_active', true)->count(),
            'easy'     => TriviaQuestion::where('difficulty', 'easy')->count(),
            'medium'   => TriviaQuestion::where('difficulty', 'medium')->count(),
            'hard'     => TriviaQuestion::where('difficulty', 'hard')->count(),
        ];

        // Categorías disponibles para filtros
        $categories = TriviaQuestion::select('category')
            ->whereNotNull('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        return Inertia::render('Games/Trivia/Admin/QuestionsIndex', [
            'questions'  => $questions,
            'stats'      => $stats,
            'categories' => $categories,
            'filters'    => request()->only(['search', 'category', 'difficulty', 'active']),
            'roomId'     => $roomId,
        ]);
    }

    /**
     * Formulario de creación.
     */
    public function create(string $roomId): Response
    {
        $categories = TriviaQuestion::select('category')
            ->whereNotNull('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        return Inertia::render('Games/Trivia/Admin/QuestionsCreate', [
            'roomId'     => $roomId,
            'categories' => $categories,
        ]);
    }

    /**
     * Guardar nueva pregunta.
     */
    public function store(Request $request, string $roomId)
    {
        $validated = $request->validate([
            'question_text'        => 'required|string|max:1000',
            'options'              => 'required|array|min:2|max:4',
            'options.*'            => 'required|string|max:500',
            'correct_option_index' => 'required|integer|min:0|max:3',
            'time_limit_seconds'   => 'required|integer|min:5|max:120',
            'points'               => 'required|integer|min:1|max:1000',
            'category'             => 'nullable|string|max:50',
            'difficulty'           => 'required|in:easy,medium,hard',
            'explanation'          => 'nullable|string|max:2000',
            'image_url'            => 'nullable|url|max:500',
            'is_active'            => 'boolean',
        ]);

        // Validar que correct_option_index esté dentro del rango de opciones
        if ($validated['correct_option_index'] >= count($validated['options'])) {
            return back()->withErrors([
                'correct_option_index' => 'El índice de respuesta correcta excede el número de opciones.',
            ])->withInput();
        }

        try {
            TriviaQuestion::create([
                'question_text'        => $validated['question_text'],
                'options'              => array_values($validated['options']),
                'correct_option_index' => $validated['correct_option_index'],
                'time_limit_seconds'   => $validated['time_limit_seconds'],
                'points'               => $validated['points'],
                'category'             => $validated['category'] ?? null,
                'difficulty'           => $validated['difficulty'],
                'explanation'          => $validated['explanation'] ?? null,
                'image_url'            => $validated['image_url'] ?? null,
                'is_active'            => $validated['is_active'] ?? true,
            ]);

            return redirect()
                ->route('trivia-questions.index', $roomId)
                ->with('flash', ['type' => 'success', 'message' => 'Pregunta creada correctamente.']);

        } catch (\Throwable $e) {
            Log::error('Error al crear pregunta de trivia.', [
                'error'   => $e->getMessage(),
                'usuario' => Auth::id(),
            ]);

            return back()
                ->withInput()
                ->with('flash', ['type' => 'error', 'message' => 'Error al crear la pregunta. Inténtelo de nuevo.']);
        }
    }

    /**
     * Formulario de edición.
     */
    public function edit(string $roomId, string $questionId): Response
    {
        $question = TriviaQuestion::findOrFail($questionId);

        $categories = TriviaQuestion::select('category')
            ->whereNotNull('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        return Inertia::render('Games/Trivia/Admin/QuestionsEdit', [
            'question'   => $question->makeVisible('correct_option_index'),
            'roomId'     => $roomId,
            'categories' => $categories,
        ]);
    }

    /**
     * Actualizar pregunta existente.
     */
    public function update(Request $request, string $roomId, string $questionId)
    {
        $question = TriviaQuestion::findOrFail($questionId);

        $validated = $request->validate([
            'question_text'        => 'required|string|max:1000',
            'options'              => 'required|array|min:2|max:4',
            'options.*'            => 'required|string|max:500',
            'correct_option_index' => 'required|integer|min:0|max:3',
            'time_limit_seconds'   => 'required|integer|min:5|max:120',
            'points'               => 'required|integer|min:1|max:1000',
            'category'             => 'nullable|string|max:50',
            'difficulty'           => 'required|in:easy,medium,hard',
            'explanation'          => 'nullable|string|max:2000',
            'image_url'            => 'nullable|url|max:500',
            'is_active'            => 'boolean',
        ]);

        if ($validated['correct_option_index'] >= count($validated['options'])) {
            return back()->withErrors([
                'correct_option_index' => 'El índice de respuesta correcta excede el número de opciones.',
            ])->withInput();
        }

        try {
            $question->update([
                'question_text'        => $validated['question_text'],
                'options'              => array_values($validated['options']),
                'correct_option_index' => $validated['correct_option_index'],
                'time_limit_seconds'   => $validated['time_limit_seconds'],
                'points'               => $validated['points'],
                'category'             => $validated['category'] ?? null,
                'difficulty'           => $validated['difficulty'],
                'explanation'          => $validated['explanation'] ?? null,
                'image_url'            => $validated['image_url'] ?? null,
                'is_active'            => $validated['is_active'] ?? true,
            ]);

            return redirect()
                ->route('trivia-questions.index', $roomId)
                ->with('flash', ['type' => 'success', 'message' => 'Pregunta actualizada correctamente.']);

        } catch (\Throwable $e) {
            Log::error('Error al actualizar pregunta de trivia.', [
                'error'       => $e->getMessage(),
                'usuario'     => Auth::id(),
                'question_id' => $questionId,
            ]);

            return back()
                ->withInput()
                ->with('flash', ['type' => 'error', 'message' => 'Error al actualizar la pregunta.']);
        }
    }

    /**
     * Eliminar pregunta (soft delete).
     */
    public function destroy(string $roomId, string $questionId)
    {
        $question = TriviaQuestion::findOrFail($questionId);

        try {
            $question->delete();

            return redirect()
                ->route('trivia-questions.index', $roomId)
                ->with('flash', ['type' => 'success', 'message' => 'Pregunta eliminada correctamente.']);

        } catch (\Throwable $e) {
            Log::error('Error al eliminar pregunta de trivia.', [
                'error'       => $e->getMessage(),
                'usuario'     => Auth::id(),
                'question_id' => $questionId,
            ]);

            return back()->with('flash', ['type' => 'error', 'message' => 'Error al eliminar la pregunta.']);
        }
    }

    /**
     * Importación masiva de preguntas (JSON).
     */
    public function import(Request $request, string $roomId)
    {
        $request->validate([
            'questions'                          => 'required|array|min:1|max:200',
            'questions.*.question_text'          => 'required|string|max:1000',
            'questions.*.options'                => 'required|array|min:2|max:4',
            'questions.*.options.*'              => 'required|string|max:500',
            'questions.*.correct_option_index'   => 'required|integer|min:0|max:3',
            'questions.*.time_limit_seconds'     => 'nullable|integer|min:5|max:120',
            'questions.*.points'                 => 'nullable|integer|min:1|max:1000',
            'questions.*.category'               => 'nullable|string|max:50',
            'questions.*.difficulty'             => 'nullable|in:easy,medium,hard',
            'questions.*.explanation'            => 'nullable|string|max:2000',
        ]);

        try {
            DB::connection('pgsql_trivia')->transaction(function () use ($request) {
                foreach ($request->questions as $data) {
                    TriviaQuestion::create([
                        'question_text'        => $data['question_text'],
                        'options'              => array_values($data['options']),
                        'correct_option_index' => $data['correct_option_index'],
                        'time_limit_seconds'   => $data['time_limit_seconds'] ?? 15,
                        'points'               => $data['points'] ?? 100,
                        'category'             => $data['category'] ?? null,
                        'difficulty'           => $data['difficulty'] ?? 'medium',
                        'explanation'          => $data['explanation'] ?? null,
                        'is_active'            => true,
                    ]);
                }
            });

            return redirect()
                ->route('trivia-questions.index', $roomId)
                ->with('flash', ['type' => 'success', 'message' => count($request->questions) . ' preguntas importadas correctamente.']);

        } catch (\Throwable $e) {
            Log::error('Error al importar preguntas de trivia.', [
                'error'   => $e->getMessage(),
                'usuario' => Auth::id(),
            ]);

            return back()->with('flash', ['type' => 'error', 'message' => 'Error al importar las preguntas.']);
        }
    }

    /**
     * Togglear is_active de una pregunta (PATCH).
     */
    public function toggleActive(string $roomId, string $questionId)
    {
        $question = TriviaQuestion::findOrFail($questionId);
        $question->update(['is_active' => !$question->is_active]);

        return back()->with('flash', [
            'type'    => 'success',
            'message' => 'Estado de la pregunta actualizado.',
        ]);
    }
}

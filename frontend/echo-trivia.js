/**
 * Cliente WebSocket para Trivia usando Laravel Echo + Reverb
 * 
 * Ejemplo de uso en frontend (Vue, React, o vanilla JS)
 */

import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

// Configuración de Echo
const echo = new Echo({
    broadcaster: 'reverb',
    key: process.env.VITE_REVERB_APP_KEY,
    wsHost: process.env.VITE_REVERB_HOST,
    wsPort: process.env.VITE_REVERB_PORT ?? 8080,
    wssPort: process.env.VITE_REVERB_PORT ?? 8080,
    forceTLS: (process.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
    auth: {
        headers: {
            Authorization: `Bearer ${localStorage.getItem('token')}`,
        },
    },
});

/**
 * Clase para manejar eventos de Trivia
 */
class TriviaClient {
    constructor(echo, roomId, gameId, playerId) {
        this.echo = echo;
        this.roomId = roomId;
        this.gameId = gameId;
        this.playerId = playerId;
        this.callbacks = {};
    }

    /**
     * Suscribirse a canales de la sala
     */
    joinRoom() {
        // Canal de sala
        this.echo.channel(`trivia.room.${this.roomId}`)
            .listen('.trivia.player.joined', (e) => {
                console.log('Jugador unido:', e.player);
                this.emit('playerJoined', e);
            })
            .listen('.trivia.player.left', (e) => {
                console.log('Jugador salió:', e.player_id);
                this.emit('playerLeft', e);
            })
            .listen('.trivia.game.started', (e) => {
                console.log('Juego iniciado:', e);
                this.emit('gameStarted', e);
            })
            .listen('.trivia.game.finished', (e) => {
                console.log('Juego finalizado:', e);
                this.emit('gameFinished', e);
            });

        // Canal de juego (privado)
        this.echo.channel(`trivia.game.${this.gameId}`)
            .listen('.trivia.question.sent', (e) => {
                console.log('Nueva pregunta:', e.question);
                this.emit('questionSent', e);
            })
            .listen('.trivia.answer.submitted', (e) => {
                console.log('Respuesta enviada:', e);
                this.emit('answerSubmitted', e);
            })
            .listen('.trivia.score.updated', (e) => {
                console.log('Leaderboard actualizado:', e.leaderboard);
                this.emit('scoreUpdated', e);
            });

        // Canal privado del jugador
        this.echo.private(`trivia.player.${this.playerId}`)
            .listen('.trivia.answer.submitted', (e) => {
                // Respuesta personalizada con si fue correcta
                console.log('Tu respuesta:', e);
                this.emit('myAnswerResult', e);
            });

        return this;
    }

    /**
     * Dejar canales
     */
    leaveRoom() {
        this.echo.leave(`trivia.room.${this.roomId}`);
        this.echo.leave(`trivia.game.${this.gameId}`);
        this.echo.leave(`trivia.player.${this.playerId}`);
    }

    /**
     * Registrar callback para evento
     */
    on(event, callback) {
        if (!this.callbacks[event]) {
            this.callbacks[event] = [];
        }
        this.callbacks[event].push(callback);
        return this;
    }

    /**
     * Emitir evento a callbacks
     */
    emit(event, data) {
        if (this.callbacks[event]) {
            this.callbacks[event].forEach(cb => cb(data));
        }
    }
}

/**
 * Ejemplo de uso en Vue/React
 */
export function useTrivia(roomId, gameId, playerId) {
    const client = new TriviaClient(echo, roomId, gameId, playerId);

    // Ejemplo: Manejar nueva pregunta
    client.on('questionSent', ({ question, server_timestamp }) => {
        // Calcular offset de tiempo servidor-cliente
        const timeOffset = Date.now() - (server_timestamp * 1000);
        
        // Mostrar pregunta
        displayQuestion(question);
        
        // Iniciar temporizador
        startTimer(question.time_limit_seconds, timeOffset);
    });

    // Ejemplo: Manejar actualización de leaderboard
    client.on('scoreUpdated', ({ leaderboard, highlighted_player_id }) => {
        updateLeaderboard(leaderboard.scores);
        
        // Destacar al jugador que acaba de responder
        if (highlighted_player_id === playerId) {
            highlightMyScore();
        }
    });

    // Ejemplo: Manejar resultado de mi respuesta
    client.on('myAnswerResult', (result) => {
        showAnswerFeedback(result.is_correct, result.points_earned);
    });

    return {
        join: () => client.joinRoom(),
        leave: () => client.leaveRoom(),
        on: (event, cb) => client.on(event, cb),
    };
}

// Helpers
function displayQuestion(question) {
    // Implementar según tu UI
    console.log('Mostrar pregunta:', question.question_text);
    console.log('Opciones:', question.options);
}

function startTimer(seconds, offset) {
    const endTime = Date.now() + (seconds * 1000) - offset;
    
    const interval = setInterval(() => {
        const remaining = Math.max(0, endTime - Date.now());
        
        if (remaining === 0) {
            clearInterval(interval);
            onTimeUp();
        }
        
        updateTimerDisplay(Math.ceil(remaining / 1000));
    }, 100);
}

function onTimeUp() {
    console.log('Tiempo agotado');
    // Enviar respuesta vacía o marcar como incorrecta
}

function updateTimerDisplay(seconds) {
    document.getElementById('timer').textContent = seconds;
}

function updateLeaderboard(scores) {
    // Renderizar tabla de posiciones
    console.log('Leaderboard:', scores);
}

function highlightMyScore() {
    console.log('Destacar mi puntuación');
}

function showAnswerFeedback(isCorrect, points) {
    if (isCorrect) {
        console.log(`¡Correcto! +${points} puntos`);
    } else {
        console.log('Incorrecto');
    }
}

export { echo, TriviaClient };

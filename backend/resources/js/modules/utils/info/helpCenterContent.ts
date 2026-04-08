export interface FaqItem {
    question: string
    answer: string
}

export interface InfographicItem {
    question: string
    steps: string[]
}

export const faqGeneral: FaqItem[] = [
    {
        question: '¿Qué es PlayRKE?',
        answer: 'PlayRKE es una plataforma de videojuegos donde puedes ganar dinero digital compitiendo con tus conocimientos y habilidades. Más que arriesgarte a la suerte, apostarás a ti mismo.',
    },
    {
        question: '¿Qué es El Poder del Saber?',
        answer: 'El Poder del Saber es una trivia de 10 preguntas con 4 opciones de respuesta. Debes responder correctamente y en el menor tiempo posible para ganar el prize pool acumulado entre todos los participantes.',
    },
    {
        question: '¿Hay que depositar dinero para utilizar los juegos o aplicaciones de PlayRKE?',
        answer: 'Sí. Para participar en salas con premio real debes tener saldo en tu Wallet. Puedes recargar a través de cualquiera de los métodos de pago disponibles en la plataforma.',
    },
    {
        question: '¿Cómo puedo generar ganancias?',
        answer: 'Participando en los juegos disponibles. Si resultas ganador, el premio se acredita automáticamente en tu Wallet y puedes retirarlo cuando quieras.',
    },
    {
        question: '¿PlayRKE está disponible en mi región?',
        answer: 'Actualmente PlayRKE opera en Venezuela y Brasil. Si resides en otro país puedes acceder bajo tu propia responsabilidad.',
    },
    {
        question: '¿Cada cuánto se abren nuevas salas de juego?',
        answer: 'Las salas se programan periódicamente. Revisa el Directorio de salas para ver las próximas fechas y horarios disponibles.',
    },
    {
        question: '¿Cuánto puedo ganar en PlayRKE?',
        answer: 'Tus ganancias dependen del prize pool de cada sala, que equivale a la suma de las inscripciones de todos los participantes, repartido equitativamente entre los ganadores.',
    },
]

export const faqVenezuela: FaqItem[] = [
    {
        question: '¿Cuáles son los métodos de pago disponibles?',
        answer: 'Para Venezuela: PagoMóvil, transferencia bancaria y USDT (Tether).',
    },
    {
        question: '¿Monto mínimo de depósito?',
        answer: 'El monto mínimo de depósito varía según el método de pago. Consulta la sección de Billetera para ver los montos actualizados.',
    },
]

export const faqBrasil: FaqItem[] = [
    {
        question: '¿Cuáles son los métodos de pago disponibles?',
        answer: 'Para Brasil: PIX y USDT (Tether).',
    },
    {
        question: '¿Monto mínimo de depósito?',
        answer: 'El monto mínimo de depósito varía según el método de pago. Consulta la sección de Billetera para ver los montos actualizados.',
    },
]

export const faqWallet: FaqItem[] = [
    {
        question: '¿En cuánto tiempo se procesa mi depósito?',
        answer: 'Los depósitos se procesan en un tiempo máximo de 24 horas hábiles una vez verificado el pago.',
    },
    {
        question: '¿Monto mínimo de retiro?',
        answer: 'El monto mínimo de retiro varía según el método elegido. Consulta la sección de Billetera para ver los límites actualizados.',
    },
    {
        question: '¿Cuánto demora en llegar un retiro?',
        answer: 'Los retiros se procesan en un máximo de 48 horas hábiles tras la aprobación.',
    },
    {
        question: '¿Cómo funciona el saldo congelado?',
        answer: 'El saldo congelado es aquel que está pendiente de confirmación o en proceso de retiro. No puede usarse hasta que se libere tras la verificación.',
    },
]

export const infographics: InfographicItem[] = [
    {
        question: '¿Cómo depositar?',
        steps: [
            'Ingresa a Billetera → Depósito.',
            'Selecciona el método de pago disponible en tu país.',
            'Realiza el pago y sube el comprobante.',
            'Espera la confirmación (máx. 24 h).',
        ],
    },
    {
        question: '¿Cómo jugar / participar?',
        steps: [
            'Asegúrate de tener saldo en tu Wallet.',
            'Entra al Directorio y elige una sala activa.',
            'Inscríbete antes del cierre.',
            'A la hora indicada, responde las preguntas correctamente y lo más rápido posible.',
        ],
    },
    {
        question: '¿Cómo retirar?',
        steps: [
            'Ingresa a Billetera → Retiro.',
            'Elige el método y monto a retirar.',
            'Confirma los datos bancarios o dirección.',
            'Espera la aprobación (máx. 48 h).',
        ],
    },
    {
        question: '¿Cómo transferir?',
        steps: [
            'Ingresa a Billetera → Transferencia.',
            'Ingresa el usuario destinatario.',
            'Indica el monto y confirma.',
            'El saldo se acredita de inmediato.',
        ],
    },
]

// country_id de Venezuela en la DB
export const VENEZUELA_COUNTRY_ID = 234

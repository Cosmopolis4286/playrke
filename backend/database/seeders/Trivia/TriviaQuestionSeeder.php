<?php

namespace Database\Seeders\Trivia;

use App\Domains\Games\Trivia\Models\TriviaQuestion;
use Illuminate\Database\Seeder;

class TriviaQuestionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run() : void
    {
        $questions = [
            // ── GEOGRAFÍA ──────────────────────────────────────────────
            [
                'question_text' => '¿Cuál es la capital de Francia?',
                'options' => ['París', 'Londres', 'Berlín', 'Madrid'],
                'correct_option_index' => 0,
                'category' => 'Geografía',
                'difficulty' => 'easy',
                'explanation' => 'París es la capital de Francia desde el siglo X.',
            ],
            [
                'question_text' => '¿Cuál es el océano más grande del mundo?',
                'options' => ['Atlántico', 'Índico', 'Pacífico', 'Ártico'],
                'correct_option_index' => 2,
                'category' => 'Geografía',
                'difficulty' => 'easy',
                'explanation' => 'El Océano Pacífico cubre aproximadamente 165,25 millones km².',
            ],
            [
                'question_text' => '¿Cuál es el río más largo del mundo?',
                'options' => ['Misisipi', 'Nilo', 'Amazonas', 'Yangtsé'],
                'correct_option_index' => 2,
                'category' => 'Geografía',
                'difficulty' => 'medium',
                'explanation' => 'El río Amazonas tiene una longitud aproximada de 6,400 km, superando al Nilo.',
            ],
            [
                'question_text' => '¿En qué continente se encuentra el desierto del Sahara?',
                'options' => ['Asia', 'África', 'América', 'Oceanía'],
                'correct_option_index' => 1,
                'category' => 'Geografía',
                'difficulty' => 'easy',
                'explanation' => 'El Sahara está en el norte de África y es el desierto cálido más grande del mundo.',
            ],
            [
                'question_text' => '¿Cuál es el país más pequeño del mundo?',
                'options' => ['Mónaco', 'Vaticano', 'San Marino', 'Liechtenstein'],
                'correct_option_index' => 1,
                'category' => 'Geografía',
                'difficulty' => 'medium',
                'explanation' => 'La Ciudad del Vaticano tiene apenas 0.44 km².',
            ],
            [
                'question_text' => '¿Cuál es la montaña más alta de América del Sur?',
                'options' => ['Monte Fitz Roy', 'Aconcagua', 'Ojos del Salado', 'Cerro Torre'],
                'correct_option_index' => 1,
                'category' => 'Geografía',
                'difficulty' => 'hard',
                'explanation' => 'El Aconcagua tiene 6,961 m s.n.m. y está en Argentina.',
            ],

            // ── HISTORIA ───────────────────────────────────────────────
            [
                'question_text' => '¿En qué año llegó el hombre a la Luna?',
                'options' => ['1965', '1969', '1972', '1959'],
                'correct_option_index' => 1,
                'category' => 'Historia',
                'difficulty' => 'easy',
                'explanation' => 'La misión Apollo 11 llegó a la Luna el 20 de julio de 1969.',
            ],
            [
                'question_text' => '¿Quién fue el primer presidente de los Estados Unidos?',
                'options' => ['Thomas Jefferson', 'Abraham Lincoln', 'George Washington', 'John Adams'],
                'correct_option_index' => 2,
                'category' => 'Historia',
                'difficulty' => 'easy',
                'explanation' => 'George Washington asumió la presidencia en 1789.',
            ],
            [
                'question_text' => '¿En qué año cayó el Muro de Berlín?',
                'options' => ['1987', '1989', '1991', '1985'],
                'correct_option_index' => 1,
                'category' => 'Historia',
                'difficulty' => 'medium',
                'explanation' => 'El Muro de Berlín cayó el 9 de noviembre de 1989.',
            ],
            [
                'question_text' => '¿Qué civilización construyó Machu Picchu?',
                'options' => ['Maya', 'Azteca', 'Inca', 'Olmeca'],
                'correct_option_index' => 2,
                'category' => 'Historia',
                'difficulty' => 'medium',
                'explanation' => 'Machu Picchu fue construida por el imperio Inca en el siglo XV.',
            ],
            [
                'question_text' => '¿En qué año comenzó la Primera Guerra Mundial?',
                'options' => ['1912', '1914', '1916', '1918'],
                'correct_option_index' => 1,
                'category' => 'Historia',
                'difficulty' => 'medium',
                'explanation' => 'La Primera Guerra Mundial comenzó en 1914 tras el asesinato del archiduque Francisco Fernando.',
            ],
            [
                'question_text' => '¿Quién fue el líder de la Revolución Cubana en 1959?',
                'options' => ['Che Guevara', 'Fidel Castro', 'Camilo Cienfuegos', 'Raúl Castro'],
                'correct_option_index' => 1,
                'category' => 'Historia',
                'difficulty' => 'hard',
                'explanation' => 'Fidel Castro lideró la Revolución Cubana que derrocó a Batista en 1959.',
            ],

            // ── CIENCIA ────────────────────────────────────────────────
            [
                'question_text' => '¿Cuál es el elemento químico más abundante en el universo?',
                'options' => ['Oxígeno', 'Carbono', 'Hidrógeno', 'Helio'],
                'correct_option_index' => 2,
                'category' => 'Ciencia',
                'difficulty' => 'medium',
                'explanation' => 'El hidrógeno constituye aproximadamente el 75% de la materia bariónica del universo.',
            ],
            [
                'question_text' => '¿Cuántos huesos tiene el cuerpo humano adulto?',
                'options' => ['196', '206', '216', '186'],
                'correct_option_index' => 1,
                'category' => 'Ciencia',
                'difficulty' => 'medium',
                'explanation' => 'El cuerpo humano adulto tiene 206 huesos; los bebés nacen con alrededor de 270.',
            ],
            [
                'question_text' => '¿Qué planeta es conocido como el "Planeta Rojo"?',
                'options' => ['Venus', 'Júpiter', 'Marte', 'Saturno'],
                'correct_option_index' => 2,
                'category' => 'Ciencia',
                'difficulty' => 'easy',
                'explanation' => 'Marte debe su color rojizo al óxido de hierro en su superficie.',
            ],
            [
                'question_text' => '¿Cuál es la velocidad de la luz en el vacío?',
                'options' => ['200,000 km/s', '300,000 km/s', '400,000 km/s', '150,000 km/s'],
                'correct_option_index' => 1,
                'category' => 'Ciencia',
                'difficulty' => 'hard',
                'explanation' => 'La velocidad de la luz es aproximadamente 299,792 km/s, redondeada a 300,000 km/s.',
            ],
            [
                'question_text' => '¿Qué gas respiramos principalmente para sobrevivir?',
                'options' => ['Nitrógeno', 'Oxígeno', 'Dióxido de carbono', 'Hidrógeno'],
                'correct_option_index' => 1,
                'category' => 'Ciencia',
                'difficulty' => 'easy',
                'explanation' => 'El oxígeno es esencial para la respiración celular aeróbica.',
            ],
            [
                'question_text' => '¿Cuál es el órgano más grande del cuerpo humano?',
                'options' => ['Hígado', 'Pulmón', 'Piel', 'Intestino'],
                'correct_option_index' => 2,
                'category' => 'Ciencia',
                'difficulty' => 'medium',
                'explanation' => 'La piel es el órgano más grande, con aproximadamente 2 m² de superficie.',
            ],
            [
                'question_text' => '¿Qué tipo de animal es una ballena?',
                'options' => ['Pez', 'Mamífero', 'Reptil', 'Anfibio'],
                'correct_option_index' => 1,
                'category' => 'Ciencia',
                'difficulty' => 'easy',
                'explanation' => 'Las ballenas son mamíferos marinos que respiran aire a través de pulmones.',
            ],

            // ── ARTE Y CULTURA ─────────────────────────────────────────
            [
                'question_text' => '¿Quién pintó la Mona Lisa?',
                'options' => ['Miguel Ángel', 'Leonardo da Vinci', 'Pablo Picasso', 'Vincent van Gogh'],
                'correct_option_index' => 1,
                'category' => 'Arte',
                'difficulty' => 'easy',
                'explanation' => 'Leonardo da Vinci pintó la Mona Lisa entre 1503 y 1519.',
            ],
            [
                'question_text' => '¿En qué museo se encuentra la Mona Lisa?',
                'options' => ['Museo del Prado', 'Museo Británico', 'Museo del Louvre', 'Galería Uffizi'],
                'correct_option_index' => 2,
                'category' => 'Arte',
                'difficulty' => 'medium',
                'explanation' => 'La Mona Lisa se exhibe en el Museo del Louvre en París desde 1797.',
            ],
            [
                'question_text' => '¿Quién compuso la "Novena Sinfonía"?',
                'options' => ['Mozart', 'Bach', 'Beethoven', 'Vivaldi'],
                'correct_option_index' => 2,
                'category' => 'Arte',
                'difficulty' => 'medium',
                'explanation' => 'Beethoven compuso su Novena Sinfonía entre 1822 y 1824, estando ya casi sordo.',
            ],
            [
                'question_text' => '¿Qué artista es conocido por la obra "La persistencia de la memoria"?',
                'options' => ['Pablo Picasso', 'Salvador Dalí', 'Joan Miró', 'Frida Kahlo'],
                'correct_option_index' => 1,
                'category' => 'Arte',
                'difficulty' => 'hard',
                'explanation' => 'Salvador Dalí pintó "La persistencia de la memoria" en 1931, famosa por sus relojes derretidos.',
            ],
            [
                'question_text' => '¿Cuál es el instrumento musical con más cuerdas en una orquesta sinfónica?',
                'options' => ['Violín', 'Arpa', 'Piano', 'Guitarra'],
                'correct_option_index' => 1,
                'category' => 'Arte',
                'difficulty' => 'hard',
                'explanation' => 'El arpa de pedales tiene 47 cuerdas, más que cualquier otro instrumento orquestal.',
            ],

            // ── DEPORTES ───────────────────────────────────────────────
            [
                'question_text' => '¿Cuántos jugadores tiene un equipo de fútbol en la cancha?',
                'options' => ['9', '10', '11', '12'],
                'correct_option_index' => 2,
                'category' => 'Deportes',
                'difficulty' => 'easy',
                'explanation' => 'Cada equipo de fútbol juega con 11 jugadores en la cancha.',
            ],
            [
                'question_text' => '¿En qué país se celebraron los primeros Juegos Olímpicos modernos?',
                'options' => ['Francia', 'Grecia', 'Italia', 'Inglaterra'],
                'correct_option_index' => 1,
                'category' => 'Deportes',
                'difficulty' => 'medium',
                'explanation' => 'Los primeros Juegos Olímpicos modernos se celebraron en Atenas, Grecia, en 1896.',
            ],
            [
                'question_text' => '¿Cuántos sets se necesita ganar para ganar un partido de tenis masculino de Grand Slam?',
                'options' => ['2', '3', '4', '5'],
                'correct_option_index' => 1,
                'category' => 'Deportes',
                'difficulty' => 'medium',
                'explanation' => 'En los Grand Slam masculinos se juega al mejor de 5 sets, se necesitan 3 para ganar.',
            ],
            [
                'question_text' => '¿Qué país ha ganado más Copas del Mundo de fútbol?',
                'options' => ['Alemania', 'Italia', 'Brasil', 'Argentina'],
                'correct_option_index' => 2,
                'category' => 'Deportes',
                'difficulty' => 'easy',
                'explanation' => 'Brasil ha ganado 5 Copas del Mundo (1958, 1962, 1970, 1994, 2002).',
            ],
            [
                'question_text' => '¿En qué deporte se utiliza el término "ace"?',
                'options' => ['Baloncesto', 'Tenis', 'Voleibol', 'Béisbol'],
                'correct_option_index' => 1,
                'category' => 'Deportes',
                'difficulty' => 'easy',
                'explanation' => 'En tenis, un "ace" es un saque que el rival no logra tocar.',
            ],

            // ── TECNOLOGÍA ─────────────────────────────────────────────
            [
                'question_text' => '¿Quién es considerado el padre de la computación?',
                'options' => ['Steve Jobs', 'Alan Turing', 'Bill Gates', 'Tim Berners-Lee'],
                'correct_option_index' => 1,
                'category' => 'Tecnología',
                'difficulty' => 'medium',
                'explanation' => 'Alan Turing es considerado el padre de la computación teórica y la inteligencia artificial.',
            ],
            [
                'question_text' => '¿En qué año se fundó Google?',
                'options' => ['1996', '1998', '2000', '2002'],
                'correct_option_index' => 1,
                'category' => 'Tecnología',
                'difficulty' => 'medium',
                'explanation' => 'Google fue fundada el 4 de septiembre de 1998 por Larry Page y Sergey Brin.',
            ],
            [
                'question_text' => '¿Qué lenguaje de programación creó Guido van Rossum?',
                'options' => ['Java', 'Python', 'Ruby', 'C++'],
                'correct_option_index' => 1,
                'category' => 'Tecnología',
                'difficulty' => 'medium',
                'explanation' => 'Python fue creado por Guido van Rossum y lanzado en 1991.',
            ],
            [
                'question_text' => '¿Qué significa las siglas HTML?',
                'options' => ['HyperText Markup Language', 'High Tech Modern Language', 'HyperTransfer Markup Language', 'Home Tool Markup Language'],
                'correct_option_index' => 0,
                'category' => 'Tecnología',
                'difficulty' => 'easy',
                'explanation' => 'HTML significa HyperText Markup Language, el lenguaje estándar para páginas web.',
            ],
            [
                'question_text' => '¿Qué empresa creó el iPhone?',
                'options' => ['Samsung', 'Apple', 'Microsoft', 'Google'],
                'correct_option_index' => 1,
                'category' => 'Tecnología',
                'difficulty' => 'easy',
                'explanation' => 'Apple lanzó el primer iPhone en 2007.',
            ],

            // ── ENTRETENIMIENTO ────────────────────────────────────────
            [
                'question_text' => '¿Quién escribió "Cien años de soledad"?',
                'options' => ['Mario Vargas Llosa', 'Gabriel García Márquez', 'Pablo Neruda', 'Jorge Luis Borges'],
                'correct_option_index' => 1,
                'category' => 'Entretenimiento',
                'difficulty' => 'easy',
                'explanation' => 'Gabriel García Márquez publicó "Cien años de soledad" en 1967.',
            ],
            [
                'question_text' => '¿Cuál es la película más taquillera de la historia (sin ajustar por inflación)?',
                'options' => ['Titanic', 'Avatar', 'Avengers: Endgame', 'Star Wars: El despertar de la fuerza'],
                'correct_option_index' => 1,
                'category' => 'Entretenimiento',
                'difficulty' => 'hard',
                'explanation' => 'Avatar (2009) recaudó más de 2,900 millones de dólares mundialmente.',
            ],
            [
                'question_text' => '¿Cómo se llama el mago protagonista de la saga de J.K. Rowling?',
                'options' => ['Frodo Bolsón', 'Harry Potter', 'Percy Jackson', 'Gandalf'],
                'correct_option_index' => 1,
                'category' => 'Entretenimiento',
                'difficulty' => 'easy',
                'explanation' => 'Harry Potter es el protagonista de la heptalogía escrita por J.K. Rowling.',
            ],
            [
                'question_text' => '¿Qué banda musical interpretó "Bohemian Rhapsody"?',
                'options' => ['The Beatles', 'Led Zeppelin', 'Queen', 'Pink Floyd'],
                'correct_option_index' => 2,
                'category' => 'Entretenimiento',
                'difficulty' => 'medium',
                'explanation' => 'Queen lanzó "Bohemian Rhapsody" en 1975, escrita por Freddie Mercury.',
            ],
            [
                'question_text' => '¿En qué año se estrenó la primera película de Star Wars?',
                'options' => ['1975', '1977', '1979', '1980'],
                'correct_option_index' => 1,
                'category' => 'Entretenimiento',
                'difficulty' => 'medium',
                'explanation' => 'Star Wars: Episode IV se estrenó el 25 de mayo de 1977.',
            ],

            // ── NATURALEZA ─────────────────────────────────────────────
            [
                'question_text' => '¿Cuál es el animal terrestre más rápido?',
                'options' => ['León', 'Guepardo', 'Gacela', 'Caballo'],
                'correct_option_index' => 1,
                'category' => 'Naturaleza',
                'difficulty' => 'easy',
                'explanation' => 'El guepardo puede alcanzar velocidades de hasta 120 km/h.',
            ],
            [
                'question_text' => '¿Cuántas patas tiene una araña?',
                'options' => ['6', '8', '10', '12'],
                'correct_option_index' => 1,
                'category' => 'Naturaleza',
                'difficulty' => 'easy',
                'explanation' => 'Las arañas son arácnidos y tienen 8 patas.',
            ],
            [
                'question_text' => '¿Cuál es el animal más grande del mundo?',
                'options' => ['Elefante africano', 'Ballena azul', 'Tiburón ballena', 'Jirafa'],
                'correct_option_index' => 1,
                'category' => 'Naturaleza',
                'difficulty' => 'easy',
                'explanation' => 'La ballena azul puede medir hasta 30 metros y pesar 180 toneladas.',
            ],
            [
                'question_text' => '¿Qué tipo de árbol es la secuoya?',
                'options' => ['Perenne', 'Caduco', 'Tropical', 'Bonsái'],
                'correct_option_index' => 0,
                'category' => 'Naturaleza',
                'difficulty' => 'hard',
                'explanation' => 'Las secuoyas son coníferas perennes que pueden vivir más de 3,000 años.',
            ],
        ];

        $created = 0;
        foreach ($questions as $qData) {
            TriviaQuestion::create([
                'question_text' => $qData['question_text'],
                'options' => $qData['options'],
                'correct_option_index' => $qData['correct_option_index'],
                'category' => $qData['category'],
                'difficulty' => $qData['difficulty'],
                'explanation' => $qData['explanation'],
                'time_limit_seconds' => 15,
                'points' => 100,
                'is_active' => true,
            ]);
            $created++;
        }

        $this->command->info("Se insertaron {$created} preguntas de trivia en el banco.");
    }
}

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PlayRKE - Demo Frontend Moderno</title>
    @vite(['resources/css/global/styles.css'])
    <style>
        /* Estilos inline temporales para la demo */
        .gradient-bg {
            background: linear-gradient(135deg, #0ea5e9 0%, #d946ef 100%);
        }
        
        .glass-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .animate-float {
            animation: float 3s ease-in-out infinite;
        }

        .animate-slide-up {
            animation: slideUp 0.8s ease-out;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }

        @keyframes slideUp {
            0% { opacity: 0; transform: translateY(50px); }
            100% { opacity: 1; transform: translateY(0); }
        }

        .gradient-text {
            background: linear-gradient(135deg, #0ea5e9, #d946ef);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hover-lift {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .hover-lift:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-900 via-purple-900 to-indigo-900 min-h-screen">
    <!-- Background Elements -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none">
        <div class="absolute -top-40 -right-40 w-80 h-80 bg-purple-500 rounded-full opacity-20 blur-3xl animate-pulse"></div>
        <div class="absolute -bottom-40 -left-40 w-80 h-80 bg-blue-500 rounded-full opacity-20 blur-3xl animate-pulse" style="animation-delay: 2s;"></div>
        <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-60 h-60 bg-indigo-500 rounded-full opacity-10 blur-3xl animate-float"></div>
    </div>

    <!-- Header -->
    <header class="relative z-10 p-6">
        <div class="max-w-7xl mx-auto">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 gradient-bg rounded-2xl flex items-center justify-center text-white font-bold text-xl shadow-lg">
                        P
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-white">PlayRKE</h1>
                        <p class="text-sm text-gray-300">Frontend Moderno Demo</p>
                    </div>
                </div>
                
                <nav class="hidden md:flex items-center gap-8">
                    <a href="#" class="text-gray-300 hover:text-white transition-colors">Dashboard</a>
                    <a href="#" class="text-gray-300 hover:text-white transition-colors">Juegos</a>
                    <a href="#" class="text-gray-300 hover:text-white transition-colors">Perfil</a>
                </nav>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="relative z-10 p-6">
        <div class="max-w-7xl mx-auto">
            <!-- Hero Section -->
            <section class="text-center mb-16 animate-slide-up">
                <h2 class="text-6xl font-bold text-white mb-6">
                    Nuevo Frontend
                    <span class="gradient-text block">Premium</span>
                </h2>
                <p class="text-xl text-gray-300 max-w-3xl mx-auto mb-8">
                    Sistema de diseño moderno construido con Vue 3, TailwindCSS e Inertia.js. 
                    Sin AdminLTE, sin Bootstrap, solo tecnologías de vanguardia.
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <button class="gradient-bg text-white px-8 py-4 rounded-xl font-semibold hover-lift shadow-lg">
                        Ver Dashboard
                    </button>
                    <button class="glass-card text-white px-8 py-4 rounded-xl font-semibold hover-lift">
                        Documentación
                    </button>
                </div>
            </section>

            <!-- Features Grid -->
            <section class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 mb-16">
                <!-- Feature 1 -->
                <div class="glass-card p-8 rounded-2xl hover-lift animate-slide-up" style="animation-delay: 0.2s">
                    <div class="w-16 h-16 gradient-bg rounded-2xl flex items-center justify-center mb-6 mx-auto">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold text-white mb-4 text-center">Performance</h3>
                    <p class="text-gray-300 text-center">
                        Bundle 68% más pequeño, carga 3x más rápida con Vite y optimizaciones modernas.
                    </p>
                </div>

                <!-- Feature 2 -->
                <div class="glass-card p-8 rounded-2xl hover-lift animate-slide-up" style="animation-delay: 0.4s">
                    <div class="w-16 h-16 gradient-bg rounded-2xl flex items-center justify-center mb-6 mx-auto">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zM21 5a2 2 0 00-2-2h-4a2 2 0 00-2 2v12a4 4 0 004 4h4a2 2 0 002-2V5z"></path>
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold text-white mb-4 text-center">Componentes</h3>
                    <p class="text-gray-300 text-center">
                        Sistema de diseño coherente con componentes Vue reutilizables y animaciones premium.
                    </p>
                </div>

                <!-- Feature 3 -->
                <div class="glass-card p-8 rounded-2xl hover-lift animate-slide-up" style="animation-delay: 0.6s">
                    <div class="w-16 h-16 gradient-bg rounded-2xl flex items-center justify-center mb-6 mx-auto">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold text-white mb-4 text-center">Developer Experience</h3>
                    <p class="text-gray-300 text-center">
                        TypeScript, HMR, linting automático y herramientas modernas para desarrollo ágil.
                    </p>
                </div>
            </section>

            <!-- Stats -->
            <section class="glass-card rounded-2xl p-8 mb-16 animate-slide-up" style="animation-delay: 0.8s">
                <h3 class="text-3xl font-bold text-white text-center mb-8">Mejoras de Performance</h3>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                    <div class="text-center">
                        <div class="text-4xl font-bold gradient-text mb-2">68%</div>
                        <div class="text-gray-300">Reducción Bundle</div>
                    </div>
                    <div class="text-center">
                        <div class="text-4xl font-bold gradient-text mb-2">3x</div>
                        <div class="text-gray-300">Más Rápido</div>
                    </div>
                    <div class="text-center">
                        <div class="text-4xl font-bold gradient-text mb-2">95</div>
                        <div class="text-gray-300">Lighthouse Score</div>
                    </div>
                    <div class="text-center">
                        <div class="text-4xl font-bold gradient-text mb-2">100%</div>
                        <div class="text-gray-300">Moderno</div>
                    </div>
                </div>
            </section>

            <!-- Tech Stack -->
            <section class="text-center animate-slide-up" style="animation-delay: 1s">
                <h3 class="text-3xl font-bold text-white mb-8">Stack Tecnológico</h3>
                <div class="flex flex-wrap justify-center gap-4">
                    <span class="glass-card px-6 py-3 rounded-full text-white font-medium">Vue 3</span>
                    <span class="glass-card px-6 py-3 rounded-full text-white font-medium">TypeScript</span>
                    <span class="glass-card px-6 py-3 rounded-full text-white font-medium">Inertia.js</span>
                    <span class="glass-card px-6 py-3 rounded-full text-white font-medium">TailwindCSS</span>
                    <span class="glass-card px-6 py-3 rounded-full text-white font-medium">Pinia</span>
                    <span class="glass-card px-6 py-3 rounded-full text-white font-medium">Vite</span>
                    <span class="glass-card px-6 py-3 rounded-full text-white font-medium">Heroicons</span>
                    <span class="glass-card px-6 py-3 rounded-full text-white font-medium">VueUse</span>
                </div>
            </section>
        </div>
    </main>

    <!-- Footer -->
    <footer class="relative z-10 p-6 mt-16">
        <div class="max-w-7xl mx-auto text-center">
            <div class="glass-card rounded-2xl p-6">
                <p class="text-gray-300">
                    © 2024 PlayRKE - Frontend Moderno. Construido con ❤️ y tecnologías de vanguardia.
                </p>
            </div>
        </div>
    </footer>
</body>
</html>
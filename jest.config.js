module.exports = {
    testEnvironment: 'jsdom', // Para pruebas que involucren DOM
    transform: {
        '^.+\\.js$': 'babel-jest', // Transformar archivos JS con Babel
    },
    moduleFileExtensions: ['js', 'json'], // Extensiones de archivo soportadas
    roots: ['<rootDir>/resources/js'], // Carpeta raíz para los tests
    moduleNameMapper: {
        '^jquery$': require.resolve('jquery'), // Resolver jQuery en los tests
    },
};

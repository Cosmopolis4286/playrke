/**
 * Barrel exports para todos los tipos
 * Punto de entrada centralizado para importar tipos
 */

// Tipos de base de datos
export * from './database.js';

// Tipos de API (request/response)
export * from './api.js';

// Re-exports de librerías externas comúnmente usadas
export type { FastifyRequest, FastifyReply, FastifyInstance } from 'fastify';
export type { ZodSchema, ZodError } from 'zod';
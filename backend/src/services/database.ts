import { PrismaClient } from '@prisma/client';
import { logger } from '@/utils/logger.js';

// Singleton Prisma client
class DatabaseService {
  private static instance: DatabaseService;
  private prisma: PrismaClient;

  private constructor() {
    this.prisma = new PrismaClient({
      log: [
        { level: 'error', emit: 'event' },
        { level: 'warn', emit: 'event' },
        { level: 'info', emit: 'event' },
        { level: 'query', emit: 'event' }
      ]
    });

    // Log database events
    this.prisma.$on('error', (e) => {
      logger.error('Prisma Error', e);
    });

    this.prisma.$on('warn', (e) => {
      logger.warn('Prisma Warning', e);
    });

    this.prisma.$on('info', (e) => {
      logger.info('Prisma Info', e);
    });

    this.prisma.$on('query', (e) => {
      logger.dbQuery(e.query, e.duration);
    });
  }

  public static getInstance(): DatabaseService {
    if (!DatabaseService.instance) {
      DatabaseService.instance = new DatabaseService();
    }
    return DatabaseService.instance;
  }

  public get client(): PrismaClient {
    return this.prisma;
  }

  public async connect(): Promise<void> {
    try {
      await this.prisma.$connect();
      logger.info('📦 Connected to database successfully');
    } catch (error) {
      logger.error('Failed to connect to database', error);
      throw error;
    }
  }

  public async disconnect(): Promise<void> {
    try {
      await this.prisma.$disconnect();
      logger.info('📦 Disconnected from database');
    } catch (error) {
      logger.error('Error disconnecting from database', error);
    }
  }

  // Health check
  public async isHealthy(): Promise<boolean> {
    try {
      await this.prisma.$queryRaw`SELECT 1`;
      return true;
    } catch {
      return false;
    }
  }
}

// Export singleton instance
export const db = DatabaseService.getInstance().client;
export const dbService = DatabaseService.getInstance();
import { PrismaClient } from '@prisma/client';

// Mocking Prisma Client for preview without a real database
export const prisma = {
    product: {
        findMany: async () => [
            { id: '1', name: 'Premium Dog Food', price: 49.99, stock: 10, description: 'High-quality ingredients.', imageUrl: '' },
            { id: '2', name: 'Catnip Toy', price: 9.99, stock: 5, description: 'Entertain your feline friend.', imageUrl: '' },
            { id: '3', name: 'Leather Collar', price: 29.99, stock: 50, description: 'Stylish and durable.', imageUrl: '' },
        ],
        count: async () => 3,
    },
    appointment: {
        findMany: async () => [],
        count: async () => 0,
        create: async (data: any) => ({ id: '1', ...data.data }),
    }
} as unknown as PrismaClient;

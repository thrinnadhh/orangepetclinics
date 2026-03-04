export const dynamic = 'force-dynamic';
import { NextResponse } from 'next/server';
import { prisma } from '@/lib/prisma';

export async function POST(req: Request) {
    try {
        const body = await req.json();
        const { userId, serviceId, date } = body;

        if (!userId || !serviceId || !date) {
            return NextResponse.json({ error: 'Missing required fields' }, { status: 400 });
        }

        const appointment = await prisma.appointment.create({
            data: {
                userId,
                serviceId,
                date: new Date(date),
            }
        });

        return NextResponse.json(appointment, { status: 201 });
    } catch {
        return NextResponse.json({ error: 'Failed to create appointment' }, { status: 500 });
    }
}

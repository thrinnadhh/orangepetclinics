export const dynamic = 'force-dynamic';
import { prisma } from '@/lib/prisma';
import { DollarSign, Users, CalendarCheck, Package, ArrowUpRight } from 'lucide-react';
import Link from 'next/link';

export default async function AdminDashboard() {
    const productsCount = await prisma.product.count();
    const appointmentsCount = await prisma.appointment.count();

    // Mock data for analytics
    const stats = [
        { name: 'Total Revenue', value: '$12,450', icon: DollarSign, trend: '+14%' },
        { name: 'Total Customers', value: '842', icon: Users, trend: '+5%' },
        { name: 'Active Appointments', value: appointmentsCount.toString(), icon: CalendarCheck, trend: '+2%' },
        { name: 'Products in Store', value: productsCount.toString(), icon: Package, trend: '+0%' },
    ];

    return (
        <div>
            <div className="mb-8">
                <h1 className="text-3xl font-extrabold text-dark-900 tracking-tight">Overview</h1>
                <p className="text-dark-500 font-light mt-1">Welcome back, here is your store&apos;s performance at a glance.</p>
            </div>

            {/* Stats Grid */}
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
                {stats.map((stat) => (
                    <div key={stat.name} className="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex items-start justify-between group hover:shadow-md transition-all">
                        <div>
                            <p className="text-sm font-medium text-dark-500 mb-1">{stat.name}</p>
                            <h3 className="text-2xl font-bold text-dark-900">{stat.value}</h3>
                        </div>
                        <div className="text-right">
                            <div className="w-10 h-10 rounded-lg bg-brand-50 text-brand-500 flex items-center justify-center mb-2">
                                <stat.icon className="w-5 h-5" />
                            </div>
                            <span className="flex items-center text-xs font-bold text-green-500">
                                <ArrowUpRight className="w-3 h-3 mr-1" />{stat.trend}
                            </span>
                        </div>
                    </div>
                ))}
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <div className="bg-white p-8 rounded-2xl border border-gray-100 shadow-sm">
                    <div className="flex justify-between items-center mb-6">
                        <h3 className="text-lg font-bold text-dark-900">Recent Orders</h3>
                        <Link href="/admin/orders" className="text-sm font-medium text-brand-500 hover:text-brand-600">View All</Link>
                    </div>
                    <p className="text-sm text-dark-400 font-light mb-4">No recent orders found.</p>
                </div>

                <div className="bg-white p-8 rounded-2xl border border-gray-100 shadow-sm">
                    <div className="flex justify-between items-center mb-6">
                        <h3 className="text-lg font-bold text-dark-900">Upcoming Appointments</h3>
                        <Link href="/admin/appointments" className="text-sm font-medium text-brand-500 hover:text-brand-600">View All</Link>
                    </div>
                    <p className="text-sm text-dark-400 font-light mb-4">No appointments scheduled for today.</p>
                </div>
            </div>
        </div>
    );
}

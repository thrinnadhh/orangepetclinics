import Link from 'next/link';
import { LayoutDashboard, Package, CalendarCheck, ShoppingBag, LogOut, Settings } from 'lucide-react';

export default function AdminLayout({ children }: { children: React.ReactNode }) {
    return (
        <div className="flex h-screen bg-gray-50 pb-0 mb-0 !pt-0 mt-0">
            {/* Sidebar - Fix: remove the pt-20 from RootLayout for admin if possible, but assuming RootLayout is global, we might have sticky issues. For simplicity, we just use h-screen and fixed sidebar. */}
            {/* Actually RootLayout has pt-20 on main tag. We should adjust this visually. */}

            <aside className="w-64 bg-dark-900 text-white flex flex-col h-full sticky left-0 shadow-xl z-20 overflow-y-auto mt-[-80px] pt-8">
                <div className="px-6 mb-8 mt-12">
                    <h2 className="text-sm uppercase tracking-widest text-dark-500 font-bold mb-6">Admin Panel</h2>
                    <nav className="space-y-2">
                        <Link href="/admin" className="flex items-center gap-3 px-4 py-3 rounded-lg bg-dark-800 text-brand-500 font-medium">
                            <LayoutDashboard className="w-5 h-5" /> Dashboard
                        </Link>
                        <Link href="/admin/products" className="flex items-center gap-3 px-4 py-3 rounded-lg text-dark-400 hover:text-white hover:bg-dark-800 transition-colors">
                            <Package className="w-5 h-5" /> Products
                        </Link>
                        <Link href="/admin/appointments" className="flex items-center gap-3 px-4 py-3 rounded-lg text-dark-400 hover:text-white hover:bg-dark-800 transition-colors">
                            <CalendarCheck className="w-5 h-5" /> Appointments
                        </Link>
                        <Link href="/admin/orders" className="flex items-center gap-3 px-4 py-3 rounded-lg text-dark-400 hover:text-white hover:bg-dark-800 transition-colors">
                            <ShoppingBag className="w-5 h-5" /> Orders
                        </Link>
                    </nav>
                </div>

                <div className="mt-auto px-6 mb-8 space-y-2">
                    <Link href="/admin/settings" className="flex items-center gap-3 px-4 py-3 rounded-lg text-dark-400 hover:text-white hover:bg-dark-800 transition-colors">
                        <Settings className="w-5 h-5" /> Settings
                    </Link>
                    <Link href="/" className="flex items-center gap-3 px-4 py-3 rounded-lg text-red-400 hover:text-white hover:bg-red-500/20 transition-colors">
                        <LogOut className="w-5 h-5" /> Exit to Site
                    </Link>
                </div>
            </aside>

            {/* Main Content */}
            <main className="flex-1 overflow-y-auto mt-[-80px] p-8 pt-28 h-screen border-l border-gray-200">
                <div className="max-w-6xl mx-auto">
                    {children}
                </div>
            </main>
        </div>
    );
}

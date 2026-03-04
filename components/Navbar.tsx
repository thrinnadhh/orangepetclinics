import Link from 'next/link';
import { ShoppingCart, Menu } from 'lucide-react';

export default function Navbar() {
    return (
        <nav className="fixed w-full z-50 bg-white/80 backdrop-blur-md border-b border-gray-100 transition-all">
            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div className="flex justify-between items-center h-20">
                    <div className="flex items-center">
                        <Link href="/" className="flex items-center gap-2">
                            <span className="text-3xl font-extrabold text-brand-500 tracking-tight">Orange</span>
                            <span className="text-3xl font-bold text-dark-900 tracking-tight">PetLife</span>
                        </Link>
                    </div>

                    <div className="hidden md:flex items-center space-x-8">
                        <Link href="/products" className="text-dark-600 hover:text-brand-500 font-medium transition-colors">Shop</Link>
                        <Link href="/#services" className="text-dark-600 hover:text-brand-500 font-medium transition-colors">Services</Link>
                        <Link href="/#about" className="text-dark-600 hover:text-brand-500 font-medium transition-colors">About</Link>
                        <Link href="/admin" className="text-dark-600 hover:text-brand-500 font-medium transition-colors">Admin</Link>

                        <div className="flex items-center gap-4">
                            <Link href="/cart" className="text-dark-800 hover:text-brand-500 transition-colors relative">
                                <ShoppingCart className="w-6 h-6" />
                                <span className="absolute -top-2 -right-2 bg-brand-500 text-white text-xs font-bold rounded-full w-5 h-5 flex items-center justify-center">0</span>
                            </Link>
                            <Link href="/appointments/book" className="btn-primary py-2 px-6 text-sm">
                                Book Visit
                            </Link>
                        </div>
                    </div>

                    <div className="md:hidden flex items-center gap-4">
                        <Link href="/cart" className="text-dark-800 relative">
                            <ShoppingCart className="w-6 h-6" />
                        </Link>
                        <button className="text-dark-900 p-2">
                            <Menu className="w-6 h-6" />
                        </button>
                    </div>
                </div>
            </div>
        </nav>
    );
}

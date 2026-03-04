import Link from 'next/link';
import { Facebook, Twitter, Instagram, Mail, Phone, MapPin } from 'lucide-react';

export default function Footer() {
    return (
        <footer className="bg-dark-900 text-white pt-16 pb-8">
            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div className="grid grid-cols-1 md:grid-cols-4 gap-12 mb-12">
                    <div className="col-span-1 md:col-span-1">
                        <Link href="/" className="flex items-center gap-2 mb-6">
                            <span className="text-3xl font-extrabold text-brand-500 tracking-tight">Orange</span>
                            <span className="text-3xl font-bold tracking-tight">PetLife</span>
                        </Link>
                        <p className="text-dark-500 mb-6 font-light leading-relaxed">
                            Elevating pet care with premium products, expert grooming, and top-tier veterinary services. Your pet deserves the best.
                        </p>
                        <div className="flex items-center gap-4">
                            <a href="#" className="w-10 h-10 rounded-full bg-dark-800 flex items-center justify-center text-dark-500 hover:bg-brand-500 hover:text-white transition-all"><Facebook className="w-5 h-5" /></a>
                            <a href="#" className="w-10 h-10 rounded-full bg-dark-800 flex items-center justify-center text-dark-500 hover:bg-brand-500 hover:text-white transition-all"><Twitter className="w-5 h-5" /></a>
                            <a href="#" className="w-10 h-10 rounded-full bg-dark-800 flex items-center justify-center text-dark-500 hover:bg-brand-500 hover:text-white transition-all"><Instagram className="w-5 h-5" /></a>
                        </div>
                    </div>

                    <div>
                        <h3 className="text-lg font-bold mb-6 text-white">Quick Links</h3>
                        <ul className="space-y-4 text-dark-500 font-light">
                            <li><Link href="/" className="hover:text-brand-500 transition-colors">Home</Link></li>
                            <li><Link href="/products" className="hover:text-brand-500 transition-colors">Shop</Link></li>
                            <li><Link href="/#services" className="hover:text-brand-500 transition-colors">Services</Link></li>
                            <li><Link href="/appointments/book" className="hover:text-brand-500 transition-colors">Book Appointment</Link></li>
                        </ul>
                    </div>

                    <div>
                        <h3 className="text-lg font-bold mb-6 text-white">Services</h3>
                        <ul className="space-y-4 text-dark-500 font-light">
                            <li><Link href="/#services" className="hover:text-brand-500 transition-colors">Pet Grooming</Link></li>
                            <li><Link href="/#services" className="hover:text-brand-500 transition-colors">Veterinary Consult</Link></li>
                            <li><Link href="/#services" className="hover:text-brand-500 transition-colors">Pet Daycare</Link></li>
                            <li><Link href="/#services" className="hover:text-brand-500 transition-colors">Training Sessions</Link></li>
                        </ul>
                    </div>

                    <div>
                        <h3 className="text-lg font-bold mb-6 text-white">Contact Us</h3>
                        <ul className="space-y-4 text-dark-500 font-light">
                            <li className="flex items-center gap-3">
                                <MapPin className="w-5 h-5 text-brand-500" />
                                <span>123 Pet Avenue, NY 10001</span>
                            </li>
                            <li className="flex items-center gap-3">
                                <Phone className="w-5 h-5 text-brand-500" />
                                <span>+1 (555) 123-4567</span>
                            </li>
                            <li className="flex items-center gap-3">
                                <Mail className="w-5 h-5 text-brand-500" />
                                <span>hello@orangepetlife.com</span>
                            </li>
                        </ul>
                    </div>
                </div>

                <div className="border-t border-dark-800 pt-8 mt-8 flex flex-col md:flex-row items-center justify-between text-dark-500 font-light text-sm">
                    <p>&copy; {new Date().getFullYear()} OrangePetLife. All rights reserved.</p>
                    <div className="flex gap-6 mt-4 md:mt-0">
                        <Link href="#" className="hover:text-brand-500 transition-colors">Privacy Policy</Link>
                        <Link href="#" className="hover:text-brand-500 transition-colors">Terms of Service</Link>
                    </div>
                </div>
            </div>
        </footer>
    );
}

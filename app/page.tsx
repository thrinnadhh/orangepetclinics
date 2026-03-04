"use client";

import Link from 'next/link';
import { ArrowRight, Star, HeartPulse, Scissors, CalendarCheck, ShoppingCart } from 'lucide-react';
import { motion } from 'framer-motion';

export default function HomePage() {
    const fadeInUp = {
        hidden: { opacity: 0, y: 20 },
        visible: { opacity: 1, y: 0 }
    };

    return (
        <div className="flex flex-col min-h-screen">

            {/* Hero Section */}
            <section className="relative overflow-hidden bg-brand-50 pt-20 pb-32">
                <div className="absolute top-0 right-0 -m-32 w-[600px] h-[600px] rounded-full bg-brand-200/50 blur-3xl -z-10 animate-pulse"></div>
                <div className="absolute bottom-0 left-0 -m-32 w-[600px] h-[600px] rounded-full bg-brand-100/50 blur-3xl -z-10"></div>

                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
                    <div className="text-center max-w-4xl mx-auto">
                        <motion.h1
                            initial="hidden" animate="visible" variants={fadeInUp} transition={{ duration: 0.5 }}
                            className="text-5xl md:text-7xl font-extrabold text-dark-900 tracking-tight leading-tight mb-8"
                        >
                            Premium Care for Your <span className="text-brand-500">Perfect Companion</span>
                        </motion.h1>
                        <motion.p
                            initial="hidden" animate="visible" variants={fadeInUp} transition={{ duration: 0.5, delay: 0.2 }}
                            className="text-xl md:text-2xl text-dark-600 mb-10 leading-relaxed font-light"
                        >
                            Discover top-tier pet products, expert grooming, and trusted veterinary services. Everything your furry friend needs in one place.
                        </motion.p>
                        <motion.div
                            initial="hidden" animate="visible" variants={fadeInUp} transition={{ duration: 0.5, delay: 0.4 }}
                            className="flex flex-col sm:flex-row items-center justify-center gap-4"
                        >
                            <Link href="/appointments/book" className="btn-primary flex items-center gap-2 text-lg">
                                Book Appointment <CalendarCheck className="w-5 h-5" />
                            </Link>
                            <Link href="/products" className="btn-secondary flex items-center gap-2 text-lg">
                                Shop Premium Products
                            </Link>
                        </motion.div>
                    </div>
                </div>
            </section>

            {/* Services Section */}
            <section id="services" className="py-24 bg-white">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="text-center max-w-3xl mx-auto mb-16">
                        <h2 className="text-brand-500 font-bold tracking-wide uppercase mb-3">Our Services</h2>
                        <h3 className="text-4xl font-extrabold text-dark-900 mb-6">World-Class Pet Services</h3>
                        <p className="text-lg text-dark-600 font-light">From medical care to luxury grooming, we provide comprehensive services to keep your pets healthy and happy.</p>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
                        <motion.div whileHover={{ y: -10 }} className="p-8 rounded-3xl bg-brand-50 border border-brand-100 hover:shadow-2xl transition-all duration-300">
                            <div className="w-16 h-16 rounded-full bg-brand-500 flex items-center justify-center text-white mb-6">
                                <HeartPulse className="w-8 h-8" />
                            </div>
                            <h4 className="text-2xl font-bold text-dark-900 mb-4">Veterinary Care</h4>
                            <p className="text-dark-600 font-light mb-6">Expert check-ups, vaccinations, and medical consultations with top-rated vets.</p>
                            <Link href="/appointments/book" className="text-brand-600 font-semibold flex items-center gap-2 hover:gap-3 transition-all">
                                Book Session <ArrowRight className="w-4 h-4" />
                            </Link>
                        </motion.div>

                        <motion.div whileHover={{ y: -10 }} className="p-8 rounded-3xl bg-dark-900 text-white shadow-2xl transition-all duration-300 transform md:-translate-y-4">
                            <div className="w-16 h-16 rounded-full bg-dark-800 flex items-center justify-center text-brand-500 mb-6">
                                <Scissors className="w-8 h-8" />
                            </div>
                            <h4 className="text-2xl font-bold mb-4">Luxury Grooming</h4>
                            <p className="text-dark-400 font-light mb-6">Spa days for your pet. Full grooming, bathing, nail trimming, and styling.</p>
                            <Link href="/appointments/book" className="text-brand-400 font-semibold flex items-center gap-2 hover:gap-3 transition-all">
                                Book Session <ArrowRight className="w-4 h-4" />
                            </Link>
                        </motion.div>

                        <motion.div whileHover={{ y: -10 }} className="p-8 rounded-3xl bg-brand-50 border border-brand-100 hover:shadow-2xl transition-all duration-300">
                            <div className="w-16 h-16 rounded-full bg-brand-500 flex items-center justify-center text-white mb-6">
                                <Star className="w-8 h-8" />
                            </div>
                            <h4 className="text-2xl font-bold text-dark-900 mb-4">Pet Training</h4>
                            <p className="text-dark-600 font-light mb-6">Behavioral training and obedience classes to build a strong bond with your pet.</p>
                            <Link href="/appointments/book" className="text-brand-600 font-semibold flex items-center gap-2 hover:gap-3 transition-all">
                                Book Session <ArrowRight className="w-4 h-4" />
                            </Link>
                        </motion.div>
                    </div>
                </div>
            </section>

            {/* Featured Products */}
            <section className="py-24 bg-dark-50">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="flex justify-between items-end mb-12">
                        <div className="max-w-2xl">
                            <h2 className="text-brand-500 font-bold tracking-wide uppercase mb-3">Shop Essentials</h2>
                            <h3 className="text-4xl font-extrabold text-dark-900">Featured Products</h3>
                        </div>
                        <Link href="/products" className="hidden md:flex items-center gap-2 text-brand-600 font-semibold hover:text-brand-700 transition-colors">
                            View All Products <ArrowRight className="w-5 h-5" />
                        </Link>
                    </div>

                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                        {/* Mock Products for Landing Page */}
                        {[1, 2, 3, 4].map((i) => (
                            <motion.div key={i} whileHover={{ y: -5 }} className="bg-white rounded-2xl p-4 shadow-sm hover:shadow-xl transition-all duration-300 border border-gray-100 group cursor-pointer">
                                <div className="aspect-square bg-gray-50 rounded-xl mb-4 overflow-hidden relative">
                                    <div className="absolute inset-0 bg-brand-100/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                        <span className="btn-primary py-2 px-4 shadow-none">Quick View</span>
                                    </div>
                                    {/* Placeholder for Product Image */}
                                    <div className="w-full h-full flex items-center justify-center text-gray-400">
                                        Product Image
                                    </div>
                                </div>
                                <div>
                                    <h4 className="font-bold text-dark-900 mb-1">Premium Dog Food</h4>
                                    <p className="text-sm text-dark-500 mb-3 line-clamp-2">High-quality ingredients for a healthy and active lifestyle.</p>
                                    <div className="flex justify-between items-center">
                                        <span className="text-xl font-extrabold text-brand-500">$49.99</span>
                                        <button className="text-dark-400 hover:text-brand-500 transition-colors">
                                            <ShoppingCart className="w-5 h-5" />
                                        </button>
                                    </div>
                                </div>
                            </motion.div>
                        ))}
                    </div>

                    <div className="mt-8 text-center md:hidden">
                        <Link href="/products" className="btn-secondary inline-flex items-center gap-2">
                            View All Products <ArrowRight className="w-5 h-5" />
                        </Link>
                    </div>
                </div>
            </section>

            {/* Testimonials */}
            <section className="py-24 bg-brand-500 text-white relative overflow-hidden">
                <div className="absolute top-0 left-0 w-full h-full bg-[url('https://www.transparenttextures.com/patterns/cubes.png')] opacity-10"></div>
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
                    <div className="text-center max-w-3xl mx-auto mb-16">
                        <h2 className="font-bold tracking-wide uppercase mb-3 text-brand-100">Testimonials</h2>
                        <h3 className="text-4xl font-extrabold mb-6">Happy Pets, Happy Owners</h3>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
                        {[
                            { text: "OrangePetLife's grooming service is unmatched. Max always comes back looking like a superstar! The staff is incredibly friendly and professional.", author: "Sarah Jenkins" },
                            { text: "The premium food I bought here completely transformed my cat's coat. Plus, the vet consultation was thorough and put my mind at ease.", author: "David Chu" },
                            { text: "Booking appointments is a breeze. It's so convenient to have a one-stop-shop for everything my golden retriever needs.", author: "Emily Roberts" },
                        ].map((testimonial, idx) => (
                            <div key={idx} className="bg-white/10 backdrop-blur-lg p-8 rounded-3xl border border-white/20">
                                <div className="flex gap-1 mb-4 text-brand-200">
                                    <Star className="w-5 h-5 fill-current" />
                                    <Star className="w-5 h-5 fill-current" />
                                    <Star className="w-5 h-5 fill-current" />
                                    <Star className="w-5 h-5 fill-current" />
                                    <Star className="w-5 h-5 fill-current" />
                                </div>
                                <p className="text-lg font-light mb-6 leading-relaxed">&quot;{testimonial.text}&quot;</p>
                                <div className="font-bold">- {testimonial.author}</div>
                            </div>
                        ))}
                    </div>
                </div>
            </section>

        </div>
    );
}

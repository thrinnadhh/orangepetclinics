export const dynamic = 'force-dynamic';
import { ShoppingCart, Filter } from 'lucide-react';
import { Product } from '@prisma/client';

// Using server component fetching directly for better SEO & performance
import { prisma } from '@/lib/prisma';

export const revalidate = 60; // Revalidate every minute

export default async function ProductsPage() {
    const products = await prisma.product.findMany({
        orderBy: { createdAt: 'desc' }
    });

    return (
        <div className="bg-white min-h-screen">
            <div className="bg-brand-50 py-16 border-b border-brand-100">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <h1 className="text-4xl md:text-5xl font-extrabold text-dark-900 mb-4 tracking-tight">
                        Premium Pet <span className="text-brand-500">Shop</span>
                    </h1>
                    <p className="text-lg text-dark-600 max-w-2xl font-light">
                        Nourish and pamper your furry companions with our curated selection of high-quality food, toys, and care essentials.
                    </p>
                </div>
            </div>

            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
                <div className="flex justify-between items-center mb-10">
                    <p className="text-dark-500 font-medium">Showing {products.length} Products</p>
                    <button className="flex items-center gap-2 px-4 py-2 border border-gray-200 rounded-lg hover:border-brand-500 hover:text-brand-500 transition-colors text-dark-600 font-medium">
                        <Filter className="w-4 h-4" /> Filter & Sort
                    </button>
                </div>

                {products.length === 0 ? (
                    <div className="text-center py-20 bg-gray-50 rounded-3xl border border-gray-100">
                        <h3 className="text-2xl font-bold text-dark-900 mb-2">No Products Available Yet</h3>
                        <p className="text-dark-500 font-light text-lg">Check back soon for our premium pet inventory!</p>
                    </div>
                ) : (
                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
                        {products.map((product: Product) => (
                            <div key={product.id} className="bg-white rounded-2xl p-5 shadow-sm hover:shadow-2xl transition-all duration-300 border border-gray-100 group flex flex-col h-full transform hover:-translate-y-2">
                                <div className="aspect-square bg-gray-50 rounded-xl mb-5 overflow-hidden relative">
                                    {product.imageUrl ? (
                                        <img src={product.imageUrl} alt={product.name} className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" />
                                    ) : (
                                        <div className="w-full h-full flex flex-col items-center justify-center text-gray-400 bg-brand-50/50">
                                            <span className="text-brand-200 font-bold text-2xl">OPL</span>
                                        </div>
                                    )}
                                    <div className="absolute top-3 left-3">
                                        {product.stock === 0 && <span className="bg-dark-900 text-white text-xs font-bold px-3 py-1 rounded-full uppercase tracking-wider">Out of Stock</span>}
                                        {product.stock > 0 && product.stock <= 5 && <span className="bg-red-500 text-white text-xs font-bold px-3 py-1 rounded-full uppercase tracking-wider">Low Stock</span>}
                                    </div>
                                </div>

                                <div className="flex-1 flex flex-col">
                                    <h3 className="font-bold text-lg text-dark-900 mb-2 line-clamp-1">{product.name}</h3>
                                    <p className="text-sm text-dark-500 mb-4 line-clamp-2 leading-relaxed flex-1">{product.description}</p>

                                    <div className="flex justify-between items-center mt-auto pt-4 border-t border-gray-50">
                                        <span className="text-2xl font-extrabold text-brand-500">${product.price.toFixed(2)}</span>
                                        <button
                                            disabled={product.stock === 0}
                                            className="w-12 h-12 rounded-full bg-brand-50 flex items-center justify-center text-brand-600 hover:bg-brand-500 hover:text-white transition-colors disabled:opacity-50 disabled:cursor-not-allowed group-hover:shadow-lg"
                                        >
                                            <ShoppingCart className="w-5 h-5" />
                                        </button>
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>
                )}
            </div>
        </div>
    );
}

export const dynamic = 'force-dynamic';
import { prisma } from '@/lib/prisma';
import { Product } from '@prisma/client';
import { Plus, Edit, Trash2, Search } from 'lucide-react';

export default async function AdminProducts() {
    const products = await prisma.product.findMany({
        orderBy: { createdAt: 'desc' }
    });

    return (
        <div>
            <div className="flex justify-between items-center mb-8">
                <div>
                    <h1 className="text-3xl font-extrabold text-dark-900 tracking-tight">Products</h1>
                    <p className="text-dark-500 font-light mt-1">Manage your store inventory and pricing.</p>
                </div>
                <button className="btn-primary flex items-center gap-2 px-6 py-3 shadow-none text-sm">
                    <Plus className="w-4 h-4" /> Add Product
                </button>
            </div>

            <div className="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div className="p-4 border-b border-gray-100 flex items-center gap-4 bg-gray-50/50">
                    <div className="relative flex-1 max-w-md">
                        <Search className="absolute left-3 top-3 w-4 h-4 text-dark-400" />
                        <input
                            type="text"
                            placeholder="Search products..."
                            className="w-full pl-10 pr-4 py-2 bg-white rounded-lg border border-gray-200 outline-none focus:border-brand-500 text-sm"
                        />
                    </div>
                </div>

                <div className="overflow-x-auto">
                    <table className="w-full text-left text-sm text-dark-600">
                        <thead className="bg-gray-50 text-xs uppercase font-bold text-dark-400">
                            <tr>
                                <th className="px-6 py-4">Product Name</th>
                                <th className="px-6 py-4 font-bold">Price</th>
                                <th className="px-6 py-4">Stock</th>
                                <th className="px-6 py-4">Status</th>
                                <th className="px-6 py-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            {products.length === 0 ? (
                                <tr>
                                    <td colSpan={5} className="px-6 py-12 text-center text-dark-400 font-light">
                                        No products found. Start by adding a new product.
                                    </td>
                                </tr>
                            ) : (
                                products.map((product: Product) => (
                                    <tr key={product.id} className="border-b border-gray-50 hover:bg-gray-50/50 transition-colors">
                                        <td className="px-6 py-4 font-medium text-dark-900">{product.name}</td>
                                        <td className="px-6 py-4 font-bold text-brand-600">${product.price.toFixed(2)}</td>
                                        <td className="px-6 py-4">{product.stock}</td>
                                        <td className="px-6 py-4">
                                            {product.stock > 0 ? (
                                                <span className="bg-green-100 text-green-600 px-3 py-1 rounded-full text-xs font-bold uppercase">In Stock</span>
                                            ) : (
                                                <span className="bg-red-100 text-red-600 px-3 py-1 rounded-full text-xs font-bold uppercase">Out of Stock</span>
                                            )}
                                        </td>
                                        <td className="px-6 py-4 text-right flex justify-end gap-3">
                                            <button className="text-dark-400 hover:text-brand-500 transition-colors"><Edit className="w-4 h-4" /></button>
                                            <button className="text-dark-400 hover:text-red-500 transition-colors"><Trash2 className="w-4 h-4" /></button>
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    );
}

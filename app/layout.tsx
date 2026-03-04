import type { Metadata } from "next";
import { Outfit } from "next/font/google";
import "./globals.css";
import Navbar from "@/components/Navbar";
import Footer from "@/components/Footer";

const outfit = Outfit({ subsets: ["latin"], variable: "--font-outfit" });

export const metadata: Metadata = {
    title: "OrangePetLife | Premium Pet Care & Services",
    description: "Your ultimate destination for premium pet products, grooming, and veterinary services.",
};

export default function RootLayout({
    children,
}: Readonly<{
    children: React.ReactNode;
}>) {
    return (
        <html lang="en" className="scroll-smooth">
            <body className={`${outfit.variable} font-sans bg-white text-dark-900 antialiased`}>
                <Navbar />
                <main className="min-h-screen pt-20">
                    {children}
                </main>
                <Footer />
            </body>
        </html>
    );
}

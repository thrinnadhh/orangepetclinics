"use client";

import { useState } from 'react';
import { Calendar, Scissors, HeartPulse, Star, CheckCircle, ArrowRight } from 'lucide-react';
import { motion } from 'framer-motion';

const services = [
    { id: '1', name: 'Luxury Grooming', duration: '60 mins', price: '$85', icon: Scissors, color: 'bg-dark-900 text-brand-500' },
    { id: '2', name: 'Veterinary Checkup', duration: '30 mins', price: '$120', icon: HeartPulse, color: 'bg-brand-500 text-white' },
    { id: '3', name: 'Pet Training Session', duration: '45 mins', price: '$90', icon: Star, color: 'bg-brand-100 text-brand-600' },
];

const timeSlots = ["09:00 AM", "10:30 AM", "01:00 PM", "03:30 PM", "05:00 PM"];

export default function BookAppointmentPage() {
    const [selectedService, setSelectedService] = useState<string | null>(null);
    const [selectedDate, setSelectedDate] = useState<string>('');
    const [selectedTime, setSelectedTime] = useState<string | null>(null);
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [isSuccess, setIsSuccess] = useState(false);

    const handleBooking = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!selectedService || !selectedDate || !selectedTime) return;

        setIsSubmitting(true);

        // Simulate API Call for booking
        setTimeout(() => {
            setIsSubmitting(false);
            setIsSuccess(true);
        }, 1500);
    };

    if (isSuccess) {
        return (
            <div className="min-h-[80vh] flex flex-col items-center justify-center bg-brand-50 px-4">
                <motion.div
                    initial={{ scale: 0.8, opacity: 0 }}
                    animate={{ scale: 1, opacity: 1 }}
                    className="bg-white p-10 rounded-3xl shadow-2xl text-center max-w-md w-full border border-gray-100"
                >
                    <div className="w-20 h-20 rounded-full bg-green-100 text-green-500 flex items-center justify-center mx-auto mb-6">
                        <CheckCircle className="w-10 h-10" />
                    </div>
                    <h2 className="text-3xl font-extrabold text-dark-900 mb-2">Booking Confirmed!</h2>
                    <p className="text-dark-500 mb-8 font-light">We&apos;ve sent the details to your email. We look forward to seeing you and your pet.</p>
                    <button
                        onClick={() => window.location.href = '/'}
                        className="w-full btn-primary"
                    >
                        Return to Home
                    </button>
                </motion.div>
            </div>
        );
    }

    return (
        <div className="bg-white min-h-screen py-24">
            <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

                <div className="text-center mb-16">
                    <h1 className="text-4xl md:text-5xl font-extrabold text-dark-900 tracking-tight mb-4">Book a <span className="text-brand-500">Service</span></h1>
                    <p className="text-lg text-dark-600 font-light">Select a service, choose a convenient time, and we&apos;ll take care of the rest.</p>
                </div>

                <form onSubmit={handleBooking} className="bg-white rounded-3xl p-8 border border-gray-100 shadow-2xl shadow-gray-200/50">

                    {/* Step 1: Service */}
                    <div className="mb-12">
                        <h3 className="text-xl font-bold text-dark-900 mb-6 flex items-center gap-2">
                            <span className="w-8 h-8 rounded-full bg-brand-500 text-white flex items-center justify-center text-sm">1</span> Select Service
                        </h3>
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                            {services.map((svc) => (
                                <div
                                    key={svc.id}
                                    onClick={() => setSelectedService(svc.id)}
                                    className={`cursor-pointer rounded-2xl p-6 border-2 transition-all duration-300 flex flex-col items-center text-center ${selectedService === svc.id ? 'border-brand-500 bg-brand-50 shadow-md' : 'border-gray-100 hover:border-gray-300'}`}
                                >
                                    <div className={`w-14 h-14 rounded-full flex items-center justify-center mb-4 ${svc.color}`}>
                                        <svc.icon className="w-6 h-6" />
                                    </div>
                                    <h4 className="font-bold text-dark-900 mb-1">{svc.name}</h4>
                                    <p className="text-dark-500 text-sm mb-3 font-light">{svc.duration}</p>
                                    <div className="font-extrabold text-xl text-brand-600 mt-auto">{svc.price}</div>
                                </div>
                            ))}
                        </div>
                    </div>

                    {/* Step 2: Date & Time */}
                    <div className="mb-12 grid grid-cols-1 md:grid-cols-2 gap-12">
                        <div>
                            <h3 className="text-xl font-bold text-dark-900 mb-6 flex items-center gap-2">
                                <span className="w-8 h-8 rounded-full bg-brand-500 text-white flex items-center justify-center text-sm">2</span> Pick a Date
                            </h3>
                            <div className="relative">
                                <Calendar className="absolute left-4 top-4 text-dark-400 w-5 h-5" />
                                <input
                                    type="date"
                                    required
                                    value={selectedDate}
                                    onChange={(e) => setSelectedDate(e.target.value)}
                                    className="w-full pl-12 pr-4 py-4 rounded-xl border border-gray-200 focus:border-brand-500 focus:ring-2 focus:ring-brand-500 outline-none text-dark-900 shadow-sm transition-all text-lg font-medium"
                                />
                            </div>
                        </div>

                        <div>
                            <h3 className="text-xl font-bold text-dark-900 mb-6 flex items-center gap-2">
                                <span className="w-8 h-8 rounded-full bg-brand-500 text-white flex items-center justify-center text-sm">3</span> Select Time
                            </h3>
                            <div className="grid grid-cols-2 lg:grid-cols-3 gap-3">
                                {timeSlots.map((time) => (
                                    <div
                                        key={time}
                                        onClick={() => setSelectedTime(time)}
                                        className={`cursor-pointer py-3 text-center rounded-xl border font-medium transition-all ${selectedTime === time ? 'bg-dark-900 text-white border-dark-900 shadow-md' : 'bg-white text-dark-600 border-gray-200 hover:border-dark-400'}`}
                                    >
                                        {time}
                                    </div>
                                ))}
                            </div>
                        </div>
                    </div>

                    {/* Step 4: Submit */}
                    <div className="pt-8 border-t border-gray-100 flex justify-end">
                        <button
                            type="submit"
                            disabled={!selectedService || !selectedDate || !selectedTime || isSubmitting}
                            className="btn-primary w-full md:w-auto text-lg py-4 px-12 disabled:opacity-50 disabled:cursor-not-allowed flex justify-center items-center gap-3"
                        >
                            {isSubmitting ? 'Confirming...' : 'Confirm Appointment'}
                            {!isSubmitting && <ArrowRight className="w-5 h-5" />}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
}

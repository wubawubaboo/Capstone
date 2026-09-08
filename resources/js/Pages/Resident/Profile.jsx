import React from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import ResidentLayout from '@/Layouts/ResidentLayout';

export default function Profile({ profileUser }) {
    // 1. Grab the explicitly passed user, fallback to global auth if needed
    const { auth } = usePage().props;
    const user = profileUser || auth?.user || {};

    // 2. Safe variable assignments
    const displayName = user.full_name || 'Resident Account';
    const userInitial = displayName.charAt(0).toUpperCase();
    const barangayName = user.barangay?.name || 'Your Barangay';
    
    // Successfully reads from the database
    const userPhone = user.phone_number || 'Not provided';

    return (
        <div className="min-h-screen bg-slate-50 p-4 md:p-8">
            <Head title="My Profile" />
            
            <div className="max-w-3xl mx-auto w-full space-y-6">
                
                {/* Header */}
                <div className="flex items-center justify-between">
                    <h2 className="text-2xl md:text-3xl font-black text-slate-900">My Account</h2>
                </div>

                {/* Profile Card */}
                <div className="bg-white rounded-xl shadow-sm border border-slate-200 p-6 md:p-8">
                    <div className="flex flex-col md:flex-row items-center md:items-start text-center md:text-left gap-6 border-b border-slate-100 pb-8">
                        <div className="w-24 h-24 bg-[#0a2342] text-white rounded-full flex items-center justify-center text-4xl font-black shrink-0 shadow-inner">
                            {userInitial}
                        </div>
                        <div className="flex-1">
                            <h1 className="text-2xl md:text-3xl font-black text-slate-900">{displayName}</h1>
                            <p className="text-slate-500 font-medium mt-1">
                                {barangayName}
                            </p>
                            <div className="mt-3">
                                {user.is_verified ? (
                                    <span className="inline-block px-3 py-1 bg-emerald-100 text-emerald-700 text-xs font-bold rounded-full">✓ VERIFIED RESIDENT</span>
                                ) : (
                                    <span className="inline-block px-3 py-1 bg-amber-100 text-amber-700 text-xs font-bold rounded-full">PENDING VERIFICATION</span>
                                )}
                            </div>
                        </div>
                    </div>

                    {/* Account Details */}
                    <div className="py-8 space-y-6">
                        <h3 className="text-xs font-black uppercase tracking-wider text-slate-400">Contact Information</h3>
                        
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <div className="bg-slate-50 p-4 rounded-lg border border-slate-200">
                                <label className="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Phone Number</label>
                                <p className="text-slate-900 font-medium">{userPhone}</p>
                            </div>
                        </div>
                    </div>

                    {/* Actions */}
                    <div className="pt-6 border-t border-slate-100 flex flex-col sm:flex-row gap-4">
                        <Link 
                            href={route('resident.logout')} 
                            method="post" 
                            as="button"
                            className="w-full sm:w-auto px-8 py-3 bg-red-50 text-red-600 border border-red-200 rounded-lg font-bold text-sm hover:bg-red-600 hover:text-white transition-colors active:scale-95 text-center"
                        >
                            Log Out
                        </Link>
                    </div>
                </div>

            </div>
        </div>
    );
}

Profile.layout = page => <ResidentLayout>{page}</ResidentLayout>;
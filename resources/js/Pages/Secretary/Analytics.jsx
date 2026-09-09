import React from 'react';
import { Head } from '@inertiajs/react';
import SecretaryLayout from '@/Layouts/SecretaryLayout';
import { MapContainer, TileLayer } from 'react-leaflet';
import { HeatmapLayer } from 'react-leaflet-heatmap-layer-v3';
import { Bar, Line } from 'react-chartjs-2';
import 'chart.js/auto'; 
import 'leaflet/dist/leaflet.css'; 

export default function Analytics({ mapLocation, heatmapData, incidentTrends, documentVolumes, serviceStats, mediationStats }) {
    
    const incidentData = {
        labels: incidentTrends.map(item => item.incident_type),
        datasets: [{
            label: 'Total Incidents Reported',
            data: incidentTrends.map(item => item.count),
            backgroundColor: '#ef4444', 
            borderRadius: 4,
        }],
    };

    const documentData = {
        labels: documentVolumes.map(item => item.name),
        datasets: [{
            label: 'Certifications Requested',
            data: documentVolumes.map(item => item.count),
            backgroundColor: '#6366f1', 
            borderRadius: 4,
        }],
    };

    const serviceData = {
        labels: serviceStats.map(item => item.service_type),
        datasets: [{
            label: 'Dispatched Assets',
            data: serviceStats.map(item => item.count),
            backgroundColor: '#10b981', 
            borderRadius: 4,
        }],
    };

    const mediationData = {
        labels: mediationStats.map(item => item.status),
        datasets: [{
            label: 'Mediation Cases by Status',
            data: mediationStats.map(item => item.count),
            borderColor: '#f59e0b', 
            backgroundColor: 'rgba(245, 158, 11, 0.2)',
            borderWidth: 2,
            fill: true,
            tension: 0.3,
        }],
    };

    return (
        <SecretaryLayout>
            <Head title="Secretary Analytics" />
            
            <div className="p-6 bg-slate-50 min-h-screen">
                <h1 className="text-3xl font-black mb-6 text-slate-800 uppercase tracking-wider">Barangay San Nicolas Analytics</h1>
                
                {/* Fixed OpenStreetMap Heatmap and Latest Incident Card */}
                <div className="bg-white p-6 rounded-xl shadow-sm border border-slate-200 mb-6 flex flex-col md:flex-row gap-6">
                    
                    {/* Latest Incident Card (Dynamic Styling) */}
                    <div className="md:w-1/3 flex flex-col justify-center">
                        <h2 className="text-xl font-bold mb-2 text-slate-800">Latest Incident Location</h2>
                        <p className="text-sm text-slate-600 mb-4">
                            Monitoring the most recent incident or emergency report logged in the system.
                        </p>
                        {mapLocation ? (
                            <div className={`p-4 rounded border-l-4 ${mapLocation.is_critical ? 'bg-red-50 border-red-500' : 'bg-blue-50 border-blue-500'}`}>
                                <p className={`text-xs font-bold uppercase ${mapLocation.is_critical ? 'text-red-700' : 'text-blue-700'}`}>
                                    {mapLocation.is_critical ? '🚨 URGENT SOS ALERT' : 'Report Type'}
                                </p>
                                <p className="font-medium text-slate-900 mb-2">{mapLocation.incident_type}</p>
                                <p className={`text-xs font-bold uppercase ${mapLocation.is_critical ? 'text-red-700' : 'text-blue-700'}`}>
                                    Timestamp
                                </p>
                                <p className="font-medium text-slate-900">{mapLocation.date}</p>
                            </div>
                        ) : (
                            <p className="text-slate-500 italic">No incident coordinates logged.</p>
                        )}
                    </div>

                    {/* Leaflet Map */}
                    <div className="md:w-2/3 h-80 rounded overflow-hidden border-2 border-slate-300 shadow-inner bg-slate-100 z-0">
                        <MapContainer 
                            center={[15.2952, 120.9416]} // Hard-locked to San Nicolas, Gapan City
                            zoom={15} 
                            style={{ height: '100%', width: '100%', zIndex: 0 }}
                            scrollWheelZoom={false}
                        >
                            <HeatmapLayer
                                points={heatmapData}
                                longitudeExtractor={m => m[1]}
                                latitudeExtractor={m => m[0]}
                                intensityExtractor={m => m[2]}
                                radius={25}
                                blur={20}
                                maxZoom={18}
                            />
                            <TileLayer
                                url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
                                attribution="&copy; OpenStreetMap contributors"
                            />
                        </MapContainer>
                    </div>
                </div>

                {/* Interactive Data Charts */}
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div className="bg-white p-6 rounded-xl shadow-sm border border-slate-200">
                        <h2 className="text-lg font-bold mb-4 text-slate-700 uppercase">Incident Categories</h2>
                        <div className="h-64">
                            <Bar data={incidentData} options={{ responsive: true, maintainAspectRatio: false }} />
                        </div>
                    </div>

                    <div className="bg-white p-6 rounded-xl shadow-sm border border-slate-200">
                        <h2 className="text-lg font-bold mb-4 text-slate-700 uppercase">Asset & Service Dispatch</h2>
                        <div className="h-64">
                            <Bar data={serviceData} options={{ indexAxis: 'y', responsive: true, maintainAspectRatio: false }} />
                        </div>
                    </div>
                    
                    <div className="bg-white p-6 rounded-xl shadow-sm border border-slate-200">
                        <h2 className="text-lg font-bold mb-4 text-slate-700 uppercase">Document Transactions</h2>
                        <div className="h-64">
                            <Bar data={documentData} options={{ responsive: true, maintainAspectRatio: false }} />
                        </div>
                    </div>

                    <div className="bg-white p-6 rounded-xl shadow-sm border border-slate-200">
                        <h2 className="text-lg font-bold mb-4 text-slate-700 uppercase">Conciliation Tracking</h2>
                        <div className="h-64">
                            <Line data={mediationData} options={{ responsive: true, maintainAspectRatio: false }} />
                        </div>
                    </div>
                </div>
            </div>
        </SecretaryLayout>
    );
}
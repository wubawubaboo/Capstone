import React, { useEffect } from 'react';
import L from 'leaflet';
import { Circle, MapContainer, Polygon, TileLayer, useMap } from 'react-leaflet';
import { HeatmapLayer } from 'react-leaflet-heatmap-layer-v3';
import 'leaflet/dist/leaflet.css';

const FALLBACK_CENTER = [15.2952, 120.9416];

const HEAT = { 0.25: '#fde68a', 0.5: '#f59e0b', 0.75: '#ea580c', 1: '#b91c1c' };

function FitToData({ points, boundary }) {
    const map = useMap();

    useEffect(() => {
        const coords = [...(boundary ?? []).flat(), ...points.map(([lat, lng]) => [lat, lng])];
        if (coords.length) {
            map.fitBounds(L.latLngBounds(coords), { padding: [24, 24], maxZoom: 17 });
        }
    }, [map, points, boundary]);

    return null;
}

export default function HotspotMap({ points, hotspot, boundary }) {
    return (
        <div className="relative h-96 rounded-lg overflow-hidden border border-slate-200 z-0">
            <MapContainer center={FALLBACK_CENTER} zoom={15} scrollWheelZoom={false} style={{ height: '100%', width: '100%', zIndex: 0 }}>
                <TileLayer url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png" attribution="&copy; OpenStreetMap contributors" />
                {boundary?.length > 0 && (
                    <Polygon positions={boundary} pathOptions={{ color: '#334155', weight: 2, fill: false }} />
                )}
                {points.length > 0 && (
                    <HeatmapLayer
                        points={points}
                        latitudeExtractor={(p) => p[0]}
                        longitudeExtractor={(p) => p[1]}
                        intensityExtractor={(p) => p[2]}
                        radius={25}
                        blur={20}
                        maxZoom={18}
                        gradient={HEAT}
                    />
                )}
                {hotspot && (
                    <Circle center={[hotspot.lat, hotspot.lng]} radius={75} pathOptions={{ color: '#0f172a', weight: 2, fill: false }} />
                )}
                <FitToData points={points} boundary={boundary} />
            </MapContainer>
            {points.length > 0 && (
                <div className="absolute right-2 top-2 z-[500] bg-white/90 rounded-md shadow px-2 py-1.5 text-[11px] text-slate-600 flex items-center gap-2">
                    Fewer
                    <span className="h-2 w-20 rounded-full" style={{ background: `linear-gradient(to right, ${Object.values(HEAT).join(', ')})` }} />
                    More reports
                </div>
            )}
            {points.length === 0 && (
                <div className="absolute inset-x-0 bottom-4 flex justify-center pointer-events-none z-[500]">
                    <p className="bg-white/90 text-sm text-slate-600 px-3 py-1.5 rounded-md shadow">No reports with a location in this period.</p>
                </div>
            )}
        </div>
    );
}

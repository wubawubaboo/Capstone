import React from 'react';
import { Link } from '@inertiajs/react';

export default function PaginationLinks({ paginator }) {
    if (!paginator?.links || paginator.links.length <= 3) return null;

    return (
        <div className="mt-4 flex flex-wrap gap-2">
            {paginator.links.map((link, index) => (
                <Link
                    key={index}
                    href={link.url || '#'}
                    preserveState
                    preserveScroll
                    className={`px-3 py-1 border rounded text-xs font-bold ${link.active ? 'bg-slate-800 text-white' : 'bg-white text-slate-600'} ${!link.url ? 'opacity-40 pointer-events-none' : 'hover:bg-slate-50'}`}
                    dangerouslySetInnerHTML={{ __html: link.label }}
                />
            ))}
        </div>
    );
}

import React, { useEffect, useRef, useState } from 'react';

export default function ResidentAutocomplete({ searchUrl, selectedLabel, onSelect, onClear, placeholder = 'Search by name or phone number...', error }) {
    const [query, setQuery] = useState('');
    const [results, setResults] = useState([]);
    const [isOpen, setIsOpen] = useState(false);
    const [isLoading, setIsLoading] = useState(false);
    const containerRef = useRef(null);
    const debounceRef = useRef(null);

    useEffect(() => {
        function handleClickOutside(e) {
            if (containerRef.current && !containerRef.current.contains(e.target)) {
                setIsOpen(false);
            }
        }
        document.addEventListener('mousedown', handleClickOutside);
        return () => document.removeEventListener('mousedown', handleClickOutside);
    }, []);

    const handleChange = (e) => {
        const value = e.target.value;
        setQuery(value);

        clearTimeout(debounceRef.current);

        if (value.trim().length < 2) {
            setResults([]);
            setIsOpen(false);
            return;
        }

        debounceRef.current = setTimeout(async () => {
            setIsLoading(true);
            try {
                const response = await window.axios.get(searchUrl, { params: { q: value } });
                setResults(response.data);
                setIsOpen(true);
            } catch (e) {
                setResults([]);
            } finally {
                setIsLoading(false);
            }
        }, 300);
    };

    const handleSelect = (resident) => {
        onSelect(resident);
        setQuery('');
        setResults([]);
        setIsOpen(false);
    };

    if (selectedLabel) {
        return (
            <div className="flex items-center justify-between gap-2 border border-slate-300 rounded-md p-2.5 text-sm bg-slate-50">
                <span className="text-slate-800 font-medium">{selectedLabel}</span>
                <button
                    type="button"
                    onClick={onClear}
                    className="text-xs font-bold text-blue-700 hover:underline shrink-0"
                >
                    Change
                </button>
            </div>
        );
    }

    return (
        <div ref={containerRef} className="relative">
            <input
                type="text"
                value={query}
                onChange={handleChange}
                onFocus={() => query.trim().length >= 2 && setIsOpen(true)}
                placeholder={placeholder}
                className="w-full border-slate-300 rounded shadow-sm p-2.5 text-sm focus:ring-blue-600"
            />
            {error && <div className="text-red-500 text-xs mt-1">{error}</div>}

            {isOpen && (
                <div className="absolute z-10 mt-1 w-full bg-white border border-slate-200 rounded-md shadow-lg max-h-56 overflow-y-auto">
                    {isLoading && (
                        <div className="p-3 text-xs text-slate-400 italic">Searching...</div>
                    )}
                    {!isLoading && results.length === 0 && (
                        <div className="p-3 text-xs text-slate-400 italic">No matching residents found.</div>
                    )}
                    {!isLoading && results.map((resident) => (
                        <button
                            key={resident.id}
                            type="button"
                            onClick={() => handleSelect(resident)}
                            className="w-full text-left p-2.5 text-sm hover:bg-blue-50 border-b border-slate-100 last:border-b-0"
                        >
                            <div className="font-semibold text-slate-800">{resident.full_name}</div>
                            <div className="text-xs text-slate-500">
                                {resident.phone_number}
                                {resident.address ? ` · ${resident.address}` : ''}
                            </div>
                        </button>
                    ))}
                </div>
            )}
        </div>
    );
}

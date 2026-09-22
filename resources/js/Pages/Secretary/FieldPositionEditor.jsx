import React, { useEffect, useRef, useState } from 'react';
import { router } from '@inertiajs/react';
import { Stage, Layer, Image as KonvaImage, Rect, Text, Transformer } from 'react-konva';

const DEFAULT_BOX = {
    x: 20,
    y: 20,
    width: 160,
    height: 30,
    font_size: 14,
    font_align: 'left',
    font_weight: 'normal',
};

const MAX_DISPLAY_WIDTH = 820;

export default function FieldPositionEditor({ documentType, fieldCatalog, onClose }) {
    const [image, setImage] = useState(null);
    const [boxes, setBoxes] = useState(documentType.field_positions_json || []);
    const [selectedIndex, setSelectedIndex] = useState(null);
    const [saving, setSaving] = useState(false);

    const shapeRefs = useRef({});
    const transformerRef = useRef(null);

    const naturalWidth = documentType.template_image_width || 800;
    const naturalHeight = documentType.template_image_height || 1000;
    const scale = Math.min(1, MAX_DISPLAY_WIDTH / naturalWidth);

    useEffect(() => {
        const img = new window.Image();
        img.src = route('secretary.document-types.template-file', documentType.id);
        img.onload = () => setImage(img);
    }, [documentType.id]);

    useEffect(() => {
        if (!transformerRef.current) return;
        const node = selectedIndex !== null ? shapeRefs.current[selectedIndex] : null;
        transformerRef.current.nodes(node ? [node] : []);
        transformerRef.current.getLayer()?.batchDraw();
    }, [selectedIndex, boxes.length]);

    const updateBox = (index, changes) => {
        setBoxes((prev) => prev.map((b, i) => (i === index ? { ...b, ...changes } : b)));
    };

    const addBox = () => {
        const firstKey = Object.keys(fieldCatalog)[0] || '';
        setBoxes((prev) => [...prev, { field_key: firstKey, ...DEFAULT_BOX, y: DEFAULT_BOX.y + prev.length * 40 }]);
        setSelectedIndex(boxes.length);
    };

    const removeBox = (index) => {
        setBoxes((prev) => prev.filter((_, i) => i !== index));
        setSelectedIndex(null);
    };

    const handleDragEnd = (index, e) => {
        updateBox(index, { x: e.target.x(), y: e.target.y() });
    };

    const handleTransformEnd = (index, e) => {
        const node = e.target;
        const scaleX = node.scaleX();
        const scaleY = node.scaleY();
        node.scaleX(1);
        node.scaleY(1);
        updateBox(index, {
            x: node.x(),
            y: node.y(),
            width: Math.max(20, node.width() * scaleX),
            height: Math.max(15, node.height() * scaleY),
        });
    };

    const saveLayout = () => {
        setSaving(true);
        router.put(route('secretary.document-types.field-positions', documentType.id), { positions: boxes }, {
            preserveScroll: true,
            onFinish: () => setSaving(false),
        });
    };

    const selectedBox = selectedIndex !== null ? boxes[selectedIndex] : null;

    return (
        <div className="fixed inset-0 flex items-center justify-center bg-slate-900 bg-opacity-70 z-50 p-4">
            <div className="bg-white rounded-xl shadow-2xl w-full max-w-6xl max-h-[92vh] overflow-y-auto border border-slate-200">
                <div className="p-4 border-b flex justify-between items-center bg-[#0a2342] text-white rounded-t-xl">
                    <h3 className="text-lg font-bold">Manage Fields — {documentType.name}</h3>
                    <button onClick={onClose} className="text-white hover:text-gray-200 font-bold text-xl">&times;</button>
                </div>

                <div className="p-6 grid grid-cols-1 lg:grid-cols-4 gap-6">
                    <div className="lg:col-span-3 overflow-auto border border-slate-200 rounded bg-slate-50 p-2">
                        {image ? (
                            <Stage
                                width={naturalWidth * scale}
                                height={naturalHeight * scale}
                                scaleX={scale}
                                scaleY={scale}
                                onMouseDown={(e) => {
                                    if (e.target === e.target.getStage()) setSelectedIndex(null);
                                }}
                            >
                                <Layer>
                                    <KonvaImage image={image} x={0} y={0} width={naturalWidth} height={naturalHeight} />
                                    {boxes.map((box, index) => (
                                        <React.Fragment key={index}>
                                            <Rect
                                                ref={(node) => { shapeRefs.current[index] = node; }}
                                                x={box.x}
                                                y={box.y}
                                                width={box.width}
                                                height={box.height}
                                                fill="rgba(59, 130, 246, 0.15)"
                                                stroke={selectedIndex === index ? '#1d4ed8' : '#3b82f6'}
                                                strokeWidth={selectedIndex === index ? 2 : 1}
                                                draggable
                                                onClick={() => setSelectedIndex(index)}
                                                onTap={() => setSelectedIndex(index)}
                                                onDragEnd={(e) => handleDragEnd(index, e)}
                                                onTransformEnd={(e) => handleTransformEnd(index, e)}
                                            />
                                            <Text
                                                x={box.x}
                                                y={box.y}
                                                width={box.width}
                                                height={box.height}
                                                text={fieldCatalog[box.field_key] || box.field_key}
                                                fontSize={box.font_size}
                                                fontStyle={box.font_weight === 'bold' ? 'bold' : 'normal'}
                                                align={box.font_align}
                                                verticalAlign="middle"
                                                fill="#1d4ed8"
                                                listening={false}
                                            />
                                        </React.Fragment>
                                    ))}
                                    <Transformer
                                        ref={transformerRef}
                                        rotateEnabled={false}
                                        boundBoxFunc={(oldBox, newBox) => {
                                            if (newBox.width < 20 || newBox.height < 15) return oldBox;
                                            return newBox;
                                        }}
                                    />
                                </Layer>
                            </Stage>
                        ) : (
                            <div className="p-10 text-center text-slate-500 text-sm">Loading image…</div>
                        )}
                    </div>

                    <div className="lg:col-span-1 space-y-4">
                        <button
                            onClick={addBox}
                            className="w-full bg-slate-800 text-white py-2 rounded text-sm font-bold hover:bg-slate-900"
                        >
                            + Add Field
                        </button>

                        {selectedBox ? (
                            <div className="border border-slate-200 rounded p-3 space-y-3">
                                <div>
                                    <label className="block text-xs font-bold text-slate-700 mb-1">Field</label>
                                    <select
                                        value={selectedBox.field_key}
                                        onChange={(e) => updateBox(selectedIndex, { field_key: e.target.value })}
                                        className="w-full border-slate-300 rounded text-sm p-1.5"
                                    >
                                        {Object.entries(fieldCatalog).map(([key, label]) => (
                                            <option key={key} value={key}>{label}</option>
                                        ))}
                                    </select>
                                </div>
                                <div>
                                    <label className="block text-xs font-bold text-slate-700 mb-1">Font Size</label>
                                    <input
                                        type="number"
                                        min="6"
                                        max="72"
                                        value={selectedBox.font_size}
                                        onChange={(e) => updateBox(selectedIndex, { font_size: Number(e.target.value) })}
                                        className="w-full border-slate-300 rounded text-sm p-1.5"
                                    />
                                </div>
                                <div>
                                    <label className="block text-xs font-bold text-slate-700 mb-1">Align</label>
                                    <select
                                        value={selectedBox.font_align}
                                        onChange={(e) => updateBox(selectedIndex, { font_align: e.target.value })}
                                        className="w-full border-slate-300 rounded text-sm p-1.5"
                                    >
                                        <option value="left">Left</option>
                                        <option value="center">Center</option>
                                        <option value="right">Right</option>
                                    </select>
                                </div>
                                <div>
                                    <label className="block text-xs font-bold text-slate-700 mb-1">Weight</label>
                                    <select
                                        value={selectedBox.font_weight}
                                        onChange={(e) => updateBox(selectedIndex, { font_weight: e.target.value })}
                                        className="w-full border-slate-300 rounded text-sm p-1.5"
                                    >
                                        <option value="normal">Normal</option>
                                        <option value="bold">Bold</option>
                                    </select>
                                </div>
                                <button
                                    onClick={() => removeBox(selectedIndex)}
                                    className="w-full bg-red-700 text-white py-1.5 rounded text-xs font-bold hover:bg-red-800"
                                >
                                    REMOVE FIELD
                                </button>
                            </div>
                        ) : (
                            <p className="text-xs text-slate-500">Click a box on the image to edit it, or add a new field.</p>
                        )}

                        <div className="border-t border-slate-200 pt-3 text-xs text-slate-500">
                            {boxes.length} field{boxes.length === 1 ? '' : 's'} placed
                        </div>
                    </div>
                </div>

                <div className="p-4 border-t bg-slate-50 flex justify-end gap-3 rounded-b-xl">
                    <button type="button" onClick={onClose} className="bg-slate-200 text-slate-700 px-4 py-2 rounded-md text-sm font-medium hover:bg-slate-300 transition">
                        Cancel
                    </button>
                    <button
                        type="button"
                        onClick={saveLayout}
                        disabled={saving}
                        className="bg-emerald-700 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-emerald-800 transition"
                    >
                        {saving ? 'SAVING...' : 'Save Layout'}
                    </button>
                </div>
            </div>
        </div>
    );
}

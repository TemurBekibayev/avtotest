import React, { useState, useEffect } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import { ArrowLeft, Search, Tag as TagIcon, Eye, Loader2, AlertCircle, ChevronRight, Users, Activity } from 'lucide-react';
import axiosInstance from './api/axios';
import './RoadSigns.css';

const RoadSigns = ({ t }) => {
    const [categories, setCategories] = useState([]);
    const [signs, setSigns] = useState([]);
    const [loadingCategories, setLoadingCategories] = useState(true);
    const [loadingSigns, setLoadingSigns] = useState(false);
    const [error, setError] = useState(null);
    const [selectedCategory, setSelectedCategory] = useState(null);
    const [selectedSign, setSelectedSign] = useState(null);
    const [drawerVisible, setDrawerVisible] = useState(false);
    const [currentLang, setCurrentLang] = useState(localStorage.getItem('language') || 'uz');

    const backendUrl = axiosInstance.defaults.baseURL?.replace('/api', '') || 'http://localhost';

    useEffect(() => {
        const handleStorageChange = () => {
            setCurrentLang(localStorage.getItem('language') || 'uz');
        };
        window.addEventListener('storage', handleStorageChange);
        return () => window.removeEventListener('storage', handleStorageChange);
    }, []);

    const fetchCategories = async () => {
        setLoadingCategories(true);
        setError(null);
        try {
            const response = await axiosInstance.get('/road-sign-types');
            setCategories(response.data);
        } catch (err) {
            console.error("Error fetching road sign categories:", err);
            setError("Kategoriyalarni yuklashda xatolik yuz berdi. Iltimos, qaytadan urinib ko'ring.");
        } finally {
            setLoadingCategories(false);
        }
    };

    const fetchSignsByCategory = async (categoryId) => {
        setLoadingSigns(true);
        setError(null);
        try {
            const response = await axiosInstance.get(`/road-signs?type_id=${categoryId}`);
            setSigns(response.data);
        } catch (err) {
            console.error("Error fetching road signs:", err);
            setError("Belgilarni yuklashda xatolik yuz berdi. Iltimos, qaytadan urinib ko'ring.");
        } finally {
            setLoadingSigns(false);
        }
    };

    useEffect(() => {
        fetchCategories();
    }, []);

    const handleCategoryClick = (category) => {
        setSelectedCategory(category);
        fetchSignsByCategory(category.id);
    };

    const handleBackClick = () => {
        setSelectedCategory(null);
        setSigns([]);
    };

    const handleSignClick = (sign) => {
        setSelectedSign(sign);
        setDrawerVisible(true);
    };

    const getTranslation = (item, field) => {
        if (!item || !item[field]) return '';
        const langValue = item[field][currentLang];
        return langValue || item[field]['uz'] || 'Noma\'lum';
    };

    const getSignDefinition = (sign) => {
        if (!sign || !sign.content || !sign.content[currentLang]) {
            return sign?.content?.['uz'] || [];
        }
        return sign.content[currentLang];
    };

    if (loadingCategories && !selectedCategory) {
        return (
            <div className="flex justify-center items-center h-full">
                <div className="flex flex-col items-center gap-4 text-blue-600">
                    <Loader2 size={40} className="animate-spin" />
                    <span className="font-medium">Kategoriyalar yuklanmoqda...</span>
                </div>
            </div>
        );
    }

    if (error && !selectedCategory) {
        return (
            <div className="p-6">
                <div className="bg-red-50 border border-red-200 text-red-700 px-6 py-4 rounded-xl flex items-start gap-4 shadow-sm">
                    <AlertCircle className="shrink-0 mt-0.5" />
                    <div className="flex-1">
                        <h3 className="font-bold mb-1">Xatolik</h3>
                        <p className="text-sm mb-3">{error}</p>
                        <button
                            className="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors"
                            onClick={fetchCategories}
                        >
                            Qayta urinish
                        </button>
                    </div>
                </div>
            </div>
        );
    }

    return (
        <div className="road-signs-outer-container h-full w-full bg-[#f8fafc] overflow-y-auto overflow-x-hidden custom-scrollbar">
            <div className="road-signs-inner-padding flex flex-col min-h-full" style={{ padding: '40px' }}>
                {/* Header Box - More padding and white space */}
                {!selectedCategory ? (
                    <div className="bg-white rounded-xl shadow-[0_2px_4px_rgba(0,0,0,0.05)] border border-slate-100 mb-10 w-full" style={{ padding: '20px 30px' }}>
                        <h2 className="text-[15px] font-bold text-slate-600 m-0 uppercase tracking-widest ">
                            YO'L BELGILARI
                        </h2>
                    </div>
                ) : (
                    <div className="flex items-center gap-6 mb-10 bg-white rounded-xl border border-slate-100 shadow-sm" style={{ padding: '28px' }}>
                        <button
                            onClick={handleBackClick}
                            className="p-3 hover:bg-slate-50 rounded-xl border border-slate-200 transition-all text-slate-600 shadow-sm group"
                        >
                            <ArrowLeft size={24} className="group-hover:-translate-x-1 transition-transform" />
                        </button>
                        <div>
                            <h2 className="text-2xl font-bold text-slate-800 m-0">
                                {getTranslation(selectedCategory, 'name')}
                            </h2>
                            <p className="text-slate-500 text-sm mt-1.5 font-medium italic">
                                Jami: {signs.length} ta belgi
                            </p>
                        </div>
                    </div>
                )}

                <div className="flex-1" style={{ marginTop: '20px' }}>
                    {!selectedCategory ? (
                        // 1. CATEGORIES VIEW - More gap and vertical padding
                        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-12 pb-16">
                            {categories.map((category) => (
                                <motion.div
                                    key={category.id}
                                    whileHover={{ scale: 1.03 }}
                                    transition={{ type: "spring", stiffness: 300, damping: 20 }}
                                    className="h-full"
                                >
                                    <div
                                        className="h-full bg-white rounded-2xl overflow-hidden border border-slate-200/60 shadow-[0_4px_20px_-1px_rgba(0,0,0,0.05)] flex flex-col hover:shadow-2xl transition-all duration-500"
                                        onClick={() => handleCategoryClick(category)}
                                    >
                                        <div className="h-56 flex items-center justify-center p-10 border-b border-slate-50 bg-white relative">
                                            <div className="absolute inset-0 bg-gradient-to-br from-blue-50/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity" />
                                            {category.image_url ? (
                                                <img
                                                    alt={getTranslation(category, 'name')}
                                                    src={`${backendUrl}${category.image_url}`}
                                                    className="max-h-full max-w-full object-contain relative z-10"
                                                    onError={(e) => { e.target.src = '/logo.png'; }}
                                                />
                                            ) : (
                                                <div className="text-slate-100 opacity-30 relative z-10">
                                                    {category.code === '8' ? <Activity size={80} strokeWidth={1} /> :
                                                        category.code === '9' ? <Users size={80} strokeWidth={1} /> :
                                                            <TagIcon size={80} strokeWidth={1} />}
                                                </div>
                                            )}
                                        </div>
                                        <div className="flex-1 flex flex-col items-center text-center bg-white" style={{ padding: '32px' }}>
                                            <h4 className="text-[14px] font-bold text-slate-700 leading-snug mb-8 px-4">
                                                {category.code}.{getTranslation(category, 'name')}
                                            </h4>
                                            <div className="w-full mt-auto px-2">
                                                <button
                                                    className="w-full bg-[#1b8aff] hover:bg-[#0070f3] text-white text-[13px] font-extrabold rounded-xl shadow-[0_8px_20px_rgba(27,138,255,0.25)] transition-all transform active:scale-95"
                                                    style={{ padding: '14px 0', marginTop: '20px', cursor: 'pointer' }}
                                                >
                                                    To'liq ko'rish
                                                </button>
                                            </div>

                                        </div>
                                    </div>
                                </motion.div>
                            ))}
                        </div>
                    ) : (
                        // 2. SIGNS VIEW
                        <div className="pb-6">
                            {loadingSigns ? (
                                <div className="flex justify-center items-center h-64 text-blue-600 gap-3">
                                    <Loader2 size={32} className="animate-spin" />
                                    <span>Belgilar yuklanmoqda...</span>
                                </div>
                            ) : error ? (
                                <div className="bg-red-50 border border-red-200 text-red-700 px-6 py-4 rounded-xl flex items-start gap-4 shadow-sm">
                                    <AlertCircle className="shrink-0 mt-0.5" />
                                    <div>
                                        <h3 className="font-bold mb-1">Xatolik</h3>
                                        <p className="text-sm">{error}</p>
                                    </div>
                                </div>
                            ) : signs.length === 0 ? (
                                <div className="flex flex-col items-center justify-center my-12 text-slate-400 gap-4">
                                    <TagIcon size={48} className="text-slate-300" />
                                    <p>{getTranslation(selectedCategory, 'name')} kategoriyasida belgilar topilmadi</p>
                                </div>
                            ) : (
                                <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4">
                                    {signs.map((sign) => (
                                        <motion.div
                                            key={sign.id}
                                            initial={{ opacity: 0, scale: 0.9 }}
                                            animate={{ opacity: 1, scale: 1 }}
                                            transition={{ duration: 0.2 }}
                                            whileHover={{ y: -3 }}
                                        >
                                            <div
                                                className="h-full bg-white rounded-xl overflow-hidden border border-slate-200 shadow-sm hover:shadow-md transition-shadow cursor-pointer flex flex-col p-4 group"
                                                onClick={() => handleSignClick(sign)}
                                            >
                                                <div className="h-28 flex items-center justify-center mb-3">
                                                    <img
                                                        src={sign.image?.startsWith('http') ? sign.image : `${backendUrl}${sign.image}`}
                                                        alt={sign.code}
                                                        className="max-h-full max-w-full object-contain filter drop-shadow-sm group-hover:scale-105 transition-transform"
                                                        onError={(e) => { e.target.src = '/logo.png'; }}
                                                    />
                                                </div>
                                                <div className="mt-auto items-center flex flex-col border-t border-slate-50 pt-3">
                                                    <span className="text-xs font-bold text-slate-500 bg-slate-100 px-2 py-1 rounded mb-2">
                                                        {sign.code}
                                                    </span>
                                                    <h4 className="text-sm font-medium text-slate-700 text-center line-clamp-2 leading-tight w-full" title={getTranslation(sign, 'name')}>
                                                        {getTranslation(sign, 'name')}
                                                    </h4>
                                                </div>
                                            </div>
                                        </motion.div>
                                    ))}
                                </div>
                            )}
                        </div>
                    )}
                </div>

                {/* DRAWER FOR SIGN DETAILS OVERLAY */}
                <AnimatePresence>
                    {drawerVisible && selectedSign && (
                        <>
                            <motion.div
                                className="fixed inset-0 bg-slate-900/50 z-[2000]"
                                initial={{ opacity: 0 }}
                                animate={{ opacity: 1 }}
                                exit={{ opacity: 0 }}
                                onClick={() => setDrawerVisible(false)}
                            />
                            <motion.div
                                className="fixed top-0 right-0 bottom-0 w-full max-w-md bg-white z-[2001] shadow-2xl flex flex-col"
                                initial={{ x: '100%' }}
                                animate={{ x: 0 }}
                                exit={{ x: '100%' }}
                                transition={{ type: 'spring', damping: 25, stiffness: 200 }}
                            >
                                <div className="flex items-center justify-between p-4 border-b border-slate-100">
                                    <h3 className="font-bold text-lg text-slate-800">
                                        {selectedSign.code} - Yo'l belgisi
                                    </h3>
                                    <button
                                        className="p-2 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-full transition-colors"
                                        onClick={() => setDrawerVisible(false)}
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M18 6 6 18" /><path d="m6 6 12 12" /></svg>
                                    </button>
                                </div>

                                <div className="flex-1 overflow-auto p-6">
                                    <div className="bg-slate-50 rounded-2xl p-8 flex justify-center items-center mb-6 shadow-inner border border-slate-100">
                                        <img
                                            src={selectedSign.image?.startsWith('http') ? selectedSign.image : `${backendUrl}${selectedSign.image}`}
                                            alt={selectedSign.code}
                                            className="max-w-full h-auto max-h-48 object-contain filter drop-shadow-md"
                                        />
                                    </div>

                                    <h4 className="text-xl font-bold mb-4 text-slate-800 leading-tight">
                                        {getTranslation(selectedSign, 'name')}
                                    </h4>

                                    <div className="text-slate-600 leading-relaxed text-[15px] space-y-4">
                                        {getSignDefinition(selectedSign).map((item, index) => {
                                            if (item.type === "1") {
                                                return (
                                                    <div key={index} className="prose max-w-none text-slate-600 prose-p:mb-2" dangerouslySetInnerHTML={{ __html: item.value }} />
                                                );
                                            } else if (item.type === "2") {
                                                return (
                                                    <div key={index} className="my-4 rounded-xl overflow-hidden shadow-sm border border-slate-200">
                                                        <video controls className="w-full bg-slate-100">
                                                            <source src={`${backendUrl}${item.value}`} type="video/mp4" />
                                                            Sizning brauzeringiz videoni qo'llab-quvvatlamaydi.
                                                        </video>
                                                    </div>
                                                );
                                            } else if (item.type === "3") {
                                                return (
                                                    <div key={index} className="my-4 rounded-xl overflow-hidden shadow-sm border border-slate-200">
                                                        <img src={`${backendUrl}${item.value}`} alt="Qo'shimcha rasm" className="w-full h-auto" />
                                                    </div>
                                                );
                                            }
                                            return null;
                                        })}

                                        {(!getSignDefinition(selectedSign) || getSignDefinition(selectedSign).length === 0) && (
                                            <p className="italic text-slate-400 bg-slate-50 p-4 rounded-xl border border-dashed border-slate-200 text-center">
                                                Ushbu belgi uchun batafsil ma'lumot hozircha kiritilmagan.
                                            </p>
                                        )}
                                    </div>
                                </div>
                            </motion.div>
                        </>
                    )}
                </AnimatePresence>
            </div>
        </div>
    );
};

export default RoadSigns;

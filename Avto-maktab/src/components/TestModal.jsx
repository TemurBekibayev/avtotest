import React, { useState, useEffect, useMemo } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import { X, Clock, ChevronRight, ChevronLeft, CheckCircle2, AlertCircle, PlayCircle, Info, Trophy, Check, Hash } from 'lucide-react';
import api from '../api/axios';
import './TestModal.css';

const TestModal = ({ template, settings, onClose, onFinish }) => {
    const [questions, setQuestions] = useState([]);
    const [loading, setLoading] = useState(true);
    const [currentIndex, setCurrentIndex] = useState(0);
    const [answers, setAnswers] = useState({}); // { questionId: { optionId, isCorrect, clickedAt } }
    const [timeLeft, setTimeLeft] = useState((template.duration_minutes || 25) * 60);
    const [submitting, setSubmitting] = useState(false);
    const [textScale, setTextScale] = useState(1);

    const handleZoomIn = () => setTextScale(prev => Math.min(prev + 0.2, 2.0));
    const handleZoomOut = () => setTextScale(prev => Math.max(prev - 0.2, 0.6));

    const backendUrl = 'https://api.amudaryoavtotest.uz';
    const lang = settings?.language || 'uz';

    useEffect(() => {
        fetchQuestions();
    }, [template.id]);

    useEffect(() => {
        if (timeLeft <= 0) {
            handleSubmit();
            return;
        }
        const timer = setInterval(() => setTimeLeft(prev => prev - 1), 1000);
        return () => clearInterval(timer);
    }, [timeLeft]);

    const fetchQuestions = async () => {
        try {
            let response;
            if (template.id === 'mixed') {
                response = await api.get('/test-questions/random', { params: { limit: 20 } });
            } else {
                response = await api.get(`/test-templates/${template.id}`);
            }

            let data = template.id === 'mixed' ? response.data : (response.data.questions || []);

            // Apply shuffle if needed
            if (settings?.shuffle) {
                data = [...data].sort(() => Math.random() - 0.5);
                data = data.map(q => ({
                    ...q,
                    options: [...q.options].sort(() => Math.random() - 0.5)
                }));
            }

            setQuestions(data);
        } catch (err) {
            console.error('Error fetching questions:', err);
        } finally {
            setLoading(false);
        }
    };

    const handleSelectOption = (optionId) => {
        const q = questions[currentIndex];
        if (answers[q.id] && settings?.instantFeedback) return; // Prevent changing if already answered in instant mode

        const option = q.options.find(o => o.id === optionId);
        const isCorrect = option.is_correct === true || option.is_correct === 1 || option.is_correct === "1";

        setAnswers(prev => ({
            ...prev,
            [q.id]: { optionId, isCorrect, clickedAt: new Date().toISOString() }
        }));

        // In instant feedback mode, we auto-move after a delay ONLY if there's no explanation to read
        if (settings?.instantFeedback && !q.answer) {
            setTimeout(() => {
                if (currentIndex < questions.length - 1) {
                    setCurrentIndex(prev => prev + 1);
                }
            }, 1500);
        }
    };

    const [showResults, setShowResults] = useState(false);
    const [finalScore, setFinalScore] = useState(0);

    const handleSubmit = async () => {
        if (submitting) return;
        setSubmitting(true);

        const correctCount = Object.values(answers).filter(a => a.isCorrect).length;
        const score = Math.round((correctCount / questions.length) * 100) || 0;
        setFinalScore(score);

        try {
            await api.post('/test-results', {
                test_template_id: template.id,
                score: score,
                taken_at: new Date().toISOString()
            });
            setShowResults(true);
        } catch (err) {
            console.error('Error submitting results:', err);
            // Even if API fails, we should show the results to the user locally
            setShowResults(true);
        } finally {
            setSubmitting(false);
        }
    };

    const formatTime = (seconds) => {
        const mins = Math.floor(seconds / 60);
        const secs = seconds % 60;
        return `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
    };

    if (loading) return (
        <div className="test-modal-overlay">
            <div className="flex flex-col items-center justify-center h-full text-slate-400">
                <Clock className="animate-spin mb-4" size={48} />
                <p className="text-xl font-bold">Yuklanmoqda...</p>
            </div>
        </div>
    );

    if (showResults) {
        const correctCount = Object.values(answers).filter(a => a.isCorrect).length;
        const wrongCount = Object.values(answers).filter(a => !a.isCorrect).length;
        const skippedCount = questions.length - Object.keys(answers).length;
        const isPassed = finalScore >= (settings?.passScore || 80); // Using 80 as default pass score

        return (
            <div className="fixed inset-0 z-[9999] bg-slate-900 flex flex-col font-sans overflow-y-auto w-full h-full">
                <div className="min-h-full w-full flex items-center justify-center p-4 lg:p-8">
                    <motion.div
                        className="bg-slate-800 w-full max-w-2xl rounded-3xl p-8 shadow-2xl border border-slate-700 relative overflow-hidden"
                        initial={{ opacity: 0, scale: 0.95, y: 20 }}
                        animate={{ opacity: 1, scale: 1, y: 0 }}
                    >
                        {/* Decorative Background Glows */}
                        <div className={`absolute top-0 left-1/2 -translate-x-1/2 w-full h-[200px] blur-[100px] opacity-20 pointer-events-none ${isPassed ? 'bg-emerald-500' : 'bg-red-500'}`}></div>

                        <div className="text-center relative z-10 mb-10">
                            <div className={`w-24 h-24 mx-auto rounded-full flex items-center justify-center mb-6 shadow-lg ${isPassed ? 'bg-emerald-500/20 text-emerald-400 border-2 border-emerald-500/50' : 'bg-red-500/20 text-red-400 border-2 border-red-500/50'}`}>
                                <Trophy size={48} />
                            </div>
                            <h2 className="text-3xl font-bold text-white mb-2">Imtihon Yakunlandi</h2>
                            <p className={`text-lg font-medium ${isPassed ? 'text-emerald-400' : 'text-red-400'}`}>
                                {isPassed ? "Tabriklaymiz! Siz imtihondan muvaffaqiyatli o'tdingiz." : "Afsuski, siz imtihondan o'ta olmadingiz."}
                            </p>
                        </div>

                        {/* Radial Progress / Score */}
                        <div className="flex justify-center mb-10 relative z-10">
                            <div className="relative w-48 h-48 flex items-center justify-center">
                                {/* SVG for Ring */}
                                <svg className="w-full h-full -rotate-90 transform" viewBox="0 0 100 100">
                                    {/* Track */}
                                    <circle cx="50" cy="50" r="45" fill="none" stroke="rgba(255,255,255,0.05)" strokeWidth="8" />
                                    {/* Progress */}
                                    <circle
                                        cx="50" cy="50" r="45" fill="none"
                                        stroke={isPassed ? "#10b981" : "#ef4444"}
                                        strokeWidth="8" strokeDasharray="283" strokeDashoffset={283 - (283 * finalScore / 100)}
                                        strokeLinecap="round" className="transition-all duration-1000 ease-out"
                                    />
                                </svg>

                                <div className="absolute inset-0 flex flex-col items-center justify-center">
                                    <span className="text-5xl font-black text-white">{finalScore}%</span>
                                    <span className="text-sm font-medium text-slate-400 uppercase tracking-widest mt-1">Natija</span>
                                </div>
                            </div>
                        </div>

                        {/* Breakdown Grid */}
                        <div className="grid grid-cols-3 gap-4 mb-10 relative z-10">
                            <div className="bg-slate-900/50 rounded-2xl p-4 border border-emerald-500/20 flex flex-col items-center justify-center">
                                <Check size={24} className="text-emerald-500 mb-2" />
                                <span className="text-3xl font-bold text-white">{correctCount}</span>
                                <span className="text-xs font-medium text-slate-400 uppercase tracking-wider mt-1">To'g'ri</span>
                            </div>
                            <div className="bg-slate-900/50 rounded-2xl p-4 border border-red-500/20 flex flex-col items-center justify-center">
                                <X size={24} className="text-red-500 mb-2" />
                                <span className="text-3xl font-bold text-white">{wrongCount}</span>
                                <span className="text-xs font-medium text-slate-400 uppercase tracking-wider mt-1">Xato</span>
                            </div>
                            <div className="bg-slate-900/50 rounded-2xl p-4 border border-slate-700 flex flex-col items-center justify-center">
                                <Hash size={24} className="text-slate-500 mb-2" />
                                <span className="text-3xl font-bold text-white">{skippedCount}</span>
                                <span className="text-xs font-medium text-slate-400 uppercase tracking-wider mt-1">Belgilanmagan</span>
                            </div>
                        </div>

                        {/* Action Buttons */}
                        <div className="flex gap-4 relative z-10 relative z-10 p-4">
                            {/* Option to Review (Optional future feature, just close for now) */}
                            <button
                                onClick={() => { if (onFinish) onFinish(); }}
                                className="flex-1 bg-white/5 hover:bg-white/10 border border-white/10 text-white font-bold py-4 rounded-xl transition-all h-auto cursor-pointer"
                            >
                                Asosiy Menu
                            </button>
                        </div>
                    </motion.div>
                </div>
            </div>
        );
    }

    const currentQuestion = questions[currentIndex];
    const translation = currentQuestion?.translations.find(t => t.language === lang) || currentQuestion?.translations[0];
    const userAnswer = answers[currentQuestion?.id];

    return (
        <div className="fixed inset-0 z-[9999] bg-slate-900 flex flex-col font-sans">
            {/* Header */}
            <header className="flex items-center justify-between bg-slate-900 border-b border-slate-800 shrink-0 shadow-sm" style={{ padding: '20px 32px' }}>
                <div className="text-2xl font-black tracking-tighter text-white flex items-center gap-3">
                    <span className="bg-gradient-to-r from-white to-slate-400 bg-clip-text text-transparent">Amudaryo <span className="text-blue-500">AvtoTest</span></span>
                </div>

                <div className="flex items-center gap-3 px-5 py-2.5">
                    <Clock size={22} className="text-blue-400" />
                    <span className="text-xl font-mono font-bold text-white tracking-widest">{formatTime(timeLeft)}</span>
                </div>

                <div className="flex items-center gap-4">
                    <div className="flex items-center bg-slate-800 rounded-lg p-1 border border-slate-700">
                        <button
                            onClick={handleZoomOut}
                            className="p-2 text-slate-400 hover:text-white hover:bg-slate-700 rounded transition-all"
                            title="Kichraytirish"
                        >
                            <span className="text-lg font-bold">A-</span>
                        </button>
                        <div className="w-px h-6 bg-slate-700 mx-1"></div>
                        <button
                            onClick={handleZoomIn}
                            className="p-2 text-slate-400 hover:text-white hover:bg-slate-700 rounded transition-all"
                            title="Kattalashtirish"
                        >
                            <span className="text-lg font-bold">A+</span>
                        </button>
                    </div>

                    <button
                        onClick={handleSubmit}
                        disabled={submitting}
                        className="bg-red-600 hover:bg-red-500 disabled:bg-red-600/50 text-white font-bold transition-all shadow-[0_0_15px_rgba(220,38,38,0.3)] hover:shadow-[0_0_20px_rgba(220,38,38,0.5)] flex items-center gap-2 border border-red-500 hover:-translate-y-0.5"
                        style={{ padding: '10px 24px', borderRadius: '8px' }}
                    >
                        {submitting ? (
                            <Clock className="animate-spin" size={20} />
                        ) : (
                            <CheckCircle2 size={20} />
                        )}
                        IMTIHONNI YAKUNLASH
                    </button>
                </div>
            </header>

            {/* Main Content */}
            <main className="flex-1 flex flex-col overflow-hidden bg-slate-950">
                {/* Top Section: Question Title (Centered) */}
                <div className="w-full bg-slate-900 border-b border-slate-800 p-6 lg:p-8 flex flex-col items-center text-center shadow-lg relative z-10">
                    <div className="inline-flex text-blue-400 font-black px-4 py-1.5 lg:px-6 lg:py-2 text-xs lg:text-sm uppercase tracking-[0.2em] mb-4">
                        {currentIndex + 1}-SAVOL
                    </div>
                    <h2 className="font-black text-white leading-tight max-w-5xl" style={{ fontSize: `calc(clamp(1.125rem, 2.5vw, 1.875rem) * ${textScale})` }}>
                        {translation?.question}
                    </h2>
                </div>

                {/* Bottom Section: Split 50/50 */}
                <div className="flex-1 flex flex-col lg:flex-row overflow-hidden bg-slate-900/20">
                    {/* Left side: Options (50%) */}
                    <div className="w-full lg:w-1/2 h-full flex flex-col p-6 lg:p-12 overflow-y-auto custom-scrollbar border-r border-slate-800/50">
                        <div className="flex flex-col gap-4 max-w-2xl mx-auto w-full">
                            {currentQuestion?.options.map((opt, idx) => {
                                const isSelected = userAnswer?.optionId === opt.id;
                                const optionTranslation = opt.translations?.find(t => t.language === lang)?.option || opt.option;
                                const isCorrectOption = opt.is_correct === true || opt.is_correct === 1 || opt.is_correct === "1";
                                
                                let statusClasses = 'bg-white/5 border-white/10 hover:bg-white/10 hover:border-white/20 text-slate-200';

                                if (isSelected) {
                                    if (settings?.instantFeedback) {
                                        statusClasses = isCorrectOption
                                            ? 'bg-emerald-500/20 border-emerald-500 text-emerald-50 shadow-[0_0_20px_rgba(16,185,129,0.15)] ring-1 ring-emerald-500'
                                            : 'bg-red-500/20 border-red-500 text-red-50 shadow-[0_0_20px_rgba(239,68,68,0.15)] ring-1 ring-red-500';
                                    } else {
                                        statusClasses = 'bg-blue-600/20 border-blue-500 text-blue-50 shadow-[0_0_20px_rgba(59,130,246,0.15)] ring-1 ring-blue-500';
                                    }
                                } else if (settings?.instantFeedback && userAnswer && isCorrectOption) {
                                    statusClasses = 'bg-emerald-500/10 border-emerald-500/50 text-emerald-200 opacity-90';
                                }

                                return (
                                    <button
                                        key={opt.id}
                                        className={`group flex items-center text-left p-4 lg:p-6 rounded-2xl border-2 transition-all duration-300 ${statusClasses} hover:-translate-y-1 active:translate-y-0 shadow-md`}
                                        onClick={() => handleSelectOption(opt.id)}
                                    >
                                        <div className={`w-10 h-10 lg:w-12 lg:h-12 rounded-xl flex items-center justify-center font-black text-base lg:text-xl mr-4 lg:mr-6 shrink-0 shadow-inner transition-all ${isSelected ? (settings?.instantFeedback ? (isCorrectOption ? 'bg-emerald-500 text-white' : 'bg-red-500 text-white') : 'bg-blue-500 text-white') : (settings?.instantFeedback && userAnswer && isCorrectOption ? 'bg-emerald-500 text-white' : 'bg-slate-800 text-slate-400 group-hover:bg-slate-700 border border-white/5')}`}>
                                            {String.fromCharCode(65 + idx)}
                                        </div>
                                        <div className="font-bold leading-snug" style={{ fontSize: `calc(clamp(1rem, 2vw, 1.25rem) * ${textScale})` }}>{optionTranslation}</div>
                                    </button>
                                );
                            })}
                        </div>

                        {settings?.instantFeedback && userAnswer && (
                            <motion.div
                                className={`mt-10 p-8 rounded-3xl flex flex-col gap-6 border-2 shadow-2xl max-w-2xl mx-auto w-full ${userAnswer.isCorrect ? 'bg-emerald-500/10 border-emerald-500/30 text-emerald-400' : 'bg-red-500/10 border-red-500/30 text-red-400'}`}
                                initial={{ opacity: 0, scale: 0.9 }}
                                animate={{ opacity: 1, scale: 1 }}
                            >
                                <div className="flex items-center gap-6">
                                    {userAnswer.isCorrect ? <CheckCircle2 size={48} className="shrink-0" /> : <AlertCircle size={48} className="shrink-0" />}
                                    <div>
                                        <div className="font-black text-2xl uppercase tracking-wider">{userAnswer.isCorrect ? "To'g'ri javob!" : "Noto'g'ri javob!"}</div>
                                        <div className="text-sm opacity-80 mt-1 font-bold uppercase tracking-widest">
                                            {currentQuestion?.answer ? "Izohni o'qing va videoni ko'ring" : "Keyingi savolga o'tilmoqda..."}
                                        </div>
                                    </div>
                                </div>

                                {currentQuestion?.answer && (
                                    <div className="mt-4 pt-6 border-t border-white/10">
                                        <h4 className="font-bold mb-3 flex items-center gap-2 text-lg">
                                            <Info size={20} /> Javob izohi:
                                        </h4>
                                        <p className="text-lg leading-relaxed opacity-90 mb-6 italic">
                                            {currentQuestion.answer.answer_description}
                                        </p>
                                        
                                        {currentQuestion.answer.answer_resource && (
                                            <div className="rounded-2xl overflow-hidden border border-white/10 bg-black shadow-inner">
                                                <video 
                                                    src={currentQuestion.answer.answer_resource.startsWith('http') ? currentQuestion.answer.answer_resource : `${backendUrl}${currentQuestion.answer.answer_resource}`}
                                                    controls
                                                    className="w-full aspect-video"
                                                >
                                                    Sizning brauzeringiz video formatini qo'llab-quvvatlamaydi.
                                                </video>
                                            </div>
                                        )}
                                        
                                        <button 
                                            onClick={() => {
                                                if (currentIndex < questions.length - 1) {
                                                    setCurrentIndex(prev => prev + 1);
                                                }
                                            }}
                                            className="mt-8 w-full py-4 bg-white/10 hover:bg-white/20 rounded-xl font-bold transition-all text-white border border-white/10"
                                        >
                                            Keyingi savolga o'tish
                                        </button>
                                    </div>
                                )}
                            </motion.div>
                        )}
                    </div>

                    {/* Right side: Image (50%) */}
                    <div className="w-full lg:w-1/2 h-full p-6 lg:p-12 flex items-center justify-center bg-slate-900/40 relative">
                        {currentQuestion?.question_file ? (
                            <div className="w-full h-full relative flex items-center justify-center rounded-3xl overflow-hidden bg-black/60 border border-white/5 shadow-[0_30px_60px_rgba(0,0,0,0.6)]">
                                <img
                                    src={currentQuestion.question_file.startsWith('http') ? currentQuestion.question_file : `${backendUrl}${currentQuestion.question_file}`}
                                    alt="Question media"
                                    className="max-w-full max-h-full object-contain drop-shadow-[0_20px_20px_rgba(0,0,0,0.7)] transition-transform duration-700 hover:scale-105"
                                />
                            </div>
                        ) : (
                            <div className="w-full h-full rounded-3xl bg-slate-900/80 border border-slate-800/80 flex flex-col items-center justify-center text-slate-600 shadow-3xl group">
                                <img src="/logo.png" alt="Amudaryo AvtoTest" className="w-[220px] h-[220px] object-contain opacity-30 grayscale group-hover:grayscale-0 group-hover:opacity-60 transition-all duration-1000 hover:scale-110" />
                                <p className="mt-10 font-black text-2xl tracking-[0.3em] text-slate-500 uppercase opacity-40">Amudaryo AvtoTest</p>
                            </div>
                        )}
                    </div>
                </div>
            </main>

            {/* Footer: Pagination */}
            <footer className="bg-slate-900 border-t border-slate-800 shrink-0 overflow-x-auto custom-scrollbar" style={{ padding: '24px 32px' }}>
                <div className="flex items-center justify-center gap-2.5 min-w-max">
                    {questions.map((q, idx) => {
                        const ans = answers[q.id];
                        let stateClass = 'bg-slate-800 text-slate-400 border-slate-700 hover:bg-slate-700 hover:text-white';

                        if (idx === currentIndex) {
                            stateClass = 'bg-blue-600 text-white border-blue-500 ring-2 ring-blue-500/30 ring-offset-2 ring-offset-slate-900 scale-110 font-bold z-10 shadow-lg';
                        } else if (ans) {
                            if (settings?.instantFeedback) {
                                stateClass = ans.isCorrect
                                    ? 'bg-emerald-500/20 text-emerald-400 border-emerald-500/50 hover:bg-emerald-500/30'
                                    : 'bg-red-500/20 text-red-400 border-red-500/50 hover:bg-red-500/30';
                            } else {
                                stateClass = 'bg-blue-500/20 text-blue-400 border-blue-500/50 hover:bg-blue-500/30';
                            }
                        }

                        return (
                            <button
                                key={q.id}
                                className={`w-11 h-11 flex items-center justify-center text-sm rounded-xl border transition-all duration-200 shrink-0 ${stateClass}`}
                                onClick={() => setCurrentIndex(idx)}
                            >
                                {idx + 1}
                            </button>
                        );
                    })}
                </div>
            </footer>
        </div>
    );
};

export default TestModal;

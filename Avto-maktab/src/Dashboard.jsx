import React, { useState, useEffect } from 'react';
import api from './api/axios';
import RoadSigns from './RoadSigns';
import { motion, AnimatePresence } from 'framer-motion';
import {
    LayoutDashboard, Users, BookOpen, Settings, LogOut,
    Bell, Search, ChevronRight, ClipboardList, Calendar,
    ShieldAlert, Award, CreditCard, MapPin, Phone,
    Briefcase, Car, BarChart3, Clock, CheckCircle2,
    Menu, X, Globe, User, Moon, Sun, Maximize,
    ChevronDown, Bookmark, MessageSquare, Cpu, Activity,
    FileText, Navigation, CheckCircle, List, Home, AlertCircle, PlayCircle
} from 'lucide-react';
import './Dashboard.css';
import TestModal from './components/TestModal';

const Dashboard = ({ onLogout, user }) => {
    const student = user?.student;
    const organization = student?.organization;
    const group = student?.group;

    const [activeTab, setActiveTab] = useState('dashboard');
    const [themeColor, setThemeColor] = useState('#2563eb');
    const [sidebarOpen, setSidebarOpen] = useState(true);
    const [testsOpen, setTestsOpen] = useState(false);
    const [langOpen, setLangOpen] = useState(false);
    const [fontSize, setFontSize] = useState(100); // percentage
    const [theme, setTheme] = useState('dark');
    const [currentLang, setCurrentLang] = useState({ code: 'uz-lat', label: "O'zbek (lotin)", flag: "🇺🇿" });
    const [templates, setTemplates] = useState([]);
    const [templatesLoading, setTemplatesLoading] = useState(false);
    const [activeTemplate, setActiveTemplate] = useState(null);
    const [showLangModal, setShowLangModal] = useState(false);
    const [selectedLang, setSelectedLang] = useState('uz');
    const [shuffleVariants, setShuffleVariants] = useState(false);
    const [showAnswersAtEnd, setShowAnswersAtEnd] = useState(true);
    const [results, setResults] = useState([]);
    const [resultsLoading, setResultsLoading] = useState(false);
    const [resultsPage, setResultsPage] = useState(1);
    const [resultsTotalPages, setResultsTotalPages] = useState(1);

    // Barcha testlar state
    const [barchaQuestions, setBarchaQuestions] = useState([]);
    const [barchaPage, setBarchaPage] = useState(1);
    const [barchaTotalPages, setBarchaTotalPages] = useState(1);
    const [barchaSearch, setBarchaSearch] = useState('');
    const [barchaLoading, setBarchaLoading] = useState(false);
    const [fetchError, setFetchError] = useState(null);

    const backendUrl = 'https://api.amudaryoavtotest.uz';

    useEffect(() => {
        fetchTemplates();
        // setShowRoadSigns(false); // This line is commented out as it refers to a removed state
    }, []);

    useEffect(() => {
        // Reset all content visibility states
        // setShowDashboard(false); // This line is commented out as it refers to a removed state
        // setShowResults(false); // This line is commented out as it refers to a removed state
        // setShowAllTests(false); // This line is commented out as it refers to a removed state
        // setShowTemplateTests(false); // This line is commented out as it refers to a removed state
        // setShowTestsList(false); // This line is commented out as it refers to a removed state
        // setShowRoadSigns(false); // This line is commented out as it refers to a removed state

        // Set visibility based on activeTab
        if (activeTab === 'dashboard') {
            // setShowDashboard(true); // This line is commented out as it refers to a removed state
            fetchResults();
        } else if (activeTab === 'shablon-testlar') {
            // setShowTemplateTests(true); // This line is commented out as it refers to a removed state
        } else if (activeTab === 'aralash-testlar') {
            // setShowTestsList(true); // This line is commented out as it refers to a removed state
        } else if (activeTab === 'barcha-testlar') {
            // setShowAllTests(true); // This line is commented out as it refers to a removed state
            // Assuming handleAllTestsClick would set specific states or fetch data
            // For now, just setting showAllTests to true
        } else if (activeTab === 'signs') {
            // setShowRoadSigns(true); // This line is commented out as it refers to a removed state
        }
    }, [activeTab, resultsPage]); // resultsPage is kept here if dashboard tab needs to refetch on page change

    useEffect(() => {
        if (activeTab === 'barcha-testlar') {
            fetchBarchaQuestions();
        }
    }, [activeTab, barchaPage]);

    // Handle search debounce for barcha testlar
    useEffect(() => {
        if (activeTab === 'barcha-testlar') {
            const delayDebounceFn = setTimeout(() => {
                if (barchaPage === 1) {
                    fetchBarchaQuestions();
                } else {
                    setBarchaPage(1); // will trigger the effect above
                }
            }, 500);
            return () => clearTimeout(delayDebounceFn);
        }
    }, [barchaSearch]);

    const fetchBarchaQuestions = async () => {
        setBarchaLoading(true);
        try {
            const response = await api.get(`/test-questions`, {
                params: {
                    page: barchaPage,
                    search: barchaSearch
                }
            });
            setBarchaQuestions(response.data.data || []);
            setBarchaTotalPages(response.data.last_page || 1);
        } catch (err) {
            console.error('Error fetching questions:', err);
        } finally {
            setBarchaLoading(false);
        }
    };

    const fetchTemplates = async () => {
        setTemplatesLoading(true);
        try {
            const response = await api.get('/test-templates');
            setTemplates(response.data);
        } catch (err) {
            console.error('Error fetching templates:', err);
            setFetchError("Ma'lumotlarni yuklashda xatolik yuz berdi. Iltimos, server holatini tekshiring.");
        } finally {
            setTemplatesLoading(false);
        }
    };

    const fetchResults = async () => {
        setResultsLoading(true);
        try {
            const response = await api.get('/test-results', {
                params: { page: resultsPage }
            });
            if (response.data.data) {
                setResults(response.data.data);
                setResultsTotalPages(response.data.last_page);
            } else {
                setResults(response.data);
            }
        } catch (err) {
            console.error('Error fetching results:', err);
            setFetchError("Natijalarni yuklashda xatolik yuz berdi.");
        } finally {
            setResultsLoading(false);
        }
    };

    const languages = [
        { code: 'uz-lat', label: "O'zbek (lotin)", flag: "🇺🇿" },
        { code: 'uz-cyr', label: "Ўзбек (кирилл)", flag: "🇺🇿" },
        { code: 'ru', label: "Русский", flag: "🇷🇺" }
    ];

    const toggleTheme = () => setTheme(theme === 'dark' ? 'light' : 'dark');
    const adjustFontSize = (delta) => setFontSize(prev => Math.min(Math.max(prev + delta, 80), 150));
    const toggleFullscreen = () => {
        if (!document.fullscreenElement) {
            document.documentElement.requestFullscreen();
        } else if (document.exitFullscreen) {
            document.exitFullscreen();
        }
    };

    const userData = {
        name: student?.full_name || user?.name || "Noma'lum",
        group: group?.name || "Noma'lum",
        category: student?.category || "N/A",
        startDate: student?.start_date || "N/A",
        endDate: student?.end_date || "N/A",
        address: student?.address || "Noma'lum",
        phone: student?.phone || "Noma'lum",
        image: student?.image_path || null
    };

    const organizationData = {
        name: organization?.name || "Noma'lum",
        inn: organization?.inn || "N/A",
        account: organization?.account || "N/A",
        address: organization?.address || "Noma'lum",
        phone: organization?.phone || "Noma'lum"
    };

    const stats = [
        // { title: "Darslar", icon: <BookOpen size={20} />, active: true, color: "#1a237e" },
        // { title: "Dars jadvali", icon: <Calendar size={20} />, color: "#0277bd" },
        // { title: "Jarimalar", icon: <ShieldAlert size={20} />, color: "#c62828" },
        // { title: "Testlar", icon: <Award size={20} />, badge: "AB", color: "#f9a825" }
    ];

    const todayLessons = []; // Empty for "No lessons for today" state

    const weeklySchedule = [
        { day: "Seshanba", date: "24/02/2026", time: "09:00 - 11:45" },
        { day: "Payshanba", date: "26/02/2026", time: "09:00 - 11:45" },
        { day: "Shanba", date: "28/02/2026", time: "09:00 - 11:45" }
    ];

    const menuItems = [
        { id: 'dashboard', label: 'Bosh sahifa', icon: <Home size={20} /> },
        { id: 'signs', label: "Yo'l belgilari", icon: <Navigation size={20} /> },
    ];

    const examResults = []; // No data

    return (
        <div
            className={`dashboard-root ${sidebarOpen ? 'sidebar-expanded' : 'sidebar-collapsed'}`}
            style={{ fontSize: `${fontSize}%` }}
        >
            {/* Sidebar */}
            <aside className="dashboard-sidebar">
                <div className="sidebar-header">
                    <div className="logo-area flex items-center gap-3">
                        <span className="logo-text">Amudaryo <span className="accent">AvtoTest</span></span>
                    </div>
                </div>

                <nav className="sidebar-nav">
                    {menuItems.map(item => (
                        <button
                            key={item.id}
                            className={`nav-item ${activeTab === item.id ? 'active' : ''}`}
                            onClick={() => setActiveTab(item.id)}
                        >
                            <span className="nav-icon">{item.icon}</span>
                            <span className="nav-label">{item.label}</span>
                        </button>
                    ))}

                    <div className="nav-divider">Testlar</div>
                    <div className={`nav-dropdown ${testsOpen ? 'open' : ''}`}>
                        <button
                            className={`nav-item dropdown-trigger ${['shablon-testlar', 'aralash-testlar', 'barcha-testlar', 'saqlangan-testlar'].includes(activeTab) ? 'active' : ''}`}
                            onClick={() => setTestsOpen(!testsOpen)}
                        >
                            <span className="nav-icon"><Award size={20} /></span>
                            <span className="nav-label">Testlar</span>
                            <ChevronRight className={`dropdown-arrow ${testsOpen ? 'rotated' : ''}`} size={16} />
                        </button>

                        <AnimatePresence>
                            {testsOpen && (
                                <motion.div
                                    className="sub-nav-menu"
                                    initial={{ height: 0, opacity: 0 }}
                                    animate={{ height: 'auto', opacity: 1 }}
                                    exit={{ height: 0, opacity: 0 }}
                                    transition={{ duration: 0.3 }}
                                >
                                    <button
                                        className={`sub-nav-item ${activeTab === 'shablon-testlar' ? 'active' : ''}`}
                                        onClick={() => setActiveTab('shablon-testlar')}
                                    >
                                        <CheckCircle size={14} /> <span>Shablon testlar (Imtihon)</span>
                                    </button>
                                    <button
                                        className={`sub-nav-item ${activeTab === 'aralash-testlar' ? 'active' : ''}`}
                                        onClick={() => setActiveTab('aralash-testlar')}
                                    >
                                        <Settings size={14} /> <span>Aralash testlar</span>
                                    </button>
                                    <button
                                        className={`sub-nav-item ${activeTab === 'barcha-testlar' ? 'active' : ''}`}
                                        onClick={() => setActiveTab('barcha-testlar')}
                                    >
                                        <List size={14} /> <span>Barcha testlar</span>
                                    </button>
                                    <button
                                        className={`sub-nav-item ${activeTab === 'saqlangan-testlar' ? 'active' : ''}`}
                                        onClick={() => setActiveTab('saqlangan-testlar')}
                                    >
                                        <Bookmark size={14} /> <span>Saqlangan testlar</span>
                                    </button>
                                </motion.div>
                            )}
                        </AnimatePresence>
                    </div>
                </nav>

                <div className="sidebar-footer">
                    <button className="logout-button" onClick={onLogout}>
                        <LogOut size={20} />
                        <span>Chiqish</span>
                    </button>
                </div>
            </aside>

            {/* Main Content */}
            <main className="dashboard-main">
                {/* Header */}
                <header className="top-navbar">
                    <button className="toggle-sidebar" onClick={() => setSidebarOpen(!sidebarOpen)}>
                        {sidebarOpen ? <X size={24} /> : <Menu size={24} />}
                    </button>

                    <div className="navbar-actions">
                        <div className="nav-tool-group">
                            <button className="utility-btn" onClick={() => adjustFontSize(10)} title="A+">A+</button>
                            <button className="utility-btn" onClick={() => adjustFontSize(-10)} title="A-">A-</button>
                            <button className="utility-btn" onClick={toggleTheme} title="Toggle Theme">
                                {theme === 'dark' ? <Moon size={20} /> : <Sun size={20} />}
                            </button>
                            <button className="utility-btn" onClick={toggleFullscreen} title="Screenshot/Fullscreen">
                                <Maximize size={20} />
                            </button>
                        </div>

                        <div className="lang-dropdown-container">
                            <button className="lang-trigger" onClick={() => setLangOpen(!langOpen)}>
                                <span className="flag-icon">{currentLang.flag}</span>
                                <span>{currentLang.label}</span>
                                <ChevronDown size={14} className={langOpen ? 'rotated' : ''} />
                            </button>

                            <AnimatePresence>
                                {langOpen && (
                                    <motion.div
                                        className="lang-menu"
                                        initial={{ opacity: 0, y: 10 }}
                                        animate={{ opacity: 1, y: 0 }}
                                        exit={{ opacity: 0, y: 10 }}
                                    >
                                        {languages.map(lang => (
                                            <button
                                                key={lang.code}
                                                className={`lang-option ${currentLang.code === lang.code ? 'active' : ''}`}
                                                onClick={() => {
                                                    setCurrentLang(lang);
                                                    setLangOpen(false);
                                                }}
                                            >
                                                <span className="flag-icon">{lang.flag}</span>
                                                <span>{lang.label}</span>
                                            </button>
                                        ))}
                                    </motion.div>
                                )}
                            </AnimatePresence>
                        </div>

                        <div className="user-profile">
                            <div className="user-avatar">
                                <User size={20} />
                            </div>
                            <div className="user-info-brief">
                                <span className="user-name">{userData.name.split(' ')[0]}</span>
                                <ChevronDown size={14} />
                            </div>
                        </div>
                    </div>
                </header>

                <div className="content-scroll">
                    {fetchError && (
                        <div className="mx-6 my-4 p-4 bg-red-50 border border-red-200 rounded-xl flex items-center justify-between text-red-800">
                            <div className="flex items-center gap-3">
                                <ShieldAlert size={20} />
                                <span className="font-medium">{fetchError}</span>
                            </div>
                            <button
                                onClick={() => {
                                    setFetchError(null);
                                    fetchTemplates();
                                    if (activeTab === 'dashboard') fetchResults();
                                }}
                                className="px-4 py-1.5 bg-red-600 text-white rounded-lg font-bold hover:bg-red-700 transition"
                            >
                                Qayta urinish
                            </button>
                        </div>
                    )}
                    {activeTab === 'dashboard' && (
                        <div className="dashboard-grid">
                            {/* Profile Section */}
                            <div className="col-left">
                                <motion.div
                                    className="profile-card glass-card"
                                    initial={{ opacity: 0, y: 20 }}
                                    animate={{ opacity: 1, y: 0 }}
                                >
                                    <div className="profile-header">
                                        <div className="photo-placeholder">
                                            <User size={100} />
                                        </div>
                                        <div className="profile-basic-info">
                                            <h2>{userData.name}</h2>
                                            <div className="info-grid">
                                                <div className="info-item">
                                                    <label>Guruh</label>
                                                    <span>{userData.group}</span>
                                                </div>
                                                <div className="info-item">
                                                    <label>Toifa</label>
                                                    <span>{userData.category}</span>
                                                </div>
                                                <div className="info-item">
                                                    <label>Boshlanish sanasi</label>
                                                    <span>{userData.startDate}</span>
                                                </div>
                                                <div className="info-item">
                                                    <label>Tugash sanasi</label>
                                                    <span>{userData.endDate}</span>
                                                </div>
                                            </div>
                                            <p className="address-line"><MapPin size={16} /> {userData.address}</p>
                                        </div>
                                    </div>

                                </motion.div>

                            </div>

                            {/* Right Section: Schedules & Stats */}
                            <div className="col-right">
                                {/* Results Table */}

                                <motion.div
                                    className="exam-section glass-card"
                                    initial={{ opacity: 0, y: 20 }}
                                    animate={{ opacity: 1, y: 0 }}
                                    transition={{ delay: 0.3 }}
                                >
                                    <div className="card-header">
                                        <h3><ShieldAlert size={18} /> Oxirgi imtihon natijalari</h3>
                                        <button className="refresh-btn" onClick={fetchResults}>Yangilash</button>
                                    </div>
                                    <div className="results-table-container">
                                        {resultsLoading && results.length === 0 ? (
                                            <div className="p-8 text-center text-slate-500">Yuklanmoqda...</div>
                                        ) : results.length > 0 ? (
                                            <>
                                                <div className="w-full bg-white rounded-2xl border border-slate-200 overflow-hidden mt-4 shadow-sm">
                                                    <table className="w-full text-left border-collapse">
                                                        <thead>
                                                            <tr className="bg-slate-50 text-slate-500 text-xs uppercase tracking-wider border-b border-slate-200">
                                                                <th className="px-6 py-4 font-semibold">Sana</th>
                                                                <th className="px-6 py-4 font-semibold w-1/3">Test nomi</th>
                                                                <th className="px-6 py-4 font-semibold">Ball</th>
                                                                <th className="px-6 py-4 font-semibold">Natija</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody className="divide-y divide-slate-100">
                                                            {results.map((res) => (
                                                                <tr key={res.id} className="hover:bg-slate-50 transition-colors group">
                                                                    <td className="px-6 py-4">
                                                                        <div className="flex items-center gap-2 text-slate-600 font-medium">
                                                                            <Calendar size={16} className="text-slate-400" />
                                                                            {new Date(res.taken_at).toLocaleDateString()}
                                                                        </div>
                                                                    </td>
                                                                    <td className="px-6 py-4">
                                                                        <div className="flex items-center gap-3">
                                                                            <div className="w-8 h-8 rounded bg-blue-50 flex items-center justify-center border border-blue-100 text-blue-600 shrink-0">
                                                                                <FileText size={16} />
                                                                            </div>
                                                                            <span className="text-slate-900 font-medium group-hover:text-blue-600 transition-colors line-clamp-1">
                                                                                {res.template?.name || 'Aralash Test'}
                                                                            </span>
                                                                        </div>
                                                                    </td>
                                                                    <td className="px-6 py-4 w-48">
                                                                        <div className="flex items-center gap-3">
                                                                            <div className="w-full h-2 bg-slate-100 rounded-full overflow-hidden border border-slate-200">
                                                                                <div
                                                                                    className={`h-full rounded-full transition-all duration-500 ${res.passed ? 'bg-emerald-500' : 'bg-red-500'}`}
                                                                                    style={{ width: `${res.score}%` }}
                                                                                ></div>
                                                                            </div>
                                                                            <span className="font-bold text-slate-700 min-w-[3ch]">{res.score}%</span>
                                                                        </div>
                                                                    </td>
                                                                    <td className="px-6 py-4">
                                                                        {res.passed ? (
                                                                            <div className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-100 font-semibold text-sm">
                                                                                <CheckCircle2 size={14} /> <span>O'tdi</span>
                                                                            </div>
                                                                        ) : (
                                                                            <div className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-red-50 text-red-700 border border-red-100 font-semibold text-sm">
                                                                                <AlertCircle size={14} /> <span>Yiqildi</span>
                                                                            </div>
                                                                        )}
                                                                    </td>
                                                                </tr>
                                                            ))}
                                                        </tbody>
                                                    </table>
                                                </div>
                                                {resultsTotalPages > 1 && (
                                                    <div className="flex justify-center gap-2 mt-4 p-2 border-t border-slate-100">
                                                        <button
                                                            disabled={resultsPage === 1}
                                                            onClick={() => setResultsPage(p => p - 1)}
                                                            className="px-2 py-1 text-xs rounded bg-slate-100 hover:bg-slate-200 disabled:opacity-50"
                                                        >
                                                            Oldingi
                                                        </button>
                                                        <span className="text-xs self-center">{resultsPage} / {resultsTotalPages}</span>
                                                        <button
                                                            disabled={resultsPage === resultsTotalPages}
                                                            onClick={() => setResultsPage(p => p + 1)}
                                                            className="px-2 py-1 text-xs rounded bg-slate-100 hover:bg-slate-200 disabled:opacity-50"
                                                        >
                                                            Keyingi
                                                        </button>
                                                    </div>
                                                )}
                                            </>
                                        ) : (
                                            <div className="p-8 text-center text-slate-500">
                                                <p>Ma'lumotlar mavjud emas</p>
                                            </div>
                                        )}
                                    </div>
                                </motion.div>
                            </div>
                        </div>
                    )}

                    {activeTab === 'shablon-testlar' && (
                        <div className="shablon-container">
                            <div className="shablon-header-card glass-card">
                                <h2>SHABLON TESTLAR (IMTIHON)</h2>
                            </div>
                            <div className="shablon-grid">
                                {templatesLoading ? (
                                    <div className="loading-templates">Yuklanmoqda...</div>
                                ) : templates.length > 0 ? (
                                    templates.map((tpl, i) => {
                                        // Find latest result for this template
                                        const latestResult = results.find(r => r.test_template_id === tpl.id);
                                        const score = latestResult ? latestResult.score : 0;
                                        const correctCount = latestResult ? Math.round((score / 100) * 20) : 0;
                                        // Calculate stroke dash offset: 251.2 is full circle (2 * PI * 40)
                                        const offset = 251.2 - (score / 100) * 251.2;

                                        return (
                                            <motion.div
                                                key={tpl.id}
                                                className={`shablon-card ${activeTemplate?.id === tpl.id ? 'active-trail' : ''}`}
                                                initial={{ opacity: 0, y: 10 }}
                                                animate={{ opacity: 1, y: 0 }}
                                                transition={{ delay: i * 0.05 }}
                                                onClick={() => {
                                                    setActiveTemplate(tpl);
                                                    setShowLangModal(true);
                                                }}
                                            >
                                                <div className="card-top">
                                                    <span className="shablon-title">{tpl.name}</span>
                                                    <div className="bookmark-icon">
                                                        <Bookmark size={18} />
                                                    </div>
                                                </div>

                                                <div className="card-body">
                                                    <div className="progress-ring">
                                                        <div className="ring-content">
                                                            <span className="percent">{score}%</span>
                                                        </div>
                                                        <svg className="ring-svg" viewBox="0 0 90 90">
                                                            <circle cx="45" cy="45" r="40" stroke="#f1f5f9" strokeWidth="6" fill="transparent" />
                                                            <circle
                                                                cx="45" cy="45" r="40"
                                                                stroke="#3b82f6" strokeWidth="6" fill="transparent"
                                                                strokeDasharray="251.2"
                                                                strokeDashoffset={offset}
                                                                strokeLinecap="round"
                                                                style={{ transition: 'stroke-dashoffset 0.5s ease' }}
                                                            />
                                                        </svg>
                                                    </div>
                                                    <div className="test-info">
                                                        <div className="info-row">
                                                            <span className="label">To'g'ri javoblar soni:</span>
                                                            <span className="value">{correctCount}</span>
                                                        </div>
                                                        <div className="info-row">
                                                            <span className="label">Vaqt:</span>
                                                            <span className="value">{tpl.duration_minutes} daqiqa</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </motion.div>
                                        );
                                    })
                                ) : (
                                    <div className="no-templates">Hech qanday test sablonlari topilmadi.</div>
                                )}
                            </div>
                        </div>
                    )}
                    {activeTab === 'aralash-testlar' && (
                        <div className="shablon-container">
                            <div className="shablon-header-card glass-card">
                                <h2>ARALASH TESTLAR</h2>
                            </div>

                            <div className="aralash-stats-grid">
                                <div className="stat-badge-card success">
                                    <div className="icon-label">To'g'ri javoblar</div>
                                    <div className="count text-emerald-600">0</div>
                                </div>
                                <div className="stat-badge-card error">
                                    <div className="icon-label">Noto'g'ri javoblar</div>
                                    <div className="count text-red-600">0</div>
                                </div>
                                <div className="stat-badge-card warning">
                                    <div className="icon-label">Jami ishlangan testlar</div>
                                    <div className="count text-amber-600">0</div>
                                </div>
                                <div className="stat-badge-card info">
                                    <div className="icon-label">Jami savollar</div>
                                    <div className="count text-blue-600">20</div>
                                </div>
                            </div>

                            <div className="glass-card p-6 text-center">
                                <p className="mb-6 text-slate-600 font-medium">Aralash testlarni istalgancha takroran ishlashingiz mumkin. Biroq, so'nggi bajargan test natijangiz saqlanadi va ko'rsatiladi.</p>
                                <div className="flex flex-wrap justify-center modal-mb-6" style={{ gap: '16px' }}>
                                    <button className="px-6 py-2 rounded-lg bg-blue-600 text-white font-bold shadow-md hover:bg-blue-700 transition" style={{ margin: '0 8px', minWidth: '140px' }}>20 ta savol</button>
                                    <button className="px-6 py-2 rounded-lg bg-white border border-slate-300 text-slate-700 font-bold hover:bg-slate-50 transition" style={{ margin: '0 8px', minWidth: '140px' }}>50 ta savol</button>
                                    <button className="px-6 py-2 rounded-lg bg-white border border-slate-300 text-slate-700 font-bold hover:bg-slate-50 transition" style={{ margin: '0 8px', minWidth: '140px' }}>100 ta savol</button>
                                </div>
                                <button
                                    className="px-8 py-3 bg-blue-600 text-white rounded-xl font-bold hover:bg-blue-700 transition" style={{ cursor: 'pointer', padding: '10px 20px' }}
                                    onClick={() => {
                                        // Start mixed test logic
                                        setActiveTemplate({ id: 'mixed', name: 'Aralash Test', duration_minutes: 25 });
                                        setShowLangModal(true);
                                    }}
                                >
                                    ▶ Yangi test boshlash
                                </button>
                            </div>
                        </div>
                    )}

                    {activeTab === 'barcha-testlar' && (
                        <div className="shablon-container">
                            <div className="shablon-header-card glass-card">
                                <h2>BARCHA TESTLAR</h2>
                            </div>

                            <div className="barcha-search-container glass-card mb-6 modal-px-4 modal-py-4 flex modal-gap-16">
                                <Search className="text-slate-400" />
                                <input
                                    type="text"
                                    className="w-full bg-transparent outline-none text-slate-700"
                                    placeholder="Savol matni bo'yicha qidirish..."
                                    value={barchaSearch}
                                    onChange={(e) => setBarchaSearch(e.target.value)}
                                />
                            </div>

                            <div className="barcha-list flex flex-col modal-gap-24">
                                {barchaLoading && barchaQuestions.length === 0 ? (
                                    <div className="loading p-8 text-center text-slate-500">Yuklanmoqda...</div>
                                ) : barchaQuestions.length > 0 ? (
                                    barchaQuestions.map((q, idx) => {
                                        const translation = q.translations?.find(t => t.language === 'uz') || q.translations?.[0];
                                        return (
                                            <div key={q.id} className="glass-card p-6 rounded-2xl border border-slate-200">
                                                <div className="flex justify-between items-start mb-4">
                                                    <span className="font-bold text-blue-600 bg-blue-50 px-3 py-1 rounded-lg" style={{ padding: '5px 10px' }}>{(barchaPage - 1) * 15 + idx + 1}-SAVOL</span>
                                                    <span className="text-slate-400 text-sm">ID: {q.new_question_id || q.id}</span>
                                                </div>

                                                <div className="flex flex-col md:flex-row mb-6 modal-gap-24">
                                                    {q.question_file && (
                                                        <div className="w-full md:w-1/3 bg-slate-100 rounded-xl overflow-hidden flex items-center justify-center">
                                                            <img
                                                                src={q.question_file.startsWith('http') ? q.question_file : `${backendUrl}${q.question_file}`}
                                                                alt="Question"
                                                                className="max-w-full max-h-48 object-contain"
                                                            />
                                                        </div>
                                                    )}
                                                    <div className="flex-1">
                                                        <h3 className="text-lg font-bold text-slate-800 mb-4">{translation?.question}</h3>
                                                        <div className="flex flex-col modal-gap-8">
                                                            {q.options?.map((opt, oIdx) => (
                                                                <div
                                                                    key={opt.id}
                                                                    className={`p-3 rounded-lg border ${opt.is_correct ? 'bg-emerald-50 border-emerald-200 text-emerald-800 font-medium' : 'bg-slate-50 border-slate-200 text-slate-700'}`} style={{ padding: '10px 20px' }}
                                                                >
                                                                    <span className="mr-3 font-bold opacity-60">{String.fromCharCode(65 + oIdx)})</span>
                                                                    {opt.option}
                                                                </div>
                                                            ))}
                                                        </div>
                                                    </div>
                                                </div>

                                                {q.answer && (
                                                    <div className="bg-emerald-50/50 rounded-2xl p-6 border border-emerald-100 mt-6 shadow-inner">
                                                        <h4 className="font-bold text-emerald-800 mb-3 flex items-center gap-2">
                                                            <div className="w-8 h-8 rounded-full bg-emerald-100 flex items-center justify-center text-emerald-600">
                                                                <PlayCircle size={18} />
                                                            </div>
                                                            Javob izohi:
                                                        </h4>
                                                        <p className="text-slate-700 leading-relaxed mb-6 font-medium bg-white/50 p-4 rounded-xl border border-emerald-50">
                                                            {q.answer.answer_description}
                                                        </p>
                                                        {q.answer.answer_resource && (
                                                            <div className="rounded-2xl overflow-hidden border border-slate-200 shadow-lg bg-black aspect-video max-w-2xl mx-auto">
                                                                <video
                                                                    src={q.answer.answer_resource.startsWith('http') ? q.answer.answer_resource : `${backendUrl}${q.answer.answer_resource}`}
                                                                    controls
                                                                    className="w-full h-full outline-none"
                                                                >
                                                                    Sizning brauzeringiz video formatini qo'llab-quvvatlamaydi.
                                                                </video>
                                                            </div>
                                                        )}
                                                    </div>
                                                )}
                                            </div>
                                        );
                                    })
                                ) : (
                                    <div className="p-8 text-center text-slate-500 glass-card">
                                        Hech qanday savol topilmadi.
                                    </div>
                                )}
                            </div>

                            {/* Pagination */}
                            {barchaTotalPages > 1 && (
                                <div className="flex justify-center gap-2 mt-8 mb-4" style={{ marginTop: '20px' }}>
                                    <button
                                        onClick={() => setBarchaPage(p => Math.max(1, p - 1))}
                                        disabled={barchaPage === 1}
                                        className="px-4 py-2 rounded-lg bg-white border border-slate-200 disabled:opacity-50" style={{ cursor: 'pointer', padding: '10px 20px' }}
                                    >
                                        Oldingi
                                    </button>
                                    <span className="px-4 py-2" style={{ padding: '10px 20px' }}>
                                        {barchaPage} / {barchaTotalPages}
                                    </span>
                                    <button
                                        onClick={() => setBarchaPage(p => Math.min(barchaTotalPages, p + 1))}
                                        disabled={barchaPage === barchaTotalPages}
                                        className="px-4 py-2 rounded-lg bg-white border border-slate-200 disabled:opacity-50" style={{ cursor: 'pointer', padding: '10px 20px' }}
                                    >
                                        Keyingi
                                    </button>
                                </div>
                            )}
                        </div>
                    )}

                    {activeTab === 'signs' && (
                        <RoadSigns />
                    )}
                </div>

            </main>

            {/* Language Selection Modal */}
            <AnimatePresence>
                {showLangModal && (
                    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm">
                        <motion.div
                            className="bg-white border border-slate-200 p-8 w-full max-w-lg rounded-2xl shadow-xl relative"
                            initial={{ scale: 0.9, opacity: 0 }}
                            animate={{ scale: 1, opacity: 1 }}
                            exit={{ scale: 0.9, opacity: 0 }}
                        >
                            <button
                                className="absolute top-4 right-4 p-2 text-slate-400 hover:text-slate-700 transition-colors"
                                onClick={() => setShowLangModal(false)}
                            >
                                <X size={20} />
                            </button>

                            <div className="flex flex-col modal-gap-24" style={{ gap: '24px', padding: '20px' }}>
                                <div className="modal-mb-6" style={{ marginBottom: '24px' }}>
                                    <h3 className="text-sm font-bold text-slate-500 mb-1 uppercase tracking-wider">{userData.name}</h3>
                                    <h2 className="text-2xl font-extrabold text-blue-600 underline decoration-2 underline-offset-4 mb-2">TILNI TANLANG!</h2>
                                </div>

                                {/* Language Buttons */}
                                <div className="flex flex-wrap w-full modal-gap-16 modal-mb-6" style={{ gap: '16px', marginBottom: '24px' }}>
                                    <button
                                        className={`flex-1 flex flex-col items-center justify-center rounded-xl border-2 transition-all ${selectedLang === 'uz' ? 'bg-blue-50 border-blue-600 text-blue-700' : 'bg-slate-50 border-slate-200 text-slate-600 hover:bg-slate-100'}`}
                                        onClick={() => setSelectedLang('uz')}
                                        style={{ padding: '16px 8px', minWidth: '100px', flexBasis: '30%' }}
                                    >
                                        <span className="text-3xl mb-2">🇺🇿</span>
                                        <span className="font-bold text-sm">O'zbek</span>
                                    </button>
                                    <button
                                        className={`flex-1 flex flex-col items-center justify-center rounded-xl border-2 transition-all ${selectedLang === 'uz-cyr' ? 'bg-blue-50 border-blue-600 text-blue-700' : 'bg-slate-50 border-slate-200 text-slate-600 hover:bg-slate-100'}`}
                                        onClick={() => setSelectedLang('uz-cyr')}
                                        style={{ padding: '16px 8px', minWidth: '100px', flexBasis: '30%' }}
                                    >
                                        <span className="text-3xl mb-2">🇺🇿</span>
                                        <span className="font-bold text-sm">Кирилл</span>
                                    </button>
                                    <button
                                        className={`flex-1 flex flex-col items-center justify-center rounded-xl border-2 transition-all ${selectedLang === 'ru' ? 'bg-blue-50 border-blue-600 text-blue-700' : 'bg-slate-50 border-slate-200 text-slate-600 hover:bg-slate-100'}`}
                                        onClick={() => setSelectedLang('ru')}
                                        style={{ padding: '16px 8px', minWidth: '100px', flexBasis: '30%' }}
                                    >
                                        <span className="text-3xl mb-2">🇷🇺</span>
                                        <span className="font-bold text-sm">Рус</span>
                                    </button>
                                </div>

                                {/* Settings Toggles */}
                                <div className="flex flex-col modal-gap-16 modal-mb-6" style={{ gap: '16px', marginBottom: '24px' }}>
                                    <div className="flex flex-wrap bg-slate-100 rounded-lg border border-slate-200 modal-gap-8" style={{ padding: '4px', gap: '8px' }}>
                                        <button
                                            className={`flex-1 text-sm rounded-md font-medium transition-colors ${shuffleVariants ? 'bg-white text-blue-600 shadow-sm' : 'text-slate-500 hover:text-slate-700'}`}
                                            onClick={() => setShuffleVariants(true)}
                                            style={{ padding: '8px', minWidth: '160px' }}
                                        >
                                            Variantlar aralashsin
                                        </button>
                                        <button
                                            className={`flex-1 text-sm rounded-md font-medium transition-colors ${!shuffleVariants ? 'bg-white text-blue-600 shadow-sm' : 'text-slate-500 hover:text-slate-700'}`}
                                            onClick={() => setShuffleVariants(false)}
                                            style={{ padding: '8px', minWidth: '160px' }}
                                        >
                                            Variantlar aralashmasin
                                        </button>
                                    </div>
                                    <div className="flex flex-wrap bg-slate-100 rounded-lg border border-slate-200 modal-gap-8" style={{ padding: '4px', gap: '8px' }}>
                                        <button
                                            className={`flex-1 text-sm rounded-md font-medium transition-colors ${!showAnswersAtEnd ? 'bg-white text-blue-600 shadow-sm' : 'text-slate-500 hover:text-slate-700'}`}
                                            onClick={() => setShowAnswersAtEnd(false)}
                                            style={{ padding: '8px', minWidth: '160px' }}
                                        >
                                            Javoblarni ko'rib ketish
                                        </button>
                                        <button
                                            className={`flex-1 text-sm rounded-md font-medium transition-colors ${showAnswersAtEnd ? 'bg-white text-blue-600 shadow-sm' : 'text-slate-500 hover:text-slate-700'}`}
                                            onClick={() => setShowAnswersAtEnd(false)}
                                            style={{ padding: '8px', minWidth: '160px' }}
                                        >
                                            Javoblarni oxirida ko'rish
                                        </button>
                                    </div>
                                </div>

                                {/* Warning Note */}
                                <div className="bg-amber-50 border border-amber-200 rounded-xl text-amber-800 text-sm leading-relaxed modal-mb-6" style={{ padding: '16px', marginBottom: '24px' }}>
                                    {activeTemplate?.id === 'mixed'
                                        ? "20 ta savollarga ajratilgan aralash savollar mavjud bo'lgan biletlar"
                                        : "Ushbu bo'limda barcha fanlardan aralash va tasodifiy shaklda tuzilgan testlar bilan tanishib, testlarga javob berish orqali REAL IMTIHON JARAYONIGA TAYYORGARLIK ko'rishingiz mumkin."}
                                    <div className="font-bold mt-2 text-amber-700">
                                        3 ta dan ortiq xato javob berilganda imtihondan yiqilgan hisoblanadi.
                                    </div>
                                </div>

                                {/* Start Button */}
                                <button
                                    className="w-full bg-emerald-600 hover:bg-emerald-500 text-white font-bold py-5 rounded-xl transition-all shadow-lg hover:shadow-emerald-600/20 active:scale-[0.98]"
                                    onClick={() => {
                                        setTestsOpen(true);
                                        setShowLangModal(false);
                                    }} style={{ padding: '16px' }}
                                >
                                    TESTNI BOSHLASH
                                </button>
                            </div>
                        </motion.div>
                    </div>
                )}
            </AnimatePresence>

            {activeTemplate && !showLangModal && (
                <TestModal
                    template={activeTemplate}
                    settings={{
                        language: selectedLang,
                        shuffle: shuffleVariants,
                        instantFeedback: !showAnswersAtEnd
                    }}
                    onClose={() => setActiveTemplate(null)}
                    onFinish={() => {
                        setActiveTemplate(null);
                        setActiveTab('shablon-testlar');
                        fetchResults();
                    }}
                />
            )}
        </div>
    );
};

export default Dashboard;

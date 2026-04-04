import React, { useState } from 'react';
import api from './api/axios';
import { motion, AnimatePresence } from 'framer-motion';
import { User, Lock, Eye, EyeOff, Globe, ClipboardCheck, ArrowRight } from 'lucide-react';
import './LoginPage.css';

const LoginPage = ({ onLogin }) => {
    const [showPassword, setShowPassword] = useState(false);
    const [lang, setLang] = useState('uz_lat'); // uz_lat, uz_cyr, ru
    const [formData, setFormData] = useState({
        login: '',
        password: '',
        remember: false
    });
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState('');

    const translations = {
        uz_lat: {
            title: "O'zbekiston Respublikasi Ichki ishlar vazirligi Jamoat xavfsizligi departamenti Yo'l harakati xavfsizligi xizmati",
            subtitle: "Avtomototransport vositalari haydovchilarini tayyorlash, qayta tayyorlash va malakasini oshirishga o'rgatuvchi ta'lim muassasalari axborot tizimi",
            loginLabel: "Login",
            passwordLabel: "Parol",
            loginPlaceholder: "Loginni kiriting",
            passwordPlaceholder: "Parolni kiriting",
            rememberMe: "Meni eslab qol",
            submitButton: "Tizimga kirish",
            langSelect: "O'zbek (lotin)"
        },
        uz_cyr: {
            title: "Ўзбекистон Республикаси Ички ишлар вазирлиги Жамоат хавфсизлиги департаменти Йўл ҳаракати хавфсизлиги хизмати",
            subtitle: "Автомототранспорт воситалари ҳайдовчиларини тайёрлаш, қайта тайёрлаш ва малакасини оширишга ўргатувчи таълим муассасалари ахборот тизими",
            loginLabel: "Логин",
            passwordLabel: "Парол",
            loginPlaceholder: "Логинни киритинг",
            passwordPlaceholder: "Паролни киритинг",
            rememberMe: "Мени эслаб қол",
            submitButton: "Тизимга кириш",
            langSelect: "Ўзбек (кирилл)"
        },
        ru: {
            title: "Министерство внутренних дел Республики Узбекистан Департамент общественной безопасности Служба безопасности дорожного движения",
            subtitle: "Информационная система образовательных учреждений по подготовке, переподготовке и повышению квалификации водителей автомототранспортных средств",
            loginLabel: "Логин",
            passwordLabel: "Пароль",
            loginPlaceholder: "Введите логин",
            passwordPlaceholder: "Введите пароль",
            rememberMe: "Запомнить меня",
            submitButton: "Войти в систему",
            langSelect: "Русский"
        }
    };

    const t = translations[lang];

    const handleSubmit = async (e) => {
        e.preventDefault();
        setLoading(true);
        setError('');

        try {
            const response = await api.post('/login', {
                email: formData.login,
                password: formData.password,
                role: 'student'
            });

            const { token, user } = response.data;
            localStorage.setItem('token', token);
            localStorage.setItem('user', JSON.stringify(user));

            onLogin(user);
        } catch (err) {
            setError(err.response?.data?.message || 'Login yoki parol xato!');
        } finally {
            setLoading(false);
        }
    };

    const containerVariants = {
        hidden: { opacity: 0 },
        visible: { opacity: 1, transition: { duration: 1 } }
    };

    const formVariants = {
        hidden: { y: 20, opacity: 0 },
        visible: { y: 0, opacity: 1, transition: { delay: 0.5, duration: 0.8 } }
    };

    return (
        <motion.div
            className="login-container"
            initial="hidden"
            animate="visible"
            variants={containerVariants}
        >
            <div className="background-overlay"></div>

            {/* Top Header Section */}
            <header className="login-header">
                <div className="language-selector">
                    <Globe size={18} />
                    <select value={lang} onChange={(e) => setLang(e.target.value)}>
                        <option value="uz_lat">O'zbek (lotin)</option>
                        <option value="uz_cyr">O'zbek (кирилл)</option>
                        <option value="ru">Русский</option>
                    </select>
                </div>
            </header>

            <main className="login-main">
                <motion.div className="brand-section" variants={formVariants}>
                    <div className="logo-placeholder">
                        <img src="/logo.png" alt="Amudaryo AvtoTest Logo" className="brand-logo-img" style={{ maxWidth: '180px' }} />
                    </div>
                    <h1 className="main-title">{t.title}</h1>
                    <p className="sub-title">{t.subtitle}</p>
                </motion.div>

                <motion.div className="login-card" variants={formVariants}>
                    <form onSubmit={handleSubmit}>
                        <div className="input-group">
                            <label>{t.loginLabel}</label>
                            <div className="input-wrapper">
                                <User className="input-icon" size={20} />
                                <input
                                    type="text"
                                    placeholder={t.loginPlaceholder}
                                    value={formData.login}
                                    onChange={(e) => setFormData({ ...formData, login: e.target.value })}
                                    required
                                />
                            </div>
                        </div>

                        <div className="input-group">
                            <label>{t.passwordLabel}</label>
                            <div className="input-wrapper">
                                <Lock className="input-icon" size={20} />
                                <input
                                    type={showPassword ? "text" : "password"}
                                    placeholder={t.passwordPlaceholder}
                                    value={formData.password}
                                    onChange={(e) => setFormData({ ...formData, password: e.target.value })}
                                    required
                                />
                                <button
                                    type="button"
                                    className="toggle-password"
                                    onClick={() => setShowPassword(!showPassword)}
                                >
                                    {showPassword ? <EyeOff size={20} /> : <Eye size={20} />}
                                </button>
                            </div>
                        </div>

                        <div className="form-actions">
                            <label className="checkbox-container">
                                <input
                                    type="checkbox"
                                    checked={formData.remember}
                                    onChange={(e) => setFormData({ ...formData, remember: e.target.checked })}
                                />
                                <span className="checkmark"></span>
                                {t.rememberMe}
                            </label>
                        </div>

                        {error && <div className="error-message" style={{ color: '#ef4444', marginBottom: '1rem', fontSize: '0.875rem', textAlign: 'center' }}>{error}</div>}

                        <button type="submit" className="login-btn" disabled={loading}>
                            {loading ? "Kirilmoqda..." : t.submitButton}
                            {!loading && <ArrowRight size={20} />}
                        </button>
                    </form>
                </motion.div>
            </main>

            <footer className="login-footer">
                <div className="footer-links">
                    <span>© 2026 Amudaryo AvtoTest</span>
                    <span className="separator">|</span>
                    <span>
                        <a href="https://www.instagram.com/amudaryo_it_akademiyasi">
                            Amudaryo IT Academy Team
                        </a>
                    </span>
                </div>
            </footer>
        </motion.div>
    );
}

export default LoginPage;

import { useState } from 'react';
import { Outlet, Link, useLocation, useNavigate } from 'react-router-dom';
import { useEffect } from 'react';
import {
    LayoutDashboard,
    Users,
    BookOpen,
    Car,
    UserSquare2,
    Menu,
    Bell,
    Search,
    LogOut,
    CreditCard,
    FileText
} from 'lucide-react';
import { Dropdown } from 'antd';
import api from '../api/axios';

const AdminLayout = () => {
    const [sidebarOpen, setSidebarOpen] = useState(true);
    const location = useLocation();
    const navigate = useNavigate();

    // Check auth on load
    useEffect(() => {
        const token = localStorage.getItem('token');
        if (!token) {
            navigate('/login');
        }
    }, [navigate]);

    const handleLogout = async () => {
        try {
            await api.post('/logout'); // Attempt backend logout
        } catch (error) {
            console.error('Logout error:', error);
        } finally {
            localStorage.removeItem('token');
            localStorage.removeItem('user');
            navigate('/login');
        }
    };

    const menuItems = [
        { path: '/dashboard', icon: <LayoutDashboard size={20} />, label: 'Bosh sahifa' },
        { path: '/students', icon: <Users size={20} />, label: "O'quvchilar" },
        { path: '/groups', icon: <BookOpen size={20} />, label: 'Guruhlar va rejalar' },
        { path: '/teachers', icon: <UserSquare2 size={20} />, label: "O'qituvchilar" },
        { path: '/tests', icon: <FileText size={20} />, label: 'Testlar' },
    ];

    const userMenuSettings = {
        items: [
            { key: 'profile', label: 'Profilim' },
            { key: 'settings', label: 'Sozlamalar' },
            { type: 'divider' },
            { key: 'logout', danger: true, label: 'Chiqish', icon: <LogOut size={16} />, onClick: handleLogout },
        ]
    };

    return (
        <div className="flex h-screen overflow-hidden bg-slate-50 font-sans">
            {/* Sidebar */}
            <aside
                className={`bg-[#2a3042] text-slate-300 w-64 flex flex-col transition-all duration-300 ${sidebarOpen ? 'translate-x-0' : '-translate-x-64 absolute'
                    } z-20 h-full`}
            >
                <div className="h-16 flex items-center px-6 bg-[#262b3c] font-bold text-white tracking-widest text-lg">
                    AMUDARYO AVTOTEST <span className="text-blue-500 ml-1">ADMIN</span>
                </div>

                <div className="overflow-y-auto flex-1 py-4">
                    <p className="px-6 text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Boshqaruv</p>
                    <nav className="space-y-1">
                        {menuItems.map((item) => {
                            const isActive = location.pathname.startsWith(item.path);
                            return (
                                <Link
                                    key={item.path}
                                    to={item.path}
                                    className={`flex items-center px-6 py-3 mx-2 rounded-md transition-colors ${isActive
                                        ? 'bg-blue-600 text-white shadow-md'
                                        : 'hover:bg-[#32394e] hover:text-white'
                                        }`}
                                >
                                    <span className="mr-3">{item.icon}</span>
                                    <span className="font-medium">{item.label}</span>
                                </Link>
                            );
                        })}
                    </nav>
                </div>
            </aside>

            {/* Main Content */}
            <div className={`flex flex-col flex-1 overflow-hidden transition-all duration-300 ${!sidebarOpen ? 'ml-0' : ''}`}>

                {/* Top Header */}
                <header className="h-16 bg-white shadow-sm flex items-center justify-between px-4 z-10 w-full">
                    <div className="flex items-center">
                        <button
                            onClick={() => setSidebarOpen(!sidebarOpen)}
                            className="p-2 mr-4 rounded-full hover:bg-slate-100 text-slate-500 transition-colors"
                        >
                            <Menu size={20} />
                        </button>
                        <div className="relative hidden md:block w-64">
                            <span className="absolute inset-y-0 left-0 flex items-center pl-3">
                                <Search size={16} className="text-slate-400" />
                            </span>
                            <input
                                type="text"
                                placeholder="Qidirish..."
                                className="w-full bg-slate-100 border-transparent rounded-full py-2 pl-10 pr-4 text-sm focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none transition-all"
                            />
                        </div>
                    </div>

                    <div className="flex items-center space-x-3">
                        <button className="p-2 rounded-full hover:bg-slate-100 text-slate-500 relative transition-colors">
                            <Bell size={20} />
                            <span className="absolute top-1 right-1.5 w-2 h-2 bg-red-500 rounded-full border border-white"></span>
                        </button>

                        <Dropdown menu={userMenuSettings} trigger={['click']} placement="bottomRight">
                            <button className="flex items-center space-x-2 p-1 pr-2 rounded-full hover:bg-slate-50 border border-slate-100 transition-colors">
                                <div className="w-8 h-8 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center font-bold text-sm uppercase">
                                    {JSON.parse(localStorage.getItem('user') || '{}')?.name?.substring(0, 2) || 'AD'}
                                </div>
                                <span className="text-sm font-medium text-slate-700 hidden sm:block">
                                    {JSON.parse(localStorage.getItem('user') || '{}')?.name || 'Admin User'}
                                </span>
                            </button>
                        </Dropdown>
                    </div>
                </header>

                {/* Page Content */}
                <main className="flex-1 overflow-x-hidden overflow-y-auto bg-slate-50 p-6">
                    <Outlet />
                </main>
            </div>
        </div>
    );
};

export default AdminLayout;

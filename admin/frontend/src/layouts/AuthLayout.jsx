import { Outlet, useNavigate } from 'react-router-dom';
import { useEffect } from 'react';

const AuthLayout = () => {
    const navigate = useNavigate();

    useEffect(() => {
        const token = sessionStorage.getItem('token');
        if (token) {
            navigate('/dashboard');
        }
    }, [navigate]);

    return (
        <div className="min-h-screen bg-slate-100 flex items-center justify-center p-4">
            <div className="max-w-md w-full">
                <Outlet />
            </div>
        </div>
    );
};

export default AuthLayout;

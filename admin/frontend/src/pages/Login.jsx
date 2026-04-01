import { Card, Button, Input, Form, message } from 'antd';
import { Lock, User } from 'lucide-react';
import { useNavigate } from 'react-router-dom';
import api from '../api/axios';
import { useState } from 'react';

const Login = () => {
    const navigate = useNavigate();
    const [loading, setLoading] = useState(false);

    const onFinish = async (values) => {
        try {
            setLoading(true);
            const response = await api.post('/login', {
                email: values.email,
                password: values.password
            });

            // Assuming token is in response.data.token
            localStorage.setItem('token', response.data.token);
            localStorage.setItem('user', JSON.stringify(response.data.user));

            message.success('Muvaffaqiyatli tizimga kirdingiz!');
            navigate('/dashboard');
        } catch (error) {
            console.error('Login error:', error);
            const errorMsg = error.response?.data?.message || 'Tizimga kirishda xatolik yuz berdi. Iltimos qayta urining.';
            message.error(errorMsg);
        } finally {
            setLoading(false);
        }
    };

    return (
        <Card className="shadow-xl border-none rounded-2xl overflow-hidden">
            <div className="text-center mb-8">
                <h1 className="text-2xl font-bold text-slate-800 tracking-tight">AMUDARYO AVTOTEST</h1>
                <p className="text-slate-500 text-sm mt-1">Admin boshqaruv paneli</p>
            </div>

            <Form
                name="login"
                initialValues={{ remember: true }}
                onFinish={onFinish}
                layout="vertical"
                size="large"
            >
                <Form.Item
                    name="email"
                    rules={[
                        { required: true, message: 'Iltimos emailingizni kiriting!' },
                        { type: 'email', message: 'Yaroqli email kiriting!' }
                    ]}
                >
                    <Input
                        prefix={<User size={18} className="text-slate-400 mr-2" />}
                        placeholder="Admin Email manzili"
                        className="rounded-lg"
                    />
                </Form.Item>

                <Form.Item
                    name="password"
                    rules={[{ required: true, message: 'Iltimos parolingizni kiriting!' }]}
                >
                    <Input.Password
                        prefix={<Lock size={18} className="text-slate-400 mr-2" />}
                        placeholder="Parol"
                        className="rounded-lg"
                    />
                </Form.Item>

                <Form.Item>
                    <Button
                        type="primary"
                        htmlType="submit"
                        loading={loading}
                        className="w-full h-11 rounded-lg font-medium text-base mt-2 shadow-blue-500/30 shadow-lg"
                    >
                        Tizimga kirish
                    </Button>
                </Form.Item>
            </Form>
        </Card>
    );
};

export default Login;

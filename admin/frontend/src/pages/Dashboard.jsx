import { useState, useEffect } from 'react';
import { Card, Row, Col, Table, Tag } from 'antd';
import { Users, BookOpen } from 'lucide-react';
import api from '../api/axios';

const Dashboard = () => {
    const [recentTests, setRecentTests] = useState([]);
    const [loading, setLoading] = useState(false);

    useEffect(() => {
        const fetchRecentTests = async () => {
            try {
                setLoading(true);
                const response = await api.get('/test-results');
                // The API returns paginated data for admin
                setRecentTests(response.data.data || []);
            } catch (error) {
                console.error('Failed to fetch recent tests:', error);
            } finally {
                setLoading(false);
            }
        };
        fetchRecentTests();
    }, []);

    const columns = [
        {
            title: 'O\'quvchi',
            dataIndex: 'student',
            key: 'student',
            render: (student) => student?.full_name || 'Noma\'lum'
        },
        {
            title: 'Guruh',
            key: 'group',
            render: (_, record) => record.student?.group?.name || 'N/A'
        },
        {
            title: 'Test shabloni',
            dataIndex: 'template',
            key: 'template',
            render: (template) => template?.name || 'Aralash test'
        },
        {
            title: 'Natija',
            key: 'score',
            render: (_, record) => (
                <div>
                    <span className="font-bold mr-2">{record.score} ball</span>
                    <Tag color={record.passed ? 'green' : 'red'}>
                        {record.passed ? 'O\'tdi' : 'Yiqildi'}
                    </Tag>
                </div>
            )
        },
        {
            title: 'Sana',
            key: 'date',
            render: (_, record) => new Date(record.taken_at || record.created_at).toLocaleString('uz-UZ')
        }
    ];

    return (
        <div className="space-y-6">
            <div>
                <h2 className="text-2xl font-bold text-slate-800">Boshqaruv paneli</h2>
                <p className="text-slate-500">Xush kelibsiz, Admin. Bugungi holat bilan tanishing.</p>
            </div>

            <Row gutter={[24, 24]}>
                <Col xs={24} sm={12} lg={12}>
                    <Card className="rounded-xl shadow-sm border-slate-200">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-slate-500 mb-1">Jami o'quvchilar</p>
                                <h3 className="text-3xl font-bold text-slate-800">1,248</h3>
                            </div>
                            <div className="p-3 bg-blue-50 rounded-lg">
                                <Users className="text-blue-500" size={24} />
                            </div>
                        </div>
                        <div className="mt-4 flex items-center text-sm">
                            <span className="text-emerald-500 font-medium flex items-center">+12%</span>
                            <span className="text-slate-400 ml-2">o'tgan oyga nisbatan</span>
                        </div>
                    </Card>
                </Col>

                <Col xs={24} sm={12} lg={12}>
                    <Card className="rounded-xl shadow-sm border-slate-200">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-slate-500 mb-1">Faol guruhlar</p>
                                <h3 className="text-3xl font-bold text-slate-800">42</h3>
                            </div>
                            <div className="p-3 bg-emerald-50 rounded-lg">
                                <BookOpen className="text-emerald-500" size={24} />
                            </div>
                        </div>
                        <div className="mt-4 flex items-center text-sm">
                            <span className="text-emerald-500 font-medium flex items-center">+3</span>
                            <span className="text-slate-400 ml-2">bu hafta yangi guruhlar</span>
                        </div>
                    </Card>
                </Col>
            </Row>

            {/* Main Content Area */}
            <div className="mt-8">
                <Card title="O'quvchilarning so'ngi ishlagan testlari" className="rounded-xl shadow-sm border-slate-200">
                    <Table 
                        columns={columns} 
                        dataSource={recentTests} 
                        rowKey="id" 
                        loading={loading}
                        pagination={{ pageSize: 10 }}
                        className="custom-table"
                    />
                </Card>
            </div>
        </div>
    );
};

export default Dashboard;

import { useState, useEffect } from 'react';
import { Card, Row, Col, Table, Tag } from 'antd';
import { Users, BookOpen } from 'lucide-react';
import api from '../api/axios';

const Dashboard = () => {
    const [recentTests, setRecentTests] = useState([]);
    const [stats, setStats] = useState({
        total_students: 0,
        active_groups_count: 0,
        student_growth_percentage: 0,
        new_groups_this_week: 0
    });
    const [loading, setLoading] = useState(false);

    useEffect(() => {
        const fetchDashboardData = async () => {
            try {
                setLoading(true);
                // Fetch stats and recent tests in parallel
                const [statsRes, testsRes] = await Promise.all([
                    api.get('/dashboard/stats'),
                    api.get('/test-results')
                ]);
                
                setStats(statsRes.data);
                setRecentTests(testsRes.data.data || []);
            } catch (error) {
                console.error('Failed to fetch dashboard data:', error);
            } finally {
                setLoading(false);
            }
        };
        fetchDashboardData();
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
                                <h3 className="text-3xl font-bold text-slate-800">{stats.total_students.toLocaleString()}</h3>
                            </div>
                            <div className="p-3 bg-blue-50 rounded-lg">
                                <Users className="text-blue-500" size={24} />
                            </div>
                        </div>
                        <div className="mt-4 flex items-center text-sm">
                            <span className="text-emerald-500 font-medium flex items-center">+{stats.student_growth_percentage}%</span>
                            <span className="text-slate-400 ml-2">o'tgan oyga nisbatan</span>
                        </div>
                    </Card>
                </Col>
                <Col xs={24} sm={12} lg={6}>
                    <Card className="dashboard-stat-card">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-slate-500 mb-1">Faol guruhlar</p>
                                <h3 className="text-3xl font-bold text-slate-800">{stats.active_groups_count}</h3>
                            </div>
                            <div className="p-3 bg-emerald-50 rounded-lg">
                                <BookOpen className="text-emerald-500" size={24} />
                            </div>
                        </div>
                        <div className="mt-4 flex items-center text-sm">
                            <span className="text-emerald-500 font-medium flex items-center">+{stats.new_groups_this_week}</span>
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

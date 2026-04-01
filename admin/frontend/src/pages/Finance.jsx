import { useState } from 'react';
import { Card, Table, Tag, Typography, Row, Col, Progress, Button } from 'antd';
import { TrendingUp, CreditCard, AlertCircle, FileText } from 'lucide-react';

const { Title, Text } = Typography;

const MOCK_PAYMENTS = [
    { id: 'INV-1001', student: 'Rasulov Isoxon', group: '19', amount: '1,500,000 UZS', date: '25.02.2026', status: 'To\'langan', type: 'O\'quv puli' },
    { id: 'INV-1002', student: 'Valijonov Sanjarbek', group: '20', amount: '500,000 UZS', date: '20.02.2026', status: 'Kutilmoqda', type: 'O\'quv puli' },
    { id: 'INV-1003', student: 'Akbarov Timur', group: '21', amount: '150,000 UZS', date: '18.02.2026', status: 'To\'lanmagan', type: 'Jarima' },
];

const Finance = () => {
    const columns = [
        { title: 'Invoys', dataIndex: 'id', key: 'id', className: 'font-mono text-slate-500' },
        { title: 'O\'quvchi', dataIndex: 'student', key: 'student', render: text => <span className="font-semibold text-slate-800">{text}</span> },
        { title: 'Guruh', dataIndex: 'group', key: 'group' },
        { title: 'Summa', dataIndex: 'amount', key: 'amount', render: text => <span className="font-bold text-slate-700">{text}</span> },
        {
            title: 'Turi',
            dataIndex: 'type',
            key: 'type',
            render: type => <Tag color={type === 'O\'quv puli' ? 'blue' : 'orange'}>{type}</Tag>
        },
        {
            title: 'Holat',
            dataIndex: 'status',
            key: 'status',
            render: status => {
                let color = 'default';
                if (status === 'To\'langan') color = 'green';
                if (status === 'Kutilmoqda') color = 'gold';
                if (status === 'To\'lanmagan') color = 'red';
                return <Tag color={color}>{status}</Tag>;
            }
        },
        { title: 'Sana', dataIndex: 'date', key: 'date' },
    ];

    return (
        <div className="space-y-6">
            <div className="flex justify-between items-center bg-white p-6 rounded-xl shadow-sm border border-slate-200">
                <div>
                    <h2 className="text-2xl font-bold text-slate-800">Moliyaviy hisobotlar</h2>
                    <p className="text-slate-500 text-sm mt-1">O'quvchilar to'lovlari, o'quv pullari va qarzdorliklarni kuzatib boring.</p>
                </div>
                <div className="flex space-x-3">
                    <Button icon={<FileText size={16} />}>Hisobotni yuklash</Button>
                    <Button type="primary" className="shadow-blue-500/30 shadow-lg">Invoys yaratish</Button>
                </div>
            </div>

            <Row gutter={[24, 24]}>
                <Col xs={24} lg={8}>
                    <Card className="rounded-xl shadow-sm border-slate-200">
                        <div className="flex items-center mb-4">
                            <div className="p-3 bg-blue-50 rounded-lg mr-4"><TrendingUp className="text-blue-500" /></div>
                            <div>
                                <Text type="secondary" className="font-medium text-xs uppercase tracking-wider">Umumiy tushum (Shu oy)</Text>
                                <Title level={3} className="!m-0 text-slate-800">12,450,000 <span className="text-base font-normal text-slate-400">UZS</span></Title>
                            </div>
                        </div>
                        <Progress percent={85} strokeColor="#3b82f6" />
                        <p className="text-sm text-slate-500 mt-2">Kutilayotgan tushumning 85% i yig'ildi</p>
                    </Card>
                </Col>

                <Col xs={24} lg={8}>
                    <Card className="rounded-xl shadow-sm border-slate-200">
                        <div className="flex items-center mb-4">
                            <div className="p-3 bg-red-50 rounded-lg mr-4"><AlertCircle className="text-red-500" /></div>
                            <div>
                                <Text type="secondary" className="font-medium text-xs uppercase tracking-wider">Mavjud qarzdorliklar</Text>
                                <Title level={3} className="!m-0 text-slate-800">2,150,000 <span className="text-base font-normal text-slate-400">UZS</span></Title>
                            </div>
                        </div>
                        <div className="flex justify-between text-sm mt-2 pt-2 border-t border-slate-100/50">
                            <span className="text-slate-500">Jami qarzdorlar: <strong className="text-slate-800">14 ta o'quvchi</strong></span>
                            <a href="#" className="text-red-500">Eslatish</a>
                        </div>
                    </Card>
                </Col>

                <Col xs={24} lg={8}>
                    <Card className="rounded-xl shadow-sm border-slate-200">
                        <div className="flex items-center mb-4">
                            <div className="p-3 bg-emerald-50 rounded-lg mr-4"><CreditCard className="text-emerald-500" /></div>
                            <div>
                                <Text type="secondary" className="font-medium text-xs uppercase tracking-wider">So'nggi operatsiyalar</Text>
                                <Title level={3} className="!m-0 text-slate-800">42 <span className="text-base font-normal text-slate-400">bu hafta</span></Title>
                            </div>
                        </div>
                        <div className="flex justify-between text-sm mt-2 pt-2 border-t border-slate-100/50">
                            <span className="text-slate-500">Asosan: <strong className="text-slate-800">Click / Payme</strong> orqali</span>
                        </div>
                    </Card>
                </Col>
            </Row>

            <div className="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <div className="p-5 border-b border-slate-100">
                    <h3 className="text-lg font-bold text-slate-800">To'lovlar tarixi</h3>
                </div>
                <Table
                    columns={columns}
                    dataSource={MOCK_PAYMENTS}
                    rowKey="id"
                    pagination={{ pageSize: 5 }}
                />
            </div>
        </div>
    );
};

export default Finance;

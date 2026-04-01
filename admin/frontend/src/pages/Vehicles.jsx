import { Table, Button, Space, Tag } from 'antd';
import { Plus, Edit2, Trash2 } from 'lucide-react';

const MOCK_VEHICLES = [
    { id: 1, model: 'Chevrolet Nexia-3', plate: '50 958 KBA', year: 2021, status: 'Faol', assigned: 'Rasulov Isoxon' },
    { id: 2, model: 'Chevrolet Damas', plate: '50 259 PBA', year: 2020, status: 'Ta\'mirda', assigned: 'Valijonov Sanjarbek' },
    { id: 3, model: 'Chevrolet Cobalt', plate: '50 111 AAA', year: 2023, status: 'Faol', assigned: '-' },
];

const Vehicles = () => {
    const columns = [
        { title: 'ID', dataIndex: 'id', key: 'id', width: 70 },
        {
            title: 'Avtomobil modeli',
            dataIndex: 'model',
            key: 'model',
            render: (text) => <span className="font-semibold text-slate-800">{text}</span>
        },
        {
            title: 'Davlat raqami',
            dataIndex: 'plate',
            key: 'plate',
            render: (text) => <span className="font-mono bg-slate-100 px-3 py-1 rounded border border-slate-300 font-bold">{text}</span>
        },
        { title: 'Yili', dataIndex: 'year', key: 'year' },
        { title: 'Biriktirilgan o\'qituvchi', dataIndex: 'assigned', key: 'assigned' },
        {
            title: 'Holat',
            dataIndex: 'status',
            key: 'status',
            render: (status) => <Tag color={status === 'Faol' ? 'green' : 'red'}>{status}</Tag>
        },
        {
            title: 'Amallar',
            key: 'actions',
            render: (_, record) => (
                <Space size="middle">
                    <Button type="text" className="text-blue-500 hover:text-blue-700 p-0" icon={<Edit2 size={16} />} />
                    <Button type="text" className="text-red-500 hover:text-red-700 p-0" icon={<Trash2 size={16} />} />
                </Space>
            ),
        },
    ];

    return (
        <div className="space-y-6">
            <div className="flex justify-between items-center bg-white p-6 rounded-xl shadow-sm border border-slate-200">
                <div>
                    <h2 className="text-2xl font-bold text-slate-800">Avtoparkni boshqarish</h2>
                    <p className="text-slate-500 text-sm mt-1">O'quv avtomobillari, davlat raqamlari va biriktiruvlarni boshqarish</p>
                </div>
                <Button
                    type="primary"
                    size="large"
                    icon={<Plus size={18} />}
                    className="rounded-lg shadow-blue-500/30 shadow-lg flex items-center"
                >
                    Avtomobil qo'shish
                </Button>
            </div>

            <div className="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <Table
                    columns={columns}
                    dataSource={MOCK_VEHICLES}
                    rowKey="id"
                    pagination={false}
                />
            </div>
        </div>
    );
};

export default Vehicles;

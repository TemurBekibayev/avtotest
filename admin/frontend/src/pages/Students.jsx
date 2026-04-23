import { useState, useEffect } from 'react';
import { Table, Button, Input, Select, Tag, Modal, Space, Form, message, Popconfirm } from 'antd';
import { Plus, Search, Edit2, Trash2 } from 'lucide-react';
import api from '../api/axios';

const { Option } = Select;

const getStatusColor = (status) => {
    switch (status?.toLowerCase()) {
        case 'active': return 'green';
        case 'debtor': return 'red';
        case 'graduated': return 'purple';
        case 'inactive': return 'default';
        default: return 'default';
    }
}

const getStatusLabel = (status) => {
    switch (status?.toLowerCase()) {
        case 'active': return 'Faol';
        case 'debtor': return 'Qarzdor';
        case 'graduated': return 'Bitirgan';
        case 'inactive': return 'Nofaol';
        default: return status;
    }
}

const Students = () => {
    const [students, setStudents] = useState([]);
    const [groups, setGroups] = useState([]);
    const [loading, setLoading] = useState(false);

    const [searchText, setSearchText] = useState('');
    const [categoryFilter, setCategoryFilter] = useState('All');

    // Modal state
    const [isModalVisible, setIsModalVisible] = useState(false);
    const [modalSubmitting, setModalSubmitting] = useState(false);
    const [editingStudent, setEditingStudent] = useState(null);
    const [form] = Form.useForm();

    const fetchStudents = async () => {
        try {
            setLoading(true);
            const response = await api.get('/students');
            setStudents(response.data);
        } catch (error) {
            console.error('Fetch students error:', error.response || error);
            if (error.response?.status === 401) return message.error('Tizimga qayta kiring (401)');
            message.error('O\'quvchilar ro\'yxatini olishda xatolik');
        } finally {
            setLoading(false);
        }
    };

    const fetchGroups = async () => {
        try {
            const response = await api.get('/groups');
            setGroups(response.data);
        } catch (error) {
            console.error('Failed to fetch groups', error.response || error);
        }
    };

    useEffect(() => {
        fetchStudents();
        fetchGroups();
    }, []);

    const handleDelete = async (id) => {
        try {
            await api.delete(`/students/${id}`);
            message.success('O\'quvchi muvaffaqiyatli o\'chirildi');
            fetchStudents();
        } catch (error) {
            if (error.response?.status === 401) return message.error('Tizimga qayta kiring (401)');
            message.error('O\'chirishda xatolik');
        }
    };

    const openAddModal = () => {
        setEditingStudent(null);
        form.resetFields();
        form.setFieldsValue({ status: 'active', duration_months: 1, category: 'B' });
        setIsModalVisible(true);
    };

    const openEditModal = (student) => {
        setEditingStudent(student);
        form.setFieldsValue({
            organization_id: student.organization_id || 1,
            group_id: student.group_id,
            full_name: student.full_name,
            category: student.category,
            phone: student.phone,
            address: student.address,
            status: student.status || 'active',
            email: student.user?.email,
            password: student.user?.plain_password,
            duration_months: null,
        });
        setIsModalVisible(true);
    };

    const handleModalSubmit = async () => {
        try {
            const values = await form.validateFields();
            setModalSubmitting(true);

            const payload = { ...values, organization_id: 1 };

            if (editingStudent) {
                await api.put(`/students/${editingStudent.id}`, payload);
                message.success('O\'quvchi ma\'lumotlari yangilandi');
            } else {
                await api.post('/students', payload);
                message.success('Yangi o\'quvchi qo\'shildi');
            }

            setIsModalVisible(false);
            fetchStudents();
        } catch (error) {
            console.error('Save expected error:', error.response || error);
            if (error.response?.status === 401) {
                message.error('Avtorizatsiya xatosi. Tizimdan chiqib, qaytdan kiring.');
            } else if (error.response?.status === 422) {
                const errors = error.response.data.errors;
                const errorMessages = Object.values(errors).flat().join(' ');
                message.error(`Validatsiya: ${errorMessages}`);
            } else {
                message.error(error.response?.data?.message || 'Saqlashda xatolik yuz berdi');
            }
        } finally {
            setModalSubmitting(false);
        }
    };

    // Table Columns Setup
    const columns = [
        { title: 'ID', dataIndex: 'id', key: 'id', width: 60 },
        {
            title: 'F.I.O.',
            dataIndex: 'full_name',
            key: 'full_name',
            render: (text) => <span className="font-semibold text-slate-800">{text}</span>
        },
        {
            title: 'Guruh',
            dataIndex: 'group',
            key: 'group',
            render: (group) => group ? group.name : 'N/A'
        },
        {
            title: 'Login',
            key: 'login',
            render: (_, record) => record.user?.email || 'N/A'
        },
        {
            title: 'Parol',
            key: 'password',
            render: (_, record) => <Tag color="orange" className="font-mono">{record.user?.plain_password || 'N/A'}</Tag>
        },
        {
            title: 'Muddat',
            key: 'expiry',
            render: (_, record) => {
                if (!record.user?.access_expires_at) return 'N/A';
                
                const expiryDate = new Date(record.user.access_expires_at);
                const today = new Date();
                const diffTime = Math.ceil((expiryDate - today) / (1000 * 60 * 60 * 24));
                
                let tagColor = 'green';
                let tagText = `${diffTime} kun qoldi`;
                
                if (diffTime < 0) {
                    tagColor = 'red';
                    tagText = 'Tugagan';
                } else if (diffTime <= 7) {
                    tagColor = 'orange';
                }
                
                return (
                    <div className="flex flex-col">
                        <span>{expiryDate.toLocaleDateString()}</span>
                        <Tag color={tagColor} className="mt-1 w-max text-xs font-medium border-0">{tagText}</Tag>
                    </div>
                );
            }
        },
        {
            title: 'Telefon',
            dataIndex: 'phone',
            key: 'phone'
        },
        {
            title: 'Holat',
            dataIndex: 'status',
            key: 'status',
            render: (status) => <Tag color={getStatusColor(status)} className="rounded-full px-3 uppercase text-xs">{getStatusLabel(status)}</Tag>
        },
        {
            title: 'Amallar',
            key: 'actions',
            render: (_, record) => (
                <Space size="middle">
                    <Button
                        type="text"
                        onClick={() => openEditModal(record)}
                        className="text-blue-500 hover:text-blue-700 p-0"
                        icon={<Edit2 size={16} />}
                    />
                    <Popconfirm
                        title="O'quvchini o'chirish"
                        description="Haqiqatan ham bu o'quvchini o'chirmoqchimisiz?"
                        onConfirm={() => handleDelete(record.id)}
                        okText="Ha"
                        cancelText="Yo'q"
                    >
                        <Button type="text" className="text-red-500 hover:text-red-700 p-0" icon={<Trash2 size={16} />} />
                    </Popconfirm>
                </Space>
            ),
        },
    ];

    // Filtering Logic
    const filteredData = students.filter(student => {
        const matchesSearch = student.full_name?.toLowerCase().includes(searchText.toLowerCase()) ||
            student.phone?.includes(searchText);
        const matchesCategory = categoryFilter === 'All' || student.category === categoryFilter;
        return matchesSearch && matchesCategory;
    });

    return (
        <div className="space-y-6">
            {/* Header Section */}
            <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-6 rounded-xl shadow-sm border border-slate-200">
                <div>
                    <h2 className="text-2xl font-bold text-slate-800">O'quvchilar bazasi</h2>
                    <p className="text-slate-500 text-sm mt-1">Ro'yxatga olish, holatlarni va aloqa ma'lumotlarini boshqarish</p>
                </div>
                <Button
                    type="primary"
                    size="large"
                    icon={<Plus size={18} />}
                    onClick={openAddModal}
                    className="rounded-lg shadow-blue-500/30 shadow-lg flex items-center"
                >
                    Yangi o'quvchi qo'shish
                </Button>
            </div>

            {/* Filter Options */}
            <div className="bg-white p-4 rounded-xl shadow-sm border border-slate-200 flex flex-wrap gap-4 items-center">
                <Input
                    placeholder="Ism yoki telefon bo'yicha qidirish..."
                    prefix={<Search size={16} className="text-slate-400" />}
                    value={searchText}
                    onChange={(e) => setSearchText(e.target.value)}
                    className="w-full sm:w-64 rounded-lg"
                    size="large"
                />
                <Select
                    defaultValue="All"
                    size="large"
                    className="w-full sm:w-40"
                    onChange={setCategoryFilter}
                >
                    <Option value="All">Barcha toifalar</Option>
                    <Option value="A">Toifa A</Option>
                    <Option value="B">Toifa B</Option>
                    <Option value="BC">Toifa BC</Option>
                    <Option value="C">Toifa C</Option>
                    <Option value="D">Toifa D</Option>
                </Select>
            </div>

            {/* Data Table */}
            <div className="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <Table
                    columns={columns}
                    dataSource={filteredData}
                    rowKey="id"
                    loading={loading}
                    pagination={{ pageSize: 10 }}
                    className="custom-table"
                />
            </div>

            {/* Add/Edit Form Modal */}
            <Modal
                title={<span className="text-lg font-bold text-slate-800">{editingStudent ? 'O\'quvchini tahrirlash' : 'Yangi o\'quvchi qo\'shish'}</span>}
                open={isModalVisible}
                onOk={handleModalSubmit}
                confirmLoading={modalSubmitting}
                onCancel={() => setIsModalVisible(false)}
                okText="Saqlash"
                cancelText="Bekor qilish"
            >
                <Form
                    form={form}
                    layout="vertical"
                    className="mt-4"
                >
                    <Form.Item
                        name="full_name"
                        label="F.I.O."
                        rules={[{ required: true, message: 'Iltimos o\'quvchi ismini kiriting' }]}
                    >
                        <Input placeholder="Masalan: Rasulov Isoxon" />
                    </Form.Item>

                    <Form.Item
                        name="group_id"
                        label="Guruh"
                        rules={[{ required: true, message: 'Iltimos guruhni tanlang' }]}
                    >
                        <Select placeholder="Guruhni tanlang">
                            {groups.map(g => (
                                <Option key={g.id} value={g.id}>{g.name}</Option>
                            ))}
                        </Select>
                    </Form.Item>

                    <div className="grid grid-cols-2 gap-4">
                        <Form.Item
                            name="category"
                            label="Guvohnoma toifasi"
                            rules={[{ required: true, message: 'Iltimos toifani tanlang' }]}
                        >
                            <Select placeholder="Tanlang">
                                <Option value="A">A</Option>
                                <Option value="B">B</Option>
                                <Option value="BC">BC</Option>
                                <Option value="C">C</Option>
                                <Option value="D">D</Option>
                            </Select>
                        </Form.Item>

                        <Form.Item
                            name="status"
                            label="Holat"
                            initialValue="active"
                        >
                            <Select>
                                <Option value="active">Faol</Option>
                                <Option value="inactive">Nofaol</Option>
                                <Option value="debtor">Qarzdor</Option>
                                <Option value="graduated">Bitirgan</Option>
                            </Select>
                        </Form.Item>
                    </div>

                    <Form.Item
                        name="phone"
                        label="Telefon raqami"
                        rules={[{ required: true, message: 'Iltimos telefon raqamini kiriting' }]}
                    >
                        <Input placeholder="+998901234567" />
                    </Form.Item>

                    <Form.Item
                        name="address"
                        label="Manzil"
                    >
                        <Input.TextArea placeholder="O'quvchi manzili..." />
                    </Form.Item>

                    <div className="bg-slate-50 p-4 rounded-lg border border-slate-200 mt-6">
                        <h4 className="text-slate-700 font-bold mb-4">Kirish ma'lumotlari</h4>
                        <div className="grid grid-cols-2 gap-4">
                            <Form.Item
                                name="email"
                                label="Login (Username yoki Email)"
                                extra="Bo'sh qolsa avtomatik yaratiladi"
                            >
                                <Input placeholder="Masalan: avto123" />
                            </Form.Item>

                            <Form.Item
                                name="password"
                                label="Parol"
                                extra="Bo'sh qolsa avtomatik yaratiladi"
                            >
                                <Input.Password placeholder="******" />
                            </Form.Item>
                        </div>
                        <div className="grid grid-cols-2 gap-4 mt-4">
                            <Form.Item
                                name="duration_months"
                                label={editingStudent ? "Muddatni uzaytirish (oy, ixtiyoriy)" : "Tarif muddati (oy)"}
                                extra={editingStudent ? "Quruq qoldirilsa avvalgi muddat o'zgarmaydi" : ""}
                            >
                                <Select allowClear={editingStudent} placeholder="Tanlang">
                                    <Option value={1}>1 oy</Option>
                                    <Option value={2}>2 oy</Option>
                                    <Option value={3}>3 oy</Option>
                                    <Option value={6}>6 oy</Option>
                                    <Option value={12}>1 yil</Option>
                                </Select>
                            </Form.Item>
                        </div>
                    </div>
                </Form>
            </Modal>
        </div>
    );
};

export default Students;

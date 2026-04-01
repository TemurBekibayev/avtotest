import { useState, useEffect } from 'react';
import { Table, Button, Space, Tag, Modal, Form, Input, message, Select, Popconfirm } from 'antd';
import { Plus, Edit2, Trash2, Search } from 'lucide-react';
import api from '../api/axios';

const { Option } = Select;

const Teachers = () => {
    const [teachers, setTeachers] = useState([]);
    const [loading, setLoading] = useState(false);
    const [isModalVisible, setIsModalVisible] = useState(false);
    const [modalSubmitting, setModalSubmitting] = useState(false);
    const [editingTeacher, setEditingTeacher] = useState(null);
    const [form] = Form.useForm();
    const [searchText, setSearchText] = useState('');

    const fetchTeachers = async () => {
        try {
            setLoading(true);
            const response = await api.get('/instructors');
            setTeachers(response.data);
        } catch (error) {
            message.error('O\'qituvchilarni yuklashda xatolik yuz berdi');
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchTeachers();
    }, []);

    const openAddModal = () => {
        setEditingTeacher(null);
        form.resetFields();
        form.setFieldsValue({ status: 'active' });
        setIsModalVisible(true);
    };

    const openEditModal = (teacher) => {
        setEditingTeacher(teacher);
        form.setFieldsValue({
            full_name: teacher.full_name,
            specialization: teacher.specialization,
            phone: teacher.phone,
            status: teacher.status,
        });
        setIsModalVisible(true);
    };

    const handleDelete = async (id) => {
        try {
            await api.delete(`/instructors/${id}`);
            message.success('O\'qituvchi o\'chirildi');
            fetchTeachers();
        } catch (error) {
            message.error('O\'chirishda xatolik');
        }
    };

    const handleSubmit = async () => {
        try {
            const values = await form.validateFields();
            setModalSubmitting(true);
            
            if (editingTeacher) {
                await api.put(`/instructors/${editingTeacher.id}`, values);
                message.success('Ma\'lumotlar yangilandi');
            } else {
                await api.post('/instructors', values);
                message.success('Yangi o\'qituvchi qo\'shildi');
            }
            
            setIsModalVisible(false);
            fetchTeachers();
        } catch (error) {
            console.error(error);
            message.error(error.response?.data?.message || 'Saqlashda xatolik yuz berdi');
        } finally {
            setModalSubmitting(false);
        }
    };

    const columns = [
        { title: 'ID', dataIndex: 'id', key: 'id', width: 70 },
        {
            title: 'O\'qituvchi ismi',
            dataIndex: 'full_name',
            key: 'full_name',
            render: (text) => <span className="font-semibold text-slate-800">{text}</span>
        },
        { title: 'Ixtisoslashuvi', dataIndex: 'specialization', key: 'specialization' },
        { title: 'Telefon', dataIndex: 'phone', key: 'phone', render: text => text || 'N/A' },
        {
            title: 'Holat',
            dataIndex: 'status',
            key: 'status',
            render: (status) => <Tag color={status === 'active' ? 'green' : 'orange'}>{status === 'active' ? 'Faol' : 'Nofaol'}</Tag>
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
                        title="O'chirishni tasdiqlaysizmi?"
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

    const filteredData = teachers.filter(t => 
        t.full_name?.toLowerCase().includes(searchText.toLowerCase()) || 
        t.specialization?.toLowerCase().includes(searchText.toLowerCase())
    );

    return (
        <div className="space-y-6">
            <div className="flex justify-between items-center bg-white p-6 rounded-xl shadow-sm border border-slate-200">
                <div>
                    <h2 className="text-2xl font-bold text-slate-800">O'qituvchilarni boshqarish</h2>
                    <p className="text-slate-500 text-sm mt-1">O'qituvchilar tarkibini va ularning mutaxassisliklarini boshqarish</p>
                </div>
                <Button
                    type="primary"
                    size="large"
                    icon={<Plus size={18} />}
                    onClick={openAddModal}
                    className="rounded-lg shadow-blue-500/30 shadow-lg flex items-center"
                >
                    O'qituvchi qo'shish
                </Button>
            </div>

            <div className="bg-white p-4 rounded-xl shadow-sm border border-slate-200 flex flex-wrap gap-4 items-center">
                <Input
                    placeholder="Ism yoki ixtisoslik bo'yicha qidirish..."
                    prefix={<Search size={16} className="text-slate-400" />}
                    value={searchText}
                    onChange={(e) => setSearchText(e.target.value)}
                    className="w-full sm:w-80 rounded-lg"
                    size="large"
                />
            </div>

            <div className="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <Table
                    columns={columns}
                    dataSource={filteredData}
                    loading={loading}
                    rowKey="id"
                    pagination={{ pageSize: 15 }}
                />
            </div>

            <Modal
                title={<div className="font-bold text-lg">{editingTeacher ? 'O\'qituvchini tahrirlash' : 'Yangi o\'qituvchi qo\'shish'}</div>}
                open={isModalVisible}
                onOk={handleSubmit}
                confirmLoading={modalSubmitting}
                onCancel={() => setIsModalVisible(false)}
                okText="Saqlash"
                cancelText="Bekor qilish"
                className="rounded-2xl"
            >
                <Form form={form} layout="vertical" className="mt-4">
                    <Form.Item
                        name="full_name"
                        label="F.I.O."
                        rules={[{ required: true, message: 'Iltimos ismni kiriting' }]}
                    >
                        <Input placeholder="Masalan: Rasulov Isoxon" />
                    </Form.Item>

                    <Form.Item
                        name="specialization"
                        label="Ixtisoslashuvi"
                    >
                        <Input placeholder="Masalan: Nazariya va Amaliyot" />
                    </Form.Item>

                    <Form.Item
                        name="phone"
                        label="Telefon raqami"
                    >
                        <Input placeholder="+998901234567" />
                    </Form.Item>

                    <Form.Item
                        name="status"
                        label="Holat"
                    >
                        <Select>
                            <Option value="active">Faol</Option>
                            <Option value="inactive">Nofaol / Ta'tilda</Option>
                        </Select>
                    </Form.Item>
                </Form>
            </Modal>
        </div>
    );
};

export default Teachers;

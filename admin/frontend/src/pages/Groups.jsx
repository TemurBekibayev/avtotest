import { useState, useEffect } from 'react';
import { Card, Button, List, Typography, Badge, Modal, Input, Row, Col, Form, Select, message, Popconfirm } from 'antd';
import { Plus, Users, CalendarDays, ChevronRight, Edit2, Trash2 } from 'lucide-react';
import api from '../api/axios';

const { Option } = Select;

const Groups = () => {
    const [groups, setGroups] = useState([]);
    const [loading, setLoading] = useState(false);
    const [selectedGroup, setSelectedGroup] = useState(null);

    // Modal state
    const [isModalVisible, setIsModalVisible] = useState(false);
    const [modalSubmitting, setModalSubmitting] = useState(false);
    const [editingGroup, setEditingGroup] = useState(null);
    const [form] = Form.useForm();

    const fetchGroups = async () => {
        try {
            setLoading(true);
            const response = await api.get('/groups');
            setGroups(response.data);
            if (response.data.length > 0 && !selectedGroup) {
                setSelectedGroup(response.data[0]);
            } else if (response.data.length === 0) {
                setSelectedGroup(null);
            } else {
                const updated = response.data.find(g => g.id === selectedGroup?.id);
                if (updated) setSelectedGroup(updated);
            }
        } catch (error) {
            console.error('Fetch error:', error.response || error);
            if (error.response?.status === 401) {
                message.error('Tizimga qayta kiring (401)');
            } else {
                message.error('Guruhlarni olishda xatolik');
            }
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchGroups();
    }, []);

    const handleDelete = async (id) => {
        try {
            await api.delete(`/groups/${id}`);
            message.success('Guruh o\'chirildi');
            if (selectedGroup?.id === id) setSelectedGroup(null);
            fetchGroups();
        } catch (error) {
            if (error.response?.status === 401) return message.error('Tizimga qayta kiring (401)');
            message.error('O\'chirishda xatolik');
        }
    };

    const openAddModal = () => {
        setEditingGroup(null);
        form.resetFields();
        setIsModalVisible(true);
    };

    const openEditModal = (group) => {
        setEditingGroup(group);
        form.setFieldsValue({
            name: group.name,
            category: group.category,
        });
        setIsModalVisible(true);
    };

    const handleModalSubmit = async () => {
        try {
            const values = await form.validateFields();
            setModalSubmitting(true);

            const payload = { ...values, organization_id: 1 };

            if (editingGroup) {
                await api.put(`/groups/${editingGroup.id}`, payload);
                message.success('Guruh yangilandi');
            } else {
                await api.post('/groups', payload);
                message.success('Yangi guruh qo\'shildi');
            }

            setIsModalVisible(false);
            fetchGroups();
        } catch (error) {
            console.error('Save error:', error.response || error);

            if (error.response?.status === 401) {
                message.error('Avtorizatsiya xatosi. Iltimos, tizimdan chiqib, qaytdan kiring.');
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

    return (
        <div className="space-y-6">
            <div className="flex justify-between items-center bg-white p-6 rounded-xl shadow-sm border border-slate-200">
                <div>
                    <h2 className="text-2xl font-bold text-slate-800">Guruhlar va rejalar</h2>
                    <p className="text-slate-500 text-sm mt-1">Faol o'quv guruhlarini va ularning haftalik dars jadvallarini boshqarish</p>
                </div>
                <Button
                    type="primary"
                    size="large"
                    icon={<Plus size={18} />}
                    onClick={openAddModal}
                    className="rounded-lg shadow-blue-500/30 shadow-lg"
                >
                    Guruh yaratish
                </Button>
            </div>

            <Row gutter={[24, 24]}>
                {/* Left Column: Groups List */}
                <Col xs={24} lg={8}>
                    <Card
                        title={<span className="font-bold text-slate-800">Faol guruhlar</span>}
                        className="rounded-xl shadow-sm border-slate-200 h-full"
                    >
                        <List
                            loading={loading}
                            dataSource={groups}
                            locale={{ emptyText: "Guruhlar topilmadi" }}
                            renderItem={(item) => (
                                <div
                                    onClick={() => setSelectedGroup(item)}
                                    className={`p-4 border-b border-slate-100 cursor-pointer transition-colors flex justify-between items-center ${selectedGroup?.id === item.id ? 'bg-blue-50 border-l-4 border-l-blue-500' : 'hover:bg-slate-50 border-l-4 border-l-transparent'}`}
                                >
                                    <div>
                                        <h4 className="font-bold text-slate-800 text-base">{item.name}</h4>
                                        <p className="text-sm text-slate-500 flex items-center mt-1">
                                            <Badge color="blue" text={`Toifa ${item.category}`} />
                                        </p>
                                    </div>
                                    <div className="flex items-center gap-2">
                                        <Button
                                            type="text"
                                            className="text-slate-400 hover:text-blue-500 p-0"
                                            icon={<Edit2 size={16} />}
                                            onClick={(e) => { e.stopPropagation(); openEditModal(item); }}
                                        />
                                        <Popconfirm
                                            title="Guruhni o'chirish"
                                            onConfirm={(e) => { e.stopPropagation(); handleDelete(item.id); }}
                                            onCancel={(e) => e.stopPropagation()}
                                        >
                                            <Button type="text" className="text-slate-400 hover:text-red-500 p-0" icon={<Trash2 size={16} />} onClick={e => e.stopPropagation()} />
                                        </Popconfirm>
                                        <ChevronRight size={18} className="text-slate-400 ml-2" />
                                    </div>
                                </div>
                            )}
                        />
                    </Card>
                </Col>

                {/* Right Column: Group Details & Schedule */}
                <Col xs={24} lg={16}>
                    {selectedGroup ? (
                        <div className="space-y-6">
                            <Card className="rounded-xl shadow-sm border-slate-200">
                                <div className="flex justify-between items-start mb-6">
                                    <div>
                                        <h3 className="text-xl font-bold text-slate-800 mb-1">{selectedGroup.name} tafsilotlari</h3>
                                        <Badge status="success" text="Faol" />
                                    </div>
                                    <Button onClick={() => openEditModal(selectedGroup)}>Guruhni tahrirlash</Button>
                                </div>

                                <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                                    <div className="bg-slate-50 p-3 rounded-lg border border-slate-100">
                                        <p className="text-xs font-semibold text-slate-500 uppercase">Toifa</p>
                                        <p className="font-medium text-slate-800">{selectedGroup.category}</p>
                                    </div>
                                    <div className="bg-slate-50 p-3 rounded-lg border border-slate-100">
                                        <p className="text-xs font-semibold text-slate-500 uppercase">Tashkilot</p>
                                        <p className="font-medium text-slate-800">{selectedGroup.organization?.name || 'Noma\'lum'}</p>
                                    </div>
                                    <div className="bg-slate-50 p-3 rounded-lg border border-slate-100">
                                        <p className="text-xs font-semibold text-slate-500 uppercase">Yaratilgan sana</p>
                                        <p className="font-medium text-slate-800">{new Date(selectedGroup.created_at).toLocaleDateString()}</p>
                                    </div>
                                </div>
                            </Card>

                            <Card
                                title={<div className="flex items-center"><CalendarDays size={18} className="mr-2 text-blue-500" /> <span className="font-bold text-slate-800">Haftalik dars jadvali</span></div>}
                                className="rounded-xl shadow-sm border-slate-200"
                                extra={<Button type="dashed" size="small" icon={<Plus size={14} />}>Dars qo'shish</Button>}
                            >
                                <div className="space-y-3">
                                    <div className="p-4 text-center text-slate-500">
                                        Jadval integratsiyasi tez orada qo'shiladi...
                                    </div>
                                </div>
                            </Card>
                        </div>
                    ) : (
                        <div className="h-full flex items-center justify-center bg-white rounded-xl shadow-sm border border-slate-200 p-8 text-slate-500">
                            Guruh tanlanmagan. Ro'yxatdan guruhni tanlang yoki yangisini yarating.
                        </div>
                    )}
                </Col>
            </Row>

            {/* Add/Edit Form Modal */}
            <Modal
                title={<span className="text-lg font-bold text-slate-800">{editingGroup ? 'Guruhni tahrirlash' : 'Guruh yaratish'}</span>}
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
                        name="name"
                        label="Guruh nomi"
                        rules={[{ required: true, message: 'Iltimos guruh nomini kiriting' }]}
                    >
                        <Input placeholder="Masalan: Group 19 (BC)" />
                    </Form.Item>

                    <Form.Item
                        name="category"
                        label="Guvohnoma toifasi"
                        rules={[{ required: true, message: 'Iltimos toifani tanlang' }]}
                    >
                        <Select placeholder="Toifani tanlang">
                            <Option value="A">A</Option>
                            <Option value="B">B</Option>
                            <Option value="BC">BC</Option>
                            <Option value="C">C</Option>
                            <Option value="D">D</Option>
                        </Select>
                    </Form.Item>
                </Form>
            </Modal>
        </div>
    );
};

export default Groups;

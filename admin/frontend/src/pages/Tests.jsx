import { useState, useEffect, useCallback } from 'react';
import { Card, Table, Tag, Button, Progress, message, Tabs, Input, Image, Space, Modal, Form, Radio, Select, Upload } from 'antd';
import { Plus, CheckCircle2, XCircle, Search, Eye, Languages, Image as ImageIcon, Video, UploadCloud } from 'lucide-react';
import api from '../api/axios';



const Tests = () => {
    const backendUrl = 'https://amudaryoavtotest.uz/api';
    const [activeTab, setActiveTab] = useState('results');

    // Results State
    const [testResults, setTestResults] = useState([]);
    const [resultsLoading, setResultsLoading] = useState(false);
    const [resultsPagination, setResultsPagination] = useState({ current: 1, pageSize: 20, total: 0 });

    // Questions State
    const [questions, setQuestions] = useState([]);
    const [questionsLoading, setQuestionsLoading] = useState(false);
    const [questionsPagination, setQuestionsPagination] = useState({ current: 1, pageSize: 15, total: 0 });
    const [searchText, setSearchText] = useState('');
    const [selectedQuestion, setSelectedQuestion] = useState(null);
    const [isModalVisible, setIsModalVisible] = useState(false);

    // Add Question State
    const [isAddModalVisible, setIsAddModalVisible] = useState(false);
    const [addForm] = Form.useForm();
    const [modalSubmitting, setModalSubmitting] = useState(false);
    const [questionFile, setQuestionFile] = useState(null);

    // Template Creation State
    const [isTemplateModalVisible, setIsTemplateModalVisible] = useState(false);
    const [templateForm] = Form.useForm();
    const [templateSubmitting, setTemplateSubmitting] = useState(false);

    // Shablon State
    const [shablons, setShablons] = useState([]);
    const [shablonsLoading, setShablonsLoading] = useState(false);
    const [selectedShablon, setSelectedShablon] = useState(null);
    const [shablonQuestions, setShablonQuestions] = useState([]);
    const [shablonQuestionsLoading, setShablonQuestionsLoading] = useState(false);
    
    // Shablon Edit State
    const [isShablonEditModalVisible, setIsShablonEditModalVisible] = useState(false);
    const [shablonEditForm] = Form.useForm();
    const [shablonEditSubmitting, setShablonEditSubmitting] = useState(false);
    
    // Question Edit State
    const [isQuestionEditModalVisible, setIsQuestionEditModalVisible] = useState(false);
    const [questionEditForm] = Form.useForm();
    const [questionEditSubmitting, setQuestionEditSubmitting] = useState(false);
    const [editingQuestion, setEditingQuestion] = useState(null);
    const [editingOptions, setEditingOptions] = useState([]);
    
    // JSON Import State
    const [isImportModalVisible, setIsImportModalVisible] = useState(false);
    const [importForm] = Form.useForm();
    const [importSubmitting, setImportSubmitting] = useState(false);

    const fetchShablons = async () => {
        try {
            setShablonsLoading(true);
            const response = await api.get('/shablons');
            setShablons(response.data);
        } catch (error) {
            message.error('Shablonlarni yuklashda xatolik yuz berdi');
        } finally {
            setShablonsLoading(false);
        }
    };

    const fetchShablonDetails = async (id) => {
        try {
            setShablonQuestionsLoading(true);
            const response = await api.get(`/shablons/${id}`);
            setSelectedShablon(response.data);
            setShablonQuestions(response.data.questions || []);
        } catch (error) {
            message.error('Shablon savollarini yuklashda xatolik yuz berdi');
        } finally {
            setShablonQuestionsLoading(false);
        }
    };

    const handleShablonEditSubmit = async () => {
        try {
            const values = await shablonEditForm.validateFields();
            setShablonEditSubmitting(true);
            await api.put(`/shablons/${selectedShablon.id}`, values);
            message.success('Shablon muvaffaqiyatli tahrirlandi');
            setIsShablonEditModalVisible(false);
            fetchShablonDetails(selectedShablon.id);
            fetchShablons();
        } catch (error) {
            message.error(error.response?.data?.message || 'Xatolik yuz berdi');
        } finally {
            setShablonEditSubmitting(false);
        }
    };

    const handleImportSubmit = async () => {
        try {
            setImportSubmitting(true);
            const formData = new FormData();
            
            const uzFileList = importForm.getFieldValue('uz_file');
            const ruFileList = importForm.getFieldValue('ru_file');
            const kirilFileList = importForm.getFieldValue('kiril_file');
            
            const uzFile = uzFileList?.[0]?.originFileObj;
            const ruFile = ruFileList?.[0]?.originFileObj;
            const kirilFile = kirilFileList?.[0]?.originFileObj;
            
            if (uzFile) formData.append('uz_file', uzFile);
            if (ruFile) formData.append('ru_file', ruFile);
            if (kirilFile) formData.append('kiril_file', kirilFile);
            
            if (!uzFile && !ruFile && !kirilFile) {
                throw new Error("Kamida bitta JSON fayl yuklanishi shart!");
            }
            
            const response = await api.post('/shablons/import-json', formData, {
                headers: { 'Content-Type': 'multipart/form-data' }
            });
            
            message.success(response.data.message || 'Muvaffaqiyatli import qilindi');
            setIsImportModalVisible(false);
            importForm.resetFields();
            fetchShablons();
        } catch (error) {
            message.error(error.response?.data?.message || error.message || 'Xatolik yuz berdi');
        } finally {
            setImportSubmitting(false);
        }
    };

    const handleQuestionEditSubmit = async () => {
        try {
            const values = await questionEditForm.validateFields();
            setQuestionEditSubmitting(true);
            
            const formattedOptions = editingOptions.map((opt, idx) => {
                const optId = values[`opt${idx}_id`] || opt.id;
                return {
                    id: optId,
                    is_correct: values.correct_option === idx,
                    translations: [
                        { language: 'uz-lat', option_text: values[`opt${idx}_uz`] },
                        { language: 'ru', option_text: values[`opt${idx}_ru`] || '' },
                        { language: 'uz-cyr', option_text: values[`opt${idx}_kiril`] || '' }
                    ]
                };
            });
            
            const payload = {
                image_path: values.image_path,
                translations: [
                    { language: 'uz-lat', question_text: values.question_uz },
                    { language: 'ru', question_text: values.question_ru || '' },
                    { language: 'uz-cyr', question_text: values.question_kiril || '' }
                ],
                options: formattedOptions,
                answers: [
                    { language: 'uz-lat', description: values.answer_description_uz || '', video_path: values.answer_resource_uz || null },
                    { language: 'ru', description: values.answer_description_ru || '', video_path: values.answer_resource_ru || null },
                    { language: 'uz-cyr', description: values.answer_description_kiril || '', video_path: values.answer_resource_kiril || null }
                ]
            };
            
            await api.put(`/shablons/questions/${editingQuestion.id}`, payload);
            message.success("Savol muvaffaqiyatli tahrirlandi!");
            setIsQuestionEditModalVisible(false);
            fetchShablonDetails(selectedShablon.id);
        } catch (error) {
            message.error(error.response?.data?.message || 'Xatolik yuz berdi');
        } finally {
            setQuestionEditSubmitting(false);
        }
    };

    const openQuestionEditModal = (q) => {
        setEditingQuestion(q);
        const optionsList = q.options || [];
        setEditingOptions(optionsList);
        
        const uzTrans = q.translations?.find(t => t.language === 'uz-lat' || t.language === 'uz');
        const ruTrans = q.translations?.find(t => t.language === 'ru');
        const kirilTrans = q.translations?.find(t => t.language === 'uz-cyr' || t.language === 'kiril');
        
        const uzAns = q.answers?.find(a => a.language === 'uz-lat' || a.language === 'uz');
        const ruAns = q.answers?.find(a => a.language === 'ru');
        const kirilAns = q.answers?.find(a => a.language === 'uz-cyr' || a.language === 'kiril');
        
        const formValues = {
            image_path: q.image_path || '',
            question_uz: uzTrans?.question_text || uzTrans?.question || '',
            question_ru: ruTrans?.question_text || ruTrans?.question || '',
            question_kiril: kirilTrans?.question_text || kirilTrans?.question || '',
            
            answer_description_uz: uzAns?.description || '',
            answer_resource_uz: uzAns?.video_path || '',
            answer_description_ru: ruAns?.description || '',
            answer_resource_ru: ruAns?.video_path || '',
            answer_description_kiril: kirilAns?.description || '',
            answer_resource_kiril: kirilAns?.video_path || '',
        };
        
        let correctOptionIndex = 0;
        optionsList.forEach((opt, idx) => {
            formValues[`opt${idx}_id`] = opt.id;
            formValues[`opt${idx}_is_correct`] = opt.is_correct;
            if (opt.is_correct) correctOptionIndex = idx;
            
            const uzOpt = opt.translations?.find(t => t.language === 'uz-lat' || t.language === 'uz');
            const ruOpt = opt.translations?.find(t => t.language === 'ru');
            const kirilOpt = opt.translations?.find(t => t.language === 'uz-cyr' || t.language === 'kiril');
            
            formValues[`opt${idx}_uz`] = uzOpt?.option_text || uzOpt?.option || opt.option || '';
            formValues[`opt${idx}_ru`] = ruOpt?.option_text || ruOpt?.option || '';
            formValues[`opt${idx}_kiril`] = kirilOpt?.option_text || kirilOpt?.option || '';
        });
        
        formValues.correct_option = correctOptionIndex;
        
        questionEditForm.setFieldsValue(formValues);
        setIsQuestionEditModalVisible(true);
    };


    const fetchTestResults = async (page = 1) => {
        try {
            setResultsLoading(true);
            const response = await api.get('/test-results', { params: { page } });
            if (response.data.data) {
                setTestResults(response.data.data);
                setResultsPagination({
                    current: response.data.current_page,
                    pageSize: response.data.per_page,
                    total: response.data.total,
                });
            } else {
                setTestResults(response.data);
            }
        } catch (error) {
            message.error('Natijalarni olishda xatolik yuz berdi');
        } finally {
            setResultsLoading(false);
        }
    };

    const fetchQuestions = useCallback(async (page = 1, search = '') => {
        try {
            setQuestionsLoading(true);
            const response = await api.get('/test-questions', {
                params: { page, search }
            });
            setQuestions(response.data.data);
            setQuestionsPagination({
                current: response.data.current_page,
                pageSize: response.data.per_page,
                total: response.data.total,
            });
        } catch (error) {
            message.error('Savollarni olishda xatolik yuz berdi: ' + (error.response?.data?.message || error.message));
        } finally {
            setQuestionsLoading(false);
        }
    }, []);

    useEffect(() => {
        if (activeTab === 'results') {
            fetchTestResults(resultsPagination.current);
        } else if (activeTab === 'questions') {
            fetchQuestions(questionsPagination.current, searchText);
        } else if (activeTab === 'shablons') {
            fetchShablons();
            setSelectedShablon(null);
        }
    }, [activeTab, fetchQuestions]);

    const handleSearch = (value) => {
        setSearchText(value);
        fetchQuestions(1, value);
    };

    const showQuestionDetails = (record) => {
        setSelectedQuestion(record);
        setIsModalVisible(true);
    };

    const handleAddSubmit = async () => {
        try {
            const values = await addForm.validateFields();
            setModalSubmitting(true);

            const formData = new FormData();
            formData.append('question_uz', values.question_uz);
            if (values.question_ru) formData.append('question_ru', values.question_ru);
            if (values.question_kiril) formData.append('question_kiril', values.question_kiril);

            if (questionFile) {
                formData.append('question_file', questionFile);
            }

            if (values.answer_description) formData.append('answer_description', values.answer_description);
            if (values.answer_resource) formData.append('answer_resource', values.answer_resource);

            const formattedOptions = [
                {
                    is_correct: values.correct_option === 1,
                    text_uz: values.opt1_uz, text_ru: values.opt1_ru, text_kiril: values.opt1_kiril
                },
                {
                    is_correct: values.correct_option === 2,
                    text_uz: values.opt2_uz, text_ru: values.opt2_ru, text_kiril: values.opt2_kiril
                },
                {
                    is_correct: values.correct_option === 3,
                    text_uz: values.opt3_uz, text_ru: values.opt3_ru, text_kiril: values.opt3_kiril
                },
                {
                    is_correct: values.correct_option === 4,
                    text_uz: values.opt4_uz, text_ru: values.opt4_ru, text_kiril: values.opt4_kiril
                }
            ].filter(o => o.text_uz); // Filter out empty options

            if (formattedOptions.length < 2) {
                throw new Error("Kamida 2 ta variant kiritilishi shart (O'zbek tilida)");
            }

            formData.append('options', JSON.stringify(formattedOptions));

            await api.post('/test-questions', formData, {
                headers: { 'Content-Type': 'multipart/form-data' }
            });

            message.success("Yangi savol muvaffaqiyatli qo'shildi");
            setIsAddModalVisible(false);
            addForm.resetFields();
            setQuestionFile(null);
            fetchQuestions(1, searchText);
            setActiveTab('questions');
        } catch (error) {
            if (error.response) {
                message.error(error.response?.data?.message || 'Xatolik yuz berdi');
            } else {
                message.error(error.message);
            }
        } finally {
            setModalSubmitting(false);
        }
    };

    const handleTemplateSubmit = async () => {
        try {
            const values = await templateForm.validateFields();
            setTemplateSubmitting(true);
            await api.post('/test-templates', values);
            message.success('Shablon muvaffaqiyatli yaratildi');
            setIsTemplateModalVisible(false);
            templateForm.resetFields();
            fetchTestResults(1); // Refresh results possibly
        } catch (error) {
            console.error(error);
            message.error(error.response?.data?.message || 'Shablon yaratishda xatolik yuz berdi');
        } finally {
            setTemplateSubmitting(false);
        }
    };

    const resultColumns = [
        { title: 'ID', dataIndex: 'id', key: 'id', width: 60 },
        {
            title: 'O\'quvchi',
            dataIndex: 'student',
            key: 'student',
            render: (student) => <span className="font-semibold text-slate-800">{student?.full_name || 'N/A'}</span>
        },
        {
            title: 'Guruh',
            dataIndex: 'student',
            key: 'group',
            render: (student) => student?.group?.name || 'N/A'
        },
        {
            title: 'Imtihon turi',
            dataIndex: 'template',
            key: 'testType',
            render: (template) => template?.name || 'N/A'
        },
        {
            title: 'Ball',
            dataIndex: 'score',
            key: 'score',
            render: score => (
                <div className="flex items-center w-32">
                    <Progress percent={score} size="small" status={score >= 80 ? 'success' : 'exception'} />
                </div>
            )
        },
        {
            title: 'Natija',
            dataIndex: 'passed',
            key: 'passed',
            render: passed => (
                passed
                    ? <span className="flex items-center text-emerald-600 font-medium"><CheckCircle2 size={16} className="mr-1" /> O'tdi</span>
                    : <span className="flex items-center text-red-600 font-medium"><XCircle size={16} className="mr-1" /> Yiqildi</span>
            )
        },
        {
            title: 'Sana',
            dataIndex: 'taken_at',
            key: 'date',
            render: date => date ? new Date(date).toLocaleString() : 'N/A'
        },
    ];

    const questionColumns = [
        {
            title: 'ID',
            dataIndex: 'new_question_id',
            key: 'id',
            width: 80,
        },
        {
            title: 'Rasm',
            dataIndex: 'question_file',
            key: 'image',
            width: 100,
            render: (file) => file ? (
                <Image
                    src={file.startsWith('http') ? file : `${backendUrl}${file}`}
                    alt="Question"
                    className="rounded-md object-cover"
                    width={60}
                    height={40}
                    fallback="https://via.placeholder.com/60x40?text=Rasm+yo'q"
                />
            ) : <Tag color="default">Rasm yo'q</Tag>
        },
        {
            title: 'Savol (O\'zbekcha)',
            dataIndex: 'translations',
            key: 'question_uz',
            render: (translations) => {
                const uz = translations?.find(t => t.language === 'uz');
                return <div className="max-w-md truncate font-medium text-slate-700">{uz?.question || 'N/A'}</div>
            }
        },
        {
            title: 'Boshqa tillar',
            dataIndex: 'translations',
            key: 'langs',
            width: 120,
            render: (translations) => (
                <Space>
                    {translations?.map(t => (
                        <Tag key={t.language} color={t.language === 'ru' ? 'blue' : 'purple'} size="small">
                            {t.language.toUpperCase()}
                        </Tag>
                    ))}
                </Space>
            )
        },
        {
            title: 'Amallar',
            key: 'actions',
            width: 100,
            render: (_, record) => (
                <Button
                    type="text"
                    icon={<Eye size={18} className="text-slate-500" />}
                    onClick={() => showQuestionDetails(record)}
                />
            )
        }
    ];

    return (
        <div className="space-y-6">
            <div className="flex justify-between items-center bg-white p-6 rounded-xl shadow-sm border border-slate-200">
                <div>
                    <h2 className="text-2xl font-bold text-slate-800">Testlar va Imtihonlar</h2>
                    <p className="text-slate-500 text-sm mt-1">Imtihonlar bazasi, savollar banki va o'quvchilar natijalarini boshqarish.</p>
                </div>
                <div className="flex space-x-3">
                    <Button 
                        type="default" 
                        className="shadow-sm font-medium border-slate-300" 
                        icon={<Plus size={16} />} 
                        onClick={() => {
                            addForm.resetFields();
                            setQuestionFile(null);
                            setIsAddModalVisible(true);
                        }}
                    >
                        Yangi savol qo'shish
                    </Button>
                    <Button 
                        type="primary" 
                        className="shadow-blue-500/30 shadow-lg" 
                        icon={<Plus size={16} />}
                        onClick={() => {
                            templateForm.resetFields();
                            setIsTemplateModalVisible(true);
                        }}
                    >
                        Shablon yaratish
                    </Button>
                </div>
            </div>

            <Card className="rounded-xl shadow-sm border-slate-200 overflow-hidden">
                <Tabs
                    activeKey={activeTab}
                    onChange={setActiveTab}
                    className="custom-tabs"
                    items={[
                        {
                            key: 'results',
                            label: 'Imtihon natijalari',
                            children: (
                                <Table
                                    columns={resultColumns}
                                    dataSource={testResults}
                                    loading={resultsLoading}
                                    rowKey="id"
                                    pagination={{
                                        ...resultsPagination,
                                        onChange: (page) => fetchTestResults(page),
                                        showSizeChanger: false
                                    }}
                                />
                            )
                        },
                        {
                            key: 'questions',
                            label: 'Savollar banki',
                            children: (
                                <>
                                    <div className="mb-4 flex justify-between items-center">
                                        <Input.Search
                                            placeholder="Savollarni qidirish..."
                                            className="max-w-md rounded-lg"
                                            allowClear
                                            onSearch={handleSearch}
                                            onPressEnter={(e) => handleSearch(e.target.value)}
                                        />
                                        <div className="text-slate-500 text-sm">
                                            Jami savollar: <span className="font-bold text-slate-800">{questionsPagination.total}</span>
                                        </div>
                                    </div>
                                    <Table
                                        columns={questionColumns}
                                        dataSource={questions}
                                        loading={questionsLoading}
                                        rowKey="id"
                                        pagination={{
                                            ...questionsPagination,
                                            onChange: (page) => fetchQuestions(page, searchText),
                                            showSizeChanger: false
                                        }}
                                    />
                                </>
                            )
                        },
                        {
                            key: 'shablons',
                            label: 'Shablonlar boshqaruvi',
                            children: (
                                <div className="space-y-6">
                                    <div className="flex justify-between items-center mb-4">
                                        <div className="text-slate-500 text-sm">
                                            Jami shablon testlar: <span className="font-bold text-slate-800">{shablons.length}</span>
                                        </div>
                                        <Button
                                            type="primary"
                                            className="bg-indigo-600 hover:bg-indigo-700 shadow-md font-medium"
                                            icon={<UploadCloud size={16} />}
                                            onClick={() => {
                                                importForm.resetFields();
                                                setIsImportModalVisible(true);
                                            }}
                                        >
                                            JSON test import qilish
                                        </Button>
                                    </div>
                                    
                                    {!selectedShablon ? (
                                        <Table
                                            columns={[
                                                { title: 'ID', dataIndex: 'id', key: 'id', width: 60 },
                                                { title: 'Shablon nomi', dataIndex: 'name', key: 'name', className: 'font-semibold text-slate-800' },
                                                { title: 'Savollar soni', dataIndex: 'question_count', key: 'question_count', render: count => <Tag color="blue">{count} ta</Tag> },
                                                { title: 'Davomiyligi (daqiqa)', dataIndex: 'duration_minutes', key: 'duration_minutes', render: min => `${min} daqiqa` },
                                                { title: 'O\'tish balli', dataIndex: 'passing_score', key: 'passing_score' },
                                                {
                                                    title: 'Amallar',
                                                    key: 'actions',
                                                    width: 250,
                                                    render: (_, record) => (
                                                        <Space>
                                                            <Button
                                                                type="default"
                                                                size="small"
                                                                onClick={() => {
                                                                    shablonEditForm.setFieldsValue(record);
                                                                    setSelectedShablon(record);
                                                                    setIsShablonEditModalVisible(true);
                                                                }}
                                                            >
                                                                Tahrirlash
                                                            </Button>
                                                            <Button
                                                                type="primary"
                                                                ghost
                                                                size="small"
                                                                icon={<Eye size={14} />}
                                                                onClick={() => fetchShablonDetails(record.id)}
                                                            >
                                                                Savollarni ko'rish ({record.question_count})
                                                            </Button>
                                                        </Space>
                                                    )
                                                }
                                            ]}
                                            dataSource={shablons}
                                            loading={shablonsLoading}
                                            rowKey="id"
                                            pagination={{ pageSize: 15 }}
                                        />
                                    ) : (
                                        <div className="space-y-4">
                                            <div className="flex justify-between items-center bg-slate-50 p-4 rounded-xl border border-slate-200">
                                                <div>
                                                    <h3 className="text-lg font-bold text-slate-800">{selectedShablon.name}</h3>
                                                    <p className="text-slate-500 text-sm mt-1">
                                                        Davomiyligi: <b>{selectedShablon.duration_minutes} daqiqa</b> | O'tish balli: <b>{selectedShablon.passing_score}</b> | Jami: <b>{shablonQuestions.length} ta savol</b>
                                                    </p>
                                                </div>
                                                <Space>
                                                    <Button onClick={() => setSelectedShablon(null)}>Orqaga qaytish</Button>
                                                    <Button
                                                        type="primary"
                                                        onClick={() => {
                                                            shablonEditForm.setFieldsValue(selectedShablon);
                                                            setIsShablonEditModalVisible(true);
                                                        }}
                                                    >
                                                        Shablonni tahrirlash
                                                    </Button>
                                                </Space>
                                            </div>

                                            <Table
                                                loading={shablonQuestionsLoading}
                                                dataSource={shablonQuestions}
                                                rowKey="id"
                                                columns={[
                                                    { title: 'T/R', key: 'index', width: 60, render: (_, __, index) => index + 1 },
                                                    { title: 'Savol ID', dataIndex: 'json_id', key: 'json_id', width: 90 },
                                                    {
                                                        title: 'Rasm',
                                                        dataIndex: 'image_path',
                                                        key: 'image',
                                                        width: 100,
                                                        render: (file) => file ? (
                                                            <Image
                                                                src={file.startsWith('http') ? file : `${backendUrl}${file}`}
                                                                alt="Question"
                                                                className="rounded-md object-cover"
                                                                width={60}
                                                                height={40}
                                                                fallback="https://via.placeholder.com/60x40?text=Rasm+yo'q"
                                                            />
                                                        ) : <Tag color="default">Rasm yo'q</Tag>
                                                    },
                                                    {
                                                        title: 'Savol (O\'zbekcha)',
                                                        dataIndex: 'translations',
                                                        key: 'question_uz',
                                                        render: (translations) => {
                                                            const uz = translations?.find(t => t.language === 'uz-lat' || t.language === 'uz');
                                                            return <div className="max-w-md truncate font-medium text-slate-700">{uz?.question_text || uz?.question || 'N/A'}</div>
                                                        }
                                                    },
                                                    {
                                                        title: 'Boshqa tillar',
                                                        dataIndex: 'translations',
                                                        key: 'langs',
                                                        width: 120,
                                                        render: (translations) => (
                                                            <Space>
                                                                {translations?.map(t => (
                                                                    <Tag key={t.language} color={t.language === 'ru' ? 'blue' : 'purple'} size="small">
                                                                        {t.language.toUpperCase()}
                                                                    </Tag>
                                                                ))}
                                                            </Space>
                                                        )
                                                    },
                                                    {
                                                        title: 'Amallar',
                                                        key: 'actions',
                                                        width: 100,
                                                        render: (_, record) => (
                                                            <Space>
                                                                <Button
                                                                    type="primary"
                                                                    ghost
                                                                    size="small"
                                                                    onClick={() => openQuestionEditModal(record)}
                                                                >
                                                                    Tahrirlash
                                                                </Button>
                                                            </Space>
                                                        )
                                                    }
                                                ]}
                                                pagination={{ pageSize: 10 }}
                                            />
                                        </div>
                                    )}
                                </div>
                            )
                        }
                    ]}
                />
            </Card>

            {/* Question Details Modal */}
            <Modal
                title={
                    <div className="flex items-center space-x-2">
                        <Languages size={20} className="text-blue-600" />
                        <span>Savol tafsilotlari #{selectedQuestion?.new_question_id}</span>
                    </div>
                }
                open={isModalVisible}
                onCancel={() => setIsModalVisible(false)}
                footer={null}
                width={800}
                className="rounded-2xl"
            >
                {selectedQuestion && (
                    <div className="space-y-6 py-4">
                        {/* Media Section */}
                        {(selectedQuestion.question_file || selectedQuestion.answer?.answer_resource) && (
                            <div className="grid grid-cols-2 gap-4">
                                {selectedQuestion.question_file && (
                                    <div className="space-y-2">
                                        <div className="flex items-center text-slate-500 text-sm font-medium">
                                            <ImageIcon size={14} className="mr-1" /> Savol rasmi
                                        </div>
                                        <Image
                                            src={selectedQuestion.question_file.startsWith('http') ? selectedQuestion.question_file : `${backendUrl}${selectedQuestion.question_file}`}
                                            className="rounded-xl border border-slate-200 w-full"
                                        />
                                    </div>
                                )}
                                {selectedQuestion.answer?.answer_resource && (
                                    <div className="space-y-2">
                                        <div className="flex items-center text-slate-500 text-sm font-medium">
                                            <Video size={14} className="mr-1" /> Javob videosi
                                        </div>
                                        <div className="aspect-video bg-black rounded-xl overflow-hidden border border-slate-200">
                                            {(() => {
                                                const url = selectedQuestion.answer.answer_resource.startsWith('http') 
                                                    ? selectedQuestion.answer.answer_resource 
                                                    : `${backendUrl}${selectedQuestion.answer.answer_resource}`;
                                                
                                                if (url.includes('youtube.com') || url.includes('youtu.be')) {
                                                    const videoId = url.includes('v=') 
                                                        ? url.split('v=')[1]?.split('&')[0] 
                                                        : url.split('/').pop();
                                                    return (
                                                        <iframe
                                                            width="100%"
                                                            height="100%"
                                                            src={`https://www.youtube.com/embed/${videoId}`}
                                                            title="YouTube video player"
                                                            frameBorder="0"
                                                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                                            allowFullScreen
                                                        ></iframe>
                                                    );
                                                }
                                                
                                                return (
                                                    <video
                                                        controls
                                                        className="w-full h-full"
                                                        src={url}
                                                    >
                                                        Sizning brauzeringiz video tegini qo'llab-quvvatlamaydi.
                                                    </video>
                                                );
                                            })()}
                                        </div>
                                    </div>
                                )}
                            </div>
                        )}

                        {/* Translations Section */}
                        <div className="space-y-4">
                            <h4 className="font-bold text-slate-800 border-b pb-2">Savol tarjimalari</h4>
                            <div className="space-y-4">
                                {selectedQuestion.translations?.map(t => (
                                    <div key={t.id} className="bg-slate-50 p-4 rounded-xl border border-slate-100">
                                        <div className="flex items-center mb-1">
                                            <Tag color={t.language === 'uz' ? 'green' : t.language === 'ru' ? 'blue' : 'purple'}>
                                                {t.language.toUpperCase()}
                                            </Tag>
                                        </div>
                                        <p className="text-slate-800 text-base leading-relaxed">{t.question}</p>
                                    </div>
                                ))}
                            </div>
                        </div>

                        {/* Options Section */}
                        <div className="space-y-3">
                            <h4 className="font-bold text-slate-800 border-b pb-2">Javob variantlari (O'zbekcha)</h4>
                            <div className="grid grid-cols-1 gap-2">
                                {selectedQuestion.options?.map(opt => (
                                    <div
                                        key={opt.id}
                                        className={`p-3 rounded-lg border flex items-center ${opt.is_correct
                                            ? 'bg-emerald-50 border-emerald-200 text-emerald-800'
                                            : 'bg-white border-slate-200 text-slate-600'
                                            }`}
                                    >
                                        {opt.is_correct ? <CheckCircle2 size={16} className="mr-2 shrink-0" /> : <div className="w-4 mr-2" />}
                                        <span className="text-sm">{opt.translations?.[0]?.option || opt.option}</span>
                                    </div>
                                ))}
                            </div>
                        </div>

                        {/* Explanation Section */}
                        {selectedQuestion.answer?.answer_description && (
                            <div className="space-y-2">
                                <h4 className="font-bold text-slate-800 border-b pb-2">Tushuntirish</h4>
                                <p className="text-slate-600 text-sm italic bg-amber-50 p-4 rounded-xl border border-amber-100">
                                    "{selectedQuestion.answer.answer_description}"
                                </p>
                            </div>
                        )}
                    </div>
                )}
            </Modal>

            {/* Add Question Modal */}
            <Modal
                title={
                    <div className="flex items-center space-x-2">
                        <Plus size={20} className="text-blue-600" />
                        <span>Yangi test savoli qo'shish</span>
                    </div>
                }
                open={isAddModalVisible}
                onOk={handleAddSubmit}
                confirmLoading={modalSubmitting}
                onCancel={() => setIsAddModalVisible(false)}
                width={800}
                okText="Saqlash"
                cancelText="Bekor qilish"
                className="rounded-2xl"
            >
                <Form form={addForm} layout="vertical" className="mt-4" initialValues={{ correct_option: 1 }}>
                    <div className="mb-4">
                        <label className="block text-sm font-medium text-slate-700 mb-1">Savol rasmi (ixtiyoriy)</label>
                        <div className="flex items-center space-x-3">
                            <input 
                                type="file" 
                                accept="image/*" 
                                onChange={(e) => setQuestionFile(e.target.files[0])}
                                className="block w-full text-sm text-slate-500
                                file:mr-4 file:py-2 file:px-4
                                file:rounded-full file:border-0
                                file:text-sm file:font-semibold
                                file:bg-blue-50 file:text-blue-700
                                hover:file:bg-blue-100"
                            />
                        </div>
                    </div>

                    <Tabs 
                        defaultActiveKey="uz" 
                        className="mb-4"
                        items={[
                            {
                                key: 'uz',
                                label: <span className="text-emerald-600 font-medium">O'zbekcha</span>,
                                children: (
                                    <Form.Item name="question_uz" label="Savol matni (O'zbek)" rules={[{ required: true, message: 'Majburiy!' }]}>
                                        <Input.TextArea rows={3} placeholder="Savol matnini kiriting..." />
                                    </Form.Item>
                                )
                            },
                            {
                                key: 'ru',
                                label: <span className="text-blue-600 font-medium">Русский</span>,
                                children: (
                                    <Form.Item name="question_ru" label="Savol matni (Rus)">
                                        <Input.TextArea rows={3} placeholder="Введите текст вопроса..." />
                                    </Form.Item>
                                )
                            },
                            {
                                key: 'kiril',
                                label: <span className="text-purple-600 font-medium">Kiril</span>,
                                children: (
                                    <Form.Item name="question_kiril" label="Savol matni (Kiril)">
                                        <Input.TextArea rows={3} placeholder="Савол матнини киритинг..." />
                                    </Form.Item>
                                )
                            }
                        ]}
                    />

                    <div className="bg-slate-50 p-4 rounded-xl border border-slate-200 mb-4">
                        <h4 className="font-bold text-slate-700 mb-4">Savol variantlari</h4>
                        <Form.Item name="correct_option" label="To'g'ri javobni tanlang">
                            <Radio.Group className="w-full">
                                <div className="space-y-4">
                                    {[1, 2, 3, 4].map(num => (
                                        <div key={num} className="flex flex-col space-y-2 p-3 bg-white rounded-lg border border-slate-200">
                                            <div className="flex items-center">
                                                <Radio value={num} className="font-bold text-slate-700">Variant {num}</Radio>
                                            </div>
                                            <div className="grid grid-cols-1 md:grid-cols-3 gap-2">
                                                <Form.Item name={`opt${num}_uz`} className="mb-0" rules={[{ required: num <= 2, message: 'Majburiy' }]}>
                                                    <Input placeholder="O'zbekcha" size="small" />
                                                </Form.Item>
                                                <Form.Item name={`opt${num}_ru`} className="mb-0">
                                                    <Input placeholder="Русский" size="small" />
                                                </Form.Item>
                                                <Form.Item name={`opt${num}_kiril`} className="mb-0">
                                                    <Input placeholder="Кирил" size="small" />
                                                </Form.Item>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </Radio.Group>
                        </Form.Item>
                    </div>

                    <div className="space-y-4">
                        <h4 className="font-bold text-slate-700">Qo'shimcha ma'lumot (Tushuntirish)</h4>
                        <Form.Item name="answer_description" label="Izoh / Tushuntirish matni">
                            <Input.TextArea rows={2} placeholder="To'g'ri javob uchun izoh (ixtiyoriy)" />
                        </Form.Item>
                        <Form.Item name="answer_resource" label="Video linki (URL)">
                            <Input placeholder="Masalan: https://youtube.com/..." />
                        </Form.Item>
                    </div>
                </Form>
            </Modal>

            {/* Add Template Modal */}
            <Modal
                title={
                    <div className="flex items-center space-x-2">
                        <Plus size={20} className="text-blue-600" />
                        <span>Yangi shablon yaratish</span>
                    </div>
                }
                open={isTemplateModalVisible}
                onOk={handleTemplateSubmit}
                confirmLoading={templateSubmitting}
                onCancel={() => setIsTemplateModalVisible(false)}
                okText="Saqlash"
                cancelText="Bekor qilish"
                className="rounded-2xl"
            >
                <Form form={templateForm} layout="vertical" className="mt-4" initialValues={{ type: 'exam', duration_minutes: 25, passing_score: 18 }}>
                    <Form.Item name="name" label="Shablon nomi" rules={[{ required: true, message: 'Majburiy!' }]}>
                        <Input placeholder="Masalan: BC kategoriyasi uchun yakuniy test" />
                    </Form.Item>
                    
                    <Form.Item name="type" label="Shablon turi" rules={[{ required: true, message: 'Majburiy!' }]}>
                        <Select>
                            <Select.Option value="exam">Imtihon (Exam)</Select.Option>
                            <Select.Option value="practice">Mashq (Practice)</Select.Option>
                        </Select>
                    </Form.Item>
                    
                    <div className="grid grid-cols-2 gap-4">
                        <Form.Item name="duration_minutes" label="Davomiyligi (daqiqa)" rules={[{ required: true, message: 'Majburiy!' }]}>
                            <Input type="number" min={1} />
                        </Form.Item>
                        <Form.Item name="passing_score" label="O'tish balli" rules={[{ required: true, message: 'Majburiy!' }]}>
                            <Input type="number" min={1} />
                        </Form.Item>
                    </div>
                </Form>
            </Modal>

            {/* Shablon Edit Modal */}
            <Modal
                title="Shablon tafsilotlarini tahrirlash"
                open={isShablonEditModalVisible}
                onOk={handleShablonEditSubmit}
                confirmLoading={shablonEditSubmitting}
                onCancel={() => setIsShablonEditModalVisible(false)}
                okText="Saqlash"
                cancelText="Bekor qilish"
                className="rounded-2xl"
            >
                <Form form={shablonEditForm} layout="vertical" className="mt-4">
                    <Form.Item name="name" label="Shablon nomi" rules={[{ required: true, message: 'Majburiy!' }]}>
                        <Input />
                    </Form.Item>
                    <div className="grid grid-cols-2 gap-4">
                        <Form.Item name="duration_minutes" label="Davomiyligi (daqiqa)" rules={[{ required: true, message: 'Majburiy!' }]}>
                            <Input type="number" min={1} />
                        </Form.Item>
                        <Form.Item name="passing_score" label="O'tish balli" rules={[{ required: true, message: 'Majburiy!' }]}>
                            <Input type="number" min={0} />
                        </Form.Item>
                    </div>
                </Form>
            </Modal>

            {/* JSON Test Import Modal */}
            <Modal
                title={
                    <div className="flex items-center space-x-2">
                        <UploadCloud size={20} className="text-indigo-600" />
                        <span>JSON Fayl orqali test import qilish</span>
                    </div>
                }
                open={isImportModalVisible}
                onOk={handleImportSubmit}
                confirmLoading={importSubmitting}
                onCancel={() => setIsImportModalVisible(false)}
                okText="Import qilish"
                cancelText="Bekor qilish"
                className="rounded-2xl"
            >
                <Form form={importForm} layout="vertical" className="mt-4">
                    <p className="text-slate-500 text-sm mb-4">
                        Tillar bo'yicha JSON fayllarni yuklang. Savollar avtomatik ravishda moslashtirilib, shablon testlar davomiga qo'shiladi (maksimal 20 tadan chunk qilinadi).
                    </p>
                    
                    <Form.Item
                        name="uz_file"
                        label="O'zbekcha test JSON fayli (uz.json)"
                        valuePropName="fileList"
                        getValueFromEvent={e => Array.isArray(e) ? e : e?.fileList}
                        rules={[{ required: true, message: 'Kamida O\'zbekcha fayl yuklanishi shart!' }]}
                    >
                        <Upload dragger beforeUpload={() => false} maxCount={1} accept=".json">
                            <div className="p-4 border border-dashed border-slate-300 rounded-lg text-center cursor-pointer hover:border-indigo-500 transition-colors">
                                <p className="flex justify-center text-slate-400 mb-2"><UploadCloud size={30} /></p>
                                <p className="text-sm text-slate-600">Faylni yuklash uchun bosing yoki tortib keling</p>
                            </div>
                        </Upload>
                    </Form.Item>

                    <Form.Item
                        name="ru_file"
                        label="Ruscha test JSON fayli (ru.json)"
                        valuePropName="fileList"
                        getValueFromEvent={e => Array.isArray(e) ? e : e?.fileList}
                    >
                        <Upload dragger beforeUpload={() => false} maxCount={1} accept=".json">
                            <div className="p-4 border border-dashed border-slate-300 rounded-lg text-center cursor-pointer hover:border-indigo-500 transition-colors">
                                <p className="flex justify-center text-slate-400 mb-2"><UploadCloud size={30} /></p>
                                <p className="text-sm text-slate-600">Faylni yuklash uchun bosing yoki tortib keling</p>
                            </div>
                        </Upload>
                    </Form.Item>

                    <Form.Item
                        name="kiril_file"
                        label="Kirilcha test JSON fayli (kiril.json)"
                        valuePropName="fileList"
                        getValueFromEvent={e => Array.isArray(e) ? e : e?.fileList}
                    >
                        <Upload dragger beforeUpload={() => false} maxCount={1} accept=".json">
                            <div className="p-4 border border-dashed border-slate-300 rounded-lg text-center cursor-pointer hover:border-indigo-500 transition-colors">
                                <p className="flex justify-center text-slate-400 mb-2"><UploadCloud size={30} /></p>
                                <p className="text-sm text-slate-600">Faylni yuklash uchun bosing yoki tortib keling</p>
                            </div>
                        </Upload>
                    </Form.Item>
                </Form>
            </Modal>

            {/* Shablon Question Edit Modal */}
            <Modal
                title="Shablon savolini tahrirlash"
                open={isQuestionEditModalVisible}
                onOk={handleQuestionEditSubmit}
                confirmLoading={questionEditSubmitting}
                onCancel={() => setIsQuestionEditModalVisible(false)}
                width={900}
                okText="Saqlash"
                cancelText="Bekor qilish"
                className="rounded-2xl"
            >
                <Form form={questionEditForm} layout="vertical" className="mt-4">
                    <Form.Item name="image_path" label="Savol rasmi linki (ixtiyoriy)">
                        <Input placeholder="Masalan: /test_files/img/newtest_questions/..." />
                    </Form.Item>

                    <Tabs
                        defaultActiveKey="uz"
                        className="mb-4"
                        items={[
                            {
                                key: 'uz',
                                label: <span className="text-emerald-600 font-medium">O'zbekcha</span>,
                                children: (
                                    <div className="space-y-4">
                                        <Form.Item name="question_uz" label="Savol matni (O'zbek)" rules={[{ required: true }]}>
                                            <Input.TextArea rows={3} />
                                        </Form.Item>
                                        <div className="grid grid-cols-2 gap-4">
                                            <Form.Item name="answer_description_uz" label="Tushuntirish / Izoh (O'zbek)">
                                                <Input.TextArea rows={2} />
                                            </Form.Item>
                                            <Form.Item name="answer_resource_uz" label="Video linki (O'zbek)">
                                                <Input placeholder="URL" />
                                            </Form.Item>
                                        </div>
                                    </div>
                                )
                            },
                            {
                                key: 'ru',
                                label: <span className="text-blue-600 font-medium">Русский</span>,
                                children: (
                                    <div className="space-y-4">
                                        <Form.Item name="question_ru" label="Savol matni (Rus)">
                                            <Input.TextArea rows={3} />
                                        </Form.Item>
                                        <div className="grid grid-cols-2 gap-4">
                                            <Form.Item name="answer_description_ru" label="Объяснение (Rus)">
                                                <Input.TextArea rows={2} />
                                            </Form.Item>
                                            <Form.Item name="answer_resource_ru" label="Видео ссылка (Rus)">
                                                <Input placeholder="URL" />
                                            </Form.Item>
                                        </div>
                                    </div>
                                )
                            },
                            {
                                key: 'kiril',
                                label: <span className="text-purple-600 font-medium">Kiril</span>,
                                children: (
                                    <div className="space-y-4">
                                        <Form.Item name="question_kiril" label="Savol matni (Kiril)">
                                            <Input.TextArea rows={3} />
                                        </Form.Item>
                                        <div className="grid grid-cols-2 gap-4">
                                            <Form.Item name="answer_description_kiril" label="Тушунтириш (Kiril)">
                                                <Input.TextArea rows={2} />
                                            </Form.Item>
                                            <Form.Item name="answer_resource_kiril" label="Видео ҳавола (Kiril)">
                                                <Input placeholder="URL" />
                                            </Form.Item>
                                        </div>
                                    </div>
                                )
                            }
                        ]}
                    />

                    <div className="bg-slate-50 p-4 rounded-xl border border-slate-200 mb-4">
                        <h4 className="font-bold text-slate-700 mb-4">Savol variantlari va to'g'riligi</h4>
                        
                        <Form.Item name="correct_option" label="To'g'ri javobni belgilang">
                            <Radio.Group className="w-full">
                                <div className="space-y-4">
                                    {editingOptions.map((opt, idx) => (
                                        <div key={opt.id || idx} className="flex flex-col space-y-2 p-3 bg-white rounded-lg border border-slate-200">
                                            <div className="flex items-center">
                                                <Radio value={idx} className="font-bold text-slate-700">Variant {idx + 1}</Radio>
                                                <Form.Item name={`opt${idx}_id`} hidden><Input /></Form.Item>
                                            </div>
                                            <div className="grid grid-cols-1 md:grid-cols-3 gap-2">
                                                <Form.Item name={`opt${idx}_uz`} label="O'zbekcha" className="mb-0" rules={[{ required: true, message: 'Majburiy!' }]}>
                                                    <Input size="small" />
                                                </Form.Item>
                                                <Form.Item name={`opt${idx}_ru`} label="Русский" className="mb-0">
                                                    <Input size="small" />
                                                </Form.Item>
                                                <Form.Item name={`opt${idx}_kiril`} label="Киril" className="mb-0">
                                                    <Input size="small" />
                                                </Form.Item>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </Radio.Group>
                        </Form.Item>
                    </div>
                </Form>
            </Modal>
        </div>
    );
};

export default Tests;


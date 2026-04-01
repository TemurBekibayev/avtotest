import { Routes, Route, Navigate } from 'react-router-dom';
import AdminLayout from './layouts/AdminLayout';
import AuthLayout from './layouts/AuthLayout';
import Dashboard from './pages/Dashboard';
import Login from './pages/Login';

import Students from './pages/Students';
import Groups from './pages/Groups';
import Teachers from './pages/Teachers';
import Vehicles from './pages/Vehicles';
import Finance from './pages/Finance';
import Tests from './pages/Tests';

function App() {
  return (
    <Routes>
      {/* Auth Routes */}
      <Route element={<AuthLayout />}>
        <Route path="/login" element={<Login />} />
      </Route>

      {/* Admin Routes */}
      <Route element={<AdminLayout />}>
        <Route path="/" element={<Navigate to="/dashboard" replace />} />
        <Route path="/dashboard" element={<Dashboard />} />
        <Route path="/students" element={<Students />} />
        <Route path="/groups" element={<Groups />} />
        <Route path="/teachers" element={<Teachers />} />
        <Route path="/vehicles" element={<Vehicles />} />
        <Route path="/finance" element={<Finance />} />
        <Route path="/tests" element={<Tests />} />
      </Route>

      <Route path="*" element={<Navigate to="/dashboard" replace />} />
    </Routes>
  );
}

export default App;
